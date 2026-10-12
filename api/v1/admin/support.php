<?php
declare(strict_types=1);
/**
 * api/v1/admin/support.php
 * GET  - List support tickets
 * POST - Reply to support ticket and update status
 */
require_once __DIR__ . '/../bootstrap.php';

$admin = requireAdmin($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $sql = '
            SELECT st.id, st.subject, st.message, st.status, st.admin_reply,
                   st.replied_at, st.created_at,
                   CONCAT(u.first_name, " ", u.last_name) AS user,
                   u.email AS user_email
            FROM support_tickets st
            LEFT JOIN users u ON st.user_id = u.id
            ORDER BY st.created_at DESC
        ';
        $stmt = $pdo->query($sql);
        $tickets = $stmt->fetchAll();

        jsonOk(['data' => $tickets]);
    } catch (Exception $e) {
        jsonError('Failed to fetch support tickets: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $data = getBody();
    $id = (int)($data['id'] ?? 0);
    $reply = trim($data['reply'] ?? '');
    $status = trim($data['status'] ?? 'resolved');

    if ($id <= 0 || empty($reply)) {
        jsonError('Ticket ID and reply message are required.');
    }

    try {
        $stmt = $pdo->prepare('
            UPDATE support_tickets SET
                admin_reply = ?,
                status = ?,
                assigned_to = ?,
                replied_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $reply,
            $status,
            $admin['id'],
            $id
        ]);

        // Notify user
        $tStmt = $pdo->prepare('SELECT user_id, subject FROM support_tickets WHERE id = ?');
        $tStmt->execute([$id]);
        $ticket = $tStmt->fetch();

        if ($ticket && !empty($ticket['user_id'])) {
            $notif = $pdo->prepare('
                INSERT INTO notifications (user_id, type, title, message, related_type, related_id, is_read, created_at)
                VALUES (?, "support_reply", "Support Ticket Replied", ?, "support_ticket", ?, 0, NOW())
            ');
            $notif->execute([
                $ticket['user_id'],
                "Your support ticket '{$ticket['subject']}' has been answered.",
                $id
            ]);
        }

        jsonOk(['message' => 'Reply sent successfully.']);
    } catch (Exception $e) {
        jsonError('Failed to reply to ticket: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
