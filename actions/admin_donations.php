<?php
/**
 * iCareDonate — Administrative Donation Management & Moderation Endpoint
 * actions/admin_donations.php
 *
 * GET: Retrieves real donation records from MySQL with category & status filters.
 * POST: Handles authentic moderation actions: approve, reject, archive, restore.
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
// POST: Moderation Actions (Approve, Reject, Archive, Restore)
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload  = json_decode($rawInput, true) ?? $_POST;

    $action     = trim((string)($payload['action'] ?? ''));
    $donationId = (int)($payload['id'] ?? 0);
    $reason     = trim((string)($payload['reason'] ?? ''));

    if ($donationId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Valid donation ID is required.']);
        exit;
    }

    try {
        // Find existing donation
        $stmtFind = $pdo->prepare('SELECT id, donor_id, title, status FROM donations WHERE id = :id LIMIT 1');
        $stmtFind->execute([':id' => $donationId]);
        $donation = $stmtFind->fetch();

        if (!$donation) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Donation record not found.']);
            exit;
        }

        $donorId = (int)$donation['donor_id'];
        $title   = $donation['title'];

        switch ($action) {
            case 'approve':
                $stmtUpdate = $pdo->prepare("UPDATE donations SET status = 'approved', updated_at = NOW() WHERE id = :id");
                $stmtUpdate->execute([':id' => $donationId]);

                // Create notification for donor
                try {
                    $stmtNotif = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
                        VALUES (:uid, 'Donation Listing Approved', :msg, 'donation', 0, NOW())
                    ");
                    $stmtNotif->execute([
                        ':uid' => $donorId,
                        ':msg' => 'Your donation listing "' . $title . '" has been verified and published to the public directory.'
                    ]);
                } catch (PDOException $e) {}

                logAdminAction($pdo, 'DONATION_APPROVED', $admin['id'], $admin['name'], 'donation', $donationId, "Approved listing {$title}", $reason ?: 'Listing verified');

                echo json_encode([
                    'success' => true,
                    'message' => "Donation #{$donationId} (\"{$title}\") has been approved and published.",
                    'status'  => 'approved'
                ]);
                exit;

            case 'reject':
                if ($reason === '') {
                    $reason = 'Listing did not meet donation verification guidelines.';
                }

                $stmtUpdate = $pdo->prepare("UPDATE donations SET status = 'rejected', rejection_reason = :reason, updated_at = NOW() WHERE id = :id");
                $stmtUpdate->execute([':reason' => $reason, ':id' => $donationId]);

                // Notify donor
                try {
                    $stmtNotif = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
                        VALUES (:uid, 'Donation Review Update', :msg, 'donation', 0, NOW())
                    ");
                    $stmtNotif->execute([
                        ':uid' => $donorId,
                        ':msg' => 'Your donation listing "' . $title . '" could not be approved at this time: ' . $reason
                    ]);
                } catch (PDOException $e) {}

                logAdminAction($pdo, 'DONATION_REJECTED', $admin['id'], $admin['name'], 'donation', $donationId, "Rejected listing {$title}", $reason);

                echo json_encode([
                    'success' => true,
                    'message' => "Donation #{$donationId} has been rejected.",
                    'status'  => 'rejected'
                ]);
                exit;

            case 'archive':
                if ($reason === '') {
                    $reason = 'Archived by administrator.';
                }

                $stmtUpdate = $pdo->prepare("UPDATE donations SET status = 'archived', archived_reason = :reason, updated_at = NOW() WHERE id = :id");
                $stmtUpdate->execute([':reason' => $reason, ':id' => $donationId]);

                logAdminAction($pdo, 'DONATION_ARCHIVED', $admin['id'], $admin['name'], 'donation', $donationId, "Archived listing {$title}", $reason);

                echo json_encode([
                    'success' => true,
                    'message' => "Donation #{$donationId} has been archived and hidden from public view.",
                    'status'  => 'archived'
                ]);
                exit;

            case 'restore':
                $stmtUpdate = $pdo->prepare("UPDATE donations SET status = 'approved', updated_at = NOW() WHERE id = :id");
                $stmtUpdate->execute([':id' => $donationId]);

                logAdminAction($pdo, 'DONATION_RESTORED', $admin['id'], $admin['name'], 'donation', $donationId, "Restored listing {$title}", 'Listing restored to active circulation');

                echo json_encode([
                    'success' => true,
                    'message' => "Donation #{$donationId} has been restored to active status.",
                    'status'  => 'approved'
                ]);
                exit;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Unsupported moderation action specified.']);
                exit;
        }

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to process moderation action.']);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────
// GET: Query Donation Records
// ─────────────────────────────────────────────────────────────
$singleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status   = trim((string)($_GET['status'] ?? 'all'));
$category = trim((string)($_GET['category'] ?? ''));
$search   = trim((string)($_GET['q'] ?? ''));

try {
    // Single donation inspection
    if ($singleId > 0) {
        $stmtSingle = $pdo->prepare("
            SELECT d.*, d.donor_id AS user_id, 'pcs' AS unit,
                   d.condition AS condition_type, COALESCE(d.city, d.location, 'Metro Manila') AS pickup_city,
                   u.name AS donor_name, u.email AS donor_email
            FROM donations d
            LEFT JOIN users u ON d.donor_id = u.id
            WHERE d.id = :id
            LIMIT 1
        ");
        $stmtSingle->execute([':id' => $singleId]);
        $row = $stmtSingle->fetch();

        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Donation record not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'donation' => $row, 'data' => $row]);
        exit;
    }

    // List donations with filters
    $sql = "
        SELECT d.*, d.donor_id AS user_id, 'pcs' AS unit,
               d.condition AS condition_type, COALESCE(d.city, d.location, 'Metro Manila') AS pickup_city,
               u.name AS donor_name, u.email AS donor_email
        FROM donations d
        LEFT JOIN users u ON d.donor_id = u.id
        WHERE 1=1
    ";
    $params = [];

    if ($status === 'pending') {
        $sql .= " AND d.status = 'pending'";
    } elseif ($status === 'approved') {
        $sql .= " AND d.status = 'approved'";
    } elseif ($status === 'archived') {
        $sql .= " AND d.status = 'archived'";
    } elseif ($status === 'rejected') {
        $sql .= " AND d.status = 'rejected'";
    } elseif ($status === 'all_active') {
        $sql .= " AND d.status IN ('pending', 'approved')";
    }

    if ($category !== '') {
        $sql .= ' AND d.category = :category';
        $params[':category'] = $category;
    }

    if ($search !== '') {
        $sql .= ' AND (d.title LIKE :s OR d.description LIKE :s OR u.name LIKE :s OR d.city LIKE :s OR d.location LIKE :s)';
        $params[':s'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY d.created_at DESC LIMIT 100';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    // Calculate queue totals
    $counts = [
        'pending'   => 0,
        'approved'  => 0,
        'archived'  => 0,
        'rejected'  => 0,
        'all'       => 0,
    ];
    try {
        $cStmt = $pdo->query("
            SELECT
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) AS archived,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
                COUNT(*) AS total
            FROM donations
        ");
        $cRow = $cStmt->fetch();
        if ($cRow) {
            $counts = [
                'pending'   => (int)($cRow['pending'] ?? 0),
                'approved'  => (int)($cRow['approved'] ?? 0),
                'archived'  => (int)($cRow['archived'] ?? 0),
                'rejected'  => (int)($cRow['rejected'] ?? 0),
                'all'       => (int)($cRow['total'] ?? 0),
            ];
        }
    } catch (PDOException $e) {}

    echo json_encode([
        'success'   => true,
        'counts'    => $counts,
        'donations' => $items,
        'data'      => $items,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to query donation records.']);
}
