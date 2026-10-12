<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

// ── Filters ─────────────────────────────────────────────────────────────────
$filter   = trim($_GET['status'] ?? 'pending_verification');
$search   = trim($_GET['q'] ?? '');
$catId    = isset($_GET['cat']) && is_numeric($_GET['cat']) ? (int)$_GET['cat'] : 0;
$sortBy   = match($_GET['sort'] ?? 'newest') {
    'oldest' => 'd.created_at ASC',
    'title'  => 'd.title ASC',
    default  => 'd.created_at DESC',
};

// ── Categories for filter dropdown ──────────────────────────────────────────
$categories = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC')->fetchAll();

// ── Build WHERE conditions ───────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($filter === 'all') {
    // Ordinary "All" view shows active queue items and excludes archived listings
    $where[] = "d.status != 'archived'";
} elseif ($filter === 'archived') {
    $where[] = "d.status = 'archived'";
} else {
    $where[]  = 'd.status = ?';
    $params[] = $filter;
}

if (!empty($search)) {
    $where[]  = '(d.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    $s = '%' . escapeLike($search) . '%';
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
}
if ($catId > 0) {
    $where[]  = 'd.category_id = ?';
    $params[] = $catId;
}

$whereSql = implode(' AND ', $where);

// ── Main query ───────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT d.*, c.name AS category_name, u.first_name, u.last_name, u.email AS donor_email,
           ab.first_name AS archived_by_first, ab.last_name AS archived_by_last,
           (SELECT file_path FROM donation_images WHERE donation_id = d.id ORDER BY is_primary DESC LIMIT 1) AS primary_image
    FROM donations d
    JOIN categories c ON d.category_id = c.id
    JOIN users u ON d.donor_id = u.id
    LEFT JOIN users ab ON d.archived_by = ab.id
    WHERE $whereSql
    ORDER BY $sortBy
");
$stmt->execute($params);
$donations = $stmt->fetchAll();

// ── Tab counts via single grouped query ────────────────────────────────────────
$statusCounts = $pdo->query("SELECT status, COUNT(*) as cnt FROM donations GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$tabCounts = [
    'pending_verification' => (int)($statusCounts['pending_verification'] ?? 0),
    'approved'             => (int)($statusCounts['approved'] ?? 0),
    'archived'             => (int)($statusCounts['archived'] ?? 0),
    'all'                  => (int)array_sum(array_filter($statusCounts, fn($k) => $k !== 'archived', ARRAY_FILTER_USE_KEY)),
];

