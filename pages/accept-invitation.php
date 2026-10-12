<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$token = trim($_GET['token'] ?? '');

// ── Validate token
$stmt = $pdo->prepare("
    SELECT * FROM admin_invitations
    WHERE token = ? AND status = 'pending' AND expires_at > NOW()
    LIMIT 1
");
$stmt->execute([$token]);
$invitation = $stmt->fetch();

$error   = '';
$success = false;

if (empty($token) || !$invitation) {
    $error = 'This invitation link is invalid or has expired. Please ask your super admin to send a new invitation.';
}

// ── Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $invitation && empty($error)) {
    verifyCsrf();

    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already registered
        $emailCheck = $pdo->prepare('SELECT id, role FROM users WHERE email = ?');
        $emailCheck->execute([$invitation['email']]);
        $existingUser = $emailCheck->fetch();

        if ($existingUser) {
            // Promote existing user to admin
            $pdo->prepare("UPDATE users SET role = ?, status = 'active' WHERE id = ?")
                ->execute([$invitation['role'], $existingUser['id']]);
            $newUserId = (int)$existingUser['id'];
        } else {
            // Create new account
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $pdo->prepare("
                INSERT INTO users (first_name, last_name, email, password_hash, role, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ");
            $ins->execute([
                $invitation['first_name'],
                $invitation['last_name'],
                $invitation['email'],
                $hash,
                $invitation['role'],
            ]);
            $newUserId = (int)$pdo->lastInsertId();
        }

        // Mark invitation as accepted
        $pdo->prepare("UPDATE admin_invitations SET status = 'accepted', accepted_at = NOW() WHERE id = ?")
            ->execute([$invitation['id']]);

        // Log in immediately
        session_regenerate_id(true);
        $_SESSION['user_id']    = $newUserId;
        $_SESSION['role']       = $invitation['role'];
        $_SESSION['status']     = 'active';
        $_SESSION['user_name']  = $invitation['first_name'] . ' ' . $invitation['last_name'];
        $_SESSION['user_email'] = $invitation['email'];

        $success = true;
    }
}

$pageTitle = 'Accept Admin Invitation — iCareDonate';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.invite-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #F0FDF4 0%, #E0F2FE 100%);
  padding: 40px 20px;
}
.invite-card {
  background: #fff;
  border-radius: 20px;
  padding: 48px;
  max-width: 480px;
  width: 100%;
  box-shadow: 0 20px 60px rgba(0,0,0,0.08);
}
.invite-card__logo {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 32px;
}
.invite-card__logo svg { width: 32px; height: 32px; color: var(--color-primary); }
.invite-card__title { font-size: 22px; font-weight: 700; color: var(--color-text); margin: 0 0 6px; }
.invite-card__sub   { font-size: 14px; color: var(--color-text-muted); margin: 0 0 28px; }
.invite-badge {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--color-primary-light); color: var(--color-primary);
  font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 99px;
  margin-bottom: 24px;
}
</style>

<div class="invite-page">
  <div class="invite-card">
    <!-- Logo -->
    <div class="invite-card__logo">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 20.4s-7.3-4.7-9.2-9C1.2 7.9 3 4.8 6.4 4.2c2.1-.4 4.1.6 5.6 2.4 1.5-1.8 3.5-2.8 5.6-2.4 3.4.6 5.2 3.7 3.6 7.2-1.9 4.3-9.2 9-9.2 9Z" fill="currentColor"/>
      </svg>
      <span style="font-size:18px;font-weight:800;color:var(--color-text);">iCareDonate</span>
    </div>

    <?php if ($success): ?>
      <!-- Success state -->
      <div style="text-align:center;padding:16px 0;">
        <div style="width:64px;height:64px;background:#D1FAE5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
          <i data-lucide="check-circle" style="width:32px;height:32px;color:#059669;"></i>
        </div>
        <h1 style="font-size:22px;font-weight:700;color:var(--color-text);margin:0 0 8px;">Welcome aboard!</h1>
        <p style="font-size:14px;color:var(--color-text-muted);margin:0 0 28px;">
          Your admin account for <strong><?= e($invitation['email']) ?></strong> is ready.
        </p>
        <a href="/CommunityDono/admin/index.php" class="btn btn-primary" style="width:100%;justify-content:center;font-size:15px;padding:12px;">
          Go to Admin Dashboard
          <i data-lucide="arrow-right" style="width:16px;height:16px;margin-left:6px;"></i>
        </a>
      </div>

    <?php elseif (!empty($error)): ?>
      <!-- Error state -->
      <div style="text-align:center;padding:16px 0;">
        <div style="width:64px;height:64px;background:#FEE2E2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
          <i data-lucide="alert-circle" style="width:32px;height:32px;color:#DC2626;"></i>
        </div>
        <h1 style="font-size:22px;font-weight:700;color:var(--color-text);margin:0 0 8px;">Invalid Invitation</h1>
        <p style="font-size:14px;color:var(--color-text-muted);margin:0 0 28px;"><?= e($error) ?></p>
        <a href="/CommunityDono/pages/login.php" class="btn btn-outline" style="width:100%;justify-content:center;">
          Back to Login
        </a>
      </div>

    <?php else: ?>
      <!-- Invitation form -->
      <div class="invite-badge">
        <i data-lucide="shield-check" style="width:13px;height:13px;"></i>
        <?= $invitation['role'] === 'superadmin' ? 'Super Admin Invitation' : 'Admin Invitation' ?>
      </div>

      <h1 class="invite-card__title">Set Up Your Admin Account</h1>
      <p class="invite-card__sub">
        You've been invited to join iCareDonate as an administrator. Create your password to get started.
      </p>

      <!-- Invitee info preview -->
      <div style="background:var(--color-bg);border:1px solid var(--color-border);border-radius:10px;padding:14px 16px;margin-bottom:24px;display:flex;gap:12px;align-items:center;">
        <div style="width:40px;height:40px;border-radius:50%;background:var(--color-primary-light);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:var(--color-primary);flex-shrink:0;">
          <?= strtoupper(substr($invitation['first_name'], 0, 1)) ?>
        </div>
        <div>
          <div style="font-weight:700;font-size:14px;color:var(--color-text);"><?= e($invitation['first_name'] . ' ' . $invitation['last_name']) ?></div>
          <div style="font-size:12px;color:var(--color-text-muted);"><?= e($invitation['email']) ?></div>
        </div>
      </div>

      <form action="" method="POST" novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <div class="form-group">
          <label class="form-label" for="ai_password">Create Password <span style="color:var(--color-danger)">*</span></label>
          <input type="password" id="ai_password" name="password" class="form-input" required minlength="8" placeholder="Minimum 8 characters" autocomplete="new-password">
        </div>

        <div class="form-group">
          <label class="form-label" for="ai_confirm">Confirm Password <span style="color:var(--color-danger)">*</span></label>
          <input type="password" id="ai_confirm" name="confirm_password" class="form-input" required placeholder="Re-enter your password" autocomplete="new-password">
        </div>

        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 14px;margin-bottom:20px;font-size:12px;color:#92400E;">
          <strong>Invitation expires:</strong> <?= date('M d, Y g:i A', strtotime($invitation['expires_at'])) ?>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;font-size:15px;padding:12px;">
          <i data-lucide="shield-check" style="width:16px;height:16px;margin-right:6px;"></i>
          Activate Admin Account
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
