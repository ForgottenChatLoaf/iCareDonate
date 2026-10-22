<?php
require_once __DIR__ . '/includes/database.php';
header('Content-Type: application/json');
$pdo = getDB();
if (!$pdo) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
$out = [
    'hostname' => $pdo->query("SELECT @@hostname")->fetchColumn(),
    'database' => $pdo->query("SELECT DATABASE()")->fetchColumn(),
    'env_host' => getenv('DB_HOST'),
    'env_db'   => getenv('DB_DATABASE'),
    'tables'   => []
];
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    $cols = $pdo->query("DESCRIBE `$t`")->fetchAll();
    $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    $out['tables'][$t] = [
        'count' => (int)$cnt,
        'fields' => array_column($cols, 'Field')
    ];
}
echo json_encode($out, JSON_PRETTY_PRINT);
