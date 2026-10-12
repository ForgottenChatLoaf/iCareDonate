<?php
declare(strict_types=1);
/**
 * api/v1/admin/logs.php
 * GET - List audit logs
 */
require_once __DIR__ . '/../bootstrap.php';

$admin = requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

try {
    $stmt = $pdo->query('
        SELECT a.id, a.actor_id, a.actor_role, a.action, a.target_type, a.target_id,
               a.reason, a.ip_address, a.created_at,
               CONCAT(u.first_name, " ", u.last_name) AS actor_name
        FROM audit_logs a
        LEFT JOIN users u ON a.actor_id = u.id
        ORDER BY a.created_at DESC
        LIMIT 200
    ');
    $logs = $stmt->fetchAll();

    jsonOk(['data' => $logs]);
} catch (Exception $e) {
    jsonError('Failed to fetch audit logs: ' . $e->getMessage(), 500);
}
