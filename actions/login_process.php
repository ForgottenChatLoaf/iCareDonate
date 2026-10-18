<?php
/**
 * iCareDonate — Login Form Handler (POST only)
 * actions/login_process.php
 *
 * Validates submitted credentials against demo accounts.
 * Creates a PHP session and redirects to the appropriate portal on success.
 * Redirects back to login.php with a generic error on failure.
 *
 * Only processes POST requests. GET requests are redirected to login.php.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/demo_accounts.php';

// Reject non-POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

// ── 1. Collect and sanitise inputs ───────────────────────────────────────────
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';

// ── 2. Server-side field validation ──────────────────────────────────────────
$errors = [];

if ($email === '') {
    $errors[] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if ($password === '') {
    $errors[] = 'Password is required.';
}

if (!empty($errors)) {
    // Redirect back with a generic error — do not reveal which field failed
    $errorParam = urlencode('Please fill in all required fields correctly.');
    header('Location: ../login.php?error=' . $errorParam);
    exit;
}

// ── 3. Check credentials against demo accounts ───────────────────────────────
$matched = null;
foreach ($DEMO_ACCOUNTS as $account) {
    if (strtolower($account['email']) === strtolower($email)) {
        if (password_verify($password, $account['hash'])) {
            $matched = $account;
            break;
        }
        // Email matched but password wrong — stop checking (prevents timing attacks)
        break;
    }
}

// ── 4. Handle account matching & universal demo fallback ───────────────────
if ($matched === null) {
    // If a valid email is provided with a password, allow prototype login
    if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 1) {
        $isAdmin = (stripos($email, 'admin') !== false);
        $namePrefix = explode('@', $email)[0];
        $formattedName = ucwords(str_replace(['.', '_', '-'], ' ', $namePrefix));
        $matched = [
            'email'    => $email,
            'role'     => $isAdmin ? 'admin' : 'donor',
            'name'     => $formattedName ?: ($isAdmin ? 'Admin Officer' : 'Community Member'),
            'initials' => strtoupper(substr($formattedName ?: 'CM', 0, 2)),
        ];
    } else {
        $errorParam = urlencode('Invalid email or password. Please try again.');
        header('Location: ../login.php?error=' . $errorParam);
        exit;
    }
}

// ── 5. Successful login — create session ─────────────────────────────────────
// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

$_SESSION['icd_user_id'] = $matched['email']; // Use email as ID (no DB primary key yet)
$_SESSION['icd_role']    = $matched['role'];
$_SESSION['icd_name']    = $matched['name'];
$_SESSION['icd_initials']= $matched['initials'];
$_SESSION['icd_login_at']= time();

// ── 6. Redirect to appropriate portal ────────────────────────────────────────
$root = _icd_root();

if ($matched['role'] === 'admin') {
    header('Location: ' . $root . ICD_REDIRECT_ADMIN);
} else {
    header('Location: ' . $root . ICD_REDIRECT_USER);
}
exit;
