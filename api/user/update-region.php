<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /CommunityDono/user/settings.php?tab=region');
    exit;
}

verifyCsrf();

$userId = (int)$_SESSION['user_id'];
$language = trim($_POST['language'] ?? 'en');
$country  = trim($_POST['country'] ?? 'Philippines');
$timezone = trim($_POST['timezone'] ?? 'Asia/Manila');
$dateFormat = trim($_POST['date_format'] ?? 'M d, Y');

try {
    $stmt = $pdo->prepare('
        UPDATE users
        SET pref_language = ?, pref_country = ?, pref_timezone = ?, pref_date_format = ?, updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([$language, $country, $timezone, $dateFormat, $userId]);

    logAudit($pdo, $userId, 'user', 'USER_PREFERENCES_UPDATED', 'user', $userId, null, null, 'Updated language and regional preferences');

    setFlash('success', 'Regional and display preferences updated.');
    header('Location: /CommunityDono/user/settings.php?tab=region');
    exit;
} catch (Exception $e) {
    error_log($e->getMessage());
    setFlash('error', 'Failed to update regional settings.');
    header('Location: /CommunityDono/user/settings.php?tab=region');
    exit;
}
