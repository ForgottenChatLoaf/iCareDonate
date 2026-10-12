<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Security: Admin-only endpoint ──────────────────────────────────────────
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    setFlash('error', 'Method not allowed. Archiving requires a POST request.');
    header('Location: ' . APP_BASE . '/admin/donations.php');
    exit;
}

verifyCsrf();

$adminId    = (int)($_SESSION['user_id'] ?? 0);
$adminRole  = (string)($_SESSION['role'] ?? 'admin');
$donationId = (int)($_POST['donation_id'] ?? 0);
$reason     = trim((string)($_POST['archive_reason'] ?? ''));
$reportId   = isset($_POST['report_id']) && is_numeric($_POST['report_id']) ? (int)$_POST['report_id'] : 0;

// Validate donation ID
if ($donationId <= 0) {
    writeAuditLog($pdo, [
        'actor_id'    => $adminId,
        'actor_role'  => $adminRole,
        'action'      => 'DONATION_ARCHIVE_DENIED',
        'target_type' => 'donation',
        'target_id'   => 0,
        'reason'      => 'Invalid or missing donation ID.',
    ]);
    setFlash('error', 'Invalid donation ID.');
    header('Location: ' . APP_BASE . '/admin/donations.php');
    exit;
}

// Require a non-empty archive reason
if ($reason === '') {
    writeAuditLog($pdo, [
        'actor_id'    => $adminId,
        'actor_role'  => $adminRole,
        'action'      => 'DONATION_ARCHIVE_DENIED',
        'target_type' => 'donation',
        'target_id'   => $donationId,
        'reason'      => 'Archive attempt rejected: mandatory reason was omitted.',
    ]);
    setFlash('error', 'Please provide a non-empty reason for archiving this donation.');
    header('Location: ' . APP_BASE . '/admin/donation-review.php?id=' . $donationId);
    exit;
}

