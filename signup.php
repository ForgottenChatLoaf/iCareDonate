<?php
/**
 * iCareDonate — Account Registration Page
 * signup.php
 *
 * Renders the registration form with server-side validation and PHP session state.
 * If the user is already logged in, redirects immediately to their portal.
 *
 * Form POSTs to actions/signup_process.php.
 * Errors are displayed via the $_GET['error'] parameter.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to portal
if (isLoggedIn()) {
    $root = _icd_root();
    if (currentRole() === 'admin') {
        header('Location: ' . $root . 'admin/index.php');
    } else {
        header('Location: ' . $root . 'user/index.php');
    }
    exit;
}

$errorMsg = htmlspecialchars(trim($_GET['error'] ?? ''), ENT_QUOTES, 'UTF-8');
$successMsg = htmlspecialchars(trim($_GET['success'] ?? ''), ENT_QUOTES, 'UTF-8');
$base = 'frontend/';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account | iCareDonate</title>
  <meta name="description" content="Create an account to donate or request help on iCareDonate community platform.">
  <meta name="theme-color" content="#1F4FA3">

  <script>
    document.documentElement.setAttribute('data-theme', 'light');
    try { localStorage.removeItem('icd-theme'); } catch(e) {}
    window.ICD_SESSION = <?= json_encode([
      'loggedIn' => isLoggedIn(),
      'role'     => currentRole(),
      'name'     => currentName(),
    ]) ?>;
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

  <!-- Header / Navigation Bar -->
  <header class="header-public nav-public" role="banner">
    <div class="container nav-public__inner">
      <a href="<?= $base ?>index.html" class="logo" aria-label="iCareDonate: Home">
        <img src="<?= $base ?>assets/images/logo.svg" class="logo-icon" width="36" height="36" alt="iCareDonate Logo">
        <span class="logo-wordmark">i<em>Care</em>Donate</span>
      </a>

      <nav class="nav-public__links" aria-label="Main navigation">
        <a href="<?= $base ?>index.html" class="nav-link">Home</a>
        <a href="<?= $base ?>about.html" class="nav-link">About us</a>
        <a href="<?= $base ?>categories.html" class="nav-link">Ways to Help</a>
        <a href="<?= $base ?>how-it-works.html" class="nav-link">How it works</a>
        <a href="<?= $base ?>contact.html" class="nav-link">Contact us</a>
      </nav>

      <div class="nav-public__actions">
        <a href="login.php" class="btn-pub-auth">Log in / Sign up</a>
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
        <a href="<?= $base ?>categories.html" class="mobile-link">Ways to Help</a>
        <a href="<?= $base ?>how-it-works.html" class="mobile-link">How it works</a>
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
                 alt="Illustration of helpful hands holding an amber heart"
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

      <!-- RIGHT CARD — Signup Form -->
      <section class="login-form-card" aria-label="Sign up form">

        <div class="login-form-header">
          <h1 class="login-form-title">Create Account</h1>
          <p class="login-form-subtitle">One account to donate and request help &mdash; all in one place.</p>
        </div>

        <?php if ($errorMsg !== ''): ?>
        <div class="alert alert-error" role="alert" style="margin-bottom:16px; padding:11px 14px; border-radius:8px; font-size:13.5px; display:flex; align-items:center; gap:10px; background:#FEE2E2; color:#991B1B; border:1px solid #FECACA;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
          <span><?= $errorMsg ?></span>
        </div>
        <?php endif; ?>

        <form id="user-signup-form" method="POST" action="actions/signup_process.php" novalidate>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
            <div class="form-group">
              <label class="auth-form-label" for="signup-name">First Name</label>
              <input type="text" id="signup-name" name="first_name"
                     class="form-input auth-form-input" required placeholder="Maria"
                     value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              <div class="form-error"></div>
            </div>
            <div class="form-group">
              <label class="auth-form-label" for="signup-lastname">Last Name</label>
              <input type="text" id="signup-lastname" name="last_name"
                     class="form-input auth-form-input" required placeholder="Santos"
                     value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              <div class="form-error"></div>
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 14px;">
            <label class="auth-form-label" for="signup-email">Email Address</label>
            <input type="email" id="signup-email" name="email"
                   class="form-input auth-form-input" required autocomplete="email" placeholder="you@example.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-error"></div>
          </div>

          <div class="form-group" style="margin-bottom: 14px;">
            <label class="auth-form-label" for="signup-role">I would like to</label>
            <select id="signup-role" name="role" class="form-input auth-form-input" style="height:44px;">
              <option value="donor" selected>Offer donations to others (Donor)</option>
              <option value="requester">Request community items (Beneficiary)</option>
            </select>
          </div>

          <div class="form-group" style="margin-bottom: 14px;">
            <label class="auth-form-label" for="signup-password">Password</label>
            <div class="password-input-wrap">
              <input type="password" id="signup-password" name="password"
                     class="form-input auth-form-input" required minlength="8" placeholder="At least 8 characters">
              <button type="button" id="toggle-signup-password" class="password-toggle-btn" aria-label="Show password">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
            <div class="form-error"></div>
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="auth-form-label" for="signup-confirm-password">Confirm Password</label>
            <div class="password-input-wrap">
              <input type="password" id="signup-confirm-password" name="confirm_password"
                     class="form-input auth-form-input" required minlength="8" placeholder="Repeat your password">
              <button type="button" id="toggle-confirm-password" class="password-toggle-btn" aria-label="Show password">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
            <div class="form-error"></div>
          </div>

          <div class="auth-hint" style="margin-bottom: 20px;">
            After you log in, you can donate or request help. Proof is only asked when you post
            your first donation or submit your first request.
          </div>

          <button type="submit" class="btn btn-primary btn-block"
                  style="padding: 12px; font-size: 0.9375rem; border-radius: var(--radius-md);
                         font-weight: 600; margin-bottom: 18px; box-shadow: 0 4px 14px rgba(31,79,163,0.28); width: 100%;">
            Create Account
          </button>

          <hr class="auth-sep">
          <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
        </form>

        <div class="login-trust-row">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
          <span>Your information is handled with care.</span>
        </div>

      </section>

    </div>
  </main>

  <!-- Footer -->
  <footer class="footer-public" role="contentinfo">
    <div class="container footer-public__container">
      <div class="footer-public__grid">
        <div class="footer-public__col footer-public__col--brand">
          <a href="<?= $base ?>index.html" class="logo footer-logo" aria-label="iCareDonate: Home">
            <img src="<?= $base ?>assets/images/logo.svg" class="logo-icon" width="36" height="36" alt="iCareDonate Logo">
            <span class="logo-wordmark">i<em>Care</em>Donate</span>
          </a>
          <p class="footer-tagline">Give what you can. Help where it matters.</p>
        </div>

        <div class="footer-public__col">
          <strong class="footer-heading">Platform</strong>
          <ul class="footer-links">
            <li><a href="<?= $base ?>donations.html">Browse Donations</a></li>
            <li><a href="<?= $base ?>categories.html">Categories</a></li>
            <li><a href="<?= $base ?>how-it-works.html">How It Works</a></li>
          </ul>
        </div>

        <div class="footer-public__col">
          <strong class="footer-heading">About</strong>
          <ul class="footer-links">
            <li><a href="<?= $base ?>about.html">About Us</a></li>
            <li><a href="<?= $base ?>mission.html">Mission &amp; Values</a></li>
            <li><a href="<?= $base ?>careers.html">Careers</a></li>
          </ul>
        </div>

        <div class="footer-public__col">
          <strong class="footer-heading">Help &amp; Legal</strong>
          <ul class="footer-links">
            <li><a href="<?= $base ?>help-center.html">Help Center</a></li>
            <li><a href="<?= $base ?>faqs.html">Guides &amp; FAQs</a></li>
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
  <script>
  (function() {
    function setupToggle(btnId, inputId) {
      var btn = document.getElementById(btnId);
      var inp = document.getElementById(inputId);
      if (btn && inp) {
        btn.addEventListener('click', function() {
          var isPw = inp.type === 'password';
          inp.type = isPw ? 'text' : 'password';
        });
      }
    }
    setupToggle('toggle-signup-password', 'signup-password');
    setupToggle('toggle-confirm-password', 'signup-confirm-password');
  })();
  </script>
</body>
</html>
