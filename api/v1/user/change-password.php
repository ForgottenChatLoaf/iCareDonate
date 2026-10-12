<?php
declare(strict_types=1);
/**
 * api/v1/user/change-password.php
 * POST - Verify current password and update to new password
 */
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user = requireAuth($pdo);
$userId = (int)$user['id'];
$data = getBody();

$currentPass = $data['current_password'] ?? '';
$newPass     = $data['new_password'] ?? ($data['password'] ?? '');
$confirmPass = $data['confirm_password'] ?? ($data['password_confirmation'] ?? '');

if (empty($currentPass) || empty($newPass)) {
    jsonError('Current password and new password are required.');
}

if (strlen($newPass) < 8) {
    jsonError('New password must be at least 8 characters long.');
}

if (!empty($confirmPass) && $newPass !== $confirmPass) {
    jsonError('New password confirmation does not match.');
}

try {
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($currentPass, $hash)) {
        jsonError('Current password is incorrect.', 422);
    }

    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
    $updateStmt = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
    $updateStmt->execute([$newHash, $userId]);

    // Audit log
    $auditStmt = $pdo->prepare('
        INSERT INTO audit_logs (actor_id, actor_role, action, target_type, target_id, ip_address, created_at)
        VALUES (?, ?, "change_password", "user", ?, ?, NOW())
    ');
    $auditStmt->execute([
        $userId,
        $user['role'],
        $userId,
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ]);

    jsonOk(['message' => 'Password changed successfully.']);
} catch (Exception $e) {
    jsonError('Failed to change password: ' . $e->getMessage(), 500);
}
