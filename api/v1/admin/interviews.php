<?php
declare(strict_types=1);
/**
 * api/v1/admin/interviews.php
 * GET  - List scheduled interviews
 * POST - Schedule or update interview
 */
require_once __DIR__ . '/../bootstrap.php';

$admin = requireAdmin($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $sql = '
            SELECT i.id, i.request_id, i.requester_id, i.scheduled_date,
                   i.scheduled_time, i.method, i.meeting_link_or_venue,
                   i.result, i.admin_notes, i.created_at,
                   CONCAT(u.first_name, " ", u.last_name) AS requester,
                   u.email AS requester_email,
                   u.phone AS requester_phone
            FROM interviews i
            LEFT JOIN users u ON i.requester_id = u.id
            ORDER BY i.scheduled_date DESC, i.scheduled_time DESC
        ';
        $stmt = $pdo->query($sql);
        $interviews = $stmt->fetchAll();

        jsonOk(['data' => $interviews]);
    } catch (Exception $e) {
        jsonError('Failed to fetch interviews: ' . $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $data = getBody();
    $requestId = (int)($data['request_id'] ?? 0);
    $requesterId = (int)($data['requester_id'] ?? 0);
    $date = $data['scheduled_date'] ?? '';
    $time = $data['scheduled_time'] ?? '';
    $methodType = $data['method'] ?? 'phone_call';
    $venue = trim($data['meeting_link_or_venue'] ?? '');

    if ($requesterId <= 0 || empty($date) || empty($time)) {
        jsonError('Requester ID, date, and time are required.');
    }

    try {
        $stmt = $pdo->prepare('
            INSERT INTO interviews (
                request_id, requester_id, scheduled_date, scheduled_time,
                method, meeting_link_or_venue, result, created_by, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, "pending", ?, NOW(), NOW()
            )
        ');
        $stmt->execute([
            $requestId > 0 ? $requestId : null,
            $requesterId,
            $date,
            $time,
            $methodType,
            $venue,
            $admin['id']
        ]);

        $newId = (int)$pdo->lastInsertId();

        // Notify user
        $notif = $pdo->prepare('
            INSERT INTO notifications (user_id, type, title, message, related_type, related_id, is_read, created_at)
            VALUES (?, "interview_scheduled", "Interview Scheduled", ?, "interview", ?, 0, NOW())
        ');
        $notif->execute([
            $requesterId,
            "An interview/verification call has been scheduled for {$date} at {$time}.",
            $newId
        ]);

        jsonOk(['message' => 'Interview scheduled successfully.', 'id' => $newId], 201);
    } catch (Exception $e) {
        jsonError('Failed to schedule interview: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed.', 405);
