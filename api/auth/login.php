<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_BASE . '/pages/login.php');
    exit;
}

$redirect = trim($_POST['redirect'] ?? '');
$redirectQuery = (!empty($redirect) && isSafeInternalRedirect($redirect))
    ? '?redirect=' . urlencode($redirect)
    : '';

// Graceful CSRF validation
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Your session has expired. Please try signing in again.');
    header('Location: ' . APP_BASE . '/pages/login.php' . $redirectQuery);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['login_email'] = $email;
    setFlash('error', 'Please provide both email and password.');
    header('Location: ' . APP_BASE . '/pages/login.php' . $redirectQuery);
    exit;
}

// Fetch user (case-insensitive email search)
$stmt = $pdo->prepare('SELECT id, email, password_hash, first_name, last_name, role, status FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    $_SESSION['login_email'] = $email;
    setFlash('error', 'Invalid email or password.');
    header('Location: ' . APP_BASE . '/pages/login.php' . $redirectQuery);
    exit;
}

// Check status
if ($user['status'] === 'pending_verification') {
    $_SESSION['login_email'] = $email;
    setFlash('info', 'Your email is not yet verified. Please verify your email before logging in.');
    header('Location: ' . APP_BASE . '/pages/login.php' . $redirectQuery);
    exit;
}

if ($user['status'] === 'suspended') {
    setFlash('error', 'Your account has been suspended. Please contact our support team.');
    header('Location: ' . APP_BASE . '/pages/login.php?e=suspended');
    exit;
}

if ($user['status'] === 'banned') {
    setFlash('error', 'Your account has been permanently banned.');
    header('Location: ' . APP_BASE . '/pages/login.php?e=banned');
    exit;
}

// Successful login: clear login_email
unset($_SESSION['login_email']);

// Regenerate session id for security
session_regenerate_id(true);

$_SESSION['user_id']    = (int)$user['id'];
$_SESSION['role']       = $user['role'];
$_SESSION['status']     = $user['status'];
$_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['user_email'] = $user['email'];

// Update last login
$upd = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
$upd->execute([$user['id']]);

// Audit log
logAudit($pdo, (int)$user['id'], $user['role'], 'USER_LOGIN', 'user', (int)$user['id'], null, null, 'User signed in');

// Safe redirect check
if (!empty($redirect) && isSafeInternalRedirect($redirect)) {
    header('Location: ' . $redirect);
    exit;
}

if (in_array($user['role'], ['admin', 'superadmin'], true)) {
    header('Location: ' . APP_BASE . '/admin/index.php');
} else {
    header('Location: ' . APP_BASE . '/user/dashboard.php');
}
exit;