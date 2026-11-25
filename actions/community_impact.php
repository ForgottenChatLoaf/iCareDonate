<?php
/**
 * iCareDonate — Personal & Community Impact, Milestones & Achievements Endpoint
 * actions/community_impact.php
 *
 * Returns real database-computed aggregate community metrics, active campaign
 * milestones, and (when authenticated) the user's personal impact and earned
 * achievements. Zero fabricated numbers or leaderboards.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

try {
    // 1. Aggregate Community Impact (strictly from real records)
    $approvedDonations = (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE status IN ('approved','claimed','completed')")->fetchColumn();
    $availableResources = (int)$pdo->query("SELECT COALESCE(SUM(GREATEST(0, quantity - allocated_quantity)), 0) FROM donations WHERE status = 'approved'")->fetchColumn();
    $completedAllocations = (int)$pdo->query("SELECT COUNT(*) FROM donation_allocations WHERE status = 'completed'")->fetchColumn();
    $fulfilledRequests = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'completed'")->fetchColumn();
    $pendingRequests = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status IN ('pending','under_review')")->fetchColumn();
    $activeCampaignsCnt = (int)$pdo->query("SELECT COUNT(*) FROM campaigns WHERE status = 'active'")->fetchColumn();

    // 2. Active Campaign Milestones
    $campaigns = $pdo->query("
        SELECT c.id, c.title, c.description, c.category, c.target_quantity, c.target_unit, c.start_date, c.end_date, c.status,
               COALESCE((SELECT SUM(d.quantity) FROM donations d WHERE d.campaign_id = c.id AND d.status IN ('approved','claimed','completed')), 0) AS verified_quantity
        FROM campaigns c
        WHERE c.status IN ('active', 'completed')
        ORDER BY c.created_at DESC
        LIMIT 10
    ")->fetchAll();

    foreach ($campaigns as &$c) {
        $tq = max(1, (int)$c['target_quantity']);
        $vq = (int)$c['verified_quantity'];
        $c['progress_percent'] = min(100, (int)round(($vq / $tq) * 100));
    }
    unset($c);

    // 3. Personal Impact & Achievements (only if authenticated)
    $personal = null;
    $uid = getAuthenticatedUserId();
    if ($uid !== null) {
        $stmtU = $pdo->prepare("SELECT role FROM users WHERE id = :uid LIMIT 1");
        $stmtU->execute([':uid' => $uid]);
        $role = (string)($stmtU->fetchColumn() ?: 'donor');

        $donSubmitted = (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE donor_id = {$uid}")->fetchColumn();
        $donApproved  = (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE donor_id = {$uid} AND status IN ('approved','claimed','completed')")->fetchColumn();
        $donCompleted = (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE donor_id = {$uid} AND status = 'completed'")->fetchColumn();
        $matchedAlloc = (int)$pdo->query("SELECT COUNT(*) FROM donation_allocations da INNER JOIN donations d ON da.donation_id = d.id WHERE d.donor_id = {$uid} AND da.status = 'completed'")->fetchColumn();

        $reqSubmitted = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE requester_id = {$uid}")->fetchColumn();
        $reqApproved  = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE requester_id = {$uid} AND status = 'approved'")->fetchColumn();
        $reqCompleted = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE requester_id = {$uid} AND status = 'completed'")->fetchColumn();

        // Earned achievements
        $stmtAch = $pdo->prepare("
            SELECT a.id, a.slug, a.title, a.description, a.icon, a.badge_color,
                   ua.earned_reason, ua.earned_at
            FROM achievements a
            LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = :uid
            WHERE a.is_active = 1
            ORDER BY (ua.earned_at IS NOT NULL) DESC, a.id ASC
        ");
        $stmtAch->execute([':uid' => $uid]);
        $achievements = $stmtAch->fetchAll();

        $personal = [
            'role' => $role,
            'donor_stats' => [
                'submitted'         => $donSubmitted,
                'approved'          => $donApproved,
                'completed'         => $donCompleted,
                'matched_completed' => $matchedAlloc,
            ],
            'requester_stats' => [
                'submitted' => $reqSubmitted,
                'approved'  => $reqApproved,
                'completed' => $reqCompleted,
            ],
            'achievements' => $achievements,
        ];
    }

    echo json_encode([
        'success'   => true,
        'community' => [
            'approved_donations'    => $approvedDonations,
            'available_resources'   => $availableResources,
            'completed_allocations' => $completedAllocations,
            'fulfilled_requests'    => $fulfilledRequests,
            'pending_requests'      => $pendingRequests,
            'active_campaigns'      => $activeCampaignsCnt,
        ],
        'campaigns' => $campaigns,
        'personal'  => $personal,
    ]);
} catch (Throwable $e) {
    error_log('Community impact error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load community impact metrics.']);
}
