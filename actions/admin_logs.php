<?php
/**
 * iCareDonate — Administrative Audit Trail & Activity Logs Endpoint
 * actions/admin_logs.php
 *
 * Retrieves genuine persistent audit trail records from MySQL audit_logs.
 * Supports action filtering, date filtering, actor search, and pagination.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error'   => 'Database connection is currently unavailable.',
        'code'    => 'DB_UNAVAILABLE'
    ]);
    exit;
}

$admin = requireAdminApi($pdo);

$action    = trim((string)($_GET['action'] ?? ''));
$startDate = trim((string)($_GET['from'] ?? ''));
$endDate   = trim((string)($_GET['to'] ?? ''));
$actor     = trim((string)($_GET['actor'] ?? ''));
$page      = max(1, (int)($_GET['page'] ?? 1));
$limit     = min(1000, max(1, (int)($_GET['limit'] ?? 15)));
$offset    = ($page - 1) * $limit;

try {
    $hasTable = false;
    try {
        $c = $pdo->query("SHOW TABLES LIKE 'audit_logs'");
        $hasTable = ($c && $c->fetch() !== false);
    } catch (PDOException $e) {}

    if (!$hasTable) {
        echo json_encode([
            'success'     => true,
            'total'       => 0,
            'page'        => 1,
            'total_pages' => 1,
            'logs'        => [],
            'notice'      => 'Audit logs table has not been initialized in the database yet. Run database/migration_admin_functionalization.sql.'
        ]);
        exit;
    }

    $sql = 'SELECT * FROM audit_logs WHERE 1=1';
    $params = [];

    if ($action !== '') {
        $sql .= ' AND action = :action';
        $params[':action'] = $action;
    }

    if ($startDate !== '') {
        $sql .= ' AND DATE(created_at) >= :start';
        $params[':start'] = $startDate;
    }

    if ($endDate !== '') {
        $sql .= ' AND DATE(created_at) <= :end';
        $params[':end'] = $endDate;
    }

    if ($actor !== '') {
        $sql .= ' AND (actor_name LIKE :actor OR reason LIKE :actor OR details LIKE :actor)';
        $params[':actor'] = '%' . $actor . '%';
    }

    // Count query
    $countSql = str_replace('SELECT *', 'SELECT COUNT(*)', $sql);
    $cStmt = $pdo->prepare($countSql);
    $cStmt->execute($params);
    $totalRecords = (int)$cStmt->fetchColumn();

    // Data query
    $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();

    echo json_encode([
        'success'     => true,
        'total'       => $totalRecords,
        'page'        => $page,
        'total_pages' => max(1, (int)ceil($totalRecords / $limit)),
        'logs'        => $logs,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve audit log records.']);
}
