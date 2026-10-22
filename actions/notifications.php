<?php
/**
 * iCareDonate — User Notifications Endpoint (GET & POST)
 * actions/notifications.php
 *
 * GET:  Returns authenticated user's private notifications.
 * POST: Marks authenticated user's unread notifications as read.
 * Strictly checks authentication via session or Bearer token.
 * Never trusts a client-supplied user_id.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

// 1. Enforce Authentication
$userId = getAuthenticatedUserId();
if ($userId === null) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please log in to view notifications.',
    ]);
    exit;
}

// 2. Database Connection
$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Database service is currently unavailable.',
    ]);
    exit;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ── Handle POST: Mark Notifications as Read ────────────────────────────────
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $jsonBody = json_decode($rawInput, true) ?? [];
    $action   = trim($_POST['action'] ?? ($jsonBody['action'] ?? 'mark_all_read'));

    try {
        if ($action === 'mark_all_read') {
            $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0');
            $stmt->execute([':uid' => $userId]);

            http_response_code(200);
            echo json_encode([
                'success'      => true,
                'message'      => 'All notifications marked as read.',
                'unread_count' => 0,
            ]);
            exit;
        }

        if ($action === 'mark_read') {
            $notifId = (int)($_POST['id'] ?? ($jsonBody['id'] ?? 0));
            if ($notifId > 0) {
                $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid');
                $stmt->execute([':id' => $notifId, ':uid' => $userId]);
            }
            $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
            $stmtCount->execute([':uid' => $userId]);
            $remaining = (int)$stmtCount->fetchColumn();

            http_response_code(200);
            echo json_encode([
                'success'      => true,
                'message'      => 'Notification marked as read.',
                'unread_count' => $remaining,
            ]);
            exit;
        }

        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Unrecognized notification action.',
        ]);
        exit;

    } catch (PDOException $e) {
        error_log('Error updating notifications: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update notifications.',
        ]);
        exit;
    }
}

// ── Handle GET: Fetch User's Notifications ─────────────────────────────────
try {
    $notifications = [];
    $unreadCount = 0;

    try {
        $stmt = $pdo->prepare('SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 50');
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $r) {
            $isRead = (int)($r['is_read'] ?? 0);
            if ($isRead === 0) {
                $unreadCount++;
            }

            // Relative time formatting
            $ts = strtotime((string)$r['created_at']);
            $secs = time() - $ts;
            if ($secs < 60) {
                $timeAgo = 'Just now';
            } elseif ($secs < 3600) {
                $timeAgo = floor($secs / 60) . 'm ago';
            } elseif ($secs < 86400) {
                $timeAgo = floor($secs / 3600) . 'h ago';
            } elseif ($secs < 604800) {
                $timeAgo = floor($secs / 86400) . 'd ago';
            } else {
                $timeAgo = date('M j, Y', $ts);
            }

            $notifications[] = [
                'id'         => (int)$r['id'],
                'title'      => $r['title'],
                'message'    => $r['message'],
                'type'       => $r['type'],
                'is_read'    => ($isRead === 1),
                'created_at' => $r['created_at'],
                'time_ago'   => $timeAgo,
            ];
        }
    } catch (PDOException $e) {
        // Table may not exist yet
    }

    http_response_code(200);
    echo json_encode([
        'success'       => true,
        'notifications' => $notifications,
        'unread_count'  => $unreadCount,
    ]);

} catch (PDOException $e) {
    error_log('Error fetching notifications: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to retrieve notifications at this time.',
    ]);
}
