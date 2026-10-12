<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_BASE . '/user/settings.php?tab=privacy');
    exit;
}

verifyCsrf();

$userId = (int)$_SESSION['user_id'];
$profileVis = in_array($_POST['profile_visibility'] ?? '', ['public', 'members', 'hidden'], true) ? $_POST['profile_visibility'] : 'public';
$contactVis = in_array($_POST['contact_visibility'] ?? '', ['matched_only', 'members', 'hidden'], true) ? $_POST['contact_visibility'] : 'matched_only';
$showArea   = !empty($_POST['show_area']) ? 1 : 0;
$deviceLoc  = !empty($_POST['device_location']) ? 1 : 0;

try {
    $stmt = $pdo->prepare('
        UPDATE users
        SET privacy_profile_visibility = ?, privacy_contact_visibility = ?,
            privacy_show_area = ?, privacy_device_location = ?, updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([$profileVis, $contactVis, $showArea, $deviceLoc, $userId]);

    logAudit($pdo, $userId, 'user', 'USER_PREFERENCES_UPDATED', 'user', $userId, null, null, 'Updated privacy & location preferences');

    setFlash('success', 'Privacy and location settings saved.');
    header('Location: ' . APP_BASE . '/user/settings.php?tab=privacy');
    exit;
} catch (Exception $e) {
    error_log($e->getMessage());
    setFlash('error', 'Failed to save preferences.');
    header('Location: ' . APP_BASE . '/user/settings.php?tab=privacy');
    exit;
}
