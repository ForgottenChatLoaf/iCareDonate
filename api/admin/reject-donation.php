<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_BASE . '/admin/donations.php');
    exit;
}

verifyCsrf();

$adminId = (int)$_SESSION['user_id'];
$donationId = (int)($_POST['donation_id'] ?? 0);
$reason = trim($_POST['reason'] ?? 'Did not meet listing guidelines');

if ($donationId <= 0) {
    setFlash('error', 'Invalid donation ID.');
    header('Location: ' . APP_BASE . '/admin/donations.php');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, donor_id, title, status FROM donations WHERE id = ? FOR UPDATE');
    $stmt->execute([$donationId]);
    $donation = $stmt->fetch();

    if (!$donation) {
        setFlash('error', 'Donation not found.');
        header('Location: ' . APP_BASE . '/admin/donations.php');
        exit;
    }

    if ($donation['status'] === 'archived') {
        setFlash('error', 'Archived donations cannot be rejected directly.');
        header('Location: ' . APP_BASE . '/admin/donation-review.php?id=' . $donationId);
        exit;
    }

    if (!in_array($donation['status'], ['pending_verification', 'needs_information', 'draft'], true)) {
        setFlash('error', 'Donation cannot be rejected from its current status (' . $donation['status'] . ').');
        header('Location: ' . APP_BASE . '/admin/donations.php');
        exit;
    }

    $oldStatus = (string)$donation['status'];

    $upd = $pdo->prepare('
        UPDATE donations
        SET status = "rejected", rejection_reason = ?
        WHERE id = ?
    ');
    $upd->execute([$reason, $donationId]);

    logAudit($pdo, $adminId, 'admin', 'DONATION_STATUS_CHANGED', 'donation', $donationId, ['status' => $oldStatus], ['status' => 'rejected'], $reason);

    createNotification($pdo, (int)$donation['donor_id'], 'DONATION_REJECTED', 'Donation Listing Update', 'Your donation listing "' . $donation['title'] . '" was not approved. Reason: ' . $reason);

    setFlash('success', 'Donation rejected.');
    header('Location: ' . APP_BASE . '/admin/donations.php');
    exit;
} catch (Exception $e) {
    error_log($e->getMessage());
    setFlash('error', 'Failed to reject donation.');
    header('Location: ' . APP_BASE . '/admin/donations.php');
    exit;
}
