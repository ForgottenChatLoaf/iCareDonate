<?php
/**
 * iCareDonate — Support Tickets Endpoint (Member & Admin)
 * actions/support_tickets.php
 *
 * GET:
 *  - Member: Retrieves own support tickets ordered by created_at DESC.
 *  - Admin:  Retrieves all support tickets (optional ?status= and ?priority= filter).
 *
 * POST:
 *  - Member (create): Submits new support ticket with subject, category, message, priority.
 *  - Member (reply): Appends follow-up to own ticket message.
 *  - Admin (reply): Sets admin_reply, updates status, dispatches user notification, logs audit.
 *  - Admin (update_status): Changes ticket status.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/admin_auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Database connection unavailable.']);
    exit;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Determine if user is admin
$isAdmin = false;
$adminUser = null;
if (isset($_SESSION['icd_user_id']) && ($_SESSION['icd_role'] ?? '') === 'admin') {
    $isAdmin = true;
    $adminUser = [
        'id' => (int)$_SESSION['icd_user_id'],
        'name' => $_SESSION['icd_name'] ?? 'Administrator',
        'role' => 'admin'
    ];
}

$memberUserId = getAuthenticatedUserId();

if ($memberUserId === null && !$isAdmin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please sign in to access support.']);
    exit;
}

// ─────────────────────────────────────────────────────────────
// GET: Fetch Tickets
// ─────────────────────────────────────────────────────────────
if ($method === 'GET') {
    try {
        if ($isAdmin && isset($_GET['admin_view']) && $_GET['admin_view'] === '1') {
            $statusFilter = trim((string)($_GET['status'] ?? ''));
            $priorityFilter = trim((string)($_GET['priority'] ?? ''));

            $sql = 'SELECT t.*, u.name AS user_name, u.email AS user_email
                    FROM support_tickets t
                    LEFT JOIN users u ON u.id = t.user_id
                    WHERE 1=1';
            $params = [];

            if ($statusFilter !== '' && in_array($statusFilter, ['open', 'in_progress', 'resolved', 'closed'], true)) {
                $sql .= ' AND t.status = :status';
                $params[':status'] = $statusFilter;
            }

            if ($priorityFilter !== '' && in_array($priorityFilter, ['low', 'medium', 'high', 'urgent'], true)) {
                $sql .= ' AND t.priority = :priority';
                $params[':priority'] = $priorityFilter;
            }

            $sql .= ' ORDER BY t.created_at DESC LIMIT 100';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tickets = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'tickets' => $tickets,
                'count'   => count($tickets)
            ]);
            exit;
        }

        // Regular Member GET: Fetch own tickets
        $stmt = $pdo->prepare('SELECT id, user_id, subject, category, message, status, priority, admin_reply, created_at, updated_at
                               FROM support_tickets
                               WHERE user_id = :uid
                               ORDER BY created_at DESC');
        $stmt->execute([':uid' => $memberUserId]);
        $tickets = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'tickets' => $tickets,
            'count'   => count($tickets)
        ]);
        exit;
    } catch (PDOException $e) {
        error_log('Error fetching support tickets: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to retrieve tickets.']);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────
// POST: Submit or Update Ticket
// ─────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload = json_decode($rawInput, true) ?? $_POST;
    $action  = trim((string)($payload['action'] ?? 'create'));

    // --- Admin Actions ---
    if ($isAdmin && in_array($action, ['admin_reply', 'update_status'], true)) {
        $ticketId = (int)($payload['ticket_id'] ?? 0);
        if ($ticketId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid ticket ID.']);
            exit;
        }

        // Verify ticket exists
        $chk = $pdo->prepare('SELECT id, user_id, subject FROM support_tickets WHERE id = :id');
        $chk->execute([':id' => $ticketId]);
        $ticket = $chk->fetch();

        if (!$ticket) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Ticket not found.']);
            exit;
        }

        if ($action === 'admin_reply') {
            $reply = trim((string)($payload['reply'] ?? ''));
            $newStatus = trim((string)($payload['status'] ?? 'resolved'));
            if ($reply === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Reply content cannot be empty.']);
                exit;
            }
            if (!in_array($newStatus, ['open', 'in_progress', 'resolved', 'closed'], true)) {
                $newStatus = 'resolved';
            }

            $stmt = $pdo->prepare('UPDATE support_tickets SET admin_reply = :reply, status = :status, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':reply'  => $reply,
                ':status' => $newStatus,
                ':id'     => $ticketId
            ]);

            // Notify user if ticket has an owner
            if (!empty($ticket['user_id'])) {
                $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, related_type, related_id)
                                            VALUES (:uid, :type, :title, :message, :rel_type, :rel_id)');
                $notifStmt->execute([
                    ':uid'      => (int)$ticket['user_id'],
                    ':type'     => 'support_reply',
                    ':title'    => 'Update on Support Ticket #' . $ticketId,
                    ':message'  => 'An administrator responded to your ticket: "' . $ticket['subject'] . '". Response: ' . (strlen($reply) > 80 ? substr($reply, 0, 77) . '...' : $reply),
                    ':rel_type' => 'support_ticket',
                    ':rel_id'   => $ticketId
                ]);
            }

            // Audit log
            if (function_exists('logAuditAction')) {
                logAuditAction($pdo, $adminUser, 'support_ticket_reply', 'support_ticket', $ticketId, [
                    'reply_preview' => substr($reply, 0, 100),
                    'status' => $newStatus
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Response submitted and delivered to user.',
                'ticket_id' => $ticketId,
                'status' => $newStatus
            ]);
            exit;
        }

        if ($action === 'update_status') {
            $newStatus = trim((string)($payload['status'] ?? ''));
            if (!in_array($newStatus, ['open', 'in_progress', 'resolved', 'closed'], true)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid status.']);
                exit;
            }

            $stmt = $pdo->prepare('UPDATE support_tickets SET status = :status, updated_at = NOW() WHERE id = :id');
            $stmt->execute([':status' => $newStatus, ':id' => $ticketId]);

            if (function_exists('logAuditAction')) {
                logAuditAction($pdo, $adminUser, 'support_ticket_status_update', 'support_ticket', $ticketId, [
                    'status' => $newStatus
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Ticket status updated to ' . $newStatus,
                'ticket_id' => $ticketId,
                'status' => $newStatus
            ]);
            exit;
        }
    }

    // --- Member Create Ticket ---
    if ($action === 'create') {
        $subject  = trim((string)($payload['subject'] ?? ''));
        $category = trim((string)($payload['category'] ?? 'general'));
        $message  = trim((string)($payload['message'] ?? ($payload['description'] ?? '')));
        $priority = trim((string)($payload['priority'] ?? 'medium'));

        if ($subject === '' || $message === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Subject and message are required.']);
            exit;
        }

        if (!in_array($priority, ['low', 'medium', 'high', 'urgent'], true)) {
            $priority = 'medium';
        }

        // Fetch verified user info from database
        $uStmt = $pdo->prepare('SELECT name, email FROM users WHERE id = :id');
        $uStmt->execute([':id' => $memberUserId]);
        $userRow = $uStmt->fetch();

        $userName  = $userRow['name'] ?? ($_SESSION['icd_name'] ?? 'Community Member');
        $userEmail = $userRow['email'] ?? ($_SESSION['icd_email'] ?? 'member@icaredonate.org');

        $stmt = $pdo->prepare('INSERT INTO support_tickets (user_id, name, email, subject, category, message, status, priority, created_at, updated_at)
                               VALUES (:uid, :name, :email, :subject, :category, :message, :status, :priority, NOW(), NOW())');
        $stmt->execute([
            ':uid'      => $memberUserId,
            ':name'     => $userName,
            ':email'    => $userEmail,
            ':subject'  => $subject,
            ':category' => $category,
            ':message'  => $message,
            ':status'   => 'open',
            ':priority' => $priority
        ]);

        $ticketId = (int)$pdo->lastInsertId();

        // Also create a confirmation notification for the user
        $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, related_type, related_id)
                                    VALUES (:uid, :type, :title, :message, :rel_type, :rel_id)');
        $notifStmt->execute([
            ':uid'      => $memberUserId,
            ':type'     => 'ticket_created',
            ':title'    => 'Support Ticket #' . $ticketId . ' Received',
            ':message'  => 'Your ticket regarding "' . $subject . '" has been logged. Our moderation team will review it shortly.',
            ':rel_type' => 'support_ticket',
            ':rel_id'   => $ticketId
        ]);

        http_response_code(201);
        echo json_encode([
            'success'   => true,
            'message'   => 'Support ticket submitted successfully.',
            'ticket_id' => $ticketId
        ]);
        exit;
    }

    // --- Member Reply To Ticket ---
    if ($action === 'reply') {
        $ticketId = (int)($payload['ticket_id'] ?? 0);
        $replyText = trim((string)($payload['reply'] ?? ($payload['message'] ?? '')));

        if ($ticketId <= 0 || $replyText === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid ticket ID or empty reply.']);
            exit;
        }

        // Verify ticket belongs to this user
        $stmt = $pdo->prepare('SELECT id, message, status FROM support_tickets WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $ticketId, ':uid' => $memberUserId]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized or ticket not found.']);
            exit;
        }

        $appendedMessage = $ticket['message'] . "\n\n[Member Update - " . date('Y-m-d H:i') . "]:\n" . $replyText;

        $upStmt = $pdo->prepare('UPDATE support_tickets SET message = :msg, status = :status, updated_at = NOW() WHERE id = :id');
        $upStmt->execute([
            ':msg'    => $appendedMessage,
            ':status' => 'open', // Re-open or keep open for admin attention
            ':id'     => $ticketId
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Follow-up reply added successfully.',
            'ticket_id' => $ticketId
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unrecognized action.']);
    exit;
}
