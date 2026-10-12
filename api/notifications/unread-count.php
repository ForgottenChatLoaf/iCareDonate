<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$count = 0;

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    $count = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    error_log($e->getMessage());
}

echo json_encode(['count' => $count]);
exit;
