<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_BASE . '/user/donations.php');
    exit;
}

verifyCsrf();

$userId = (int)$_SESSION['user_id'];
$donationId = (int)($_POST['donation_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$categoryId = (int)($_POST['category_id'] ?? 0);
$conditionType = trim($_POST['condition_type'] ?? 'good');
$quantityTotal = max(1, (int)($_POST['quantity_total'] ?? 1));
$unit = trim($_POST['unit'] ?? 'pcs');
$description = trim($_POST['description'] ?? '');
$pickupCity = trim($_POST['pickup_city'] ?? '');
$pickupBarangay = trim($_POST['pickup_barangay'] ?? '');

if ($donationId <= 0 || empty($title) || $categoryId <= 0) {
    setFlash('error', 'Please fill in all required fields.');
    header('Location: ' . APP_BASE . '/user/edit-donation.php?id=' . $donationId);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, donor_id, status FROM donations WHERE id = ? AND donor_id = ?');
    $stmt->execute([$donationId, $userId]);
    $donation = $stmt->fetch();

    if (!$donation || in_array($donation['status'], ['approved', 'fully_allocated', 'archived', 'cancelled', 'expired'], true)) {
        setFlash('error', 'This donation cannot be updated.');
        header('Location: ' . APP_BASE . '/user/donations.php');
        exit;
    }

    $upd = $pdo->prepare('
        UPDATE donations
        SET title = ?, category_id = ?, condition_type = ?, quantity_total = ?, unit = ?,
            description = ?, pickup_city = ?, pickup_barangay = ?, status = "pending_verification",
            submitted_at = NOW(), updated_at = NOW()
        WHERE id = ?
    ');
    $upd->execute([
        $title, $categoryId, $conditionType, $quantityTotal, $unit,
        $description ?: null, $pickupCity, $pickupBarangay ?: null, $donationId
    ]);

    logAudit($pdo, $userId, 'user', 'DONATION_EDITED', 'donation', $donationId, null, ['title' => $title], 'Updated and resubmitted donation listing');

    setFlash('success', 'Donation updated and resubmitted for admin verification.');
    header('Location: ' . APP_BASE . '/user/donations.php');
    exit;
} catch (Exception $e) {
    error_log($e->getMessage());
    setFlash('error', 'Failed to update donation.');
    header('Location: ' . APP_BASE . '/user/edit-donation.php?id=' . $donationId);
    exit;
}
