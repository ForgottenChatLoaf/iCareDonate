<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: /CommunityDono/admin/index.php');
    } else {
        header('Location: /CommunityDono/user/dashboard.php');
    }
    exit;
}

/* Retrieve and clear any prefilled email from previous failed attempt */
$savedEmail = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_email']);

/* Only show Quick Test panel on local/dev */
$isDevEnv = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1', '::1'], true);

$redirectParam = trim($_GET['redirect'] ?? '');

$pageTitle = 'Log In — iCareDonate';
$extraCss  = 'public.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav-public.php';
?>

<style>
/* ── Page Wrapper & Centered Container ───────────────────── */
.login-page-wrap {
  background: var(--bg);
  min-height: calc(100vh - 72px - 280px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  box-sizing: border-box;
  transition: background var(--transition-smooth);
}

.login-container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  align-items: stretch;
}

/* ── Left: Contained Brand / Info Card ───────────────────── */
.login-brand-card {
  position: relative;
  background: linear-gradient(150deg, #0E1A33 0%, #14213D 28%, #183870 65%, #1F4FA3 100%);
  color: #fff;
  border-radius: 20px;
  box-shadow: var(--shadow-md);
  border: 1px solid rgba(242, 184, 75, 0.22);
  padding: 40px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  overflow: hidden;
  z-index: 1;
  box-sizing: border-box;
  transition: transform var(--transition-smooth), box-shadow var(--transition-smooth);
}

.login-brand-card::before {
  content: '';
  position: absolute;
  top: -20%; right: -20%;
  width: 480px; height: 480px;
  background: radial-gradient(circle, rgba(242, 184, 75, 0.12) 0%, rgba(31, 79, 163, 0.05) 50%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
  z-index: 0;
}

.login-brand-card::after {
  content: '';
  position: absolute;
  bottom: -15%; left: -15%;
  width: 420px; height: 420px;
  background: radial-gradient(circle, rgba(31, 79, 163, 0.38) 0%, transparent 65%);
  border-radius: 50%;
  pointer-events: none;
  z-index: 0;
}

.login-brand__top,
.login-brand__body,
.login-brand__footer-note {
  position: relative;
  z-index: 1;
}

.login-brand__top {
  display: flex;
  align-items: center;
  gap: 12px;
}

.login-brand__logo-img {
  width: 38px;
  height: 38px;
  object-fit: contain;
  filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.3));
}

.login-brand__wordmark {
  font-family: var(--font-heading);
  font-weight: 700;
  font-size: 1.55rem;
  letter-spacing: -0.02em;
  color: #fff;
  text-decoration: none;
}
.login-brand__wordmark em {
  font-style: normal;
  color: #F2B84B;
}

.login-brand__body {
  display: flex;
  flex-direction: column;
  gap: 22px;
  margin: 28px 0;
}

.login-brand__pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(242, 184, 75, 0.14);
  border: 1px solid rgba(242, 184, 75, 0.35);
  color: #F2B84B;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  width: fit-content;
}
.login-brand__pill svg {
  width: 14px;
  height: 14px;
  fill: currentColor;
}

.login-brand__heading {
  font-family: var(--font-heading);
  font-size: 2.1rem;
  font-weight: 700;
  line-height: 1.15;
  letter-spacing: -0.02em;
  color: #fff;
  margin: 12px 0 6px;
}

.login-brand__sub {
  font-size: 0.95rem;
  color: #E3EEFB;
  line-height: 1.5;
  margin: 0;
  opacity: 0.92;
}

.login-brand__illus-wrap {
  width: 100%;
  max-width: 440px;
  height: 150px;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid rgba(227, 238, 251, 0.2);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
  margin: 4px 0;
  background: #0E1A33;
}
.login-brand__illus {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
}

