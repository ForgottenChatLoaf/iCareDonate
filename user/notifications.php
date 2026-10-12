<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

$userId = (int)$_SESSION['user_id'];

// Fetch all notifications for user
$notifications = [];
try {
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();

    // Mark unread as read
    $mark = $pdo->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0');
    $mark->execute([$userId]);
} catch (Exception $e) {
    error_log($e->getMessage());
}

$pageTitle = 'Notifications — iCareDonate';
$extraCss = 'user.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="user-layout">
  <?php require_once __DIR__ . '/../includes/nav-user.php'; ?>

  <div class="user-main">
    <header class="user-topbar">
      <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0;">Notification Center</h1>
      </div>
      <div>
        <a href="<?= $base ?>/user/dashboard.php" class="btn btn-outline" style="font-size: 13.5px; padding: 6px 14px;">
          &larr; Dashboard
        </a>
      </div>
    </header>

    <div class="user-content" style="max-width: 800px; margin: 0 auto;">
      <?php renderFlash(); ?>

      <?php if (empty($notifications)): ?>
        <div class="card" style="padding: 60px 20px; text-align: center; border-radius: var(--radius-lg);">
          <div style="margin-bottom: 12px; color: var(--color-text-muted);"><i data-lucide="bell" style="width:48px;height:48px;"></i></div>
          <h2 style="font-size: 20px; color: var(--color-text); margin-bottom: 8px;">No notifications yet</h2>
          <p style="color: var(--color-text-muted); font-size: 14px;">
            You will receive updates here when your listings are approved, requests are matched, or items are claimed.
          </p>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
          <?php foreach ($notifications as $n): ?>
            <div class="card" style="padding: 18px 20px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); display: flex; gap: 16px; align-items: flex-start; <?= (int)$n['is_read'] === 0 ? 'background: var(--color-tint-teal);' : '' ?>">
              <div style="color: var(--color-primary); line-height: 1;"><i data-lucide="bell" style="width:20px;height:20px;"></i></div>
              <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                  <h3 style="font-size: 15px; font-weight: 700; color: var(--color-text); margin: 0;"><?= e($n['title']) ?></h3>
                  <span style="font-size: 12px; color: var(--color-text-muted);"><?= date('M d, g:i A', strtotime($n['created_at'])) ?></span>
                </div>
                <p style="font-size: 13.5px; color: var(--color-text-muted); margin: 0; line-height: 1.5;">
                  <?= e($n['message']) ?>
                </p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
