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

    // Helper to execute a SQL seed file safely
    $execSeedFile = function(string $path) use ($pdo, &$results): int {
        if (!file_exists($path)) return 0;
        $sql = file_get_contents($path);
        $cleaned = preg_replace('/--.*$/m', '', $sql);
        $statements = array_filter(array_map('trim', explode(';', $cleaned)));
        $executed = 0;
        foreach ($statements as $stmt) {
            if ($stmt !== '') {
                try {
                    $pdo->exec($stmt);
                    $executed++;
                } catch (PDOException $e) {
                    $results['warnings'][] = basename($path) . ': ' . $e->getMessage() . ' | SQL: ' . substr($stmt, 0, 120);
                }
            }
        }
        return $executed;
    };

    // Execute seed_dashboard_test_data.sql (original test data)
    $results['seed_dashboard_executed'] = $execSeedFile(__DIR__ . '/database/seed_dashboard_test_data.sql');

    // Execute seed_demo_data.sql (comprehensive admin demo data)
    $results['seed_demo_executed'] = $execSeedFile(__DIR__ . '/database/seed_demo_data.sql');

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