.login-brand__benefits {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.login-brand__benefit {
  display: flex;
  align-items: center;
  gap: 14px;
}

.login-brand__icon-box {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(227, 238, 251, 0.12);
  border: 1px solid rgba(227, 238, 251, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.login-brand__icon-box svg {
  width: 18px;
  height: 18px;
  stroke: #F2B84B;
  stroke-width: 2.2;
}

.login-brand__benefit-title {
  display: block;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  line-height: 1.3;
}

.login-brand__benefit-desc {
  display: block;
  font-size: 0.8125rem;
  color: #CBD5E1;
  line-height: 1.3;
}

.login-brand__divider {
  border: none;
  border-top: 1px solid rgba(227, 238, 251, 0.16);
  margin: 8px 0 16px;
}

.login-brand__footer-note {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.875rem;
  color: #E3EEFB;
  opacity: 0.88;
}
.login-brand__footer-note svg {
  width: 16px;
  height: 16px;
  fill: #F2B84B;
  flex-shrink: 0;
}

/* ── Right: Contained Login Form Card ────────────────────── */
.login-form-card {
  background: var(--surface);
  border-radius: 20px;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border);
  padding: 40px 48px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-sizing: border-box;
  transition: background var(--transition-smooth), border-color var(--transition-smooth);
}

.login-form-header {
  margin-bottom: 24px;
}

.login-form-title {
  font-family: var(--font-heading);
  font-size: 2rem;
  font-weight: 700;
  color: var(--navy);
  margin: 0 0 6px;
  letter-spacing: -0.02em;
  line-height: 1.15;
}

.login-form-subtitle {
  font-size: 0.9375rem;
  color: var(--slate);
  margin: 0;
}

/* Quick Test Dev Panel */
.quick-test {
  background: var(--sky-light, #F0F6FD);
  border: 1px dashed #7EA5DC;
  border-radius: 12px;
  padding: 12px 14px;
  margin-bottom: 20px;
}

.quick-test__label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--cobalt);
  margin-bottom: 8px;
}

.quick-test__btns {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.qt-btn {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 8px 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--navy);
  font-family: var(--font-body);
  transition: all 0.15s ease-in-out;
}
.qt-btn:hover {
  background: var(--sky);
  border-color: var(--cobalt);
  color: var(--cobalt);
  transform: translateY(-1px);
}
.qt-btn--admin {
  border-color: rgba(242, 184, 75, 0.6);
  background: #FFFDF9;
}
.qt-btn--admin:hover {
  background: #FDF4E1;
  border-color: var(--gold);
}
.qt-badge {
  font-size: 0.6875rem;
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: 700;
  text-transform: uppercase;
  background: var(--sky);
  color: var(--cobalt);
}
.qt-btn--admin .qt-badge {
  background: #FDF4E1;
  color: #BC7F08;
}

.auth-form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--navy);
  margin-bottom: 6px;
}

.auth-form-input {
  width: 100%;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  padding: 12px 14px;
  font-size: 0.9375rem;
  font-family: var(--font-body);
  color: var(--navy);
  background: var(--surface);
  box-sizing: border-box;
  transition: border-color 0.18s ease, box-shadow 0.18s ease;
}
.auth-form-input:focus {
  outline: none;
  border-color: var(--cobalt);
  box-shadow: 0 0 0 3px rgba(31, 79, 163, 0.15);
}

.password-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.password-input-wrap input {
  padding-right: 44px;
}

.password-toggle-btn {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  padding: 4px;
  cursor: pointer;
  color: var(--slate);
  display: flex;
  align-items: center;
  justify-content: center;
}
.password-toggle-btn:hover {
  color: var(--cobalt);
}

.caps-lock-warn {
  display: none;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  color: #C0392B;
  margin-top: 6px;
  font-weight: 600;
}
.caps-lock-warn.active {
  display: flex;
}
.caps-lock-warn svg {
  width: 14px;
  height: 14px;
}

.auth-forgot-wrap {
  text-align: right;
  margin: 4px 0 18px;
}
.auth-forgot {
  font-size: 0.8125rem;
  color: var(--cobalt);
  text-decoration: none;
  font-weight: 600;
}
.auth-forgot:hover {
  text-decoration: underline;
}

.auth-sep {
  border: none;
  border-top: 1px solid var(--border);
  margin: 18px 0;
}

