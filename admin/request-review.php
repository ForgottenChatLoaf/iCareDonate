<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: ' . APP_BASE . '/admin/requests.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT r.*, d.title AS donation_title, d.quantity_total, d.quantity_reserved, d.quantity_claimed, d.unit,
           u.first_name, u.last_name, u.email AS requester_email, u.phone AS requester_phone,
           u.barangay AS requester_barangay, u.city AS requester_city,
           (SELECT file_path FROM request_proofs WHERE request_id = r.id LIMIT 1) AS proof_path
    FROM requests r
    JOIN donations d ON r.donation_id = d.id
    JOIN users u ON r.requester_id = u.id
    WHERE r.id = ?
    LIMIT 1
');
$stmt->execute([$id]);
$request = $stmt->fetch();

if (!$request) {
    setFlash('error', 'Request not found.');
    header('Location: ' . APP_BASE . '/admin/requests.php');
    exit;
}

$availableStock = (int)$request['quantity_total'] - (int)$request['quantity_reserved'] - (int)$request['quantity_claimed'];

$pageTitle = 'Request Review #' . $id . ' — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Review Assistance Request #<?= (int)$request['id'] ?></h1>
      </div>
      <div>
        <a href="<?= $base ?>/admin/requests.php" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;">
          &larr; Back to Requests Queue
        </a>
      </div>
    </header>

    <div class="admin-content" style="max-width: 960px; margin: 0 auto;">
      <?php renderFlash(); ?>

      <div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px; align-items: start;">
        
        <!-- Left: Statement of need, Proof document, Interview state -->
        <div>
          <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
              <span class="badge badge-info">Urgency: <?= ucfirst($request['urgency_level']) ?></span>
              <div><?= renderBadge($request['status']) ?></div>
            </div>

            <h2 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0 0 6px;">
              Target: <?= e($request['donation_title']) ?>
            </h2>
            <div style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px;">
              Requested: <strong><?= (int)$request['quantity_requested'] ?> <?= e($request['unit']) ?></strong> · Available in Donation: <strong><?= $availableStock ?> <?= e($request['unit']) ?></strong>
            </div>

            <div style="background: #F8FAFC; padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--color-border); font-size: 14px; line-height: 1.6; margin-bottom: 16px;">
              <strong>Statement of Need / Purpose:</strong><br>
              <?= e($request['purpose']) ?>
            </div>

            <div style="font-size: 13px; color: var(--color-text);">
              <div>Delivery Address: <strong><?= e($request['delivery_address'] ?? 'Not specified') ?></strong></div>
              <div style="margin-top: 4px;">Filing Date: <strong><?= date('M d, Y g:i A', strtotime($request['submitted_at'])) ?></strong></div>
            </div>
          </div>

          <!-- Supporting Proof File (Scope 6.3) -->
          <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 20px;">
            <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 12px;">Supporting Proof Document</h3>
            <?php if (!empty($request['proof_path'])): ?>
              <div style="display: flex; align-items: center; justify-content: space-between; background: var(--color-tint-teal); padding: 14px; border-radius: var(--radius-md); border: 1px solid rgba(31, 79, 163, 0.12);">
                <div>
                  <div style="font-weight: 700; font-size: 14px; color: var(--color-primary);"><i data-lucide="paperclip" style="width:14px;height:14px;"></i> Proof Document Attached</div>
                  <div style="font-size: 12px; color: var(--color-text-muted);">Encrypted private verification storage</div>
                </div>
                <a href="<?= $base ?>/<?= e($request['proof_path']) ?>" target="_blank" class="btn btn-primary" style="font-size: 12px; padding: 6px 14px;">
                  Inspect Document ↗
                </a>
              </div>
            <?php else: ?>
              <p style="color: var(--color-text-muted); font-size: 13.5px; margin: 0;">No proof document uploaded with this request.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right: Requester Profile & Interview Decision / Matching Engine -->
        <div>
          <!-- Requester Profile -->
          <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 20px;">
            <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 12px;">Requester Information</h3>
            <div style="font-size: 13.5px; display: flex; flex-direction: column; gap: 8px;">
              <div><strong>Name:</strong> <?= e($request['first_name'] . ' ' . $request['last_name']) ?></div>
              <div><strong>Email:</strong> <?= e($request['requester_email']) ?></div>
              <div><strong>Phone:</strong> <?= e($request['contact_number'] ?? $request['requester_phone'] ?? 'N/A') ?></div>
              <div><strong>Area:</strong> <?= e($request['requester_barangay'] ? $request['requester_barangay'] . ', ' : '') ?><?= e($request['requester_city'] ?? 'Metro Manila') ?></div>
            </div>
          </div>

          <!-- Interview Decision (Scope 6.3 & 6.5) -->
          <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 20px;">
            <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 12px;">Verification Interview Status</h3>
            <div style="font-size: 13.5px; margin-bottom: 12px;">
              Status: <strong><?= ucfirst(str_replace('_', ' ', $request['interview_status'] ?? 'not_required')) ?></strong>
            </div>

            <a href="<?= $base ?>/admin/interviews.php" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 12.5px; padding: 8px;">
              <i data-lucide="calendar-clock" style="width:13px;height:13px;"></i> Schedule / Manage Verification Interview
            </a>
          </div>

          <!-- Approve / Match & Actions (Scope 6.3) -->
          <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1.5px solid var(--color-border); box-shadow: 0 4px 16px rgba(20, 33, 61, 0.05); background: var(--color-surface, #FFFFFF);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
              <h3 style="font-size: 16px; font-weight: 700; color: var(--color-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="shield-check" style="width: 18px; height: 18px;"></i>
                Match &amp; Decision
              </h3>
              <span class="badge badge-info" style="font-size: 11px;">Admin Action</span>
            </div>

            <!-- Stock availability indicator -->
            <div style="background: var(--color-surface-soft, #F8FAFC); border: 1px solid var(--color-border); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px;">
              <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 6px;">
                <span style="color: var(--color-text-muted);">Stock vs. Request:</span>
                <strong><?= (int)$request['quantity_requested'] ?> / <?= $availableStock ?> <?= e($request['unit']) ?></strong>
              </div>
              <div style="width: 100%; height: 6px; background: #E2E8F0; border-radius: 99px; overflow: hidden;">
                <?php
                  $pct = $availableStock > 0 ? min(100, round(((int)$request['quantity_requested'] / $availableStock) * 100)) : 100;
                  $barColor = $availableStock >= (int)$request['quantity_requested'] ? 'var(--color-primary, #1F4FA3)' : '#DC2626';
                ?>
                <div style="width: <?= $pct ?>%; height: 100%; background: <?= $barColor ?>; border-radius: 99px;"></div>
              </div>
            </div>

            <?php if ($availableStock >= (int)$request['quantity_requested']): ?>
              <form action="<?= $base ?>/api/admin/match.php" method="POST" style="margin-bottom: 12px;">
                <?= csrfField() ?>
                <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                <input type="hidden" name="donation_id" value="<?= (int)$request['donation_id'] ?>">
                <input type="hidden" name="quantity" value="<?= (int)$request['quantity_requested'] ?>">
                
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 11px; font-weight: 600; font-size: 13.5px; border-radius: 10px; box-shadow: 0 4px 12px rgba(31, 79, 163, 0.2);">
                  <i data-lucide="zap" style="width: 15px; height: 15px;"></i> Approve Request &amp; Create Match
                </button>
              </form>
            <?php else: ?>
              <div class="alert alert-warning" style="font-size: 12px; margin-bottom: 12px; border-radius: 8px;">
                <i data-lucide="alert-triangle" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                Requested quantity exceeds available stock (<?= $availableStock ?> left).
              </div>
            <?php endif; ?>

            <!-- Delete Request -->
            <form action="<?= $base ?>/api/admin/delete-request.php" method="POST" onsubmit="return confirm('Delete this request record?');">
              <?= csrfField() ?>
              <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
              <button type="submit" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 12px; padding: 8px; color: #991B1B; border-color: #FCA5A5; border-radius: 8px;">
                <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i> Delete Request Record
              </button>
            </form>
          </div>

        </div>

      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
