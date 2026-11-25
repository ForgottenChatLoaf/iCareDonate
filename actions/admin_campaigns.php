<?php
/**
 * iCareDonate — Campaign Management CRUD Endpoint
 * actions/admin_campaigns.php
 *
 * GET: Lists campaigns with live verified donation progress (public/admin).
 * POST: Create, update, status transition, and archive campaigns
 *       (restricted to campaign_admin and head_admin/admin/superadmin).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin = requireAdminRoleApi($pdo, ['campaign_admin']);
    $payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = trim((string)($payload['action'] ?? ''));

    try {
        if ($action === 'create') {
            $title       = trim((string)($payload['title'] ?? ''));
            $description = trim((string)($payload['description'] ?? ''));
            $category    = trim((string)($payload['category'] ?? ''));
            $targetQty   = max(1, (int)($payload['target_quantity'] ?? 100));
            $targetUnit  = trim((string)($payload['target_unit'] ?? 'items')) ?: 'items';
            $startDate   = trim((string)($payload['start_date'] ?? ''));
            $endDate     = trim((string)($payload['end_date'] ?? ''));
            $status      = trim((string)($payload['status'] ?? 'active'));

            if ($title === '' || mb_strlen($title) < 3) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Campaign title must be at least 3 characters.']);
                exit;
            }
            if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'End date must be on or after start date.']);
                exit;
            }
            if (!in_array($status, ['draft', 'active'], true)) {
                $status = 'active';
            }

            $slugBase = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)) ?? '', '-');
            $slug = $slugBase . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

            $catId = null;
            if ($category !== '') {
                $cStmt = $pdo->prepare("SELECT id FROM categories WHERE slug = :c OR name = :c LIMIT 1");
                $cStmt->execute([':c' => $category]);
                $catId = $cStmt->fetchColumn() ?: null;
            }

            $stmt = $pdo->prepare("
                INSERT INTO campaigns (title, slug, description, category_id, category, target_quantity, target_unit, start_date, end_date, status, created_by, created_at)
                VALUES (:title, :slug, :desc, :cid, :cat, :tq, :tu, :sd, :ed, :st, :uid, NOW())
            ");
            $stmt->execute([
                ':title' => $title,
                ':slug'  => $slug,
                ':desc'  => $description ?: null,
                ':cid'   => $catId ? (int)$catId : null,
                ':cat'   => $category ?: null,
                ':tq'    => $targetQty,
                ':tu'    => $targetUnit,
                ':sd'    => $startDate !== '' ? $startDate : null,
                ':ed'    => $endDate !== '' ? $endDate : null,
                ':st'    => $status,
                ':uid'   => $admin['id'],
            ]);
            $newId = (int)$pdo->lastInsertId();

            logAdminAction($pdo, 'CAMPAIGN_CREATED', $admin['id'], $admin['name'], 'campaign', $newId, "Created campaign: {$title}");
            http_response_code(201);
            echo json_encode(['success' => true, 'id' => $newId, 'message' => "Campaign \"{$title}\" created."]);
            exit;
        }

        if ($action === 'update') {
            $id = (int)($payload['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Valid campaign ID required.']);
                exit;
            }
            $chk = $pdo->prepare("SELECT * FROM campaigns WHERE id = :id LIMIT 1");
            $chk->execute([':id' => $id]);
            $existing = $chk->fetch();
            if (!$existing) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Campaign not found.']);
                exit;
            }

            $title       = trim((string)($payload['title'] ?? $existing['title']));
            $description = trim((string)($payload['description'] ?? (string)$existing['description']));
            $targetQty   = max(1, (int)($payload['target_quantity'] ?? $existing['target_quantity']));
            $startDate   = trim((string)($payload['start_date'] ?? (string)$existing['start_date']));
            $endDate     = trim((string)($payload['end_date'] ?? (string)$existing['end_date']));
            $status      = trim((string)($payload['status'] ?? $existing['status']));

            if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'End date cannot precede start date.']);
                exit;
            }
            if (!in_array($status, ['draft', 'active', 'completed', 'cancelled', 'archived'], true)) {
                $status = $existing['status'];
            }

            $pdo->prepare("
                UPDATE campaigns
                SET title = :t, description = :d, target_quantity = :tq, start_date = :sd, end_date = :ed, status = :st, updated_at = NOW()
                WHERE id = :id
            ")->execute([
                ':t'  => $title,
                ':d'  => $description ?: null,
                ':tq' => $targetQty,
                ':sd' => $startDate !== '' ? $startDate : null,
                ':ed' => $endDate !== '' ? $endDate : null,
                ':st' => $status,
                ':id' => $id,
            ]);

            logAdminAction($pdo, 'CAMPAIGN_UPDATED', $admin['id'], $admin['name'], 'campaign', $id, "Updated campaign #{$id} ({$title})");
            echo json_encode(['success' => true, 'message' => 'Campaign updated successfully.']);
            exit;
        }

        if ($action === 'archive') {
            $id = (int)($payload['id'] ?? 0);
            $chk = $pdo->prepare("SELECT id, title FROM campaigns WHERE id = :id LIMIT 1");
            $chk->execute([':id' => $id]);
            $c = $chk->fetch();
            if (!$c) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Campaign not found.']);
                exit;
            }
            $pdo->prepare("UPDATE campaigns SET status = 'archived', updated_at = NOW() WHERE id = :id")->execute([':id' => $id]);
            logAdminAction($pdo, 'CAMPAIGN_ARCHIVED', $admin['id'], $admin['name'], 'campaign', $id, "Archived campaign: {$c['title']}");
            echo json_encode(['success' => true, 'message' => 'Campaign archived.']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid campaign action.']);
        exit;
    } catch (Throwable $e) {
        error_log('Campaign CRUD error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error processing campaign.']);
        exit;
    }
}

// GET: List campaigns with real verified donation progress
try {
    $onlyActive = isset($_GET['active']) && $_GET['active'] === '1';
    $sql = "
        SELECT c.*,
               COALESCE((
                   SELECT SUM(d.quantity)
                   FROM donations d
                   WHERE d.campaign_id = c.id AND d.status IN ('approved', 'claimed', 'completed')
               ), 0) AS verified_quantity
        FROM campaigns c
    ";
    if ($onlyActive) {
        $sql .= " WHERE c.status = 'active'";
    }
    $sql .= " ORDER BY c.created_at DESC";
    $rows = $pdo->query($sql)->fetchAll();

    foreach ($rows as &$r) {
        $tq = max(1, (int)$r['target_quantity']);
        $vq = (int)$r['verified_quantity'];
        $r['progress_percent'] = min(100, (int)round(($vq / $tq) * 100));
    }
    unset($r);

    echo json_encode(['success' => true, 'campaigns' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load campaigns.']);
}