$pageTitle = 'Donations Moderation — iCareDonate Admin';
$extraCss  = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Donation Moderation Queue</h1>
      </div>
      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="?status=pending_verification<?= !empty($search) ? '&q='.urlencode($search) : '' ?>" class="btn <?= $filter === 'pending_verification' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 12px;">
          Pending (<?= $tabCounts['pending_verification'] ?>)
        </a>
        <a href="?status=approved<?= !empty($search) ? '&q='.urlencode($search) : '' ?>" class="btn <?= $filter === 'approved' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 12px;">
          Approved (<?= $tabCounts['approved'] ?>)
        </a>
        <a href="?status=archived<?= !empty($search) ? '&q='.urlencode($search) : '' ?>" class="btn <?= $filter === 'archived' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 12px;">
          <i data-lucide="archive" style="width:12px;height:12px;"></i> Archived (<?= $tabCounts['archived'] ?>)
        </a>
        <a href="?status=all<?= !empty($search) ? '&q='.urlencode($search) : '' ?>" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 12px;">
          All (<?= $tabCounts['all'] ?>)
        </a>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <!-- ── Search & Filter Bar ─────────────────────────────────────────── -->
      <div class="card" style="padding: 16px 20px; margin-bottom: 20px; border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
        <form method="GET" action="/CommunityDono/admin/donations.php" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
          <input type="hidden" name="status" value="<?= e($filter) ?>">

          <div style="flex: 1; min-width: 200px;">
            <label style="font-size: 11px; font-weight: 600; color: var(--color-text-muted); display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.04em;">Search</label>
            <input type="text" name="q" value="<?= e($search) ?>" class="form-input" placeholder="Donation title, donor name..." style="height: 36px; font-size: 13px;">
          </div>

          <div style="min-width: 160px;">
            <label style="font-size: 11px; font-weight: 600; color: var(--color-text-muted); display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.04em;">Category</label>
            <select name="cat" class="form-input" style="height: 36px; font-size: 13px;">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= $catId === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div style="min-width: 140px;">
            <label style="font-size: 11px; font-weight: 600; color: var(--color-text-muted); display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.04em;">Sort By</label>
            <select name="sort" class="form-input" style="height: 36px; font-size: 13px;">
              <option value="newest" <?= ($_GET['sort'] ?? 'newest') === 'newest' ? 'selected' : '' ?>>Newest First</option>
              <option value="oldest" <?= ($_GET['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
              <option value="title"  <?= ($_GET['sort'] ?? '') === 'title'  ? 'selected' : '' ?>>Title A–Z</option>
            </select>
          </div>

          <div style="display: flex; gap: 8px; align-items: flex-end;">
            <button type="submit" class="btn btn-primary" style="height: 36px; font-size: 13px; padding: 0 16px;">Filter</button>
            <?php if (!empty($search) || $catId > 0): ?>
              <a href="?status=<?= e($filter) ?>" class="btn btn-outline" style="height: 36px; font-size: 13px; padding: 0 14px;">Clear</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- ── Archived notice banner ─────────────────────────────────────── -->
      <?php if ($filter === 'archived'): ?>
        <div class="alert alert-info" role="alert" style="margin-bottom: 16px; display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 8px; font-size: 13.5px;">
          <i data-lucide="archive" style="width:16px;height:16px;flex-shrink:0;"></i>
          <span>Archived donations are hidden from public browse pages. You can restore any listing below to return it to its previous status.</span>
        </div>
      <?php endif; ?>

      <?php if (empty($donations)): ?>
        <div class="card" style="padding: 40px; text-align: center;">
          <i data-lucide="inbox" style="width:40px;height:40px;color:var(--color-text-muted);margin-bottom:12px;"></i>
          <p style="color: var(--color-text-muted);">No donations found for this filter.</p>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <?php foreach ($donations as $d): ?>
            <?php
              $isArchived = $d['status'] === 'archived';
              $archiveReasonText = (string)($d['archive_reason'] ?? '');
              $archivedPrevStatus = (string)($d['archived_prev_status'] ?? '');
              $archivedAdminName = !empty($d['archived_by_first'])
                  ? ($d['archived_by_first'] . ' ' . $d['archived_by_last'])
                  : 'Administrator';
            ?>
            <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid <?= $isArchived ? '#FCA5A5' : 'var(--color-border)' ?>; <?= $isArchived ? 'opacity: 0.92;' : '' ?>">

              <div style="display: grid; grid-template-columns: 120px 1fr auto; gap: 20px; align-items: start;">
                <!-- Image -->
                <div style="width: 120px; height: 100px; background: #F1F5F9; border-radius: var(--radius-md); overflow: hidden; display: flex; align-items: center; justify-content: center;">
                  <?php if (!empty($d['primary_image'])): ?>
                    <img src="/CommunityDono/<?= e($d['primary_image']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                  <?php else: ?>
                    <div style="color: #94A3B8;"><i data-lucide="gift" style="width:32px;height:32px;"></i></div>
                  <?php endif; ?>
                </div>

                <!-- Info -->
                <div>
                  <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 4px; flex-wrap: wrap;">
                    <span class="badge badge-neutral" style="font-size: 11px;"><?= e($d['category_name']) ?></span>
                    <?= renderBadge($d['status']) ?>
                    <span style="font-size: 12px; color: var(--color-text-muted);">
                      Donor: <strong><?= e($d['first_name'] . ' ' . $d['last_name']) ?></strong> (<?= e($d['donor_email']) ?>)
                    </span>
                  </div>

                  <h3 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0 0 6px;">
                    <?= e($d['title']) ?>
                  </h3>

                  <p style="font-size: 13.5px; color: var(--color-text-muted); margin: 0 0 10px; line-height: 1.5;">
                    <?= e($d['description'] ?? 'No description provided.') ?>
                  </p>

                  <div style="font-size: 12.5px; color: var(--color-text); display: flex; gap: 16px; flex-wrap: wrap;">
                    <div>Qty: <strong><?= (int)$d['quantity_total'] ?> <?= e($d['unit']) ?></strong></div>
                    <div>Condition: <strong><?= ucfirst($d['condition_type']) ?></strong></div>
                    <div>Pickup: <strong><?= ucfirst($d['pickup_method']) ?> (<?= e($d['pickup_city']) ?>)</strong></div>
                  </div>

                  <?php if ($isArchived): ?>
                    <div style="margin-top: 10px; font-size: 12px; color: #991B1B; display: flex; flex-wrap: wrap; gap: 14px; background: #FEF2F2; padding: 10px 14px; border-radius: 8px; border: 1px solid #FEE2E2;">
                      <?php if (!empty($d['archived_at'])): ?>
                        <span><i data-lucide="clock" style="width:12px;height:12px;vertical-align:-1px;"></i> Archived: <?= date('M j, Y g:i A', strtotime($d['archived_at'])) ?></span>
                      <?php endif; ?>
                      <span><i data-lucide="user" style="width:12px;height:12px;vertical-align:-1px;"></i> By: <?= e($archivedAdminName) ?></span>
                      <?php if (!empty($archivedPrevStatus)): ?>
                        <span><i data-lucide="corner-up-left" style="width:12px;height:12px;vertical-align:-1px;"></i> Prev Status: <strong><?= e(ucfirst(str_replace('_', ' ', $archivedPrevStatus))) ?></strong></span>
                      <?php endif; ?>
                      <?php if (!empty($archiveReasonText)): ?>
                        <span style="width: 100%;"><i data-lucide="message-square" style="width:12px;height:12px;vertical-align:-1px;"></i> Reason: <?= e($archiveReasonText) ?></span>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Status / Quick Link -->
                <div style="text-align: right; white-space: nowrap;">
                  <a href="/CommunityDono/admin/donation-review.php?id=<?= (int)$d['id'] ?>"
                     class="btn btn-outline" style="font-size: 12px; padding: 5px 12px; display: inline-flex; align-items: center; gap: 5px;">
                    <i data-lucide="eye" style="width:12px;height:12px;"></i> Review
                  </a>
                </div>
              </div>

              <!-- ── Action Bar ─────────────────────────────────────────── -->
              <?php if ($isArchived): ?>
                <!-- Restore action for archived donations -->
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end;">
                  <button type="button"
                          class="btn btn-outline"
                          style="font-size: 12px; padding: 6px 14px; color: #15803D; border-color: #86EFAC; display: inline-flex; align-items: center; gap: 5px;"
                          data-restore-id="<?= (int)$d['id'] ?>"
                          data-restore-title="<?= e($d['title']) ?>"
                          data-restore-status="<?= e(ucfirst(str_replace('_', ' ', $archivedPrevStatus ?: 'pending_verification'))) ?>"
                          onclick="openRestoreModal(this)">
                    <i data-lucide="rotate-ccw" style="width:12px;height:12px;"></i> Restore Donation
                  </button>
                </div>

              <?php elseif ($d['status'] === 'pending_verification' || $d['status'] === 'needs_information'): ?>
                <!-- Approve / Reject for pending donations -->
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">

                  <!-- Approve -->
                  <form action="/CommunityDono/api/admin/approve-donation.php" method="POST" style="display: inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="donation_id" value="<?= (int)$d['id'] ?>">
                    <button type="submit" class="btn btn-primary" style="font-size: 13px; padding: 6px 16px;">
                      <i data-lucide="check" style="width:13px;height:13px;"></i> Approve Listing
                    </button>
                  </form>

                  <!-- Reject -->
                  <form action="/CommunityDono/api/admin/reject-donation.php" method="POST" style="display: inline; display: flex; gap: 6px;">
                    <?= csrfField() ?>
                    <input type="hidden" name="donation_id" value="<?= (int)$d['id'] ?>">
                    <input type="text" name="reason" placeholder="Rejection reason..." required class="form-input" style="height: 34px; font-size: 12px; width: 220px;">
                    <button type="submit" class="btn btn-danger" style="font-size: 13px; padding: 6px 14px;">Reject</button>
                  </form>

                  <!-- Archive (pending/needs-info) -->
                  <button type="button"
                          class="btn btn-outline"
                          style="font-size: 12px; padding: 6px 12px; color: #92400E; border-color: #FCD34D;"
                          data-archive-id="<?= (int)$d['id'] ?>"
                          data-archive-title="<?= e($d['title']) ?>"
                          onclick="openArchiveModal(this)">
                    <i data-lucide="archive" style="width:12px;height:12px;"></i> Archive
                  </button>
                </div>

              <?php elseif ($d['status'] === 'approved'): ?>
                <!-- Archive action for approved donations -->
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end; gap: 10px;">
                  <button type="button"
                          class="btn btn-outline"
                          style="font-size: 12px; padding: 6px 12px; color: #92400E; border-color: #FCD34D;"
                          data-archive-id="<?= (int)$d['id'] ?>"
                          data-archive-title="<?= e($d['title']) ?>"
                          onclick="openArchiveModal(this)">
                    <i data-lucide="archive" style="width:12px;height:12px;"></i> Archive Donation
                  </button>
                </div>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

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

    <!-- Header -->
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
      <div style="width: 40px; height: 40px; border-radius: 50%; background: #FEF3C7; display: grid; place-items: center; flex-shrink: 0;">
        <i data-lucide="archive" style="width:18px;height:18px;color:#D97706;"></i>
      </div>
      <h2 id="archive-dialog-title" style="font-size: 18px; font-weight: 700; margin: 0;">Archive Donation?</h2>
    </div>

    <!-- Donation title -->
    <p style="font-size: 14px; color: var(--color-text-muted); margin: 0 0 6px;">
      You are about to archive:
    </p>
    <p id="archive-dialog-donation-name"
       style="font-size: 15px; font-weight: 700; color: var(--color-text); margin: 0 0 16px;
              padding: 10px 14px; background: #F8FAFC; border-radius: 8px; border: 1px solid var(--color-border);">
    </p>

    <!-- Warning -->
    <div class="alert alert-warning" role="alert"
         style="display: flex; gap: 10px; align-items: flex-start; padding: 10px 14px;
                border-radius: 8px; font-size: 13px; margin-bottom: 20px;">
      <i data-lucide="alert-triangle" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;"></i>
      <span>This listing will be <strong>removed from public browsing</strong>.
            Its historical claim, request, and transaction records will remain fully preserved.
            You can restore this donation at any time from the Archived view.</span>
    </div>

    <!-- Archive reason form -->
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
                  placeholder="Provide a clear reason (e.g. Item damaged, donor requested removal, guidelines violation)..."
                  style="font-size: 13.5px; resize: vertical;"></textarea>
      </div>

      <!-- Actions -->
      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button type="button"
                id="archive-cancel-btn"
                class="btn btn-outline"
                onclick="closeArchiveModal()">
          Cancel
        </button>
        <button type="submit"
                id="archive-confirm-btn"
                class="btn btn-danger"
                style="display: inline-flex; align-items: center; gap: 7px;">
          <i data-lucide="archive" style="width:14px;height:14px;"></i>
          Confirm Archive
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

    <!-- Header -->
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
      <div style="width: 40px; height: 40px; border-radius: 50%; background: #DCFCE7; display: grid; place-items: center; flex-shrink: 0;">
        <i data-lucide="rotate-ccw" style="width:18px;height:18px;color:#16A34A;"></i>
      </div>
      <h2 id="restore-dialog-title" style="font-size: 18px; font-weight: 700; margin: 0;">Restore Donation?</h2>
    </div>

    <!-- Donation title -->
    <p style="font-size: 14px; color: var(--color-text-muted); margin: 0 0 6px;">
      You are restoring listing:
    </p>
    <p id="restore-dialog-donation-name"
       style="font-size: 15px; font-weight: 700; color: var(--color-text); margin: 0 0 14px;
              padding: 10px 14px; background: #F8FAFC; border-radius: 8px; border: 1px solid var(--color-border);">
    </p>

    <!-- Info message -->
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

      <!-- Actions -->
      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button type="button"
                id="restore-cancel-btn"
                class="btn btn-outline"
                onclick="closeRestoreModal()">
          Cancel
        </button>
        <button type="submit"
                id="restore-confirm-btn"
                class="btn btn-primary"
                style="display: inline-flex; align-items: center; gap: 7px; background: #16A34A; border-color: #16A34A;">
          <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i>
          Confirm Restore
        </button>
      </div>
    </form>
  </div>
</dialog>

<script>
  let lastActiveOpener = null;

  // ── Archive modal helpers ──────────────────────────────────────────────────
  const archiveDialog     = document.getElementById('archive-dialog');
  const archiveForm       = document.getElementById('archive-dialog-form');
  const archiveIdInput    = document.getElementById('archive-dialog-donation-id');
  const archiveNameEl     = document.getElementById('archive-dialog-donation-name');
  const archiveConfirmBtn = document.getElementById('archive-confirm-btn');
  const archiveReasonTxt  = document.getElementById('archive-reason-input');

  function openArchiveModal(btn) {
    lastActiveOpener = btn;
    archiveIdInput.value = btn.dataset.archiveId;
    archiveNameEl.textContent = btn.dataset.archiveTitle; // Safe textContent DOM assignment
    archiveReasonTxt.value = '';
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

  // ── Restore modal helpers ──────────────────────────────────────────────────
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

  // Close when clicking dialog backdrop
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

  // Double submit prevention
  archiveForm.addEventListener('submit', () => {
    archiveConfirmBtn.disabled = true;
    archiveConfirmBtn.textContent = 'Archiving…';
  });

  restoreForm.addEventListener('submit', () => {
    restoreConfirmBtn.disabled = true;
    restoreConfirmBtn.textContent = 'Restoring…';
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
