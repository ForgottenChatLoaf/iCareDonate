<?php
/**
 * iCareDonate — User & Admin Profile & Account Security CRUD Endpoint
 * actions/user_profile.php
 *
 * GET: Returns authenticated user's safe profile fields (never password_hash).
 * POST: Update personal info (including email uniqueness check), change password
 *       (with current password verification, policy check, session regeneration,
 *       and other-session invalidation), or deactivate own account.
 *       Blocks role/status mass-assignment.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$userId = getAuthenticatedUserId();
if ($userId === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (!icd_verify_csrf_request(is_array($payload) ? $payload : null)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
        exit;
    }

    $action = trim((string)($payload['action'] ?? 'update_profile'));

    try {
        if ($action === 'update_profile') {
            $firstName  = trim((string)($payload['first_name'] ?? ''));
            $lastName   = trim((string)($payload['last_name'] ?? ''));
            $email      = strtolower(trim((string)($payload['email'] ?? '')));
            $phone      = trim((string)($payload['phone'] ?? ''));
            $city       = trim((string)($payload['city'] ?? ''));
            $barangay   = trim((string)($payload['barangay'] ?? ''));
            $department = trim((string)($payload['department'] ?? ''));
            $recOptIn   = isset($payload['recognition_opt_in']) ? ((int)$payload['recognition_opt_in'] ? 1 : 0) : 1;

            $fullName = trim($firstName . ' ' . $lastName);
            if ($fullName === '' || mb_strlen($fullName) < 2 || mb_strlen($fullName) > 150) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Valid first and last name (2–150 characters) are required.']);
                exit;
            }

            if ($email !== '') {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
                    exit;
                }
                $dupStmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) AND id != :uid LIMIT 1');
                $dupStmt->execute([':email' => $email, ':uid' => $userId]);
                if ($dupStmt->fetch()) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'This email address is already associated with another account.']);
                    exit;
                }
            }

            $fields = "name = :name, first_name = :fn, last_name = :ln, phone = :phone, city = :city, barangay = :brgy, department = :dept, recognition_opt_in = :opt, updated_at = NOW()";
            $params = [
                ':name'  => $fullName,
                ':fn'    => $firstName,
                ':ln'    => $lastName,
                ':phone' => $phone !== '' ? $phone : null,
                ':city'  => $city !== '' ? $city : null,
                ':brgy'  => $barangay !== '' ? $barangay : null,
                ':dept'  => $department !== '' ? $department : null,
                ':opt'   => $recOptIn,
                ':uid'   => $userId,
            ];

            if ($email !== '') {
                $fields .= ", email = :email";
                $params[':email'] = $email;
            }

            $pdo->prepare("UPDATE users SET {$fields} WHERE id = :uid")->execute($params);

            $_SESSION['icd_name'] = $fullName;
            if ($email !== '') {
                $_SESSION['icd_email'] = $email;
            }

            $stmtRef = $pdo->prepare("
                SELECT id, name, first_name, last_name, email, phone, role, status, city, barangay, province, department, recognition_opt_in, must_change_password, created_at, last_login_at
                FROM users WHERE id = :uid LIMIT 1
            ");
            $stmtRef->execute([':uid' => $userId]);
            $updated = $stmtRef->fetch();

            echo json_encode([
                'success' => true,
                'message' => 'Profile information updated successfully.',
                'profile' => $updated,
            ]);
            exit;
        }

        if ($action === 'change_password') {
            $curr = (string)($payload['current_password'] ?? '');
            $new  = (string)($payload['new_password'] ?? '');
            $conf = (string)($payload['confirm_password'] ?? '');

            if ($curr === '') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Current password is required.']);
                exit;
            }

            $pwErr = icd_validate_password_policy($new);
            if ($pwErr !== null) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $pwErr]);
                exit;
            }

            if ($new !== $conf) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match.']);
                exit;
            }

            if ($curr === $new) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'New password must be different from your current password.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :uid LIMIT 1");
            $stmt->execute([':uid' => $userId]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($curr, (string)$row['password_hash'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
                exit;
            }

            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = :hash, must_change_password = 0, updated_at = NOW() WHERE id = :uid")
                ->execute([':hash' => $newHash, ':uid' => $userId]);

            $_SESSION['icd_must_change_password'] = false;
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }

            // Invalidate other active sessions while preserving current Bearer token if present
            $keepToken = null;
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
            if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $m)) {
                $keepToken = $m[1];
            }
            icd_invalidate_user_sessions($pdo, $userId, $keepToken);

            echo json_encode([
                'success' => true,
                'message' => 'Password updated securely. Other active sessions have been signed out.',
                'must_change_password' => false,
            ]);
            exit;
        }

        if ($action === 'deactivate_account') {
            $roleStmt = $pdo->prepare("SELECT role FROM users WHERE id = :uid LIMIT 1");
            $roleStmt->execute([':uid' => $userId]);
            $role = (string)$roleStmt->fetchColumn();
            if (in_array($role, ['admin', 'head_admin', 'superadmin'], true)) {
                $headCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'head_admin', 'superadmin') AND status = 'active'")->fetchColumn();
                if ($headCount <= 1) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Cannot deactivate the last active Head Administrator.']);
                    exit;
                }
            }

            $pdo->prepare("UPDATE users SET status = 'suspended', status_reason = 'User self-deactivated', updated_at = NOW() WHERE id = :uid")
                ->execute([':uid' => $userId]);
            icd_invalidate_user_sessions($pdo, $userId, null);
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }

            echo json_encode(['success' => true, 'message' => 'Your account has been deactivated.']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unsupported profile action.']);
        exit;
    } catch (Throwable $e) {
        error_log('user_profile error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error updating profile.']);
        exit;
    }
}

// GET: Safe user profile
try {
    $stmt = $pdo->prepare("
        SELECT id, name, first_name, last_name, email, phone, role, status, city, barangay, province, department, recognition_opt_in, must_change_password, created_at, last_login_at
        FROM users WHERE id = :uid LIMIT 1
    ");
    $stmt->execute([':uid' => $userId]);
    $profile = $stmt->fetch();
    if (!$profile) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }
    echo json_encode(['success' => true, 'profile' => $profile]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load profile.']);
}
