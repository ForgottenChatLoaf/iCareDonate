<?php
declare(strict_types=1);

/**
 * scripts/create_admin.php
 *
 * Secure CLI Script to create the initial production administrator account.
 *
 * Usage:
 *   php scripts/create_admin.php "admin@example.com" "SecurePassword123!" "Admin" "User"
 *   OR interactively:
 *   php scripts/create_admin.php
 *
 * Security:
 * - Cannot be executed via web browser (CLI only).
 * - Enforces minimum 8-character password length.
 * - Uses password_hash(PASSWORD_DEFAULT).
 * - Creates account with role='superadmin' and status='active'.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Access Denied: This script can only be executed from the command line.\n");
}

require_once __DIR__ . '/../config/database.php';

echo "====================================================\n";
echo "   iCareDonate - Production Administrator Setup\n";
echo "====================================================\n\n";

$email = $argv[1] ?? '';
$password = $argv[2] ?? '';
$firstName = $argv[3] ?? '';
$lastName = $argv[4] ?? '';

if (empty($email)) {
    echo "Enter Administrator Email: ";
    $email = trim((string)fgets(STDIN));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "ERROR: Invalid email address.\n";
    exit(1);
}

// Check if user already exists
$stmt = $pdo->prepare('SELECT id, role, email FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    echo "ERROR: A user with email '{$email}' already exists (ID: {$existing['id']}, Role: {$existing['role']}).\n";
    exit(1);
}

if (empty($password)) {
    echo "Enter Administrator Password (min 8 chars): ";
    $password = trim((string)fgets(STDIN));
}

if (strlen($password) < 8) {
    echo "ERROR: Password must be at least 8 characters long.\n";
    exit(1);
}

if (empty($firstName)) {
    echo "Enter First Name [System]: ";
    $firstName = trim((string)fgets(STDIN));
    if ($firstName === '') $firstName = 'System';
}

if (empty($lastName)) {
    echo "Enter Last Name [Administrator]: ";
    $lastName = trim((string)fgets(STDIN));
    if ($lastName === '') $lastName = 'Administrator';
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $insert = $pdo->prepare('
        INSERT INTO users (
            email, password_hash, first_name, last_name,
            role, status, email_verified_at, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?,
            "superadmin", "active", NOW(), NOW(), NOW()
        )
    ');
    $insert->execute([$email, $hash, $firstName, $lastName]);
    $newId = (int)$pdo->lastInsertId();

    echo "\nSUCCESS! Superadmin account created successfully.\n";
    echo "  User ID:    {$newId}\n";
    echo "  Email:      {$email}\n";
    echo "  Name:       {$firstName} {$lastName}\n";
    echo "  Role:       superadmin\n";
    echo "  Status:     active\n\n";
    echo "You may now log in via /pages/login.php\n";
    exit(0);
} catch (PDOException $e) {
    echo "DATABASE ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
