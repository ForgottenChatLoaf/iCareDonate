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

$token = getBearerToken();
if ($token) {
    $hash = hash('sha256', $token);
    $pdo->prepare('DELETE FROM user_sessions WHERE id = ?')->execute([$hash]);
}

jsonOk(['message' => 'Logged out successfully.']);
