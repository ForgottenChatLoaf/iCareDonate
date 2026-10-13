<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_BASE . '/admin/admins.php');
    exit;
}
verifyCsrf();

$action = $_POST['action'] ?? '';

switch ($action) {

    // ── INVITE ────────────────────────────────────────────
    case 'invite': {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $role      = in_array($_POST['role'] ?? '', ['admin','superadmin'], true)
                     ? $_POST['role']
                     : 'admin';

        if (empty($firstName) || empty($lastName) || empty($email)) {
            setFlash('error', 'All fields are required.');
            header('Location: ' . APP_BASE . '/admin/admins.php');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Invalid email address.');
            header('Location: ' . APP_BASE . '/admin/admins.php');
            exit;
        }

        // Check if email already has an admin account
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = ? AND role IN (\'admin\',\'superadmin\')');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            setFlash('error', 'This email already has an admin account.');
            header('Location: ' . APP_BASE . '/admin/admins.php');
            exit;
        }

        // Revoke any existing pending invitations for this email
        $pdo->prepare("UPDATE admin_invitations SET status = 'revoked' WHERE email = ? AND status = 'pending'")
            ->execute([$email]);

        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+48 hours'));

        $stmt = $pdo->prepare("
            INSERT INTO admin_invitations (email, first_name, last_name, role, token, invited_by, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$email, $firstName, $lastName, $role, $token, $_SESSION['user_id'], $expiresAt]);

        logAudit(
            $pdo, (int)$_SESSION['user_id'], $_SESSION['role'],
            'ADMIN_INVITE_SENT', 'admin_invitations', (int)$pdo->lastInsertId(),
            null, null, "Invited {$email} as {$role}"
        );

        setFlash('success', "Invitation created for {$firstName} {$lastName} ({$email}). Copy the link below and share it with them.");
        header('Location: ' . APP_BASE . '/admin/admins.php');
        exit;
    }

    // ── REVOKE INVITATION ─────────────────────────────────
    case 'revoke_invite': {
        $inviteId = (int)($_POST['invite_id'] ?? 0);
        if ($inviteId <= 0) {
            setFlash('error', 'Invalid invitation.');
            header('Location: ' . APP_BASE . '/admin/admins.php');
            exit;
        }

        $pdo->prepare("UPDATE admin_invitations SET status = 'revoked' WHERE id = ? AND status = 'pending'")
            ->execute([$inviteId]);

        logAudit($pdo, (int)$_SESSION['user_id'], $_SESSION['role'],
            'ADMIN_INVITE_REVOKED', 'admin_invitations', $inviteId, null, null, 'Invitation revoked');

        setFlash('success', 'Invitation has been revoked.');
        header('Location: ' . APP_BASE . '/admin/admins.php');
        exit;
    }

    // ── SUSPEND ADMIN ─────────────────────────────────────
    case 'suspend': {
        $adminId = (int)($_POST['admin_id'] ?? 0);
        if ($adminId <= 0 || $adminId === (int)$_SESSION['user_id']) {
            setFlash('error', 'Invalid action.');
            header('Location: ' . APP_BASE . '/admin/admins.php');
            exit;
        }

        // Protect superadmins from being suspended by other superadmins
        $target = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $target->execute([$adminId]);
        $targetUser = $target->fetch();
        if (!$targetUser || $targetUser['role'] === 'superadmin') {
            setFlash('error', 'Cannot suspend a superadmin account.');
            header('Location: ' . APP_BASE . '/admin/admins.php');
            exit;
        }

        $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?")->execute([$adminId]);
        logAudit($pdo, (int)$_SESSION['user_id'], $_SESSION['role'],
            'ADMIN_SUSPENDED', 'users', $adminId, null, null, 'Admin account suspended by superadmin');

        setFlash('success', 'Admin account has been suspended.');
        header('Location: ' . APP_BASE . '/admin/admins.php');
        exit;
    }

    // ── REACTIVATE ADMIN ──────────────────────────────────
    case 'reactivate': {
        $adminId = (int)($_POST['admin_id'] ?? 0);
        if ($adminId <= 0) {
            setFlash('error', 'Invalid action.');
            header('Location: ' . APP_BASE . '/admin/admins.php');
            exit;
        }

        $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role IN ('admin','superadmin')")
            ->execute([$adminId]);

        logAudit($pdo, (int)$_SESSION['user_id'], $_SESSION['role'],
            'ADMIN_REACTIVATED', 'users', $adminId, null, null, 'Admin account reactivated by superadmin');

        setFlash('success', 'Admin account has been reactivated.');
        header('Location: ' . APP_BASE . '/admin/admins.php');
        exit;
    }

    // ── DEMOTE ADMIN ──────────────────────────────────────
    case 'demote': {
        $adminId = (int)($_POST['admin_id'] ?? 0);
        if ($adminId <= 0 || $adminId === (int)$_SESSION['user_id']) {
            setFlash('error', 'Invalid action.');
            header('Location: ' . APP_BASE . '/admin/admins.php');
            exit;
        }

        $target = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $target->execute([$adminId]);
        $targetUser = $target->fetch();
        if (!$targetUser || $targetUser['role'] === 'superadmin') {
            setFlash('error', 'Cannot demote a superadmin account.');
            header('Location: ' . APP_BASE . '/admin/admins.php');
            exit;
        }

        $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ? AND role = 'admin'")->execute([$adminId]);
        logAudit($pdo, (int)$_SESSION['user_id'], $_SESSION['role'],
            'ADMIN_DEMOTED', 'users', $adminId, null, null, 'Admin demoted to user by superadmin');

        setFlash('success', 'Admin has been demoted to a regular user.');
        header('Location: ' . APP_BASE . '/admin/admins.php');
        exit;
    }

    default:
        setFlash('error', 'Unknown action.');
        header('Location: ' . APP_BASE . '/admin/admins.php');
        exit;
}
