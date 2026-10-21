<?php
/**
 * iCareDonate — Administrative Transaction & Handover Monitoring Endpoint
 * actions/admin_transactions.php
 *
 * NOTE: iCareDonate is a free dignity-first community item donation platform.
 * "Transactions" represents goods release passes, verified handovers, and claims.
 * It contains NO financial, monetary, or payment records.
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

// ─────────────────────────────────────────────────────────────
// POST: Confirm Handover / Release Goods
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload  = json_decode($rawInput, true) ?? $_POST;

    $action = trim((string)($payload['action'] ?? 'confirm_handover'));
    $txId   = (int)($payload['id'] ?? 0);

    if ($txId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Valid transaction / request ID is required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT r.id, r.requester_id, r.donation_id, r.status,
                   COALESCE(d.title, 'Physical Item Handover') AS item_title,
                   u.name AS beneficiary_name,
                   d.donor_id
            FROM requests r
            LEFT JOIN users u ON r.requester_id = u.id
            LEFT JOIN donations d ON r.donation_id = d.id
            WHERE r.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $txId]);
        $tx = $stmt->fetch();

        if (!$tx) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Transaction record not found.']);
            exit;
        }

        // Mark request as completed
        $updateReq = $pdo->prepare("UPDATE requests SET status = 'completed', updated_at = NOW() WHERE id = :id");
        $updateReq->execute([':id' => $txId]);

        // Archive fulfilled donation listing so it is removed from active pool
        if (!empty($tx['donation_id'])) {
            $updateDon = $pdo->prepare("UPDATE donations SET status = 'archived', updated_at = NOW() WHERE id = :did");
            $updateDon->execute([':did' => $tx['donation_id']]);

            // Persist to handovers audit table
            try {
                $insHandover = $pdo->prepare("
                    INSERT INTO handovers (request_id, donation_id, donor_id, requester_id, status, completed_at, confirmed_by, created_at)
                    VALUES (:rid, :did, :donor_id, :requester_id, 'completed', NOW(), :admin_id, NOW())
                ");
                $insHandover->execute([
                    ':rid'          => $txId,
                    ':did'          => $tx['donation_id'],
                    ':donor_id'     => $tx['donor_id'] ?: $admin['id'],
                    ':requester_id' => $tx['requester_id'],
                    ':admin_id'     => $admin['id'],
                ]);
            } catch (PDOException $e) {}
        }

        // Notify beneficiary
        try {
            $notif = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
                VALUES (:uid, 'Handover Confirmed', :msg, 'match', 0, NOW())
            ");
            $notif->execute([
                ':uid' => $tx['requester_id'],
                ':msg' => 'Your handover for "' . $tx['item_title'] . '" has been confirmed by administration. Thank you for participating!'
            ]);
        } catch (PDOException $e) {}

        logAdminAction($pdo, 'HANDOVER_COMPLETED', $admin['id'], $admin['name'], 'request', $txId, "Handover confirmed for {$tx['item_title']}", 'Goods received and handover verified');

        echo json_encode([
            'success' => true,
            'message' => "Handover #{$txId} successfully confirmed and finalized.",
            'status'  => 'completed'
        ]);
        exit;

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to confirm handover.']);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────
// GET: Query Transactions & Claims
// ─────────────────────────────────────────────────────────────
$status = trim((string)($_GET['status'] ?? 'all'));
$search = trim((string)($_GET['q'] ?? ''));

try {
    // Matched requests and claims from requests joined with donations & users
    $sql = "
        SELECT r.id AS tx_id,
               CONCAT('TX-2026-', LPAD(r.id, 3, '0')) AS tx_code,
               CONCAT('CLM-', LPAD(r.id, 3, '0')) AS claim_code,
               COALESCE(d.title, 'Physical Item Handover') AS item_title,
               r.quantity,
               'items' AS unit,
               r.status AS request_status,
               r.created_at,
               COALESCE(req_user.name, 'Beneficiary') AS beneficiary_name,
               req_user.email AS beneficiary_email,
               COALESCE(d.city, 'Metro Manila') AS beneficiary_city,
               COALESCE(donor_user.name, 'Community Donor') AS donor_name,
               COALESCE(d.handover_method, 'Community Handover') AS pickup_method,
               CASE
                   WHEN r.status = 'completed' THEN 'completed'
                   WHEN r.status = 'approved' AND r.donation_id IS NOT NULL THEN 'claimed'
                   ELSE 'scheduled'
               END AS handover_status
        FROM requests r
        LEFT JOIN users req_user ON r.requester_id = req_user.id
        LEFT JOIN donations d ON r.donation_id = d.id
        LEFT JOIN users donor_user ON d.donor_id = donor_user.id
        WHERE 1=1
    ";
    $params = [];

    if ($status === 'completed') {
        $sql .= " AND r.status = 'completed'";
    } elseif ($status === 'claimed' || $status === 'in_transit') {
        $sql .= " AND r.status = 'approved' AND r.donation_id IS NOT NULL";
    } elseif ($status === 'created' || $status === 'scheduled') {
        $sql .= " AND r.status IN ('pending', 'approved')";
    }

    if ($search !== '') {
        $sql .= " AND (d.title LIKE :s OR req_user.name LIKE :s OR donor_user.name LIKE :s)";
        $params[':s'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY r.created_at DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Compute status counts
    $counts = [
        'all'       => 0,
        'scheduled' => 0,
        'claimed'   => 0,
        'completed' => 0,
    ];
    try {
        $cStmt = $pdo->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS scheduled,
                SUM(CASE WHEN status = 'approved' AND donation_id IS NOT NULL THEN 1 ELSE 0 END) AS claimed,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
            FROM requests
        ");
        $cRow = $cStmt->fetch();
        if ($cRow) {
            $counts = [
                'all'       => (int)($cRow['total'] ?? 0),
                'scheduled' => (int)($cRow['scheduled'] ?? 0),
                'claimed'   => (int)($cRow['claimed'] ?? 0),
                'completed' => (int)($cRow['completed'] ?? 0),
            ];
        }
    } catch (PDOException $e) {}

    echo json_encode([
        'success'      => true,
        'counts'       => $counts,
        'transactions' => $rows,
        'data'         => $rows,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve transaction records.']);
}
