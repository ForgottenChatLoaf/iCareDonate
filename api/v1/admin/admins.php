<?php
declare(strict_types=1);
/**
 * api/v1/admin/admins.php
 * GET  - List all administrator accounts
 * POST - Invite or register an admin
 */
require_once __DIR__ . '/../bootstrap.php';

$admin = requireAdmin($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query('
            SELECT id, CONCAT(first_name, " ", last_name) AS name, email,
                   role, status, created_at, last_login_at
            FROM users
            WHERE role IN ("admin", "superadmin")
            ORDER BY role ASC, created_at ASC
        ');
        $admins = $stmt->fetchAll();

        jsonOk(['data' => $admins]);
    } catch (Exception $e) {
        jsonError('Failed to fetch admin accounts: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    if ($admin['role'] !== 'superadmin') {
        jsonError('Only superadmins can add new administrators.', 403);
    }

    $data = getBody();
    $email = trim($data['email'] ?? '');
    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $password = $data['password'] ?? 'Admin@123456';
    $role = $data['role'] ?? 'admin';

    if (empty($email) || empty($firstName)) {
        jsonError('Email and first name are required.');
    }

    try {
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            jsonError('A user with this email already exists.', 422);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('
            INSERT INTO users (email, password_hash, first_name, last_name, role, status, email_verified_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, "active", NOW(), NOW(), NOW())
        ');
        $stmt->execute([$email, $hash, $firstName, $lastName, $role]);

        $newId = (int)$pdo->lastInsertId();

        jsonOk(['message' => 'Admin account created successfully.', 'id' => $newId], 201);
    } catch (Exception $e) {
        jsonError('Failed to create admin: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
