<?php
/**
 * iCareDonate — User Activity Log Endpoint
 * actions/activity_log.php
 *
 * Replaces internal messaging with an authenticated, chronological Activity Log.
 * Strictly checks session / Bearer token authentication.
 * Returns only authorized activity records for the current user.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$userId = getAuthenticatedUserId();
if ($userId === null) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to view your activity log.',
    ]);
    exit;
}

$pdo = getDB();
if ($pdo === null) {
    http_response_code(200);
    echo json_encode([
        'success'    => true,
        'activities' => [],
        'message'    => 'Database service unavailable. Displaying offline activity.',
    ]);
    exit;
}

$filterType = trim((string)($_GET['type'] ?? 'all'));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 25)));

$activities = [];

try {
    // 1. Donation Activities
    if ($filterType === 'all' || $filterType === 'donations') {
        $stmtDon = $pdo->prepare('
            SELECT id, title, category, quantity, unit, status, created_at, updated_at
            FROM donations
            WHERE user_id = :uid
            ORDER BY created_at DESC
            LIMIT 30
        ');
        $stmtDon->execute([':uid' => $userId]);
        while ($d = $stmtDon->fetch()) {
            $createdTs = strtotime((string)$d['created_at']);
            $status = (string)$d['status'];

            $statusText = match($status) {
                'pending'   => 'Donation listing submitted and awaiting admin verification.',
                'approved'  => 'Donation verified & published to the community catalog.',
                'claimed'   => 'Donation claimed by matched community recipient. Coordination in progress.',
                'completed' => 'Item handover successfully completed and confirmed.',
                'cancelled' => 'Donation listing cancelled or archived.',
                default     => 'Donation status updated to ' . htmlspecialchars($status) . '.',
            };

            $activities[] = [
                'id'          => 'act-don-' . $d['id'],
                'ref_id'      => $d['id'],
                'type'        => 'donation',
                'category'    => 'Donations',
                'title'       => $d['title'],
                'description' => $statusText,
                'status'      => $status,
                'created_at'  => $d['created_at'],
                'timestamp'   => $createdTs,
                'link'        => 'donations.html',
            ];
        }
    }

    // 2. Resource Request Activities
    if ($filterType === 'all' || $filterType === 'requests') {
        $stmtReq = $pdo->prepare('
            SELECT id, item_title, quantity, unit, status, created_at, updated_at
            FROM requests
            WHERE user_id = :uid
            ORDER BY created_at DESC
            LIMIT 30
        ');
        $stmtReq->execute([':uid' => $userId]);
        while ($r = $stmtReq->fetch()) {
            $createdTs = strtotime((string)$r['created_at']);
            $status = (string)$r['status'];

            $statusText = match($status) {
                'pending'      => 'Assistance request submitted and queued for verification.',
                'under_review' => 'Request documentation currently under coordinator review.',
                'approved'     => 'Assistance request approved and matched for resource coordination.',
                'completed'    => 'Requested assistance item safely received and finalized.',
                'rejected'     => 'Request could not be approved based on verification criteria.',
                'cancelled'    => 'Assistance request cancelled.',
                default        => 'Request status updated to ' . htmlspecialchars($status) . '.',
            };

            $activities[] = [
                'id'          => 'act-req-' . $r['id'],
                'ref_id'      => $r['id'],
                'type'        => 'request',
                'category'    => 'Requests',
                'title'       => $r['item_title'],
                'description' => $statusText,
                'status'      => $status,
                'created_at'  => $r['created_at'],
                'timestamp'   => $createdTs,
                'link'        => 'request-details.html?id=' . $r['id'],
            ];
        }
    }

    // 3. System & Administrative Notifications
    if ($filterType === 'all' || $filterType === 'system' || $filterType === 'notifications') {
        $stmtNotif = $pdo->prepare('
            SELECT id, title, message, type, is_read, created_at
            FROM notifications
            WHERE user_id = :uid
            ORDER BY created_at DESC
            LIMIT 25
        ');
        $stmtNotif->execute([':uid' => $userId]);
        while ($n = $stmtNotif->fetch()) {
            $createdTs = strtotime((string)$n['created_at']);
            $activities[] = [
                'id'          => 'act-notif-' . $n['id'],
                'ref_id'      => $n['id'],
                'type'        => 'system',
                'category'    => 'System Update',
                'title'       => $n['title'],
                'description' => $n['message'],
                'status'      => ($n['is_read'] ? 'read' : 'unread'),
                'created_at'  => $n['created_at'],
                'timestamp'   => $createdTs,
                'link'        => 'notifications.html',
            ];
        }
    }

    // Sort chronologically: newest first
    usort($activities, static function($a, $b) {
        return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
    });

    // Limit output
    $activities = array_slice($activities, 0, $limit);

    // Compute relative time string
    $now = time();
    foreach ($activities as &$act) {
        $diff = max(0, $now - ($act['timestamp'] ?? $now));
        if ($diff < 60) {
            $act['time_ago'] = 'Just now';
        } elseif ($diff < 3600) {
            $act['time_ago'] = (int)floor($diff / 60) . 'm ago';
        } elseif ($diff < 86400) {
            $act['time_ago'] = (int)floor($diff / 3600) . 'h ago';
        } elseif ($diff < 604800) {
            $act['time_ago'] = (int)floor($diff / 86400) . 'd ago';
        } else {
            $act['time_ago'] = date('M j, Y', $act['timestamp']);
        }
    }

    echo json_encode([
        'success'    => true,
        'count'      => count($activities),
        'activities' => $activities,
    ]);

} catch (PDOException $e) {
    error_log('Error retrieving activity log: ' . $e->getMessage());
    echo json_encode([
        'success'    => false,
        'activities' => [],
        'message'    => 'Unable to load activity records at this time.',
    ]);
}
