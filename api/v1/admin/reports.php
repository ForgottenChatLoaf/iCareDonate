<?php
declare(strict_types=1);
/**
 * api/v1/admin/reports.php
 * GET  - List problem reports
 * POST - Resolve or dismiss report
 */
require_once __DIR__ . '/../bootstrap.php';

$admin = requireAdmin($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $sql = '
            SELECT pr.id, pr.target_type, pr.target_id, pr.reason, pr.description,
                   pr.status, pr.admin_notes, pr.resolved_at, pr.created_at,
                   CONCAT(u.first_name, " ", u.last_name) AS reporter,
                   u.email AS reporter_email
            FROM problem_reports pr
            LEFT JOIN users u ON pr.reporter_id = u.id
            ORDER BY pr.created_at DESC
        ';
        $stmt = $pdo->query($sql);
        $reports = $stmt->fetchAll();

        jsonOk(['data' => $reports]);
    } catch (Exception $e) {
        jsonError('Failed to fetch reports: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $data = getBody();
    $id = (int)($data['id'] ?? 0);
    $status = trim($data['status'] ?? 'resolved'); // resolved, dismissed
    $notes = trim($data['admin_notes'] ?? ($data['notes'] ?? ''));

    if ($id <= 0) {
        jsonError('Report ID is required.');
    }

    try {
        $stmt = $pdo->prepare('
            UPDATE problem_reports SET
                status = ?,
                admin_notes = ?,
                reviewed_by = ?,
                resolved_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $status,
            $notes,
            $admin['id'],
            $id
        ]);

        jsonOk(['message' => "Report marked as {$status}."]);
    } catch (Exception $e) {
        jsonError('Failed to update report: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
