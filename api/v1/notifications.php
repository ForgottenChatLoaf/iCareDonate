<?php
declare(strict_types=1);
/**
 * api/v1/notifications.php
 * GET   - List user notifications
 * PATCH - Mark notification as read
 */
require_once __DIR__ . '/bootstrap.php';

$user = requireAuth($pdo);
$userId = (int)$user['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare('SELECT id, type, title, message, related_type, related_id, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll();
        jsonOk(['data' => $items, 'items' => $items]);
    } catch (Exception $e) {
        jsonError('Failed to fetch notifications: ' . $e->getMessage(), 500);
    }
}

if ($method === 'PATCH' || $method === 'POST') {
    $data = getBody();
    $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($data['id'] ?? 0);
    $markAll = isset($_GET['all']) || !empty($data['mark_all']);

    try {
        if ($markAll) {
            $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ?');
            $stmt->execute([$userId]);
            jsonOk(['message' => 'All notifications marked as read.']);
        } elseif ($id > 0) {
            $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);
            jsonOk(['message' => 'Notification marked as read.']);
        } else {
            jsonError('Invalid notification ID.');
        }
    } catch (Exception $e) {
        jsonError('Failed to update notifications: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
