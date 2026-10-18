<?php
/**
 * iCareDonate — Logout Handler
 * logout.php
 *
 * Destroys the current PHP session and redirects to the login page.
 * Accepts both GET and POST requests (sidebar logout links use GET).
 */

require_once __DIR__ . '/includes/auth.php';

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session data on the server
session_destroy();

// Redirect to login page
header('Location: ' . _icd_root() . 'login.php');
exit;
