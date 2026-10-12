<?php
declare(strict_types=1);
/**
 * api/v1/admin/requests.php
 * GET  - List all requests for moderation/review
 * POST - Approve, reject, or update request status
 */
require_once __DIR__ . '/../bootstrap.php';

$admin = requireAdmin($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $sql = '
            SELECT r.id, r.donation_id, r.requester_id, r.quantity_requested,
                   r.quantity_matched, r.urgency_level, r.status, r.submitted_at,
                   r.purpose, r.delivery_address, r.contact_number,
                   d.title AS donation_title,
                   CONCAT(u.first_name, " ", u.last_name) AS requester,
                   u.email AS requester_email
            FROM requests r
            LEFT JOIN donations d ON r.donation_id = d.id
            LEFT JOIN users u ON r.requester_id = u.id
            ORDER BY r.created_at DESC
        ';
        $stmt = $pdo->query($sql);
        $requests = $stmt->fetchAll();

        jsonOk(['data' => $requests]);
    } catch (Exception $e) {
        jsonError('Failed to fetch admin requests: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $data = getBody();
    $id = (int)($data['id'] ?? 0);
    $action = $data['action'] ?? ''; // 'approve', 'reject', 'needs_information'
    $reason = trim($data['reason'] ?? ($data['notes'] ?? ''));

    if ($id <= 0 || empty($action)) {
        jsonError('Request ID and action are required.');
    }

    $newStatus = match ($action) {
        'approve' => 'approved',
        'reject'  => 'rejected',
        'needs_information' => 'needs_information',
        default   => null
    };

    if (!$newStatus) {
        jsonError('Invalid action.');
    }

    try {
        $stmt = $pdo->prepare('
            UPDATE requests SET
                status = ?,
                admin_notes = ?,
                rejection_reason = CASE WHEN ? = "rejected" THEN ? ELSE rejection_reason END,
                reviewed_at = NOW(),
                reviewed_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $newStatus,
            $reason,
            $newStatus,
            $reason,
            $admin['id'],
            $id
        ]);

        // Notify requester
        $rStmt = $pdo->prepare('SELECT requester_id, donation_id FROM requests WHERE id = ?');
        $rStmt->execute([$id]);
        $req = $rStmt->fetch();

        if ($req) {
            $notif = $pdo->prepare('
                INSERT INTO notifications (user_id, type, title, message, related_type, related_id, is_read, created_at)
                VALUES (?, "request_update", ?, ?, "request", ?, 0, NOW())
            ');
            $notif->execute([
                $req['requester_id'],
                "Request Status: " . ucfirst($newStatus),
                "Your request has been updated to: " . str_replace('_', ' ', $newStatus),
                $id
            ]);
        }

        // Audit log
        $audit = $pdo->prepare('
            INSERT INTO audit_logs (actor_id, actor_role, action, target_type, target_id, new_value, reason, ip_address, created_at)
            VALUES (?, ?, ?, "request", ?, ?, ?, ?, NOW())
        ');
        $audit->execute([
            $admin['id'],
            $admin['role'],
            "request_{$action}",
            $id,
            json_encode(['status' => $newStatus]),
            $reason,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);

        jsonOk(['message' => "Request successfully updated to {$newStatus}."]);
    } catch (Exception $e) {
        jsonError('Failed to update request: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
