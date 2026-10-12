<?php
declare(strict_types=1);
/**
 * api/v1/admin/donations.php
 * GET  - List all donations for moderation/review
 * POST - Approve, reject, or update donation status
 */
require_once __DIR__ . '/../bootstrap.php';

$admin = requireAdmin($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $status = $_GET['status'] ?? null;
        $sql = '
            SELECT d.id, d.title, d.category_id, c.name AS category_name,
                   d.quantity_total, d.quantity_reserved, d.quantity_claimed,
                   d.unit, d.condition_type, d.pickup_city, d.pickup_method,
                   d.status, d.submitted_at, d.created_at, d.admin_notes,
                   CONCAT(u.first_name, " ", u.last_name) AS donor,
                   u.email AS donor_email
            FROM donations d
            LEFT JOIN categories c ON d.category_id = c.id
            LEFT JOIN users u ON d.donor_id = u.id
        ';
        $params = [];
        if ($status) {
            $sql .= ' WHERE d.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY d.created_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $donations = $stmt->fetchAll();

        jsonOk(['data' => $donations]);
    } catch (Exception $e) {
        jsonError('Failed to fetch admin donations: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $data = getBody();
    $id = (int)($data['id'] ?? 0);
    $action = $data['action'] ?? ''; // 'approve', 'reject', 'needs_information'
    $reason = trim($data['reason'] ?? ($data['notes'] ?? ''));

    if ($id <= 0 || empty($action)) {
        jsonError('Donation ID and action are required.');
    }

    try {
        $newStatus = match ($action) {
            'approve' => 'approved',
            'reject'  => 'rejected',
            'needs_information' => 'needs_information',
            default   => null
        };

        if (!$newStatus) {
            jsonError('Invalid action.');
        }

        $stmt = $pdo->prepare('
            UPDATE donations SET
                status = ?,
                admin_notes = ?,
                approved_at = CASE WHEN ? = "approved" THEN NOW() ELSE approved_at END,
                approved_by = CASE WHEN ? = "approved" THEN ? ELSE approved_by END,
                rejection_reason = CASE WHEN ? = "rejected" THEN ? ELSE rejection_reason END,
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $newStatus,
            $reason,
            $newStatus,
            $newStatus,
            $admin['id'],
            $newStatus,
            $reason,
            $id
        ]);

        // Notify donor
        $dStmt = $pdo->prepare('SELECT donor_id, title FROM donations WHERE id = ?');
        $dStmt->execute([$id]);
        $don = $dStmt->fetch();

        if ($don) {
            $notif = $pdo->prepare('
                INSERT INTO notifications (user_id, type, title, message, related_type, related_id, is_read, created_at)
                VALUES (?, "donation_update", ?, ?, "donation", ?, 0, NOW())
            ');
            $notif->execute([
                $don['donor_id'],
                "Donation Status: " . ucfirst($newStatus),
                "Your donation offer '{$don['title']}' has been updated to: " . str_replace('_', ' ', $newStatus),
                $id
            ]);
        }

        // Audit log
        $audit = $pdo->prepare('
            INSERT INTO audit_logs (actor_id, actor_role, action, target_type, target_id, new_value, reason, ip_address, created_at)
            VALUES (?, ?, ?, "donation", ?, ?, ?, ?, NOW())
        ');
        $audit->execute([
            $admin['id'],
            $admin['role'],
            "donation_{$action}",
            $id,
            json_encode(['status' => $newStatus]),
            $reason,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);

        jsonOk(['message' => "Donation successfully updated to {$newStatus}."]);
    } catch (Exception $e) {
        jsonError('Failed to update donation: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
