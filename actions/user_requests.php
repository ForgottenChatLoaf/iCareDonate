<?php
/**
 * iCareDonate — User Assistance Request Self-Management CRUD Endpoint
 * actions/user_requests.php
 *
 * GET: Lists authenticated requester's own requests with allocation progress (privacy-safe).
 * POST: Update open request details or cancel an eligible request (ownership verified).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$userId = getAuthenticatedUserId();
if ($userId === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in to manage your requests.']);
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

    $action = trim((string)($payload['action'] ?? ''));
    $reqId  = (int)($payload['id'] ?? 0);

    if ($reqId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Valid request ID is required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = :id AND requester_id = :uid LIMIT 1");
        $stmt->execute([':id' => $reqId, ':uid' => $userId]);
        $req = $stmt->fetch();

        if (!$req) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Assistance request not found or unauthorized.']);
            exit;
        }

        if ($action === 'update') {
            if (!in_array($req['status'], ['pending', 'under_review', 'approved'], true)) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Only pending, under-review, or approved requests can be edited.']);
                exit;
            }
            $itemTitle = trim((string)($payload['item_title'] ?? ($req['item_title'] ?? '')));
            $category  = trim((string)($payload['category'] ?? ($req['category'] ?? '')));
            $message   = trim((string)($payload['reason'] ?? ($payload['message'] ?? $req['message'])));
            $quantity  = (int)($payload['quantity'] ?? $req['quantity']);
            $urgency   = trim((string)($payload['urgency'] ?? ($req['urgency'] ?? 'Medium')));
            $city      = trim((string)($payload['city'] ?? ($req['city'] ?? '')));
            $barangay  = trim((string)($payload['barangay'] ?? ($req['barangay'] ?? '')));
            $address   = trim((string)($payload['complete_address'] ?? ($req['complete_address'] ?? '')));
            $fulfilled = (int)($req['fulfilled_quantity'] ?? 0);

            if (!in_array($urgency, ['Low', 'Medium', 'High', 'Critical'], true)) {
                $urgency = $req['urgency'] ?? 'Medium';
            }
            if (mb_strlen($message) < 10) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Description must be at least 10 characters.']);
                exit;
            }
            if ($quantity < max(1, $fulfilled) || $quantity > 10000) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Quantity must be at least ' . max(1, $fulfilled) . '.']);
                exit;
            }

            $newStatus = $req['status'];
            if ($req['status'] === 'approved' && (
                $itemTitle !== ($req['item_title'] ?? '') ||
                $category !== ($req['category'] ?? '') ||
                $quantity !== (int)$req['quantity']
            )) {
                $newStatus = 'pending';
            }

            $pdo->prepare("
                UPDATE requests
                SET item_title = :title, category = :cat, message = :msg, quantity = :qty,
                    urgency = :urg, city = :city, barangay = :brgy, complete_address = :addr,
                    status = :st, updated_at = NOW()
                WHERE id = :id AND requester_id = :uid
            ")->execute([
                ':title' => $itemTitle !== '' ? $itemTitle : $req['item_title'],
                ':cat'   => $category !== '' ? $category : $req['category'],
                ':msg'   => $message,
                ':qty'   => $quantity,
                ':urg'   => $urgency,
                ':city'  => $city !== '' ? $city : $req['city'],
                ':brgy'  => $barangay !== '' ? $barangay : $req['barangay'],
                ':addr'  => $address !== '' ? $address : $req['complete_address'],
                ':st'    => $newStatus,
                ':id'    => $reqId,
                ':uid'   => $userId,
            ]);

            $msgOut = ($newStatus === 'pending' && $req['status'] === 'approved')
                ? 'Assistance request updated and returned to review because core requirements changed.'
                : 'Assistance request updated successfully.';
            echo json_encode(['success' => true, 'message' => $msgOut, 'status' => $newStatus]);
            exit;
        }

        if ($action === 'cancel') {
            if (in_array($req['status'], ['completed', 'cancelled', 'rejected'], true)) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'This request cannot be cancelled in its current state.']);
                exit;
            }
            // Check for in-progress handovers
            $activeAlloc = (int)$pdo->query("SELECT COUNT(*) FROM donation_allocations WHERE request_id = {$reqId} AND status IN ('preparing','ready_for_handover','completed')")->fetchColumn();
            if ($activeAlloc > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Cannot cancel a request with an active or completed handover allocation.']);
                exit;
            }

            $pdo->prepare("UPDATE requests SET status = 'cancelled', updated_at = NOW() WHERE id = :id AND requester_id = :uid")
                ->execute([':id' => $reqId, ':uid' => $userId]);

            echo json_encode(['success' => true, 'message' => 'Assistance request cancelled.']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unsupported request action.']);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error updating request.']);
        exit;
    }
}

// GET: List user's own requests
try {
    $stmt = $pdo->prepare("
        SELECT r.id, r.donation_id, COALESCE(NULLIF(r.item_title, ''), d.title, 'Assistance Request') AS item_title,
               r.category, r.message, r.quantity, r.fulfilled_quantity, r.unit, r.urgency, r.proof_type,
               r.status, r.rejection_reason, r.created_at, r.updated_at
        FROM requests r
        LEFT JOIN donations d ON r.donation_id = d.id
        WHERE r.requester_id = :uid
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $requests = $stmt->fetchAll();

    echo json_encode(['success' => true, 'requests' => $requests]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load requests.']);
}
