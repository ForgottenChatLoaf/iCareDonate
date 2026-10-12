<?php
declare(strict_types=1);
/**
 * api/v1/user/donations.php
 * GET - Returns all donations posted by the authenticated user
 */
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

$user = requireAuth($pdo);
$userId = (int)$user['id'];

try {
    $stmt = $pdo->prepare('
        SELECT d.id, d.title, d.category_id, c.name AS category_name,
               d.quantity_total, d.quantity_reserved, d.quantity_claimed,
               d.unit, d.condition_type, d.pickup_city, d.pickup_method,
               d.status, d.created_at, d.submitted_at
        FROM donations d
        LEFT JOIN categories c ON d.category_id = c.id
        WHERE d.donor_id = ?
        ORDER BY d.created_at DESC
    ');
    $stmt->execute([$userId]);
    $donations = $stmt->fetchAll();

    jsonOk(['data' => $donations]);
} catch (Exception $e) {
    jsonError('Failed to fetch user donations: ' . $e->getMessage(), 500);
}
