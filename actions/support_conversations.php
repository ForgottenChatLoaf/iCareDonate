<?php
/**
 * iCareDonate — Internal Customer Support Messaging & Conversations Endpoint
 * actions/support_conversations.php
 *
 * Asynchronous threaded support inbox between authenticated Donors/Requesters
 * and Support Administrators (support_admin, head_admin, moderator).
 *
 * GET:
 *   - List conversations (user: own only; admin: all with filters)
 *   - Single conversation (?id=...) with messages (internal notes hidden from users)
 *   - ?unread_count=1: returns unread conversation/message count for badge
 *
 * POST:
 *   - create (user): start new conversation (optional donation_id OR request_id)
 *   - reply (user or support_admin): append message, update status, notify other party
 *   - internal_note (support_admin/head_admin): private note never shown to users
 *   - update_status (support_admin/head_admin): controlled status transitions
 *   - reopen (user): reopen own resolved conversation
 *   - assign (support_admin/head_admin): assign conversation to support admin
 *   - escalate (support_admin/head_admin): tag escalation department role
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

$userId = getAuthenticatedUserId();
if ($userId === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please sign in to access support.']);
    exit;
}

$stmtMe = $pdo->prepare('SELECT id, name, email, role, status FROM users WHERE id = :id LIMIT 1');
$stmtMe->execute([':id' => $userId]);
$me = $stmtMe->fetch();
if (!$me || in_array(strtolower((string)$me['status']), ['suspended', 'banned'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Your account is not active.']);
    exit;
}

$supportAdminRoles = ['support_admin', 'head_admin', 'admin', 'superadmin', 'moderator'];
$isSupportAdmin = in_array($me['role'], $supportAdminRoles, true);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ─────────────────────────────────────────────────────────────
// GET: Unread Count, Conversation List, or Single Thread
// ─────────────────────────────────────────────────────────────
if ($method === 'GET') {
    try {
        if (isset($_GET['unread_count']) && $_GET['unread_count'] === '1') {
            if ($isSupportAdmin) {
                $cnt = (int)$pdo->query("
                    SELECT COUNT(DISTINCT c.id)
                    FROM support_conversations c
                    INNER JOIN support_messages m ON m.conversation_id = c.id
                    WHERE m.sender_type = 'user' AND m.is_read = 0 AND m.is_internal_note = 0
                ")->fetchColumn();
            } else {
                $st = $pdo->prepare("
                    SELECT COUNT(DISTINCT c.id)
                    FROM support_conversations c
                    INNER JOIN support_messages m ON m.conversation_id = c.id
                    WHERE c.user_id = :uid AND m.sender_type IN ('support_admin','admin') AND m.is_read = 0 AND m.is_internal_note = 0
                ");
                $st->execute([':uid' => $userId]);
                $cnt = (int)$st->fetchColumn();
            }
            echo json_encode(['success' => true, 'unread_count' => $cnt]);
            exit;
        }

        $convId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($convId > 0) {
            $stmtC = $pdo->prepare("
                SELECT c.*, u.name AS user_name, u.email AS user_email, u.role AS user_role,
                       a.name AS assigned_admin_name,
                       d.title AS donation_title, d.status AS donation_status,
                       COALESCE(NULLIF(r.item_title, ''), rd.title) AS request_title, r.status AS request_status
                FROM support_conversations c
                INNER JOIN users u ON u.id = c.user_id
                LEFT JOIN users a ON a.id = c.assigned_admin_id
                LEFT JOIN donations d ON d.id = c.donation_id
                LEFT JOIN requests r ON r.id = c.request_id
                LEFT JOIN donations rd ON rd.id = r.donation_id
                WHERE c.id = :cid
                LIMIT 1
            ");
            $stmtC->execute([':cid' => $convId]);
            $conv = $stmtC->fetch();

            if (!$conv) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Conversation not found.']);
                exit;
            }

            // Deny-by-default ownership check
            if (!$isSupportAdmin && (int)$conv['user_id'] !== (int)$userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden. You can only view your own support conversations.']);
                exit;
            }

            $msgSql = "
                SELECT m.id, m.conversation_id, m.sender_id, m.sender_type, m.message,
                       m.is_internal_note, m.is_read, m.read_at, m.created_at,
                       u.name AS sender_name
                FROM support_messages m
                INNER JOIN users u ON u.id = m.sender_id
                WHERE m.conversation_id = :cid
            ";
            if (!$isSupportAdmin) {
                $msgSql .= " AND m.is_internal_note = 0";
            }
            $msgSql .= " ORDER BY m.created_at ASC, m.id ASC";

            $stmtM = $pdo->prepare($msgSql);
            $stmtM->execute([':cid' => $convId]);
            $messages = $stmtM->fetchAll();

            // Mark opposite party's unread messages as read
            if ($isSupportAdmin) {
                $pdo->prepare("UPDATE support_messages SET is_read = 1, read_at = NOW() WHERE conversation_id = :cid AND sender_type = 'user' AND is_read = 0")
                    ->execute([':cid' => $convId]);
            } else {
                $pdo->prepare("UPDATE support_messages SET is_read = 1, read_at = NOW() WHERE conversation_id = :cid AND sender_type IN ('support_admin','admin') AND is_internal_note = 0 AND is_read = 0")
                    ->execute([':cid' => $convId]);
            }

            echo json_encode([
                'success'      => true,
                'conversation' => $conv,
                'messages'     => $messages,
            ]);
            exit;
        }

        // List conversations
        $statusFilter = trim((string)($_GET['status'] ?? ''));
        $catFilter    = trim((string)($_GET['category'] ?? ''));
        $search       = trim((string)($_GET['q'] ?? ''));
        $unreadOnly   = isset($_GET['unread_only']) && $_GET['unread_only'] === '1';

        $unreadExpr = $isSupportAdmin
            ? "(SELECT COUNT(*) FROM support_messages m2 WHERE m2.conversation_id = c.id AND m2.sender_type = 'user' AND m2.is_read = 0 AND m2.is_internal_note = 0)"
            : "(SELECT COUNT(*) FROM support_messages m2 WHERE m2.conversation_id = c.id AND m2.sender_type IN ('support_admin','admin') AND m2.is_read = 0 AND m2.is_internal_note = 0)";

        $sql = "
            SELECT c.*, u.name AS user_name, u.email AS user_email,
                   a.name AS assigned_admin_name,
                   d.title AS donation_title,
                   COALESCE(NULLIF(r.item_title, ''), rd.title) AS request_title,
                   {$unreadExpr} AS unread_count,
                   (SELECT m3.message FROM support_messages m3 WHERE m3.conversation_id = c.id AND m3.is_internal_note = 0 ORDER BY m3.id DESC LIMIT 1) AS last_message
            FROM support_conversations c
            INNER JOIN users u ON u.id = c.user_id
            LEFT JOIN users a ON a.id = c.assigned_admin_id
            LEFT JOIN donations d ON d.id = c.donation_id
            LEFT JOIN requests r ON r.id = c.request_id
            LEFT JOIN donations rd ON rd.id = r.donation_id
            WHERE 1=1
        ";
        $params = [];

        if (!$isSupportAdmin || empty($_GET['admin_view'])) {
            $sql .= ' AND c.user_id = :uid';
            $params[':uid'] = $userId;
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['open', 'waiting_support', 'waiting_user', 'resolved', 'closed'], true)) {
            $sql .= ' AND c.status = :status';
            $params[':status'] = $statusFilter;
        }
        if ($catFilter !== '') {
            $sql .= ' AND c.category = :cat';
            $params[':cat'] = $catFilter;
        }
        if ($search !== '') {
            $sql .= ' AND (c.subject LIKE :s OR u.name LIKE :s OR u.email LIKE :s)';
            $params[':s'] = '%' . $search . '%';
        }
        if ($unreadOnly) {
            $sql .= " AND {$unreadExpr} > 0";
        }

        $sql .= ' ORDER BY c.updated_at DESC, c.id DESC LIMIT 100';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $convs = $stmt->fetchAll();

        // Also return list of support admins if admin view
        $supportStaff = [];
        if ($isSupportAdmin && !empty($_GET['admin_view'])) {
            $supportStaff = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('support_admin','head_admin','moderator') AND status = 'active' ORDER BY name ASC")->fetchAll();
        }

        echo json_encode([
            'success'       => true,
            'conversations' => $convs,
            'support_staff' => $supportStaff,
        ]);
        exit;
    } catch (Throwable $e) {
        error_log('support_conversations GET error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load conversations.']);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────
// POST: Create, Reply, Internal Note, Status, Assign, Escalate
// ─────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (!icd_verify_csrf_request(is_array($payload) ? $payload : null)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
        exit;
    }

    icd_enforce_rate_limit('support_msg_' . $userId, 25, 300);

    $action = trim((string)($payload['action'] ?? 'create'));

    try {
        // 1. Create New Conversation (User)
        if ($action === 'create') {
            $subject    = trim((string)($payload['subject'] ?? ''));
            $category   = trim((string)($payload['category'] ?? 'general'));
            $message    = trim((string)($payload['message'] ?? ''));
            $priority   = trim((string)($payload['priority'] ?? 'medium'));
            $donationId = !empty($payload['donation_id']) ? (int)$payload['donation_id'] : null;
            $requestId  = !empty($payload['request_id']) ? (int)$payload['request_id'] : null;

            if ($subject === '' || mb_strlen($subject) < 3 || mb_strlen($subject) > 200) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Subject must be between 3 and 200 characters.']);
                exit;
            }
            if ($message === '' || mb_strlen($message) < 5 || mb_strlen($message) > 3000) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Message must be between 5 and 3,000 characters.']);
                exit;
            }
            if (!in_array($priority, ['low', 'medium', 'high', 'urgent'], true)) {
                $priority = 'medium';
            }
            if ($donationId !== null && $requestId !== null) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'A conversation cannot reference both an unrelated donation and assistance request.']);
                exit;
            }

            if ($donationId !== null) {
                $chkD = $pdo->prepare('SELECT id FROM donations WHERE id = :id AND donor_id = :uid LIMIT 1');
                $chkD->execute([':id' => $donationId, ':uid' => $userId]);
                if (!$chkD->fetch()) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'You can only attach your own donation to a support conversation.']);
                    exit;
                }
            }
            if ($requestId !== null) {
                $chkR = $pdo->prepare('SELECT id FROM requests WHERE id = :id AND requester_id = :uid LIMIT 1');
                $chkR->execute([':id' => $requestId, ':uid' => $userId]);
                if (!$chkR->fetch()) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'You can only attach your own assistance request to a support conversation.']);
                    exit;
                }
            }

            $pdo->beginTransaction();
            $pdo->prepare("
                INSERT INTO support_conversations (user_id, subject, category, donation_id, request_id, status, priority, created_at, updated_at)
                VALUES (:uid, :subj, :cat, :did, :rid, 'waiting_support', :prio, NOW(), NOW())
            ")->execute([
                ':uid'  => $userId,
                ':subj' => $subject,
                ':cat'  => $category !== '' ? $category : 'general',
                ':did'  => $donationId,
                ':rid'  => $requestId,
                ':prio' => $priority,
            ]);
            $convId = (int)$pdo->lastInsertId();

            $pdo->prepare("
                INSERT INTO support_messages (conversation_id, sender_id, sender_type, message, is_internal_note, is_read, created_at)
                VALUES (:cid, :sid, 'user', :msg, 0, 0, NOW())
            ")->execute([
                ':cid' => $convId,
                ':sid' => $userId,
                ':msg' => $message,
            ]);

            // Notify active support_admin accounts (or head_admin if none)
            $supAdmins = $pdo->query("SELECT id FROM users WHERE role IN ('support_admin', 'head_admin') AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
            $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_type, related_id) VALUES (:uid, 'support_new', :title, :msg, 'support_conversation', :cid)");
            foreach ($supAdmins as $saId) {
                $nStmt->execute([
                    ':uid'   => (int)$saId,
                    ':title' => "New Support Conversation #{$convId}",
                    ':msg'   => "{$me['name']} opened a support conversation: \"{$subject}\".",
                    ':cid'   => $convId,
                ]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Support conversation started.', 'conversation_id' => $convId]);
            exit;
        }

        // All subsequent actions require conversation_id
        $convId = (int)($payload['conversation_id'] ?? ($payload['id'] ?? 0));
        if ($convId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Valid conversation ID is required.']);
            exit;
        }

        $stmtC = $pdo->prepare('SELECT * FROM support_conversations WHERE id = :id LIMIT 1');
        $stmtC->execute([':id' => $convId]);
        $conv = $stmtC->fetch();
        if (!$conv) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Conversation not found.']);
            exit;
        }

        // 2. Reply (User or Support Admin)
        if ($action === 'reply') {
            if (!$isSupportAdmin && (int)$conv['user_id'] !== (int)$userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit;
            }
            if ($conv['status'] === 'closed' && !$isSupportAdmin) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'This conversation is closed. Please start a new conversation if you need further help.']);
                exit;
            }

            $message = trim((string)($payload['message'] ?? ''));
            if ($message === '' || mb_strlen($message) > 3000) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Message cannot be empty (max 3,000 characters).']);
                exit;
            }

            // Duplicate message guard (identical message in last 15 seconds)
            $dupStmt = $pdo->prepare("SELECT id FROM support_messages WHERE conversation_id = :cid AND sender_id = :sid AND message = :msg AND created_at >= DATE_SUB(NOW(), INTERVAL 15 SECOND) LIMIT 1");
            $dupStmt->execute([':cid' => $convId, ':sid' => $userId, ':msg' => $message]);
            if ($dupStmt->fetch()) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Duplicate message detected. Your previous message was already sent.']);
                exit;
            }

            $senderType = $isSupportAdmin ? ($me['role'] === 'support_admin' ? 'support_admin' : 'admin') : 'user';
            $nextStatus = $isSupportAdmin ? 'waiting_user' : 'waiting_support';

            $pdo->beginTransaction();
            $pdo->prepare("
                INSERT INTO support_messages (conversation_id, sender_id, sender_type, message, is_internal_note, is_read, created_at)
                VALUES (:cid, :sid, :stype, :msg, 0, 0, NOW())
            ")->execute([
                ':cid'   => $convId,
                ':sid'   => $userId,
                ':stype' => $senderType,
                ':msg'   => $message,
            ]);

            $pdo->prepare("UPDATE support_conversations SET status = :st, updated_at = NOW() WHERE id = :cid")
                ->execute([':st' => $nextStatus, ':cid' => $convId]);

            if ($isSupportAdmin) {
                $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_type, related_id) VALUES (:uid, 'support_reply', :title, :msg, 'support_conversation', :cid)")
                    ->execute([
                        ':uid'   => (int)$conv['user_id'],
                        ':title' => "Support Reply on #{$convId}",
                        ':msg'   => "Customer Support replied to \"{$conv['subject']}\". Open your Support Inbox to read and respond.",
                        ':cid'   => $convId,
                    ]);
                logAdminAction($pdo, 'SUPPORT_CONVERSATION_REPLY', $userId, $me['name'], 'support_conversation', $convId, "Replied to conversation #{$convId}");
            } else {
                $targetAdminId = !empty($conv['assigned_admin_id']) ? [(int)$conv['assigned_admin_id']] : $pdo->query("SELECT id FROM users WHERE role IN ('support_admin','head_admin') AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
                $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_type, related_id) VALUES (:uid, 'support_reply', :title, :msg, 'support_conversation', :cid)");
                foreach ($targetAdminId as $aid) {
                    $nStmt->execute([
                        ':uid'   => (int)$aid,
                        ':title' => "User Reply on Support #{$convId}",
                        ':msg'   => "{$me['name']} replied in \"{$conv['subject']}\".",
                        ':cid'   => $convId,
                    ]);
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Reply sent.', 'status' => $nextStatus]);
            exit;
        }

        // 3. Private Internal Note (Support Admin / Head Admin only)
        if ($action === 'internal_note') {
            if (!$isSupportAdmin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit;
            }
            $message = trim((string)($payload['message'] ?? ''));
            if ($message === '' || mb_strlen($message) > 3000) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Internal note cannot be empty.']);
                exit;
            }
            $pdo->prepare("
                INSERT INTO support_messages (conversation_id, sender_id, sender_type, message, is_internal_note, is_read, created_at)
                VALUES (:cid, :sid, 'support_admin', :msg, 1, 1, NOW())
            ")->execute([':cid' => $convId, ':sid' => $userId, ':msg' => $message]);

            logAdminAction($pdo, 'SUPPORT_INTERNAL_NOTE', $userId, $me['name'], 'support_conversation', $convId, "Added private internal note on #{$convId}");
            echo json_encode(['success' => true, 'message' => 'Private internal note saved.']);
            exit;
        }

        // 4. Reopen (User on own resolved conversation)
        if ($action === 'reopen') {
            if ((int)$conv['user_id'] !== (int)$userId && !$isSupportAdmin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden.']);
                exit;
            }
            if ($conv['status'] !== 'resolved' && !$isSupportAdmin) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Only resolved conversations can be reopened by users.']);
                exit;
            }
            $pdo->prepare("UPDATE support_conversations SET status = 'waiting_support', resolved_at = NULL, closed_at = NULL, updated_at = NOW() WHERE id = :cid")
                ->execute([':cid' => $convId]);
            echo json_encode(['success' => true, 'message' => 'Conversation reopened.', 'status' => 'waiting_support']);
            exit;
        }

        // Remaining actions are Support Admin only
        if (!$isSupportAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden. Administrator privileges required.']);
            exit;
        }

        if ($action === 'update_status') {
            $newStatus = trim((string)($payload['status'] ?? ''));
            if (!in_array($newStatus, ['open', 'waiting_support', 'waiting_user', 'resolved', 'closed'], true)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Invalid conversation status.']);
                exit;
            }
            $resolvedExpr = $newStatus === 'resolved' ? 'NOW()' : ($newStatus === 'open' || $newStatus === 'waiting_support' ? 'NULL' : 'resolved_at');
            $closedExpr   = $newStatus === 'closed' ? 'NOW()' : 'NULL';
            $pdo->prepare("UPDATE support_conversations SET status = :st, resolved_at = {$resolvedExpr}, closed_at = {$closedExpr}, updated_at = NOW() WHERE id = :cid")
                ->execute([':st' => $newStatus, ':cid' => $convId]);

            if (in_array($newStatus, ['resolved', 'waiting_user'], true)) {
                $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_type, related_id) VALUES (:uid, 'support_status', :title, :msg, 'support_conversation', :cid)")
                    ->execute([
                        ':uid'   => (int)$conv['user_id'],
                        ':title' => "Support Conversation #{$convId} Updated",
                        ':msg'   => "Your conversation \"{$conv['subject']}\" status changed to " . str_replace('_', ' ', $newStatus) . ".",
                        ':cid'   => $convId,
                    ]);
            }
            logAdminAction($pdo, 'SUPPORT_STATUS_UPDATED', $userId, $me['name'], 'support_conversation', $convId, "Changed status to {$newStatus}");
            echo json_encode(['success' => true, 'message' => 'Conversation status updated.', 'status' => $newStatus]);
            exit;
        }

        if ($action === 'assign') {
            $assignId = !empty($payload['assigned_admin_id']) ? (int)$payload['assigned_admin_id'] : $userId;
            $pdo->prepare("UPDATE support_conversations SET assigned_admin_id = :aid, updated_at = NOW() WHERE id = :cid")
                ->execute([':aid' => $assignId, ':cid' => $convId]);
            logAdminAction($pdo, 'SUPPORT_ASSIGNED', $userId, $me['name'], 'support_conversation', $convId, "Assigned conversation #{$convId} to admin #{$assignId}");
            echo json_encode(['success' => true, 'message' => 'Conversation assigned.']);
            exit;
        }

        if ($action === 'escalate') {
            $deptRole = trim((string)($payload['escalated_to_role'] ?? ''));
            if (!in_array($deptRole, ['donation_admin', 'campaign_admin', 'moderator', 'head_admin', ''], true)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Invalid escalation role.']);
                exit;
            }
            $pdo->prepare("UPDATE support_conversations SET escalated_to_role = :r, updated_at = NOW() WHERE id = :cid")
                ->execute([':r' => $deptRole !== '' ? $deptRole : null, ':cid' => $convId]);
            logAdminAction($pdo, 'SUPPORT_ESCALATED', $userId, $me['name'], 'support_conversation', $convId, "Escalated conversation #{$convId} to {$deptRole}");
            echo json_encode(['success' => true, 'message' => $deptRole ? "Escalated to {$deptRole}." : 'Escalation cleared.']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unsupported support action.']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('support_conversations POST error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to process support request.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
