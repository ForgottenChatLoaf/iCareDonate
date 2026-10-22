<?php
require_once __DIR__ . '/includes/database.php';
header('Content-Type: application/json');
$pdo = getDB();
$out = [];
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    $cols = $pdo->query("DESCRIBE `$t`")->fetchAll();
    $out[$t] = array_column($cols, 'Field');
}
echo json_encode($out, JSON_PRETTY_PRINT);
