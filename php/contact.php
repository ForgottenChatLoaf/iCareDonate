<?php
declare(strict_types=1);

/**
 * php/contact.php
 * Form Processing for Contact Inquiries
 * Validates input, sanitizes data, and persists into MySQL database.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /CommunityDono/pages/contact.html');
    exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    $_SESSION['contact_error'] = 'Please complete all required fields.';
    header('Location: /CommunityDono/pages/contact.html?error=required');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['contact_error'] = 'Please provide a valid email address.';
    header('Location: /CommunityDono/pages/contact.html?error=invalid_email');
    exit;
}

try {
    $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $stmt = $pdo->prepare('
        INSERT INTO support_tickets (user_id, subject, message, email, status, created_at)
        VALUES (?, ?, ?, ?, "open", NOW())
    ');
    $stmt->execute([$userId, "[$name] " . $subject, $message, $email]);

    $_SESSION['contact_success'] = 'Thank you! Your message has been sent to our team.';
    header('Location: /CommunityDono/pages/contact.html?success=1');
    exit;
} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    $_SESSION['contact_error'] = 'Failed to submit inquiry. Please try again later.';
    header('Location: /CommunityDono/pages/contact.html?error=failed');
    exit;
}
