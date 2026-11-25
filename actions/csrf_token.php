<?php
/**
 * iCareDonate — CSRF Token Issuer (GET)
 * actions/csrf_token.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

echo json_encode([
    'success'    => true,
    'csrf_token' => icd_get_csrf_token(),
]);
