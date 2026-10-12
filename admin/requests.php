<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$filter = trim($_GET['status'] ?? 'pending');

$where = '1=1';
$params = [];
if (!empty($filter) && $filter !== 'all') {
    if ($filter === 'pending') {
        $where = 'r.status IN ("submitted", "proof_review")';
    } else {
        $where = 'r.status = ?';
        $params[] = $filter;
    }
}

// Counts for filter tabs
$countPending = 0;
$countMatched = 0;
$countCompleted = 0;
$countAll = 0;

try {
    $countPending   = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status IN ('submitted', 'proof_review')")->fetchColumn();
    $countMatched   = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'matched'")->fetchColumn();
    $countCompleted = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'completed'")->fetchColumn();
    $countAll       = (int)$pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$stmt = $pdo->prepare("
    SELECT r.*, 
           d.title AS donation_title, d.quantity_total, d.quantity_reserved, d.quantity_claimed, d.unit,
           COALESCE(c.name, 'General') AS category_name,
           u.first_name, u.last_name, u.email AS requester_email, u.phone AS requester_phone,
           u.city AS requester_city, u.barangay AS requester_barangay,
           (SELECT file_path FROM request_proofs WHERE request_id = r.id LIMIT 1) AS proof_path,
           (SELECT file_name FROM request_proofs WHERE request_id = r.id LIMIT 1) AS proof_orig_name,
           (SELECT description FROM request_proofs WHERE request_id = r.id LIMIT 1) AS proof_desc
    FROM requests r
    JOIN donations d ON r.donation_id = d.id
    LEFT JOIN categories c ON d.category_id = c.id
    JOIN users u ON r.requester_id = u.id
    WHERE $where
    ORDER BY r.submitted_at DESC
");
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$adminName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
if (empty($adminName)) { $adminName = 'Staff Admin 1'; }

$pageTitle = 'Request & Proof Review — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ═══ Request & Proof Review — Brand Design System ═══ */

/* Card-based request table */
.req-table-wrap { border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border); }
.req-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.req-table th {
  padding: 13px 16px; font-size: 11px; font-weight: 700; color: var(--color-text-muted);
  letter-spacing: 0.06em; text-transform: uppercase; background: var(--color-surface-soft);
  border-bottom: 1px solid var(--color-border); white-space: nowrap;
}
.req-table td {
  padding: 14px 16px; border-bottom: 1px solid var(--color-border);
  vertical-align: middle; color: var(--color-text); background: var(--color-surface);
}
.req-table tr:last-child td { border-bottom: none; }
.req-table tbody tr:hover td { background: var(--color-surface-soft); }

/* Urgency Pills */
.urg-high  { background: #FFF0EE; color: #C0392B; border: 1.5px solid #F9A99C; font-size: 11.5px; font-weight: 700; padding: 3px 11px; border-radius: 999px; display: inline-block; white-space: nowrap; }
.urg-med   { background: #FEF9E7; color: #B7770D; border: 1.5px solid #F6D55C; font-size: 11.5px; font-weight: 700; padding: 3px 11px; border-radius: 999px; display: inline-block; white-space: nowrap; }
.urg-low   { background: #EEF4FF; color: #1F4FA3; border: 1.5px solid #B6CDFC; font-size: 11.5px; font-weight: 700; padding: 3px 11px; border-radius: 999px; display: inline-block; white-space: nowrap; }

/* Proof Status Pills */
.proof-none      { background: #F8FAFC; color: #94A3B8; border: 1.5px solid #E2E8F0; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 999px; display: inline-block; }
.proof-uploaded  { background: var(--color-surface-soft); color: var(--color-text-muted); border: 1.5px solid var(--color-border); font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 999px; display: inline-block; }
.proof-verified  { background: #EEF4FF; color: #1F4FA3; border: 1.5px solid #BCD0FB; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 999px; display: inline-block; }
.proof-info      { background: #FEF9E7; color: #B7770D; border: 1.5px solid #F6D55C; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 999px; display: inline-block; }

/* Status Pills */
.stat-pending    { background: #F8F9FC; color: #5A6781; border: 1.5px solid var(--color-border); font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 999px; display: inline-block; }
.stat-approved   { background: #EEF4FF; color: #1F4FA3; border: 1.5px solid #BCD0FB; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 999px; display: inline-block; }
.stat-info       { background: #FEF9E7; color: #B7770D; border: 1.5px solid #F6D55C; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 999px; display: inline-block; }
.stat-rejected   { background: #FFF0EE; color: #C0392B; border: 1.5px solid #F9A99C; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 999px; display: inline-block; }
.stat-matched    { background: #F0F4FF; color: #2563EB; border: 1.5px solid #93C5FD; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 999px; display: inline-block; }
.stat-completed  { background: #EEF4FF; color: #1F4FA3; border: 1.5px solid #1F4FA3; font-size: 11.5px; font-weight: 700; padding: 3px 11px; border-radius: 999px; display: inline-block; }

/* Action Buttons */
.btn-review {
  background: var(--color-primary); color: #fff; border: none; border-radius: 8px;
  padding: 7px 14px; font-size: 12.5px; font-weight: 600; cursor: pointer;
  display: inline-flex; align-items: center; gap: 5px; transition: background 0.15s;
  font-family: var(--font-body);
}
.btn-review:hover { background: var(--color-primary-dark, #163e85); }
.btn-del-row {
  background: transparent; border: 1.5px solid var(--color-border); border-radius: 8px;
  padding: 7px 9px; cursor: pointer; display: inline-flex; align-items: center;
  transition: border-color 0.15s, background 0.15s;
}
.btn-del-row:hover { border-color: #DC2626; background: #FEF2F2; }

/* Modal */
.req-modal-overlay {
  display: none; position: fixed; inset: 0; background: rgba(20, 33, 61, 0.65);
  z-index: 9999; align-items: flex-start; justify-content: center; padding: 32px 16px;
  overflow-y: auto;
}
.req-modal-box {
  background: var(--color-surface); border-radius: 20px; width: 100%; max-width: 700px;
  box-shadow: 0 24px 48px rgba(0,0,0,0.18), 0 8px 20px rgba(0,0,0,0.08);
  border: 1px solid var(--color-border); margin: auto;
}
.req-modal-header {
  padding: 18px 24px; border-bottom: 1px solid var(--color-border); display: flex;
  justify-content: space-between; align-items: center; background: var(--color-surface);
  border-radius: 20px 20px 0 0; position: sticky; top: 0; z-index: 10;
}
.req-modal-body { padding: 20px 24px; }
.modal-section-label {
  font-size: 10.5px; font-weight: 700; color: var(--color-primary); letter-spacing: 0.08em;
  text-transform: uppercase; margin: 0 0 10px; display: flex; align-items: center; gap: 6px;
}
.modal-section-label::after {
  content: ''; flex: 1; height: 1px; background: var(--color-border);
}
.modal-field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; margin-bottom: 18px; }
.modal-field {
  background: var(--color-surface-soft); border: 1px solid var(--color-border);
  border-radius: 10px; padding: 10px 13px;
}
.modal-field-key {
  font-size: 10px; font-weight: 700; color: var(--color-text-muted); letter-spacing: 0.05em;
  text-transform: uppercase; display: block; margin-bottom: 3px;
}
.modal-field-val { font-size: 13.5px; color: var(--color-text); font-weight: 500; line-height: 1.45; }
.modal-proof-box {
  background: linear-gradient(135deg, var(--color-tint-teal, #EEF4FF) 0%, var(--color-surface-soft) 100%);
  border: 1px solid var(--color-primary); border-radius: 12px; padding: 14px 16px;
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; gap: 12px;
}
.modal-verify-box {
  background: var(--color-surface-soft); border: 1px solid var(--color-border);
  border-radius: 12px; padding: 16px; margin-bottom: 16px;
}
.modal-close-btn {
  background: var(--color-surface-soft); border: 1px solid var(--color-border); border-radius: 8px;
  padding: 6px 12px; font-size: 13px; font-weight: 600; cursor: pointer; color: var(--color-text);
  display: flex; align-items: center; gap: 5px; transition: background 0.15s;
}
.modal-close-btn:hover { background: var(--color-border); }
.modal-footer { display: flex; justify-content: flex-end; align-items: center; gap: 8px; flex-wrap: wrap; padding-top: 14px; border-top: 1px solid var(--color-border); }
</style>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0;">Request &amp; Proof Review</h1>
        <p style="font-size: 12.5px; color: var(--color-text-muted); margin: 2px 0 0;">Verify community assistance requests, inspect supporting proof documents, and coordinate verification interviews.</p>
      </div>
      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="?status=pending" class="btn <?= $filter === 'pending' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 12px;">
          Pending Review (<?= $countPending ?>)
        </a>
        <a href="?status=matched" class="btn <?= $filter === 'matched' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 12px;">
          Matched (<?= $countMatched ?>)
        </a>
        <a href="?status=completed" class="btn <?= $filter === 'completed' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 12px;">
          Completed (<?= $countCompleted ?>)
        </a>
        <a href="?status=all" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 12px;">
          All Records (<?= $countAll ?>)
        </a>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <!-- Verification Notice -->
      <div style="background: #FEF9E7; border: 1.5px solid #F6D55C; border-radius: 12px; padding: 13px 16px; margin-bottom: 20px; font-size: 13px; color: #92600A; line-height: 1.55; display: flex; align-items: flex-start; gap: 10px;">
        <i data-lucide="alert-triangle" style="width:17px;height:17px;flex-shrink:0;margin-top:1px;"></i>
        <span><strong>Review Required:</strong> Verify the requester's proof of need before approval. The administrator decides whether an interview is needed. Approved requests without an interview will trigger an automated pickup notification.</span>
      </div>

      <?php if (empty($requests)): ?>
        <div class="card" style="padding: 48px 24px; text-align: center; border-radius: var(--radius-lg); border: 1px solid var(--color-border); background:#fff;">
          <div style="width:48px;height:48px;border-radius:50%;background:var(--color-tint-teal);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <i data-lucide="inbox" style="width:24px;height:24px;color:var(--color-primary);"></i>
          </div>
          <h3 style="font-size: 16px; font-weight: 700; color: var(--color-text); margin: 0 0 6px;">No requests found</h3>
          <p style="color: var(--color-text-muted); font-size: 13.5px; margin: 0;">There are currently no assistance requests matching the "<?= e(ucfirst($filter)) ?>" filter.</p>
        </div>
      <?php else: ?>
        <!-- Requests & Proof Review Table -->
        <div class="req-table-wrap">
          <table class="req-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Requester</th>
                <th>Item Requested</th>
                <th>Qty</th>
                <th>Urgency</th>
                <th>Proof</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($requests as $r): ?>
                <?php
                  $urg = strtolower($r['urgency_level'] ?? 'medium');
                  $urgClass = $urg === 'high' ? 'urg-high' : ($urg === 'low' ? 'urg-low' : 'urg-med');
                  $urgLabel = $urg === 'high' ? 'High' : ($urg === 'low' ? 'Low' : 'Medium');

                  // Proof status
                  $proofStatus = 'Uploaded';
                  $proofClass = 'proof-uploaded';
                  if ($r['status'] === 'approved') {
                      $proofStatus = 'Verified'; $proofClass = 'proof-verified';
                  } elseif ($r['status'] === 'needs_information') {
                      $proofStatus = 'Needs Info'; $proofClass = 'proof-info';
                  } elseif (empty($r['proof_path']) && empty($r['proof_orig_name'])) {
                      $proofStatus = 'None'; $proofClass = 'proof-none';
                  }

                  // Status pill
                  $statusLabel = 'Pending';
                  $statusClass = 'stat-pending';
                  if ($r['status'] === 'approved') { $statusLabel = 'Approved'; $statusClass = 'stat-approved'; }
                  elseif ($r['status'] === 'needs_information') { $statusLabel = 'Needs Info'; $statusClass = 'stat-info'; }
                  elseif ($r['status'] === 'rejected') { $statusLabel = 'Rejected'; $statusClass = 'stat-rejected'; }
                  elseif ($r['status'] === 'matched') { $statusLabel = 'Matched'; $statusClass = 'stat-matched'; }
                  elseif ($r['status'] === 'completed') { $statusLabel = 'Completed'; $statusClass = 'stat-completed'; }

                  // Address and phone formatting
                  $delivAddr = !empty($r['delivery_address']) ? $r['delivery_address'] : trim(($r['requester_barangay'] ? $r['requester_barangay'] . ', ' : '') . ($r['requester_city'] ?? 'Metro Manila'));
                  if (empty($delivAddr)) { $delivAddr = '123 Community Lane, Tondo Manila'; }
                  $delivPhone = !empty($r['contact_number']) ? $r['contact_number'] : ($r['requester_phone'] ?? '0941234512');

                  // Quantity label
                  $qtyDisplay = (int)$r['quantity_requested'] . ' ' . e($r['unit'] ?: 'items');

                  // Prepare JSON for Modal
                  $modalData = [
                      'id'           => (int)$r['id'],
                      'item'         => $r['donation_title'],
                      'category'     => $r['category_name'],
                      'qty'          => $qtyDisplay,
                      'urgency'      => $urgLabel,
                      'date'         => date('Y-m-d h:i A', strtotime($r['submitted_at'])),
                      'status'       => $statusLabel,
                      'reason'       => $r['purpose'],
                      'name'         => $r['first_name'] . ' ' . $r['last_name'],
                      'email'        => $r['requester_email'],
                      'phone'        => $delivPhone,
                      'address'      => $delivAddr,
                      'proof_file'   => $r['proof_orig_name'] ?: ('cert_r-' . $r['id'] . '.pdf'),
                      'proof_path'   => $r['proof_path'] ?: '',
                      'proof_status' => $proofStatus,
                      'admin_notes'  => $r['admin_notes'] ?? '',
                      'interview'    => ($r['interview_status'] === 'scheduled' || $r['interview_status'] === 'pending') ? 1 : 0
                  ];
                ?>
                <tr>
                  <td style="font-weight: 700; font-size: 13px; color: var(--color-primary);">#R-<?= (int)$r['id'] ?></td>
                  <td>
                    <div style="font-weight: 600; color: var(--color-text);"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></div>
                    <div style="font-size: 11.5px; color: var(--color-text-muted); margin-top: 2px;"><?= e($r['requester_email']) ?></div>
                  </td>
                  <td style="max-width: 180px;">
                    <div style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($r['donation_title']) ?></div>
                    <div style="font-size: 11.5px; color: var(--color-text-muted);"><?= e($r['category_name']) ?></div>
                  </td>
                  <td style="white-space: nowrap; font-weight: 600;"><?= $qtyDisplay ?></td>
                  <td><span class="<?= $urgClass ?>"><?= $urgLabel ?></span></td>
                  <td><span class="<?= $proofClass ?>"><?= $proofStatus ?></span></td>
                  <td><span class="<?= $statusClass ?>"><?= $statusLabel ?></span></td>
                  <td>
                    <div style="display: flex; gap: 6px; align-items: center; white-space: nowrap;">
                      <button type="button" class="btn-review" onclick='openRequestModal(<?= htmlspecialchars(json_encode($modalData), ENT_QUOTES) ?>)'>
                        <i data-lucide="eye" style="width:13px;height:13px;"></i> Review
                      </button>
                      <button type="button" class="btn-del-row" onclick="deleteRequestRecord(<?= (int)$r['id'] ?>)" title="Delete Request Record">
                        <i data-lucide="trash-2" style="width:14px;height:14px;color:#DC2626;"></i>
                      </button>
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

<!-- Modal Overlay: Request & Proof Details (Wireframe Format) -->
<div id="request-details-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center; padding:16px;">
  <div style="background:#fff; border-radius:6px; width:100%; max-width:680px; max-height:92vh; overflow-y:auto; box-shadow:0 20px 25px -5px rgba(0,0,0,0.25), 0 10px 10px -5px rgba(0,0,0,0.1); border:1px solid #94A3B8;">
    
    <!-- Modal Header -->
    <div style="padding:12px 18px; border-bottom:1px solid #CBD5E1; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; background:#fff; z-index:10;">
      <h2 id="modal-title" style="font-size:15px; font-weight:700; color:#0F172A; margin:0;">Request &amp; Proof Details &mdash; R-XXXX</h2>
      <button type="button" onclick="closeRequestModal()" style="background:#fff; border:1px solid #333; font-size:12px; font-weight:700; padding:2px 7px; border-radius:2px; cursor:pointer; line-height:1;" aria-label="Close modal">X</button>
    </div>

    <!-- Modal Form & Content -->
    <form id="modal-decision-form" action="<?= $base ?>/api/admin/decide-request.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="request_id" id="modal-req-id" value="">
      <input type="hidden" name="decision" id="modal-decision" value="">

      <div style="padding:16px 20px;">
        
        <!-- Section 1: Request Information -->
        <div style="font-weight:700; font-size:12.5px; color:#0F172A; margin-bottom:8px;">Request Information</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px 12px; margin-bottom:16px;">
          <div class="modal-field-box">
            <span class="modal-field-label">ITEM REQUESTED</span>
            <div class="modal-field-val" id="m-item-requested"></div>
          </div>
          <div class="modal-field-box">
            <span class="modal-field-label">CATEGORY</span>
            <div class="modal-field-val" id="m-category"></div>
          </div>
          <div class="modal-field-box">
            <span class="modal-field-label">QUANTITY</span>
            <div class="modal-field-val" id="m-quantity"></div>
          </div>
          <div class="modal-field-box">
            <span class="modal-field-label">URGENCY</span>
            <div class="modal-field-val" id="m-urgency"></div>
          </div>
          <div class="modal-field-box">
            <span class="modal-field-label">SUBMISSION DATE</span>
            <div class="modal-field-val" id="m-submission-date"></div>
          </div>
          <div class="modal-field-box">
            <span class="modal-field-label">STATUS</span>
            <div class="modal-field-val" id="m-status"></div>
          </div>
          <div class="modal-field-box" style="grid-column: span 2; min-height: 54px;">
            <span class="modal-field-label">REASON / JUSTIFICATION</span>
            <div class="modal-field-val" id="m-reason" style="line-height: 1.45; white-space: pre-wrap;"></div>
          </div>
        </div>

        <!-- Section 2: Requester Information -->
        <div style="font-weight:700; font-size:12.5px; color:#0F172A; margin-bottom:8px;">Requester Information</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px 12px; margin-bottom:16px;">
          <div class="modal-field-box">
            <span class="modal-field-label">NAME</span>
            <div class="modal-field-val" id="m-requester-name"></div>
          </div>
          <div class="modal-field-box">
            <span class="modal-field-label">EMAIL</span>
            <div class="modal-field-val" id="m-requester-email"></div>
          </div>
          <div class="modal-field-box">
            <span class="modal-field-label">CONTACT NUMBER</span>
            <div class="modal-field-val" id="m-requester-phone"></div>
          </div>
          <div class="modal-field-box">
            <span class="modal-field-label">DELIVERY ADDRESS</span>
            <div class="modal-field-val" id="m-requester-address"></div>
          </div>
        </div>

        <!-- Section 3: Supporting Proof Document -->
        <div style="font-weight:700; font-size:12.5px; color:#0F172A; margin-bottom:8px;">Supporting Proof Document</div>
        <div style="border:1px dashed #94A3B8; border-radius:4px; padding:16px 14px; background:#F8FAFC; text-align:center; font-size:12.5px; color:#334155; margin-bottom:16px;">
          <div id="m-proof-info"></div>
          <div id="m-proof-link-wrap" style="margin-top:6px;"></div>
        </div>

        <!-- Section 4: Verification & Interview Review -->
        <div style="border:1px solid #CBD5E1; background:#F8FAFC; border-radius:4px; padding:12px 14px; margin-bottom:16px;">
          <div style="font-weight:700; font-size:11.5px; color:#0F172A; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
            <i data-lucide="check-square" style="width:14px; height:14px; color:var(--color-primary);"></i>
            Verification &amp; Interview Review
          </div>
          <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:#1E293B; margin-bottom:10px; cursor:pointer;">
            <input type="checkbox" id="m-require-interview" name="require_interview" value="1">
            Require verification interview with staff before final approval
          </label>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
            <div>
              <label style="display:block; font-size:11px; color:#475569; margin-bottom:4px; font-weight:600;">Interview Date &amp; Time (if scheduling)</label>
              <input type="datetime-local" id="m-interview-datetime" name="interview_datetime" class="form-input" style="width:100%; height:34px; font-size:12px; padding:4px 8px; border:1px solid #94A3B8; border-radius:3px;" value="<?= date('Y-m-d\T10:00', strtotime('+1 day')) ?>">
            </div>
            <div>
              <label style="display:block; font-size:11px; color:#475569; margin-bottom:4px; font-weight:600;">Assigned Staff</label>
              <input type="text" id="m-assigned-staff" name="assigned_staff" class="form-input" style="width:100%; height:34px; font-size:12px; padding:4px 8px; border:1px solid #94A3B8; border-radius:3px;" value="<?= e($adminName) ?>">
            </div>
          </div>
          <div>
            <label style="display:block; font-size:11px; color:#475569; margin-bottom:4px; font-weight:600;">Staff Notes / Instructions</label>
            <textarea id="m-staff-notes" name="staff_notes" rows="2" style="width:100%; font-size:12px; padding:6px 8px; border:1px solid #94A3B8; border-radius:3px; font-family:inherit;" placeholder="Record verification findings or requester instructions..."></textarea>
          </div>
        </div>

        <!-- Modal Footer Actions (Aligned Right) -->
        <div style="display:flex; justify-content:flex-end; align-items:center; gap:8px; flex-wrap:wrap; padding-top:10px; border-top:1px solid #E2E8F0;">
          <button type="button" class="btn btn-outline" style="font-size:12px; padding:6px 14px;" onclick="closeRequestModal()">Close</button>
          <button type="button" class="btn btn-outline" style="font-size:12px; padding:6px 14px;" onclick="submitDecision('Schedule Interview')">&#9654; Schedule Interview</button>
          <button type="button" class="btn btn-outline" style="font-size:12px; padding:6px 14px;" onclick="submitDecision('Needs Info')">Request More Proof</button>
          <button type="button" class="btn btn-outline" style="font-size:12px; padding:6px 14px; color:#DC2626; border-color:#FCA5A5;" onclick="submitDecision('Rejected')">Reject Request</button>
          <button type="button" class="btn btn-primary" style="font-size:12px; padding:6px 16px;" onclick="submitDecision('Approved')">&#10003; Approve Request</button>
        </div>

      </div>
    </form>

  </div>
</div>

<script>
function openRequestModal(r) {
  document.getElementById('modal-title').textContent = 'Request & Proof Details \u2014 R-' + r.id;
  document.getElementById('modal-req-id').value = r.id;
  document.getElementById('modal-decision').value = '';

  document.getElementById('m-item-requested').textContent = r.item || 'N/A';
  document.getElementById('m-category').textContent = r.category || 'General';
  document.getElementById('m-quantity').textContent = r.qty || '1';
  document.getElementById('m-urgency').textContent = r.urgency || 'Normal';
  document.getElementById('m-submission-date').textContent = r.date || 'N/A';
  document.getElementById('m-status').textContent = r.status || 'Pending';
  document.getElementById('m-reason').textContent = r.reason || 'No justification provided.';

  document.getElementById('m-requester-name').textContent = r.name || 'Anonymous';
  document.getElementById('m-requester-email').textContent = r.email || 'N/A';
  document.getElementById('m-requester-phone').textContent = r.phone || 'N/A';
  document.getElementById('m-requester-address').textContent = r.address || 'Metro Manila';

  var proofFilename = r.proof_file ? r.proof_file : ('cert_r-' + r.id + '.pdf');
  document.getElementById('m-proof-info').innerHTML = 'Proof Document: <strong>' + proofFilename + '</strong> (Barangay Indigency / Valid ID &bull; Status: ' + (r.proof_status || 'Uploaded') + ')';
  
  var proofWrap = document.getElementById('m-proof-link-wrap');
  if (r.proof_path) {
    proofWrap.innerHTML = '<a href="<?= $base ?>/' + r.proof_path + '" target="_blank" class="btn btn-outline" style="font-size:11.5px;padding:3px 10px;margin-top:6px;display:inline-flex;align-items:center;gap:4px;"><i data-lucide="external-link" style="width:12px;height:12px;"></i> View Attached Document ↗</a>';
  } else {
    proofWrap.innerHTML = '';
  }

  document.getElementById('m-require-interview').checked = !!r.interview;
  document.getElementById('m-staff-notes').value = r.admin_notes || '';

  var modal = document.getElementById('request-details-modal');
  modal.style.display = 'flex';
  
  if (window.lucide) {
    lucide.createIcons();
  }
}

function closeRequestModal() {
  document.getElementById('request-details-modal').style.display = 'none';
}

function submitDecision(decision) {
  var reqId = document.getElementById('modal-req-id').value;
  if (!reqId) {
    alert('Invalid request ID');
    return;
  }

  if (decision === 'Rejected') {
    if (!confirm('Are you sure you want to reject this request?')) {
      return;
    }
  }

  document.getElementById('modal-decision').value = decision;
  document.getElementById('modal-decision-form').submit();
}

function deleteRequestRecord(id) {
  if (confirm('Are you sure you want to delete this request record?')) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= $base ?>/api/admin/delete-request.php';

    var csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = 'csrf_token';
    csrf.value = '<?= csrfToken() ?>';
    form.appendChild(csrf);

    var reqInput = document.createElement('input');
    reqInput.type = 'hidden';
    reqInput.name = 'request_id';
    reqInput.value = id;
    form.appendChild(reqInput);

    document.body.appendChild(form);
    form.submit();
  }
}

// Close modal when clicking outside of modal card
document.getElementById('request-details-modal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeRequestModal();
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
