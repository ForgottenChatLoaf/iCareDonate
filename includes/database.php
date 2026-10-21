<?php
/**
 * iCareDonate — Database Connection (PDO Singleton)
 * includes/database.php
 *
 * On Render, credentials are injected as environment variables.
 * On XAMPP/localhost, loads from .env file in the project root.
 *
 * Environment variables read:
 *   DB_HOST      — MySQL hostname
 *   DB_PORT      — MySQL port
 *   DB_DATABASE  — Database name
 *   DB_USERNAME  — DB user
 *   DB_PASSWORD  — DB password
 *   DB_SSL_CA    — (optional) path to CA cert for SSL connections (Aiven)
 */

declare(strict_types=1);

// Load .env file for local development (XAMPP) if it exists
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if (!getenv($key)) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
            }
        }
    }
}

$host    = getenv('DB_HOST')     ?: '127.0.0.1';
$port    = getenv('DB_PORT')     ?: '3306';
$dbName  = getenv('DB_DATABASE') ?: 'icaredonate';
$dbUser  = getenv('DB_USERNAME') ?: 'root';
$dbPass  = getenv('DB_PASSWORD') ?: '';
$sslCa   = getenv('DB_SSL_CA')   ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Add SSL CA cert for Aiven / remote MySQL (SSL-REQUIRED connections)
if ($sslCa && file_exists($sslCa)) {
    $options[PDO::MYSQL_ATTR_SSL_CA]               = $sslCa;
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    $pdo = null;
}

/**
 * Returns the active PDO database connection or null on failure.
 */
function getDB(): ?PDO {
    global $pdo;
    return $pdo;
}
