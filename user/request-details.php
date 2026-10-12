<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

$userId = (int)$_SESSION['user_id'];
$requestId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($requestId <= 0) {
    header('Location: ' . APP_BASE . '/user/requests.php');
    exit;
}

// Fetch request details
$stmt = $pdo->prepare('
    SELECT r.*, d.title AS donation_title, d.unit, d.pickup_city, d.pickup_barangay, d.pickup_method,
           c.name AS category_name,
           (SELECT file_path FROM request_proofs WHERE request_id = r.id LIMIT 1) AS proof_path,
           (SELECT id FROM transactions WHERE match_id = (SELECT id FROM matches WHERE request_id = r.id LIMIT 1) LIMIT 1) AS tx_id
    FROM requests r
    JOIN donations d ON r.donation_id = d.id
    JOIN categories c ON d.category_id = c.id
    WHERE r.id = ? AND r.requester_id = ?
    LIMIT 1
');
$stmt->execute([$requestId, $userId]);
$req = $stmt->fetch();

if (!$req) {
    setFlash('error', 'Request not found.');
    header('Location: ' . APP_BASE . '/user/requests.php');
    exit;
}

$pageTitle = 'Request Tracking #' . $requestId . ' — iCareDonate';
$extraCss = 'user.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="user-layout">
  <?php require_once __DIR__ . '/../includes/nav-user.php'; ?>

  <div class="user-main">
    <header class="user-topbar">
      <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0;">Request Tracking #<?= (int)$req['id'] ?></h1>
      </div>
      <div>
        <a href="<?= $base ?>/user/requests.php" class="btn btn-outline" style="font-size: 13.5px; padding: 6px 14px;">
          &larr; My Requests
        </a>
      </div>
    </header>

    <div class="user-content" style="max-width: 800px; margin: 0 auto;">
      <?php renderFlash(); ?>

      <!-- Status Progress Stepper -->
      <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 24px;">
        <h2 style="font-size: 16px; font-weight: 700; color: var(--color-text); margin: 0 0 20px;">Request Progress Tracker</h2>
        
        <?php
          $statuses = ['submitted', 'proof_review', 'approved', 'matched', 'completed'];
          $curIndex = array_search($req['status'], $statuses, true);
          if ($curIndex === false) { $curIndex = 0; }
        ?>
        <div class="wizard-steps">
          <div class="wizard-step <?= $curIndex >= 0 ? 'completed active' : '' ?>">
            <div class="wizard-step__number">1</div>
            <div class="wizard-step__label">Submitted</div>
          </div>
          <div class="wizard-step <?= $curIndex >= 1 ? 'completed active' : '' ?>">
            <div class="wizard-step__number">2</div>
            <div class="wizard-step__label">Verification</div>
          </div>
          <div class="wizard-step <?= $curIndex >= 2 ? 'completed active' : '' ?>">
            <div class="wizard-step__number">3</div>
            <div class="wizard-step__label">Approved</div>
          </div>
          <div class="wizard-step <?= $curIndex >= 3 ? 'completed active' : '' ?>">
            <div class="wizard-step__number">4</div>
            <div class="wizard-step__label">Matched</div>
          </div>
          <div class="wizard-step <?= $curIndex >= 4 ? 'completed active' : '' ?>">
            <div class="wizard-step__number">5</div>
            <div class="wizard-step__label">Completed</div>
          </div>
        </div>
      </div>

      <!-- Request Information Card -->
      <div class="card" style="padding: 28px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
          <div>
            <span class="badge badge-info" style="font-size: 11px;"><?= e($req['category_name']) ?></span>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 6px 0 0;"><?= e($req['donation_title']) ?></h2>
          </div>
          <div><?= renderBadge($req['status']) ?></div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; background: #F8FAFC; padding: 16px; border-radius: var(--radius-md); font-size: 13.5px; margin-bottom: 20px;">
          <div>
            <div><strong>Requested Quantity:</strong> <?= (int)$req['quantity_requested'] ?> <?= e($req['unit']) ?></div>
            <div style="margin-top: 4px;"><strong>Urgency Level:</strong> <?= ucfirst($req['urgency_level']) ?></div>
            <div style="margin-top: 4px;"><strong>Filing Date:</strong> <?= date('M d, Y g:i A', strtotime($req['submitted_at'])) ?></div>
          </div>
          <div>
            <div><strong>Contact Number:</strong> <?= e($req['contact_number'] ?? 'N/A') ?></div>
            <div style="margin-top: 4px;"><strong>Delivery Address:</strong> <?= e($req['delivery_address'] ?? 'N/A') ?></div>
          </div>
        </div>

        <div style="margin-bottom: 20px;">
          <h3 style="font-size: 15px; font-weight: 700; color: var(--color-text); margin: 0 0 6px;">Reason for Request:</h3>
          <p style="font-size: 14px; color: var(--color-text-muted); line-height: 1.6; margin: 0; background: #FFF; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
            <?= e($req['purpose']) ?>
          </p>
        </div>

        <?php if (!empty($req['proof_path'])): ?>
          <div style="margin-bottom: 20px; font-size: 13px;">
            <strong>Attached Proof Document:</strong>
            <a href="<?= $base ?>/<?= e($req['proof_path']) ?>" target="_blank" style="color: var(--color-primary); font-weight: 600; margin-left: 6px;">
              View Uploaded File ↗
            </a>
          </div>
        <?php endif; ?>

        <?php if (!empty($req['tx_id'])): ?>
          <div style="padding-top: 16px; border-top: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 14px; color: var(--color-text); font-weight: 600;">Transaction Handover is Active!</span>
            <a href="<?= $base ?>/user/transactions.php" class="btn btn-primary" style="font-size: 13px; padding: 6px 16px;">
              Manage Handover &rarr;
            </a>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
