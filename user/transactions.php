<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

$userId = (int)$_SESSION['user_id'];

// Fetch user's transactions (both as donor or requester)
$transactions = [];
try {
    $stmt = $pdo->prepare('
        SELECT t.*, d.title AS donation_title, d.unit,
               donor.first_name AS donor_first, donor.last_name AS donor_last, donor.phone AS donor_phone,
               req.first_name AS req_first, req.last_name AS req_last, req.phone AS req_phone
        FROM transactions t
        JOIN donations d ON t.donation_id = d.id
        JOIN users donor ON t.donor_id = donor.id
        JOIN users req ON t.requester_id = req.id
        WHERE t.donor_id = ? OR t.requester_id = ?
        ORDER BY t.created_at DESC
    ');
    $stmt->execute([$userId, $userId]);
    $transactions = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$pageTitle = 'Transactions & Handovers — iCareDonate';
$extraCss = 'user.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="user-layout">
  <?php require_once __DIR__ . '/../includes/nav-user.php'; ?>

  <div class="user-main">
    <header class="user-topbar">
      <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0;">Logistics &amp; Handover Tracking</h1>
      </div>
      <div>
        <a href="<?= $base ?>/user/dashboard.php" class="btn btn-outline" style="font-size: 13.5px; padding: 6px 14px;">
          &larr; Dashboard
        </a>
      </div>
    </header>

    <div class="user-content">
      <?php renderFlash(); ?>

      <?php if (empty($transactions)): ?>
        <div class="card" style="padding: 60px 20px; text-align: center; border-radius: var(--radius-lg);">
          <div style="margin-bottom: 12px; color: var(--color-text-muted);"><i data-lucide="truck" style="width:48px;height:48px;"></i></div>
          <h2 style="font-size: 20px; color: var(--color-text); margin-bottom: 8px;">No active transactions yet</h2>
          <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 24px;">
            When an admin matches a donation to your request (or your donation to a requester), handover coordination will appear here.
          </p>
          <a href="<?= $base ?>/pages/browse.php" class="btn btn-primary">Browse Listings</a>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 20px;">
          <?php foreach ($transactions as $tx): ?>
            <?php 
              $isDonor = ((int)$tx['donor_id'] === $userId);
              $otherRole = $isDonor ? 'Requester' : 'Donor';
              $otherName = $isDonor ? ($tx['req_first'] . ' ' . $tx['req_last']) : ($tx['donor_first'] . ' ' . $tx['donor_last']);
              $otherPhone = $isDonor ? ($tx['req_phone'] ?? 'Not provided') : ($tx['donor_phone'] ?? 'Not provided');
              $deadlineTime = strtotime($tx['claim_deadline']);
              $isExpired = ($deadlineTime < time() && in_array($tx['status'], ['created', 'pending_claim', 'claimed']));
            ?>
            <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm);">
              
              <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; flex-wrap: wrap;">
                <div>
                  <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                    <span class="badge <?= $isDonor ? 'badge-info' : 'badge-neutral' ?>">
                      Your Role: <?= $isDonor ? '<i data-lucide="gift" style="width:12px;height:12px;"></i> Donor' : '<i data-lucide="hand" style="width:12px;height:12px;"></i> Requester' ?>
                    </span>
                    <span style="font-size: 12px; color: var(--color-text-muted);">
                      Tx ID #<?= (int)$tx['id'] ?> · Created <?= date('M d, Y', strtotime($tx['created_at'])) ?>
                    </span>
                  </div>

                  <h2 style="font-size: 19px; font-weight: 700; color: var(--color-text); margin: 0 0 6px;">
                    <?= e($tx['donation_title']) ?> (<?= (int)$tx['quantity'] ?> <?= e($tx['unit']) ?>)
                  </h2>
                </div>

                <div style="text-align: right;">
                  <?= renderBadge($tx['status']) ?>
                </div>
              </div>

              <!-- Handover coordination details -->
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; background: #F8FAFC; padding: 18px; border-radius: var(--radius-md); margin-bottom: 20px;">
                <div>
                  <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">
                    Matched <?= $otherRole ?> Contact
                  </div>
                  <div style="font-size: 15px; font-weight: 600; color: var(--color-text);"><?= e($otherName) ?></div>
                  <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 2px;"><i data-lucide="phone" style="width:12px;height:12px;"></i> <?= e($otherPhone) ?></div>
                </div>

                <div>
                  <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">
                    Handover Method &amp; Deadline
                  </div>
                  <div style="font-size: 14px; font-weight: 600; color: var(--color-text);">
                    Method: <?= ucfirst($tx['pickup_method']) ?>
                  </div>
                  <div style="font-size: 13px; color: <?= $isExpired ? 'var(--color-accent)' : 'var(--color-primary)' ?>; font-weight: 600; margin-top: 2px;">
                    Claim Deadline: <?= date('M d, Y g:i A', $deadlineTime) ?> <?= $isExpired ? '(EXPIRED)' : '' ?>
                  </div>
                </div>
              </div>

              <!-- Action buttons for completion -->
              <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-top: 1px solid var(--color-border); padding-top: 16px;">
                <div style="font-size: 13px; color: var(--color-text-muted);">
                  Status updates are recorded in the community audit ledger.
                </div>

                <div style="display: flex; gap: 10px;">
                  <?php if ($tx['status'] === 'created' || $tx['status'] === 'pending_claim'): ?>
                    <form action="<?= $base ?>/api/transactions/update-status.php" method="POST" style="display: inline;">
                      <?= csrfField() ?>
                      <input type="hidden" name="transaction_id" value="<?= (int)$tx['id'] ?>">
                      <input type="hidden" name="action" value="claim">
                      <button type="submit" class="btn btn-outline" style="font-size: 13px; padding: 6px 14px;">
                        Mark as Claimed / Picked Up
                      </button>
                    </form>
                  <?php endif; ?>

                  <?php if ($tx['status'] === 'claimed' || ($tx['status'] === 'pending_claim' && $isDonor)): ?>
                    <form action="<?= $base ?>/api/transactions/update-status.php" method="POST" style="display: inline;">
                      <?= csrfField() ?>
                      <input type="hidden" name="transaction_id" value="<?= (int)$tx['id'] ?>">
                      <input type="hidden" name="action" value="complete">
                      <button type="submit" class="btn btn-primary" style="font-size: 13px; padding: 6px 14px;">
                        <i data-lucide="check-circle" style="width:14px;height:14px;"></i> Confirm Handover Completed
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
