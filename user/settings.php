<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

$userId = (int)$_SESSION['user_id'];
$activeTab = trim($_GET['tab'] ?? 'password');

// Fetch user data
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
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

    <div class="user-content" style="max-width: 920px; margin: 0 auto;">
      <?php renderFlash(); ?>

      <!-- Settings Navigation Tabs -->
      <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--color-border); margin-bottom: 28px; overflow-x: auto; padding-bottom: 4px;">
        <a href="?tab=password" class="btn <?= $activeTab === 'password' ? 'btn-primary' : 'btn-soft' ?>" style="font-size: 13px; border-radius: var(--radius-md);">
          <i data-lucide="key-round" style="width:13px;height:13px;"></i> Change Password
        </a>
        <a href="?tab=privacy" class="btn <?= $activeTab === 'privacy' ? 'btn-primary' : 'btn-soft' ?>" style="font-size: 13px; border-radius: var(--radius-md);">
          <i data-lucide="shield" style="width:13px;height:13px;"></i> Privacy &amp; Location
        </a>
        <a href="?tab=sessions" class="btn <?= $activeTab === 'sessions' ? 'btn-primary' : 'btn-soft' ?>" style="font-size: 13px; border-radius: var(--radius-md);">
          <i data-lucide="monitor" style="width:13px;height:13px;"></i> Active Sessions
        </a>
        <a href="?tab=region" class="btn <?= $activeTab === 'region' ? 'btn-primary' : 'btn-soft' ?>" style="font-size: 13px; border-radius: var(--radius-md);">
          <i data-lucide="globe" style="width:13px;height:13px;"></i> Language &amp; Region
        </a>
        <a href="?tab=linked" class="btn <?= $activeTab === 'linked' ? 'btn-primary' : 'btn-soft' ?>" style="font-size: 13px; border-radius: var(--radius-md);">
          <i data-lucide="link" style="width:13px;height:13px;"></i> Linked Accounts
        </a>
      </div>

      <!-- Tab 1: Change Password (Scope 2.7.1) -->
      <?php if ($activeTab === 'password'): ?>
        <div class="card" style="padding: 32px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm);">
          <h2 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0 0 16px;">Change Password</h2>
          <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 24px;">Ensure your account is using a long, random password to stay secure.</p>

          <form action="<?= $base ?>/api/user/change-password.php" method="POST" data-validate>
            <?= csrfField() ?>

            <div class="form-group" style="margin-bottom: 16px;">
              <label class="form-label" for="current_password">Current Password</label>
              <input type="password" id="current_password" name="current_password" class="form-input" required placeholder="••••••••">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
              <div class="form-group">
                <label class="form-label" for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-input" required minlength="8" placeholder="At least 8 characters">
              </div>

              <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" required minlength="8" placeholder="Re-type new password">
              </div>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 14px;">
              Update Password
            </button>
          </form>
        </div>
      <?php endif; ?>

      <!-- Tab 2: Privacy & Location (Scope 2.7.2) -->
      <?php if ($activeTab === 'privacy'): ?>
        <div class="card" style="padding: 32px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm);">
          <h2 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0 0 16px;">Privacy &amp; Location Preferences</h2>
          
          <form action="<?= $base ?>/api/user/update-privacy.php" method="POST">
            <?= csrfField() ?>

            <div class="form-group" style="margin-bottom: 20px;">
              <label class="form-label" for="profile_visibility">Profile Visibility</label>
              <select id="profile_visibility" name="profile_visibility" class="form-input">
                <option value="public" <?= ($user['privacy_profile_visibility'] ?? '') === 'public' ? 'selected' : '' ?>>Public (Visible to all community members)</option>
                <option value="members" <?= ($user['privacy_profile_visibility'] ?? '') === 'members' ? 'selected' : '' ?>>Registered Members Only</option>
                <option value="hidden" <?= ($user['privacy_profile_visibility'] ?? '') === 'hidden' ? 'selected' : '' ?>>Hidden (Strict Privacy)</option>
              </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
              <label class="form-label" for="contact_visibility">Donation Contact Visibility</label>
              <select id="contact_visibility" name="contact_visibility" class="form-input">
                <option value="matched_only" <?= ($user['privacy_contact_visibility'] ?? '') === 'matched_only' ? 'selected' : '' ?>>Matched Users Only (Recommended)</option>
                <option value="members" <?= ($user['privacy_contact_visibility'] ?? '') === 'members' ? 'selected' : '' ?>>All Logged-in Members</option>
                <option value="hidden" <?= ($user['privacy_contact_visibility'] ?? '') === 'hidden' ? 'selected' : '' ?>>Private / In-app Chat Only</option>
              </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
              <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px;">
                <input type="checkbox" name="show_area" value="1" <?= !empty($user['privacy_show_area']) ? 'checked' : '' ?>>
                <span><strong>Show General Area on Donation Posts</strong> (e.g. Pasig City, San Antonio)</span>
              </label>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
              <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px;">
                <input type="checkbox" name="device_location" value="1" <?= !empty($user['privacy_device_location']) ? 'checked' : '' ?>>
                <span><strong>Use Device Location</strong> for discovering nearby donations</span>
              </label>
              <p style="font-size: 12px; color: var(--color-text-muted); margin: 6px 0 0 24px;">
                Browser location permission is required. Location is only used to compute distance and is never broadcasted publicly.
              </p>
            </div>

            <div style="display: flex; gap: 12px;">
              <button type="submit" class="btn btn-primary">Save Preferences</button>
              <a href="?tab=privacy" class="btn btn-outline">Cancel</a>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <!-- Tab 3: Active Sessions (Scope 2.7.3) -->
      <?php if ($activeTab === 'sessions'): ?>
        <div class="card" style="padding: 32px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
              <h2 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Active Devices &amp; Sessions</h2>
              <p style="color: var(--color-text-muted); font-size: 13.5px; margin: 4px 0 0;">Manage browsers and devices currently signed in to your account.</p>
            </div>
          </div>

          <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 24px;">
            <div style="padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: #F8FAFC; display: flex; justify-content: space-between; align-items: center;">
              <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span style="font-weight: 700; font-size: 15px;"><i data-lucide="monitor" style="width:15px;height:15px;vertical-align:middle;"></i> <?= e($_SERVER['HTTP_USER_AGENT'] ?? 'Web Browser') ?></span>
                  <span class="badge badge-approved" style="font-size: 11px;">Current Session</span>
                </div>
                <div style="font-size: 12.5px; color: var(--color-text-muted); margin-top: 4px;">
                  IP: <?= e($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?> · Active now
                </div>
              </div>
            </div>
          </div>

          <div style="background: var(--color-tint-teal); padding: 16px; border-radius: var(--radius-md); font-size: 13px; color: var(--color-text);">
            <strong>Account Security Tip:</strong> If you see an unfamiliar device, change your password immediately.
          </div>
        </div>
      <?php endif; ?>

      <!-- Tab 4: Language & Region (Scope 2.7.4) -->
      <?php if ($activeTab === 'region'): ?>
        <div class="card" style="padding: 32px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm);">
          <h2 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0 0 16px;">Language &amp; Regional Settings</h2>

          <form action="<?= $base ?>/api/user/update-region.php" method="POST">
            <?= csrfField() ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
              <div class="form-group">
                <label class="form-label" for="language">Display Language</label>
                <select id="language" name="language" class="form-input">
                  <option value="en" selected>English (Default)</option>
                  <option value="fil">Filipino / Tagalog</option>
                  <option value="ceb">Cebuano</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label" for="country">Region / Country</label>
                <select id="country" name="country" class="form-input">
                  <option value="Philippines" selected>Philippines</option>
                </select>
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
              <div class="form-group">
                <label class="form-label" for="timezone">Time Zone</label>
                <select id="timezone" name="timezone" class="form-input">
                  <option value="Asia/Manila" selected>Asia/Manila (GMT+8)</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label" for="date_format">Date Format</label>
                <select id="date_format" name="date_format" class="form-input">
                  <option value="M d, Y">October 04, 2026 (M d, Y)</option>
                  <option value="Y-m-d">2026-10-04 (YYYY-MM-DD)</option>
                  <option value="d/m/Y">04/10/2026 (DD/MM/YYYY)</option>
                </select>
              </div>
            </div>

            <!-- Localized Preview (Scope 2.7.4) -->
            <div style="background: #F8FAFC; padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--color-border); font-size: 13px; margin-bottom: 24px;">
              <strong>Localized Preview:</strong> Today's date will appear as: <code style="color: var(--color-primary);"><?= date('F j, Y, g:i A') ?></code>
            </div>

            <div style="display: flex; gap: 12px;">
              <button type="submit" class="btn btn-primary">Save Preferences</button>
              <a href="?tab=region" class="btn btn-outline">Cancel</a>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <!-- Tab 5: Linked Accounts (Scope 2.7.5) -->
      <?php if ($activeTab === 'linked'): ?>
        <div class="card" style="padding: 32px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm);">
          <h2 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0 0 16px;">Linked Social Accounts</h2>
          <p style="color: var(--color-text-muted); font-size: 13.5px; margin-bottom: 24px;">Connect third-party accounts for fast sign-in access.</p>

          <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--color-border); border-radius: var(--radius-md); background: #F8FAFC;">
              <div style="display: flex; align-items: center; gap: 12px;">
                <i data-lucide="globe" style="width:24px;height:24px;"></i>
                <div>
                  <div style="font-weight: 700; font-size: 15px;">Google Account</div>
                  <div style="font-size: 12.5px; color: var(--color-text-muted);">
                    Status: <?= !empty($user['google_linked']) ? '<strong style="color:#166534;">Connected</strong>' : 'Not Connected' ?>
                  </div>
                </div>
              </div>
              <form action="<?= $base ?>/api/user/link-account.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="provider" value="google">
                <button type="submit" class="btn <?= !empty($user['google_linked']) ? 'btn-danger' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 14px;">
                  <?= !empty($user['google_linked']) ? 'Disconnect' : 'Connect Google' ?>
                </button>
              </form>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--color-border); border-radius: var(--radius-md); background: #F8FAFC;">
              <div style="display: flex; align-items: center; gap: 12px;">
                <i data-lucide="users" style="width:24px;height:24px;"></i>
                <div>
                  <div style="font-weight: 700; font-size: 15px;">Facebook Account</div>
                  <div style="font-size: 12.5px; color: var(--color-text-muted);">
                    Status: <?= !empty($user['facebook_linked']) ? '<strong style="color:#166534;">Connected</strong>' : 'Not Connected' ?>
                  </div>
                </div>
              </div>
              <form action="<?= $base ?>/api/user/link-account.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="provider" value="facebook">
                <button type="submit" class="btn <?= !empty($user['facebook_linked']) ? 'btn-danger' : 'btn-outline' ?>" style="font-size: 12px; padding: 6px 14px;">
                  <?= !empty($user['facebook_linked']) ? 'Disconnect' : 'Connect Facebook' ?>
                </button>
              </form>
            </div>
          </div>

          <div style="background: var(--color-tint-coral); padding: 14px; border-radius: var(--radius-md); font-size: 12.5px; color: var(--color-text);">
            <strong>Account Safety Notice:</strong> Ensure you have set an account password before disconnecting external sign-in accounts.
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
