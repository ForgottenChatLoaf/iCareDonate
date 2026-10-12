<?php
declare(strict_types=1);
/**
 * api/v1/user/dashboard.php
 * GET — Returns the authenticated user's dashboard stats and recent activity.
 */
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

$user   = requireAuth($pdo);
$userId = (int)$user['id'];

$stats = ['my_donations' => 0, 'my_requests' => 0, 'in_progress' => 0, 'completed' => 0];

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM donations WHERE donor_id = ?');
    $stmt->execute([$userId]);
    $stats['my_donations'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM requests WHERE requester_id = ?');
    $stmt->execute([$userId]);
    $stats['my_requests'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE (donor_id = ? OR requester_id = ?) AND status IN ("created","pending_claim","claimed")');
    $stmt->execute([$userId, $userId]);
    $stats['in_progress'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE (donor_id = ? OR requester_id = ?) AND status = "completed"');
    $stmt->execute([$userId, $userId]);
    $stats['completed'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    error_log($e->getMessage());
}

// Recent activity
$recentActivity = [];
try {
    $dStmt = $pdo->prepare("SELECT 'donation' AS type, title, status, created_at AS ts FROM donations WHERE donor_id = ? ORDER BY created_at DESC LIMIT 4");
    $dStmt->execute([$userId]);
    foreach ($dStmt->fetchAll() as $row) {
        $recentActivity[] = ['type' => 'donation', 'label' => "You posted a new donation request for '{$row['title']}'", 'ts' => $row['ts'], 'status' => $row['status']];
    }

    $rStmt = $pdo->prepare("SELECT 'request' AS type, d.title AS t, r.submitted_at AS ts, r.status FROM requests r JOIN donations d ON r.donation_id = d.id WHERE r.requester_id = ? ORDER BY r.submitted_at DESC LIMIT 4");
    $rStmt->execute([$userId]);
    foreach ($rStmt->fetchAll() as $row) {
        $recentActivity[] = ['type' => 'request', 'label' => "You placed a request for '{$row['t']}'", 'ts' => $row['ts'], 'status' => $row['status']];
    }

    $tStmt = $pdo->prepare("SELECT 'completed' AS type, d.title AS t, t.updated_at AS ts FROM transactions t JOIN donations d ON t.donation_id = d.id WHERE (t.donor_id = ? OR t.requester_id = ?) AND t.status = 'completed' ORDER BY t.updated_at DESC LIMIT 3");
    $tStmt->execute([$userId, $userId]);
    foreach ($tStmt->fetchAll() as $row) {
        $recentActivity[] = ['type' => 'completed', 'label' => "Completed delivery verification for '{$row['t']}'", 'ts' => $row['ts'], 'status' => 'completed'];
    }

    usort($recentActivity, fn($a, $b) => strtotime($b['ts']) - strtotime($a['ts']));
    $recentActivity = array_slice($recentActivity, 0, 6);
} catch (Exception $e) {
    error_log($e->getMessage());
}

// Unread notifications
$unreadNotifs = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unreadNotifs = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

jsonOk([
    'stats'               => $stats,
    'recent_activity'     => $recentActivity,
    'unread_notifications'=> $unreadNotifs,
]);
