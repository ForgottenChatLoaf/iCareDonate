<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /CommunityDono/admin/requests.php');
    exit;
}

verifyCsrf();

$adminId = (int)$_SESSION['user_id'];
$requestId = (int)($_POST['request_id'] ?? 0);

if ($requestId <= 0) {
    setFlash('error', 'Invalid request ID.');
    header('Location: /CommunityDono/admin/requests.php');
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM requests WHERE id = ?');
    $stmt->execute([$requestId]);

    logAudit($pdo, $adminId, 'admin', 'REQUEST_DELETED', 'request', $requestId, null, null, 'Administrator deleted request record');

    setFlash('success', 'Request record deleted.');
    header('Location: /CommunityDono/admin/requests.php');
    exit;
} catch (Exception $e) {
    error_log($e->getMessage());
    setFlash('error', 'Cannot delete request: Active matches or transactions are linked.');
    header('Location: /CommunityDono/admin/requests.php');
    exit;
}
