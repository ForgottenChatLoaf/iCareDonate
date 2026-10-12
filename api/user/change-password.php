<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_BASE . '/user/settings.php?tab=password');
    exit;
}

verifyCsrf();

$userId = (int)$_SESSION['user_id'];
$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (empty($current) || strlen($new) < 8 || $new !== $confirm) {
    setFlash('error', 'Please verify your current password and ensure the new password is at least 8 characters.');
    header('Location: ' . APP_BASE . '/user/settings.php?tab=password');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current, $user['password_hash'])) {
        setFlash('error', 'Incorrect current password.');
        header('Location: ' . APP_BASE . '/user/settings.php?tab=password');
        exit;
    }

    $hash = password_hash($new, PASSWORD_BCRYPT);
    $upd = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
    $upd->execute([$hash, $userId]);

    logAudit($pdo, $userId, 'user', 'PASSWORD_CHANGED', 'user', $userId, null, null, 'User changed password via account settings');

    setFlash('success', 'Password updated successfully!');
    header('Location: ' . APP_BASE . '/user/settings.php?tab=password');
    exit;
} catch (Exception $e) {
    error_log($e->getMessage());
    setFlash('error', 'Failed to update password.');
    header('Location: ' . APP_BASE . '/user/settings.php?tab=password');
    exit;
}
