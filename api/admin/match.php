<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_BASE . '/admin/requests.php');
    exit;
}

verifyCsrf();

$adminId = (int)$_SESSION['user_id'];
$requestId = (int)($_POST['request_id'] ?? 0);
$donationId = (int)($_POST['donation_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));
$pickupMethod = in_array($_POST['pickup_method'] ?? '', ['pickup', 'delivery'], true) ? $_POST['pickup_method'] : 'pickup';

if ($requestId <= 0 || $donationId <= 0) {
    setFlash('error', 'Invalid match parameters.');
    header('Location: ' . APP_BASE . '/admin/requests.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Fetch donation and request
    $dStmt = $pdo->prepare('SELECT id, donor_id, title, quantity_total, quantity_reserved, quantity_claimed FROM donations WHERE id = ? FOR UPDATE');
    $dStmt->execute([$donationId]);
    $donation = $dStmt->fetch();

    $rStmt = $pdo->prepare('SELECT id, requester_id, quantity_requested FROM requests WHERE id = ? FOR UPDATE');
    $rStmt->execute([$requestId]);
    $request = $rStmt->fetch();

    if (!$donation || !$request) {
        throw new Exception('Donation or request not found.');
    }

    $available = (int)$donation['quantity_total'] - (int)$donation['quantity_reserved'] - (int)$donation['quantity_claimed'];
    if ($quantity > $available) {
        throw new Exception('Match quantity exceeds available stock.');
    }

    // 1. Create match record
    $mStmt = $pdo->prepare('
        INSERT INTO matches (donation_id, request_id, created_by, quantity, status, created_at)
        VALUES (?, ?, ?, ?, "active", NOW())
    ');
    $mStmt->execute([$donationId, $requestId, $adminId, $quantity]);
    $matchId = (int)$pdo->lastInsertId();

    // 2. Reserve stock on donation
    $updDonation = $pdo->prepare('
        UPDATE donations
        SET quantity_reserved = quantity_reserved + ?
        WHERE id = ?
    ');
    $updDonation->execute([$quantity, $donationId]);

    // 3. Update request status to matched
    $updRequest = $pdo->prepare('
        UPDATE requests
        SET status = "matched", reviewed_at = NOW(), reviewed_by = ?
        WHERE id = ?
    ');
    $updRequest->execute([$adminId, $requestId]);

    // 4. Create transaction record with 7-day claim deadline
    $deadline = date('Y-m-d H:i:s', strtotime('+7 days'));
    $txStmt = $pdo->prepare('
        INSERT INTO transactions (
            match_id, donor_id, requester_id, donation_id, quantity, status, pickup_method, claim_deadline, created_at
        ) VALUES (?, ?, ?, ?, ?, "created", ?, ?, NOW())
    ');
    $txStmt->execute([
        $matchId, (int)$donation['donor_id'], (int)$request['requester_id'], $donationId, $quantity, $pickupMethod, $deadline
    ]);
    $txId = (int)$pdo->lastInsertId();

    // 5. Audit Log
    logAudit($pdo, $adminId, 'admin', 'MATCH_CREATED', 'match', $matchId, null, [
        'transaction_id' => $txId, 'quantity' => $quantity, 'donation_id' => $donationId, 'request_id' => $requestId
    ], 'Created donation-request match');

    // 6. Notify both donor and requester
    createNotification(
        $pdo, (int)$donation['donor_id'], 'MATCH_CREATED',
        'Match Created for Your Donation!',
        'Your donation "' . $donation['title'] . '" has been matched with a community requester! Please check your transactions page for handover coordination.'
    );

    createNotification(
        $pdo, (int)$request['requester_id'], 'MATCH_CREATED',
        'Your Request has been Approved & Matched!',
        'Great news! Your request for "' . $donation['title'] . '" has been approved. Please claim your item before ' . date('M d, Y', strtotime($deadline)) . '.'
    );

    $pdo->commit();

    setFlash('success', 'Match created successfully! Transaction #' . $txId . ' has been initiated and both parties notified.');
    header('Location: ' . APP_BASE . '/admin/matches.php');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    setFlash('error', 'Failed to create match: ' . $e->getMessage());
    header('Location: ' . APP_BASE . '/admin/requests.php');
    exit;
}
