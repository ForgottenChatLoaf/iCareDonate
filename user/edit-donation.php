<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

$userId = (int)$_SESSION['user_id'];
$donationId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($donationId <= 0) {
    header('Location: /CommunityDono/user/donations.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM donations WHERE id = ? AND donor_id = ?');
$stmt->execute([$donationId, $userId]);
$donation = $stmt->fetch();

if (!$donation || in_array($donation['status'], ['approved', 'fully_allocated', 'archived', 'cancelled', 'expired'], true)) {
    setFlash('error', 'This donation cannot be edited at its current stage.');
    header('Location: /CommunityDono/user/donations.php');
    exit;
}

$categories = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll();

$pageTitle = 'Edit Donation — ' . e($donation['title']);
$extraCss = 'user.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="user-layout">
  <?php require_once __DIR__ . '/../includes/nav-user.php'; ?>

  <div class="user-main">
    <header class="user-topbar">
      <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0;">Edit / Resubmit Donation</h1>
      </div>
      <div>
        <a href="/CommunityDono/user/donations.php" class="btn btn-outline" style="font-size: 13.5px; padding: 6px 14px;">
          &larr; My Donations
        </a>
      </div>
    </header>

    <div class="user-content" style="max-width: 840px; margin: 0 auto;">
      <?php renderFlash(); ?>

      <div class="card" style="padding: 32px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm);">
        <form action="/CommunityDono/api/donations/update.php" method="POST" enctype="multipart/form-data" data-validate>
          <?= csrfField() ?>
          <input type="hidden" name="donation_id" value="<?= (int)$donation['id'] ?>">

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="title">Donation Title / Item Name</label>
            <input type="text" id="title" name="title" class="form-input" required value="<?= e($donation['title']) ?>">
          </div>

          <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div class="form-group">
              <label class="form-label" for="category_id">Donation Category</label>
              <select id="category_id" name="category_id" class="form-input" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>" <?= (int)$cat['id'] === (int)$donation['category_id'] ? 'selected' : '' ?>>
                    <?= e($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="condition_type">Donation Condition</label>
              <select id="condition_type" name="condition_type" class="form-input" required>
                <option value="new" <?= $donation['condition_type'] === 'new' ? 'selected' : '' ?>>New</option>
                <option value="good" <?= $donation['condition_type'] === 'good' ? 'selected' : '' ?>>Good</option>
                <option value="fair" <?= $donation['condition_type'] === 'fair' ? 'selected' : '' ?>>Used / Fair</option>
              </select>
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div class="form-group">
              <label class="form-label" for="quantity_total">Quantity</label>
              <input type="number" id="quantity_total" name="quantity_total" class="form-input" required min="1" value="<?= (int)$donation['quantity_total'] ?>">
            </div>

            <div class="form-group">
              <label class="form-label" for="unit">Unit</label>
              <input type="text" id="unit" name="unit" class="form-input" required value="<?= e($donation['unit']) ?>">
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="description">Item Description</label>
            <textarea id="description" name="description" class="form-input" rows="4"><?= e($donation['description'] ?? '') ?></textarea>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div class="form-group">
              <label class="form-label" for="pickup_city">City / Municipality</label>
              <input type="text" id="pickup_city" name="pickup_city" class="form-input" required value="<?= e($donation['pickup_city'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label class="form-label" for="pickup_barangay">Barangay / Area</label>
              <input type="text" id="pickup_barangay" name="pickup_barangay" class="form-input" value="<?= e($donation['pickup_barangay'] ?? '') ?>">
            </div>
          </div>

          <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <a href="/CommunityDono/user/donations.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Save &amp; Resubmit for Verification</button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
