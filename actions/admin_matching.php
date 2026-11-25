<?php
/**
 * iCareDonate — Resource Matching & Allocation Lifecycle Endpoint
 * actions/admin_matching.php
 *
 * GET: Computes live match recommendations, summary KPIs, and allocation history.
 * POST: Approves allocations (with SELECT ... FOR UPDATE concurrency protection),
 *       rejects recommendations, and transitions allocation statuses cleanly.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/matching_engine.php';
require_once __DIR__ . '/../includes/achievements.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Database connection unavailable.', 'code' => 'DB_UNAVAILABLE']);
    exit;
}

$admin = requireAdminRoleApi($pdo, ['donation_admin']);

// ─────────────────────────────────────────────────────────────
// POST: Allocation Decisions & Lifecycle Transitions
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload  = json_decode($rawInput, true) ?? $_POST;

    $action = trim((string)($payload['action'] ?? ''));

    // 1. Approve a recommended match -> Create Allocation
    if ($action === 'approve_allocation') {
        $donationId = (int)($payload['donation_id'] ?? 0);
        $requestId  = (int)($payload['request_id'] ?? 0);
        $allocQty   = (int)($payload['allocated_quantity'] ?? 0);
        $notes      = trim((string)($payload['notes'] ?? ''));

        if ($donationId <= 0 || $requestId <= 0 || $allocQty <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Valid donation_id, request_id, and positive allocated_quantity are required.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Lock donation and request rows to prevent concurrent over-allocation
            $stmtDon = $pdo->prepare("SELECT * FROM donations WHERE id = :id FOR UPDATE");
            $stmtDon->execute([':id' => $donationId]);
            $don = $stmtDon->fetch();

            $stmtReq = $pdo->prepare("SELECT * FROM requests WHERE id = :id FOR UPDATE");
            $stmtReq->execute([':id' => $requestId]);
            $req = $stmtReq->fetch();

            if (!$don || !$req) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Donation or assistance request not found.']);
                exit;
            }

            if ($don['status'] !== 'approved') {
                $pdo->rollBack();
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Only approved donations can be allocated.']);
                exit;
            }

            if (!in_array($req['status'], ['pending', 'under_review', 'approved'], true)) {
                $pdo->rollBack();
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Request is not in an open status for allocation.']);
                exit;
            }

            // Recompute match and true remaining quantities server-side
            $donRemaining = max(0, (int)$don['quantity'] - (int)$don['allocated_quantity']);
            $reqRemaining = max(0, (int)$req['quantity'] - (int)$req['fulfilled_quantity']);

            if ($allocQty > $donRemaining) {
                $pdo->rollBack();
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => "Cannot allocate {$allocQty}: only {$donRemaining} remaining on this donation."]);
                exit;
            }

            if ($allocQty > $reqRemaining) {
                $pdo->rollBack();
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => "Cannot allocate {$allocQty}: request only needs {$reqRemaining} more."]);
                exit;
            }

            $eval = icd_evaluate_match_pair($don, $req);
            $score = $eval ? (float)$eval['match_score'] : 50.00;
            $classification = $eval ? $eval['classification'] : 'Manual';
            $explanation = $eval ? $eval['explanation'] : 'Manual administrator allocation';

            // Insert allocation
            $stmtIns = $pdo->prepare("
                INSERT INTO donation_allocations (
                    donation_id, request_id, allocated_quantity, match_score,
                    match_classification, match_explanation, status, approved_by, notes, created_at
                ) VALUES (
                    :did, :rid, :qty, :score, :cls, :exp, 'approved', :aid, :notes, NOW()
                )
            ");
            $stmtIns->execute([
                ':did'   => $donationId,
                ':rid'   => $requestId,
                ':qty'   => $allocQty,
                ':score' => $score,
                ':cls'   => $classification,
                ':exp'   => $explanation,
                ':aid'   => $admin['id'],
                ':notes' => $notes ?: null,
            ]);
            $allocId = (int)$pdo->lastInsertId();

            // Reserve quantity on donation
            $newAllocated = (int)$don['allocated_quantity'] + $allocQty;
            $newDonStatus = ($newAllocated >= (int)$don['quantity']) ? 'claimed' : 'approved';
            $pdo->prepare("UPDATE donations SET allocated_quantity = :alloc, status = :st, updated_at = NOW() WHERE id = :id")
                ->execute([':alloc' => $newAllocated, ':st' => $newDonStatus, ':id' => $donationId]);

            // If request was pending/under_review, mark approved
            if (in_array($req['status'], ['pending', 'under_review'], true)) {
                $pdo->prepare("UPDATE requests SET status = 'approved', updated_at = NOW() WHERE id = :id")
                    ->execute([':id' => $requestId]);
            }

            // Log match transition & audit log
            $pdo->prepare("
                INSERT INTO resource_match_logs (allocation_id, donation_id, request_id, action, actor_id, previous_status, new_status, details)
                VALUES (:aid, :did, :rid, 'ALLOCATION_APPROVED', :actor, 'suggested', 'approved', :det)
            ")->execute([
                ':aid'   => $allocId,
                ':did'   => $donationId,
                ':rid'   => $requestId,
                ':actor' => $admin['id'],
                ':det'   => "Allocated {$allocQty} unit(s) (Score: {$score})",
            ]);

            logAdminAction($pdo, 'ALLOCATION_APPROVED', $admin['id'], $admin['name'], 'allocation', $allocId, "Allocated {$allocQty} of donation #{$donationId} to request #{$requestId}", $notes ?: 'Match approved');

            // Notify donor & requester
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, related_type, related_id, is_read, created_at) VALUES (:uid, :title, :msg, 'match', 'allocation', :rid, 0, NOW())");
            $notifStmt->execute([
                ':uid'   => (int)$don['donor_id'],
                ':title' => 'Donation Matched & Allocated',
                ':msg'   => "{$allocQty} unit(s) of your donation \"{$don['title']}\" have been matched to an approved community request.",
                ':rid'   => $allocId,
            ]);
            $notifStmt->execute([
                ':uid'   => (int)$req['requester_id'],
                ':title' => 'Assistance Match Approved',
                ':msg'   => "Your request has been matched with \"{$don['title']}\" ({$allocQty} unit(s) allocated). Our team will coordinate handover.",
                ':rid'   => $allocId,
            ]);

            $pdo->commit();

            echo json_encode([
                'success'       => true,
                'allocation_id' => $allocId,
                'message'       => "Allocation #{$allocId} approved ({$allocQty} unit(s) reserved).",
            ]);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Allocation approval error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to approve allocation.']);
            exit;
        }
    }

    // 2. Reject a recommended match pair
    if ($action === 'reject_recommendation') {
        $donationId = (int)($payload['donation_id'] ?? 0);
        $requestId  = (int)($payload['request_id'] ?? 0);
        $reason     = trim((string)($payload['reason'] ?? 'Unsuitable match per administrator review.'));

        if ($donationId <= 0 || $requestId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Valid donation_id and request_id are required.']);
            exit;
        }

        try {
            $pdo->beginTransaction();
            $stmtIns = $pdo->prepare("
                INSERT INTO donation_allocations (
                    donation_id, request_id, allocated_quantity, match_score,
                    match_classification, match_explanation, status, approved_by, rejection_reason, created_at
                ) VALUES (
                    :did, :rid, 0, 0, 'Manual', 'Rejected by administrator', 'rejected', :aid, :reason, NOW()
                )
            ");
            $stmtIns->execute([
                ':did'    => $donationId,
                ':rid'    => $requestId,
                ':aid'    => $admin['id'],
                ':reason' => $reason,
            ]);
            $allocId = (int)$pdo->lastInsertId();

            $pdo->prepare("
                INSERT INTO resource_match_logs (allocation_id, donation_id, request_id, action, actor_id, previous_status, new_status, details)
                VALUES (:aid, :did, :rid, 'RECOMMENDATION_REJECTED', :actor, 'suggested', 'rejected', :det)
            ")->execute([
                ':aid'   => $allocId,
                ':did'   => $donationId,
                ':rid'   => $requestId,
                ':actor' => $admin['id'],
                ':det'   => $reason,
            ]);

            logAdminAction($pdo, 'MATCH_REJECTED', $admin['id'], $admin['name'], 'allocation', $allocId, "Rejected match between donation #{$donationId} and request #{$requestId}", $reason);
            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Match recommendation dismissed.']);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to reject recommendation.']);
            exit;
        }
    }

    // 3. Transition Allocation Status (preparing -> ready_for_handover -> completed | cancelled)
    if ($action === 'update_status') {
        $allocId   = (int)($payload['allocation_id'] ?? 0);
        $newStatus = trim((string)($payload['status'] ?? ''));
        $reason    = trim((string)($payload['reason'] ?? ''));

        $allowedTransitions = [
            'approved'           => ['preparing', 'ready_for_handover', 'completed', 'cancelled'],
            'preparing'          => ['ready_for_handover', 'completed', 'cancelled'],
            'ready_for_handover' => ['completed', 'cancelled'],
        ];

        if ($allocId <= 0 || $newStatus === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'allocation_id and target status are required.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmtAlloc = $pdo->prepare("SELECT * FROM donation_allocations WHERE id = :id FOR UPDATE");
            $stmtAlloc->execute([':id' => $allocId]);
            $alloc = $stmtAlloc->fetch();

            if (!$alloc) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Allocation record not found.']);
                exit;
            }

            $prevStatus = (string)$alloc['status'];
            if (!isset($allowedTransitions[$prevStatus]) || !in_array($newStatus, $allowedTransitions[$prevStatus], true)) {
                $pdo->rollBack();
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => "Invalid status transition from '{$prevStatus}' to '{$newStatus}'."]);
                exit;
            }

            $donStmt = $pdo->prepare("SELECT * FROM donations WHERE id = :id FOR UPDATE");
            $donStmt->execute([':id' => (int)$alloc['donation_id']]);
            $don = $donStmt->fetch();

            $reqStmt = $pdo->prepare("SELECT * FROM requests WHERE id = :id FOR UPDATE");
            $reqStmt->execute([':id' => (int)$alloc['request_id']]);
            $req = $reqStmt->fetch();

            $allocQty = (int)$alloc['allocated_quantity'];

            if ($newStatus === 'completed') {
                $pdo->prepare("UPDATE donation_allocations SET status = 'completed', completed_at = NOW(), updated_at = NOW() WHERE id = :id")
                    ->execute([':id' => $allocId]);

                if ($req) {
                    $newFulfilled = min((int)$req['quantity'], (int)$req['fulfilled_quantity'] + $allocQty);
                    $reqStatus = ($newFulfilled >= (int)$req['quantity']) ? 'completed' : 'approved';
                    $pdo->prepare("UPDATE requests SET fulfilled_quantity = :fq, status = :st, updated_at = NOW() WHERE id = :id")
                        ->execute([':fq' => $newFulfilled, ':st' => $reqStatus, ':id' => (int)$req['id']]);
                }

                if ($don) {
                    // Check if all allocations for this donation are completed and full quantity allocated
                    $pendingAllocCnt = (int)$pdo->query("SELECT COUNT(*) FROM donation_allocations WHERE donation_id = " . (int)$don['id'] . " AND status IN ('approved','preparing','ready_for_handover')")->fetchColumn();
                    if ((int)$don['allocated_quantity'] >= (int)$don['quantity'] && $pendingAllocCnt === 0) {
                        $pdo->prepare("UPDATE donations SET status = 'completed', updated_at = NOW() WHERE id = :id")
                            ->execute([':id' => (int)$don['id']]);
                    }
                }

                // Notify donor & requester of completion
                if ($don && $req) {
                    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, related_type, related_id, is_read, created_at) VALUES (:uid, :title, :msg, 'handover', 'allocation', :rid, 0, NOW())");
                    $notifStmt->execute([
                        ':uid'   => (int)$don['donor_id'],
                        ':title' => 'Donation Handover Completed',
                        ':msg'   => "Thank you! {$allocQty} unit(s) of \"{$don['title']}\" have been handed over and verified.",
                        ':rid'   => $allocId,
                    ]);
                    $notifStmt->execute([
                        ':uid'   => (int)$req['requester_id'],
                        ':title' => 'Assistance Handover Completed',
                        ':msg'   => "Your allocated assistance ({$allocQty} unit(s) of \"{$don['title']}\") has been marked completed.",
                        ':rid'   => $allocId,
                    ]);
                }
            } elseif ($newStatus === 'cancelled') {
                $pdo->prepare("UPDATE donation_allocations SET status = 'cancelled', rejection_reason = :reason, updated_at = NOW() WHERE id = :id")
                    ->execute([':reason' => $reason ?: 'Cancelled by administrator', ':id' => $allocId]);

                // Release reserved quantity back to donation
                if ($don) {
                    $newAlloc = max(0, (int)$don['allocated_quantity'] - $allocQty);
                    $donStatus = ($don['status'] === 'claimed' && $newAlloc < (int)$don['quantity']) ? 'approved' : $don['status'];
                    $pdo->prepare("UPDATE donations SET allocated_quantity = :alloc, status = :st, updated_at = NOW() WHERE id = :id")
                        ->execute([':alloc' => $newAlloc, ':st' => $donStatus, ':id' => (int)$don['id']]);
                }
            } else {
                $pdo->prepare("UPDATE donation_allocations SET status = :st, updated_at = NOW() WHERE id = :id")
                    ->execute([':st' => $newStatus, ':id' => $allocId]);
            }

            $pdo->prepare("
                INSERT INTO resource_match_logs (allocation_id, donation_id, request_id, action, actor_id, previous_status, new_status, details)
                VALUES (:aid, :did, :rid, 'ALLOCATION_STATUS_UPDATED', :actor, :prev, :next, :det)
            ")->execute([
                ':aid'   => $allocId,
                ':did'   => (int)$alloc['donation_id'],
                ':rid'   => (int)$alloc['request_id'],
                ':actor' => $admin['id'],
                ':prev'  => $prevStatus,
                ':next'  => $newStatus,
                ':det'   => $reason ?: "Status updated to {$newStatus}",
            ]);

            logAdminAction($pdo, 'ALLOCATION_STATUS_UPDATED', $admin['id'], $admin['name'], 'allocation', $allocId, "Allocation #{$allocId}: {$prevStatus} -> {$newStatus}", $reason ?: null);

            $pdo->commit();

            // Award verified achievements after transaction commits
            if ($newStatus === 'completed' && $don) {
                icd_evaluate_user_achievements($pdo, (int)$don['donor_id']);
            }

            echo json_encode([
                'success'    => true,
                'message'    => "Allocation #{$allocId} updated to {$newStatus}.",
                'new_status' => $newStatus,
            ]);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Allocation status update error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update allocation status.']);
            exit;
        }
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unsupported matching action.']);
    exit;
}

// ─────────────────────────────────────────────────────────────
// GET: Live Matching Results & Allocation History
// ─────────────────────────────────────────────────────────────
try {
    $filters = [
        'category'       => trim((string)($_GET['category'] ?? '')),
        'urgency'        => trim((string)($_GET['urgency'] ?? '')),
        'location'       => trim((string)($_GET['location'] ?? '')),
        'classification' => trim((string)($_GET['classification'] ?? '')),
        'search'         => trim((string)($_GET['search'] ?? '')),
    ];

    $computed = icd_compute_live_matches($pdo, $filters);

    // Fetch existing allocations (privacy-safe: no private addresses/emails/phones)
    $allocStmt = $pdo->query("
        SELECT da.id, da.donation_id, da.request_id, da.allocated_quantity,
               da.match_score, da.match_classification, da.match_explanation,
               da.status, da.rejection_reason, da.completed_at, da.created_at, da.updated_at,
               d.title AS donation_title, d.category AS donation_category, d.unit AS donation_unit,
               d.barangay AS donation_barangay, d.city AS donation_city,
               COALESCE(NULLIF(r.item_title, ''), d.title, 'Assistance Request') AS request_title,
               r.urgency AS request_urgency, r.barangay AS request_barangay, r.city AS request_city,
               donor.name AS donor_name, req_user.name AS requester_name,
               approver.name AS approved_by_name
        FROM donation_allocations da
        INNER JOIN donations d ON da.donation_id = d.id
        INNER JOIN requests r ON da.request_id = r.id
        LEFT JOIN users donor ON d.donor_id = donor.id
        LEFT JOIN users req_user ON r.requester_id = req_user.id
        LEFT JOIN users approver ON da.approved_by = approver.id
        ORDER BY da.updated_at DESC
        LIMIT 100
    ");
    $allocations = $allocStmt->fetchAll();

    $computed['summary']['recent_allocations_count'] = count(array_filter(
        $allocations,
        static fn(array $a): bool => $a['status'] !== 'rejected'
    ));

    echo json_encode([
        'success'     => true,
        'summary'     => $computed['summary'],
        'matches'     => $computed['matches'],
        'allocations' => $allocations,
    ]);
} catch (Throwable $e) {
    error_log('Matching GET error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to compute resource matches.']);
}
