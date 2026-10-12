<?php
declare(strict_types=1);
/**
 * api/v1/admin/categories.php
 * GET    - List all categories (active & inactive)
 * POST   - Create or update category
 * DELETE - Remove category
 */
require_once __DIR__ . '/../bootstrap.php';

$admin = requireAdmin($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query('SELECT id, name, slug, description, icon, sort_order, is_active, created_at, updated_at FROM categories ORDER BY sort_order ASC, name ASC');
        $cats = $stmt->fetchAll();
        jsonOk(['data' => $cats]);
    } catch (Exception $e) {
        jsonError('Failed to fetch categories: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $data = getBody();
    $id = (int)($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $slug = trim($data['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
    $description = trim($data['description'] ?? '');
    $icon = trim($data['icon'] ?? 'tag');
    $sortOrder = (int)($data['sort_order'] ?? 0);
    $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

    if (empty($name)) {
        jsonError('Category name is required.');
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, description = ?, icon = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$name, $slug, $description, $icon, $sortOrder, $isActive, $id]);
            jsonOk(['message' => 'Category updated successfully.']);
        } else {
            $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description, icon, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$name, $slug, $description, $icon, $sortOrder, $isActive]);
            jsonOk(['message' => 'Category created successfully.', 'id' => (int)$pdo->lastInsertId()], 201);
        }
    } catch (Exception $e) {
        jsonError('Failed to save category: ' . $e->getMessage(), 500);
    }
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) jsonError('Valid category ID is required.');

    try {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        jsonOk(['message' => 'Category deleted successfully.']);
    } catch (Exception $e) {
        jsonError('Failed to delete category: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
