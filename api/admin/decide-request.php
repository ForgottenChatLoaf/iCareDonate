<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_BASE . '/admin/requests.php');
    exit;
}

verifyCsrf();

$adminId   = (int)$_SESSION['user_id'];
$requestId = (int)($_POST['request_id'] ?? 0);
$decision  = trim($_POST['decision'] ?? '');
$notes     = trim($_POST['staff_notes'] ?? '');
$staffName = trim($_POST['assigned_staff'] ?? 'Staff Admin');
$intDt     = trim($_POST['interview_datetime'] ?? '');

if ($requestId <= 0) {
    setFlash('error', 'Invalid request ID.');
    header('Location: ' . APP_BASE . '/admin/requests.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Fetch request
    $stmt = $pdo->prepare('SELECT r.*, u.email AS requester_email, u.first_name, u.last_name, d.title AS item_title FROM requests r JOIN users u ON r.requester_id = u.id JOIN donations d ON r.donation_id = d.id WHERE r.id = ?');
    $stmt->execute([$requestId]);
    $req = $stmt->fetch();

    if (!$req) {
        throw new Exception('Request not found.');
    }

    if ($decision === 'Approved') {
        $upd = $pdo->prepare('UPDATE requests SET status = "approved", admin_notes = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?');
        $upd->execute([$notes ?: 'Approved by moderator', $adminId, $requestId]);

        logAudit($pdo, $adminId, 'admin', 'REQUEST_APPROVED', 'request', $requestId, null, ['status' => 'approved'], 'Admin approved request R-' . $requestId);
        createNotification($pdo, (int)$req['requester_id'], 'REQUEST_APPROVED', 'Assistance Request Approved', 'Your request for "' . $req['item_title'] . '" was verified and approved by the moderator team.');
        setFlash('success', 'Request #R-' . $requestId . ' has been approved.');

    } elseif ($decision === 'Needs Info') {
        $upd = $pdo->prepare('UPDATE requests SET status = "needs_information", info_request = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?');
        $upd->execute([$notes ?: 'Please provide additional verification documents', $adminId, $requestId]);

        logAudit($pdo, $adminId, 'admin', 'REQUEST_NEEDS_INFO', 'request', $requestId, null, ['status' => 'needs_information'], 'Admin requested additional proof for request R-' . $requestId);
        createNotification($pdo, (int)$req['requester_id'], 'REQUEST_NEEDS_INFO', 'Additional Proof Required', 'The moderation team requires additional verification documents for your request.');
        setFlash('warning', 'Request #R-' . $requestId . ' marked as Needs Information.');

    } elseif ($decision === 'Rejected') {
        $upd = $pdo->prepare('UPDATE requests SET status = "rejected", rejection_reason = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?');
        $upd->execute([$notes ?: 'Did not meet verification criteria', $adminId, $requestId]);

        logAudit($pdo, $adminId, 'admin', 'REQUEST_REJECTED', 'request', $requestId, null, ['status' => 'rejected'], 'Admin rejected request R-' . $requestId);
        createNotification($pdo, (int)$req['requester_id'], 'REQUEST_REJECTED', 'Assistance Request Update', 'Your request could not be approved at this time: ' . ($notes ?: 'Criteria not met.'));
        setFlash('error', 'Request #R-' . $requestId . ' has been rejected.');

    } elseif ($decision === 'Schedule Interview') {
        $scheduledDate = date('Y-m-d', strtotime($intDt ?: 'tomorrow 10:00 AM'));
        $scheduledTime = date('H:i:s', strtotime($intDt ?: '10:00 AM'));

        $intStmt = $pdo->prepare('
            INSERT INTO interviews (request_id, scheduled_date, scheduled_time, method, assigned_admin_id, admin_notes, status, created_at)
            VALUES (?, ?, ?, "phone_call", ?, ?, "scheduled", NOW())
        ');
        $intStmt->execute([$requestId, $scheduledDate, $scheduledTime, $adminId, $notes ?: 'Verification interview scheduled by ' . $staffName]);

        $upd = $pdo->prepare('UPDATE requests SET interview_status = "scheduled", admin_notes = ? WHERE id = ?');
        $upd->execute(['Interview scheduled for ' . $scheduledDate . ' ' . $scheduledTime, $requestId]);

        logAudit($pdo, $adminId, 'admin', 'INTERVIEW_SCHEDULED', 'request', $requestId, null, ['date' => $scheduledDate], 'Interview scheduled for request R-' . $requestId);
        createNotification($pdo, (int)$req['requester_id'], 'INTERVIEW_SCHEDULED', 'Verification Interview Scheduled', 'A staff verification interview has been scheduled for ' . date('M d, Y g:i A', strtotime($scheduledDate . ' ' . $scheduledTime)) . '.');
        setFlash('success', 'Verification interview scheduled for Request #R-' . $requestId . '.');
    }

    $pdo->commit();
    header('Location: ' . APP_BASE . '/admin/requests.php');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    setFlash('error', 'Action failed: ' . $e->getMessage());
    header('Location: ' . APP_BASE . '/admin/requests.php');
    exit;
}
