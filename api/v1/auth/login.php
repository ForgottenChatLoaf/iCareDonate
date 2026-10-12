<?php
declare(strict_types=1);
/**
 * api/v1/auth/login.php
 * POST — Accepts JSON { email, password }
 * Returns JSON { token, user }
 */
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$body     = getBody();
$email    = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

if (empty($email) || empty($password)) {
    jsonError('Email and password are required.', 422, [
        'email'    => empty($email)    ? ['Email is required.']    : [],
        'password' => empty($password) ? ['Password is required.'] : [],
    ]);
}

// Fetch user (case-insensitive)
$stmt = $pdo->prepare(
    'SELECT id, email, password_hash, first_name, last_name, role, status, profile_photo
       FROM users
      WHERE LOWER(email) = LOWER(?)
      LIMIT 1'
);
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonError('Invalid email or password.', 401);
}

// Status checks
match ($user['status']) {
    'pending_verification' => jsonError('Your email is not verified. Please verify before logging in.', 403),
    'suspended'            => jsonError('Your account has been suspended. Contact support.', 403),
    'banned'               => jsonError('Your account has been permanently banned.', 403),
    default                => null,
};

// Establish secure server-side PHP session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_regenerate_id(true);

$_SESSION['user_id']    = (int)$user['id'];
$_SESSION['role']       = $user['role'];
$_SESSION['status']     = $user['status'];
$_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['user_email'] = $user['email'];

// Generate token
$rawToken   = bin2hex(random_bytes(40));
$tokenHash  = hash('sha256', $rawToken);
$expiresAt  = date('Y-m-d H:i:s', strtotime('+30 days'));
$ip         = $_SERVER['REMOTE_ADDR'] ?? null;
$ua         = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

// Store in user_sessions (reuse existing table, payload = JSON metadata)
$ins = $pdo->prepare(
    'INSERT INTO user_sessions (id, user_id, ip_address, user_agent, payload, last_activity, expires_at)
     VALUES (?, ?, ?, ?, ?, NOW(), ?)'
);
$ins->execute([
    $tokenHash,
    $user['id'],
    $ip,
    $ua,
    json_encode(['source' => 'api_token']),
    $expiresAt,
]);

// Update last_login_at
$pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
    ->execute([$user['id']]);

// Audit log (if function exists)
if (function_exists('logAudit')) {
    logAudit($pdo, (int)$user['id'], $user['role'], 'USER_LOGIN', 'user', (int)$user['id'], null, null, 'API login');
}

jsonOk([
    'token' => $rawToken,
    'user'  => [
        'id'           => (int)$user['id'],
        'email'        => $user['email'],
        'first_name'   => $user['first_name'],
        'last_name'    => $user['last_name'],
        'name'         => $user['first_name'] . ' ' . $user['last_name'],
        'role'         => $user['role'],
        'status'       => $user['status'],
        'profile_photo'=> $user['profile_photo'],
    ],
]);
