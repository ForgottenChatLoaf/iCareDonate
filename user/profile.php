<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT id, email, first_name, last_name, phone, barangay, city, province, role, status, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . APP_BASE . '/pages/login.php');
    exit;
}

$pageTitle = 'Account Settings — iCareDonate';
$extraCss = 'user.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="user-layout">
  <?php require_once __DIR__ . '/../includes/nav-user.php'; ?>

  <div class="user-main">
    <header class="user-topbar">
      <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0;">Account Settings</h1>
      </div>
      <div>
        <a href="<?= $base ?>/user/dashboard.php" class="btn btn-outline" style="font-size: 13.5px; padding: 6px 14px;">
          &larr; Dashboard
        </a>
      </div>
    </header>

    <div class="user-content" style="max-width: 720px; margin: 0 auto;">
      <?php renderFlash(); ?>

      <div class="card" style="padding: 32px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); margin-bottom: 24px;">
        <h2 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin-bottom: 20px;">
          Personal &amp; Contact Details
        </h2>

        <form action="<?= $base ?>/api/user/update-profile.php" method="POST" data-validate>
          <?= csrfField() ?>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div class="form-group">
              <label class="form-label" for="first_name">First Name</label>
              <input type="text" id="first_name" name="first_name" class="form-input" required value="<?= e($user['first_name']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="last_name">Last Name</label>
              <input type="text" id="last_name" name="last_name" class="form-input" required value="<?= e($user['last_name']) ?>">
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="email">Email Address (Read-only)</label>
            <input type="email" id="email" class="form-input" disabled value="<?= e($user['email']) ?>" style="background: #F8FAFC;">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="phone">Phone / Mobile Number</label>
            <input type="tel" id="phone" name="phone" class="form-input" placeholder="09171234567" value="<?= e($user['phone'] ?? '') ?>">
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div class="form-group">
              <label class="form-label" for="barangay">Barangay</label>
              <input type="text" id="barangay" name="barangay" class="form-input" placeholder="San Antonio" value="<?= e($user['barangay'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="city">City / Municipality</label>
              <input type="text" id="city" name="city" class="form-input" placeholder="Pasig City" value="<?= e($user['city'] ?? '') ?>">
            </div>
          </div>

          <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 14px;">
            Save Profile Changes
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
