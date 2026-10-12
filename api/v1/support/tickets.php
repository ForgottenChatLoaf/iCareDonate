<?php
declare(strict_types=1);
/**
 * api/v1/support/tickets.php
 * GET  - List support tickets for authenticated user
 * POST - Create a new support ticket
 */
require_once __DIR__ . '/../bootstrap.php';

$user = requireAuth($pdo);
$userId = (int)$user['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare('
            SELECT id, subject, message, status, admin_reply, replied_at, created_at, updated_at
            FROM support_tickets
            WHERE user_id = ?
            ORDER BY created_at DESC
        ');
        $stmt->execute([$userId]);
        $tickets = $stmt->fetchAll();

        jsonOk(['data' => $tickets]);
    } catch (Exception $e) {
        jsonError('Failed to fetch support tickets: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $data = getBody();
    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');

    if (empty($subject) || empty($message)) {
        jsonError('Subject and message are required.');
    }

    try {
        $stmt = $pdo->prepare('
            INSERT INTO support_tickets (user_id, email, subject, message, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, "open", NOW(), NOW())
        ');
        $stmt->execute([
            $userId,
            $user['email'],
            $subject,
            $message
        ]);

        $newId = (int)$pdo->lastInsertId();

        jsonOk([
            'message' => 'Support ticket created successfully.',
            'id' => $newId
        ], 201);
    } catch (Exception $e) {
        jsonError('Failed to create support ticket: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
