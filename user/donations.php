<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

$userId = (int)$_SESSION['user_id'];

// Fetch user's donations
$donations = [];
try {
    $stmt = $pdo->prepare('
        SELECT d.*, c.name AS category_name,
               (SELECT file_path FROM donation_images WHERE donation_id = d.id ORDER BY is_primary DESC LIMIT 1) AS primary_image
        FROM donations d
        JOIN categories c ON d.category_id = c.id
        WHERE d.donor_id = ?
        ORDER BY d.created_at DESC
    ');
    $stmt->execute([$userId]);
    $donations = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$pageTitle = 'My Offered Donations — iCareDonate';
$extraCss = 'user.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="user-layout">
  <?php require_once __DIR__ . '/../includes/nav-user.php'; ?>

  <div class="user-main">
    <header class="user-topbar">
      <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0;">My Offered Donations</h1>
      </div>
      <div>
        <a href="<?= $base ?>/user/offer-donation.php" class="btn btn-primary" style="font-size: 13.5px; padding: 8px 16px;">
          + Offer New Donation
        </a>
      </div>
    </header>

    <div class="user-content">
      <?php renderFlash(); ?>

      <?php if (empty($donations)): ?>
        <div class="card" style="padding: 60px 20px; text-align: center; border-radius: var(--radius-lg);">
          <div style="margin-bottom: 12px; color: var(--color-text-muted);"><i data-lucide="gift" style="width:48px;height:48px;"></i></div>
          <h2 style="font-size: 20px; color: var(--color-text); margin-bottom: 8px;">No donations offered yet</h2>
          <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 24px;">
            Have clothes, school supplies, or household items you no longer use? Share them with a family in need.
          </p>
          <a href="<?= $base ?>/user/offer-donation.php" class="btn btn-primary">Offer a Donation</a>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <?php foreach ($donations as $d): ?>
            <?php 
              $available = (int)$d['quantity_total'] - (int)$d['quantity_reserved'] - (int)$d['quantity_claimed'];
            ?>
            <div class="card" style="padding: 20px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); display: grid; grid-template-columns: 100px 1fr auto; gap: 20px; align-items: center;">
              
              <!-- Thumbnail -->
              <div style="width: 100px; height: 80px; background: #F1F5F9; border-radius: var(--radius-md); overflow: hidden; display: flex; align-items: center; justify-content: center;">
                <?php if (!empty($d['primary_image'])): ?>
                  <img src="<?= $base ?>/<?= e($d['primary_image']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                  <div style="color: #94A3B8;"><i data-lucide="gift" style="width:28px;height:28px;"></i></div>
                <?php endif; ?>
              </div>

              <!-- Item info -->
              <div>
                <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 4px;">
                  <span class="badge badge-neutral" style="font-size: 11px;"><?= e($d['category_name']) ?></span>
                  <span style="font-size: 12px; color: var(--color-text-muted);">Submitted <?= date('M d, Y', strtotime($d['created_at'])) ?></span>
                </div>
                
                <h3 style="font-size: 17px; font-weight: 700; color: var(--color-text); margin: 0 0 4px;">
                  <?= e($d['title']) ?>
                </h3>

                <div style="font-size: 13px; color: var(--color-text-muted); display: flex; gap: 16px; flex-wrap: wrap;">
                  <div>Total: <strong><?= (int)$d['quantity_total'] ?></strong> <?= e($d['unit']) ?></div>
                  <div>Available: <strong style="color: var(--color-primary);"><?= $available ?></strong> <?= e($d['unit']) ?></div>
                  <div>Claimed: <strong><?= (int)$d['quantity_claimed'] ?></strong> <?= e($d['unit']) ?></div>
                  <div>Condition: <?= ucfirst($d['condition_type']) ?></div>
                </div>

                <?php if (!empty($d['rejection_reason'])): ?>
                  <div class="alert alert-error" style="margin-top: 10px; padding: 8px 12px; font-size: 12px;">
                    <strong>Admin Note:</strong> <?= e($d['rejection_reason']) ?>
                  </div>
                <?php elseif (!empty($d['info_request'])): ?>
                  <div class="alert alert-warning" style="margin-top: 10px; padding: 8px 12px; font-size: 12px;">
                    <strong>Information Requested by Admin:</strong> <?= e($d['info_request']) ?>
                  </div>
                <?php elseif ($d['status'] === 'archived'): ?>
                  <div class="alert alert-info" style="margin-top: 10px; padding: 8px 12px; font-size: 12px;">
                    <strong>Archived:</strong> This listing has been archived by the administrative moderation team and is no longer publicly visible.
                  </div>
                <?php endif; ?>
              </div>

              <!-- Status and action -->
              <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                <div>
                  <?= renderBadge($d['status']) ?>
                </div>

                <?php if ($d['status'] === 'approved'): ?>
                  <a href="<?= $base ?>/pages/donation-details.php?id=<?= (int)$d['id'] ?>" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;" target="_blank">
                    Public View ↗
                  </a>
                <?php endif; ?>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
