<?php
/**
 * iCareDonate — Cloud Database Seed Runner
 * Executes test data seeding directly from Render to Aiven.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/database.php';

header('Content-Type: application/json; charset=utf-8');

// Optional basic protection
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
    // 1. Ensure core test accounts exist with known passwords
    // Passwords:
    // admin@icaredonate.test -> Admin@1234
    // donor@icaredonate.test -> DonorPass123!
    // requester@icaredonate.test -> RequestPass123!
    $userSql = "
        INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `created_at`)
        VALUES
        ('System Administrator', 'admin@icaredonate.test', '$2y$10$EldpE/z5JCoAimVCjjQyFOB59k.4KU6r5CEqP.jVdcxv6XOsNGyE6', 'admin', NOW()),
        ('David Tan', 'donor@icaredonate.test', '$2y$10$Tp8I1RL1w90ghbqaIRn0RuxXlJoJ07KtBqzrn6b8t.yo7PkaSfznq', 'donor', NOW()),
        ('Maria Santos', 'requester@icaredonate.test', '$2y$10$TaO9lGf61pBO50NmF2YsTufI6gjoR8qGkWTGja/LH9FDpuj12wMku', 'requester', NOW())
        ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `role` = VALUES(`role`);
    ";
    $pdo->exec($userSql);
    $results['users'] = 'Admin, Donor, and Requester accounts verified/created.';

    // 2. Read and execute seed_dashboard_test_data.sql
    $seedFile = __DIR__ . '/database/seed_dashboard_test_data.sql';
    if (file_exists($seedFile)) {
        $sql = file_get_contents($seedFile);
        // Remove comment lines
        $cleaned = preg_replace('/--.*$/m', '', $sql);
        // Split on semicolons
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

    // 3. Verify accounts
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
