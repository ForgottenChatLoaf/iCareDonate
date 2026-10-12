<?php
declare(strict_types=1);
/**
 * api/v1/contact.php
 * POST — Handle contact us inquiry form submissions
 * Inserts inquiry ticket into support_tickets table
 */
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$data = getBody();
$name    = trim($data['name'] ?? '');
$email   = trim($data['email'] ?? '');
$subject = trim($data['subject'] ?? 'General Inquiry');
$message = trim($data['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    jsonError('Please complete all required fields (Name, Email, Message).', 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Please provide a valid email address.', 422);
}

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    $formattedSubject = "[$name] " . $subject;

    $stmt = $pdo->prepare('
        INSERT INTO support_tickets (user_id, subject, message, email, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, "open", NOW(), NOW())
    ');
    $stmt->execute([$userId, $formattedSubject, $message, $email]);
    $newId = (int)$pdo->lastInsertId();

    jsonOk([
        'message' => 'Thanks. We received your inquiry.',
        'id'      => $newId,
        'email'   => $email,
    ], 201);
} catch (Exception $e) {
    error_log('Contact inquiry error: ' . $e->getMessage());
    jsonError('Failed to submit inquiry. Please try again later.', 500);
}
