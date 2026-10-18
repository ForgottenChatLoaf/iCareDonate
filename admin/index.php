<?php
/**
 * iCareDonate — Admin Portal Protected Gate
 * admin/index.php
 *
 * Enforces PHP session authentication and Admin role check.
 * Non-admins are redirected to 403.html.
 * Unauthenticated visitors are redirected to login.php.
 */

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pages = [
    'dashboard'       => 'index.html',
    'index'           => 'index.html',
    'donations'       => 'donations.html',
    'donation-review' => 'donation-review.html',
    'requests'        => 'requests.html',
    'request-review'  => 'request-review.html',
    'transactions'    => 'transactions.html',
    'claims'          => 'claims.html',
    'interviews'      => 'interviews.html',
    'matches'         => 'matches.html',
    'users'           => 'users.html',
    'user-detail'     => 'user-detail.html',
    'moderation'      => 'moderation.html',
    'support'         => 'support.html',
    'inquiries'       => 'inquiries.html',
    'categories'      => 'categories.html',
    'faqs'            => 'faqs.html',
    'reports'         => 'reports.html',
    'statistics'      => 'statistics.html',
    'admins'          => 'admins.html',
    'profile'         => 'profile.html',
    'logs'            => 'logs.html',
];

$page = $_GET['page'] ?? 'index';
$page = preg_replace('/\.html$/i', '', $page);

if (!isset($pages[$page])) {
    $page = 'index';
}

$filePath = __DIR__ . '/../frontend/admin/' . $pages[$page];
if (!file_exists($filePath)) {
    http_response_code(404);
    echo "Admin page not found.";
    exit;
}

$html = file_get_contents($filePath);

// Inject base tag so relative assets resolve cleanly
$baseTag = '<base href="' . _icd_root() . 'frontend/admin/">';
$html = preg_replace('/<head[^>]*>/i', "$0\n  $baseTag", $html, 1);

$sessionScript = '<script>window.ICD_SESSION = ' . json_encode([
    'loggedIn' => isLoggedIn(),
    'role'     => currentRole(),
    'name'     => currentName(),
]) . ';</script>';
$html = preg_replace('/<head[^>]*>/i', "$0\n  $sessionScript", $html, 1);

echo $html;
