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
    header('Location: /CommunityDono/pages/browse.php');
    exit;
}

// Fetch donation
$stmt = $pdo->prepare('
    SELECT d.*, c.name AS category_name, u.first_name AS donor_first, u.last_name AS donor_last
    FROM donations d
    JOIN categories c ON d.category_id = c.id
    JOIN users u ON d.donor_id = u.id
    WHERE d.id = ? AND d.status = "approved"
    LIMIT 1
');
$stmt->execute([$donationId]);
$donation = $stmt->fetch();

if (!$donation) {
    setFlash('error', 'The requested donation is no longer available.');
    header('Location: /CommunityDono/pages/browse.php');
    exit;
}

if ((int)$donation['donor_id'] === $userId) {
    setFlash('error', 'You cannot request your own donation listing.');
    header('Location: /CommunityDono/user/donations.php');
    exit;
}

$available = (int)$donation['quantity_total'] - (int)$donation['quantity_reserved'] - (int)$donation['quantity_claimed'];
if ($available <= 0) {
    setFlash('error', 'This item has already been fully allocated.');
    header('Location: /CommunityDono/pages/donation-details.php?id=' . $donationId);
    exit;
}

// Fetch user defaults
$userStmt = $pdo->prepare('SELECT first_name, last_name, phone, barangay, city FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$userProfile = $userStmt->fetch() ?: [];

$pageTitle = 'Request Resource — ' . e($donation['title']);
$extraCss = 'user.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="user-layout">
  <?php require_once __DIR__ . '/../includes/nav-user.php'; ?>

  <div class="user-main">
    <header class="user-topbar">
      <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0;">Submit Resource Request</h1>
      </div>
      <div>
        <a href="/CommunityDono/pages/donation-details.php?id=<?= $donationId ?>" class="btn btn-outline" style="font-size: 13.5px; padding: 6px 14px;">
          &larr; Back to Item Details
        </a>
      </div>
    </header>

    <div class="user-content" style="max-width: 800px; margin: 0 auto;">
      <?php renderFlash(); ?>

      <!-- Resource Summary Header -->
      <div class="card" style="padding: 20px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 24px; background: var(--color-tint-teal);">
        <div style="font-size: 11.5px; color: var(--color-primary); font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Target Resource</div>
        <h2 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0 0 6px;"><?= e($donation['title']) ?></h2>
        <div style="font-size: 13px; color: var(--color-text-muted);">
          Category: <strong><?= e($donation['category_name']) ?></strong> · Available: <strong style="color: var(--color-primary);"><?= $available ?> <?= e($donation['unit']) ?></strong> · Location: <?= e($donation['pickup_city']) ?>
        </div>
      </div>

      <!-- Request Form -->
      <div class="card" style="padding: 32px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm);">
        <form id="request-form" action="/CommunityDono/api/requests/create.php" method="POST" enctype="multipart/form-data" data-validate>
          <?= csrfField() ?>
          <input type="hidden" name="donation_id" value="<?= $donationId ?>">

          <!-- 1. Request Details -->
          <div style="margin-bottom: 24px;">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--color-text); border-bottom: 1px solid var(--color-border); padding-bottom: 8px; margin-bottom: 16px;">
              1. Request Details
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
              <div class="form-group">
                <label class="form-label" for="quantity_requested">Quantity Needed (Max: <?= $available ?> <?= e($donation['unit']) ?>)</label>
                <input type="number" id="quantity_requested" name="quantity_requested" class="form-input" required min="1" max="<?= $available ?>" value="1">
              </div>

              <div class="form-group">
                <label class="form-label" for="urgency_level">Urgency Level</label>
                <select id="urgency_level" name="urgency_level" class="form-input" required>
                  <option value="medium" selected>Medium (Standard Need)</option>
                  <option value="high">High (Immediate Need / Emergency)</option>
                  <option value="low">Low (Preparation)</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="purpose">Reason for Request / Statement of Need</label>
              <textarea id="purpose" name="purpose" class="form-input" rows="4" required placeholder="Please explain who will benefit from this resource and why your household or organization needs this assistance..."></textarea>
            </div>
          </div>

          <!-- 2. Contact Information & Delivery Address -->
          <div style="margin-bottom: 24px;">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--color-text); border-bottom: 1px solid var(--color-border); padding-bottom: 8px; margin-bottom: 16px;">
              2. Contact Information &amp; Delivery Address
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
              <div class="form-group">
                <label class="form-label" for="contact_number">Your Phone / Mobile Number</label>
                <input type="tel" id="contact_number" name="contact_number" class="form-input" required placeholder="09195554321" value="<?= e($userProfile['phone'] ?? '') ?>">
              </div>

              <div class="form-group">
                <label class="form-label" for="delivery_address">Preferred Delivery Address / Area</label>
                <input type="text" id="delivery_address" name="delivery_address" class="form-input" required placeholder="Street address or Barangay" value="<?= e(($userProfile['barangay'] ? $userProfile['barangay'] . ', ' : '') . ($userProfile['city'] ?? '')) ?>">
              </div>
            </div>
          </div>

          <!-- 3. Supporting Proof Document -->
          <div style="margin-bottom: 28px;">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--color-text); border-bottom: 1px solid var(--color-border); padding-bottom: 8px; margin-bottom: 12px;">
              3. Supporting Proof Document (Optional)
            </h3>
            <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 12px;">
              Uploading a Certificate of Indigency, Barangay Certificate, Student ID, or Medical Slip helps administrators fast-track your verification.
            </p>
            <input type="file" name="proof_document" accept=".pdf,.jpg,.jpeg,.png" class="form-input">
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <a href="/CommunityDono/pages/donation-details.php?id=<?= $donationId ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-size: 15px;">
              Submit Resource Request &rarr;
            </button>
          </div>

        </form>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
