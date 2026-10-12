<?php
declare(strict_types=1);
/**
 * api/v1/auth/logout.php
 * POST — Revokes the current Bearer token.
 */
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

// Invalidate PHP server session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

$token = getBearerToken();
if ($token) {
    $hash = hash('sha256', $token);
    $pdo->prepare('DELETE FROM user_sessions WHERE id = ?')->execute([$hash]);
}

jsonOk(['message' => 'Logged out successfully.']);
