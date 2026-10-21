<?php
/**
 * iCareDonate — Administrative Session & Authorization Helpers
 * includes/admin_auth.php
 *
 * Verifies authenticated administrator access for API endpoints and actions.
 * Enforces server-side authorization and provides audit logging.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

/**
 * Ensures the requesting client is an authenticated Administrator.
 * Returns the admin user record array or sends a 401/403 JSON response and exits.
 *
 * @param PDO|null $pdo
 * @return array{id: int, name: string, email: string, role: string}
 */
function requireAdminApi(?PDO $pdo = null): array {
    if ($pdo === null) {
        $pdo = getDB();
    }

    $userId = getAuthenticatedUserId();

    // In local development or when session exists
    if ($userId !== null && $pdo !== null) {
        try {
            $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = :uid LIMIT 1');
            $stmt->execute([':uid' => $userId]);
            $user = $stmt->fetch();

            if ($user && ($user['role'] === 'admin' || $user['role'] === 'superadmin')) {
                return [
                    'id'    => (int)$user['id'],
                    'name'  => $user['name'] ?? 'Admin Officer',
                    'email' => $user['email'] ?? 'admin@icaredonate.test',
                    'role'  => $user['role'] ?? 'admin',
                ];
            }
        } catch (PDOException $e) {
            error_log('requireAdminApi DB check error: ' . $e->getMessage());
        }
    }

    // Check PHP session role directly if DB query wasn't possible or fallback
    if (!empty($_SESSION['icd_user_id']) && ($_SESSION['icd_role'] ?? '') === 'admin') {
        return [
            'id'    => (int)$_SESSION['icd_user_id'],
            'name'  => $_SESSION['icd_name'] ?? 'System Administrator',
            'email' => $_SESSION['icd_email'] ?? 'admin@icaredonate.org',
            'role'  => 'admin',
        ];
    }

    // Unauthorized
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Administrator authentication required to perform this action.'
    ]);
    exit;
}

/**
 * Persists an administrative action to the audit_logs table.
 */
function logAdminAction(
    ?PDO $pdo,
    string $action,
    ?int $actorId,
    string $actorName,
    string $targetType,
    ?int $targetId,
    ?string $details = null,
    ?string $reason = null
): void {
    if ($pdo === null) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    try {
        $stmt = $pdo->prepare('
            INSERT INTO audit_logs (
                action, actor_id, actor_name, actor_role,
                target_type, target_id, details, reason, ip_address, created_at
            ) VALUES (
                :action, :actor_id, :actor_name, "admin",
                :target_type, :target_id, :details, :reason, :ip, NOW()
            )
        ');
        $stmt->execute([
            ':action'      => $action,
            ':actor_id'    => $actorId,
            ':actor_name'  => $actorName,
            ':target_type' => $targetType,
            ':target_id'   => $targetId,
            ':details'     => $details,
            ':reason'      => $reason,
            ':ip'          => $ip,
        ]);
    } catch (PDOException $e) {
        // Fail gracefully without crashing the main action if table is not yet created
        error_log('Failed to write audit log: ' . $e->getMessage());
    }
}
