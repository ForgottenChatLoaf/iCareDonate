<?php
/**
 * iCareDonate — Database Connection (PDO Singleton)
 * includes/database.php
 *
 * On Render, credentials are injected as environment variables.
 * On XAMPP/localhost, falls back to hardcoded local defaults.
 *
 * Environment variables read:
 *   DB_HOST      — MySQL hostname  (default: 127.0.0.1)
 *   DB_PORT      — MySQL port      (default: 3306)
 *   DB_DATABASE  — Database name   (default: icaredonate)
 *   DB_USERNAME  — DB user         (default: root)
 *   DB_PASSWORD  — DB password     (default: empty)
 */

declare(strict_types=1);

$host    = getenv('DB_HOST')     ?: '127.0.0.1';
$port    = getenv('DB_PORT')     ?: '3306';
$dbName  = getenv('DB_DATABASE') ?: 'icaredonate';
$dbUser  = getenv('DB_USERNAME') ?: 'root';
$dbPass  = getenv('DB_PASSWORD') ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

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
