<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$transactions = [];
try {
    $stmt = $pdo->query('
        SELECT t.*, d.title AS donation_title, d.unit,
               donor.first_name AS donor_first, donor.last_name AS donor_last, donor.email AS donor_email,
               req.first_name AS req_first, req.last_name AS req_last, req.email AS req_email
        FROM transactions t
        JOIN donations d ON t.donation_id = d.id
        JOIN users donor ON t.donor_id = donor.id
        JOIN users req ON t.requester_id = req.id
        ORDER BY t.created_at DESC
    ');
    $transactions = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$pageTitle = 'Matches & Transactions — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Matched Handover Transactions</h1>
      </div>
      <div>
        <a href="/CommunityDono/admin/requests.php" class="btn btn-primary" style="font-size: 12px; padding: 6px 12px;">
          + Match Pending Request
        </a>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <div class="admin-table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Tx ID</th>
              <th>Donation Item</th>
              <th>Quantity</th>
              <th>Donor</th>
              <th>Requester</th>
              <th>Method</th>
              <th>Deadline</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($transactions)): ?>
              <tr>
                <td colspan="8" style="text-align: center; padding: 32px; color: var(--color-text-muted);">No match transactions generated yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($transactions as $tx): ?>
                <tr>
                  <td><strong>#<?= (int)$tx['id'] ?></strong></td>
                  <td><strong><?= e($tx['donation_title']) ?></strong></td>
                  <td><?= (int)$tx['quantity'] ?> <?= e($tx['unit']) ?></td>
                  <td><?= e($tx['donor_first'] . ' ' . $tx['donor_last']) ?></td>
                  <td><?= e($tx['req_first'] . ' ' . $tx['req_last']) ?></td>
                  <td><span class="badge badge-neutral"><?= ucfirst($tx['pickup_method']) ?></span></td>
                  <td><?= date('M d, Y', strtotime($tx['claim_deadline'])) ?></td>
                  <td><?= renderBadge($tx['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
