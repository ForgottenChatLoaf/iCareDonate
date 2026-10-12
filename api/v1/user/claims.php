<?php
declare(strict_types=1);
/**
 * api/v1/user/claims.php
 * GET - Returns all transactions/claims involving the authenticated user
 */
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

$user = requireAuth($pdo);
$userId = (int)$user['id'];

try {
    $stmt = $pdo->prepare('
        SELECT t.id, t.match_id, t.donor_id, t.requester_id, t.donation_id,
               t.quantity, t.status, t.pickup_method, t.pickup_date, t.pickup_time,
               t.pickup_address, t.claim_deadline, t.claimed_at, t.completed_at,
               t.created_at, t.updated_at,
               d.title AS donation_title,
               CASE 
                 WHEN t.donor_id = ? THEN "Donor"
                 ELSE "Recipient"
               END AS role,
               CONCAT(donor.first_name, " ", donor.last_name) AS donor_name,
               CONCAT(req.first_name, " ", req.last_name) AS requester_name
        FROM transactions t
        LEFT JOIN donations d ON t.donation_id = d.id
        LEFT JOIN users donor ON t.donor_id = donor.id
        LEFT JOIN users req ON t.requester_id = req.id
        WHERE t.donor_id = ? OR t.requester_id = ?
        ORDER BY t.updated_at DESC
    ');
    $stmt->execute([$userId, $userId, $userId]);
    $claims = $stmt->fetchAll();

    jsonOk(['data' => $claims]);
} catch (Exception $e) {
    jsonError('Failed to fetch user claims: ' . $e->getMessage(), 500);
}
