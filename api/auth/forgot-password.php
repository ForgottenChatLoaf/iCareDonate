<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /CommunityDono/pages/forgot-password.php');
    exit;
}

verifyCsrf();

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please enter a valid email address.');
    header('Location: /CommunityDono/pages/forgot-password.php');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, first_name, email FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $insert = $pdo->prepare('
            INSERT INTO password_resets (user_id, token, expires_at)
            VALUES (?, ?, ?)
        ');
        $insert->execute([(int)$user['id'], $token, $expiresAt]);

        logAudit($pdo, (int)$user['id'], 'user', 'PASSWORD_RESET_REQUESTED', 'user', (int)$user['id'], null, null, 'Requested password reset link');

        // For local development demonstration: redirect directly with flash containing token link
        $resetUrl = "/CommunityDono/pages/reset-password.php?token={$token}";
        setFlash('success', 'Password reset generated! For local demo: <a href="' . $resetUrl . '" style="color: inherit; text-decoration: underline; font-weight: bold;">Click here to reset your password</a>');
        header('Location: /CommunityDono/pages/forgot-password.php');
        exit;
    } else {
        // Generic message for security
        setFlash('info', 'If an account exists with that email, a password reset link has been issued.');
        header('Location: /CommunityDono/pages/forgot-password.php');
        exit;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    setFlash('error', 'Unable to process request. Please try again.');
    header('Location: /CommunityDono/pages/forgot-password.php');
    exit;
}
