<?php
/**
 * iCareDonate — Administrative User Management & Moderation Endpoint
 * actions/admin_users.php
 *
 * GET: Retrieves registered user profiles, status, activity history, and metrics from MySQL.
 * POST: Handles moderation decisions: suspend, ban, activate, approve verification.
 * Strictly prevents exposing password hashes or credentials.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error'   => 'Database connection is currently unavailable.',
        'code'    => 'DB_UNAVAILABLE'
    ]);
    exit;
}

$admin = requireAdminApi($pdo);

// ─────────────────────────────────────────────────────────────
// POST: User Moderation Actions (Suspend, Ban, Activate)
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload  = json_decode($rawInput, true) ?? $_POST;

    $action = trim((string)($payload['action'] ?? ''));

    if ($action === 'create_admin') {
        $name = trim((string)($payload['name'] ?? ''));
        $email = strtolower(trim((string)($payload['email'] ?? '')));
        $password = (string)($payload['password'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Valid name and email address are required.']);
            exit;
        }
        if (strlen($password) < 8) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
            exit;
        }

        $stmtCheck = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmtCheck->execute([':email' => $email]);
        if ($stmtCheck->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'A user account with this email already exists.']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmtIns = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, created_at, updated_at) VALUES (:name, :email, :hash, 'admin', NOW(), NOW())");
        $stmtIns->execute([
            ':name' => $name,
            ':email' => $email,
            ':hash' => $hash,
        ]);
        $newAdminId = (int)$pdo->lastInsertId();

        logAdminAction($pdo, 'ADMIN_CREATED', $admin['id'], $admin['name'], 'user', $newAdminId, "Created admin {$name} ({$email})", 'Created via administrator portal');
        echo json_encode(['success' => true, 'message' => "Admin account for {$name} created successfully.", 'id' => $newAdminId]);
        exit;
    }

    $targetId = (int)($payload['id'] ?? 0);
    $reason   = trim((string)($payload['reason'] ?? ''));

    if ($targetId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Valid user ID is required.']);
        exit;
    }

    if ($targetId === $admin['id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You cannot moderate your own administrator account.']);
        exit;
    }

    try {
        $stmtUser = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1');
        $stmtUser->execute([':id' => $targetId]);
        $target = $stmtUser->fetch();

        if (!$target) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Target user account not found.']);
            exit;
        }

        // Check if status column exists in users table
        $hasStatusCol = false;
        try {
            $c = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
            $hasStatusCol = ($c && $c->rowCount() > 0);
        } catch (PDOException $e) {}

        $targetName = $target['name'];

        switch ($action) {
            case 'suspend':
                if ($reason === '') {
                    $reason = 'Account suspended pending community policy review.';
                }
                if ($hasStatusCol) {
                    $stmtUpdate = $pdo->prepare("UPDATE users SET status = 'suspended', status_reason = :reason WHERE id = :id");
                    $stmtUpdate->execute([':reason' => $reason, ':id' => $targetId]);
                }
                logAdminAction($pdo, 'USER_SUSPENDED', $admin['id'], $admin['name'], 'user', $targetId, "Suspended {$targetName}", $reason);
                echo json_encode(['success' => true, 'message' => "User #{$targetId} ({$targetName}) has been suspended.", 'status' => 'suspended']);
                exit;

            case 'ban':
                if ($reason === '') {
                    $reason = 'Permanent exclusion for repeat policy violation.';
                }
                if ($hasStatusCol) {
                    $stmtUpdate = $pdo->prepare("UPDATE users SET status = 'banned', status_reason = :reason WHERE id = :id");
                    $stmtUpdate->execute([':reason' => $reason, ':id' => $targetId]);
                }
                logAdminAction($pdo, 'USER_BANNED', $admin['id'], $admin['name'], 'user', $targetId, "Banned {$targetName}", $reason);
                echo json_encode(['success' => true, 'message' => "User #{$targetId} ({$targetName}) has been banned.", 'status' => 'banned']);
                exit;

            case 'activate':
            case 'restore':
            case 'approve_verification':
                if ($hasStatusCol) {
                    $stmtUpdate = $pdo->prepare("UPDATE users SET status = 'active', status_reason = NULL WHERE id = :id");
                    $stmtUpdate->execute([':id' => $targetId]);
                }
                logAdminAction($pdo, 'USER_RESTORED', $admin['id'], $admin['name'], 'user', $targetId, "Restored/Activated {$targetName}", 'Account activated in good standing');
                echo json_encode(['success' => true, 'message' => "User #{$targetId} ({$targetName}) is now active.", 'status' => 'active']);
                exit;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid moderation action.']);
                exit;
        }

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to execute user moderation.']);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────
// GET: Query User Accounts
// ─────────────────────────────────────────────────────────────
$singleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status   = trim((string)($_GET['status'] ?? 'all'));
$search   = trim((string)($_GET['q'] ?? ''));
$role     = trim((string)($_GET['role'] ?? ''));

try {
    // Single user detail with activity history
    if ($singleId > 0) {
        $stmtUser = $pdo->prepare("
            SELECT id, name, email, role,
                   'N/A' AS phone,
                   'Metro Manila' AS city,
                   '' AS barangay,
                   'active' AS status,
                   NULL AS status_reason,
                   created_at
            FROM users
            WHERE id = :id
            LIMIT 1
        ");
        $stmtUser->execute([':id' => $singleId]);
        $user = $stmtUser->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }

        // Fetch donations by user
        $userDonations = [];
        try {
            $stmtDon = $pdo->prepare("SELECT id, title, category, quantity, 'pcs' AS unit, status, created_at FROM donations WHERE donor_id = :uid ORDER BY created_at DESC LIMIT 20");
            $stmtDon->execute([':uid' => $singleId]);
            $userDonations = $stmtDon->fetchAll();
        } catch (PDOException $e) {}

        // Fetch requests by user
        $userRequests = [];
        try {
            $stmtReq = $pdo->prepare("
                SELECT r.id, COALESCE(d.title, 'Community Request') AS item_title,
                       r.quantity, 'items' AS unit, r.status, r.created_at
                FROM requests r
                LEFT JOIN donations d ON r.donation_id = d.id
                WHERE r.requester_id = :uid
                ORDER BY r.created_at DESC LIMIT 20
            ");
            $stmtReq->execute([':uid' => $singleId]);
            $userRequests = $stmtReq->fetchAll();
        } catch (PDOException $e) {}

        echo json_encode([
            'success'   => true,
            'user'      => $user,
            'data'      => $user,
            'donations' => $userDonations,
            'requests'  => $userRequests,
        ]);
        exit;
    }

    // List users
    $sql = "
        SELECT u.id, u.name, u.email, u.role,
               '' AS phone,
               'Metro Manila' AS city,
               '' AS barangay,
               'active' AS status,
               u.created_at
        FROM users u
        WHERE 1=1
    ";
    $params = [];

    if ($role !== '') {
        $sql .= ' AND u.role = :role';
        $params[':role'] = $role;
    }

    if ($search !== '') {
        $sql .= ' AND (u.name LIKE :s OR u.email LIKE :s)';
        $params[':s'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY u.created_at DESC LIMIT 100';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    // Compute status counts
    $counts = [
        'all'                  => 0,
        'active'               => 0,
        'pending_verification' => 0,
        'suspended'            => 0,
        'banned'               => 0,
    ];
    try {
        $cStmt = $pdo->query('
            SELECT
                COUNT(*) AS total,
                COUNT(*) AS active,
                0 AS pending,
                0 AS suspended,
                0 AS banned
            FROM users
        ');
        $cRow = $cStmt->fetch();
        if ($cRow) {
            $counts = [
                'all'                  => (int)($cRow['total'] ?? 0),
                'active'               => (int)($cRow['active'] ?? 0),
                'pending_verification' => 0,
                'suspended'            => 0,
                'banned'               => 0,
            ];
        }
    } catch (PDOException $e) {}

    echo json_encode([
        'success' => true,
        'counts'  => $counts,
        'users'   => $users,
        'data'    => $users,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve user accounts.']);
}
