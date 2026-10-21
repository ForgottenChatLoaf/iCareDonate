<?php
/**
 * iCareDonate — User Dashboard Summary & Activity Endpoint
 * actions/user_dashboard.php
 *
 * Returns authenticated user profile, real aggregated statistics,
 * and user-specific recent activity from MySQL.
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
        'message' => 'Unauthorized. Please log in to access your dashboard.',
    ]);
    exit;
}

// 2. Database Connection
$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Database service is currently unavailable. Please try again shortly.',
    ]);
    exit;
}

try {
    // 3. Retrieve Authenticated User Profile
    $stmtUser = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = :uid LIMIT 1');
    $stmtUser->execute([':uid' => $userId]);
    $userRow = $stmtUser->fetch();

    if (!$userRow) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'User account not found.',
        ]);
        exit;
    }

    $nameParts = explode(' ', trim($userRow['name'] ?? ''));
    $firstName = $nameParts[0] ?? 'Community';
    $lastName  = implode(' ', array_slice($nameParts, 1)) ?: 'Member';
    $initials  = strtoupper(substr($firstName, 0, 1) . substr($lastName ?: $firstName, 0, 1));

    $userData = [
        'id'         => (int)$userRow['id'],
        'name'       => $userRow['name'],
        'first_name' => $firstName,
        'last_name'  => $lastName,
        'initials'   => $initials,
        'email'      => $userRow['email'],
        'role'       => $userRow['role'],
    ];

    // 4. Calculate User-Specific Counts
    $donationsCount = 0;
    $requestsCount  = 0;
    $inProgressCount = 0;
    $completedCount = 0;
    $unreadNotifs   = 0;

    // Check donations table
    try {
        $stmtDonations = $pdo->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN status IN ('pending', 'approved') THEN 1 ELSE 0 END) AS in_prog, SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) AS comp FROM donations WHERE donor_id = :uid");
        $stmtDonations->execute([':uid' => $userId]);
        $donRow = $stmtDonations->fetch();
        if ($donRow) {
            $donationsCount  = (int)($donRow['total'] ?? 0);
            $inProgressCount += (int)($donRow['in_prog'] ?? 0);
            $completedCount  += (int)($donRow['comp'] ?? 0);
        }
    } catch (PDOException $e) {
        // Table might not exist yet if migration has not been run
    }

    // Check requests table
    try {
        $stmtRequests = $pdo->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN status IN ('pending', 'approved') THEN 1 ELSE 0 END) AS in_prog, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS comp FROM requests WHERE requester_id = :uid");
        $stmtRequests->execute([':uid' => $userId]);
        $reqRow = $stmtRequests->fetch();
        if ($reqRow) {
            $requestsCount   = (int)($reqRow['total'] ?? 0);
            $inProgressCount += (int)($reqRow['in_prog'] ?? 0);
            $completedCount  += (int)($reqRow['comp'] ?? 0);
        }
    } catch (PDOException $e) {
        // Table might not exist yet
    }

    // Check unread notifications count
    try {
        $stmtNotifCount = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
        $stmtNotifCount->execute([':uid' => $userId]);
        $unreadNotifs = (int)$stmtNotifCount->fetchColumn();
    } catch (PDOException $e) {
        // Table might not exist yet
    }

    // 5. Gather Recent Activity Exclusively for This User
    $activityItems = [];

    // User's recent donations
    try {
        $stmtRecentDon = $pdo->prepare("SELECT id, title, status, 'donation' AS type, created_at FROM donations WHERE donor_id = :uid ORDER BY created_at DESC LIMIT 5");
        $stmtRecentDon->execute([':uid' => $userId]);
        while ($d = $stmtRecentDon->fetch()) {
            $statusLabel = match($d['status']) {
                'pending'   => 'Donation listing pending admin verification: ',
                'approved'  => 'Donation listing verified & published: ',
                'archived'  => 'Donation handover completed / archived: ',
                'rejected'  => 'Donation listing rejected: ',
                default     => 'Donation update for: ',
            };
            $activityItems[] = [
                'id'         => 'don-' . $d['id'],
                'type'       => 'donation',
                'title'      => $d['title'],
                'text'       => $statusLabel . $d['title'],
                'status'     => $d['status'],
                'created_at' => $d['created_at'],
                'timestamp'  => strtotime((string)$d['created_at']),
            ];
        }
    } catch (PDOException $e) {}

    // User's recent requests
    try {
        $stmtRecentReq = $pdo->prepare("
            SELECT r.id, COALESCE(d.title, 'Assistance Request') AS title, r.status, 'request' AS type, r.created_at
            FROM requests r
            LEFT JOIN donations d ON r.donation_id = d.id
            WHERE r.requester_id = :uid
            ORDER BY r.created_at DESC LIMIT 5
        ");
        $stmtRecentReq->execute([':uid' => $userId]);
        while ($r = $stmtRecentReq->fetch()) {
            $statusLabel = match($r['status']) {
                'pending'      => 'Assistance request submitted: ',
                'approved'     => 'Assistance request approved: ',
                'completed'    => 'Assistance item received & completed: ',
                'rejected'     => 'Assistance request rejected: ',
                default        => 'Assistance request update: ',
            };
            $activityItems[] = [
                'id'         => 'req-' . $r['id'],
                'type'       => 'request',
                'title'      => $r['title'],
                'text'       => $statusLabel . $r['title'],
                'status'     => $r['status'],
                'created_at' => $r['created_at'],
                'timestamp'  => strtotime((string)$r['created_at']),
            ];
        }
    } catch (PDOException $e) {}

    // Sort combined activity by created timestamp descending
    usort($activityItems, static function($a, $b) {
        return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
    });
    $activityItems = array_slice($activityItems, 0, 5);

    // Format human readable relative time helper
    foreach ($activityItems as &$item) {
        $secs = time() - ($item['timestamp'] ?? time());
        if ($secs < 60) {
            $item['time_ago'] = 'Just now';
        } elseif ($secs < 3600) {
            $m = (int)floor($secs / 60);
            $item['time_ago'] = $m . 'm ago';
        } elseif ($secs < 86400) {
            $h = (int)floor($secs / 3600);
            $item['time_ago'] = $h . 'h ago';
        } elseif ($secs < 604800) {
            $d = (int)floor($secs / 86400);
            $item['time_ago'] = $d . 'd ago';
        } else {
            $item['time_ago'] = date('M j, Y', $item['timestamp']);
        }
        unset($item['timestamp']);
    }
    unset($item);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'user'    => $userData,
        'stats'   => [
            'donations'            => $donationsCount,
            'requests'             => $requestsCount,
            'in_progress'          => $inProgressCount,
            'completed'            => $completedCount,
            'unread_notifications' => $unreadNotifs,
        ],
        'recent_activity' => $activityItems,
    ]);

} catch (PDOException $e) {
    error_log('Error in user_dashboard.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while loading dashboard data.',
    ]);
}
