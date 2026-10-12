<?php
declare(strict_types=1);
/**
 * api/v1/bootstrap.php
 * Shared bootstrap for all JSON API v1 endpoints.
 * Sets JSON headers, loads DB + functions, provides helpers.
 */

// ── CORS ────────────────────────────────────────────────────────────────────
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Load core ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../config/database.php';   // $pdo
require_once __DIR__ . '/../../includes/functions.php'; // helpers

// ── JSON helpers ─────────────────────────────────────────────────────────────
function jsonOk(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $message, int $code = 400, ?array $errors = null): never {
    http_response_code($code);
    $body = ['message' => $message];
    if ($errors) $body['errors'] = $errors;
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Parse the incoming JSON or form-encoded body.
 */
function getBody(): array {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return $decoded;
    }
    return $_POST;
}

/**
 * Resolve a Bearer token from Authorization header.
 * Checks multiple sources for XAMPP/Apache/PHP-CGI compatibility.
 * Returns the token string or null.
 */
function getBearerToken(): ?string {
    // 1. Standard server var (works when .htaccess RewriteRule sets it)
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    // 2. Some PHP-CGI setups use REDIRECT_HTTP_AUTHORIZATION
    if (empty($header)) {
        $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    }

    // 3. getallheaders() works on Apache mod_php
    if (empty($header) && function_exists('getallheaders')) {
        $allHeaders = getallheaders();
        // Case-insensitive search
        foreach ($allHeaders as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                $header = $value;
                break;
            }
        }
    }

    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return trim($m[1]);
    }
    return null;
}

/**
 * Authenticate the request via Bearer token stored in user_sessions.
 * Calls jsonError(401) on failure; returns user row on success.
 */
function requireAuth(PDO $pdo): array {
    $token = getBearerToken();
    if (!$token) jsonError('Unauthenticated.', 401);

    $stmt = $pdo->prepare(
        'SELECT u.id, u.email, u.first_name, u.last_name, u.role, u.status, u.profile_photo
           FROM user_sessions s
           JOIN users u ON u.id = s.user_id
          WHERE s.id = ? AND s.expires_at > NOW()
          LIMIT 1'
    );
    $stmt->execute([hash('sha256', $token)]);
    $user = $stmt->fetch();

    if (!$user) jsonError('Token expired or invalid.', 401);
    if ($user['status'] === 'suspended') jsonError('Your account has been suspended.', 403);
    if ($user['status'] === 'banned')    jsonError('Your account has been banned.', 403);

    return $user;
}

/**
 * Require the authenticated user to be admin/superadmin.
 */
function requireAdmin(PDO $pdo): array {
    $user = requireAuth($pdo);
    if (!in_array($user['role'], ['admin', 'superadmin'], true)) {
        jsonError('Forbidden. Admin access required.', 403);
    }
    return $user;
}
