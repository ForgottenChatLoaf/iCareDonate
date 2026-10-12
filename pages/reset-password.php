<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$token = trim($_GET['token'] ?? '');
$validToken = false;
$userId = 0;

if (!empty($token)) {
    $stmt = $pdo->prepare('
        SELECT user_id, expires_at, used_at FROM password_resets
        WHERE token = ? AND used_at IS NULL AND expires_at > NOW()
        LIMIT 1
    ');
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    if ($reset) {
        $validToken = true;
        $userId = (int)$reset['user_id'];
    }
}

$pageTitle = 'Set New Password — iCareDonate';
$extraCss = 'public.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav-public.php';
?>

<main class="auth-page">
  <div class="container container--sm" style="max-width: 460px; margin: 60px auto; padding: 0 16px;">
    
    <?php renderFlash(); ?>

    <div class="card" style="padding: 32px; box-shadow: var(--shadow-md); border-radius: var(--radius-lg);">
      <div style="text-align: center; margin-bottom: 24px;">
        <h1 style="font-family: var(--font-brand); font-size: 28px; color: var(--color-primary); margin-bottom: 8px;">Create New Password</h1>
        <p style="color: var(--color-text-muted); font-size: 14px;">Enter a secure replacement password for your account.</p>
      </div>

      <?php if (!$validToken): ?>
        <div class="alert alert-error" style="margin-bottom: 20px;">
          This password reset token is invalid or has expired. Please request a new link.
        </div>
        <a href="/CommunityDono/pages/forgot-password.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
          Request New Link
        </a>
      <?php else: ?>
        <form action="/CommunityDono/api/auth/reset-password.php" method="POST" data-validate>
          <?= csrfField() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" class="form-input" required minlength="8" placeholder="At least 8 characters">
          </div>

          <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-input" required minlength="8" placeholder="Re-type new password">
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 15px;">
            Update Password &amp; Log In
          </button>
        </form>
      <?php endif; ?>

    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
