<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_BASE . '/user/settings.php?tab=linked');
    exit;
}

verifyCsrf();

$userId   = (int)$_SESSION['user_id'];
$provider = trim($_POST['provider'] ?? '');

if (!in_array($provider, ['google', 'facebook'], true)) {
    setFlash('error', 'Invalid provider.');
    header('Location: ' . APP_BASE . '/user/settings.php?tab=linked');
    exit;
}

try {
    $column = ($provider === 'google') ? 'google_linked' : 'facebook_linked';
    
    // Toggle status
    $stmt = $pdo->prepare("UPDATE users SET $column = IF($column = 1, 0, 1), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$userId]);

    logAudit($pdo, $userId, 'user', 'LINKED_ACCOUNT_TOGGLED', 'user', $userId, null, ['provider' => $provider], 'Toggled linked account');

    setFlash('success', ucfirst($provider) . ' account status updated.');
    header('Location: ' . APP_BASE . '/user/settings.php?tab=linked');
    exit;
} catch (Exception $e) {
    error_log($e->getMessage());
    setFlash('error', 'Failed to update linked account.');
    header('Location: ' . APP_BASE . '/user/settings.php?tab=linked');
    exit;
}
