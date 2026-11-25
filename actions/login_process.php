<?php
/**
 * iCareDonate â€” Login Form Handler (POST only)
 * actions/login_process.php
 *
 * Authenticates user credentials using PDO prepared statements and password_verify().
 * Supports database persistence (MySQL/MariaDB via XAMPP) with demo account fallback.
 * Creates a secure PHP session and redirects to the appropriate portal.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

$isJson = (
    (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
    (!empty($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
);

// Helper for responses
function sendLoginResponse(bool $success, string $message, string $redirectUrl, ?array $user = null, bool $isJson = false, ?string $token = null): void {
    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($success ? 200 : 400);
        echo json_encode([
            'success'  => $success,
            'message'  => $message,
            'redirect' => $redirectUrl,
            // Only send a token on confirmed success â€” never generate an unstored fallback token.
            'token'    => ($success && $token !== null) ? $token : null,
            'user'     => $user,
        ]);
        exit;
    }

    if (!$success) {
        header('Location: ' . _icd_root() . 'login.php?error=' . urlencode($message));
        exit;
    }

    header('Location: ' . $redirectUrl);
    exit;
}

// Reject non-POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isJson) {
        sendLoginResponse(false, 'Method Not Allowed. POST required.', '', null, true);
    }
    header('Location: ' . _icd_root() . 'login.php');
    exit;
}

// Read inputs from POST or JSON body
$rawInput = file_get_contents('php://input');
$jsonBody = json_decode($rawInput, true);

$email    = trim($_POST['email'] ?? ($jsonBody['email'] ?? ''));
$password = (string)($_POST['password'] ?? ($jsonBody['password'] ?? ''));
$returnUrl= trim($_POST['redirect'] ?? ($jsonBody['redirect'] ?? ''));

// Validate fields
if ($email === '' || $password === '') {
    sendLoginResponse(false, 'Please enter both email and password.', '', null, $isJson);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendLoginResponse(false, 'Please enter a valid email address.', '', null, $isJson);
}

$matched = null;
$isProduction = (getenv('APP_ENV') === 'production');

// â”€â”€ 1. Check against MySQL database via PDO â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$pdo = getDB();
if ($pdo !== null) {
    try {
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role, status, must_change_password FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        if ($row && !empty($row['password_hash'])) {
            if (password_verify($password, $row['password_hash'])) {
                $status = strtolower((string)($row['status'] ?? 'active'));
                if ($status === 'suspended' || $status === 'banned') {
                    sendLoginResponse(false, 'Your account is currently ' . $status . '. Please contact support.', '', null, $isJson);
                }

                try {
                    $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute([':id' => (int)$row['id']]);
                } catch (PDOException $e) {}

                $nameParts = explode(' ', trim($row['name']));
                $firstName = $nameParts[0] ?? 'User';
                $lastName  = implode(' ', array_slice($nameParts, 1)) ?: '';
                $initials  = strtoupper(substr($firstName, 0, 1) . substr($lastName ?: $firstName, 0, 1));

                $validRoles = ['admin', 'head_admin', 'superadmin', 'donation_admin', 'campaign_admin', 'moderator', 'support_admin', 'requester', 'donor'];
                $dbRole = strtolower(trim((string)($row['role'] ?? 'donor')));
                $resolvedRole = in_array($dbRole, $validRoles, true) ? $dbRole : 'donor';

                $matched = [
                    'id'                   => (int)$row['id'],
                    'email'                => $row['email'],
                    'role'                 => $resolvedRole,
                    'name'                 => $row['name'],
                    'first_name'           => $firstName,
                    'last_name'            => $lastName,
                    'initials'             => $initials,
                    'must_change_password' => (bool)($row['must_change_password'] ?? false),
                ];
            }
        }
    } catch (PDOException $e) {
        error_log('Database query error in login_process: ' . $e->getMessage());
        sendLoginResponse(false, 'Database service is currently unavailable. Please try again shortly.', '', null, $isJson);
    }
} else {
    sendLoginResponse(false, 'Database service is currently unavailable. Please try again shortly.', '', null, $isJson);
}

// â”€â”€ 2. Reject if invalid â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if ($matched === null) {
    sendLoginResponse(false, 'Invalid email or password. Please try again.', '', null, $isJson);
}

// â”€â”€ 4. Successful login: Regenerate session & store state â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
session_regenerate_id(true);

$_SESSION['icd_user_id'] = $matched['id'];
$_SESSION['icd_email']   = $matched['email'];
$_SESSION['icd_role']    = $matched['role'];
$_SESSION['icd_name']    = $matched['name'];
$_SESSION['icd_initials']= $matched['initials'];
$_SESSION['icd_login_at']= time();
$_SESSION['icd_must_change_password'] = $matched['must_change_password'];

// 5. Determine redirection destination
$root = _icd_root();
if ($isJson || $isProduction) {
    // Frontend static dashboard pages
    $adminRoles = ['admin', 'head_admin', 'superadmin', 'donation_admin', 'campaign_admin', 'moderator', 'support_admin'];
    $target = in_array($matched['role'], $adminRoles, true) ? 'admin/index.html' : 'user/dashboard.html';
} else {
    // Local PHP server pages
    $adminRoles2 = ['admin', 'head_admin', 'superadmin', 'donation_admin', 'campaign_admin', 'moderator', 'support_admin'];
    $target = in_array($matched['role'], $adminRoles2, true) ? ($root . ICD_REDIRECT_ADMIN) : ($root . ICD_REDIRECT_USER);
}

// Honor safe return redirect if inside application
if ($returnUrl !== '' && !preg_match('#^(https?:)?//#i', $returnUrl)) {
    $target = $returnUrl;
}

// â”€â”€ 6. Generate and persist API session token â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// We store only the SHA-256 hash in the DB so a database leak cannot yield
// working bearer tokens. The raw token is returned to the frontend only once.
$sessionToken = 'sess-' . bin2hex(random_bytes(16));
$sessionTokenHash = hash('sha256', $sessionToken);
if ($pdo !== null) {
    try {
        $stmtSess = $pdo->prepare('INSERT INTO user_sessions (user_id, token, expires_at) VALUES (:uid, :token, DATE_ADD(NOW(), INTERVAL 30 DAY))');
        $stmtSess->execute([':uid' => $matched['id'], ':token' => $sessionTokenHash]);
    } catch (PDOException $e) {
        // user_sessions table not yet created â€” token won't validate via Bearer;
        // PHP session cookie still works on same-origin requests.
        $sessionToken = null;
    }
}

sendLoginResponse(true, 'Login successful.', $target, [
    'id'         => $matched['id'],
    'first_name' => $matched['first_name'],
    'last_name'  => $matched['last_name'],
    'email'                => $matched['email'],
    'role'                 => $matched['role'],
    'must_change_password' => $matched['must_change_password'],
], $isJson, $sessionToken);


