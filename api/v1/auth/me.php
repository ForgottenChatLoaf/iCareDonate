<?php
declare(strict_types=1);
/**
 * api/v1/auth/me.php
 * GET — Returns the currently authenticated user's profile.
 */
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

$user = requireAuth($pdo);

jsonOk([
    'user' => [
        'id'            => (int)$user['id'],
        'email'         => $user['email'],
        'first_name'    => $user['first_name'],
        'last_name'     => $user['last_name'],
        'name'          => $user['first_name'] . ' ' . $user['last_name'],
        'role'          => $user['role'],
        'status'        => $user['status'],
        'profile_photo' => $user['profile_photo'],
    ],
]);
