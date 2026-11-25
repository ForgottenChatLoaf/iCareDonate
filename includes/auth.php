<?php
/**
 * iCareDonate — Session Authentication Helpers
 * includes/auth.php
 *
 * Provides requireLogin(), requireAdmin(), and isLoggedIn().
 * Include this file at the top of any PHP page that needs protection.
 *
 * Usage:
 *   require_once __DIR__ . '/../includes/auth.php';
 *   requireLogin();   // Redirects to login.php if not authenticated
 *   requireAdmin();   // Redirects to 403.html if not admin role
 */

require_once __DIR__ . '/cors.php';

// Start session if one is not already active
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => 0,          // Session cookie — expires when browser closes
        'path'     => '/',
        'httponly' => true,       // Not accessible to JavaScript
        'samesite' => $isSecure ? 'None' : 'Lax', // 'None' permits cross-origin API credentials over HTTPS
        'secure'   => $isSecure,
    ]);
    session_start();
}

/**
 * Safely resolves the authenticated user ID from server-side session or verified token.
 * Never trusts a client-supplied user ID parameter.
 */
function getAuthenticatedUserId(): ?int {
    // 1. Check PHP Session
    if (!empty($_SESSION['icd_user_id'])) {
        return (int)$_SESSION['icd_user_id'];
    }

    // 2. Check Authorization Bearer header
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ($authHeader === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? '');
    }

    if ($authHeader !== '' && preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        $token = $matches[1];
        if (function_exists('getDB')) {
            $pdo = getDB();
            if ($pdo !== null) {
                try {
                    // DB stores SHA-256(token); compare hash so raw tokens never live in DB.
                    $tokenHash = hash('sha256', $token);
                    $stmt = $pdo->prepare('SELECT user_id FROM user_sessions WHERE token = :token AND expires_at > NOW() LIMIT 1');
                    $stmt->execute([':token' => $tokenHash]);
                    $row = $stmt->fetch();
                    if ($row) {
                        $uid = (int)$row['user_id'];
                        $_SESSION['icd_user_id'] = $uid;
                        return $uid;
                    }
                } catch (PDOException $e) {
                    // Graceful fallback if table is not yet created
                }
            }
        }
    }

    return null;
}

/**
 * Redirect to login.php if the user is not authenticated.
 * Call this at the top of any protected page.
 */
function requireLogin(): void {
    if (empty($_SESSION['icd_user_id']) || empty($_SESSION['icd_role'])) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header('Location: ' . _icd_root() . 'login.php' . ($redirect ? '?redirect=' . $redirect : ''));
        exit;
    }
}

/**
 * Redirect to 403.html if the user is not authenticated as an admin.
 * Call this at the top of any admin-only page.
 */
function requireAdmin(): void {
    requireLogin();
    $adminRoles = ['admin', 'head_admin', 'superadmin', 'donation_admin', 'campaign_admin', 'moderator', 'support_admin'];
    if (!in_array($_SESSION['icd_role'] ?? '', $adminRoles, true)) {
        header('Location: ' . _icd_root() . 'frontend/403.html');
        exit;
    }
}

/**
 * Returns true if a valid session exists.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['icd_user_id']) && !empty($_SESSION['icd_role']);
}

/**
 * Returns the authenticated user's role, or null if not logged in.
 */
function currentRole(): ?string {
    return $_SESSION['icd_role'] ?? null;
}

/**
 * Returns the authenticated user's display name, or null if not logged in.
 */
function currentName(): ?string {
    return $_SESSION['icd_name'] ?? null;
}

/**
 * Returns (and lazily creates) the per-session CSRF token.
 */
function icd_get_csrf_token(): string {
    if (empty($_SESSION['icd_csrf_token'])) {
        $_SESSION['icd_csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['icd_csrf_token'];
}

/**
 * Verifies a CSRF token for state-changing requests.
 * Requests authenticated via Authorization: Bearer <token> are exempt from cookie-CSRF.
 */
function icd_verify_csrf_request(?array $payload = null): bool {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ($authHeader !== '' && preg_match('/Bearer\s+\S+/i', $authHeader)) {
        return true;
    }
    if (empty($_SESSION['icd_csrf_token'])) {
        return true; // No browser CSRF token established yet (e.g. first stateless POST)
    }
    $clientToken = $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? ($_POST['_csrf'] ?? ($payload['_csrf'] ?? ''));
    if (!is_string($clientToken) || $clientToken === '') {
        return false;
    }
    return hash_equals((string)$_SESSION['icd_csrf_token'], $clientToken);
}

/**
 * Validates password strength according to platform security policy.
 * Returns null if valid, or a user-facing error message if invalid.
 */
function icd_validate_password_policy(string $password): ?string {
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one letter and one number.';
    }
    if (trim($password) === '') {
        return 'Password cannot be empty or whitespace.';
    }
    return null;
}

/**
 * Invalidates all stored API sessions for a user (optionally keeping the current Bearer token).
 */
function icd_invalidate_user_sessions(PDO $pdo, int $userId, ?string $keepRawToken = null): void {
    try {
        if ($keepRawToken !== null && $keepRawToken !== '') {
            $keepHash = hash('sha256', $keepRawToken);
            $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE user_id = :uid AND token != :keep');
            $stmt->execute([':uid' => $userId, ':keep' => $keepHash]);
        } else {
            $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE user_id = :uid');
            $stmt->execute([':uid' => $userId]);
        }
    } catch (PDOException $e) {
        error_log('icd_invalidate_user_sessions error: ' . $e->getMessage());
    }
}

/**
 * Helper: resolves the project root URL path so redirects work from any depth.
 *
 * On Render (APP_ENV=production or APP_URL is set), the app IS the document
 * root, so this returns '/'.
 * On XAMPP (subdirectory install), it computes the subpath automatically.
 */
function _icd_root(): string {
    // Production: app served from document root (Render, VPS, etc.)
    $appEnv = getenv('APP_ENV') ?: '';
    $appUrl = getenv('APP_URL') ?: '';
    if ($appEnv === 'production' || $appUrl !== '') {
        return '/';
    }

    // Local XAMPP: compute subdirectory path
    $dir     = __DIR__; // iCareDonate/includes/
    $root    = dirname($dir); // iCareDonate/
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');

    $rootNorm = str_replace('\\', '/', $root);
    $docNorm  = str_replace('\\', '/', $docRoot);

    if ($docNorm !== '' && stripos($rootNorm, $docNorm) === 0) {
        $urlPath = substr($rootNorm, strlen($docNorm));
    } else {
        $urlPath = '/iCareDonate';
    }

    return '/' . trim($urlPath, '/') . '/';
}
