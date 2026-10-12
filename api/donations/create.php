<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /CommunityDono/user/offer-donation.php');
    exit;
}

verifyCsrf();

$userId = (int)$_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$categoryId = (int)($_POST['category_id'] ?? 0);
$conditionType = trim($_POST['condition_type'] ?? 'good');
$quantityTotal = max(1, (int)($_POST['quantity_total'] ?? 1));
$unit = trim($_POST['unit'] ?? 'pcs');
$description = trim($_POST['description'] ?? '');
$pickupMethod = trim($_POST['pickup_method'] ?? 'both');
$pickupAddress = trim($_POST['pickup_address'] ?? '');
$pickupCity = trim($_POST['pickup_city'] ?? '');
$pickupBarangay = trim($_POST['pickup_barangay'] ?? '');
$pickupDate = !empty($_POST['pickup_date']) ? trim($_POST['pickup_date']) : null;
$pickupTime = !empty($_POST['pickup_time']) ? trim($_POST['pickup_time']) : null;
$pickupNotes = trim($_POST['pickup_notes'] ?? '');

if (empty($title) || $categoryId <= 0 || empty($pickupCity)) {
    setFlash('error', 'Please fill in all required donation fields.');
    header('Location: /CommunityDono/user/offer-donation.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        INSERT INTO donations (
            donor_id, category_id, title, description, quantity_total, unit, condition_type,
            pickup_method, pickup_address, pickup_city, pickup_barangay, pickup_date, pickup_time, pickup_notes, status, submitted_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending_verification", NOW())
    ');
    $stmt->execute([
        $userId, $categoryId, $title, $description ?: null, $quantityTotal, $unit, $conditionType,
        $pickupMethod, $pickupAddress ?: null, $pickupCity, $pickupBarangay ?: null,
        $pickupDate, $pickupTime, $pickupNotes ?: null
    ]);
    $donationId = (int)$pdo->lastInsertId();

    // Handle uploaded images
    if (!empty($_FILES['images']['name'][0])) {
        $uploadBase = __DIR__ . '/../../uploads/donations/' . $donationId;
        if (!is_dir($uploadBase)) {
            mkdir($uploadBase, 0777, true);
        }

        $count = count($_FILES['images']['name']);
        for ($i = 0; $i < min(5, $count); $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['images']['tmp_name'][$i];
                $origName = basename($_FILES['images']['name'][$i]);
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
                    $destPath = $uploadBase . '/' . $randomName;
                    $relPath = 'uploads/donations/' . $donationId . '/' . $randomName;

                    if (move_uploaded_file($tmpName, $destPath)) {
                        $imgStmt = $pdo->prepare('
                            INSERT INTO donation_images (donation_id, file_path, file_name, mime_type, file_size, is_primary, sort_order)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ');
                        $isPrimary = ($i === 0) ? 1 : 0;
                        $imgStmt->execute([
                            $donationId, $relPath, $origName, mime_content_type($destPath) ?: 'image/jpeg',
                            filesize($destPath), $isPrimary, $i
                        ]);
                    }
                }
            }
        }
    }

    // Audit log
    logAudit($pdo, $userId, 'user', 'DONATION_SUBMITTED', 'donation', $donationId, null, ['title' => $title], 'Donation submitted for verification');

    // Notify user
    createNotification($pdo, $userId, 'DONATION_SUBMITTED', 'Donation Listing Submitted', 'Your donation "' . $title . '" has been received and is pending admin review.');

    $pdo->commit();

    setFlash('success', 'Thank you! Your donation was submitted and is pending quick review by our admin team.');
    header('Location: /CommunityDono/user/donations.php');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    setFlash('error', 'Failed to save donation listing. Please try again.');
    header('Location: /CommunityDono/user/offer-donation.php');
    exit;
}
