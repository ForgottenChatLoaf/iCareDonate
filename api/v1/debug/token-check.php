<?php
declare(strict_types=1);
/**
 * api/v1/debug/token-check.php
 * GET — Quick diagnostic to confirm Authorization header is being received.
 * REMOVE or restrict this in production!
 */
require_once __DIR__ . '/../bootstrap.php';

$rawHeader    = $_SERVER['HTTP_AUTHORIZATION']          ?? null;
$redirectHdr  = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
$allHeaders   = function_exists('getallheaders') ? getallheaders() : [];
$token        = getBearerToken();

$tokenPreview = null;
if ($token) {
    $tokenPreview = substr($token, 0, 8) . '…' . substr($token, -4);
}

jsonOk([
    'http_authorization_present'          => !empty($rawHeader),
    'redirect_http_authorization_present' => !empty($redirectHdr),
    'getallheaders_authorization'         => $allHeaders['Authorization'] ?? $allHeaders['authorization'] ?? null,
    'bearer_token_resolved'               => $token !== null,
    'bearer_token_preview'                => $tokenPreview,
    'php_sapi'                            => PHP_SAPI,
    'server_software'                     => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
]);
