<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'] ?? 'user';
    logAudit($pdo, $uid, $role, 'USER_LOGOUT', 'user', $uid, null, null, 'User signed out');
}

session_unset();
session_destroy();

header('Location: ' . appUrl('/'));
exit;