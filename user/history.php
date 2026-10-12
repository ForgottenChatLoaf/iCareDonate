<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

$userId = (int)$_SESSION['user_id'];
$tab = trim($_GET['tab'] ?? 'all');

// Past Donations (fully_allocated, rejected, expired, cancelled, archived)
$donStmt = $pdo->prepare('
  SELECT d.*, c.name AS category_name 
  FROM donations d 
  LEFT JOIN categories c ON d.category_id = c.id 
  WHERE d.donor_id = ? AND d.status IN ("fully_allocated", "rejected", "expired", "cancelled", "approved", "archived")
  ORDER BY d.created_at DESC
');
$donStmt->execute([$userId]);
$pastDonations = $donStmt->fetchAll();

// Past Requests (completed, cancelled, rejected, expired) - LEFT JOIN to preserve history if donation is archived/unavailable
$reqStmt = $pdo->prepare('
  SELECT r.*, COALESCE(d.title, "Historical Donation Item") AS donation_title, c.name AS category_name 
  FROM requests r 
  LEFT JOIN donations d ON r.donation_id = d.id 
  LEFT JOIN categories c ON d.category_id = c.id 
  WHERE r.requester_id = ?
  ORDER BY r.created_at DESC
');
$reqStmt->execute([$userId]);
$pastRequests = $reqStmt->fetchAll();

// Past Transactions - LEFT JOIN to preserve history if donation is archived/unavailable
$txStmt = $pdo->prepare('
  SELECT t.*, COALESCE(d.title, "Historical Transaction Item") AS donation_title, 
         ud.first_name AS donor_fn, ud.last_name AS donor_ln,
         ur.first_name AS req_fn, ur.last_name AS req_ln
  FROM transactions t
  LEFT JOIN donations d ON t.donation_id = d.id
  LEFT JOIN users ud ON t.donor_id = ud.id
  LEFT JOIN users ur ON t.requester_id = ur.id
  WHERE t.donor_id = ? OR t.requester_id = ?
  ORDER BY t.created_at DESC
');
$txStmt->execute([$userId, $userId]);
$pastTransactions = $txStmt->fetchAll();

$pageTitle = 'Activity History — iCareDonate';
$extraCss = 'user.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="user-layout">
  <?php require_once __DIR__ . '/../includes/nav-user.php'; ?>

  <div class="user-main">
    <header class="user-topbar">
      <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0;">Activity History</h1>
      </div>
      <div>
        <a href="/CommunityDono/user/dashboard.php" class="btn btn-outline" style="font-size: 13px; padding: 5px 12px;">
          &larr; Dashboard
        </a>
      </div>
    </header>

    <div class="user-content" style="max-width: 1000px; margin: 0 auto;">
      <?php renderFlash(); ?>

      <!-- Filter tabs -->
      <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="/CommunityDono/user/history.php?tab=all" class="btn <?= $tab === 'all' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12.5px; padding: 6px 16px; border-radius: 99px;">
          All History
        </a>
        <a href="/CommunityDono/user/history.php?tab=donations" class="btn <?= $tab === 'donations' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12.5px; padding: 6px 16px; border-radius: 99px;">
          Donations History (<?= count($pastDonations) ?>)
        </a>
        <a href="/CommunityDono/user/history.php?tab=requests" class="btn <?= $tab === 'requests' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12.5px; padding: 6px 16px; border-radius: 99px;">
          Requests History (<?= count($pastRequests) ?>)
        </a>
        <a href="/CommunityDono/user/history.php?tab=transactions" class="btn <?= $tab === 'transactions' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12.5px; padding: 6px 16px; border-radius: 99px;">
          Transactions & Claims (<?= count($pastTransactions) ?>)
        </a>
      </div>

      <!-- Donations Section -->
      <?php if ($tab === 'all' || $tab === 'donations'): ?>
        <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md); margin-bottom: 24px;">
          <h2 style="font-size: 16px; font-weight: 700; color: var(--color-primary); margin-bottom: 14px;">Donation Listings History</h2>
          <?php if (empty($pastDonations)): ?>
            <p style="font-size: 13px; color: var(--color-text-muted);">No donation history found.</p>
          <?php else: ?>
            <div class="admin-table-container">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Item Title</th>
                    <th>Category</th>
                    <th>Total Qty</th>
                    <th>Claimed</th>
                    <th>Status</th>
                    <th>Date Posted</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pastDonations as $d): ?>
                    <tr>
                      <td>D-<?= (int)$d['id'] ?></td>
                      <td><strong><?= e($d['title']) ?></strong></td>
                      <td><?= e($d['category_name'] ?? 'General') ?></td>
                      <td><?= (int)$d['quantity_total'] ?> <?= e($d['unit'] ?? 'items') ?></td>
                      <td><?= (int)$d['quantity_claimed'] ?></td>
                      <td><?= renderBadge($d['status']) ?></td>
                      <td><?= date('M d, Y', strtotime($d['created_at'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Requests Section -->
      <?php if ($tab === 'all' || $tab === 'requests'): ?>
        <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md); margin-bottom: 24px;">
          <h2 style="font-size: 16px; font-weight: 700; color: var(--color-primary); margin-bottom: 14px;">Requests History</h2>
          <?php if (empty($pastRequests)): ?>
            <p style="font-size: 13px; color: var(--color-text-muted);">No request history found.</p>
          <?php else: ?>
            <div class="admin-table-container">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Resource Requested</th>
                    <th>Qty Needed</th>
                    <th>Qty Matched</th>
                    <th>Status</th>
                    <th>Submitted</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pastRequests as $r): ?>
                    <tr>
                      <td>R-<?= (int)$r['id'] ?></td>
                      <td><strong><?= e($r['donation_title']) ?></strong></td>
                      <td><?= (int)$r['quantity_requested'] ?></td>
                      <td><?= (int)$r['quantity_matched'] ?></td>
                      <td><?= renderBadge($r['status']) ?></td>
                      <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Transactions & Claims Section -->
      <?php if ($tab === 'all' || $tab === 'transactions'): ?>
        <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
          <h2 style="font-size: 16px; font-weight: 700; color: var(--color-primary); margin-bottom: 14px;">Transactions & Claims History</h2>
          <?php if (empty($pastTransactions)): ?>
            <p style="font-size: 13px; color: var(--color-text-muted);">No transaction history found.</p>
          <?php else: ?>
            <div class="admin-table-container">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Txn ID</th>
                    <th>Resource Item</th>
                    <th>Role</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pastTransactions as $t): 
                    $myRole = ((int)$t['donor_id'] === $userId) ? 'Donor' : 'Requester';
                  ?>
                    <tr>
                      <td>T-<?= (int)$t['id'] ?></td>
                      <td><strong><?= e($t['donation_title']) ?></strong></td>
                      <td><span class="badge badge-info"><?= $myRole ?></span></td>
                      <td><?= (int)$t['quantity'] ?></td>
                      <td><?= renderBadge($t['status']) ?></td>
                      <td><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                      <td>
                        <a href="/CommunityDono/user/transactions.php?id=<?= (int)$t['id'] ?>" class="btn btn-outline" style="font-size: 11px; padding: 3px 8px;">
                          View Details
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
