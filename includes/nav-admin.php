<?php
// includes/nav-admin.php
$base    = '/CommunityDono';
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="user-sidebar admin-sidebar" role="complementary" aria-label="Admin navigation">
  <!-- Brand logo & Admin badge component -->
  <?php
    $brandRole = 'ADMIN';
    $brandAlign = 'left';
    $brandLink = $base . '/admin/';
    require __DIR__ . '/brand-sidebar.php';
  ?>

  <!-- Exact navigation destinations from wireframe -->
  <nav class="sidebar-nav" aria-label="Admin navigation">
    <a href="<?= $base ?>/admin/index.php" class="sidebar-link <?= $current === 'index.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="layout-dashboard"></i>
      <span>Dashboard</span>
    </a>

    <a href="<?= $base ?>/admin/donations.php" class="sidebar-link <?= in_array($current, ['donations.php', 'donation-review.php'], true) ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="gift"></i>
      <span>Donation Review</span>
    </a>

    <a href="<?= $base ?>/admin/requests.php" class="sidebar-link <?= in_array($current, ['requests.php', 'request-review.php'], true) ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="file-check"></i>
      <span>Request & Proof Review</span>
    </a>

    <a href="<?= $base ?>/admin/transactions.php" class="sidebar-link <?= in_array($current, ['transactions.php', 'claims.php', 'matches.php'], true) ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="repeat"></i>
      <span>Transaction Monitoring</span>
    </a>

    <a href="<?= $base ?>/admin/interviews.php" class="sidebar-link <?= $current === 'interviews.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="calendar"></i>
      <span>Interviews</span>
    </a>

    <a href="<?= $base ?>/admin/users.php" class="sidebar-link <?= in_array($current, ['users.php', 'moderation.php', 'user-detail.php'], true) ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="users"></i>
      <span>Users & Moderation</span>
    </a>

    <a href="<?= $base ?>/admin/support.php" class="sidebar-link <?= in_array($current, ['support.php', 'inquiries.php'], true) ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="message-square"></i>
      <span>Message & Support</span>
    </a>

    <a href="<?= $base ?>/admin/faqs.php" class="sidebar-link <?= $current === 'faqs.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="help-circle"></i>
      <span>FAQ Content</span>
    </a>

    <a href="<?= $base ?>/admin/reports.php" class="sidebar-link <?= $current === 'reports.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="bar-chart-2"></i>
      <span>Reports</span>
    </a>

    <a href="<?= $base ?>/admin/categories.php" class="sidebar-link <?= $current === 'categories.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="tag"></i>
      <span>Categories</span>
    </a>

    <a href="<?= $base ?>/admin/statistics.php" class="sidebar-link <?= $current === 'statistics.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="activity"></i>
      <span>Statistics</span>
    </a>

    <a href="<?= $base ?>/admin/profile.php" class="sidebar-link <?= $current === 'profile.php' ? 'sidebar-link--active' : '' ?>">
      <i data-lucide="user"></i>
      <span>Admin Profile</span>
    </a>

    <?php if (isSuperAdmin()): ?>
    <hr style="border:none;border-top:1px solid var(--color-border);margin:8px 0;">
    <a href="<?= $base ?>/admin/admins.php" class="sidebar-link <?= $current === 'admins.php' ? 'sidebar-link--active' : '' ?>" style="color:var(--color-warning, #D97706);">
      <i data-lucide="shield"></i>
      <span>Admin Management</span>
    </a>
    <a href="<?= $base ?>/admin/logs.php" class="sidebar-link <?= $current === 'logs.php' ? 'sidebar-link--active' : '' ?>" style="color:var(--color-warning, #D97706);">
      <i data-lucide="scroll-text"></i>
      <span>Audit Logs</span>
    </a>
    <?php endif; ?>
  </nav>

  <!-- Sidebar Footer matching user layout -->
  <div class="sidebar-footer">
    <a href="<?= $base ?>/admin/profile.php" class="sidebar-user-name" style="text-decoration: none; color: inherit;">
      <?= e($_SESSION['user_name'] ?? 'Admin') ?>
    </a>
    <a href="<?= $base ?>/api/auth/logout.php" class="sidebar-link--muted" title="Log Out" style="text-decoration: none; display: flex; align-items: center; gap: 4px;">
      <i data-lucide="log-out" style="width: 15px; height: 15px;"></i>
    </a>
  </div>
</aside>