<?php
/**
 * iCareDonate — User Donation Listings & Self-Management Endpoint
 * actions/user_donations.php
 *
 * GET: Retrieves authenticated user's donation listings from MySQL.
 * POST: Allows authenticated donors to cancel or archive their own listings.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

// 1. Enforce Authentication
$userId = getAuthenticatedUserId();
if ($userId === null) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please log in to manage your donations.',
    ]);
    exit;
}

// 2. Database Connection
$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Database service is currently unavailable.',
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────
// POST: Donor Actions (Archive, Cancel Listing)
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload  = json_decode($rawInput, true) ?? $_POST;

    $action     = trim((string)($payload['action'] ?? ''));
    $donationId = (int)($payload['id'] ?? 0);

    if ($donationId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Valid donation ID is required.']);
        exit;
    }

    try {
        // Verify ownership
        $stmtCheck = $pdo->prepare('SELECT id, title, status FROM donations WHERE id = :id AND donor_id = :uid LIMIT 1');
        $stmtCheck->execute([':id' => $donationId, ':uid' => $userId]);
        $donation = $stmtCheck->fetch();

        if (!$donation) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Donation listing not found or unauthorized.']);
            exit;
        }

        if ($action === 'archive' || $action === 'cancel') {
            $newStatus = ($action === 'archive') ? 'archived' : 'rejected';
            $stmtUpdate = $pdo->prepare("UPDATE donations SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmtUpdate->execute([':status' => $newStatus, ':id' => $donationId]);

            echo json_encode([
                'success' => true,
                'message' => 'Donation listing updated successfully.',
                'new_status' => $newStatus
            ]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action requested.']);
        exit;

    } catch (PDOException $e) {
        error_log('Error in user_donations.php POST: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error executing request.']);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────
// GET: Retrieve User's Donation Listings
// ─────────────────────────────────────────────────────────────
try {
    $statusFilter = trim((string)($_GET['status'] ?? 'all'));
    $search       = trim((string)($_GET['q'] ?? ''));
    $category     = trim((string)($_GET['category'] ?? ''));
    $sort         = trim((string)($_GET['sort'] ?? 'newest'));

    $sql = "SELECT d.id, d.title, d.category, d.quantity, d.unit, d.item_condition, d.status,
                   d.image_url, d.created_at, d.updated_at
            FROM donations d
            WHERE d.donor_id = :uid";
    $params = [':uid' => $userId];

    if ($statusFilter !== '' && $statusFilter !== 'all') {
        if ($statusFilter === 'active' || $statusFilter === 'approved') {
            $sql .= " AND d.status = 'approved'";
        } elseif ($statusFilter === 'pending') {
            $sql .= " AND d.status = 'pending'";
        } elseif ($statusFilter === 'archived') {
            $sql .= " AND d.status = 'archived'";
        } elseif ($statusFilter === 'rejected' || $statusFilter === 'cancelled') {
            $sql .= " AND d.status = 'rejected'";
        }
    }

    if ($category !== '' && $category !== 'all') {
        $sql .= " AND d.category = :category";
        $params[':category'] = $category;
    }

    if ($search !== '') {
        $sql .= " AND (d.title LIKE :search OR d.category LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    if ($sort === 'oldest') {
        $sql .= " ORDER BY d.created_at ASC";
    } elseif ($sort === 'quantity') {
        $sql .= " ORDER BY d.quantity DESC";
    } else {
        $sql .= " ORDER BY d.created_at DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = [];

    while ($row = $stmt->fetch()) {
        $items[] = [
            'id'             => (int)$row['id'],
            'ref_code'       => 'REF-' . str_pad((string)$row['id'], 5, '0', STR_PAD_LEFT),
            'title'          => $row['title'],
            'category'       => $row['category'] ?? 'General',
            'quantity'       => (int)($row['quantity'] ?? 1),
            'unit'           => $row['unit'] ?: 'units',
            'item_condition' => $row['item_condition'] ?: 'Good',
            'status'         => $row['status'],
            'image_url'      => $row['image_url'] ?: '../assets/images/categories/household-goods.jpg',
            'created_at'     => $row['created_at'],
            'formatted_date' => date('M j, Y', strtotime((string)$row['created_at'])),
        ];
    }

    // Counts for tabs
    $countsStmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) AS archived,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected
        FROM donations
        WHERE donor_id = :uid
    ");
    $countsStmt->execute([':uid' => $userId]);
    $counts = $countsStmt->fetch() ?: [];

    echo json_encode([
        'success' => true,
        'donations' => $items,
        'counts'  => [
            'all'       => (int)($counts['total'] ?? 0),
            'pending'   => (int)($counts['pending'] ?? 0),
            'approved'  => (int)($counts['approved'] ?? 0),
            'matched'   => 0,
            'completed' => (int)($counts['archived'] ?? 0),
            'archived'  => (int)($counts['archived'] ?? 0),
            'cancelled' => (int)($counts['rejected'] ?? 0),
        ]
    ]);

} catch (PDOException $e) {
    error_log('Error in user_donations.php GET: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load donation listings.'
    ]);
}
