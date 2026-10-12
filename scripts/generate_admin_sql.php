<?php
declare(strict_types=1);

/**
 * scripts/generate_admin_sql.php
 *
 * Generates a ready-to-run SQL INSERT statement for shared hosting environments
 * (such as InfinityFree or cPanel) that lack SSH / terminal command-line access.
 *
 * Usage:
 *   php scripts/generate_admin_sql.php "admin@example.com" "SecurePassword123!" "Firstname" "Lastname"
 *   OR interactively:
 *   php scripts/generate_admin_sql.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Access Denied: This script can only be run from the command line.\n");
}

echo "========================================================\n";
echo "   iCareDonate - Generate Admin SQL for phpMyAdmin\n";
echo "========================================================\n\n";

$email     = $argv[1] ?? '';
$password  = $argv[2] ?? '';
$firstName = $argv[3] ?? 'System';
$lastName  = $argv[4] ?? 'Administrator';

if (empty($email)) {
    echo "Enter Administrator Email: ";
    $email = trim((string)fgets(STDIN));
}

if (empty($password)) {
    echo "Enter Administrator Password (min 8 chars): ";
    $password = trim((string)fgets(STDIN));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "ERROR: Invalid email address.\n";
    exit(1);
}

if (strlen($password) < 8) {
    echo "ERROR: Password must be at least 8 characters long.\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$safeEmail = addslashes($email);
$safeFirst = addslashes($firstName);
$safeLast  = addslashes($lastName);

echo "\n--- COPY AND PASTE THIS QUERY INTO phpMyAdmin (SQL TAB) ---\n\n";
echo "INSERT INTO `users` (\n";
echo "  `email`, `password_hash`, `first_name`, `last_name`,\n";
echo "  `role`, `status`, `email_verified_at`, `created_at`, `updated_at`\n";
echo ") VALUES (\n";
echo "  '{$safeEmail}', '{$hash}', '{$safeFirst}', '{$safeLast}',\n";
echo "  'superadmin', 'active', NOW(), NOW(), NOW()\n";
echo ");\n\n";
echo "------------------------------------------------------------\n";
