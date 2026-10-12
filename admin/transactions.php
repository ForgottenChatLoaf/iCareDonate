<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$adminId = (int)$_SESSION['user_id'];
$statusFilter = trim($_GET['status'] ?? 'all');
$search = trim($_GET['q'] ?? '');

// Handle update transaction status (Scope 6.4)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    verifyCsrf();
    $txId = (int)($_POST['transaction_id'] ?? 0);
    $newStatus = in_array($_POST['new_status'] ?? '', ['created', 'pending_claim', 'claimed', 'completed', 'cancelled', 'expired'], true) ? $_POST['new_status'] : '';
    $notes = trim($_POST['admin_notes'] ?? '');

    if ($txId > 0 && !empty($newStatus)) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ?');
            $stmt->execute([$txId]);
            $tx = $stmt->fetch();

            if ($tx) {
                $upd = $pdo->prepare('
                    UPDATE transactions
                    SET status = ?, admin_notes = ?, updated_at = NOW(),
                        completed_at = CASE WHEN ? = "completed" THEN NOW() ELSE completed_at END,
                        cancelled_at = CASE WHEN ? = "cancelled" THEN NOW() ELSE cancelled_at END
                    WHERE id = ?
                ');
                $upd->execute([$newStatus, $notes ?: null, $newStatus, $newStatus, $txId]);

                // If completed, update stock and request
                if ($newStatus === 'completed' && $tx['status'] !== 'completed') {
                    $dUpd = $pdo->prepare('
                        UPDATE donations
                        SET quantity_reserved = GREATEST(0, quantity_reserved - ?),
                            quantity_claimed = quantity_claimed + ?
                        WHERE id = ?
                    ');
                    $dUpd->execute([(int)$tx['quantity'], (int)$tx['quantity'], (int)$tx['donation_id']]);

                    $rUpd = $pdo->prepare('
                        UPDATE requests
                        SET status = "completed", quantity_matched = quantity_matched + ?
                        WHERE id = (SELECT request_id FROM matches WHERE id = ?)
                    ');
                    $rUpd->execute([(int)$tx['quantity'], (int)$tx['match_id']]);
                }

                logAudit($pdo, $adminId, 'admin', 'TRANSACTION_STATUS_CHANGED', 'transaction', $txId, ['status' => $tx['status']], ['status' => $newStatus], 'Admin updated transaction status: ' . $notes);

                setFlash('success', 'Transaction #' . $txId . ' status updated to ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            setFlash('error', 'Failed to update transaction status.');
        }
    }
    header('Location: ' . APP_BASE . '/admin/transactions.php' . ($statusFilter !== 'all' ? '?status=' . urlencode($statusFilter) : ''));
    exit;
}

// Handle delete transaction (Scope 6.4)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verifyCsrf();
    $txId = (int)($_POST['transaction_id'] ?? 0);
    if ($txId > 0) {
        try {
            $del = $pdo->prepare('DELETE FROM transactions WHERE id = ?');
            $del->execute([$txId]);
            logAudit($pdo, $adminId, 'admin', 'TRANSACTION_DELETED', 'transaction', $txId, null, null, 'Admin deleted transaction #' . $txId);
            setFlash('success', 'Transaction #' . $txId . ' record deleted.');
        } catch (Exception $e) {
            error_log($e->getMessage());
            setFlash('error', 'Cannot delete transaction: Linked records exist.');
        }
    }
    header('Location: ' . APP_BASE . '/admin/transactions.php' . ($statusFilter !== 'all' ? '?status=' . urlencode($statusFilter) : ''));
    exit;
}

// Counts for filter pills
$countAll = 0;
$countCreated = 0;
$countClaimed = 0;
$countCompleted = 0;
$countCancelled = 0;
try {
    $countAll = (int)$pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
    $countCreated = (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE status = "created"')->fetchColumn();
    $countClaimed = (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE status = "claimed"')->fetchColumn();
    $countCompleted = (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE status = "completed"')->fetchColumn();
    $countCancelled = (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE status IN ("cancelled","expired")')->fetchColumn();
} catch (Exception $e) {
    error_log($e->getMessage());
}

// Build query
$where = '1=1';
$params = [];
if (!empty($statusFilter) && $statusFilter !== 'all') {
    if ($statusFilter === 'cancelled_expired') {
        $where .= ' AND t.status IN ("cancelled", "expired")';
    } else {
        $where .= ' AND t.status = ?';
        $params[] = $statusFilter;
    }
}

if (!empty($search)) {
    $where .= ' AND (d.title LIKE ? OR donor.first_name LIKE ? OR donor.last_name LIKE ? OR req.first_name LIKE ? OR req.last_name LIKE ? OR donor.email LIKE ? OR req.email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$transactions = [];
try {
    $stmt = $pdo->prepare("
        SELECT t.*, d.title AS donation_title, d.unit, d.condition_type,
               c.name AS category_name,
               donor.first_name AS donor_first, donor.last_name AS donor_last, donor.email AS donor_email, donor.phone AS donor_phone, donor.barangay AS donor_barangay, donor.city AS donor_city,
               req.first_name AS req_first, req.last_name AS req_last, req.email AS req_email, req.phone AS req_phone, req.barangay AS req_barangay, req.city AS req_city,
               m.request_id
        FROM transactions t
        JOIN donations d ON t.donation_id = d.id
        LEFT JOIN categories c ON d.category_id = c.id
        JOIN users donor ON t.donor_id = donor.id
        JOIN users req ON t.requester_id = req.id
        LEFT JOIN matches m ON t.match_id = m.id
        WHERE $where
        ORDER BY t.created_at DESC
    ");
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$pageTitle = 'Transaction & Claim Monitoring — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Unified Transaction & Review Design System (Matching Requests Review) ── */
.tx-table-wrap {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg, 16px);
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(20, 33, 61, 0.05);
  margin-bottom: 24px;
}

.tx-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13.5px;
  min-width: 900px;
}

.tx-table thead th {
  background: var(--color-surface-soft, #F8FAFC);
  color: var(--color-text-muted);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 13px 16px;
  text-align: left;
  border-bottom: 1.5px solid var(--color-border);
  white-space: nowrap;
}

.tx-table tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid var(--color-border);
  color: var(--color-text);
  vertical-align: middle;
}

.tx-table tbody tr:last-child td {
  border-bottom: none;
}

.tx-table tbody tr:hover td {
  background: var(--color-surface-soft, #F8FAFC);
}

/* User pill in table */
.user-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--brand-badge-bg, #DCE8F7);
  color: var(--brand-blue, #1E4DA1);
  font-size: 12px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1px solid var(--brand-badge-border, #C4D9F8);
}

.user-name-title {
  font-weight: 600;
  color: var(--color-text);
  display: block;
}

.user-contact-sub {
  font-size: 11.5px;
  color: var(--color-text-muted);
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 1px;
}

/* Action Buttons */
.btn-review-action {
  background: var(--color-primary);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 7px 14px;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.15s ease;
  font-family: var(--font-body);
}

.btn-review-action:hover {
  background: var(--color-primary-dark, #163B7C);
  transform: translateY(-1px);
}

.btn-del-row {
  background: transparent;
  border: 1.5px solid var(--color-border);
  border-radius: 8px;
  padding: 7px 9px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  transition: all 0.15s ease;
  color: var(--color-text-muted);
}

.btn-del-row:hover {
  border-color: #DC2626;
  background: #FEF2F2;
  color: #DC2626;
}

/* ── Modern Review Details Modal ─────────────────────────── */
.tx-modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(20, 33, 61, 0.7);
  z-index: 9999;
  align-items: flex-start;
  justify-content: center;
  padding: 32px 16px;
  overflow-y: auto;
  backdrop-filter: blur(4px);
}

.tx-modal-box {
  background: var(--color-surface, #FFFFFF);
  border-radius: 20px;
  width: 100%;
  max-width: 740px;
  box-shadow: 0 24px 48px rgba(0,0,0,0.22), 0 8px 20px rgba(0,0,0,0.1);
  border: 1px solid var(--color-border);
  margin: auto;
  overflow: hidden;
  animation: modalFadeIn 0.2s ease-out;
}

@keyframes modalFadeIn {
  from { opacity: 0; transform: translateY(-10px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.tx-modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--color-surface, #FFFFFF);
  position: sticky;
  top: 0;
  z-index: 10;
}

.tx-modal-body {
  padding: 24px;
  max-height: calc(85vh - 70px);
  overflow-y: auto;
}

.modal-section-label {
  font-size: 11px;
  font-weight: 700;
  color: var(--color-primary);
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin: 0 0 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.modal-section-label::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--color-border);
}

.modal-field-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 20px;
}

.modal-field {
  background: var(--color-surface-soft, #F8FAFC);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 12px 14px;
}

.modal-field-key {
  font-size: 10.5px;
  font-weight: 700;
  color: var(--color-text-muted);
  letter-spacing: 0.05em;
  text-transform: uppercase;
  display: block;
  margin-bottom: 4px;
}

.modal-field-val {
  font-size: 13.5px;
  color: var(--color-text);
  font-weight: 600;
  line-height: 1.45;
}

/* Decision Box & Modern Status Selectors */
.decision-card {
  background: var(--color-surface-soft, #F8FAFC);
  border: 1.5px solid var(--color-border);
  border-radius: 16px;
  padding: 18px;
  margin-top: 10px;
}

.status-options-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 8px;
  margin-bottom: 16px;
}

.status-radio-option {
  position: relative;
}

.status-radio-option input[type="radio"] {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.status-radio-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 10px 8px;
  border-radius: 10px;
  border: 1.5px solid var(--color-border);
  background: var(--color-surface, #FFFFFF);
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text);
  text-align: center;
  transition: all 0.15s ease;
  user-select: none;
}

.status-radio-option input[type="radio"]:checked + .status-radio-label {
  border-color: var(--color-primary);
  background: var(--brand-badge-bg, #DCE8F7);
  color: var(--color-primary);
  box-shadow: 0 0 0 2px rgba(31, 79, 163, 0.18);
}

.status-radio-label:hover {
  border-color: var(--color-primary);
  background: var(--color-surface-soft);
}

.tx-modal-footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 10px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
  margin-top: 20px;
}
</style>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <!-- Topbar matching Request Review -->
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0;">Transaction &amp; Claim Monitoring</h1>
        <p style="font-size: 12.5px; color: var(--color-text-muted); margin: 2px 0 0;">Track active resource allocations, verify participant handovers, and confirm distribution completion.</p>
      </div>

      <!-- Filter pills with live record counts -->
      <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
        <a href="?status=all" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 12px;">
          All (<?= $countAll ?>)
        </a>
        <a href="?status=created" class="btn <?= $statusFilter === 'created' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 12px;">
          Pending Handover (<?= $countCreated ?>)
        </a>
        <a href="?status=claimed" class="btn <?= $statusFilter === 'claimed' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 12px;">
          Claimed (<?= $countClaimed ?>)
        </a>
        <a href="?status=completed" class="btn <?= $statusFilter === 'completed' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 12px;">
          Completed (<?= $countCompleted ?>)
        </a>
        <a href="?status=cancelled_expired" class="btn <?= $statusFilter === 'cancelled_expired' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 12px;">
          Cancelled/Expired (<?= $countCancelled ?>)
        </a>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <!-- Informational Verification Notice (matching Request Review banner) -->
      <div style="background: #FEF9E7; border: 1.5px solid #F6D55C; border-radius: 12px; padding: 13px 16px; margin-bottom: 20px; font-size: 13px; color: #92600A; line-height: 1.55; display: flex; align-items: flex-start; gap: 10px;">
        <i data-lucide="repeat" style="width:17px;height:17px;flex-shrink:0;margin-top:1px;color:#D97706;"></i>
        <span><strong>Handover &amp; Claim Lifecycle:</strong> Monitor active allocation claims between verified donors and community beneficiaries. Once the item has been physically received or delivered, mark the transaction as <strong>Completed</strong> to reconcile inventory and close the assistance request.</span>
      </div>

      <!-- Search & Quick Query Bar -->
      <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
        <form method="GET" action="<?= $base ?>/admin/transactions.php" style="display: flex; gap: 8px; width: 100%; max-width: 360px;">
          <?php if ($statusFilter !== 'all'): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
          <?php endif; ?>
          <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search item, donor, or requester..." class="form-input" style="height: 36px; font-size: 12.5px; flex: 1;">
          <button type="submit" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;">Search</button>
        </form>
      </div>

      <?php if (empty($transactions)): ?>
        <div class="card" style="padding: 48px 24px; text-align: center; border-radius: var(--radius-lg); border: 1px solid var(--color-border); background: var(--color-surface, #fff);">
          <div style="width:48px;height:48px;border-radius:50%;background:var(--color-surface-soft);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <i data-lucide="repeat" style="width:24px;height:24px;color:var(--color-primary);"></i>
          </div>
          <h3 style="font-size: 16px; font-weight: 700; color: var(--color-text); margin: 0 0 6px;">No transactions found</h3>
          <p style="color: var(--color-text-muted); font-size: 13.5px; margin: 0;">There are currently no transactions matching the selected criteria.</p>
        </div>
      <?php else: ?>
        <!-- Modern Transaction Table Container -->
        <div class="tx-table-wrap">
          <table class="tx-table">
            <thead>
              <tr>
                <th>Tx ID</th>
                <th>Donation Resource</th>
                <th>Matched Qty</th>
                <th>Donor Info</th>
                <th>Requester Info</th>
                <th>Deadline &amp; Method</th>
                <th>Status</th>
                <th style="text-align: right;">Review Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($transactions as $tx): ?>
                <?php
                  $donorInitial = strtoupper(substr($tx['donor_first'] ?: 'D', 0, 1));
                  $reqInitial = strtoupper(substr($tx['req_first'] ?: 'R', 0, 1));
                  $isNearDeadline = strtotime($tx['claim_deadline']) < (time() + 86400 * 2) && in_array($tx['status'], ['created', 'pending_claim'], true);
                ?>
                <tr>
                  <td>
                    <span style="font-weight: 700; color: var(--color-primary); font-family: monospace; font-size: 13px;">#<?= (int)$tx['id'] ?></span>
                    <div style="font-size: 11px; color: var(--color-text-muted); margin-top: 2px;">Match #<?= (int)$tx['match_id'] ?></div>
                  </td>
                  <td>
                    <strong style="font-size: 14px; color: var(--color-text);"><?= e($tx['donation_title']) ?></strong>
                    <div style="font-size: 11.5px; color: var(--color-text-muted); margin-top: 2px;">
                      <?= e($tx['category_name'] ?? 'Donation Item') ?> · ID: #<?= (int)$tx['donation_id'] ?>
                    </div>
                  </td>
                  <td>
                    <span style="font-weight: 700; font-size: 14px;"><?= (int)$tx['quantity'] ?></span>
                    <span style="font-size: 12px; color: var(--color-text-muted);"><?= e($tx['unit']) ?></span>
                  </td>
                  <td>
                    <div class="user-cell">
                      <span class="user-avatar"><?= $donorInitial ?></span>
                      <div>
                        <span class="user-name-title"><?= e($tx['donor_first'] . ' ' . $tx['donor_last']) ?></span>
                        <span class="user-contact-sub"><i data-lucide="phone" style="width:11px;height:11px;"></i> <?= e($tx['donor_phone'] ?? 'No phone') ?></span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="user-cell">
                      <span class="user-avatar" style="background:#FEF3C7; color:#B45309; border-color:#FDE68A;"><?= $reqInitial ?></span>
                      <div>
                        <span class="user-name-title"><?= e($tx['req_first'] . ' ' . $tx['req_last']) ?></span>
                        <span class="user-contact-sub"><i data-lucide="phone" style="width:11px;height:11px;"></i> <?= e($tx['req_phone'] ?? 'No phone') ?></span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div style="font-size: 13px; font-weight: 500; <?= $isNearDeadline ? 'color:#B91C1C;font-weight:700;' : '' ?>">
                      <?= date('M d, Y', strtotime($tx['claim_deadline'])) ?>
                    </div>
                    <div style="font-size: 11.5px; color: var(--color-text-muted); text-transform: capitalize; margin-top: 2px;">
                      <i data-lucide="<?= $tx['pickup_method'] === 'delivery' ? 'truck' : 'map-pin' ?>" style="width:11px;height:11px;display:inline-block;vertical-align:middle;"></i>
                      <?= e($tx['pickup_method']) ?>
                    </div>
                  </td>
                  <td>
                    <?= renderBadge($tx['status']) ?>
                  </td>
                  <td style="text-align: right;">
                    <div style="display: inline-flex; gap: 6px; align-items: center;">
                      <!-- Review Details Button -->
                      <button type="button"
                              class="btn-review-action"
                              onclick='openTxModal(<?= json_encode($tx, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);'
                              title="Review transaction details & update status">
                        <i data-lucide="file-text" style="width:13px;height:13px;"></i>
                        <span>Review</span>
                      </button>

                      <!-- Delete Action -->
                      <form action="<?= $base ?>/admin/transactions.php" method="POST" style="display: inline;" onsubmit="return confirm('Delete transaction record #<?= $tx['id'] ?>? Historical audit logs will record this action.');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="transaction_id" value="<?= (int)$tx['id'] ?>">
                        <button type="submit" class="btn-del-row" title="Delete transaction record">
                          <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     Modern Transaction Review Details Modal Dialog
     ══════════════════════════════════════════════════════════════════════════ -->
<div id="tx-modal" class="tx-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-title">
  <div class="tx-modal-box">
    
    <!-- Modal Header -->
    <div class="tx-modal-header">
      <div style="display: flex; align-items: center; gap: 10px;">
        <div style="width:34px;height:34px;border-radius:10px;background:var(--brand-badge-bg);display:flex;align-items:center;justify-content:center;color:var(--color-primary);">
          <i data-lucide="repeat" style="width:18px;height:18px;"></i>
        </div>
        <div>
          <h2 id="modal-title" style="font-size: 17px; font-weight: 700; color: var(--color-text); margin: 0;">Transaction Review</h2>
          <div id="modal-subtitle" style="font-size: 12px; color: var(--color-text-muted); margin-top: 1px;">Transaction Record Details</div>
        </div>
      </div>
      <button type="button" onclick="closeTxModal();" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--color-text-muted); line-height: 1;">&times;</button>
    </div>

    <div class="tx-modal-body">
      
      <!-- Section 1: Resource & Allocation Summary -->
      <div class="modal-section-label">Allocation &amp; Resource Details</div>
      <div class="modal-field-grid">
        <div class="modal-field">
          <span class="modal-field-key">Donation Resource</span>
          <span class="modal-field-val" id="m-item-title">-</span>
        </div>
        <div class="modal-field">
          <span class="modal-field-key">Allocated Quantity</span>
          <span class="modal-field-val" id="m-item-qty">-</span>
        </div>
        <div class="modal-field">
          <span class="modal-field-key">Fulfillment Logistics</span>
          <span class="modal-field-val" id="m-item-method">-</span>
        </div>
        <div class="modal-field">
          <span class="modal-field-key">Claim Deadline</span>
          <span class="modal-field-val" id="m-item-deadline">-</span>
        </div>
      </div>

      <!-- Section 2: Participants Information -->
      <div class="modal-section-label">Participant Handover Information</div>
      <div class="modal-field-grid">
        <!-- Donor Column -->
        <div class="modal-field" style="border-left: 3px solid var(--brand-blue);">
          <span class="modal-field-key">Donor (Provider)</span>
          <div class="modal-field-val" id="m-donor-name" style="margin-bottom: 4px;">-</div>
          <div style="font-size: 12px; color: var(--color-text-muted); line-height: 1.45;">
            <div><i data-lucide="mail" style="width:11px;height:11px;"></i> <span id="m-donor-email">-</span></div>
            <div><i data-lucide="phone" style="width:11px;height:11px;"></i> <span id="m-donor-phone">-</span></div>
            <div><i data-lucide="map-pin" style="width:11px;height:11px;"></i> <span id="m-donor-loc">-</span></div>
          </div>
        </div>

        <!-- Requester Column -->
        <div class="modal-field" style="border-left: 3px solid #D97706;">
          <span class="modal-field-key">Beneficiary / Requester</span>
          <div class="modal-field-val" id="m-req-name" style="margin-bottom: 4px;">-</div>
          <div style="font-size: 12px; color: var(--color-text-muted); line-height: 1.45;">
            <div><i data-lucide="mail" style="width:11px;height:11px;"></i> <span id="m-req-email">-</span></div>
            <div><i data-lucide="phone" style="width:11px;height:11px;"></i> <span id="m-req-phone">-</span></div>
            <div><i data-lucide="map-pin" style="width:11px;height:11px;"></i> <span id="m-req-loc">-</span></div>
          </div>
        </div>
      </div>

      <!-- Section 3: Timeline & Schedule -->
      <div class="modal-section-label">Timeline &amp; Schedule</div>
      <div class="modal-field-grid">
        <div class="modal-field">
          <span class="modal-field-key">Created Date</span>
          <span class="modal-field-val" id="m-date-created">-</span>
        </div>
        <div class="modal-field">
          <span class="modal-field-key">Completed Date</span>
          <span class="modal-field-val" id="m-date-completed">-</span>
        </div>
      </div>

      <!-- Section 4: Proper Design for Review Decision & Status Update -->
      <div class="decision-card">
        <div class="modal-section-label" style="color: var(--color-primary); margin-top: 0;">
          Administrative Decision &amp; Status Control
        </div>
        
        <form action="<?= $base ?>/admin/transactions.php" method="POST" id="tx-decision-form">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="update_status">
          <input type="hidden" id="modal-tx-id" name="transaction_id" value="">

          <!-- Visual Status Selector Radio Tiles -->
          <label style="font-size: 12px; font-weight: 700; color: var(--color-text); display: block; margin-bottom: 8px;">
            Set Lifecycle Status:
          </label>
          <div class="status-options-grid">
            <div class="status-radio-option">
              <input type="radio" name="new_status" id="st-created" value="created">
              <label for="st-created" class="status-radio-label">
                <span>Pending Handover</span>
              </label>
            </div>
            <div class="status-radio-option">
              <input type="radio" name="new_status" id="st-claimed" value="claimed">
              <label for="st-claimed" class="status-radio-label">
                <span>Claimed</span>
              </label>
            </div>
            <div class="status-radio-option">
              <input type="radio" name="new_status" id="st-completed" value="completed">
              <label for="st-completed" class="status-radio-label">
                <span style="color:#16A34A;">Completed</span>
              </label>
            </div>
            <div class="status-radio-option">
              <input type="radio" name="new_status" id="st-cancelled" value="cancelled">
              <label for="st-cancelled" class="status-radio-label">
                <span style="color:#DC2626;">Cancelled</span>
              </label>
            </div>
            <div class="status-radio-option">
              <input type="radio" name="new_status" id="st-expired" value="expired">
              <label for="st-expired" class="status-radio-label">
                <span style="color:#6B7280;">Expired</span>
              </label>
            </div>
          </div>

          <!-- Admin Notes -->
          <div class="form-group" style="margin-bottom: 12px;">
            <label class="form-label" for="modal-tx-notes" style="font-size: 12px; font-weight: 700;">
              Admin Audit Notes:
            </label>
            <textarea id="modal-tx-notes"
                      name="admin_notes"
                      class="form-input"
                      rows="3"
                      placeholder="Add moderation notes regarding physical verification, handover schedule, or cancellation reason..."
                      style="font-size: 13px; line-height: 1.5; resize: vertical;"></textarea>
          </div>

          <div class="tx-modal-footer">
            <button type="button" onclick="closeTxModal();" class="btn btn-outline" style="font-size: 13px; padding: 8px 16px;">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" style="font-size: 13px; padding: 8px 20px; font-weight: 600;">
              Save Transaction Decision
            </button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
function openTxModal(tx) {
  if (!tx) return;
  document.getElementById('modal-tx-id').value = tx.id;
  document.getElementById('modal-title').textContent = 'Transaction Review #' + tx.id;
  document.getElementById('modal-subtitle').textContent = (tx.donation_title || 'Resource Allocation') + ' · Match #' + tx.match_id;

  document.getElementById('m-item-title').textContent = (tx.donation_title || 'N/A') + (tx.category_name ? ' (' + tx.category_name + ')' : '');
  document.getElementById('m-item-qty').textContent = tx.quantity + ' ' + (tx.unit || 'units');
  document.getElementById('m-item-method').textContent = (tx.pickup_method ? tx.pickup_method.charAt(0).toUpperCase() + tx.pickup_method.slice(1) : 'Pickup') + (tx.pickup_address ? ' — ' + tx.pickup_address : '');
  document.getElementById('m-item-deadline').textContent = tx.claim_deadline ? new Date(tx.claim_deadline).toLocaleDateString(undefined, {month: 'short', day: 'numeric', year: 'numeric'}) : 'No deadline';

  document.getElementById('m-donor-name').textContent = (tx.donor_first || '') + ' ' + (tx.donor_last || '');
  document.getElementById('m-donor-email').textContent = tx.donor_email || 'N/A';
  document.getElementById('m-donor-phone').textContent = tx.donor_phone || 'N/A';
  document.getElementById('m-donor-loc').textContent = (tx.donor_barangay ? tx.donor_barangay + ', ' : '') + (tx.donor_city || 'Metro Manila');

  document.getElementById('m-req-name').textContent = (tx.req_first || '') + ' ' + (tx.req_last || '');
  document.getElementById('m-req-email').textContent = tx.req_email || 'N/A';
  document.getElementById('m-req-phone').textContent = tx.req_phone || 'N/A';
  document.getElementById('m-req-loc').textContent = (tx.req_barangay ? tx.req_barangay + ', ' : '') + (tx.req_city || 'Metro Manila');

  document.getElementById('m-date-created').textContent = tx.created_at ? new Date(tx.created_at).toLocaleString() : 'N/A';
  document.getElementById('m-date-completed').textContent = tx.completed_at ? new Date(tx.completed_at).toLocaleString() : (tx.status === 'completed' ? 'Completed' : 'Pending completion');

  // Radio selection
  var statusRadio = document.querySelector('input[name="new_status"][value="' + tx.status + '"]');
  if (statusRadio) {
    statusRadio.checked = true;
  } else {
    var defaultRadio = document.getElementById('st-created');
    if (defaultRadio) defaultRadio.checked = true;
  }

  document.getElementById('modal-tx-notes').value = tx.admin_notes || '';

  var modal = document.getElementById('tx-modal');
  modal.style.display = 'flex';
  if (window.lucide) { lucide.createIcons(); }
}

function closeTxModal() {
  document.getElementById('tx-modal').style.display = 'none';
}

// Close when clicking background outside modal box
document.getElementById('tx-modal').addEventListener('click', function(e) {
  if (e.target === this) { closeTxModal(); }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