.auth-switch {
  text-align: center;
  font-size: 0.875rem;
  color: var(--slate);
  margin: 0;
}
.auth-switch a {
  color: var(--cobalt);
  font-weight: 600;
  text-decoration: none;
}
.auth-switch a:hover {
  text-decoration: underline;
}

.login-trust-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 0.8125rem;
  color: var(--slate);
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--border);
}
.login-trust-row svg {
  width: 16px;
  height: 16px;
  color: var(--cobalt);
}

/* ── Responsive adjustments ──────────────────────────────── */
@media (max-width: 991px) {
  .login-container {
    grid-template-columns: 1fr;
    max-width: 580px;
    gap: 32px;
  }
  .login-form-card {
    padding: 36px 32px;
  }
}

@media (max-width: 640px) {
  .login-page-wrap {
    padding: 24px 16px;
  }
  .login-brand-card {
    padding: 28px 22px;
  }
  .login-form-card {
    padding: 28px 22px;
  }
  .login-brand__heading {
    font-size: 1.7rem;
  }
  .quick-test__btns {
    grid-template-columns: 1fr;
  }
}

/* Dark theme overrides for login cards */
:root[data-theme="dark"] .login-form-card {
  background: var(--surface);
  border-color: var(--border);
}
:root[data-theme="dark"] .login-form-title {
  color: #fff;
}
:root[data-theme="dark"] .auth-form-label {
  color: #E3EEFB;
}
:root[data-theme="dark"] .quick-test {
  background: rgba(31, 79, 163, 0.15);
  border-color: rgba(242, 184, 75, 0.4);
}
:root[data-theme="dark"] .qt-btn {
  background: #14213D;
  color: #E3EEFB;
  border-color: #2A4374;
}
:root[data-theme="dark"] .qt-btn:hover {
  background: #1F4FA3;
  color: #fff;
}
:root[data-theme="dark"] .auth-form-input {
  background: #0E172A;
  border-color: #2A4374;
  color: #fff;
}
:root[data-theme="dark"] .auth-form-input:focus {
  border-color: #5B8DE8;
}
</style>

