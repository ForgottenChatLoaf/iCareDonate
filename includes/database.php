<?php
/**
 * iCareDonate — Database Connection (PDO Singleton)
 * includes/database.php
 *
 * Connects to MySQL/MariaDB via PDO using prepared statement defaults.
 * Provides getDB() and $pdo for authentication and account operations.
 */

declare(strict_types=1);

$host    = '127.0.0.1';
$dbName  = 'icaredonate';
$dbUser  = 'root';
$dbPass  = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // Graceful fallback: check if alternative database name 'icare_donate' exists
    try {
        $dsnFallback = "mysql:host={$host};dbname=icare_donate;charset={$charset}";
        $pdo = new PDO($dsnFallback, $dbUser, $dbPass, $options);
    } catch (PDOException $eFallback) {
        error_log('Database connection error: ' . $eFallback->getMessage());
        $pdo = null;
    }
}

/**
 * Returns the active PDO database connection or null on failure.
 */
function getDB(): ?PDO {
    global $pdo;
    return $pdo;
}
