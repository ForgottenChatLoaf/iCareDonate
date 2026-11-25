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
            $stmt = $pdo->prepare('SELECT id, name, email, role, status, must_change_password FROM users WHERE id = :uid LIMIT 1');
            $stmt->execute([':uid' => $userId]);
            $user = $stmt->fetch();

            $adminRoles = ['admin', 'head_admin', 'superadmin', 'moderator', 'donation_admin', 'campaign_admin', 'support_admin'];
            if ($user && in_array($user['role'], $adminRoles, true)) {
                if (in_array(strtolower((string)($user['status'] ?? 'active')), ['suspended', 'banned'], true)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'code' => 'ACCOUNT_DISABLED', 'message' => 'Your administrator account is currently ' . $user['status'] . '.']);
                    exit;
                }
                $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
                if (!empty($user['must_change_password']) && !in_array($script, ['user_profile.php', 'admin_dashboard.php'], true)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'code' => 'MUST_CHANGE_PASSWORD', 'message' => 'You must change your initial password before accessing administrative actions.']);
                    exit;
                }
                return [
                    'id'    => (int)$user['id'],
                    'name'  => $user['name'] ?? 'Administrator',
                    'email' => $user['email'] ?? '',
                    'role'  => $user['role'] ?? 'admin',
                ];
            }
        } catch (PDOException $e) {
            error_log('requireAdminApi DB check error: ' . $e->getMessage());
        }
    }

    // Check PHP session role directly if DB query wasn't possible or fallback
    $adminRoles = ['admin', 'head_admin', 'superadmin', 'moderator', 'donation_admin', 'campaign_admin', 'support_admin'];
    if (!empty($_SESSION['icd_user_id']) && in_array($_SESSION['icd_role'] ?? '', $adminRoles, true)) {
        return [
            'id'    => (int)$_SESSION['icd_user_id'],
            'name'  => $_SESSION['icd_name'] ?? 'System Administrator',
            'email' => $_SESSION['icd_email'] ?? '',
            'role'  => $_SESSION['icd_role'] ?? 'admin',
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
 * Enforces granular administrative role-based access control (RBAC).
 * Head Administrator roles ('admin', 'head_admin', 'superadmin') are granted
 * platform-wide access; specialized roles must appear in $allowedRoles.
 *
 * @param PDO|null $pdo
 * @param array<string> $allowedRoles
 * @return array{id: int, name: string, email: string, role: string}
 */
function requireAdminRoleApi(?PDO $pdo, array $allowedRoles): array {
    $admin = requireAdminApi($pdo);
    $headRoles = ['admin', 'head_admin', 'superadmin'];
    if (in_array($admin['role'], $headRoles, true) || in_array($admin['role'], $allowedRoles, true)) {
        return $admin;
    }

    http_response_code(403);
    echo json_encode([
        'success' => false,
        'code'    => 'FORBIDDEN_ROLE',
        'message' => 'Forbidden. Your administrator role (' . $admin['role'] . ') does not have permission for this operation.'
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
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (
                action, actor_id, actor_name, actor_role,
                target_type, target_id, details, reason, ip_address, created_at
            ) VALUES (
                :action, :actor_id, :actor_name, 'admin',
                :target_type, :target_id, :details, :reason, :ip, NOW()
            )
        ");
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
