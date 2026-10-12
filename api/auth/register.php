<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /CommunityDono/pages/signup.php');
    exit;
}

verifyCsrf();

$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$barangay  = trim($_POST['barangay'] ?? '');
$city      = trim($_POST['city'] ?? '');
$password  = $_POST['password'] ?? '';
$confirm   = $_POST['confirm_password'] ?? '';

if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
    setFlash('error', 'Please fill in all required fields.');
    header('Location: /CommunityDono/pages/signup.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please enter a valid email address.');
    header('Location: /CommunityDono/pages/signup.php');
    exit;
}

if (strlen($password) < 8) {
    setFlash('error', 'Password must be at least 8 characters long.');
    header('Location: /CommunityDono/pages/signup.php');
    exit;
}

if ($password !== $confirm) {
    setFlash('error', 'Passwords do not match. Please re-enter.');
    header('Location: /CommunityDono/pages/signup.php');
    exit;
}

// Check if email already exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    setFlash('error', 'An account with this email address already exists. Please log in.');
    header('Location: /CommunityDono/pages/login.php');
    exit;
}

// Hash password
$hash = password_hash($password, PASSWORD_BCRYPT);

// Insert user (For development, status defaults to 'active' or auto-verifies)
$insert = $pdo->prepare('
    INSERT INTO users (email, password_hash, first_name, last_name, phone, barangay, city, role, status, email_verified_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, "user", "active", NOW())
');
$insert->execute([$email, $hash, $firstName, $lastName, $phone ?: null, $barangay ?: null, $city ?: null]);
$userId = (int)$pdo->lastInsertId();

// Create audit log
logAudit($pdo, $userId, 'user', 'USER_REGISTERED', 'user', $userId, null, ['email' => $email], 'New user registered and activated');

// Auto login
session_regenerate_id(true);
$_SESSION['user_id']    = $userId;
$_SESSION['role']       = 'user';
$_SESSION['status']     = 'active';
$_SESSION['user_name']  = $firstName . ' ' . $lastName;
$_SESSION['user_email'] = $email;

// Welcome notification
createNotification($pdo, $userId, 'ACCOUNT_CREATED', 'Welcome to iCareDonate!', 'Thank you for joining our community. You can now browse donations, offer items, or submit assistance requests.');

setFlash('success', 'Account created successfully! Welcome to iCareDonate.');
header('Location: /CommunityDono/user/dashboard.php');
exit;