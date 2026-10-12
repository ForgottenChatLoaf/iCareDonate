<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

$where = '1=1';
$params = [];

if (!empty($search)) {
    $where .= ' AND (t.id LIKE ? OR d.title LIKE ? OR u_donor.first_name LIKE ? OR u_req.first_name LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter !== 'all' && in_array($statusFilter, ['pending_claim', 'claimed', 'completed', 'expired', 'cancelled'], true)) {
    $where .= ' AND t.status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("
  SELECT t.*, 
         d.title AS donation_title,
         d.unit AS donation_unit,
         u_donor.first_name AS donor_fn, u_donor.last_name AS donor_ln, u_donor.email AS donor_email,
         u_req.first_name AS req_fn, u_req.last_name AS req_ln, u_req.email AS req_email
  FROM transactions t
  JOIN donations d ON t.donation_id = d.id
  JOIN users u_donor ON t.donor_id = u_donor.id
  JOIN users u_req ON t.requester_id = u_req.id
  WHERE $where
  ORDER BY t.claim_deadline ASC, t.created_at DESC
");
$stmt->execute($params);
$claims = $stmt->fetchAll();

// Counts for filter pills
$counts = [
    'all' => (int)$pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn(),
    'pending_claim' => (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE status = "pending_claim"')->fetchColumn(),
    'claimed' => (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE status = "claimed"')->fetchColumn(),
    'completed' => (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE status = "completed"')->fetchColumn(),
    'expired' => (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE status = "expired"')->fetchColumn(),
];

$pageTitle = 'Claim Monitoring — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Claim & Pick-up Monitoring</h1>
      </div>
      <div>
        <form method="GET" action="<?= $base ?>/admin/claims.php" style="display: flex; gap: 8px;">
          <?php if ($statusFilter !== 'all'): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
          <?php endif; ?>
          <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search claim, donation, user..." class="form-input" style="height: 32px; font-size: 12px; width: 220px;">
          <button type="submit" class="btn btn-outline" style="font-size: 12px; padding: 4px 10px;">Search</button>
        </form>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <!-- Filter tabs -->
      <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
        <a href="<?= $base ?>/admin/claims.php?status=all<?= $search ? '&q='.urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          All Claims (<?= $counts['all'] ?>)
        </a>
        <a href="<?= $base ?>/admin/claims.php?status=pending_claim<?= $search ? '&q='.urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'pending_claim' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          Ready for Claim (<?= $counts['pending_claim'] ?>)
        </a>
        <a href="<?= $base ?>/admin/claims.php?status=claimed<?= $search ? '&q='.urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'claimed' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          Claimed / Picked up (<?= $counts['claimed'] ?>)
        </a>
        <a href="<?= $base ?>/admin/claims.php?status=completed<?= $search ? '&q='.urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'completed' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          Completed (<?= $counts['completed'] ?>)
        </a>
        <a href="<?= $base ?>/admin/claims.php?status=expired<?= $search ? '&q='.urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'expired' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          Expired (<?= $counts['expired'] ?>)
        </a>
      </div>

      <div class="admin-table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Txn ID</th>
              <th>Claim Code</th>
              <th>Donation Resource</th>
              <th>Allocated Qty</th>
              <th>Requester</th>
              <th>Donor</th>
              <th>Deadline</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($claims)): ?>
              <tr>
                <td colspan="9" style="text-align: center; color: var(--color-text-muted); padding: 24px;">No claims found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($claims as $c): 
                $claimCode = 'IC-' . str_pad((string)$c['id'], 4, '0', STR_PAD_LEFT) . '-D' . $c['donation_id'];
                $isExpired = strtotime($c['claim_deadline']) < time() && in_array($c['status'], ['pending_claim', 'claimed']);
              ?>
                <tr>
                  <td><strong>T-<?= (int)$c['id'] ?></strong></td>
                  <td>
                    <span style="font-family: monospace; font-weight: 700; background: #F1F5F9; padding: 2px 6px; border-radius: 4px; border: 1px solid #CBD5E1;">
                      <?= $claimCode ?>
                    </span>
                  </td>
                  <td>
                    <a href="<?= $base ?>/admin/donation-review.php?id=<?= (int)$c['donation_id'] ?>" style="color: var(--color-primary); font-weight: 600; text-decoration: none;">
                      <?= e($c['donation_title']) ?>
                    </a>
                  </td>
                  <td><?= (int)$c['quantity'] ?> <?= e($c['donation_unit'] ?? 'items') ?></td>
                  <td>
                    <a href="<?= $base ?>/admin/user-detail.php?id=<?= (int)$c['requester_id'] ?>" style="text-decoration: none; color: inherit;">
                      <?= e($c['req_fn'] . ' ' . $c['req_ln']) ?>
                    </a>
                  </td>
                  <td>
                    <a href="<?= $base ?>/admin/user-detail.php?id=<?= (int)$c['donor_id'] ?>" style="text-decoration: none; color: inherit;">
                      <?= e($c['donor_fn'] . ' ' . $c['donor_ln']) ?>
                    </a>
                  </td>
                  <td>
                    <span style="<?= $isExpired ? 'color: #DC2626; font-weight: 700;' : '' ?>">
                      <?= date('M d, Y', strtotime($c['claim_deadline'])) ?>
                    </span>
                    <?php if ($isExpired): ?>
                      <span class="badge badge-danger" style="font-size: 10px; margin-left: 4px;">Expiring/Overdue</span>
                    <?php endif; ?>
                  </td>
                  <td><?= renderBadge($c['status']) ?></td>
                  <td style="white-space: nowrap;">
                    <a href="<?= $base ?>/admin/transactions.php" class="btn btn-outline" style="font-size: 11px; padding: 4px 8px;">
                      View Details
                    </a>
                    <?php if ($c['status'] === 'pending_claim'): ?>
                      <form action="<?= $base ?>/api/transactions/status.php" method="POST" style="display: inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="transaction_id" value="<?= (int)$c['id'] ?>">
                        <input type="hidden" name="status" value="claimed">
                        <input type="hidden" name="redirect" value="<?= $base ?>/admin/claims.php">
                        <button type="submit" class="btn btn-primary" style="font-size: 11px; padding: 4px 8px;">
                          Mark Claimed
                        </button>
                      </form>
                    <?php elseif ($c['status'] === 'claimed'): ?>
                      <form action="<?= $base ?>/api/transactions/status.php" method="POST" style="display: inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="transaction_id" value="<?= (int)$c['id'] ?>">
                        <input type="hidden" name="status" value="completed">
                        <input type="hidden" name="redirect" value="<?= $base ?>/admin/claims.php">
                        <button type="submit" class="btn btn-success" style="font-size: 11px; padding: 4px 8px;">
                          Complete
                        </button>
                      </form>
                    <?php endif; ?>
                  </td>
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
