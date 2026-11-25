<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database.php';
$_SESSION['icd_user_id'] = 1;
$_SESSION['icd_role'] = 'admin';
$_SESSION['icd_name'] = 'System Administrator';

ob_start();
require __DIR__ . '/../actions/admin_dashboard.php';
$output = ob_get_clean();

$json = json_decode($output, true);
echo "SUCCESS: " . ($json['success'] ? 'YES' : 'NO') . "\n";
echo "COUNTS: " . json_encode($json['counts'], JSON_PRETTY_PRINT) . "\n";
echo "USER ROLES: " . json_encode($json['user_roles'], JSON_PRETTY_PRINT) . "\n";
echo "CATEGORY TABLE ROWS: " . count($json['category_table']) . "\n";
echo "MONTHLY TRENDS: " . json_encode($json['monthly_trends']) . "\n";
