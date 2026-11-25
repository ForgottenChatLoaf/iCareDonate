<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database.php';
$_SESSION['icd_user_id'] = 3; // David Tan (donor)
$_SESSION['icd_role'] = 'donor';
$_SESSION['icd_name'] = 'David Tan';

ob_start();
require __DIR__ . '/../actions/user_dashboard.php';
$output = ob_get_clean();

$json = json_decode($output, true);
echo "SUCCESS: " . ($json['success'] ? 'YES' : 'NO') . "\n";
echo "STATS: " . json_encode($json['stats'], JSON_PRETTY_PRINT) . "\n";
echo "RECENT ACTIVITIES COUNT: " . count($json['recent_activities']) . "\n";
echo "ACTIVE LISTINGS COUNT: " . count($json['active_listings']) . "\n";
