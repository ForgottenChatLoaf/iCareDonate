<?php
declare(strict_types=1);
/**
 * api/v1/user/history.php
 * GET - Returns past and completed activities for the user
 */
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

$user = requireAuth($pdo);
$userId = (int)$user['id'];

try {
    $history = [];

    // Past completed or cancelled transactions
    $stmtT = $pdo->prepare('
        SELECT d.title AS donation_title, 
               "Transaction" AS type, 
               t.status, 
               t.updated_at
        FROM transactions t
        JOIN donations d ON t.donation_id = d.id
        WHERE (t.donor_id = ? OR t.requester_id = ?)
    ');
    $stmtT->execute([$userId, $userId]);
    $history = array_merge($history, $stmtT->fetchAll());

    // Past donations
    $stmtD = $pdo->prepare('
        SELECT title AS donation_title,
               "Donation Offer" AS type,
               status,
               updated_at
        FROM donations
        WHERE donor_id = ?
    ');
    $stmtD->execute([$userId]);
    $history = array_merge($history, $stmtD->fetchAll());

    // Past requests
    $stmtR = $pdo->prepare('
        SELECT d.title AS donation_title,
               "Item Request" AS type,
               r.status,
               r.updated_at
        FROM requests r
        JOIN donations d ON r.donation_id = d.id
        WHERE r.requester_id = ?
    ');
    $stmtR->execute([$userId]);
    $history = array_merge($history, $stmtR->fetchAll());

    usort($history, fn($a, $b) => strtotime($b['updated_at'] ?? '0') - strtotime($a['updated_at'] ?? '0'));

    jsonOk(['data' => $history]);
} catch (Exception $e) {
    jsonError('Failed to fetch user history: ' . $e->getMessage(), 500);
}
