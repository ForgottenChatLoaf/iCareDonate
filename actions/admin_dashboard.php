<?php
/**
 * iCareDonate — Admin Dashboard Summary & Operations Overview Endpoint
 * actions/admin_dashboard.php
 *
 * Returns live aggregated counts, moderation queues, urgent requests,
 * and recent audit/system operations from MySQL.
 * Strictly checks administrator authorization.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error'   => 'Database connection is currently unavailable.',
        'code'    => 'DB_UNAVAILABLE'
    ]);
    exit;
}

$admin = requireAdminApi($pdo);

try {
    // 1. Pending Counts & Core Metrics
    $pendingDonations = 0;
    $pendingRequests = 0;
    $activeTransactions = 0;
    $totalMembers = 0;
    $totalUsers = 0;
    $fulfilledTx = 0;
    $beneficiariesServed = 0;
    $unscheduledInterviews = 0;
    $readyTransactions = 0;

    // Count pending donations
    try {
        $stmtDon = $pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'pending'");
        $pendingDonations = (int)$stmtDon->fetchColumn();
    } catch (PDOException $e) {}

    // Count active approved donations
    $activeDonations = 0;
    try {
        $stmtActiveDon = $pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'approved'");
        $activeDonations = (int)$stmtActiveDon->fetchColumn();
    } catch (PDOException $e) {}

    // Count pending requests (pending or under_review)
    try {
        $stmtReq = $pdo->query("SELECT COUNT(*) FROM requests WHERE status IN ('pending', 'under_review')");
        $pendingRequests = (int)$stmtReq->fetchColumn();
    } catch (PDOException $e) {}

    // Count active handovers / matched transactions
    try {
        $stmtTx = $pdo->query("SELECT COUNT(*) FROM donations WHERE status IN ('approved', 'claimed')");
        $activeTransactions = (int)$stmtTx->fetchColumn();
    } catch (PDOException $e) {}

    // Count fulfilled transactions (completed requests or handovers)
    try {
        $stmtFulfilled = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'completed'");
        $fulfilledTx = (int)$stmtFulfilled->fetchColumn();
    } catch (PDOException $e) {}

    // Count distinct beneficiaries served
    try {
        $stmtServed = $pdo->query("SELECT COUNT(DISTINCT requester_id) FROM requests WHERE status = 'completed'");
        $beneficiariesServed = (int)$stmtServed->fetchColumn();
    } catch (PDOException $e) {}

    // Count registered users
    try {
        $stmtUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
        $totalMembers = (int)$stmtUsers->fetchColumn();
        $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    } catch (PDOException $e) {}

    // Count unscheduled interviews
    try {
        $stmtInterviews = $pdo->query("SELECT COUNT(*) FROM interviews WHERE status = 'pending'");
        $unscheduledInterviews = (int)$stmtInterviews->fetchColumn();
    } catch (PDOException $e) {}

    // Count ready to close transactions (approved requests linked to donations)
    try {
        $stmtReady = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'approved' AND donation_id IS NOT NULL");
        $readyTransactions = (int)$stmtReady->fetchColumn();
    } catch (PDOException $e) {}

    // Count user roles
    $donorsCount = 0;
    $beneficiariesCount = 0;
    $bothCount = 0;
    try {
        $stmtRoleD = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'donor'");
        $donorsCount = (int)$stmtRoleD->fetchColumn();
        $stmtRoleR = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'requester'");
        $beneficiariesCount = (int)$stmtRoleR->fetchColumn();
    } catch (PDOException $e) {}

    // Count open inquiries (support tickets)
    $openInquiries = 0;
    try {
        $stmtInq = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'");
        $openInquiries = (int)$stmtInq->fetchColumn();
    } catch (PDOException $e) {}

    // 2. Urgent Attention Queues: Pending Donations
    $urgentDonations = [];
    try {
        $stmtUrgentDon = $pdo->prepare("
            SELECT d.id, d.title, d.category, d.quantity, 'pcs' AS unit,
                   d.condition AS condition_type, COALESCE(d.city, d.location, 'Metro Manila') AS pickup_city,
                   d.created_at, u.name AS donor_name, u.email AS donor_email
            FROM donations d
            LEFT JOIN users u ON d.donor_id = u.id
            WHERE d.status = 'pending'
            ORDER BY d.created_at ASC
            LIMIT 5
        ");
        $stmtUrgentDon->execute();
        $urgentDonations = $stmtUrgentDon->fetchAll();
    } catch (PDOException $e) {
        error_log('urgentDonations query error: ' . $e->getMessage());
    }

    // 3. Urgent Attention Queues: Pending Beneficiary Proofs / Requests
    $urgentRequests = [];
    try {
        $stmtUrgentReq = $pdo->prepare("
            SELECT r.id, r.requester_id AS user_id, COALESCE(d.title, 'Community Request') AS item_title,
                   r.quantity, 'items' AS unit, r.proof_type, r.status,
                   COALESCE(r.urgency, 'High') AS urgency,
                   u.name AS requester_name,
                   COALESCE(d.city, 'Metro Manila') AS city, r.created_at
            FROM requests r
            LEFT JOIN users u ON r.requester_id = u.id
            LEFT JOIN donations d ON r.donation_id = d.id
            WHERE r.status IN ('pending', 'under_review')
            ORDER BY r.created_at ASC
            LIMIT 5
        ");
        $stmtUrgentReq->execute();
        $urgentRequests = $stmtUrgentReq->fetchAll();
    } catch (PDOException $e) {
        error_log('urgentRequests query error: ' . $e->getMessage());
    }

    // 4. Recent System Operations (from audit_logs or recent donations/requests)
    $recentOperations = [];
    try {
        $stmtLogs = $pdo->query("
            SELECT id, action, actor_name, target_type, target_id, details, reason, created_at
            FROM audit_logs
            ORDER BY created_at DESC
            LIMIT 6
        ");
        $recentOperations = $stmtLogs->fetchAll();
    } catch (PDOException $e) {}

    // If audit_logs is empty, generate from real recent donations and requests
    if (empty($recentOperations)) {
        try {
            $stmtFallback = $pdo->query("
                (SELECT d.id, 'DONATION_SUBMITTED' AS action, u.name AS actor_name, 'donation' AS target_type,
                        d.id AS target_id, CONCAT(u.name, ' listed ', d.title) AS details,
                        CONCAT('Status: ', d.status) AS reason, d.created_at
                 FROM donations d JOIN users u ON d.donor_id = u.id ORDER BY d.created_at DESC LIMIT 3)
                UNION ALL
                (SELECT r.id, 'REQUEST_SUBMITTED' AS action, u.name AS actor_name, 'request' AS target_type,
                        r.id AS target_id, CONCAT(u.name, ' requested item #', r.donation_id) AS details,
                        CONCAT('Status: ', r.status) AS reason, r.created_at
                 FROM requests r JOIN users u ON r.requester_id = u.id ORDER BY r.created_at DESC LIMIT 3)
                ORDER BY created_at DESC LIMIT 6
            ");
            $recentOperations = $stmtFallback->fetchAll();
        } catch (PDOException $e) {}
    }

    // 5. 6-Month Monthly Trends for Bar Graph (Donations vs Requests)
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $ts = strtotime("-$i months");
        $mKey = date('Y-m', $ts);
        $mLabel = date('M', $ts);
        $months[$mKey] = [
            'key'       => $mKey,
            'label'     => $mLabel,
            'donations' => 0,
            'requests'  => 0,
        ];
    }

    try {
        $stmtMDon = $pdo->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
            FROM donations
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY ym
        ");
        while ($r = $stmtMDon->fetch()) {
            if (isset($months[$r['ym']])) {
                $months[$r['ym']]['donations'] = (int)$r['cnt'];
            }
        }
    } catch (PDOException $e) {}

    try {
        $stmtMReq = $pdo->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
            FROM requests
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY ym
        ");
        while ($r = $stmtMReq->fetch()) {
            if (isset($months[$r['ym']])) {
                $months[$r['ym']]['requests'] = (int)$r['cnt'];
            }
        }
    } catch (PDOException $e) {}

    $monthlyTrends = array_values($months);

    // 6. Category Distribution for Analytics Bars & Table
    $categoryDistribution = [];
    $categoryTable = [];
    $totalDonationsCount = 0;
    try {
        $totalDonationsCount = (int)$pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn();
        $stmtCatTable = $pdo->query("
            SELECT
                COALESCE(c.name, NULLIF(d.category, ''), 'General Goods') AS cat_name,
                SUM(CASE WHEN d.status = 'approved' THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN d.status IN ('claimed', 'completed') THEN 1 ELSE 0 END) AS fulfilled_count,
                SUM(CASE WHEN d.status = 'archived' THEN 1 ELSE 0 END) AS archived_count,
                COUNT(*) AS total_items
            FROM donations d
            LEFT JOIN categories c ON (d.category = c.slug OR d.category = c.name)
            GROUP BY cat_name
            ORDER BY total_items DESC
        ");
        while ($catRow = $stmtCatTable->fetch()) {
            $catTotal = (int)$catRow['total_items'];
            $share = $totalDonationsCount > 0 ? round(($catTotal / $totalDonationsCount) * 100) : 0;
            $entry = [
                'name'      => $catRow['cat_name'],
                'active'    => (int)$catRow['active_count'],
                'fulfilled' => (int)$catRow['fulfilled_count'],
                'archived'  => (int)$catRow['archived_count'],
                'total'     => $catTotal,
                'share_pct' => $share,
            ];
            $categoryTable[] = $entry;
            if (count($categoryDistribution) < 5) {
                $categoryDistribution[] = [
                    'cat_name'    => $catRow['cat_name'],
                    'total_items' => $catTotal,
                ];
            }
        }
    } catch (PDOException $e) {}

    // 7. Fulfillment Status Breakdown
    $statusBreakdown = [
        'pending'   => $pendingDonations,
        'approved'  => $activeDonations,
        'claimed'   => 0,
        'completed' => $fulfilledTx,
    ];
    try {
        $stmtStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM donations GROUP BY status");
        while ($r = $stmtStatus->fetch()) {
            $st = strtolower((string)$r['status']);
            if (isset($statusBreakdown[$st])) {
                $statusBreakdown[$st] = (int)$r['cnt'];
            } elseif ($st === 'archived') {
                $statusBreakdown['completed'] += (int)$r['cnt'];
            }
        }
    } catch (PDOException $e) {}

    // 8. Platform Health Rates
    $totalRequestsCount = $pendingRequests + $fulfilledTx;
    $fulfillmentRate = $totalRequestsCount > 0 ? round(($fulfilledTx / $totalRequestsCount) * 100) : 0;
    $verifiedDonorsRate = $donorsCount > 0 ? 100 : 0;
    try {
        $activeDonors = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'donor' AND status = 'active'")->fetchColumn();
        if ($donorsCount > 0) {
            $verifiedDonorsRate = round(($activeDonors / $donorsCount) * 100);
        }
    } catch (PDOException $e) {}

    // User Role percentages
    $roleTotal = $donorsCount + $beneficiariesCount + $bothCount;
    $donorPct = $roleTotal > 0 ? round(($donorsCount / $roleTotal) * 100) : 0;
    $beneficiaryPct = $roleTotal > 0 ? round(($beneficiariesCount / $roleTotal) * 100) : 0;
    $bothPct = $roleTotal > 0 ? round(($bothCount / $roleTotal) * 100) : 0;

    echo json_encode([
        'success' => true,
        'admin'   => [
            'id'    => $admin['id'],
            'name'  => $admin['name'],
            'email' => $admin['email'],
            'role'  => $admin['role']
        ],
        'counts'  => [
            'pending_donations'      => $pendingDonations,
            'pending_requests'       => $pendingRequests,
            'active_donations'       => $activeDonations,
            'active_transactions'    => $activeTransactions,
            'fulfilled_transactions' => $fulfilledTx,
            'beneficiaries_served'   => $beneficiariesServed,
            'unscheduled_interviews' => $unscheduledInterviews,
            'ready_transactions'     => $readyTransactions,
            'open_inquiries'         => $openInquiries,
            'total_members'          => $totalMembers,
            'total_users'            => $totalUsers,
        ],
        'user_roles' => [
            'donors_count'        => $donorsCount,
            'donors_pct'          => $donorPct,
            'beneficiaries_count' => $beneficiariesCount,
            'beneficiaries_pct'   => $beneficiaryPct,
            'both_count'          => $bothCount,
            'both_pct'            => $bothPct,
        ],
        'platform_health' => [
            'fulfillment_rate'     => $fulfillmentRate,
            'verified_donors_rate' => $verifiedDonorsRate,
        ],
        'monthly_trends'        => $monthlyTrends,
        'category_distribution' => $categoryDistribution,
        'category_table'        => $categoryTable,
        'status_breakdown'      => $statusBreakdown,
        'urgent_donations'      => $urgentDonations,
        'urgent_requests'       => $urgentRequests,
        'recent_operations'     => $recentOperations,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while compiling the dashboard statistics.'
    ]);
}
