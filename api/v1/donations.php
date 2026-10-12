<?php
declare(strict_types=1);
/**
 * api/v1/donations.php
 * GET  - Browse approved public donations
 * POST - Submit a new donation offer (authenticated user/donor)
 */
require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $sql = "SELECT d.id, d.title, d.description, d.donor_id, d.category_id, 
                       c.name AS category_name, d.quantity_total, d.quantity_reserved, 
                       d.quantity_claimed, d.unit, d.condition_type, d.pickup_city, 
                       d.pickup_barangay, d.pickup_method, d.status, d.created_at,
                       CONCAT(u.first_name, ' ', u.last_name) AS donor_name
                FROM donations d
                LEFT JOIN categories c ON d.category_id = c.id
                LEFT JOIN users u ON d.donor_id = u.id
                WHERE " . publicDonationWhereClause('d') . "
                ORDER BY d.created_at DESC";
        $stmt = $pdo->query($sql);
        $donations = $stmt->fetchAll();
        jsonOk(['data' => $donations]);
    } catch (Exception $e) {
        jsonError('Failed to fetch donations: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $user = requireAuth($pdo);
    $data = getBody();

    $title = trim($data['title'] ?? '');
    $categoryId = (int)($data['category_id'] ?? 0);
    $quantityTotal = (int)($data['quantity_total'] ?? 0);
    $conditionType = trim($data['condition_type'] ?? '');
    $pickupCity = trim($data['pickup_city'] ?? '');
    $pickupMethod = trim($data['pickup_method'] ?? 'pickup');
    $unit = trim($data['unit'] ?? 'item');
    $description = trim($data['description'] ?? '');
    $pickupBarangay = trim($data['pickup_barangay'] ?? '');

    $errors = [];
    if (empty($title)) $errors['title'] = 'Title is required.';
    if ($categoryId <= 0) $errors['category_id'] = 'Valid category is required.';
    if ($quantityTotal <= 0) $errors['quantity_total'] = 'Quantity must be at least 1.';
    if (empty($conditionType)) $errors['condition_type'] = 'Condition type is required.';
    if (empty($pickupCity)) $errors['pickup_city'] = 'City is required.';

    if (!empty($errors)) {
        jsonError('Validation failed.', 422, $errors);
    }

    try {
        $stmt = $pdo->prepare('
            INSERT INTO donations (
                donor_id, category_id, title, description, quantity_total, quantity_reserved,
                quantity_claimed, unit, condition_type, pickup_method, pickup_city, pickup_barangay,
                status, submitted_at, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, 0,
                0, ?, ?, ?, ?, ?,
                "pending_verification", NOW(), NOW(), NOW()
            )
        ');
        $stmt->execute([
            $user['id'],
            $categoryId,
            $title,
            $description,
            $quantityTotal,
            $unit,
            $conditionType,
            $pickupMethod,
            $pickupCity,
            $pickupBarangay
        ]);

        $newId = (int)$pdo->lastInsertId();

        // Add a notification for user
        $notifStmt = $pdo->prepare('
            INSERT INTO notifications (user_id, type, title, message, related_type, related_id, is_read, created_at)
            VALUES (?, "donation_submitted", "Donation Submitted", ?, "donation", ?, 0, NOW())
        ');
        $notifStmt->execute([
            $user['id'],
            "Your donation offer '{$title}' has been submitted and is pending verification.",
            $newId
        ]);

        // Audit log
        $auditStmt = $pdo->prepare('
            INSERT INTO audit_logs (actor_id, actor_role, action, target_type, target_id, new_value, ip_address, created_at)
            VALUES (?, ?, "create_donation", "donation", ?, ?, ?, NOW())
        ');
        $auditStmt->execute([
            $user['id'],
            $user['role'],
            $newId,
            json_encode(['title' => $title, 'qty' => $quantityTotal]),
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);

        jsonOk([
            'message' => 'Donation submitted successfully and queued for review.',
            'id' => $newId
        ], 201);
    } catch (Exception $e) {
        jsonError('Failed to submit donation: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
