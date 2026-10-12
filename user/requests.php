<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

$userId = (int)$_SESSION['user_id'];

// Fetch user's requests
$requests = [];
try {
    $stmt = $pdo->prepare('
        SELECT r.*, d.title AS donation_title, d.pickup_city, d.unit,
               c.name AS category_name
        FROM requests r
        JOIN donations d ON r.donation_id = d.id
        JOIN categories c ON d.category_id = c.id
        WHERE r.requester_id = ?
        ORDER BY r.submitted_at DESC
    ');
    $stmt->execute([$userId]);
    $requests = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$pageTitle = 'My Assistance Requests — iCareDonate';
$extraCss = 'user.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="user-layout">
  <?php require_once __DIR__ . '/../includes/nav-user.php'; ?>

  <div class="user-main">
    <header class="user-topbar">
      <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0;">My Assistance Requests</h1>
      </div>
      <div>
        <a href="/CommunityDono/pages/browse.php" class="btn btn-primary" style="font-size: 13.5px; padding: 8px 16px;">
          + Request New Items
        </a>
      </div>
    </header>

    <div class="user-content">
      <?php renderFlash(); ?>

      <?php if (empty($requests)): ?>
        <div class="card" style="padding: 60px 20px; text-align: center; border-radius: var(--radius-lg);">
          <div style="margin-bottom: 12px; color: var(--color-text-muted);"><i data-lucide="clipboard-list" style="width:48px;height:48px;"></i></div>
          <h2 style="font-size: 20px; color: var(--color-text); margin-bottom: 8px;">No requests submitted yet</h2>
          <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 24px;">
            Explore our browse section to find available food, school supplies, clothes, and essentials for your household.
          </p>
          <a href="/CommunityDono/pages/browse.php" class="btn btn-primary">Browse Available Items</a>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <?php foreach ($requests as $r): ?>
            <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
              
              <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 12px; flex-wrap: wrap;">
                <div>
                  <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 4px;">
                    <span class="badge badge-neutral" style="font-size: 11px;"><?= e($r['category_name']) ?></span>
                    <span style="font-size: 12px; color: var(--color-text-muted);">Submitted <?= date('M d, Y', strtotime($r['submitted_at'])) ?></span>
                    <span class="badge badge-info" style="font-size: 11px;">Urgency: <?= ucfirst($r['urgency_level']) ?></span>
                  </div>

                  <h3 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0 0 4px;">
                    <?= e($r['donation_title']) ?>
                  </h3>

                  <div style="font-size: 13px; color: var(--color-text-muted);">
                    Requested Quantity: <strong><?= (int)$r['quantity_requested'] ?></strong> <?= e($r['unit']) ?> · Location: <?= e($r['pickup_city']) ?>
                  </div>
                </div>

                <div style="text-align: right;">
                  <?= renderBadge($r['status']) ?>
                </div>
              </div>

              <!-- Purpose Statement -->
              <div style="background: #F8FAFC; padding: 14px 16px; border-radius: var(--radius-md); font-size: 13.5px; color: var(--color-text); margin-bottom: 12px;">
                <strong>Purpose of Request:</strong> <?= e($r['purpose']) ?>
              </div>

              <!-- Admin Feedback or Notes -->
              <?php if (!empty($r['rejection_reason'])): ?>
                <div class="alert alert-error" style="margin-bottom: 12px; padding: 10px 14px; font-size: 13px;">
                  <strong>Admin Rejection Reason:</strong> <?= e($r['rejection_reason']) ?>
                </div>
              <?php elseif (!empty($r['info_request'])): ?>
                <div class="alert alert-warning" style="margin-bottom: 12px; padding: 10px 14px; font-size: 13px;">
                  <strong>Admin Action Required:</strong> <?= e($r['info_request']) ?>
                </div>
              <?php endif; ?>

              <?php if (in_array($r['status'], ['matched', 'pickup_scheduled', 'claimed'], true)): ?>
                <div style="display: flex; justify-content: flex-end; margin-top: 12px;">
                  <a href="/CommunityDono/user/transactions.php" class="btn btn-primary" style="font-size: 13px; padding: 6px 14px;">
                    View Handover Instructions &rarr;
                  </a>
                </div>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
