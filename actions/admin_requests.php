<?php
/**
 * iCareDonate — Administrative Request & Proof Moderation Endpoint
 * actions/admin_requests.php
 *
 * GET: Retrieves real assistance request records with proof metadata from MySQL.
 * POST: Handles proof verification, matching, rejection, and interview scheduling.
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
// POST: Moderation Actions (Approve Proof, Reject Proof, Interview)
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload  = json_decode($rawInput, true) ?? $_POST;

    $action    = trim((string)($payload['action'] ?? ''));
    $requestId = (int)($payload['id'] ?? 0);
    $reason    = trim((string)($payload['reason'] ?? ''));

    if ($requestId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Valid request ID is required.']);
        exit;
    }

    try {
        $stmtFind = $pdo->prepare("
            SELECT r.*, r.requester_id, COALESCE(d.title, 'Community Request') AS item_title,
                   u.name AS user_name, u.email AS user_email
            FROM requests r
            LEFT JOIN users u ON r.requester_id = u.id
            LEFT JOIN donations d ON r.donation_id = d.id
            WHERE r.id = :id
            LIMIT 1
        ");
        $stmtFind->execute([':id' => $requestId]);
        $request = $stmtFind->fetch();

        if (!$request) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Assistance request record not found.']);
            exit;
        }

        $requesterId = (int)$request['requester_id'];
        $itemTitle   = $request['item_title'];

        switch ($action) {
            case 'approve_proof':
                $stmtUpdate = $pdo->prepare("UPDATE requests SET status = 'approved', updated_at = NOW() WHERE id = :id");
                $stmtUpdate->execute([':id' => $requestId]);

                // Create notification for beneficiary
                try {
                    $stmtNotif = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
                        VALUES (:uid, 'Assistance Request Approved', :msg, 'match', 0, NOW())
                    ");
                    $stmtNotif->execute([
                        ':uid' => $requesterId,
                        ':msg' => 'Your request for "' . $itemTitle . '" has been verified and approved by administration.'
                    ]);
                } catch (PDOException $e) {}

                logAdminAction($pdo, 'REQUEST_PROOF_APPROVED', $admin['id'], $admin['name'], 'request', $requestId, "Verified proof for request #{$requestId} ({$itemTitle})", $reason ?: 'Documentation verified');

                echo json_encode([
                    'success' => true,
                    'message' => "Request #{$requestId} proof verified successfully. Request is now approved.",
                    'status'  => 'approved'
                ]);
                exit;

            case 'reject_proof':
                if ($reason === '') {
                    $reason = 'Submitted verification documentation did not substantiate the request.';
                }

                $stmtUpdate = $pdo->prepare("UPDATE requests SET status = 'rejected', rejection_reason = :reason, updated_at = NOW() WHERE id = :id");
                $stmtUpdate->execute([':reason' => $reason, ':id' => $requestId]);

                // Notify beneficiary
                try {
                    $stmtNotif = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
                        VALUES (:uid, 'Request Review Notice', :msg, 'system', 0, NOW())
                    ");
                    $stmtNotif->execute([
                        ':uid' => $requesterId,
                        ':msg' => 'Your assistance request for "' . $itemTitle . '" could not be approved: ' . $reason
                    ]);
                } catch (PDOException $e) {}

                logAdminAction($pdo, 'REQUEST_PROOF_REJECTED', $admin['id'], $admin['name'], 'request', $requestId, "Rejected request #{$requestId} ({$itemTitle})", $reason);

                echo json_encode([
                    'success' => true,
                    'message' => "Request #{$requestId} has been rejected.",
                    'status'  => 'rejected'
                ]);
                exit;

            case 'schedule_interview':
                // requests status enum: pending, approved, rejected, completed, cancelled
                logAdminAction($pdo, 'INTERVIEW_SCHEDULED', $admin['id'], $admin['name'], 'request', $requestId, "Scheduled interview for request #{$requestId}", $reason ?: 'Verification interview requested');

                echo json_encode([
                    'success' => true,
                    'message' => "Verification interview requested for Request #{$requestId}.",
                    'status'  => 'pending'
                ]);
                exit;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
                exit;
        }

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to process request action.']);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────
// GET: Query Assistance Requests
// ─────────────────────────────────────────────────────────────
$singleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status   = trim((string)($_GET['status'] ?? 'all'));
$search   = trim((string)($_GET['search'] ?? $_GET['q'] ?? ''));

try {
    if ($singleId > 0) {
        $stmtSingle = $pdo->prepare("
            SELECT r.*, r.requester_id AS user_id,
                   COALESCE(d.title, 'Community Request') AS item_title,
                   'items' AS unit, 'items' AS donation_unit,
                   d.quantity AS donation_qty, d.title AS donation_title,
                   COALESCE(d.city, 'Metro Manila') AS city,
                   r.message AS reason, r.proof_file_path AS proof_document_url,
                   u.name AS user_name, u.email AS user_email,
                   u.name AS beneficiary_name, u.name AS requester_name
            FROM requests r
            LEFT JOIN users u ON r.requester_id = u.id
            LEFT JOIN donations d ON r.donation_id = d.id
            WHERE r.id = :id
            LIMIT 1
        ");
        $stmtSingle->execute([':id' => $singleId]);
        $row = $stmtSingle->fetch();

        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Assistance request record not found.']);
            exit;
        }

        // Count previous completed claims for this beneficiary
        $prevClaims = 0;
        try {
            $prevStmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE requester_id = :uid AND status = 'completed' AND id != :rid");
            $prevStmt->execute([':uid' => $row['requester_id'], ':rid' => $singleId]);
            $prevClaims = (int)$prevStmt->fetchColumn();
        } catch (PDOException $e) {}

        $row['previous_claims_count'] = $prevClaims;

        echo json_encode(['success' => true, 'request' => $row]);
        exit;
    }

    $sql = "
        SELECT r.*, r.requester_id AS user_id,
               COALESCE(d.title, 'Community Request') AS item_title,
               'items' AS unit, COALESCE(d.city, 'Metro Manila') AS city,
               r.message AS reason, r.proof_file_path AS proof_document_url,
               u.name AS user_name, u.email AS user_email,
               u.name AS beneficiary_name, u.name AS requester_name,
               d.title AS donation_title
        FROM requests r
        LEFT JOIN users u ON r.requester_id = u.id
        LEFT JOIN donations d ON r.donation_id = d.id
        WHERE 1=1
    ";
    $params = [];

    if ($status === 'pending') {
        $sql .= " AND r.status = 'pending'";
    } elseif ($status === 'matched') {
        $sql .= " AND r.status = 'approved' AND r.donation_id IS NOT NULL";
    } elseif ($status === 'approved') {
        $sql .= " AND r.status = 'approved'";
    } elseif ($status === 'completed') {
        $sql .= " AND r.status = 'completed'";
    } elseif ($status === 'rejected') {
        $sql .= " AND r.status = 'rejected'";
    }

    if ($search !== '') {
        $sql .= ' AND (d.title LIKE :s OR r.message LIKE :s OR u.name LIKE :s OR d.city LIKE :s)';
        $params[':s'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY r.created_at DESC LIMIT 100';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    // Compute status counts
    $counts = [
        'pending'   => 0,
        'matched'   => 0,
        'completed' => 0,
        'rejected'  => 0,
        'all'       => 0,
    ];
    try {
        $cStmt = $pdo->query("
            SELECT
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'approved' AND donation_id IS NOT NULL THEN 1 ELSE 0 END) AS matched,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
                COUNT(*) AS total
            FROM requests
        ");
        $cRow = $cStmt->fetch();
        if ($cRow) {
            $counts = [
                'pending'   => (int)($cRow['pending'] ?? 0),
                'matched'   => (int)($cRow['matched'] ?? 0),
                'completed' => (int)($cRow['completed'] ?? 0),
                'rejected'  => (int)($cRow['rejected'] ?? 0),
                'all'       => (int)($cRow['total'] ?? 0),
            ];
        }
    } catch (PDOException $e) {}

    echo json_encode([
        'success'  => true,
        'counts'   => $counts,
        'requests' => $items,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve assistance requests.']);
}
