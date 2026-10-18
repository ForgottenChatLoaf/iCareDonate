<?php
/**
 * iCareDonate — Session Authentication Helpers
 * includes/auth.php
 *
 * Provides requireLogin(), requireAdmin(), and isLoggedIn().
 * Include this file at the top of any PHP page that needs protection.
 *
 * Usage:
 *   require_once __DIR__ . '/../includes/auth.php';
 *   requireLogin();   // Redirects to login.php if not authenticated
 *   requireAdmin();   // Redirects to 403.html if not admin role
 */

// Start session if one is not already active
if (session_status() === PHP_SESSION_NONE) {
    // Harden session cookie for local development
    session_set_cookie_params([
        'lifetime' => 0,          // Session cookie — expires when browser closes
        'path'     => '/',
        'httponly' => true,       // Not accessible to JavaScript
        'samesite' => 'Strict',   // No cross-site submission
    ]);
    session_start();
}

/**
 * Redirect to login.php if the user is not authenticated.
 * Call this at the top of any protected page.
 */
function requireLogin(): void {
    if (empty($_SESSION['icd_user_id']) || empty($_SESSION['icd_role'])) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header('Location: ' . _icd_root() . 'login.php' . ($redirect ? '?redirect=' . $redirect : ''));
        exit;
    }
}

/**
 * Redirect to 403.html if the user is not authenticated as an admin.
 * Call this at the top of any admin-only page.
 */
function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['icd_role'] ?? '') !== 'admin') {
        header('Location: ' . _icd_root() . 'frontend/403.html');
        exit;
    }
}

/**
 * Returns true if a valid session exists.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['icd_user_id']) && !empty($_SESSION['icd_role']);
}

/**
 * Returns the authenticated user's role, or null if not logged in.
 */
function currentRole(): ?string {
    return $_SESSION['icd_role'] ?? null;
}

/**
 * Returns the authenticated user's display name, or null if not logged in.
 */
function currentName(): ?string {
    return $_SESSION['icd_name'] ?? null;
}

/**
 * Helper: resolves the project root URL path so redirects work from any depth.
 * Returns something like '/iCareDonate/'.
 */
function _icd_root(): string {
    $dir = __DIR__; // This file is at iCareDonate/includes/
    $root = dirname($dir); // iCareDonate/
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');

    $rootNorm = str_replace('\\', '/', $root);
    $docNorm  = str_replace('\\', '/', $docRoot);

    if ($docNorm !== '' && stripos($rootNorm, $docNorm) === 0) {
        $urlPath = substr($rootNorm, strlen($docNorm));
    } else {
        $urlPath = '/iCareDonate';
    }

    return '/' . trim($urlPath, '/') . '/';
}
