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

.auth-hint {
  background: var(--sky-light, #F0F6FD);
  border: 1px solid rgba(31, 79, 163, 0.2);
  border-radius: 10px;
  padding: 12px 14px;
  font-size: 0.8125rem;
  color: var(--slate);
  line-height: 1.5;
  margin-bottom: 20px;
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
}

/* Dark theme overrides */
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
         RIGHT CARD — Contained Signup Form Card
         ══════════════════════════════════════════════════════════ -->
    <section class="login-form-card" aria-label="Sign up form">

      <div class="login-form-header">
        <?php renderFlash(); ?>
        <h1 class="login-form-title">Create Account</h1>
        <p class="login-form-subtitle">One account to donate and request help &mdash; all in one place.</p>
      </div>

      <form action="/CommunityDono/api/auth/register.php" method="POST" data-validate id="signup-form">
        <?= csrfField() ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
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

        <div class="form-group" style="margin-bottom: 14px;">
          <label class="auth-form-label" for="email">Email Address</label>
          <input type="email" id="email" name="email"
                 class="form-input auth-form-input" required autocomplete="email" placeholder="you@example.com">
        </div>

        <div class="form-group" style="margin-bottom: 14px;">
          <label class="auth-form-label" for="password">Password</label>
          <div class="password-input-wrap">
            <input type="password" id="password" name="password"
                   class="form-input auth-form-input" required minlength="8" placeholder="At least 8 characters">
            <button type="button" id="toggle-password" class="password-toggle-btn" aria-label="Show password">
              <svg id="pw-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="auth-form-label" for="confirm_password">Confirm Password</label>
          <div class="password-input-wrap">
            <input type="password" id="confirm_password" name="confirm_password"
                   class="form-input auth-form-input" required minlength="8" placeholder="Repeat your password">
            <button type="button" id="toggle-confirm" class="password-toggle-btn" aria-label="Show password">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="auth-hint">
          After you log in, you can donate or request help. Proof is only asked when you post
          your first donation or submit your first request.
        </div>

        <button type="submit" class="btn btn-primary btn-block"
                style="padding: 12px; font-size: 0.9375rem; border-radius: var(--radius-md);
                       font-weight: 600; margin-bottom: 18px; box-shadow: 0 4px 14px rgba(31,79,163,0.28); width: 100%;">
          Create Account
        </button>

        <hr class="auth-sep">
        <p class="auth-switch">Already have an account? <a href="/CommunityDono/pages/login.php">Log in</a></p>
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
(function() {
  function setupToggle(btnId, inputId) {
    var btn = document.getElementById(btnId);
    var inp = document.getElementById(inputId);
    if (btn && inp) {
      btn.addEventListener('click', function() {
        var isPw = inp.getAttribute('type') === 'password';
        inp.setAttribute('type', isPw ? 'text' : 'password');
      });
    }
  }
  setupToggle('toggle-password', 'password');
  setupToggle('toggle-confirm', 'confirm_password');
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>