<?php
// includes/nav-user.php
require_once __DIR__ . '/../config/app.php';
$base    = APP_BASE;
$current = basename($_SERVER['PHP_SELF']);
$dir     = basename(dirname($_SERVER['PHP_SELF']));
$unread  = 0;
if (!empty($pdo) && !empty($_SESSION['user_id'])) {
    try { $unread = getUnreadNotificationCount($pdo, (int)$_SESSION['user_id']); } catch(Exception $e) {}
}
?>
<aside class="user-sidebar" role="complementary" aria-label="User navigation">
  <!-- Brand logo & User badge component -->
  <?php
    $brandRole = 'USER';
    $brandAlign = 'left';
    $brandLink = $base . '/user/dashboard.php';
    require __DIR__ . '/brand-sidebar.php';
  ?>

  <nav class="sidebar-nav" aria-label="User dashboard navigation">
    <a href="<?= $base ?>/user/dashboard.php" class="sidebar-link <?= $current === 'dashboard.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="layout-dashboard"></i> Dashboard
    </a>
    <a href="<?= $base ?>/user/offer-donation.php" class="sidebar-link sidebar-link--accent <?= $current === 'offer-donation.php' || $current === 'post-donation.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="plus-circle"></i> Post Donation
    </a>
    <a href="<?= $base ?>/user/browse-donations.php" class="sidebar-link <?= $current === 'browse-donations.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="search"></i> Browse Donations
    </a>
    <a href="<?= $base ?>/user/donations.php" class="sidebar-link <?= $current === 'donations.php' && $dir === 'user' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="gift"></i> My Donations
    </a>
    <a href="<?= $base ?>/user/requests.php" class="sidebar-link <?= $current === 'requests.php' && $dir === 'user' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="hand"></i> My Requests
    </a>
    <a href="<?= $base ?>/user/transactions.php" class="sidebar-link <?= $current === 'transactions.php' && $dir === 'user' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="repeat"></i> Claim & Pick-up
    </a>
    <a href="<?= $base ?>/user/history.php" class="sidebar-link <?= $current === 'history.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="clock"></i> History
    </a>
    <a href="<?= $base ?>/user/notifications.php" class="sidebar-link <?= $current === 'notifications.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="bell"></i> Notifications
      <?php if ($unread > 0): ?>
        <span class="sidebar-badge" aria-label="<?= $unread ?> unread"><?= $unread ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= $base ?>/user/support.php" class="sidebar-link <?= $current === 'support.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="help-circle"></i> Messages & Support
    </a>
    <a href="<?= $base ?>/user/settings.php" class="sidebar-link <?= $current === 'settings.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="settings"></i> Profile & Settings
    </a>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= $base ?>/user/profile.php" class="sidebar-user-name" style="text-decoration: none; color: inherit;">
      <?= e($_SESSION['user_name'] ?? 'User') ?>
    </a>
    <a href="<?= $base ?>/api/auth/logout.php" class="sidebar-link sidebar-link--muted">
      <i data-lucide="log-out"></i> Log out
    </a>
  </div>
</aside>