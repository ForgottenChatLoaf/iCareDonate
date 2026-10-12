<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /CommunityDono/admin/users.php');
    exit;
}

verifyCsrf();

$adminId = (int)$_SESSION['user_id'];
$targetUserId = (int)($_POST['user_id'] ?? 0);
$action = trim($_POST['action'] ?? '');
$reason = trim($_POST['reason'] ?? '');
$redirect = trim($_POST['redirect'] ?? '/CommunityDono/admin/users.php');

// Validate allowed redirect
if (!str_starts_with($redirect, '/CommunityDono/admin/')) {
    $redirect = '/CommunityDono/admin/users.php';
}

if ($targetUserId <= 0 || !in_array($action, ['warn', 'suspend', 'ban', 'activate'], true)) {
    setFlash('error', 'Invalid user moderation action.');
    header("Location: $redirect");
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, email, role, status FROM users WHERE id = ?');
    $stmt->execute([$targetUserId]);
    $user = $stmt->fetch();

    if (!$user || $user['role'] === 'superadmin' || (int)$user['id'] === $adminId) {
        setFlash('error', 'Cannot modify this administrative user account.');
        header("Location: $redirect");
        exit;
    }

    if ($action === 'warn') {
        if (empty($reason)) {
            $reason = 'Administrative account warning issued for policy compliance.';
        }
        createNotification(
            $pdo,
            $targetUserId,
            'moderation_warning',
            'Account Warning from Administration',
            "You have received an official account warning: $reason",
            'user',
            $targetUserId
        );
        logAudit($pdo, $adminId, 'admin', 'USER_WARNED', 'user', $targetUserId, null, ['warning' => $reason], $reason);
        setFlash('success', 'Official warning notification sent to user.');
    } elseif ($action === 'suspend') {
        if (empty($reason)) {
            $reason = 'Account temporarily suspended by administration.';
        }
        $upd = $pdo->prepare('UPDATE users SET status = "suspended", suspension_reason = ?, suspended_at = NOW(), suspended_by = ? WHERE id = ?');
        $upd->execute([$reason, $adminId, $targetUserId]);
        createNotification(
            $pdo,
            $targetUserId,
            'account_suspended',
            'Account Suspended',
            "Your account has been temporarily suspended. Reason: $reason",
            'user',
            $targetUserId
        );
        logAudit($pdo, $adminId, 'admin', 'USER_SUSPENDED', 'user', $targetUserId, ['status' => $user['status']], ['status' => 'suspended', 'reason' => $reason], $reason);
        setFlash('success', 'User account has been suspended.');
    } elseif ($action === 'ban') {
        if (empty($reason)) {
            $reason = 'Permanent account ban due to severe policy violation.';
        }
        $upd = $pdo->prepare('UPDATE users SET status = "banned", suspension_reason = ?, banned_at = NOW(), banned_by = ? WHERE id = ?');
        $upd->execute([$reason, $adminId, $targetUserId]);
        logAudit($pdo, $adminId, 'admin', 'USER_BANNED', 'user', $targetUserId, ['status' => $user['status']], ['status' => 'banned', 'reason' => $reason], $reason);
        setFlash('success', 'User account has been permanently banned.');
    } elseif ($action === 'activate') {
        $upd = $pdo->prepare('UPDATE users SET status = "active", suspension_reason = NULL, suspended_at = NULL, suspended_by = NULL, banned_at = NULL, banned_by = NULL WHERE id = ?');
        $upd->execute([$targetUserId]);
        createNotification(
            $pdo,
            $targetUserId,
            'account_reactivated',
            'Account Reactivated',
            'Your iCareDonate account is now active and in good standing.',
            'user',
            $targetUserId
        );
        logAudit($pdo, $adminId, 'admin', 'USER_STATUS_CHANGED', 'user', $targetUserId, ['status' => $user['status']], ['status' => 'active'], 'User account reactivated');
        setFlash('success', 'User account reactivated successfully.');
    }

    header("Location: $redirect");
    exit;
} catch (Exception $e) {
    error_log($e->getMessage());
    setFlash('error', 'Failed to update user account: ' . $e->getMessage());
    header("Location: $redirect");
    exit;
}
