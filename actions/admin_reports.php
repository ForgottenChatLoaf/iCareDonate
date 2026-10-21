<?php
/**
 * iCareDonate — Administrative Reports & Real Analytics Endpoint
 * actions/admin_reports.php
 *
 * Aggregates live MySQL metrics and returns filtered datasets
 * for on-screen report rendering and matching CSV export.
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

$type      = trim((string)($_GET['type'] ?? 'donations'));
$startDate = trim((string)($_GET['start_date'] ?? ''));
$endDate   = trim((string)($_GET['end_date'] ?? ''));
$category  = trim((string)($_GET['category'] ?? ''));

try {
    // 1. Overall KPIs
    $kpiDonations = 0;
    $kpiRequests = 0;
    $kpiTransactions = 0;
    $kpiServed = 0;

    try {
        $kpiDonations = (int)$pdo->query('SELECT COUNT(*) FROM donations')->fetchColumn();
        $kpiRequests  = (int)$pdo->query('SELECT COUNT(*) FROM requests')->fetchColumn();
        $kpiTransactions = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'completed'")->fetchColumn();
        $kpiServed    = (int)$pdo->query("SELECT COUNT(DISTINCT requester_id) FROM requests WHERE status = 'completed'")->fetchColumn();
    } catch (PDOException $e) {}

    // 2. Fetch dataset based on requested type
    $rows = [];

    if ($type === 'donations') {
        $sql = '
            SELECT d.id, d.title, d.category, u.name AS donor_name,
                   d.quantity, d.status, DATE(d.created_at) AS date_posted
            FROM donations d
            LEFT JOIN users u ON d.donor_id = u.id
            WHERE 1=1
        ';
        $params = [];
        if ($startDate !== '') {
            $sql .= ' AND DATE(d.created_at) >= :start';
            $params[':start'] = $startDate;
        }
        if ($endDate !== '') {
            $sql .= ' AND DATE(d.created_at) <= :end';
            $params[':end'] = $endDate;
        }
        if ($category !== '') {
            $sql .= ' AND d.category = :cat';
            $params[':cat'] = $category;
        }
        $sql .= ' ORDER BY d.created_at DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll();

        foreach ($raw as $r) {
            $rows[] = [
                (int)$r['id'],
                $r['title'],
                $r['category'],
                $r['donor_name'] ?? 'Anonymous Donor',
                (int)$r['quantity'],
                $r['status'],
                $r['date_posted']
            ];
        }

    } elseif ($type === 'requests') {
        $sql = "
            SELECT r.id, COALESCE(d.title, 'Resource Request') AS item_title,
                   u.name AS requester_name,
                   r.quantity, COALESCE(r.urgency, 'Medium') AS urgency, r.status,
                   DATE(r.created_at) AS date_submitted
            FROM requests r
            LEFT JOIN users u ON r.requester_id = u.id
            LEFT JOIN donations d ON r.donation_id = d.id
            WHERE 1=1
        ";
        $params = [];
        if ($startDate !== '') {
            $sql .= ' AND DATE(r.created_at) >= :start';
            $params[':start'] = $startDate;
        }
        if ($endDate !== '') {
            $sql .= ' AND DATE(r.created_at) <= :end';
            $params[':end'] = $endDate;
        }
        $sql .= ' ORDER BY r.created_at DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll();

        foreach ($raw as $r) {
            $rows[] = [
                (int)$r['id'],
                $r['item_title'],
                $r['requester_name'] ?? 'Beneficiary',
                (int)$r['quantity'],
                $r['urgency'],
                $r['status'],
                $r['date_submitted']
            ];
        }

    } elseif ($type === 'transactions' || $type === 'claims') {
        $sql = "
            SELECT r.id, CONCAT('TX-', YEAR(r.created_at), '-', LPAD(r.id, 3, '0')) AS tx_code,
                   CONCAT('CLM-', LPAD(r.id, 3, '0')) AS claim_code,
                   COALESCE(d.title, 'Physical Item Handover') AS item_title,
                   COALESCE(donor_user.name, 'Donor') AS donor_name,
                   COALESCE(req_user.name, 'Beneficiary') AS beneficiary_name,
                   r.quantity,
                   COALESCE(d.handover_method, 'Community Drop-off') AS method,
                   r.status,
                   DATE(r.created_at) AS date_record
            FROM requests r
            LEFT JOIN users req_user ON r.requester_id = req_user.id
            LEFT JOIN donations d ON r.donation_id = d.id
            LEFT JOIN users donor_user ON d.donor_id = donor_user.id
            WHERE 1=1
        ";
        $params = [];
        if ($startDate !== '') {
            $sql .= ' AND DATE(r.created_at) >= :start';
            $params[':start'] = $startDate;
        }
        if ($endDate !== '') {
            $sql .= ' AND DATE(r.created_at) <= :end';
            $params[':end'] = $endDate;
        }
        $sql .= ' ORDER BY r.created_at DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll();

        foreach ($raw as $r) {
            if ($type === 'claims') {
                $rows[] = [
                    $r['tx_code'],
                    $r['claim_code'],
                    $r['item_title'],
                    $r['beneficiary_name'],
                    (int)$r['quantity'],
                    $r['status'],
                    $r['date_record']
                ];
            } else {
                $rows[] = [
                    $r['tx_code'],
                    $r['item_title'],
                    $r['donor_name'],
                    $r['beneficiary_name'],
                    (int)$r['quantity'],
                    $r['method'],
                    $r['status'],
                    $r['date_record']
                ];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'kpis'    => [
            'donations'    => $kpiDonations,
            'requests'     => $kpiRequests,
            'transactions' => $kpiTransactions,
            'served'       => $kpiServed,
        ],
        'type'    => $type,
        'count'   => count($rows),
        'rows'    => $rows,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate report.']);
}
