<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /CommunityDono/pages/login.php');
    exit;
}

verifyCsrf();

$token   = trim($_POST['token'] ?? '');
$newPass = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (empty($token) || strlen($newPass) < 8 || $newPass !== $confirm) {
    setFlash('error', 'Please ensure passwords match and are at least 8 characters long.');
    header("Location: /CommunityDono/pages/reset-password.php?token=" . urlencode($token));
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        SELECT user_id, expires_at, used_at FROM password_resets
        WHERE token = ? AND used_at IS NULL AND expires_at > NOW()
        LIMIT 1
        FOR UPDATE
    ');
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        throw new Exception('Invalid or expired password reset token.');
    }

    $userId = (int)$reset['user_id'];
    $hash = password_hash($newPass, PASSWORD_BCRYPT);

    // Update user password
    $updUser = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
    $updUser->execute([$hash, $userId]);

    // Mark token used
    $updToken = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE token = ?');
    $updToken->execute([$token]);

    logAudit($pdo, $userId, 'user', 'PASSWORD_RESET_COMPLETED', 'user', $userId, null, null, 'Successfully reset password');

    $pdo->commit();

    setFlash('success', 'Your password has been reset successfully! You can now log in.');
    header('Location: /CommunityDono/pages/login.php');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    setFlash('error', 'Failed to reset password: ' . $e->getMessage());
    header('Location: /CommunityDono/pages/forgot-password.php');
    exit;
}
