<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /CommunityDono/pages/browse.php');
    exit;
}

verifyCsrf();

$reporterId = (int)$_SESSION['user_id'];
$targetType = in_array($_POST['target_type'] ?? '', ['donation', 'user'], true) ? $_POST['target_type'] : 'donation';
$targetId   = (int)($_POST['target_id'] ?? 0);
$reason     = trim($_POST['reason'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($targetId <= 0 || empty($reason)) {
    setFlash('error', 'Please provide a valid report reason.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/CommunityDono/pages/browse.php'));
    exit;
}

try {
    $stmt = $pdo->prepare('
        INSERT INTO problem_reports (reporter_id, target_type, target_id, reason, description, status, created_at)
        VALUES (?, ?, ?, ?, ?, "pending", NOW())
    ');
    $stmt->execute([$reporterId, $targetType, $targetId, $reason, $description ?: null]);
    $reportId = (int)$pdo->lastInsertId();

    // Audit log
    logAudit($pdo, $reporterId, 'user', 'REPORT_SUBMITTED', 'report', $reportId, null, [
        'target_type' => $targetType, 'target_id' => $targetId, 'reason' => $reason
    ], 'User submitted problem report');

    setFlash('success', 'Thank you for keeping our community safe. Your report has been submitted to moderators.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/CommunityDono/pages/browse.php'));
    exit;
} catch (Exception $e) {
    error_log($e->getMessage());
    setFlash('error', 'Failed to submit report. Please try again.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/CommunityDono/pages/browse.php'));
    exit;
}
