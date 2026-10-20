<?php
/**
 * iCareDonate — Account Registration Form Handler (POST only)
 * actions/signup_process.php
 *
 * Validates registration fields, prevents duplicate emails, hashes passwords with
 * PHP password_hash(PASSWORD_DEFAULT), and inserts accounts into MySQL via PDO.
 * Never allows public registration of administrator accounts.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

$isJson = (
    (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
    (!empty($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
);

function sendSignupResponse(bool $success, string $message, string $redirectUrl = '', ?array $user = null, bool $isJson = false): void {
    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($success ? 200 : 400);
        echo json_encode([
            'success'  => $success,
            'message'  => $message,
            'redirect' => $redirectUrl,
            'token'    => $success ? ('sess-' . bin2hex(random_bytes(16))) : null,
            'user'     => $user,
        ]);
        exit;
    }

    if (!$success) {
        header('Location: ' . _icd_root() . 'signup.php?error=' . urlencode($message));
        exit;
    }

    header('Location: ' . $redirectUrl);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isJson) {
        sendSignupResponse(false, 'Method Not Allowed. POST required.', '', null, true);
    }
    header('Location: ' . _icd_root() . 'signup.php');
    exit;
}

$rawInput = file_get_contents('php://input');
$jsonBody = json_decode($rawInput, true);

$firstName = trim($_POST['first_name'] ?? ($jsonBody['first_name'] ?? ''));
$lastName  = trim($_POST['last_name']  ?? ($jsonBody['last_name']  ?? ''));
$email     = trim($_POST['email']      ?? ($jsonBody['email']      ?? ''));
$password  = (string)($_POST['password'] ?? ($jsonBody['password'] ?? ''));
$confirmPw = (string)($_POST['confirm_password'] ?? ($jsonBody['confirm_password'] ?? ''));
$roleReq   = strtolower(trim($_POST['role'] ?? ($jsonBody['role'] ?? 'donor')));

// Combine name
$fullName = trim($firstName . ' ' . $lastName);
if ($fullName === '') {
    $fullName = trim($_POST['name'] ?? ($jsonBody['name'] ?? ''));
}

// ── 1. Validation ───────────────────────────────────────────────────────────
if ($fullName === '') {
    sendSignupResponse(false, 'Please enter your first and last name.', '', null, $isJson);
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendSignupResponse(false, 'Please enter a valid email address.', '', null, $isJson);
}

if (strlen($password) < 8) {
    sendSignupResponse(false, 'Password must be at least 8 characters in length.', '', null, $isJson);
}

if ($confirmPw !== '' && $password !== $confirmPw) {
    sendSignupResponse(false, 'Passwords do not match. Please verify and try again.', '', null, $isJson);
}

// Security: Public registration ONLY allows 'donor' or 'requester'. NEVER 'admin'.
$allowedRoles = ['donor', 'requester'];
$role = in_array($roleReq, $allowedRoles, true) ? $roleReq : 'donor';

// ── 2. Database Operations (PDO Prepared Statements) ───────────────────────
$pdo = getDB();
if ($pdo === null) {
    sendSignupResponse(false, 'Database service is currently unavailable. Please try again shortly.', '', null, $isJson);
}

try {
    // Check for duplicate email
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
    $checkStmt->execute([':email' => $email]);
    if ($checkStmt->fetch()) {
        sendSignupResponse(false, 'An account with this email address already exists. Please log in instead.', '', null, $isJson);
    }

    // Secure password hash
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user record
    $insertStmt = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name, :email, :hash, :role, NOW())'
    );
    $insertStmt->execute([
        ':name'  => $fullName,
        ':email' => strtolower($email),
        ':hash'  => $passwordHash,
        ':role'  => $role,
    ]);

    $newId = (int)$pdo->lastInsertId();

    // ── 3. Authenticate New User Immediately ────────────────────────────────
    session_regenerate_id(true);

    $initials = strtoupper(substr($firstName ?: 'U', 0, 1) . substr($lastName ?: $firstName, 0, 1));

    $_SESSION['icd_user_id'] = $newId;
    $_SESSION['icd_email']   = strtolower($email);
    $_SESSION['icd_role']    = $role;
    $_SESSION['icd_name']    = $fullName;
    $_SESSION['icd_initials']= $initials;
    $_SESSION['icd_login_at']= time();

    $root = _icd_root();
    $target = ($isJson || getenv('APP_ENV') === 'production') ? 'user/dashboard.html' : ($root . 'user/index.php');

    // Honor safe return redirect if provided
    $returnUrl = trim($_POST['redirect'] ?? ($jsonBody['redirect'] ?? ''));
    if ($returnUrl !== '' && !preg_match('#^(https?:)?//#i', $returnUrl)) {
        $target = $returnUrl;
    }

    sendSignupResponse(true, 'Account created successfully! Welcome to iCareDonate.', $target, [
        'id'         => $newId,
        'first_name' => $firstName ?: 'Community',
        'last_name'  => $lastName ?: 'Member',
        'email'      => strtolower($email),
        'role'       => $role,
    ], $isJson);

} catch (PDOException $e) {
    error_log('Registration database error: ' . $e->getMessage());
    sendSignupResponse(false, 'Unable to create account at this time. Please try again.', '', null, $isJson);
}
