<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$adminId = (int)$_SESSION['user_id'];

// Handle action (resolve or dismiss)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'], $_POST['action'])) {
    verifyCsrf();
    $reportId = (int)$_POST['report_id'];
    $action   = $_POST['action'] === 'resolve' ? 'resolved' : 'dismissed';
    $notes    = trim($_POST['admin_notes'] ?? '');

    try {
        $upd = $pdo->prepare('
            UPDATE problem_reports
            SET status = ?, reviewed_by = ?, admin_notes = ?, resolved_at = NOW()
            WHERE id = ?
        ');
        $upd->execute([$action, $adminId, $notes ?: null, $reportId]);

        logAudit($pdo, $adminId, 'admin', 'REPORT_REVIEWED', 'report', $reportId, null, ['status' => $action], 'Moderated problem report');
        setFlash('success', 'Report marked as ' . $action . '.');
    } catch (Exception $e) {
        error_log($e->getMessage());
        setFlash('error', 'Failed to update report.');
    }
    header('Location: /CommunityDono/admin/moderation.php');
    exit;
}

// Fetch pending and historical reports
$reports = [];
try {
    $stmt = $pdo->query('
        SELECT p.*,
               u.first_name AS reporter_first, u.last_name AS reporter_last, u.email AS reporter_email,
               admin_u.first_name AS admin_first, admin_u.last_name AS admin_last,
               d.title AS donation_title, d.status AS donation_status
        FROM problem_reports p
        JOIN users u ON p.reporter_id = u.id
        LEFT JOIN users admin_u ON p.reviewed_by = admin_u.id
        LEFT JOIN donations d ON (p.target_type = "donation" AND p.target_id = d.id)
        ORDER BY FIELD(p.status, "pending", "reviewed", "resolved", "dismissed"), p.created_at DESC
    ');
    $reports = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$pageTitle = 'Moderation & Reports — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Community Moderation &amp; Flags</h1>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php if (empty($reports)): ?>
          <div class="card" style="padding: 40px; text-align: center;">
            <p style="color: var(--color-text-muted);">No problem reports filed by the community.</p>
          </div>
        <?php else: ?>
          <?php foreach ($reports as $rep): ?>
            <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
              
              <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 12px; flex-wrap: wrap;">
                <div>
                  <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 4px;">
                    <span class="badge badge-accent">Flag: <?= e($rep['reason']) ?></span>
                    <span style="font-size: 12px; color: var(--color-text-muted);">
                      Reported by <?= e($rep['reporter_first'] . ' ' . $rep['reporter_last']) ?> · <?= date('M d, Y g:i A', strtotime($rep['created_at'])) ?>
                    </span>
                  </div>

                  <h3 style="font-size: 17px; font-weight: 700; color: var(--color-text); margin: 0 0 4px;">
                    Target: <?= ucfirst($rep['target_type']) ?> #<?= (int)$rep['target_id'] ?>
                    <?php if (!empty($rep['donation_title'])): ?>
                      — <span style="color: var(--color-text); font-weight: 600;"><?= e($rep['donation_title']) ?></span>
                      <?php if ($rep['donation_status'] === 'archived'): ?>
                        <span class="badge badge-neutral" style="font-size: 11px; margin-left: 6px;">Archived</span>
                      <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($rep['target_type'] === 'donation'): ?>
                      <a href="/CommunityDono/admin/donation-review.php?id=<?= (int)$rep['target_id'] ?>" style="font-size: 13px; color: var(--color-primary); font-weight: normal; margin-left: 8px;">
                        Review Item ↗
                      </a>
                    <?php endif; ?>
                  </h3>
                </div>

                <div>
                  <?= renderBadge($rep['status']) ?>
                </div>
              </div>

              <?php if (!empty($rep['description'])): ?>
                <div style="background: #F8FAFC; padding: 12px 14px; border-radius: var(--radius-md); font-size: 13.5px; color: var(--color-text); margin-bottom: 14px; border: 1px solid var(--color-border);">
                  <strong>User Details:</strong> <?= e($rep['description']) ?>
                </div>
              <?php endif; ?>

              <?php if ($rep['status'] === 'pending'): ?>
                <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid var(--color-border); flex-wrap: wrap;">
                  
                  <!-- Dismiss Form -->
                  <form action="/CommunityDono/admin/moderation.php" method="POST" style="display: inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="report_id" value="<?= (int)$rep['id'] ?>">
                    <input type="hidden" name="action" value="dismiss">
                    <button type="submit" class="btn btn-outline" style="font-size: 12px; padding: 6px 14px;">
                      Dismiss Flag
                    </button>
                  </form>

                  <!-- Resolve Form -->
                  <form action="/CommunityDono/admin/moderation.php" method="POST" style="display: inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="report_id" value="<?= (int)$rep['id'] ?>">
                    <input type="hidden" name="action" value="resolve">
                    <button type="submit" class="btn btn-outline" style="font-size: 12px; padding: 6px 16px; color: #16A34A; border-color: #86EFAC;">
                      <i data-lucide="check-circle" style="width:13px;height:13px;"></i> Resolve Only
                    </button>
                  </form>

                  <!-- Archive Donation & Resolve Report -->
                  <?php if ($rep['target_type'] === 'donation' && ($rep['donation_status'] ?? '') !== 'archived'): ?>
                    <button type="button"
                            class="btn btn-danger"
                            style="font-size: 12px; padding: 6px 16px; display: inline-flex; align-items: center; gap: 6px;"
                            data-donation-id="<?= (int)$rep['target_id'] ?>"
                            data-report-id="<?= (int)$rep['id'] ?>"
                            data-donation-title="<?= e($rep['donation_title'] ?? ('Donation #' . $rep['target_id'])) ?>"
                            data-flag-reason="<?= e($rep['reason']) ?>"
                            onclick="openModerationArchiveDialog(this)">
                      <i data-lucide="archive" style="width:13px;height:13px;"></i> Archive Item &amp; Resolve
                    </button>
                  <?php endif; ?>

                </div>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     Moderation Archive Dialog
     ══════════════════════════════════════════════════════════════════════════ -->
<dialog id="mod-archive-dialog"
        aria-labelledby="mod-archive-dialog-title"
        style="border: none; border-radius: var(--radius-lg); padding: 0; background: transparent; max-width: 480px; width: 92%;">
  <div class="card"
       style="padding: 32px; border-radius: var(--radius-lg); background: var(--color-bg, #FFFFFF); box-shadow: 0 20px 60px rgba(0,0,0,0.3); border: 1px solid var(--color-border);">

    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
      <div style="width: 40px; height: 40px; border-radius: 50%; background: #FEE2E2; display: grid; place-items: center; flex-shrink: 0;">
        <i data-lucide="archive" style="width:18px;height:18px;color:#DC2626;"></i>
      </div>
      <h2 id="mod-archive-dialog-title" style="font-size: 18px; font-weight: 700; margin: 0;">Archive &amp; Resolve Flag</h2>
    </div>

    <p style="font-size: 14px; color: var(--color-text-muted); margin: 0 0 6px;">Target item to be archived:</p>
    <p id="mod-archive-donation-name"
       style="font-size: 15px; font-weight: 700; color: var(--color-text); margin: 0 0 16px;
              padding: 10px 14px; background: #F8FAFC; border-radius: 8px; border: 1px solid var(--color-border);">
    </p>

    <div class="alert alert-warning" role="alert"
         style="display: flex; gap: 10px; align-items: flex-start; padding: 10px 14px;
                border-radius: 8px; font-size: 13px; margin-bottom: 20px;">
      <i data-lucide="alert-triangle" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;"></i>
      <span>Archiving will remove this listing from public search and mark this problem report as <strong>resolved</strong>. Historical records are preserved.</span>
    </div>

    <form id="mod-archive-form" action="/CommunityDono/api/admin/delete-donation.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="donation_id" id="mod-archive-donation-id" value="">
      <input type="hidden" name="report_id" id="mod-archive-report-id" value="">

      <div class="form-group" style="margin-bottom: 20px;">
        <label class="form-label" for="mod-archive-reason-input" style="font-weight: 600;">
          Reason for Archiving <span style="color: #DC2626;">*</span>
        </label>
        <textarea id="mod-archive-reason-input"
                  name="archive_reason"
                  class="form-input"
                  rows="3"
                  required
                  placeholder="Reason for moderation archive..."
                  style="font-size: 13.5px; resize: vertical;"></textarea>
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button type="button" id="mod-archive-cancel-btn" class="btn btn-outline" onclick="closeModerationArchiveDialog()">Cancel</button>
        <button type="submit" id="mod-archive-confirm-btn" class="btn btn-danger"
                style="display: inline-flex; align-items: center; gap: 7px;">
          <i data-lucide="archive" style="width:14px;height:14px;"></i> Archive &amp; Resolve
        </button>
      </div>
    </form>
  </div>
</dialog>

<script>
  let modLastOpener = null;
  const modDialog     = document.getElementById('mod-archive-dialog');
  const modForm       = document.getElementById('mod-archive-form');
  const modDonationId = document.getElementById('mod-archive-donation-id');
  const modReportId   = document.getElementById('mod-archive-report-id');
  const modDonationEl = document.getElementById('mod-archive-donation-name');
  const modReasonTxt  = document.getElementById('mod-archive-reason-input');
  const modConfirmBtn = document.getElementById('mod-archive-confirm-btn');

  function openModerationArchiveDialog(btn) {
    modLastOpener = btn;
    modDonationId.value = btn.dataset.donationId;
    modReportId.value   = btn.dataset.reportId;
    modDonationEl.textContent = btn.dataset.donationTitle; // Safe textContent
    modReasonTxt.value  = 'Community flag (' + (btn.dataset.flagReason || 'Inappropriate listing') + '): removed by moderation.';
    modConfirmBtn.disabled = false;
    modConfirmBtn.textContent = 'Archive & Resolve';

    if (typeof modDialog.showModal === 'function') {
      modDialog.showModal();
    } else {
      modDialog.setAttribute('open', '');
    }
    modReasonTxt.focus();
  }

  function closeModerationArchiveDialog() {
    if (typeof modDialog.close === 'function') {
      modDialog.close();
    } else {
      modDialog.removeAttribute('open');
    }
    modDonationId.value = '';
    modReportId.value = '';
    if (modLastOpener) {
      modLastOpener.focus();
      modLastOpener = null;
    }
  }

  modDialog.addEventListener('click', e => {
    const rect = modDialog.getBoundingClientRect();
    if (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom) {
      closeModerationArchiveDialog();
    }
  });

  modForm.addEventListener('submit', () => {
    modConfirmBtn.disabled = true;
    modConfirmBtn.textContent = 'Processing…';
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
