<?php
/**
 * iCareDonate — User Portal Protected Gate
 * user/index.php
 *
 * Enforces PHP session authentication for all user portal pages.
 * Rejects unauthenticated visitors and redirects them to login.php.
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// Whitelist of valid user portal page slugs
$pages = [
    'dashboard'        => 'dashboard.html',
    'browse-donations' => 'browse-donations.html',
    'donations'        => 'donations.html',
    'offer-donation'   => 'offer-donation.html',
    'edit-donation'    => 'edit-donation.html',
    'requests'         => 'requests.html',
    'request-donation' => 'request-donation.html',
    'request-details'  => 'request-details.html',
    'transactions'     => 'transactions.html',
    'history'          => 'history.html',
    'notifications'    => 'notifications.html',
    'support'          => 'support.html',
    'profile'          => 'profile.html',
    'settings'         => 'settings.html',
];

$page = $_GET['page'] ?? 'dashboard';
$page = preg_replace('/\.html$/i', '', $page);

if (!isset($pages[$page])) {
    $page = 'dashboard';
}

$filePath = __DIR__ . '/../frontend/user/' . $pages[$page];
if (!file_exists($filePath)) {
    http_response_code(404);
    echo "Portal page not found.";
    exit;
}

$html = file_get_contents($filePath);

// Inject base tag so all relative assets, CSS, JS, and images resolve to frontend/user/
$baseTag = '<base href="' . _icd_root() . 'frontend/user/">';
$html = preg_replace('/<head[^>]*>/i', "$0\n  $baseTag", $html, 1);

// Inject user session information into window.ICD_SESSION
$sessionScript = '<script>window.ICD_SESSION = ' . json_encode([
    'loggedIn' => isLoggedIn(),
    'role'     => currentRole(),
    'name'     => currentName(),
]) . ';</script>';
$html = preg_replace('/<head[^>]*>/i', "$0\n  $sessionScript", $html, 1);

echo $html;
