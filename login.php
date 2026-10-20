<?php
/**
 * iCareDonate — Login Page
 * login.php
 *
 * Renders the login form and injects server-side session state into the page.
 * If the user is already logged in, redirects immediately to their portal.
 *
 * On form submission, the form POSTs to actions/login_process.php.
 * Error messages are passed back as URL parameters.
 */

require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to the right portal
if (isLoggedIn()) {
    $root = _icd_root();
    if (currentRole() === 'admin') {
        header('Location: ' . $root . ICD_REDIRECT_ADMIN);
    } else {
        header('Location: ' . $root . ICD_REDIRECT_USER);
    }
    exit;
}

require_once __DIR__ . '/includes/demo_accounts.php';

// Read error message passed from login_process.php
$errorMsg = htmlspecialchars(trim($_GET['error'] ?? ''), ENT_QUOTES, 'UTF-8');

// Base path for assets (from project root, frontend/ holds all static files)
$base = 'frontend/';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log In — iCareDonate</title>
  <meta name="description" content="Sign in to continue to iCareDonate community platform.">
  <meta name="theme-color" content="#1F4FA3">

  <script>
    document.documentElement.setAttribute('data-theme', 'light');
    try { localStorage.removeItem('icd-theme'); } catch(e) {}
    // Inject PHP session state for use by public JS (nav.js auth check)
    window.ICD_SESSION = <?php echo json_encode([
      'loggedIn' => isLoggedIn(),
      'role'     => currentRole(),
      'name'     => currentName(),
    ]); ?>;
  </script>

  <link rel="icon" type="image/svg+xml" href="<?= $base ?>assets/images/logo.svg">
  <link rel="stylesheet" href="<?= $base ?>css/variables.css">
  <link rel="stylesheet" href="<?= $base ?>css/base.css">
  <link rel="stylesheet" href="<?= $base ?>css/layout.css">
  <link rel="stylesheet" href="<?= $base ?>css/components.css">
  <link rel="stylesheet" href="<?= $base ?>css/pages/auth.css">
