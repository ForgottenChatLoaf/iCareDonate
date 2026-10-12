<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_BASE . '/pages/browse.php');
    exit;
}

verifyCsrf();

$userId = (int)$_SESSION['user_id'];
$donationId = (int)($_POST['donation_id'] ?? 0);
$quantityRequested = max(1, (int)($_POST['quantity_requested'] ?? 1));
$urgencyLevel = in_array($_POST['urgency_level'] ?? '', ['low', 'medium', 'high'], true) ? $_POST['urgency_level'] : 'medium';
$purpose = trim($_POST['purpose'] ?? '');

if ($donationId <= 0 || empty($purpose)) {
    setFlash('error', 'Please complete all required fields.');
    header('Location: ' . APP_BASE . '/user/request-donation.php?id=' . $donationId);
    exit;
}

// Fetch donation to check availability
$stmt = $pdo->prepare('SELECT id, donor_id, title, quantity_total, quantity_reserved, quantity_claimed, status FROM donations WHERE id = ?');
$stmt->execute([$donationId]);
$donation = $stmt->fetch();

if (!$donation || $donation['status'] !== 'approved') {
    setFlash('error', 'This donation is no longer available for requests.');
    header('Location: ' . APP_BASE . '/pages/browse.php');
    exit;
}

if ((int)$donation['donor_id'] === $userId) {
    setFlash('error', 'You cannot request your own donation listing.');
    header('Location: ' . APP_BASE . '/user/donations.php');
    exit;
}

// Enforce rule: No multiple active requests for the same donation
$activeReqStmt = $pdo->prepare('
    SELECT id FROM requests 
    WHERE requester_id = ? AND donation_id = ? AND status IN ("submitted", "under_review", "approved", "matched")
    LIMIT 1
');
$activeReqStmt->execute([$userId, $donationId]);
if ($activeReqStmt->fetch()) {
    setFlash('error', 'You already have an active request for this donation item.');
    header('Location: ' . APP_BASE . '/user/requests.php');
    exit;
}

$available = (int)$donation['quantity_total'] - (int)$donation['quantity_reserved'] - (int)$donation['quantity_claimed'];
if ($quantityRequested > $available) {
    setFlash('error', 'Requested quantity exceeds available stock (' . $available . ' available).');
    header('Location: ' . APP_BASE . '/user/request-donation.php?id=' . $donationId);
    exit;
}

try {
    $pdo->beginTransaction();

    $insert = $pdo->prepare('
        INSERT INTO requests (requester_id, donation_id, quantity_requested, purpose, urgency_level, status, submitted_at)
        VALUES (?, ?, ?, ?, ?, "submitted", NOW())
    ');
    $insert->execute([$userId, $donationId, $quantityRequested, $purpose, $urgencyLevel]);
    $requestId = (int)$pdo->lastInsertId();

    // Check proof document upload
    if (!empty($_FILES['proof_document']['name']) && $_FILES['proof_document']['error'] === UPLOAD_ERR_OK) {
        $proofDir = __DIR__ . '/../../uploads/private/proofs/' . $requestId;
        if (!is_dir($proofDir)) {
            mkdir($proofDir, 0777, true);
        }

        $origName = basename($_FILES['proof_document']['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
            $destPath = $proofDir . '/' . $randomName;
            $relPath = 'uploads/private/proofs/' . $requestId . '/' . $randomName;

            if (move_uploaded_file($_FILES['proof_document']['tmp_name'], $destPath)) {
                $proofStmt = $pdo->prepare('
                    INSERT INTO request_proofs (request_id, file_path, file_name, mime_type, file_size)
                    VALUES (?, ?, ?, ?, ?)
                ');
                $proofStmt->execute([
                    $requestId, $relPath, $origName, mime_content_type($destPath) ?: 'application/octet-stream', filesize($destPath)
                ]);

                // Update request status to proof_review
                $updStatus = $pdo->prepare('UPDATE requests SET status = "proof_review" WHERE id = ?');
                $updStatus->execute([$requestId]);
            }
        }
    }

    // Audit log
    logAudit($pdo, $userId, 'user', 'REQUEST_CREATED', 'request', $requestId, null, ['donation_id' => $donationId, 'quantity' => $quantityRequested], 'Submitted assistance request');

    // Notify requester
    createNotification($pdo, $userId, 'REQUEST_RECEIVED', 'Request Submitted Successfully', 'Your request for "' . $donation['title'] . '" has been submitted for admin verification.');

    $pdo->commit();

    setFlash('success', 'Your request was successfully submitted! Our team will review your application.');
    header('Location: ' . APP_BASE . '/user/requests.php');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    setFlash('error', 'Failed to submit request. Please try again.');
    header('Location: ' . APP_BASE . '/user/request-donation.php?id=' . $donationId);
    exit;
}
