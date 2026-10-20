<?php
/**
 * iCareDonate — Health Check Endpoint
 * health.php
 *
 * Used by Render's HTTP health check to determine if the service is alive.
 * Returns only a generic status — never leaks DB credentials or stack traces.
 *
 * Render health check path: /health.php
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$status = 'healthy';
$httpCode = 200;

// Attempt DB ping — generic failure only, no details exposed
try {
    require_once __DIR__ . '/includes/database.php';
    $db = getDB();
    if ($db === null) {
        $status = 'degraded';
        $httpCode = 503;
    } else {
        $db->query('SELECT 1');
    }
} catch (Throwable $e) {
    // Log internally, never expose to the client
    error_log('Health check DB error: ' . $e->getMessage());
    $status = 'degraded';
    $httpCode = 503;
}

http_response_code($httpCode);
echo json_encode(['status' => $status]);
