<?php
declare(strict_types=1);
/**
 * api/v1/admin/transactions.php
 * GET - List all transactions/claims across the platform
 */
require_once __DIR__ . '/../bootstrap.php';

$admin = requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

try {
    $sql = '
        SELECT t.id, t.match_id, t.donor_id, t.requester_id, t.donation_id,
               t.quantity, t.status, t.pickup_method, t.pickup_date, t.pickup_time,
               t.pickup_address, t.claim_deadline, t.claimed_at, t.completed_at,
               t.created_at, t.updated_at,
               d.title AS donation_title,
               CONCAT(donor.first_name, " ", donor.last_name) AS donor,
               CONCAT(req.first_name, " ", req.last_name) AS requester
        FROM transactions t
        LEFT JOIN donations d ON t.donation_id = d.id
        LEFT JOIN users donor ON t.donor_id = donor.id
        LEFT JOIN users req ON t.requester_id = req.id
        ORDER BY t.updated_at DESC
    ';
    $stmt = $pdo->query($sql);
    $transactions = $stmt->fetchAll();

    jsonOk(['data' => $transactions]);
} catch (Exception $e) {
    jsonError('Failed to fetch admin transactions: ' . $e->getMessage(), 500);
}