<main class="login-page-wrap">
  <div class="login-container">

    <!-- ══════════════════════════════════════════════════════════
         LEFT CARD — Contained Info / Brand Card
         ══════════════════════════════════════════════════════════ -->
    <aside class="login-brand-card" aria-label="iCareDonate platform introduction">

      <!-- 1. Logo & wordmark -->
      <div class="login-brand__top">
        <img src="<?= $base ?>/icaredonate-icon.svg" class="login-brand__logo-img" width="38" height="38" alt="iCareDonate">
        <a href="<?= $base ?>/" class="login-brand__wordmark">iCare<em>Donate</em></a>
      </div>

      <!-- 2-6. Main content group -->
      <div class="login-brand__body">

        <!-- 2. "Community Sharing Platform" Pill -->
        <div>
          <div class="login-brand__pill">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
            Community Sharing Platform
          </div>
          <!-- 3. Headline & 4. Subheadline -->
          <h2 class="login-brand__heading">Small acts.<br>Real impact.</h2>
          <p class="login-brand__sub">Give what you can. Receive what you need.</p>
        </div>

        <!-- 5. Community care illustration (contained, rounded, cover) -->
        <figure class="login-brand__illus-wrap">
          <img
            src="<?= $base ?>/docs/icaredonate_content/images/login_page/login_page_test_image.jpg"
            alt="Illustration of helpful hands holding an amber heart surrounded by leaves"
            class="login-brand__illus"
            loading="eager"
          >
        </figure>

        <!-- 6. 3 Feature Rows -->
        <ul class="login-brand__benefits" aria-label="Platform benefits">
          <li class="login-brand__benefit">
            <div class="login-brand__icon-box" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
              </svg>
            </div>
            <div>
              <strong class="login-brand__benefit-title">Give with purpose</strong>
              <span class="login-brand__benefit-desc">Share useful resources with others.</span>
            </div>
          </li>
          <li class="login-brand__benefit">
            <div class="login-brand__icon-box" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
              </svg>
            </div>
            <div>
              <strong class="login-brand__benefit-title">Support your community</strong>
              <span class="login-brand__benefit-desc">Connect people with needed resources.</span>
            </div>
          </li>
          <li class="login-brand__benefit">
            <div class="login-brand__icon-box" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
              </svg>
            </div>
            <div>
              <strong class="login-brand__benefit-title">One platform, shared care</strong>
              <span class="login-brand__benefit-desc">Donate, request, and discover resources.</span>
            </div>
          </li>
        </ul>

      </div>

      <!-- 7. Divider -->
      <hr class="login-brand__divider">

      <!-- 8. Italic quote -->
      <footer class="login-brand__footer-note">
        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M9.983 3v7.391c0 5.704-3.731 9.57-8.983 10.609l-.995-2.151c2.432-.917 3.995-3.638 3.995-5.849h-4v-10h9.983zm14.017 0v7.391c0 5.704-3.748 9.571-9 10.609l-.996-2.151c2.433-.917 3.996-3.638 3.996-5.849h-3.983v-10h9.983z"/>
        </svg>
        <span><em>A little help can go a long way.</em></span>
      </footer>

    </aside>


    <!-- ══════════════════════════════════════════════════════════
         RIGHT CARD — Contained Login Form Card
         ══════════════════════════════════════════════════════════ -->
    <section class="login-form-card" aria-label="Sign in form">

      <div class="login-form-header">
        <!-- Flash messages & error banners -->
        <?php renderFlash(); ?>

        <?php if (isset($_GET['e'])): ?>
          <?php if ($_GET['e'] === 'suspended'): ?>
            <div class="alert alert-warning alert--dismissible" style="margin-bottom: 16px;">
              <strong>Account Suspended:</strong> Your account is currently suspended. Please contact community support.
            </div>
          <?php elseif ($_GET['e'] === 'banned'): ?>
            <div class="alert alert-danger alert-error alert--dismissible" style="margin-bottom: 16px;">
              <strong>Account Banned:</strong> This account has been banned due to violation of community terms.
            </div>
          <?php elseif ($_GET['e'] === 'verified'): ?>
            <div class="alert alert-success alert--dismissible" style="margin-bottom: 16px;">
              <strong>Email Verified!</strong> You can now log in to your account.
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <h1 class="login-form-title">Welcome back!</h1>
        <p class="login-form-subtitle">Sign in to continue to iCareDonate.</p>
      </div>

      <!-- ── Quick Test Panel (dev / localhost only) ─────── -->
      <?php if ($isDevEnv): ?>
      <div class="quick-test" id="quick-test-panel" role="region" aria-label="Quick test credentials">
        <div class="quick-test__label">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2.2"
                  stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Quick test &mdash; click to prefill
        </div>
        <div class="quick-test__btns">
          <button type="button" class="qt-btn qt-btn--admin" id="qt-admin"
                  data-email="admin@icaredonate.test" data-pass="Admin@1234"
                  title="admin@icaredonate.test / Admin@1234">
            <span class="qt-btn-icon" aria-hidden="true">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
              </svg>
            </span>
            <span>Admin</span>
            <span class="qt-badge">admin</span>
          </button>

          <button type="button" class="qt-btn" id="qt-user"
                  data-email="user@icaredonate.test" data-pass="User@1234"
                  title="user@icaredonate.test / User@1234">
            <span class="qt-btn-icon" aria-hidden="true">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
            </span>
            <span>Regular User</span>
            <span class="qt-badge">user</span>
          </button>

          <button type="button" class="qt-btn" id="qt-donor"
                  data-email="donor@icaredonate.test" data-pass="Donor@1234"
                  title="donor@icaredonate.test / Donor@1234">
            <span class="qt-btn-icon" aria-hidden="true">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
              </svg>
            </span>
            <span>Donor</span>
            <span class="qt-badge">user</span>
          </button>
        </div>
      </div>
      <?php endif; ?>
      <!-- ─────────────────────────────────────────────────── -->

      <form action="/CommunityDono/api/auth/login.php" method="POST" id="login-form">
        <?= csrfField() ?>
        <input type="hidden" name="redirect" value="<?= e($redirectParam) ?>">

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="auth-form-label" for="email">Email Address</label>
          <input type="email" id="email" name="email"
                 class="form-input auth-form-input"
                 required autocomplete="username" placeholder="name@example.com"
                 value="<?= e($savedEmail) ?>" <?= empty($savedEmail) ? 'autofocus' : '' ?>>
        </div>

        <div class="form-group" style="margin-bottom: 8px;">
          <label class="auth-form-label" for="password">Password</label>
          <div class="password-input-wrap">
            <input type="password" id="password" name="password"
                   class="form-input auth-form-input"
                   required autocomplete="current-password" placeholder="Enter your password"
                   <?= !empty($savedEmail) ? 'autofocus' : '' ?>>
            <button type="button" id="toggle-password" class="password-toggle-btn" aria-label="Show password" aria-pressed="false">
              <svg id="pw-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <svg id="pw-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                <line x1="2" x2="22" y1="2" y2="22"/>
              </svg>
            </button>
          </div>
          <!-- Caps Lock warning -->
          <div id="caps-lock-warning" class="caps-lock-warn" role="status" aria-live="polite">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            Caps Lock is on
          </div>
        </div>

        <div class="auth-forgot-wrap" style="margin-bottom: 20px;">
          <a href="/CommunityDono/pages/forgot-password.php" class="auth-forgot">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-block"
                style="padding: 12px; font-size: 0.9375rem; border-radius: var(--radius-md);
                       font-weight: 600; margin-bottom: 18px; box-shadow: 0 4px 14px rgba(31,79,163,0.28);">
          Sign In
        </button>

        <hr class="auth-sep">
        <p class="auth-switch">Don't have an account? <a href="/CommunityDono/pages/signup.php">Sign Up</a></p>
      </form>

      <!-- Trust note -->
      <div class="login-trust-row">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <span>Your information is handled with care.</span>
      </div>

    </section>

  </div><!-- /.login-container -->
