<?php
/**
 * includes/brand-sidebar.php
 * Reusable iCareDonate Sidebar/Header Brand Component.
 * Applicable to both Admin and User dashboards.
 *
 * Configurable parameters (set before include or defaults will apply):
 * - $brandRole  : 'ADMIN' | 'USER' | 'DONOR' (default: 'ADMIN')
 * - $brandAlign : 'left' | 'center' (default: 'left')
 * - $brandLink  : URL string (default: '/CommunityDono/admin/' or '/CommunityDono/user/dashboard.php')
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';

$base = $base ?? APP_BASE;
$brandRole = $brandRole ?? 'ADMIN';
$brandAlign = $brandAlign ?? 'left';
$brandLink = $brandLink ?? ($brandRole === 'ADMIN' ? $base . '/admin/' : $base . '/user/dashboard.php');
$alignClass = ($brandAlign === 'center') ? 'brand-sidebar-container--center' : 'brand-sidebar-container--left';
?>

<div class="brand-sidebar-container <?= e($alignClass) ?>" role="banner">
  <!-- Top Logo Row: Heart Icon + Wordmark (horizontal, 8px gap) -->
  <a href="<?= e($brandLink) ?>" class="brand-sidebar-logo" aria-label="iCareDonate <?= e($brandRole) ?>">
    <!-- Heart-shaped icon: royal blue (#1E4DA1) with white handshake and golden cuff accent -->
    <img src="<?= $base ?>/icaredonate-icon.svg"
         class="brand-sidebar-icon"
         alt="iCareDonate Logo"
         width="32"
         height="32">
    <!-- Wordmark: "i" in blue (#1E4DA1), "Care" in golden orange (#D98A1B), "Donate" in blue (#1E4DA1) -->
    <span class="brand-sidebar-wordmark">
      <span class="brand-split-blue">i</span><span class="brand-split-orange">Care</span><span class="brand-split-blue">Donate</span>
    </span>
  </a>

  <!-- Role Badge: Directly BELOW the logo, 6px spacing, pill shape -->
  <?php if (!empty($brandRole)): ?>
    <span class="brand-sidebar-badge brand-sidebar-badge--<?= strtolower(e($brandRole)) ?>" aria-label="<?= e($brandRole) ?> Portal">
      <?= e(strtoupper($brandRole)) ?>
    </span>
  <?php endif; ?>
</div>

<style>
/* ── Sidebar/Header Brand Component Styling ─────────────── */
:root {
  --brand-blue: #1E4DA1;
  --brand-orange: #D98A1B;
  --brand-badge-bg: #DCE8F7;
  --brand-badge-border: #C4D9F8;
  --brand-badge-text: #1E4DA1;
}

:root[data-theme="dark"] {
  --brand-blue: #5B8DE8;
  --brand-orange: #F2B84B;
  --brand-badge-bg: rgba(91, 141, 232, 0.16);
  --brand-badge-border: rgba(91, 141, 232, 0.35);
  --brand-badge-text: #8AB4F8;
}

.brand-sidebar-container {
  display: flex;
  flex-direction: column;
  padding: 16px;
  background-color: var(--color-surface, #FFFFFF);
  border-bottom: 1px solid var(--color-border);
  box-sizing: border-box;
  width: 100%;
  flex-shrink: 0;
  transition: background-color var(--transition-base, 0.2s), border-color var(--transition-base, 0.2s);
}

.brand-sidebar-container--left {
  align-items: flex-start;
}

.brand-sidebar-container--center {
  align-items: center;
}

.brand-sidebar-logo {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  line-height: 1;
}

.brand-sidebar-logo:hover .brand-sidebar-wordmark {
  opacity: 0.95;
}

.brand-sidebar-icon {
  width: 32px;
  height: 32px;
  display: block;
  flex-shrink: 0;
  object-fit: contain;
}

.brand-sidebar-wordmark {
  font-family: var(--font-heading, 'Poppins', sans-serif);
  font-size: 18.5px;
  font-weight: 800;
  letter-spacing: -0.025em;
  white-space: nowrap;
  user-select: none;
}

.brand-split-blue {
  color: var(--brand-blue);
  transition: color var(--transition-base, 0.2s);
}

.brand-split-orange {
  color: var(--brand-orange);
  transition: color var(--transition-base, 0.2s);
}

/* Badge placed directly below logo with ~6px vertical spacing */
.brand-sidebar-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-top: 6px;
  padding: 2px 10px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-radius: 9999px;
  background-color: var(--brand-badge-bg);
  color: var(--brand-badge-text);
  border: 1px solid var(--brand-badge-border);
  line-height: 1.35;
  white-space: nowrap;
  user-select: none;
  transition: all var(--transition-base, 0.2s);
}

/* User / Donor Badge color variation if desired */
.brand-sidebar-badge--user {
  background-color: var(--color-surface-soft, #E3EEFB);
  color: var(--brand-blue);
  border-color: var(--color-border);
}

/* Responsive Collapsed Sidebar: hide wordmark, keep icon & badge */
.sidebar-collapsed .brand-sidebar-wordmark,
.admin-sidebar--collapsed .brand-sidebar-wordmark,
.user-sidebar--collapsed .brand-sidebar-wordmark {
  display: none !important;
}

.sidebar-collapsed .brand-sidebar-container,
.admin-sidebar--collapsed .brand-sidebar-container,
.user-sidebar--collapsed .brand-sidebar-container {
  align-items: center !important;
  padding: 12px 6px !important;
}

.sidebar-collapsed .brand-sidebar-badge,
.admin-sidebar--collapsed .brand-sidebar-badge,
.user-sidebar--collapsed .brand-sidebar-badge {
  font-size: 9px !important;
  padding: 1px 6px !important;
  letter-spacing: 0.2px !important;
}
</style>
