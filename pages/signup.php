<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isLoggedIn()) {
    header('Location: /CommunityDono/user/dashboard.php');
    exit;
}

$pageTitle = 'Create Account — iCareDonate';
$extraCss  = 'public.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav-public.php';
?>

<style>
/* ── Auth page layout (shared with login) ──────────────── */
.auth-page {
  padding: 40px 0 80px;
  background: var(--bg);
  min-height: calc(100vh - 72px);
}

.auth-card {
  background: var(--surface);
  border: 1px solid var(--sky);
  border-radius: 20px;
  box-shadow: var(--shadow-md);
  padding: 40px 36px;
  transition: background var(--transition-smooth), border-color var(--transition-smooth);
}

.auth-card__title {
  font-family: var(--font-heading);
  font-size: 1.75rem;
  color: var(--cobalt);
  margin: 0 0 6px;
  font-weight: 700;
  line-height: 1.15;
  letter-spacing: -0.02em;
}

.auth-card__sub {
  font-family: var(--font-body);
  font-size: var(--text-sm);
  color: var(--slate);
  margin: 0;
  line-height: 1.5;
}

.auth-form-label {
  font-family: var(--font-body);
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--text);
  display: block;
  margin-bottom: 6px;
}

.auth-form-input {
  border-radius: var(--radius-md);
  padding: 11px 14px;
  font-family: var(--font-body);
  font-size: 0.875rem;
}

.auth-sep {
  border: none;
  border-top: 1px solid var(--border);
  margin: 0 0 16px;
}

.auth-switch {
  font-family: var(--font-body);
  font-size: 0.875rem;
  color: var(--text-muted);
  text-align: center;
  margin: 0;
}

.auth-switch a {
  color: var(--cobalt);
  font-weight: 600;
  text-decoration: none;
}

.auth-switch a:hover {
  color: var(--brand-primary-dark);
  text-decoration: underline;
}

.auth-hint {
  background: var(--surface-soft);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 12px 14px;
  font-family: var(--font-body);
  font-size: var(--text-sm);
  color: var(--text-muted);
  line-height: 1.6;
}
</style>

<main class="auth-page">
  <div style="width: min(520px, calc(100% - 32px)); margin: 20px auto;">

    <?php renderFlash(); ?>

    <div class="auth-card">
      <div style="text-align: center; margin-bottom: 28px;">
        <a href="<?= $base ?>/" class="logo" style="justify-content: center; margin-bottom: 14px;">
          <img src="<?= $base ?>/icaredonate-icon.svg" class="logo-icon" width="42" height="42" alt="iCareDonate">
          <span class="logo-wordmark" style="font-size: 1.6rem;">i<em>Care</em>Donate</span>
        </a>
        <h1 class="auth-card__title">Create Account</h1>
        <p class="auth-card__sub">One account to donate and request help &mdash; all in one place.</p>
      </div>

      <form action="/CommunityDono/api/auth/register.php" method="POST" data-validate>
        <?= csrfField() ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
          <div class="form-group">
            <label class="auth-form-label" for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name"
                   class="form-input auth-form-input" required placeholder="Maria">
          </div>
          <div class="form-group">
            <label class="auth-form-label" for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name"
                   class="form-input auth-form-input" required placeholder="Santos">
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="auth-form-label" for="email">Email Address</label>
          <input type="email" id="email" name="email"
                 class="form-input auth-form-input" required autocomplete="email" placeholder="you@example.com">
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="auth-form-label" for="password">Password</label>
          <input type="password" id="password" name="password"
                 class="form-input auth-form-input" required minlength="8" placeholder="At least 8 characters">
        </div>

        <div class="form-group" style="margin-bottom: 18px;">
          <label class="auth-form-label" for="confirm_password">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password"
                 class="form-input auth-form-input" required minlength="8" placeholder="Repeat your password">
        </div>

        <div class="auth-hint" style="margin-bottom: 20px;">
          After you log in, you can donate or request help. Proof is only asked when you post
          your first donation or submit your first request.
        </div>

        <button type="submit" class="btn btn-primary btn-block"
                style="padding: 12px; font-size: 0.9375rem; border-radius: var(--radius-md);
                       margin-bottom: 20px; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);">
          Create Account
        </button>

        <hr class="auth-sep">
        <p class="auth-switch">Already have an account? <a href="/CommunityDono/pages/login.php">Log in</a></p>
      </form>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>