<?php
declare(strict_types=1);
/**
 * api/v1/auth/register.php
 * POST — Accepts JSON { first_name, last_name, email, password, confirm_password, phone?, barangay?, city? }
 * Returns JSON { token, user }
 */
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$body          = getBody();
$firstName     = trim($body['first_name'] ?? '');
$lastName      = trim($body['last_name'] ?? '');
$email         = trim($body['email'] ?? '');
$phone         = trim($body['phone'] ?? '');
$barangay      = trim($body['barangay'] ?? '');
$city          = trim($body['city'] ?? '');
$password      = $body['password'] ?? '';
$confirmPass   = $body['confirm_password'] ?? '';

// Validation
$errors = [];
if (empty($firstName))  $errors['first_name'][]        = 'First name is required.';
if (empty($lastName))   $errors['last_name'][]         = 'Last name is required.';
if (empty($email))      $errors['email'][]             = 'Email is required.';
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'][] = 'Email is not valid.';
if (empty($password))   $errors['password'][]          = 'Password is required.';
elseif (strlen($password) < 8) $errors['password'][]  = 'Password must be at least 8 characters.';
if ($password !== $confirmPass) $errors['confirm_password'][] = 'Passwords do not match.';

if ($errors) jsonError('Validation failed.', 422, $errors);

// Duplicate email check
$chk = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
$chk->execute([$email]);
if ($chk->fetch()) {
    jsonError('An account with this email already exists.', 409, [
        'email' => ['This email is already registered.']
    ]);
}

// Hash & insert
$hash   = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$ins    = $pdo->prepare('
    INSERT INTO users (email, password_hash, first_name, last_name, phone, barangay, city, role, status, email_verified_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, "user", "active", NOW())
');
$ins->execute([
    $email, $hash, $firstName, $lastName,
    $phone    ?: null,
    $barangay ?: null,
    $city     ?: null,
]);
$userId = (int)$pdo->lastInsertId();

// Audit log
if (function_exists('logAudit')) {
    logAudit($pdo, $userId, 'user', 'USER_REGISTERED', 'user', $userId, null, ['email' => $email], 'API registration');
}

// Notification
if (function_exists('createNotification')) {
    createNotification($pdo, $userId, 'ACCOUNT_CREATED', 'Welcome to iCareDonate!',
        'Thank you for joining our community. You can now browse donations, offer items, or submit assistance requests.');
}

// Auto-login: generate token
$rawToken  = bin2hex(random_bytes(40));
$tokenHash = hash('sha256', $rawToken);
$expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
$ip        = $_SERVER['REMOTE_ADDR'] ?? null;
$ua        = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

$pdo->prepare('INSERT INTO user_sessions (id, user_id, ip_address, user_agent, payload, last_activity, expires_at) VALUES (?, ?, ?, ?, ?, NOW(), ?)')
    ->execute([$tokenHash, $userId, $ip, $ua, json_encode(['source' => 'api_token']), $expiresAt]);

jsonOk([
    'token' => $rawToken,
    'user'  => [
        'id'            => $userId,
        'email'         => $email,
        'first_name'    => $firstName,
        'last_name'     => $lastName,
        'name'          => $firstName . ' ' . $lastName,
        'role'          => 'user',
        'status'        => 'active',
        'profile_photo' => null,
    ],
], 201);
