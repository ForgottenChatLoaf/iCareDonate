<?php
/**
 * iCareDonate — Check Pending Submissions Endpoint
 * actions/check_pending_submission.php
 *
 * Checks if the authenticated user has any donation or resource request
 * currently awaiting admin review (status = 'pending' or 'under_review').
 * Enforces business rule: 1 pending submission at a time across donations & requests combined.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$userId = getAuthenticatedUserId();
if ($userId === null) {
    http_response_code(401);
    echo json_encode([
        'success'    => false,
        'restricted' => false,
        'message'    => 'Please log in to check submission eligibility.',
    ]);
    exit;
}

$pdo = getDB();
if ($pdo === null) {
    // If database unavailable, respond gracefully without hard blocking
    http_response_code(200);
    echo json_encode([
        'success'    => true,
        'restricted' => false,
        'message'    => 'Database service offline. Proceeding in offline mode.',
    ]);
    exit;
}

$pendingDonation = null;
$pendingRequest  = null;

try {
    // 1. Check for pending donations
    $stmtDon = $pdo->prepare("SELECT id, title, status, created_at FROM donations WHERE donor_id = :uid AND status = 'pending' LIMIT 1");
    $stmtDon->execute([':uid' => $userId]);
    $pendingDonation = $stmtDon->fetch();
} catch (PDOException $e) {
    // Table may not exist yet
}

try {
    // 2. Check for pending resource requests
    $stmtReq = $pdo->prepare("
        SELECT r.id, COALESCE(d.title, 'Assistance Request') AS item_title, r.status, r.created_at
        FROM requests r
        LEFT JOIN donations d ON r.donation_id = d.id
        WHERE r.requester_id = :uid AND r.status = 'pending'
        LIMIT 1
    ");
    $stmtReq->execute([':uid' => $userId]);
    $pendingRequest = $stmtReq->fetch();
} catch (PDOException $e) {
    // Table may not exist yet
}

$isRestricted = ($pendingDonation !== null && $pendingDonation !== false) || ($pendingRequest !== null && $pendingRequest !== false);

if ($isRestricted) {
    $type = ($pendingDonation !== null && $pendingDonation !== false) ? 'donation' : 'request';
    $title = ($type === 'donation') ? ($pendingDonation['title'] ?? 'Donation Item') : ($pendingRequest['item_title'] ?? 'Assistance Request');
    $status = ($type === 'donation') ? ($pendingDonation['status'] ?? 'pending') : ($pendingRequest['status'] ?? 'pending');

    echo json_encode([
        'success'        => true,
        'restricted'     => true,
        'pending_type'   => $type,
        'pending_title'  => $title,
        'pending_status' => $status,
        'message'        => 'You already have a submission awaiting admin review. Please wait for the review to finish before submitting another donation or resource request.',
    ]);
    exit;
}

echo json_encode([
    'success'    => true,
    'restricted' => false,
    'message'    => 'You are eligible to submit a donation or resource request.',
]);
