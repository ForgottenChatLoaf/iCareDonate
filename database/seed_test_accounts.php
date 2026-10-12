<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$accounts = [
    [
        'email'      => 'admin@icaredonate.test',
        'password'   => 'Admin@1234',
        'first_name' => 'Admin',
        'last_name'  => 'Tester',
        'role'       => 'admin',
        'status'     => 'active',
    ],
    [
        'email'      => 'user@icaredonate.test',
        'password'   => 'User@1234',
        'first_name' => 'Regular',
        'last_name'  => 'User',
        'role'       => 'user',
        'status'     => 'active',
    ],
    [
        'email'      => 'donor@icaredonate.test',
        'password'   => 'Donor@1234',
        'first_name' => 'Generous',
        'last_name'  => 'Donor',
        'role'       => 'user',
        'status'     => 'active',
    ],
];

foreach ($accounts as $acc) {
    $hash = password_hash($acc['password'], PASSWORD_DEFAULT);
    
    // Check if exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$acc['email']]);
    $existing = $stmt->fetch();

    if ($existing) {
        $update = $pdo->prepare('UPDATE users SET password_hash = ?, first_name = ?, last_name = ?, role = ?, status = ?, email_verified_at = NOW() WHERE id = ?');
        $update->execute([$hash, $acc['first_name'], $acc['last_name'], $acc['role'], $acc['status'], $existing['id']]);
        echo "Updated {$acc['email']} (ID: {$existing['id']})\n";
    } else {
        $insert = $pdo->prepare('INSERT INTO users (email, password_hash, first_name, last_name, role, status, email_verified_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $insert->execute([$acc['email'], $hash, $acc['first_name'], $acc['last_name'], $acc['role'], $acc['status']]);
        echo "Inserted {$acc['email']}\n";
    }
}

echo "Done seeding test accounts.\n";