try {
    $pdo->beginTransaction();

    // ── Lock and load target donation ────────────────────────────────────────
    $stmt = $pdo->prepare('
        SELECT d.id, d.title, d.status, d.donor_id
        FROM donations d
        WHERE d.id = ?
        FOR UPDATE
    ');
    $stmt->execute([$donationId]);
    $donation = $stmt->fetch();

    if (!$donation) {
        $pdo->rollBack();
        writeAuditLog($pdo, [
            'actor_id'    => $adminId,
            'actor_role'  => $adminRole,
            'action'      => 'DONATION_ARCHIVE_DENIED',
            'target_type' => 'donation',
            'target_id'   => $donationId,
            'reason'      => 'Target donation record not found.',
        ]);
        setFlash('error', 'Donation not found. It may have already been removed.');
        header('Location: ' . APP_BASE . '/admin/donations.php');
        exit;
    }

    // ── Idempotency: already archived ────────────────────────────────────────
    if ($donation['status'] === 'archived') {
        $pdo->rollBack();
        setFlash('info', 'This donation is already archived.');
        header('Location: ' . APP_BASE . '/admin/donations.php?status=archived');
        exit;
    }

    $prevStatus = (string)$donation['status'];

    // ── Concurrency & Active-Record Integrity Checks ─────────────────────────
    // Active requests (not in terminal statuses)
    $reqStmt = $pdo->prepare("
        SELECT COUNT(*) FROM requests
        WHERE donation_id = ?
          AND status NOT IN ('cancelled', 'rejected', 'expired', 'completed')
        FOR UPDATE
    ");
    $reqStmt->execute([$donationId]);
    $activeRequests = (int)$reqStmt->fetchColumn();

    // Active matches
    $matchStmt = $pdo->prepare("
        SELECT COUNT(*) FROM matches
        WHERE donation_id = ? AND status = 'active'
        FOR UPDATE
    ");
    $matchStmt->execute([$donationId]);
    $activeMatches = (int)$matchStmt->fetchColumn();

    // Active transactions
    $txnStmt = $pdo->prepare("
        SELECT COUNT(*) FROM transactions
        WHERE donation_id = ?
          AND status NOT IN ('cancelled', 'completed', 'expired')
        FOR UPDATE
    ");
    $txnStmt->execute([$donationId]);
    $activeTransactions = (int)$txnStmt->fetchColumn();

    if ($activeRequests > 0 || $activeMatches > 0 || $activeTransactions > 0) {
        $pdo->rollBack();

        $details = [];
        if ($activeRequests > 0)     $details[] = "{$activeRequests} active request(s)";
        if ($activeMatches > 0)      $details[] = "{$activeMatches} active match(es)";
        if ($activeTransactions > 0) $details[] = "{$activeTransactions} active transaction(s)";
        $conflictReason = 'Active records linked to donation: ' . implode(', ', $details);

        writeAuditLog($pdo, [
            'actor_id'    => $adminId,
            'actor_role'  => $adminRole,
            'action'      => 'DONATION_ARCHIVE_CONFLICT',
            'target_type' => 'donation',
            'target_id'   => $donationId,
            'reason'      => $conflictReason,
        ]);

        setFlash('error', 'Cannot archive: this donation has ' . implode(', ', $details) . '. Resolve or complete them before archiving.');
        header('Location: ' . APP_BASE . '/admin/donation-review.php?id=' . $donationId);
        exit;
    }

    // ── Execute Archiving: Store metadata in dedicated columns ───────────────
    $upd = $pdo->prepare("
        UPDATE donations
        SET status               = 'archived',
            archived_at          = NOW(),
            archived_by          = ?,
            archive_reason       = ?,
            archived_prev_status = ?,
            updated_at           = NOW()
        WHERE id = ?
    ");
    $upd->execute([
        $adminId,
        $reason,
        $prevStatus,
        $donationId
    ]);

    // ── Moderation Integration: resolve linked problem report if present ────
    if ($reportId > 0) {
        $repStmt = $pdo->prepare("
            UPDATE problem_reports
            SET status = 'resolved',
                reviewed_by = ?,
                admin_notes = ?,
                resolved_at = NOW(),
                updated_at = NOW()
            WHERE id = ? AND target_type = 'donation' AND target_id = ?
        ");
        $repStmt->execute([
            $adminId,
            'Resolved via administrative archive: ' . $reason,
            $reportId,
            $donationId
        ]);

        logAudit(
            $pdo,
            $adminId,
            $adminRole,
            'REPORT_RESOLVED',
            'report',
            $reportId,
            ['status' => 'pending'],
            ['status' => 'resolved'],
            'Resolved by archiving target donation #' . $donationId
        );
    }

    // ── Notify the Donor ─────────────────────────────────────────────────────
    createNotification(
        $pdo,
        (int)$donation['donor_id'],
        'DONATION_ARCHIVED',
        'Donation Listing Archived',
        'Your listing "' . $donation['title'] . '" is no longer publicly visible. It has been archived by the moderation team. Reason: ' . $reason,
        'donation',
        $donationId
    );

    // ── Record Audit Log Entry ───────────────────────────────────────────────
    logAudit(
        $pdo,
        $adminId,
        $adminRole,
        'DONATION_ARCHIVED',
        'donation',
        $donationId,
        ['status' => $prevStatus],
        ['status' => 'archived'],
        $reason
    );

    $pdo->commit();

    setFlash('success', 'Donation "' . $donation['title'] . '" has been archived successfully and hidden from public views.');
    header('Location: ' . APP_BASE . '/admin/donations.php?status=archived');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[archive-donation error] ' . $e->getMessage());

    writeAuditLog($pdo, [
        'actor_id'    => $adminId,
        'actor_role'  => $adminRole,
        'action'      => 'DONATION_ARCHIVE_ERROR',
        'target_type' => 'donation',
        'target_id'   => $donationId,
        'reason'      => 'Internal database error during archive transaction: ' . $e->getMessage(),
    ]);

    setFlash('error', 'Unable to archive donation due to a system error. Please try again.');
    header('Location: ' . APP_BASE . '/admin/donations.php');
    exit;
}
