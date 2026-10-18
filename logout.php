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
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Logging Out | iCareDonate</title>
  <script>
    try {
      localStorage.removeItem('icd_token');
      localStorage.removeItem('icd_user');
      sessionStorage.clear();
    } catch(e) {}
    window.location.replace('<?= _icd_root() ?>login.php');
  </script>
  <noscript>
    <meta http-equiv="refresh" content="0; url=<?= _icd_root() ?>login.php">
  </noscript>
</head>
<body>
</body>
</html>
