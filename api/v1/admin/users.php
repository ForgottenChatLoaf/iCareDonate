<?php
declare(strict_types=1);
/**
 * api/v1/admin/users.php
 * GET        - List all registered users
 * POST/PATCH - Moderate user status (active, suspended, banned)
 */
require_once __DIR__ . '/../bootstrap.php';

$admin = requireAdmin($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $sql = '
            SELECT id, CONCAT(first_name, " ", last_name) AS name, email,
                   role, status, phone, city, created_at, last_login_at
            FROM users
            ORDER BY created_at DESC
        ';
        $stmt = $pdo->query($sql);
        $users = $stmt->fetchAll();

        jsonOk(['data' => $users]);
    } catch (Exception $e) {
        jsonError('Failed to fetch users: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST' || $method === 'PATCH') {
    $data = getBody();
    $targetId = (int)($data['id'] ?? ($data['user_id'] ?? 0));
    $newStatus = trim($data['status'] ?? '');
    $reason = trim($data['reason'] ?? '');

    if ($targetId <= 0 || empty($newStatus)) {
        jsonError('User ID and status are required.');
    }

    if (!in_array($newStatus, ['active', 'suspended', 'banned', 'pending_verification'], true)) {
        jsonError('Invalid status value.');
    }

    // Protect superadmin accounts from lower admin modifications
    $chkStmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $chkStmt->execute([$targetId]);
    $targetRole = $chkStmt->fetchColumn();

    if ($targetRole === 'superadmin' && $admin['role'] !== 'superadmin') {
        jsonError('You cannot moderate a superadmin account.', 403);
    }

    try {
        $stmt = $pdo->prepare('
            UPDATE users SET
                status = ?,
                suspension_reason = CASE WHEN ? = "suspended" THEN ? ELSE suspension_reason END,
                suspended_at = CASE WHEN ? = "suspended" THEN NOW() ELSE suspended_at END,
                suspended_by = CASE WHEN ? = "suspended" THEN ? ELSE suspended_by END,
                banned_at = CASE WHEN ? = "banned" THEN NOW() ELSE banned_at END,
                banned_by = CASE WHEN ? = "banned" THEN ? ELSE banned_by END,
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $newStatus,
            $newStatus,
            $reason,
            $newStatus,
            $newStatus,
            $admin['id'],
            $newStatus,
            $newStatus,
            $admin['id'],
            $targetId
        ]);

        // Audit log
        $audit = $pdo->prepare('
            INSERT INTO audit_logs (actor_id, actor_role, action, target_type, target_id, new_value, reason, ip_address, created_at)
            VALUES (?, ?, "moderate_user_status", "user", ?, ?, ?, ?, NOW())
        ');
        $audit->execute([
            $admin['id'],
            $admin['role'],
            $targetId,
            json_encode(['status' => $newStatus]),
            $reason,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);

        jsonOk(['message' => "User status updated to {$newStatus}."]);
    } catch (Exception $e) {
        jsonError('Failed to update user status: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
