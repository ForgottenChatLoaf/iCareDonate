<?php
/**
 * iCareDonate — Administrative User & Administrator Account Management Endpoint
 * actions/admin_users.php
 *
 * GET: Retrieves user & administrator profiles, status, activity history, and metrics.
 * POST: Create admin (Head Admin only), update admin/user profile, change role (Head Admin only),
 *       initiate secure password reset (Head Admin only), and moderate (suspend/ban/activate).
 * Strictly prevents exposing password hashes or plaintext passwords.
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

$admin = requireAdminRoleApi($pdo, ['moderator']);
$headRoles = ['admin', 'head_admin', 'superadmin'];
$allAdminRoles = ['admin', 'head_admin', 'superadmin', 'donation_admin', 'campaign_admin', 'moderator', 'support_admin'];
$isHeadAdmin = in_array($admin['role'], $headRoles, true);

// ─────────────────────────────────────────────────────────────
// POST: Account Provisioning, Editing, Password Reset & Moderation
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload  = json_decode($rawInput, true) ?? $_POST;
    if (!icd_verify_csrf_request(is_array($payload) ? $payload : null)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
        exit;
    }

    $action = trim((string)($payload['action'] ?? ''));

    // 1. Create Administrator (Head Admin Only)
    if ($action === 'create_admin') {
        if (!$isHeadAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden. Only Head Administrators can provision administrator accounts.']);
            exit;
        }
        $name       = trim((string)($payload['name'] ?? ''));
        $email      = strtolower(trim((string)($payload['email'] ?? '')));
        $password   = (string)($payload['password'] ?? '');
        $phone      = trim((string)($payload['phone'] ?? ''));
        $department = trim((string)($payload['department'] ?? ''));
        $reqRole    = strtolower(trim((string)($payload['role'] ?? 'donation_admin')));
        $allowedCreateRoles = ['admin', 'head_admin', 'donation_admin', 'campaign_admin', 'moderator', 'support_admin'];
        $assignedRole = in_array($reqRole, $allowedCreateRoles, true) ? $reqRole : 'donation_admin';

        if ($name === '' || mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Valid display name and email address are required.']);
            exit;
        }
        $pwErr = icd_validate_password_policy($password);
        if ($pwErr !== null) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $pwErr]);
            exit;
        }

        $stmtCheck = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmtCheck->execute([':email' => $email]);
        if ($stmtCheck->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'A user account with this email already exists.']);
            exit;
        }

        $nameParts = preg_split('/\s+/', $name);
        $firstName = $nameParts[0] ?? $name;
        $lastName  = implode(' ', array_slice($nameParts, 1)) ?: 'Administrator';

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmtIns = $pdo->prepare("
            INSERT INTO users (name, first_name, last_name, email, password_hash, must_change_password, phone, department, role, status, created_at, updated_at)
            VALUES (:name, :fn, :ln, :email, :hash, 1, :phone, :dept, :role, 'active', NOW(), NOW())
        ");
        $stmtIns->execute([
            ':name'  => $name,
            ':fn'    => $firstName,
            ':ln'    => $lastName,
            ':email' => $email,
            ':hash'  => $hash,
            ':phone' => $phone !== '' ? $phone : null,
            ':dept'  => $department !== '' ? $department : null,
            ':role'  => $assignedRole,
        ]);
        $newAdminId = (int)$pdo->lastInsertId();

        logAdminAction($pdo, 'ADMIN_CREATED', $admin['id'], $admin['name'], 'user', $newAdminId, "Created {$assignedRole} account for {$name} ({$email})", 'Created via administrator portal');
        echo json_encode(['success' => true, 'message' => "Admin account for {$name} ({$assignedRole}) created successfully.", 'id' => $newAdminId]);
        exit;
    }

    $targetId = (int)($payload['id'] ?? 0);
    $reason   = trim((string)($payload['reason'] ?? ''));

    if ($targetId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Valid user ID is required.']);
        exit;
    }

    try {
        $stmtUser = $pdo->prepare('SELECT id, name, first_name, last_name, email, phone, city, barangay, department, role, status FROM users WHERE id = :id LIMIT 1');
        $stmtUser->execute([':id' => $targetId]);
        $target = $stmtUser->fetch();

        if (!$target) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Target user account not found.']);
            exit;
        }

        $targetIsAdmin = in_array($target['role'], $allAdminRoles, true);

        // 2. Update Administrator Profile & Role (Head Admin Only)
        if ($action === 'update_admin') {
            if (!$isHeadAdmin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden. Only Head Administrators can edit administrator accounts.']);
                exit;
            }

            $firstName  = trim((string)($payload['first_name'] ?? ''));
            $lastName   = trim((string)($payload['last_name'] ?? ''));
            $name       = trim((string)($payload['name'] ?? trim($firstName . ' ' . $lastName)));
            $email      = strtolower(trim((string)($payload['email'] ?? $target['email'])));
            $phone      = trim((string)($payload['phone'] ?? ($target['phone'] ?? '')));
            $department = trim((string)($payload['department'] ?? ($target['department'] ?? '')));
            $newRole    = strtolower(trim((string)($payload['role'] ?? $target['role'])));

            if ($name === '' || mb_strlen($name) < 2) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Valid administrator display name is required.']);
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Valid email address is required.']);
                exit;
            }
            $allowedAdminRoles = ['admin', 'head_admin', 'donation_admin', 'campaign_admin', 'moderator', 'support_admin'];
            if (!in_array($newRole, $allowedAdminRoles, true)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Invalid administrator role specified.']);
                exit;
            }

            // Protect last active Head Administrator from demotion
            if (in_array($target['role'], $headRoles, true) && !in_array($newRole, $headRoles, true)) {
                $headCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'head_admin', 'superadmin') AND status = 'active'")->fetchColumn();
                if ($headCount <= 1) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Action blocked: Cannot demote the last active Head Administrator.']);
                    exit;
                }
            }

            // Email uniqueness check
            $dup = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) AND id != :id LIMIT 1');
            $dup->execute([':email' => $email, ':id' => $targetId]);
            if ($dup->fetch()) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Another account already uses this email address.']);
                exit;
            }

            if ($firstName === '' && $lastName === '') {
                $parts = preg_split('/\s+/', $name);
                $firstName = $parts[0] ?? $name;
                $lastName  = implode(' ', array_slice($parts, 1));
            }

            $pdo->prepare("
                UPDATE users
                SET name = :name, first_name = :fn, last_name = :ln, email = :email,
                    phone = :phone, department = :dept, role = :role, updated_at = NOW()
                WHERE id = :id
            ")->execute([
                ':name'  => $name,
                ':fn'    => $firstName,
                ':ln'    => $lastName,
                ':email' => $email,
                ':phone' => $phone !== '' ? $phone : null,
                ':dept'  => $department !== '' ? $department : null,
                ':role'  => $newRole,
                ':id'    => $targetId,
            ]);

            if ($targetId === $admin['id']) {
                $_SESSION['icd_name']  = $name;
                $_SESSION['icd_email'] = $email;
                $_SESSION['icd_role']  = $newRole;
            }

            logAdminAction($pdo, 'ADMIN_UPDATED', $admin['id'], $admin['name'], 'user', $targetId, "Updated administrator {$name} ({$email}, role={$newRole})");
            echo json_encode(['success' => true, 'message' => "Administrator {$name} updated successfully."]);
            exit;
        }

        // 3. Update Regular User Details (Head Admin or Moderator for non-admins)
        if ($action === 'update_user') {
            if ($targetIsAdmin && !$isHeadAdmin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden. Moderators cannot edit administrator accounts.']);
                exit;
            }

            $firstName = trim((string)($payload['first_name'] ?? ''));
            $lastName  = trim((string)($payload['last_name'] ?? ''));
            $name      = trim((string)($payload['name'] ?? trim($firstName . ' ' . $lastName)));
            $email     = strtolower(trim((string)($payload['email'] ?? $target['email'])));
            $phone     = trim((string)($payload['phone'] ?? ($target['phone'] ?? '')));
            $city      = trim((string)($payload['city'] ?? ($target['city'] ?? '')));
            $barangay  = trim((string)($payload['barangay'] ?? ($target['barangay'] ?? '')));

            if ($name === '' || mb_strlen($name) < 2) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Valid user name is required.']);
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Valid email address is required.']);
                exit;
            }

            $dup = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) AND id != :id LIMIT 1');
            $dup->execute([':email' => $email, ':id' => $targetId]);
            if ($dup->fetch()) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Another account already uses this email address.']);
                exit;
            }

            if ($firstName === '' && $lastName === '') {
                $parts = preg_split('/\s+/', $name);
                $firstName = $parts[0] ?? $name;
                $lastName  = implode(' ', array_slice($parts, 1));
            }

            // Only Head Admin can change a regular user's role between donor/requester
            $roleToSave = $target['role'];
            if ($isHeadAdmin && isset($payload['role'])) {
                $reqUserRole = strtolower(trim((string)$payload['role']));
                if (in_array($reqUserRole, ['donor', 'requester'], true)) {
                    $roleToSave = $reqUserRole;
                }
            }

            $pdo->prepare("
                UPDATE users
                SET name = :name, first_name = :fn, last_name = :ln, email = :email,
                    phone = :phone, city = :city, barangay = :brgy, role = :role, updated_at = NOW()
                WHERE id = :id
            ")->execute([
                ':name'  => $name,
                ':fn'    => $firstName,
                ':ln'    => $lastName,
                ':email' => $email,
                ':phone' => $phone !== '' ? $phone : null,
                ':city'  => $city !== '' ? $city : null,
                ':brgy'  => $barangay !== '' ? $barangay : null,
                ':role'  => $roleToSave,
                ':id'    => $targetId,
            ]);

            logAdminAction($pdo, 'USER_UPDATED', $admin['id'], $admin['name'], 'user', $targetId, "Updated user profile for {$name} ({$email})");
            echo json_encode(['success' => true, 'message' => "User {$name} updated successfully."]);
            exit;
        }

        // 4. Initiate Secure Single-Use Password Reset (Head Admin Only)
        if ($action === 'initiate_password_reset') {
            if (!$isHeadAdmin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden. Only Head Administrators can initiate password resets.']);
                exit;
            }

            $rawToken  = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);

            $pdo->beginTransaction();
            // Invalidate any previous unused reset tokens for this user
            $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL")
                ->execute([':uid' => $targetId]);
            $pdo->prepare("
                INSERT INTO password_resets (user_id, token_hash, expires_at, created_by, created_at)
                VALUES (:uid, :th, DATE_ADD(NOW(), INTERVAL 1 HOUR), :by, NOW())
            ")->execute([
                ':uid' => $targetId,
                ':th'  => $tokenHash,
                ':by'  => $admin['id'],
            ]);
            $pdo->prepare("UPDATE users SET must_change_password = 1, updated_at = NOW() WHERE id = :uid")
                ->execute([':uid' => $targetId]);
            icd_invalidate_user_sessions($pdo, $targetId, null);
            $pdo->commit();

            logAdminAction($pdo, 'PASSWORD_RESET_INITIATED', $admin['id'], $admin['name'], 'user', $targetId, "Initiated 1-hour single-use password reset for {$target['name']} ({$target['email']})");

            $resetUrl = _icd_root() . 'frontend/reset-password.html?token=' . $rawToken;
            echo json_encode([
                'success'   => true,
                'message'   => "Secure single-use password reset link generated for {$target['name']} (expires in 1 hour). Existing sessions invalidated.",
                'reset_url' => $resetUrl,
                'token'     => $rawToken,
            ]);
            exit;
        }

        // 5. Moderation actions (cannot moderate self or admin accounts unless Head Admin)
        if ($targetId === $admin['id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You cannot moderate your own account.']);
            exit;
        }

        if ($targetIsAdmin && !$isHeadAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden. Moderators cannot moderate administrator accounts.']);
            exit;
        }

        // Prevent removing, suspending, or banning the last active Head Administrator
        if (in_array($target['role'], $headRoles, true) && in_array($action, ['suspend', 'ban'], true)) {
            $headCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'head_admin', 'superadmin') AND status = 'active'")->fetchColumn();
            if ($headCount <= 1) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Action blocked: Cannot suspend or ban the last active Head Administrator.']);
                exit;
            }
        }

        $targetName = $target['name'];

        switch ($action) {
            case 'suspend':
                if ($reason === '') {
                    $reason = 'Account suspended pending community policy review.';
                }
                $pdo->prepare("UPDATE users SET status = 'suspended', status_reason = :reason, updated_at = NOW() WHERE id = :id")
                    ->execute([':reason' => $reason, ':id' => $targetId]);
                icd_invalidate_user_sessions($pdo, $targetId, null);
                logAdminAction($pdo, 'USER_SUSPENDED', $admin['id'], $admin['name'], 'user', $targetId, "Suspended {$targetName}", $reason);
                echo json_encode(['success' => true, 'message' => "Account #{$targetId} ({$targetName}) has been suspended.", 'status' => 'suspended']);
                exit;

            case 'ban':
                if ($reason === '') {
                    $reason = 'Permanent exclusion for repeat policy violation.';
                }
                $pdo->prepare("UPDATE users SET status = 'banned', status_reason = :reason, updated_at = NOW() WHERE id = :id")
                    ->execute([':reason' => $reason, ':id' => $targetId]);
                icd_invalidate_user_sessions($pdo, $targetId, null);
                logAdminAction($pdo, 'USER_BANNED', $admin['id'], $admin['name'], 'user', $targetId, "Banned {$targetName}", $reason);
                echo json_encode(['success' => true, 'message' => "Account #{$targetId} ({$targetName}) has been banned.", 'status' => 'banned']);
                exit;

            case 'activate':
            case 'restore':
            case 'approve_verification':
                $pdo->prepare("UPDATE users SET status = 'active', status_reason = NULL, updated_at = NOW() WHERE id = :id")
                    ->execute([':id' => $targetId]);
                logAdminAction($pdo, 'USER_RESTORED', $admin['id'], $admin['name'], 'user', $targetId, "Activated {$targetName}", 'Account activated in good standing');
                echo json_encode(['success' => true, 'message' => "Account #{$targetId} ({$targetName}) is now active.", 'status' => 'active']);
                exit;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action requested.']);
                exit;
        }

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('admin_users POST error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to execute account operation.']);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────
// GET: Query User & Admin Accounts
// ─────────────────────────────────────────────────────────────
$singleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status   = trim((string)($_GET['status'] ?? 'all'));
$search   = trim((string)($_GET['search'] ?? $_GET['q'] ?? ''));
$role     = trim((string)($_GET['role'] ?? ''));
$roleExact = trim((string)($_GET['role_exact'] ?? ''));

try {
    if ($singleId > 0) {
        $stmtUser = $pdo->prepare("
            SELECT id, name, first_name, last_name, email, role,
                   COALESCE(phone, '') AS phone,
                   COALESCE(city, 'Metro Manila') AS city,
                   COALESCE(barangay, '') AS barangay,
                   COALESCE(department, '') AS department,
                   status, status_reason, must_change_password, created_at, last_login_at
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

        $userDonations = [];
        try {
            $stmtDon = $pdo->prepare("SELECT id, title, category, quantity, unit, status, created_at FROM donations WHERE donor_id = :uid ORDER BY created_at DESC LIMIT 20");
            $stmtDon->execute([':uid' => $singleId]);
            $userDonations = $stmtDon->fetchAll();
        } catch (PDOException $e) {}

        $userRequests = [];
        try {
            $stmtReq = $pdo->prepare("
                SELECT r.id, COALESCE(NULLIF(r.item_title, ''), d.title, 'Community Request') AS item_title,
                       r.quantity, r.unit, r.status, r.created_at
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

    $sql = "
        SELECT u.id, u.name, u.first_name, u.last_name, u.email, u.role,
               COALESCE(u.phone, '') AS phone,
               COALESCE(u.city, 'Metro Manila') AS city,
               COALESCE(u.barangay, '') AS barangay,
               COALESCE(u.department, '') AS department,
               u.status, u.must_change_password, u.created_at, u.last_login_at
        FROM users u
        WHERE 1=1
    ";
    $params = [];

    if ($roleExact !== '') {
        $sql .= ' AND u.role = :role_exact';
        $params[':role_exact'] = $roleExact;
    } elseif ($role === 'admin') {
        $sql .= " AND u.role IN ('admin', 'head_admin', 'donation_admin', 'campaign_admin', 'moderator', 'support_admin', 'superadmin')";
    } elseif ($role === 'regular') {
        $sql .= " AND u.role IN ('donor', 'requester')";
    } elseif ($role !== '') {
        $sql .= ' AND u.role = :role';
        $params[':role'] = $role;
    }

    if ($status !== '' && $status !== 'all') {
        $sql .= ' AND u.status = :status';
        $params[':status'] = $status;
    }

    if ($search !== '') {
        $sql .= ' AND (u.name LIKE :s OR u.email LIKE :s OR u.city LIKE :s)';
        $params[':s'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY u.id ASC LIMIT 200';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    $counts = [
        'all'                  => 0,
        'active'               => 0,
        'pending_verification' => 0,
        'suspended'            => 0,
        'banned'               => 0,
    ];
    try {
        $cStmt = $pdo->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_cnt,
                SUM(CASE WHEN status = 'pending_verification' THEN 1 ELSE 0 END) AS pending_cnt,
                SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) AS suspended_cnt,
                SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) AS banned_cnt
            FROM users
        ");
        $cRow = $cStmt->fetch();
        if ($cRow) {
            $counts['all']                  = (int)$cRow['total'];
            $counts['active']               = (int)$cRow['active_cnt'];
            $counts['pending_verification'] = (int)$cRow['pending_cnt'];
            $counts['suspended']            = (int)$cRow['suspended_cnt'];
            $counts['banned']               = (int)$cRow['banned_cnt'];
        }
    } catch (PDOException $e) {}

    echo json_encode([
        'success' => true,
        'users'   => $users,
        'counts'  => $counts,
    ]);
} catch (Throwable $e) {
    error_log('admin_users GET error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load user accounts.']);
}
