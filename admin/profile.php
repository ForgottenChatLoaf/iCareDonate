<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$adminId = (int)$_SESSION['user_id'];

// Handle Profile and Password Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = trim($_POST['action'] ?? '');

    try {
        if ($action === 'update_profile') {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $barangay = trim($_POST['barangay'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $province = trim($_POST['province'] ?? '');

            if (empty($firstName) || empty($lastName)) {
                setFlash('error', 'First name and Last name are required.');
            } else {
                $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ?, barangay = ?, city = ?, province = ? WHERE id = ?');
                $stmt->execute([$firstName, $lastName, $phone, $barangay, $city, $province, $adminId]);
                $_SESSION['user_name'] = $firstName . ' ' . $lastName;
                logAudit($pdo, $adminId, 'admin', 'ADMIN_PROFILE_UPDATED', 'user', $adminId, null, null, 'Administrator profile updated');
                setFlash('success', 'Profile updated successfully.');
            }
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (strlen($newPassword) < 8) {
                setFlash('error', 'New password must be at least 8 characters long.');
            } elseif ($newPassword !== $confirmPassword) {
                setFlash('error', 'New password and confirmation do not match.');
            } else {
                $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
                $stmt->execute([$adminId]);
                $hash = $stmt->fetchColumn();

                if (!$hash || !password_verify($currentPassword, $hash)) {
                    setFlash('error', 'Current password is incorrect.');
                } else {
                    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                    $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $upd->execute([$newHash, $adminId]);
                    logAudit($pdo, $adminId, 'admin', 'ADMIN_PASSWORD_CHANGED', 'user', $adminId, null, null, 'Administrator password changed');
                    setFlash('success', 'Password changed successfully.');
                }
            }
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        setFlash('error', 'Failed to update settings: ' . $e->getMessage());
    }

    header('Location: /CommunityDono/admin/profile.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

// Fetch Admin's own accountability log
$logStmt = $pdo->prepare('
  SELECT * FROM audit_logs 
  WHERE actor_id = ? 
  ORDER BY created_at DESC 
  LIMIT 15
');
$logStmt->execute([$adminId]);
$myLogs = $logStmt->fetchAll();

$pageTitle = 'Admin Profile & Settings — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Admin Profile & Settings</h1>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <div class="grid grid--2col" style="gap: 20px; margin-bottom: 24px;">
        <!-- Left: Edit Profile -->
        <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
          <h2 style="font-size: 16px; font-weight: 700; color: var(--color-primary); margin-bottom: 16px;">Personal Information</h2>
          <form action="/CommunityDono/admin/profile.php" method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_profile">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
              <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">First Name</label>
                <input type="text" name="first_name" value="<?= e($admin['first_name']) ?>" class="form-input" required>
              </div>
              <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Last Name</label>
                <input type="text" name="last_name" value="<?= e($admin['last_name']) ?>" class="form-input" required>
              </div>
            </div>

            <div style="margin-bottom: 12px;">
              <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Email (Read-only)</label>
              <input type="email" value="<?= e($admin['email']) ?>" class="form-input" disabled style="background: #F1F5F9;">
            </div>

            <div style="margin-bottom: 12px;">
              <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Phone Number</label>
              <input type="text" name="phone" value="<?= e($admin['phone'] ?? '') ?>" class="form-input" placeholder="0917xxxxxxx">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
              <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Barangay</label>
                <input type="text" name="barangay" value="<?= e($admin['barangay'] ?? '') ?>" class="form-input">
              </div>
              <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">City</label>
                <input type="text" name="city" value="<?= e($admin['city'] ?? '') ?>" class="form-input">
              </div>
            </div>

            <button type="submit" class="btn btn-primary" style="font-size: 12px; padding: 6px 16px;">
              Save Profile
            </button>
          </form>
        </div>

        <!-- Right: Security & Password -->
        <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
          <h2 style="font-size: 16px; font-weight: 700; color: var(--color-primary); margin-bottom: 16px;">Security & Password</h2>
          <form action="/CommunityDono/admin/profile.php" method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="change_password">

            <div style="margin-bottom: 12px;">
              <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Current Password</label>
              <input type="password" name="current_password" class="form-input" required>
            </div>

            <div style="margin-bottom: 12px;">
              <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">New Password (min 8 chars)</label>
              <input type="password" name="new_password" class="form-input" required>
            </div>

            <div style="margin-bottom: 16px;">
              <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-input" required>
            </div>

            <button type="submit" class="btn btn-outline" style="font-size: 12px; padding: 6px 16px;">
              Update Password
            </button>
          </form>
        </div>
      </div>

      <!-- Administrator's Personal Accountability Records -->
      <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
          <h2 style="font-size: 16px; font-weight: 700; color: var(--color-text); margin: 0;">My Accountability Records</h2>
          <span style="font-size: 12px; color: var(--color-text-muted);">Recorded for audit and security</span>
        </div>

        <div class="admin-table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Date & Time</th>
                <th>Action Performed</th>
                <th>Target Type</th>
                <th>Target ID</th>
                <th>Details / Reason</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($myLogs)): ?>
                <tr>
                  <td colspan="6" style="text-align: center; color: var(--color-text-muted); padding: 20px;">No recent administrative actions recorded.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($myLogs as $log): ?>
                  <tr>
                    <td style="font-size: 12px;"><?= date('M d, Y g:i A', strtotime($log['created_at'])) ?></td>
                    <td><span class="badge badge-info"><?= e($log['action']) ?></span></td>
                    <td><?= e($log['target_type']) ?></td>
                    <td>#<?= (int)$log['target_id'] ?></td>
                    <td style="font-size: 12px;"><?= e($log['reason'] ?? '—') ?></td>
                    <td style="font-size: 11.5px; color: var(--color-text-muted);"><?= e($log['ip_address'] ?? '127.0.0.1') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
