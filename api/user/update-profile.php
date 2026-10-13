<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_BASE . '/user/profile.php');
    exit;
}

verifyCsrf();

$userId = (int)$_SESSION['user_id'];
$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$barangay = trim($_POST['barangay'] ?? '');
$city = trim($_POST['city'] ?? '');

if (empty($firstName) || empty($lastName)) {
    setFlash('error', 'First name and last name are required.');
    header('Location: ' . APP_BASE . '/user/profile.php');
    exit;
}

try {
    $stmt = $pdo->prepare('
        UPDATE users
        SET first_name = ?, last_name = ?, phone = ?, barangay = ?, city = ?, updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([$firstName, $lastName, $phone ?: null, $barangay ?: null, $city ?: null, $userId]);

    $_SESSION['user_name'] = $firstName . ' ' . $lastName;

    logAudit($pdo, $userId, 'user', 'USER_PROFILE_UPDATED', 'user', $userId, null, ['name' => $firstName . ' ' . $lastName], 'Updated profile details');

    setFlash('success', 'Profile updated successfully.');
    header('Location: ' . APP_BASE . '/user/profile.php');
    exit;
} catch (Exception $e) {
    error_log($e->getMessage());
    setFlash('error', 'Failed to update profile. Please try again.');
    header('Location: ' . APP_BASE . '/user/profile.php');
    exit;
}