</head>
<body>

  <a href="#main-content" class="skip-link">Skip to main content</a>

  <!-- Public Navigation Bar -->
  <header class="header-public nav-public" role="banner">
    <div class="container nav-public__inner">
      <a href="<?= $base ?>index.html" class="logo" aria-label="iCareDonate — Home">
        <img src="<?= $base ?>assets/images/logo.svg" class="logo-icon" width="36" height="36" alt="iCareDonate Logo">
        <span class="logo-wordmark">i<em>Care</em>Donate</span>
      </a>

      <nav class="nav-public__links" aria-label="Main navigation">
        <a href="<?= $base ?>index.html" class="nav-link">Home</a>
        <a href="<?= $base ?>about.html" class="nav-link">About us</a>
        <a href="<?= $base ?>how-it-works.html" class="nav-link">How it works</a>
        <a href="<?= $base ?>categories.html" class="nav-link">Ways to Help</a>
        <a href="<?= $base ?>contact.html" class="nav-link">Contact us</a>
      </nav>

      <div class="nav-public__actions">
        <a href="login.php" class="btn-pub-auth active">Log in / Sign up</a>
      </div>

      <button class="nav-hamburger" id="nav-hamburger" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
      </button>
    </div>

    <!-- Mobile Drawer -->
    <div class="mobile-menu" id="mobile-menu" role="dialog" aria-label="Mobile navigation" hidden>
      <nav class="mobile-menu__links" aria-label="Mobile navigation">
        <a href="<?= $base ?>index.html" class="mobile-link">Home</a>
        <a href="<?= $base ?>about.html" class="mobile-link">About us</a>
        <a href="<?= $base ?>how-it-works.html" class="mobile-link">How it works</a>
        <a href="<?= $base ?>categories.html" class="mobile-link">Ways to Help</a>
        <a href="<?= $base ?>contact.html" class="mobile-link">Contact us</a>
        <a href="login.php" class="mobile-link mobile-link--accent active">Log in / Sign up</a>
      </nav>
    </div>
  </header>

  <!-- Main Content -->
  <main class="login-page-wrap" id="main-content">
    <div class="login-container">

      <!-- LEFT CARD — Brand Card -->
      <aside class="login-brand-card" aria-label="iCareDonate platform introduction">
        <div class="login-brand__top">
          <img src="<?= $base ?>assets/images/logo.svg" class="login-brand__logo-img" width="38" height="38" alt="iCareDonate">
          <a href="<?= $base ?>index.html" class="login-brand__wordmark">iCare<em>Donate</em></a>
        </div>

        <div class="login-brand__body">
          <div>
            <div class="login-brand__pill">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
              Community Sharing Platform
            </div>
            <h2 class="login-brand__heading">Small acts.<br>Real impact.</h2>
            <p class="login-brand__sub">Give what you can. Receive what you need.</p>
          </div>

          <figure class="login-brand__illus-wrap">
            <img src="<?= $base ?>assets/images/login-brand.jpg"
                 alt="Illustration of helpful hands holding an amber heart surrounded by leaves"
                 class="login-brand__illus" loading="eager">
          </figure>

          <ul class="login-brand__benefits" aria-label="Platform benefits">
            <li class="login-brand__benefit">
              <div class="login-brand__icon-box" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
              </div>
              <div>
                <strong class="login-brand__benefit-title">Give with purpose</strong>
                <span class="login-brand__benefit-desc">Share useful resources with others.</span>
              </div>
            </li>
            <li class="login-brand__benefit">
              <div class="login-brand__icon-box" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
              </div>
              <div>
                <strong class="login-brand__benefit-title">Support your community</strong>
                <span class="login-brand__benefit-desc">Connect people with needed resources.</span>
              </div>
            </li>
            <li class="login-brand__benefit">
              <div class="login-brand__icon-box" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
              </div>
              <div>
                <strong class="login-brand__benefit-title">One platform, shared care</strong>
                <span class="login-brand__benefit-desc">Donate, request, and discover resources.</span>
              </div>
            </li>
          </ul>
        </div>

        <hr class="login-brand__divider">
        <footer class="login-brand__footer-note">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M9.983 3v7.391c0 5.704-3.731 9.57-8.983 10.609l-.995-2.151c2.432-.917 3.995-3.638 3.995-5.849h-4v-10h9.983zm14.017 0v7.391c0 5.704-3.748 9.571-9 10.609l-.996-2.151c2.433-.917 3.996-3.638 3.996-5.849h-3.983v-10h9.983z"/></svg>
          <span><em>A little help can go a long way.</em></span>
        </footer>
      </aside>

      <!-- RIGHT CARD — Login Form -->
      <section class="login-form-card" aria-label="Sign in form">

        <div class="login-form-header">
          <h1 class="login-form-title">Welcome back!</h1>
          <p class="login-form-subtitle">Sign in to continue to iCareDonate.</p>
        </div>

        <!-- PHP-injected error message -->
        <?php if ($errorMsg !== ''): ?>
        <div class="alert alert-error" role="alert" style="margin-bottom:16px; padding:11px 14px; border-radius:8px; font-size:13.5px; display:flex; align-items:center; gap:10px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
          <span><?= $errorMsg ?></span>
        </div>
        <?php endif; ?>

        <!-- Quick Test Panel — local development only -->
        <!-- IMPORTANT: This panel is for classroom demonstration only.          -->
        <!-- Remove or gate with APP_ENV before any non-local deployment.        -->
        <div class="quick-test" id="quick-test-panel" role="region" aria-label="Quick test credentials">
          <div class="quick-test__label">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Quick test &mdash; click to prefill
          </div>
          <div class="quick-test__btns">
            <button type="button" class="qt-btn qt-btn--admin" id="qt-admin"
                    data-email="admin@icaredonate.test" data-pass="Admin@1234"
                    title="admin@icaredonate.test / Admin@1234">
              <span class="qt-btn-icon" aria-hidden="true">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
              </span>
              <span>Admin</span>
              <span class="qt-badge">admin</span>
            </button>
            <button type="button" class="qt-btn" id="qt-donor"
                    data-email="donor@icaredonate.test" data-pass="Donor@1234"
                    title="donor@icaredonate.test / Donor@1234">
              <span class="qt-btn-icon" aria-hidden="true">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
              </span>
              <span>Donor</span>
              <span class="qt-badge">donor</span>
            </button>
            <button type="button" class="qt-btn" id="qt-requester"
                    data-email="requester@icaredonate.test" data-pass="Requester@1234"
                    title="requester@icaredonate.test / Requester@1234">
              <span class="qt-btn-icon" aria-hidden="true">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              </span>
              <span>Requester</span>
              <span class="qt-badge">requester</span>
            </button>
          </div>
        </div>

        <!-- Login Form — POSTs to server, no JavaScript required to authenticate -->
        <form id="user-login-form" method="POST" action="actions/login_process.php" novalidate>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="auth-form-label" for="login-email">Email Address</label>
            <input type="email" id="login-email" name="email"
                   class="form-input auth-form-input"
                   required autocomplete="username" placeholder="name@example.com"
                   value="<?= htmlspecialchars($_GET['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-error"></div>
          </div>

          <div class="form-group" style="margin-bottom: 8px;">
            <label class="auth-form-label" for="login-password">Password</label>
            <div class="password-input-wrap">
              <input type="password" id="login-password" name="password"
                     class="form-input auth-form-input"
                     required autocomplete="current-password" placeholder="Enter your password">
              <button type="button" id="toggle-password" class="password-toggle-btn" aria-label="Show password" aria-pressed="false">
                <svg id="pw-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
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
            <div class="form-error"></div>
          </div>

          <div class="auth-forgot-wrap" style="margin-bottom: 20px;">
            <a href="<?= $base ?>forgot-password.html" class="auth-forgot">Forgot password?</a>
          </div>

          <button type="submit" class="btn btn-primary btn-block"
                  style="padding: 12px; font-size: 0.9375rem; border-radius: var(--radius-md);
                         font-weight: 600; margin-bottom: 18px; box-shadow: 0 4px 14px rgba(31,79,163,0.28); width: 100%;">
            Sign In
          </button>

          <hr class="auth-sep">
          <p class="auth-switch">Don't have an account? <a href="signup.php" id="link-to-signup">Sign Up</a></p>
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

  <!-- Footer -->
  <footer class="footer-public" role="contentinfo">
    <div class="container footer-public__container">
      <div class="footer-public__grid">
        <div class="footer-public__col footer-public__col--brand">
          <a href="<?= $base ?>index.html" class="logo footer-logo" aria-label="iCareDonate — Home">
            <img src="<?= $base ?>assets/images/logo.svg" class="logo-icon" width="36" height="36" alt="iCareDonate Logo">
            <span class="logo-wordmark">i<em>Care</em>Donate</span>
          </a>
          <p class="footer-tagline">Give what you can. Help where it matters.</p>
        </div>
        <div class="footer-public__col">
          <strong class="footer-heading">Explore</strong>
          <ul class="footer-links">
            <li><a href="<?= $base ?>donations.html">Browse Donations</a></li>
            <li><a href="<?= $base ?>categories.html">Ways to Help</a></li>
            <li><a href="<?= $base ?>how-it-works.html">How It Works</a></li>
          </ul>
        </div>
        <div class="footer-public__col">
          <strong class="footer-heading">About</strong>
          <ul class="footer-links">
            <li><a href="<?= $base ?>about.html">About Us</a></li>
            <li><a href="<?= $base ?>mission.html">Our Mission</a></li>
            <li><a href="<?= $base ?>careers.html">Careers &amp; Volunteers</a></li>
          </ul>
        </div>
        <div class="footer-public__col">
          <strong class="footer-heading">Help &amp; Legal</strong>
          <ul class="footer-links">
            <li><a href="<?= $base ?>help-center.html">Help Center</a></li>
            <li><a href="<?= $base ?>guides.html">Guides and Tutorials</a></li>
            <li><a href="<?= $base ?>faqs.html">Frequently Asked Questions</a></li>
            <li><a href="<?= $base ?>contact.html">Contact Support</a></li>
            <li><a href="<?= $base ?>terms.html">Terms of Service</a></li>
            <li><a href="<?= $base ?>privacy.html">Privacy Policy</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-public__bottom">
        <span>&copy; 2026 iCareDonate. All rights reserved.</span>
        <span class="footer-support-email">
          <a href="mailto:icaredonate.team@gmail.com">icaredonate.team@gmail.com</a>
        </span>
      </div>
    </div>
  </footer>

  <script src="<?= $base ?>js/theme.js"></script>
  <script src="<?= $base ?>js/nav.js"></script>
  <script src="<?= $base ?>js/main.js"></script>

  <script>
  (function () {
    'use strict';

    // Password show/hide toggle
    var toggleBtn  = document.getElementById('toggle-password');
    var passInput  = document.getElementById('login-password');
    var eyeIcon    = document.getElementById('pw-eye');
    var eyeOffIcon = document.getElementById('pw-eye-off');

    if (toggleBtn && passInput && eyeIcon && eyeOffIcon) {
      toggleBtn.addEventListener('click', function () {
        var isHidden = passInput.type === 'password';
        passInput.type = isHidden ? 'text' : 'password';
        eyeIcon.style.display    = isHidden ? 'none'   : 'inline';
        eyeOffIcon.style.display = isHidden ? 'inline' : 'none';
        toggleBtn.setAttribute('aria-label',   isHidden ? 'Hide password' : 'Show password');
        toggleBtn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
      });
    }

    // Caps Lock detection
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

    // Quick test autofill — fills the form fields, does NOT bypass PHP auth
    function prefill(email, pass) {
      var emailEl = document.getElementById('login-email');
      var passEl  = document.getElementById('login-password');
      if (emailEl) { emailEl.value = email; emailEl.focus(); }
      if (passEl)  { passEl.value  = pass; }
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
  })();
  </script>
</body>
</html>
