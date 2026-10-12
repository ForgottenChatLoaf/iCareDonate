<?php
declare(strict_types=1);
/**
 * api/v1/categories.php
 * GET  - List all active categories
 * POST - Create a new category (admin only)
 */
require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query('SELECT id, name, slug, description, icon, sort_order, is_active FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC');
        $categories = $stmt->fetchAll();
        jsonOk(['data' => $categories]);
    } catch (Exception $e) {
        jsonError('Failed to fetch categories: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $user = requireAdmin($pdo);
    $data = getBody();
    
    $name = trim($data['name'] ?? '');
    if (empty($name)) {
        jsonError('Category name is required.');
    }
    
    $slug = trim($data['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
    $description = trim($data['description'] ?? '');
    $icon = trim($data['icon'] ?? 'tag');
    $sortOrder = (int)($data['sort_order'] ?? 0);
    $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
    
    try {
        $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description, icon, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$name, $slug, $description, $icon, $sortOrder, $isActive]);
        jsonOk(['message' => 'Category created successfully.', 'id' => (int)$pdo->lastInsertId()], 201);
    } catch (Exception $e) {
        jsonError('Failed to create category: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
