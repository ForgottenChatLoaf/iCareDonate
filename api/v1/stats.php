<?php
declare(strict_types=1);
/**
 * api/v1/stats.php
 * GET - Platform impact statistics
 */
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

try {
    $statsWhere = publicDonationWhereClause('');
    $donationsCount = (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE {$statsWhere}")->fetchColumn();
    $claimedItems   = (int)$pdo->query('SELECT COALESCE(SUM(quantity_claimed), 0) FROM donations')->fetchColumn();
    $communityUsers = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn();

    jsonOk([
        'data' => [
            'donations_count' => $donationsCount,
            'claimed_items'   => $claimedItems,
            'community_users' => $communityUsers
        ]
    ]);
} catch (Exception $e) {
    jsonError('Failed to fetch statistics: ' . $e->getMessage(), 500);
}
