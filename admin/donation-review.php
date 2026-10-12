<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: /CommunityDono/admin/donations.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT d.*, c.name AS category_name,
           u.first_name, u.last_name, u.email AS donor_email, u.phone AS donor_phone,
           u.barangay AS donor_barangay, u.city AS donor_city,
           ab.first_name AS archived_by_first, ab.last_name AS archived_by_last
    FROM donations d
    JOIN categories c ON d.category_id = c.id
    JOIN users u ON d.donor_id = u.id
    LEFT JOIN users ab ON d.archived_by = ab.id
    WHERE d.id = ?
    LIMIT 1
');
$stmt->execute([$id]);
$donation = $stmt->fetch();

if (!$donation) {
    setFlash('error', 'Donation not found.');
    header('Location: /CommunityDono/admin/donations.php');
    exit;
}

// Fetch images
$imgStmt = $pdo->prepare('SELECT * FROM donation_images WHERE donation_id = ? ORDER BY is_primary DESC, sort_order ASC');
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll();

$pageTitle = 'Donation Review #' . $id . ' — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Review Donation #<?= (int)$donation['id'] ?></h1>
      </div>
      <div>
        <a href="/CommunityDono/admin/donations.php" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;">
          &larr; Back to Queue
        </a>
      </div>
    </header>

    <div class="admin-content" style="max-width: 960px; margin: 0 auto;">
      <?php renderFlash(); ?>

      <div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px; align-items: start;">
        
        <!-- Left Column: Item details and Media -->
        <div>
          <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
              <span class="badge badge-info"><?= e($donation['category_name']) ?></span>
              <div><?= renderBadge($donation['status']) ?></div>
            </div>

            <h2 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0 0 12px;"><?= e($donation['title']) ?></h2>

            <div style="font-size: 14px; color: var(--color-text); line-height: 1.6; margin-bottom: 16px; background: #F8FAFC; padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
              <strong>Description:</strong><br>
              <?= e($donation['description'] ?? 'No description provided.') ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
              <div>Quantity: <strong><?= (int)$donation['quantity_total'] ?> <?= e($donation['unit']) ?></strong></div>
              <div>Condition: <strong><?= ucfirst($donation['condition_type']) ?></strong></div>
              <div>Expiration Date: <strong><?= $donation['expiration_date'] ? date('M d, Y', strtotime($donation['expiration_date'])) : 'N/A' ?></strong></div>
              <div>Delivery Method: <strong><?= ucfirst($donation['pickup_method']) ?></strong></div>
            </div>
          </div>

          <!-- Media gallery -->
          <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
            <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 14px;">Submitted Media &amp; Photos</h3>
            <?php if (empty($images)): ?>
              <p style="color: var(--color-text-muted); font-size: 13px;">No photos uploaded.</p>
            <?php else: ?>
              <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 10px;">
                <?php foreach ($images as $img): ?>
                  <a href="/CommunityDono/<?= e($img['file_path']) ?>" target="_blank" style="aspect-ratio: 1; border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--color-border); display: block;">
                    <img src="/CommunityDono/<?= e($img['file_path']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($donation['video_url'])): ?>
              <div style="margin-top: 14px; font-size: 13px;">
                <strong>Video Demonstration:</strong>
                <a href="<?= e($donation['video_url']) ?>" target="_blank" style="color: var(--color-primary); font-weight: 600; margin-left: 6px;">
                  Open Video Link ↗
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right Column: Donor Info, Logistics, Actions -->
        <div>
          <!-- Donor Info (Scope 6.2) -->
          <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 20px;">
            <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 12px;">Donor Information</h3>
            <div style="font-size: 13.5px; display: flex; flex-direction: column; gap: 8px;">
              <div><strong>Name:</strong> <?= e($donation['first_name'] . ' ' . $donation['last_name']) ?></div>
              <div><strong>Email:</strong> <?= e($donation['donor_email']) ?></div>
              <div><strong>Contact Number:</strong> <?= e($donation['donor_contact_number'] ?? $donation['donor_phone'] ?? 'N/A') ?></div>
              <div><strong>Location:</strong> <?= e($donation['donor_barangay'] ? $donation['donor_barangay'] . ', ' : '') ?><?= e($donation['donor_city'] ?? 'Metro Manila') ?></div>
            </div>
          </div>

          <!-- Pickup Schedule Info -->
          <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 20px;">
            <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 12px;">Pickup / Delivery Logistics</h3>
            <div style="font-size: 13.5px; display: flex; flex-direction: column; gap: 8px;">
              <div><strong>Method:</strong> <?= ucfirst($donation['pickup_method']) ?></div>
              <div><strong>Pickup Location:</strong> <?= e($donation['pickup_barangay'] ? $donation['pickup_barangay'] . ', ' : '') ?><?= e($donation['pickup_city'] ?? '') ?></div>
              <div><strong>Available Date / Time:</strong> <?= $donation['pickup_date'] ? date('M d, Y', strtotime($donation['pickup_date'])) : 'Flexible' ?> <?= $donation['pickup_time'] ? 'at ' . date('g:i A', strtotime($donation['pickup_time'])) : '' ?></div>
              <?php if (!empty($donation['pickup_notes'])): ?>
                <div><strong>Schedule Notes:</strong> <?= e($donation['pickup_notes']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Administrative Decision & Actions -->
          <?php if ($donation['status'] === 'archived'): ?>
            <?php
              $archivedAdminName = !empty($donation['archived_by_first'])
                  ? ($donation['archived_by_first'] . ' ' . $donation['archived_by_last'])
                  : 'Administrator';
              $prevStatusLabel = ucfirst(str_replace('_', ' ', (string)($donation['archived_prev_status'] ?? 'pending_verification')));
            ?>
            <!-- Archived Information Card -->
            <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 2px solid #F87171; background: #FEF2F2;">
              <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #FEE2E2; display: grid; place-items: center; flex-shrink: 0;">
                  <i data-lucide="archive" style="width:18px;height:18px;color:#DC2626;"></i>
                </div>
                <div>
                  <h3 style="font-size: 16px; font-weight: 700; color: #991B1B; margin: 0;">Archived Listing</h3>
                  <span style="font-size: 12px; color: #7F1D1D;">Hidden from public browsing</span>
                </div>
              </div>

              <div style="font-size: 13px; color: #7F1D1D; display: flex; flex-direction: column; gap: 8px; margin-bottom: 18px; background: #FFFFFF; padding: 14px; border-radius: 8px; border: 1px solid #FECACA;">
                <?php if (!empty($donation['archived_at'])): ?>
                  <div><strong>Archived At:</strong> <?= date('M j, Y g:i A', strtotime($donation['archived_at'])) ?></div>
                <?php endif; ?>
                <div><strong>Archived By:</strong> <?= e($archivedAdminName) ?></div>
                <div><strong>Previous Status:</strong> <?= e($prevStatusLabel) ?></div>
                <div><strong>Reason:</strong> <?= e($donation['archive_reason'] ?? 'No reason recorded.') ?></div>
              </div>

              <p style="font-size: 12.5px; color: #991B1B; margin-bottom: 16px; line-height: 1.45;">
                This listing cannot be directly approved or rejected while archived. You can safely restore it to return it to <strong><?= e($prevStatusLabel) ?></strong> status.
              </p>

              <button type="button"
                      class="btn btn-primary"
                      style="width: 100%; justify-content: center; font-size: 13px; padding: 10px; background: #16A34A; border-color: #16A34A;"
                      data-restore-id="<?= (int)$donation['id'] ?>"
                      data-restore-title="<?= e($donation['title']) ?>"
                      data-restore-status="<?= e($prevStatusLabel) ?>"
                      onclick="openRestoreModal(this)">
                <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i> Restore Donation Listing
              </button>
            </div>

          <?php else: ?>
            <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1.5px solid var(--color-border); box-shadow: 0 4px 16px rgba(20, 33, 61, 0.05); background: var(--color-surface, #FFFFFF);">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <h3 style="font-size: 16px; font-weight: 700; color: var(--color-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                  <i data-lucide="shield-check" style="width: 18px; height: 18px;"></i>
                  Moderation Decision
                </h3>
                <span class="badge badge-info" style="font-size: 11px;">Admin Review</span>
              </div>

              <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php if (in_array($donation['status'], ['pending_verification', 'needs_information', 'draft'], true)): ?>
                  <!-- Approve Button -->
                  <form action="/CommunityDono/api/admin/approve-donation.php" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="donation_id" value="<?= (int)$donation['id'] ?>">
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 11px; font-weight: 600; font-size: 13.5px; border-radius: 10px; box-shadow: 0 4px 12px rgba(31, 79, 163, 0.2);">
                      <i data-lucide="check-circle" style="width: 15px; height: 15px;"></i> Approve Donation Listing
                    </button>
                  </form>

                  <!-- Reject Form with Reason -->
                  <div style="background: var(--color-surface-soft, #F8FAFC); border: 1px solid var(--color-border); border-radius: 10px; padding: 12px;">
                    <form action="/CommunityDono/api/admin/reject-donation.php" method="POST">
                      <?= csrfField() ?>
                      <input type="hidden" name="donation_id" value="<?= (int)$donation['id'] ?>">
                      <div class="form-group" style="margin-bottom: 8px;">
                        <label style="font-size: 11.5px; font-weight: 600; color: var(--color-text-muted); display: block; margin-bottom: 4px;">Rejection Reason:</label>
                        <input type="text" name="reason" placeholder="State reason for rejecting listing..." required class="form-input" style="font-size: 12.5px; height: 36px; border-radius: 8px;">
                      </div>
                      <button type="submit" class="btn btn-danger" style="width: 100%; justify-content: center; padding: 8px; font-size: 12.5px; font-weight: 600; border-radius: 8px;">
                        <i data-lucide="x-circle" style="width: 13px; height: 13px;"></i> Reject Listing
                      </button>
                    </form>
                  </div>
                <?php endif; ?>

                <!-- Archive Donation (triggers modal dialog) -->
                <button type="button"
                        class="btn btn-outline"
                        style="width: 100%; justify-content: center; font-size: 12.5px; font-weight: 600; padding: 9px; color: #92400E; border-color: #FCD34D; background: #FEF3C7; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;"
                        data-archive-id="<?= (int)$donation['id'] ?>"
                        data-archive-title="<?= e($donation['title']) ?>"
                        onclick="openArchiveModal(this)">
                  <i data-lucide="archive" style="width: 14px; height: 14px;"></i> Archive Donation
                </button>
              </div>
            </div>
          <?php endif; ?>

        </div>

      </div>

    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     Archive Confirmation Dialog
     ══════════════════════════════════════════════════════════════════════════ -->
<dialog id="archive-dialog"
        aria-labelledby="archive-dialog-title"
        style="border: none; border-radius: var(--radius-lg); padding: 0; background: transparent; max-width: 480px; width: 92%;">
  <div class="card"
       style="padding: 32px; border-radius: var(--radius-lg); background: var(--color-bg, #FFFFFF); box-shadow: 0 20px 60px rgba(0,0,0,0.3); border: 1px solid var(--color-border);">

    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
      <div style="width: 40px; height: 40px; border-radius: 50%; background: #FEF3C7; display: grid; place-items: center; flex-shrink: 0;">
        <i data-lucide="archive" style="width:18px;height:18px;color:#D97706;"></i>
      </div>
      <h2 id="archive-dialog-title" style="font-size: 18px; font-weight: 700; margin: 0;">Archive Donation?</h2>
    </div>

    <p style="font-size: 14px; color: var(--color-text-muted); margin: 0 0 6px;">You are about to archive:</p>
    <p id="archive-dialog-donation-name"
       style="font-size: 15px; font-weight: 700; color: var(--color-text); margin: 0 0 16px;
              padding: 10px 14px; background: #F8FAFC; border-radius: 8px; border: 1px solid var(--color-border);">
    </p>

    <div class="alert alert-warning" role="alert"
         style="display: flex; gap: 10px; align-items: flex-start; padding: 10px 14px;
                border-radius: 8px; font-size: 13px; margin-bottom: 20px;">
      <i data-lucide="alert-triangle" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;"></i>
      <span>This listing will be <strong>removed from public browsing</strong>.
            All related historical claim and transaction records are preserved.
            You can restore this listing at any time.</span>
    </div>

    <form id="archive-dialog-form" action="/CommunityDono/api/admin/delete-donation.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="donation_id" id="archive-dialog-donation-id" value="">

      <div class="form-group" style="margin-bottom: 20px;">
        <label class="form-label" for="archive-reason-input" style="font-weight: 600;">
          Reason for Archiving <span style="color: #DC2626;">*</span>
        </label>
        <textarea id="archive-reason-input"
                  name="archive_reason"
                  class="form-input"
                  rows="3"
                  required
                  placeholder="Provide a reason for archiving this donation..."
                  style="font-size: 13.5px; resize: vertical;"></textarea>
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button type="button" id="archive-cancel-btn" class="btn btn-outline" onclick="closeArchiveModal()">Cancel</button>
        <button type="submit" id="archive-confirm-btn" class="btn btn-danger"
                style="display: inline-flex; align-items: center; gap: 7px;">
          <i data-lucide="archive" style="width:14px;height:14px;"></i> Confirm Archive
        </button>
      </div>
    </form>
  </div>
</dialog>

<!-- ══════════════════════════════════════════════════════════════════════════
     Restore Confirmation Dialog
     ══════════════════════════════════════════════════════════════════════════ -->
<dialog id="restore-dialog"
        aria-labelledby="restore-dialog-title"
        style="border: none; border-radius: var(--radius-lg); padding: 0; background: transparent; max-width: 480px; width: 92%;">
  <div class="card"
       style="padding: 32px; border-radius: var(--radius-lg); background: var(--color-bg, #FFFFFF); box-shadow: 0 20px 60px rgba(0,0,0,0.3); border: 1px solid var(--color-border);">

    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
      <div style="width: 40px; height: 40px; border-radius: 50%; background: #DCFCE7; display: grid; place-items: center; flex-shrink: 0;">
        <i data-lucide="rotate-ccw" style="width:18px;height:18px;color:#16A34A;"></i>
      </div>
      <h2 id="restore-dialog-title" style="font-size: 18px; font-weight: 700; margin: 0;">Restore Donation?</h2>
    </div>

    <p style="font-size: 14px; color: var(--color-text-muted); margin: 0 0 6px;">You are restoring listing:</p>
    <p id="restore-dialog-donation-name"
       style="font-size: 15px; font-weight: 700; color: var(--color-text); margin: 0 0 14px;
              padding: 10px 14px; background: #F8FAFC; border-radius: 8px; border: 1px solid var(--color-border);">
    </p>

    <div class="alert alert-info" role="alert"
         style="display: flex; gap: 10px; align-items: flex-start; padding: 10px 14px;
                border-radius: 8px; font-size: 13px; margin-bottom: 20px;">
      <i data-lucide="info" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;"></i>
      <span>This donation will be restored to its previous status (<strong id="restore-target-status-label">approved</strong>).
            If approved, it will become publicly visible again subject to availability and expiration rules.</span>
    </div>

    <form id="restore-dialog-form" action="/CommunityDono/api/admin/restore-donation.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="donation_id" id="restore-dialog-donation-id" value="">

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button type="button" id="restore-cancel-btn" class="btn btn-outline" onclick="closeRestoreModal()">Cancel</button>
        <button type="submit" id="restore-confirm-btn" class="btn btn-primary"
                style="display: inline-flex; align-items: center; gap: 7px; background: #16A34A; border-color: #16A34A;">
          <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i> Confirm Restore
        </button>
      </div>
    </form>
  </div>
</dialog>

<script>
  let lastActiveOpener = null;

  const archiveDialog     = document.getElementById('archive-dialog');
  const archiveForm       = document.getElementById('archive-dialog-form');
  const archiveIdInput    = document.getElementById('archive-dialog-donation-id');
  const archiveNameEl     = document.getElementById('archive-dialog-donation-name');
  const archiveConfirmBtn = document.getElementById('archive-confirm-btn');
  const archiveReasonTxt  = document.getElementById('archive-reason-input');

  function openArchiveModal(btn) {
    lastActiveOpener = btn;
    archiveIdInput.value      = btn.dataset.archiveId;
    archiveNameEl.textContent = btn.dataset.archiveTitle; // Safe textContent DOM assignment
    archiveReasonTxt.value    = '';
    archiveConfirmBtn.disabled = false;
    archiveConfirmBtn.textContent = 'Confirm Archive';

    if (typeof archiveDialog.showModal === 'function') {
      archiveDialog.showModal();
    } else {
      archiveDialog.setAttribute('open', '');
    }
    archiveReasonTxt.focus();
  }

  function closeArchiveModal() {
    if (typeof archiveDialog.close === 'function') {
      archiveDialog.close();
    } else {
      archiveDialog.removeAttribute('open');
    }
    archiveIdInput.value = '';
    if (lastActiveOpener) {
      lastActiveOpener.focus();
      lastActiveOpener = null;
    }
  }

  const restoreDialog     = document.getElementById('restore-dialog');
  const restoreForm       = document.getElementById('restore-dialog-form');
  const restoreIdInput    = document.getElementById('restore-dialog-donation-id');
  const restoreNameEl     = document.getElementById('restore-dialog-donation-name');
  const restoreStatusEl   = document.getElementById('restore-target-status-label');
  const restoreConfirmBtn = document.getElementById('restore-confirm-btn');

  function openRestoreModal(btn) {
    lastActiveOpener = btn;
    restoreIdInput.value = btn.dataset.restoreId;
    restoreNameEl.textContent = btn.dataset.restoreTitle; // Safe textContent DOM assignment
    restoreStatusEl.textContent = btn.dataset.restoreStatus || 'previous status';
    restoreConfirmBtn.disabled = false;
    restoreConfirmBtn.textContent = 'Confirm Restore';

    if (typeof restoreDialog.showModal === 'function') {
      restoreDialog.showModal();
    } else {
      restoreDialog.setAttribute('open', '');
    }
    document.getElementById('restore-cancel-btn').focus();
  }

  function closeRestoreModal() {
    if (typeof restoreDialog.close === 'function') {
      restoreDialog.close();
    } else {
      restoreDialog.removeAttribute('open');
    }
    restoreIdInput.value = '';
    if (lastActiveOpener) {
      lastActiveOpener.focus();
      lastActiveOpener = null;
    }
  }

  archiveDialog.addEventListener('click', e => {
    const rect = archiveDialog.getBoundingClientRect();
    if (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom) {
      closeArchiveModal();
    }
  });

  restoreDialog.addEventListener('click', e => {
    const rect = restoreDialog.getBoundingClientRect();
    if (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom) {
      closeRestoreModal();
    }
  });

  archiveForm.addEventListener('submit', () => {
    archiveConfirmBtn.disabled    = true;
    archiveConfirmBtn.textContent = 'Archiving…';
  });

  restoreForm.addEventListener('submit', () => {
    restoreConfirmBtn.disabled    = true;
    restoreConfirmBtn.textContent = 'Restoring…';
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
