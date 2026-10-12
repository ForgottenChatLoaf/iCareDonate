<?php
declare(strict_types=1);
/**
 * api/v1/admin/dashboard.php
 * GET — Returns admin dashboard stats and recent activity.
 */
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

$admin = requireAdmin($pdo);

$stats = ['pending_donations' => 0, 'pending_requests' => 0, 'active_matches' => 0, 'open_concerns' => 0];

try {
    $stats['pending_donations'] = (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'pending_verification'")->fetchColumn();
    $stats['pending_requests']  = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status IN ('submitted','proof_review')")->fetchColumn();
    $stats['active_matches']    = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status IN ('created','pending_claim','claimed')")->fetchColumn();
    $stats['open_concerns']     = (int)$pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open','in_progress')")->fetchColumn();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$recentActivities = [];
try {
    $dStmt = $pdo->query("SELECT CONCAT(u.first_name,' ',u.last_name,' submitted donation D-',d.id) AS title, d.created_at FROM donations d JOIN users u ON d.donor_id=u.id ORDER BY d.created_at DESC LIMIT 4");
    foreach ($dStmt->fetchAll() as $row) {
        $recentActivities[] = ['title' => $row['title'], 'created_at' => $row['created_at'], 'type' => 'donation'];
    }
    $rStmt = $pdo->query("SELECT CONCAT(u.first_name,' ',u.last_name,' submitted request R-',r.id) AS title, r.created_at FROM requests r JOIN users u ON r.requester_id=u.id ORDER BY r.created_at DESC LIMIT 4");
    foreach ($rStmt->fetchAll() as $row) {
        $recentActivities[] = ['title' => $row['title'], 'created_at' => $row['created_at'], 'type' => 'request'];
    }
    usort($recentActivities, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
    $recentActivities = array_slice($recentActivities, 0, 6);
} catch (Exception $e) {
    error_log($e->getMessage());
}

// Pending donation for review panel
$pendingDonation = null;
try {
    $stmt = $pdo->query("SELECT d.*, c.name AS category_name, u.first_name, u.last_name, u.email AS donor_email, u.phone AS donor_phone, u.barangay AS donor_barangay, u.city AS donor_city FROM donations d JOIN categories c ON d.category_id=c.id JOIN users u ON d.donor_id=u.id WHERE d.status='pending_verification' ORDER BY d.created_at ASC LIMIT 1");
    $pendingDonation = $stmt->fetch() ?: null;
} catch (Exception $e) {}

// Pending requests
$pendingRequests = [];
try {
    $stmt = $pdo->query("SELECT r.*, d.title AS donation_title, u.first_name, u.last_name, u.email AS requester_email FROM requests r JOIN donations d ON r.donation_id=d.id JOIN users u ON r.requester_id=u.id WHERE r.status IN ('submitted','proof_review') ORDER BY r.submitted_at ASC LIMIT 4");
    $pendingRequests = $stmt->fetchAll();
} catch (Exception $e) {}

jsonOk([
    'stats'            => $stats,
    'recent_activities'=> $recentActivities,
    'pending_donation' => $pendingDonation,
    'pending_requests' => $pendingRequests,
]);
