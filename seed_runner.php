<?php
/**
 * iCareDonate — Cloud Database Seed Runner
 * Executes test data seeding directly from Render to Aiven.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/database.php';

header('Content-Type: application/json; charset=utf-8');

$secret = $_GET['secret'] ?? '';
if ($secret !== 'icd_defense_2026') {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid or missing secret key.']);
    exit;
}

$pdo = getDB();
if ($pdo === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$results = [];

try {
    // Insert/Update users using parameterized queries so bcrypt $ characters are never interpolated!
    $accounts = [
        [
            'name'  => 'System Administrator',
            'email' => 'admin@icaredonate.test',
            'pass'  => 'Admin@1234',
            'role'  => 'admin'
        ],
        [
            'name'  => 'David Tan',
            'email' => 'donor@icaredonate.test',
            'pass'  => 'Donor@1234',
            'role'  => 'donor'
        ],
        [
            'name'  => 'Maria Santos',
            'email' => 'requester@icaredonate.test',
            'pass'  => 'Requester@1234',
            'role'  => 'requester'
        ],
        [
            'name'  => 'Alex Rivera',
            'email' => 'user@icaredonate.test',
            'pass'  => 'User@1234',
            'role'  => 'donor'
        ]
    ];

    $userStmt = $pdo->prepare('
        INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `created_at`)
        VALUES (:name, :email, :hash, :role, NOW())
        ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `role` = VALUES(`role`)
    ');

    foreach ($accounts as $acc) {
        $hash = password_hash($acc['pass'], PASSWORD_DEFAULT);
        $userStmt->execute([
            ':name'  => $acc['name'],
            ':email' => strtolower($acc['email']),
            ':hash'  => $hash,
            ':role'  => $acc['role']
        ]);
    }
    $results['users'] = 'Admin, Donor, and Requester accounts safely seeded with correct bcrypt hashes.';

    // Execute seed_dashboard_test_data.sql
    $seedFile = __DIR__ . '/database/seed_dashboard_test_data.sql';
    if (file_exists($seedFile)) {
        $sql = file_get_contents($seedFile);
        $cleaned = preg_replace('/--.*$/m', '', $sql);
        $statements = array_filter(array_map('trim', explode(';', $cleaned)));
        $executed = 0;
        foreach ($statements as $stmt) {
            if ($stmt !== '') {
                $pdo->exec($stmt);
                $executed++;
            }
        }
        $results['seed_statements_executed'] = $executed;
    }

    $check = $pdo->query("SELECT id, name, email, role FROM users WHERE email IN ('admin@icaredonate.test', 'donor@icaredonate.test', 'requester@icaredonate.test')");
    $results['active_accounts'] = $check->fetchAll();

    echo json_encode([
        'success' => true,
        'message' => 'Database successfully seeded and accounts verified!',
        'details' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
