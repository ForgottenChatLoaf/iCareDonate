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
    setFlash('error', 'Method not allowed. Restoration requires a POST request.');
    header('Location: /CommunityDono/admin/donations.php?status=archived');
    exit;
}

verifyCsrf();

$adminId    = (int)($_SESSION['user_id'] ?? 0);
$adminRole  = (string)($_SESSION['role'] ?? 'admin');
$donationId = (int)($_POST['donation_id'] ?? 0);

if ($donationId <= 0) {
    writeAuditLog($pdo, [
        'actor_id'    => $adminId,
        'actor_role'  => $adminRole,
        'action'      => 'DONATION_RESTORE_DENIED',
        'target_type' => 'donation',
        'target_id'   => 0,
        'reason'      => 'Invalid or missing donation ID for restore operation.',
    ]);
    setFlash('error', 'Invalid donation ID.');
    header('Location: /CommunityDono/admin/donations.php?status=archived');
    exit;
}

try {
    $pdo->beginTransaction();

    // ── Lock and load target donation with donor & category status ───────────
    $stmt = $pdo->prepare('
        SELECT d.id, d.title, d.status, d.donor_id, d.expires_at,
               d.archived_prev_status, d.archive_reason,
               c.is_active AS category_active,
               u.status AS donor_status, u.first_name AS donor_first, u.last_name AS donor_last
        FROM donations d
        JOIN categories c ON d.category_id = c.id
        JOIN users u ON d.donor_id = u.id
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
            'action'      => 'DONATION_RESTORE_DENIED',
            'target_type' => 'donation',
            'target_id'   => $donationId,
            'reason'      => 'Target donation record not found for restore operation.',
        ]);
        setFlash('error', 'Donation not found.');
        header('Location: /CommunityDono/admin/donations.php?status=archived');
        exit;
    }

    if ($donation['status'] !== 'archived') {
        $pdo->rollBack();
        setFlash('error', 'Only archived donations can be restored. Current status is "' . $donation['status'] . '".');
        header('Location: /CommunityDono/admin/donations.php?status=archived');
        exit;
    }

    // ── Determine and validate previous status ───────────────────────────────
    $prevStatus = trim((string)($donation['archived_prev_status'] ?? ''));

    // Safe valid restoration targets based on schema
    $allowedRestoreTargets = [
        'draft',
        'pending_verification',
        'needs_information',
        'approved',
        'cancelled',
        'fully_allocated'
    ];

    if (!in_array($prevStatus, $allowedRestoreTargets, true)) {
        // Fallback safely to pending_verification so admin can review
        $prevStatus = 'pending_verification';
    }

    // ── Pre-restoration business rule verifications ──────────────────────────
    // 1. Check expiration if restoring to 'approved'
    if ($prevStatus === 'approved' && !empty($donation['expires_at'])) {
        if (strtotime($donation['expires_at']) <= time()) {
            $pdo->rollBack();
            writeAuditLog($pdo, [
                'actor_id'    => $adminId,
                'actor_role'  => $adminRole,
                'action'      => 'DONATION_RESTORE_CONFLICT',
                'target_type' => 'donation',
                'target_id'   => $donationId,
                'reason'      => 'Cannot restore to Approved: donation reached its expiration date on ' . $donation['expires_at'],
            ]);
            setFlash('error', 'Cannot restore donation to Approved: the listing has expired (' . date('M d, Y', strtotime($donation['expires_at'])) . ').');
            header('Location: /CommunityDono/admin/donation-review.php?id=' . $donationId);
            exit;
        }
    }

    // 2. Check donor account status
    if ($donation['donor_status'] !== 'active') {
        $pdo->rollBack();
        writeAuditLog($pdo, [
            'actor_id'    => $adminId,
            'actor_role'  => $adminRole,
            'action'      => 'DONATION_RESTORE_CONFLICT',
            'target_type' => 'donation',
            'target_id'   => $donationId,
            'reason'      => 'Cannot restore: donor account is currently ' . $donation['donor_status'],
        ]);
        setFlash('error', 'Cannot restore donation: the donor account (' . $donation['donor_first'] . ' ' . $donation['donor_last'] . ') is currently ' . $donation['donor_status'] . '.');
        header('Location: /CommunityDono/admin/donation-review.php?id=' . $donationId);
        exit;
    }

    // 3. Check category active status
    if ((int)$donation['category_active'] === 0) {
        $pdo->rollBack();
        writeAuditLog($pdo, [
            'actor_id'    => $adminId,
            'actor_role'  => $adminRole,
            'action'      => 'DONATION_RESTORE_CONFLICT',
            'target_type' => 'donation',
            'target_id'   => $donationId,
            'reason'      => 'Cannot restore: associated category is inactive.',
        ]);
        setFlash('error', 'Cannot restore donation: the assigned category is currently deactivated.');
        header('Location: /CommunityDono/admin/donation-review.php?id=' . $donationId);
        exit;
    }

    // ── Execute Restoration ──────────────────────────────────────────────────
    $upd = $pdo->prepare("
        UPDATE donations
        SET status               = ?,
            archived_at          = NULL,
            archived_by          = NULL,
            archive_reason       = NULL,
            archived_prev_status = NULL,
            updated_at           = NOW()
        WHERE id = ?
    ");
    $upd->execute([$prevStatus, $donationId]);

    // ── Notify the Donor ─────────────────────────────────────────────────────
    createNotification(
        $pdo,
        (int)$donation['donor_id'],
        'DONATION_RESTORED',
        'Donation Listing Restored',
        'Your listing "' . $donation['title'] . '" has been restored to ' . ucfirst(str_replace('_', ' ', $prevStatus)) . ' status by our moderation team.',
        'donation',
        $donationId
    );

    // ── Record Audit Log Entry ───────────────────────────────────────────────
    logAudit(
        $pdo,
        $adminId,
        $adminRole,
        'DONATION_RESTORED',
        'donation',
        $donationId,
        ['status' => 'archived'],
        ['status' => $prevStatus],
        'Restored from archive back to ' . $prevStatus
    );

    $pdo->commit();

    setFlash('success', 'Donation "' . $donation['title'] . '" has been successfully restored to ' . ucfirst(str_replace('_', ' ', $prevStatus)) . '.');
    header('Location: /CommunityDono/admin/donations.php?status=' . urlencode($prevStatus));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[restore-donation error] ' . $e->getMessage());

    writeAuditLog($pdo, [
        'actor_id'    => $adminId,
        'actor_role'  => $adminRole,
        'action'      => 'DONATION_RESTORE_ERROR',
        'target_type' => 'donation',
        'target_id'   => $donationId,
        'reason'      => 'Internal database error during restore transaction: ' . $e->getMessage(),
    ]);

    setFlash('error', 'Unable to restore donation due to a system error. Please try again.');
    header('Location: /CommunityDono/admin/donations.php?status=archived');
    exit;
}
