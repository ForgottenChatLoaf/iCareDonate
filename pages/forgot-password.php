<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pageTitle = 'Forgot Password — iCareDonate';
$extraCss = 'public.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav-public.php';
?>

<main class="auth-page">
  <div class="container container--sm" style="max-width: 460px; margin: 60px auto; padding: 0 16px;">
    
    <?php renderFlash(); ?>

    <div class="card" style="padding: 32px; box-shadow: var(--shadow-md); border-radius: var(--radius-lg);">
      <div style="text-align: center; margin-bottom: 24px;">
        <div style="margin-bottom: 8px; color: var(--color-primary);"><i data-lucide="lock-keyhole" style="width:40px;height:40px;"></i></div>
        <h1 style="font-family: var(--font-brand); font-size: 28px; color: var(--color-primary); margin-bottom: 8px;">Reset Your Password</h1>
        <p style="color: var(--color-text-muted); font-size: 14px;">Enter your registered email address and we will generate a password recovery link.</p>
      </div>

      <form action="<?= $base ?>/api/auth/forgot-password.php" method="POST" data-validate>
        <?= csrfField() ?>

        <div class="form-group" style="margin-bottom: 20px;">
          <label class="form-label" for="email">Account Email Address</label>
          <input type="email" id="email" name="email" class="form-input" required autocomplete="email" placeholder="you@example.com">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 15px; margin-bottom: 16px;">
          Send Reset Link
        </button>

        <div style="text-align: center; font-size: 14px; color: var(--color-text-muted);">
          Remember your password? <a href="<?= $base ?>/pages/login.php" style="color: var(--color-primary); font-weight: 600; text-decoration: none;">Log in</a>
        </div>
      </form>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
