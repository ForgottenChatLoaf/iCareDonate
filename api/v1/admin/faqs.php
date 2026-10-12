<?php
declare(strict_types=1);
/**
 * api/v1/admin/faqs.php
 * GET    - List FAQs
 * POST   - Create or update FAQ
 * DELETE - Remove FAQ
 */
require_once __DIR__ . '/../bootstrap.php';

$admin = requireAdmin($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query('SELECT id, question, answer, category, sort_order, is_active, created_at, updated_at FROM faqs ORDER BY sort_order ASC, id ASC');
        $faqs = $stmt->fetchAll();
        jsonOk(['data' => $faqs]);
    } catch (Exception $e) {
        jsonError('Failed to fetch FAQs: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $data = getBody();
    $id = (int)($data['id'] ?? 0);
    $question = trim($data['question'] ?? '');
    $answer = trim($data['answer'] ?? '');
    $category = trim($data['category'] ?? 'General');
    $sortOrder = (int)($data['sort_order'] ?? 0);
    $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

    if (empty($question) || empty($answer)) {
        jsonError('Question and answer are required.');
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE faqs SET question = ?, answer = ?, category = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$question, $answer, $category, $sortOrder, $isActive, $id]);
            jsonOk(['message' => 'FAQ updated successfully.']);
        } else {
            $stmt = $pdo->prepare('INSERT INTO faqs (question, answer, category, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$question, $answer, $category, $sortOrder, $isActive]);
            jsonOk(['message' => 'FAQ created successfully.', 'id' => (int)$pdo->lastInsertId()], 201);
        }
    } catch (Exception $e) {
        jsonError('Failed to save FAQ: ' . $e->getMessage(), 500);
    }
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) jsonError('Valid FAQ ID is required.');

    try {
        $stmt = $pdo->prepare('DELETE FROM faqs WHERE id = ?');
        $stmt->execute([$id]);
        jsonOk(['message' => 'FAQ deleted successfully.']);
    } catch (Exception $e) {
        jsonError('Failed to delete FAQ: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
