<?php
/**
 * iCareDonate — Administrative Category Management Endpoint
 * actions/admin_categories.php
 *
 * GET: Retrieves categories with live item count per category from MySQL.
 * POST: Handles creating, editing, and safe deletion. Checks dependencies
 * before deletion to prevent orphaning live donation records.
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
// POST: Category Management (Create, Update, Delete)
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload  = json_decode($rawInput, true) ?? $_POST;

    $action = trim((string)($payload['action'] ?? 'create'));
    $catId  = (int)($payload['id'] ?? 0);
    $name   = trim((string)($payload['name'] ?? ''));
    $desc   = trim((string)($payload['description'] ?? ''));
    $order  = (int)($payload['sort_order'] ?? 99);
    $active = isset($payload['is_active']) ? (int)(bool)$payload['is_active'] : 1;

    try {
        switch ($action) {
            case 'create':
                if ($name === '' || mb_strlen($name) < 2) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'message' => 'Please provide a category name (at least 2 characters).']);
                    exit;
                }

                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

                $stmt = $pdo->prepare('
                    INSERT INTO categories (name, slug, description, sort_order, is_active, created_at)
                    VALUES (:name, :slug, :desc, :order, :active, NOW())
                ');
                $stmt->execute([
                    ':name'   => $name,
                    ':slug'   => $slug,
                    ':desc'   => $desc,
                    ':order'  => $order,
                    ':active' => $active,
                ]);
                $newId = (int)$pdo->lastInsertId();

                logAdminAction($pdo, 'CATEGORY_CREATED', $admin['id'], $admin['name'], 'category', $newId, "Created category {$name}");

                echo json_encode(['success' => true, 'message' => "Category \"{$name}\" created successfully.", 'id' => $newId]);
                exit;

            case 'update':
                if ($catId <= 0 || $name === '') {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'message' => 'Valid ID and category name are required.']);
                    exit;
                }

                $stmt = $pdo->prepare('UPDATE categories SET name = :name, description = :desc, sort_order = :order, is_active = :active WHERE id = :id');
                $stmt->execute([
                    ':name'   => $name,
                    ':desc'   => $desc,
                    ':order'  => $order,
                    ':active' => $active,
                    ':id'     => $catId
                ]);

                logAdminAction($pdo, 'CATEGORY_UPDATED', $admin['id'], $admin['name'], 'category', $catId, "Updated category {$name}");

                echo json_encode(['success' => true, 'message' => "Category \"{$name}\" updated successfully."]);
                exit;

            case 'delete':
                if ($catId <= 0) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'message' => 'Valid category ID required.']);
                    exit;
                }

                // Retrieve category slug
                $stmtSlug = $pdo->prepare('SELECT name, slug FROM categories WHERE id = :id');
                $stmtSlug->execute([':id' => $catId]);
                $cat = $stmtSlug->fetch();

                if (!$cat) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Category not found.']);
                    exit;
                }

                // Check dependencies in donations table
                $depStmt = $pdo->prepare('SELECT COUNT(*) FROM donations WHERE category = :slug OR category = :name');
                $depStmt->execute([':slug' => $cat['slug'], ':name' => $cat['name']]);
                $depCount = (int)$depStmt->fetchColumn();

                if ($depCount > 0) {
                    http_response_code(409);
                    echo json_encode([
                        'success' => false,
                        'message' => "Cannot delete \"{$cat['name']}\" because {$depCount} active donation listing(s) are categorized under it. Please deactivate the category instead, or re-assign the items first."
                    ]);
                    exit;
                }

                $delStmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
                $delStmt->execute([':id' => $catId]);

                logAdminAction($pdo, 'CATEGORY_DELETED', $admin['id'], $admin['name'], 'category', $catId, "Deleted category {$cat['name']}");

                echo json_encode(['success' => true, 'message' => "Category \"{$cat['name']}\" deleted successfully."]);
                exit;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Unsupported category action.']);
                exit;
        }

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to process category request.']);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────
// GET: Query Master Categories & Real Listing Counts
// ─────────────────────────────────────────────────────────────
try {
    $hasTable = false;
    try {
        $c = $pdo->query("SHOW TABLES LIKE 'categories'");
        $hasTable = ($c && $c->rowCount() > 0);
    } catch (PDOException $e) {}

    $categories = [];

    if ($hasTable) {
        $stmt = $pdo->query('
            SELECT c.id, c.name, c.slug, c.description, c.sort_order, c.is_active,
                   COUNT(d.id) AS donation_count
            FROM categories c
            LEFT JOIN donations d ON (d.category = c.slug OR d.category = c.name)
            GROUP BY c.id, c.name, c.slug, c.description, c.sort_order, c.is_active
            ORDER BY c.sort_order ASC, c.name ASC
        ');
        $categories = $stmt->fetchAll();
    } else {
        // Fallback aggregation from distinct donations categories
        $stmt = $pdo->query("
            SELECT category AS name, category AS slug, '' AS description, 1 AS sort_order, 1 AS is_active,
                   COUNT(*) AS donation_count
            FROM donations
            GROUP BY category
        ");
        $categories = $stmt->fetchAll();
    }

    echo json_encode([
        'success'    => true,
        'categories' => $categories,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve categories.']);
}
