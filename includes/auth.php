<?php
declare(strict_types=1);
//  includes/auth.php

/**
 * Redirect unauthenticated users to login.
 * Also blocks suspended/banned accounts.
 */
function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /CommunityDono/pages/login.php');
        exit;
    }

    $status = $_SESSION['status'] ?? '';
    if ($status !== 'active') {
        session_unset();
        session_destroy();
        $msg = ($status === 'suspended') ? 'suspended' : 'banned';
        header("Location: /CommunityDono/pages/login.php?e={$msg}");
        exit;
    }
}

/**
 * Restrict access to admin and superadmin roles only.
 */
function requireAdmin(): void
{
    requireLogin();

    if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
        http_response_code(403);
        include __DIR__ . '/../pages/403.php';
        exit;
    }
}

/**
 * Check if the current session user is an admin.
 */
function isAdmin(): bool
{
    return in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true);
}

/**
 * Check if the current session user is a superadmin.
 */
function isSuperAdmin(): bool
{
    return ($_SESSION['role'] ?? '') === 'superadmin';
}

/**
 * Restrict access to superadmin role only.
 */
function requireSuperAdmin(): void
{
    requireLogin();

    if (!isSuperAdmin()) {
        http_response_code(403);
        include __DIR__ . '/../pages/403.php';
        exit;
    }
}

/**
 * Check if session user is logged in (without redirect).
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']) && ($_SESSION['status'] ?? '') === 'active';
}