<?php
/**
 * iCareDonate — Secure Token-Based Password Reset Endpoint
 * actions/reset_password.php
 *
 * Validates a single-use SHA-256 hashed reset token from password_resets,
 * enforces password policy, updates password_hash, clears must_change_password,
 * invalidates all existing sessions, and logs the completion.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/admin_auth.php';

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

$token    = trim((string)($payload['token'] ?? ''));
$password = (string)($payload['password'] ?? ($payload['new_password'] ?? ''));

if ($token === '' || strlen($token) < 32) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing password reset token.']);
    exit;
}

$pwErr = icd_validate_password_policy($password);
if ($pwErr !== null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $pwErr]);
    exit;
}

$tokenHash = hash('sha256', $token);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT pr.id AS reset_id, pr.user_id, pr.expires_at, pr.used_at, u.name, u.email, u.status
        FROM password_resets pr
        INNER JOIN users u ON u.id = pr.user_id
        WHERE pr.token_hash = :th
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([':th' => $tokenHash]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This password reset link is invalid.']);
        exit;
    }

    if ($row['used_at'] !== null) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This password reset link has already been used.']);
        exit;
    }

    if (strtotime((string)$row['expires_at']) < time()) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This password reset link has expired. Please request a new one.']);
        exit;
    }

    if (in_array(strtolower((string)$row['status']), ['banned', 'suspended'], true)) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'This account is currently ' . $row['status'] . '.']);
        exit;
    }

    $uid = (int)$row['user_id'];
    $newHash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->prepare("UPDATE users SET password_hash = :hash, must_change_password = 0, updated_at = NOW() WHERE id = :uid")
        ->execute([':hash' => $newHash, ':uid' => $uid]);

    $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :rid")
        ->execute([':rid' => (int)$row['reset_id']]);

    icd_invalidate_user_sessions($pdo, $uid, null);

    $pdo->commit();

    logAdminAction($pdo, 'PASSWORD_RESET_COMPLETED', $uid, (string)$row['name'], 'user', $uid, 'Password reset completed via single-use token');

    echo json_encode([
        'success' => true,
        'message' => 'Your password has been reset successfully. You may now sign in with your new password.'
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('reset_password error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to complete password reset.']);
}
