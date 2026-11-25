<?php
/**
 * iCareDonate — Forgot Password Request Endpoint
 * actions/forgot_password.php
 *
 * Accepts an email address, generates a single-use SHA-256 hashed reset token
 * stored in password_resets (1-hour expiry), and returns a generic success
 * response to prevent email enumeration. In local development (localhost/127.0.0.1),
 * returns reset_url so the flow can be tested without an external SMTP relay.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$email = strtolower(trim((string)($payload['email'] ?? '')));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

icd_enforce_rate_limit('forgot_password', 10, 300);

$genericMsg = 'If an active account exists for that email address, a password reset link has been generated.';

try {
    $stmt = $pdo->prepare('SELECT id, name, email, status FROM users WHERE LOWER(email) = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || in_array(strtolower((string)$user['status']), ['banned', 'suspended'], true)) {
        echo json_encode(['success' => true, 'message' => $genericMsg]);
        exit;
    }

    $uid = (int)$user['id'];
    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);

    // Invalidate any prior unused tokens for this user
    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL')
        ->execute([':uid' => $uid]);

    $pdo->prepare('
        INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
        VALUES (:uid, :th, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())
    ')->execute([
        ':uid' => $uid,
        ':th'  => $tokenHash,
    ]);

    logAdminAction($pdo, 'PASSWORD_RESET_REQUESTED', $uid, (string)$user['name'], 'user', $uid, 'User requested password reset link');

    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $isLocal = str_contains($host, 'localhost') || str_starts_with($host, '127.0.0.1');

    $response = [
        'success' => true,
        'message' => $genericMsg,
    ];

    if ($isLocal) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $scriptDir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/actions/forgot_password.php')));
        $basePath = preg_replace('#/actions$#', '', $scriptDir);
        $response['reset_url'] = $scheme . '://' . $host . $basePath . '/frontend/reset-password.html?token=' . urlencode($rawToken);
    }

    echo json_encode($response);
} catch (Throwable $e) {
    error_log('forgot_password error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to process password reset request right now.']);
}
