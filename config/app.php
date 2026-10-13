<?php
declare(strict_types=1);

/**
 * config/app.php
 *
 * Central Application Base Path and URL Configuration for iCareDonate.
 *
 * Supports both environments:
 * 1. Local XAMPP subfolder: APP_BASE = '/CommunityDono'
 * 2. Production domain root: APP_BASE = ''
 *
 * Can be overridden via environment variable `APP_BASE` or changed below.
 */

if (!defined('APP_BASE')) {
    $envBase = getenv('APP_BASE');
    if ($envBase !== false) {
        define('APP_BASE', rtrim((string)$envBase, '/'));
    } else {
        // Default: '/CommunityDono' for local XAMPP. Change to '' for root domain deployment.
        define('APP_BASE', '/CommunityDono');
    }
}

// Global variable for view templates
$base = APP_BASE;

/**
 * Generate an internal application URL with the configured APP_BASE.
 * Prevents double slashes and handles empty base path seamlessly.
 *
 * Examples:
 *   appUrl('/pages/login.php')          -> '/CommunityDono/pages/login.php' (local)
 *                                       -> '/pages/login.php' (production root)
 *   appUrl('/')                         -> '/CommunityDono/' (local)
 *                                       -> '/' (production root)
 *   appUrl('assets/css/php/tokens.css') -> '/CommunityDono/assets/css/php/tokens.css' (local)
 */
if (!function_exists('appUrl')) {
    function appUrl(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        if ($path === '/' && APP_BASE === '') {
            return '/';
        }
        return APP_BASE . $path;
    }
}

/**
 * Perform a safe application HTTP redirect.
 */
if (!function_exists('appRedirect')) {
    function appRedirect(string $path): void
    {
        header('Location: ' . appUrl($path));
        exit;
    }
}

/**
 * Check if a redirect target is a safe internal application path.
 * Guards against open redirect attacks (e.g. '//evil.com' or external domains).
 */
if (!function_exists('isSafeInternalRedirect')) {
    function isSafeInternalRedirect(string $url): bool
    {
        if (empty($url) || str_contains($url, "\n") || str_contains($url, "\r")) {
            return false;
        }
        // Disallow protocol-relative URLs (e.g., //evil.com)
        if (str_starts_with($url, '//')) {
            return false;
        }
        if (APP_BASE !== '') {
            return str_starts_with($url, APP_BASE . '/');
        }
        return str_starts_with($url, '/') && !str_starts_with($url, '//');
    }
}