</main>

<script>
(function () {
  'use strict';

  // 1. Password show / hide toggle
  var toggleBtn  = document.getElementById('toggle-password');
  var passInput  = document.getElementById('password');
  var eyeIcon    = document.getElementById('pw-eye');
  var eyeOffIcon = document.getElementById('pw-eye-off');

  if (toggleBtn && passInput && eyeIcon && eyeOffIcon) {
    toggleBtn.addEventListener('click', function () {
      var isHidden = passInput.getAttribute('type') === 'password';
      passInput.setAttribute('type', isHidden ? 'text' : 'password');
      eyeIcon.style.display    = isHidden ? 'none'   : 'inline';
      eyeOffIcon.style.display = isHidden ? 'inline' : 'none';
      toggleBtn.setAttribute('aria-label',   isHidden ? 'Hide password' : 'Show password');
      toggleBtn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
    });
  }

  // 2. Caps Lock detection
  var capsWarn = document.getElementById('caps-lock-warning');
  if (passInput && capsWarn) {
    function checkCaps(e) {
      if (typeof e.getModifierState === 'function') {
        capsWarn.classList.toggle('active', e.getModifierState('CapsLock'));
      }
    }
    passInput.addEventListener('keydown', checkCaps);
    passInput.addEventListener('keyup', checkCaps);
  }

  // 3. Quick test autofill (local/dev only)
  <?php if ($isDevEnv): ?>
  function prefill(email, pass) {
    var emailEl = document.getElementById('email');
    var passEl  = document.getElementById('password');
    if (emailEl) { emailEl.value = email; emailEl.focus(); }
    if (passEl)  { passEl.value  = pass; }
    // Dispatch input events so any validation updates
    [emailEl, passEl].forEach(function (el) {
      if (el) el.dispatchEvent(new Event('input', { bubbles: true }));
    });
    var panel = document.getElementById('quick-test-panel');
    if (panel) {
      panel.style.borderColor = 'var(--cobalt)';
      setTimeout(function () { panel.style.borderColor = ''; }, 700);
    }
  }

  document.querySelectorAll('.qt-btn[data-email]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      prefill(btn.dataset.email, btn.dataset.pass);
    });
  });
  <?php endif; ?>
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>