<?php
// includes/nav-public.php
require_once __DIR__ . '/../config/app.php';
$base     = APP_BASE;
$loggedIn = isLoggedIn();
$current  = basename($_SERVER['PHP_SELF']);
$homeUri  = ($base === '') ? '/' : $base . '/';
$isHome   = ($current === 'index.php' || $current === '' || $_SERVER['REQUEST_URI'] === $homeUri || ($base !== '' && $_SERVER['REQUEST_URI'] === $base));
?>
<header class="nav-public" role="banner">
  <div class="container nav-public__inner">

    <!-- Logo -->
    <a href="<?= $base ?>/" class="logo" aria-label="iCareDonate — Home">
      <img src="<?= $base ?>/icaredonate-icon.svg" class="logo-icon" alt="iCareDonate" width="36" height="36">
      <span class="logo-wordmark">i<em>Care</em>Donate</span>
    </a>

    <!-- Desktop nav (Wireframe spec: Home, About us, How it works, Categories, Contact us, Log in / Sign up) -->
    <nav class="nav-public__links" aria-label="Main navigation">
      <a href="<?= $base ?>/" class="nav-link <?= $isHome ? 'nav-link--active' : '' ?>">Home</a>
      <a href="<?= $base ?>/pages/about.php" class="nav-link <?= $current === 'about.php' ? 'nav-link--active' : '' ?>">About us</a>
      <a href="<?= $base ?>/pages/how-it-works.php" class="nav-link <?= $current === 'how-it-works.php' ? 'nav-link--active' : '' ?>">How it works</a>
      <a href="<?= $base ?>/pages/categories.php" class="nav-link <?= $current === 'categories.php' ? 'nav-link--active' : '' ?>">Categories</a>
      <a href="<?= $base ?>/pages/contact.php" class="nav-link <?= $current === 'contact.php' ? 'nav-link--active' : '' ?>">Contact us</a>
    </nav>

    <!-- Actions / Auth Button -->
    <div class="nav-public__actions">
      <?php if ($loggedIn): ?>
        <?php if (isAdmin()): ?>
          <a href="<?= $base ?>/admin/" class="btn-pub-auth">Admin Panel</a>
        <?php else: ?>
          <a href="<?= $base ?>/user/dashboard.php" class="btn-pub-auth">Dashboard</a>
        <?php endif; ?>
        <a href="<?= $base ?>/api/auth/logout.php" class="nav-link" style="font-size: 13.5px;">Log out</a>
      <?php else: ?>
        <a href="<?= $base ?>/pages/login.php" class="btn-pub-auth">Log in / Sign up</a>
      <?php endif; ?>
    </div>

    <!-- Mobile hamburger -->
    <button class="nav-hamburger" id="nav-hamburger" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu">
      <i data-lucide="menu"></i>
    </button>
  </div>

  <!-- Mobile menu -->
  <div class="mobile-menu" id="mobile-menu" role="dialog" aria-label="Mobile navigation" hidden>
    <nav class="mobile-menu__links" aria-label="Mobile navigation">
      <a href="<?= $base ?>/" class="mobile-link <?= $isHome ? 'mobile-link--active' : '' ?>">Home</a>
      <a href="<?= $base ?>/pages/about.php" class="mobile-link <?= $current === 'about.php' ? 'mobile-link--active' : '' ?>">About us</a>
      <a href="<?= $base ?>/pages/how-it-works.php" class="mobile-link <?= $current === 'how-it-works.php' ? 'mobile-link--active' : '' ?>">How it works</a>
      <a href="<?= $base ?>/pages/categories.php" class="mobile-link <?= $current === 'categories.php' ? 'mobile-link--active' : '' ?>">Categories</a>
      <a href="<?= $base ?>/pages/contact.php" class="mobile-link <?= $current === 'contact.php' ? 'mobile-link--active' : '' ?>">Contact us</a>
      <?php if ($loggedIn): ?>
        <?php if (isAdmin()): ?>
          <a href="<?= $base ?>/admin/" class="mobile-link mobile-link--accent">Admin Panel</a>
        <?php else: ?>
          <a href="<?= $base ?>/user/dashboard.php" class="mobile-link mobile-link--accent">Dashboard</a>
        <?php endif; ?>
        <a href="<?= $base ?>/api/auth/logout.php" class="mobile-link mobile-link--muted">Log out</a>
      <?php else: ?>
        <a href="<?= $base ?>/pages/login.php" class="mobile-link mobile-link--accent">Log in / Sign up</a>
      <?php endif; ?>
    </nav>
  </div>
</header>