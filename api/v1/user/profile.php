<?php
declare(strict_types=1);
/**
 * api/v1/user/profile.php
 * GET      - Get profile of current user
 * PUT/POST - Update profile info
 */
require_once __DIR__ . '/../bootstrap.php';

$user = requireAuth($pdo);
$userId = (int)$user['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare('
            SELECT id, email, first_name, last_name, username, phone,
                   barangay, city, province, role, status, profile_photo,
                   pref_language, pref_country, pref_timezone, created_at
            FROM users
            WHERE id = ?
        ');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
        jsonOk(['data' => $profile, 'user' => $profile]);
    } catch (Exception $e) {
        jsonError('Failed to fetch profile: ' . $e->getMessage(), 500);
    }
}

if ($method === 'PUT' || $method === 'POST') {
    $data = getBody();

    $firstName = trim($data['first_name'] ?? '');
    $lastName  = trim($data['last_name'] ?? '');
    $phone     = trim($data['phone'] ?? '');
    $barangay  = trim($data['barangay'] ?? ($data['address_barangay'] ?? ''));
    $city      = trim($data['city'] ?? ($data['address_city'] ?? ''));
    $province  = trim($data['province'] ?? ($data['address_province'] ?? ''));

    if (empty($firstName)) {
        jsonError('First name is required.');
    }

    try {
        $stmt = $pdo->prepare('
            UPDATE users SET
                first_name = ?,
                last_name = ?,
                phone = ?,
                barangay = ?,
                city = ?,
                province = ?,
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $firstName,
            $lastName,
            $phone,
            $barangay,
            $city,
            $province,
            $userId
        ]);

        // Audit log
        $auditStmt = $pdo->prepare('
            INSERT INTO audit_logs (actor_id, actor_role, action, target_type, target_id, ip_address, created_at)
            VALUES (?, ?, "update_profile", "user", ?, ?, NOW())
        ');
        $auditStmt->execute([
            $userId,
            $user['role'],
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);

        jsonOk(['message' => 'Profile updated successfully.']);
    } catch (Exception $e) {
        jsonError('Failed to update profile: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
