<?php
declare(strict_types=1);
/**
 * api/v1/admin/profile.php
 * GET  - Return admin account details
 * POST/PUT - Update admin account details
 */
require_once __DIR__ . '/../bootstrap.php';

$admin = requireAdmin($pdo);
$userId = (int)$admin['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare('
            SELECT id, email, first_name, last_name, role, status, phone, profile_photo, created_at, last_login_at
            FROM users
            WHERE id = ?
        ');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        jsonOk(['data' => $row, 'user' => $row]);
    } catch (Exception $e) {
        jsonError('Failed to fetch admin profile: ' . $e->getMessage(), 500);
    }
}

if ($method === 'PUT' || $method === 'POST') {
    $data = getBody();
    $firstName = trim($data['first_name'] ?? '');
    $lastName  = trim($data['last_name'] ?? '');
    $phone     = trim($data['phone'] ?? '');

    if (empty($firstName)) {
        jsonError('First name is required.');
    }

    try {
        $stmt = $pdo->prepare('
            UPDATE users SET
                first_name = ?,
                last_name = ?,
                phone = ?,
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([$firstName, $lastName, $phone, $userId]);

        jsonOk(['message' => 'Admin profile updated successfully.']);
    } catch (Exception $e) {
        jsonError('Failed to update admin profile: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
