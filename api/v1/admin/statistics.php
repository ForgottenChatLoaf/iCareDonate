<?php
declare(strict_types=1);
/**
 * api/v1/admin/statistics.php
 * GET - Aggregate platform metrics and statistics
 */
require_once __DIR__ . '/../bootstrap.php';

$admin = requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

try {
    $stats = [];

    // Total counts
    $stats['total_users'] = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn();
    $stats['total_donations'] = (int)$pdo->query('SELECT COUNT(*) FROM donations')->fetchColumn();
    $stats['approved_donations'] = (int)$pdo->query('SELECT COUNT(*) FROM donations WHERE status = "approved"')->fetchColumn();
    $stats['pending_donations'] = (int)$pdo->query('SELECT COUNT(*) FROM donations WHERE status = "pending_verification"')->fetchColumn();
    $stats['total_requests'] = (int)$pdo->query('SELECT COUNT(*) FROM requests')->fetchColumn();
    $stats['approved_requests'] = (int)$pdo->query('SELECT COUNT(*) FROM requests WHERE status = "approved"')->fetchColumn();
    $stats['total_transactions'] = (int)$pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
    $stats['completed_transactions'] = (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE status = "completed"')->fetchColumn();
    $stats['total_categories'] = (int)$pdo->query('SELECT COUNT(*) FROM categories WHERE is_active = 1')->fetchColumn();
    $stats['total_interviews'] = (int)$pdo->query('SELECT COUNT(*) FROM interviews')->fetchColumn();

    // Breakdown by condition
    $condStmt = $pdo->query('SELECT condition_type, COUNT(*) as count FROM donations GROUP BY condition_type');
    $stats['by_condition'] = $condStmt->fetchAll();

    // Breakdown by category
    $catStmt = $pdo->query('
        SELECT c.name, COUNT(d.id) as count 
        FROM categories c 
        LEFT JOIN donations d ON c.id = d.category_id 
        GROUP BY c.id, c.name 
        ORDER BY count DESC
    ');
    $stats['by_category'] = $catStmt->fetchAll();

    jsonOk(['data' => $stats, 'stats' => $stats]);
} catch (Exception $e) {
    jsonError('Failed to fetch statistics: ' . $e->getMessage(), 500);
}
