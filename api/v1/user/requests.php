<?php
declare(strict_types=1);
/**
 * api/v1/user/requests.php
 * GET  - Returns all item requests submitted by the authenticated user
 * POST - Submit a new request for a donation
 */
require_once __DIR__ . '/../bootstrap.php';

$user = requireAuth($pdo);
$userId = (int)$user['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare('
            SELECT r.id, r.donation_id, r.quantity_requested, r.quantity_matched,
                   r.purpose, r.urgency_level, r.status, r.submitted_at, r.created_at,
                   d.title AS donation_title, c.name AS category_name,
                   d.pickup_city
            FROM requests r
            LEFT JOIN donations d ON r.donation_id = d.id
            LEFT JOIN categories c ON d.category_id = c.id
            WHERE r.requester_id = ?
            ORDER BY r.created_at DESC
        ');
        $stmt->execute([$userId]);
        $requests = $stmt->fetchAll();

        jsonOk(['data' => $requests]);
    } catch (Exception $e) {
        jsonError('Failed to fetch user requests: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $data = getBody();
    $donationId = (int)($data['donation_id'] ?? 0);
    $quantityRequested = (int)($data['quantity_requested'] ?? 1);
    $purpose = trim($data['purpose'] ?? '');
    $urgencyLevel = trim($data['urgency_level'] ?? 'medium');
    $deliveryAddress = trim($data['delivery_address'] ?? '');
    $contactNumber = trim($data['contact_number'] ?? '');

    if ($donationId <= 0) {
        jsonError('Valid donation ID is required.');
    }
    if ($quantityRequested <= 0) {
        jsonError('Quantity requested must be at least 1.');
    }

    try {
        // Verify donation exists and is approved
        $checkStmt = $pdo->prepare('SELECT id, title, donor_id, quantity_total, quantity_reserved, quantity_claimed FROM donations WHERE id = ? AND status = "approved"');
        $checkStmt->execute([$donationId]);
        $donation = $checkStmt->fetch();

        if (!$donation) {
            jsonError('Donation item is not available or not found.', 404);
        }

        if ((int)$donation['donor_id'] === $userId) {
            jsonError('You cannot request your own donation offer.', 400);
        }

        // Check if user already has an active request for this donation
        $dupStmt = $pdo->prepare('SELECT id FROM requests WHERE requester_id = ? AND donation_id = ? AND status IN ("submitted", "under_review", "approved", "matched") LIMIT 1');
        $dupStmt->execute([$userId, $donationId]);
        if ($dupStmt->fetch()) {
            jsonError('You already have an active request for this donation item.', 400);
        }

        $stmt = $pdo->prepare('
            INSERT INTO requests (
                requester_id, donation_id, quantity_requested, quantity_matched,
                purpose, urgency_level, delivery_address, contact_number,
                status, submitted_at, created_at, updated_at
            ) VALUES (
                ?, ?, ?, 0,
                ?, ?, ?, ?,
                "submitted", NOW(), NOW(), NOW()
            )
        ');
        $stmt->execute([
            $userId,
            $donationId,
            $quantityRequested,
            $purpose,
            $urgencyLevel,
            $deliveryAddress,
            $contactNumber
        ]);

        $newId = (int)$pdo->lastInsertId();

        // Notify user
        $notif = $pdo->prepare('
            INSERT INTO notifications (user_id, type, title, message, related_type, related_id, is_read, created_at)
            VALUES (?, "request_submitted", "Request Submitted", ?, "request", ?, 0, NOW())
        ');
        $notif->execute([
            $userId,
            "Your request for {$quantityRequested}x '{$donation['title']}' has been submitted for review.",
            $newId
        ]);

        jsonOk([
            'message' => 'Request submitted successfully.',
            'id' => $newId
        ], 201);
    } catch (Exception $e) {
        jsonError('Failed to create request: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
