<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireSuperAdmin();

// ── Load admin accounts (role IN admin, superadmin)
$adminUsers = $pdo->query("
    SELECT id, first_name, last_name, email, role, status, last_login_at, created_at
    FROM users
    WHERE role IN ('admin','superadmin')
    ORDER BY role DESC, created_at ASC
")->fetchAll();

// ── Load pending invitations
$invitations = $pdo->query("
    SELECT i.*, u.first_name AS invited_by_first, u.last_name AS invited_by_last
    FROM admin_invitations i
    JOIN users u ON u.id = i.invited_by
    WHERE i.status = 'pending'
    ORDER BY i.created_at DESC
")->fetchAll();

$pageTitle = 'Admin Management — iCareDonate Admin';
$extraCss  = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size:18px;font-weight:700;color:var(--color-text);margin:0;">Admin Management</h1>
        <p style="font-size:12px;color:var(--color-text-muted);margin:4px 0 0;">Manage administrator accounts and invitations. Superadmin access only.</p>
      </div>
      <div>
        <button class="btn btn-primary" style="font-size:13px;display:flex;align-items:center;gap:6px;" onclick="openInviteModal()">
          <i data-lucide="user-plus" style="width:15px;height:15px;"></i>
          Invite Admin
        </button>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <!-- ── Active Admins ─────────────────────────────────── -->
      <div class="admin-section-card" style="margin-bottom:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
          <h2 style="font-size:15px;font-weight:700;color:var(--color-text);margin:0;">
            <i data-lucide="shield-check" style="width:16px;height:16px;vertical-align:-3px;margin-right:6px;color:var(--color-primary);"></i>
            Administrator Accounts
            <span style="font-size:12px;font-weight:500;background:var(--color-primary-light);color:var(--color-primary);padding:2px 8px;border-radius:99px;margin-left:8px;"><?= count($adminUsers) ?></span>
          </h2>
        </div>
        <div class="admin-table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($adminUsers)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--color-text-muted);padding:24px;">No admin accounts found.</td></tr>
              <?php else: ?>
                <?php foreach ($adminUsers as $a): ?>
                  <tr>
                    <td>
                      <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--color-primary-light);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--color-primary);flex-shrink:0;">
                          <?= strtoupper(substr($a['first_name'], 0, 1) . substr($a['last_name'], 0, 1)) ?>
                        </div>
                        <span style="font-weight:600;color:var(--color-text);"><?= e($a['first_name'] . ' ' . $a['last_name']) ?></span>
                        <?php if ((int)$a['id'] === (int)$_SESSION['user_id']): ?>
                          <span style="font-size:10px;padding:1px 6px;background:#D1FAE5;color:#065F46;border-radius:99px;font-weight:600;">YOU</span>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td style="color:var(--color-text-muted);font-size:13px;"><?= e($a['email']) ?></td>
                    <td>
                      <?php if ($a['role'] === 'superadmin'): ?>
                        <span class="badge" style="background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;">
                          <i data-lucide="shield" style="width:11px;height:11px;vertical-align:-1px;margin-right:3px;"></i>
                          Super Admin
                        </span>
                      <?php else: ?>
                        <span class="badge badge-info">Admin</span>
                      <?php endif; ?>
                    </td>
                    <td><?= renderBadge($a['status']) ?></td>
                    <td style="font-size:12px;color:var(--color-text-muted);">
                      <?= $a['last_login_at'] ? date('M d, Y g:i A', strtotime($a['last_login_at'])) : 'Never' ?>
                    </td>
                    <td style="font-size:12px;color:var(--color-text-muted);"><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                    <td>
                      <?php if ((int)$a['id'] !== (int)$_SESSION['user_id'] && $a['role'] !== 'superadmin'): ?>
                        <div style="display:flex;gap:6px;align-items:center;">
                          <?php if ($a['status'] === 'active'): ?>
                            <form action="<?= $base ?>/api/admin/admin-action.php" method="POST" style="display:inline;" onsubmit="return confirm('Suspend this admin account? They will lose access immediately.');">
                              <?= csrfField() ?>
                              <input type="hidden" name="admin_id" value="<?= (int)$a['id'] ?>">
                              <input type="hidden" name="action" value="suspend">
                              <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 10px;">Suspend</button>
                            </form>
                          <?php elseif ($a['status'] === 'suspended'): ?>
                            <form action="<?= $base ?>/api/admin/admin-action.php" method="POST" style="display:inline;">
                              <?= csrfField() ?>
                              <input type="hidden" name="admin_id" value="<?= (int)$a['id'] ?>">
                              <input type="hidden" name="action" value="reactivate">
                              <button type="submit" class="btn btn-success" style="font-size:11px;padding:4px 10px;">Reactivate</button>
                            </form>
                          <?php endif; ?>
                          <form action="<?= $base ?>/api/admin/admin-action.php" method="POST" style="display:inline;" onsubmit="return confirm('Demote this admin to a regular user? This will remove all admin access.');">
                            <?= csrfField() ?>
                            <input type="hidden" name="admin_id" value="<?= (int)$a['id'] ?>">
                            <input type="hidden" name="action" value="demote">
                            <button type="submit" class="btn btn-outline" style="font-size:11px;padding:4px 10px;color:#B45309;">Demote</button>
                          </form>
                        </div>
                      <?php elseif ((int)$a['id'] === (int)$_SESSION['user_id']): ?>
                        <span style="font-size:12px;color:var(--color-text-muted);font-style:italic;">Your account</span>
                      <?php else: ?>
                        <span style="font-size:12px;color:var(--color-text-muted);font-style:italic;">Protected</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Pending Invitations ────────────────────────────── -->
      <div class="admin-section-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
          <h2 style="font-size:15px;font-weight:700;color:var(--color-text);margin:0;">
            <i data-lucide="mail" style="width:16px;height:16px;vertical-align:-3px;margin-right:6px;color:var(--color-primary);"></i>
            Pending Invitations
            <span style="font-size:12px;font-weight:500;background:#FEF3C7;color:#92400E;padding:2px 8px;border-radius:99px;margin-left:8px;"><?= count($invitations) ?></span>
          </h2>
        </div>
        <div class="admin-table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Invitee</th>
                <th>Email</th>
                <th>Role</th>
                <th>Invited By</th>
                <th>Sent</th>
                <th>Expires</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($invitations)): ?>
                <tr>
                  <td colspan="7" style="text-align:center;padding:32px;">
                    <i data-lucide="mail-open" style="width:32px;height:32px;color:var(--color-text-muted);display:block;margin:0 auto 8px;"></i>
                    <span style="color:var(--color-text-muted);font-size:13px;">No pending invitations.</span>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($invitations as $inv): ?>
                  <?php $isExpired = strtotime($inv['expires_at']) < time(); ?>
                  <tr>
                    <td style="font-weight:600;color:var(--color-text);"><?= e($inv['first_name'] . ' ' . $inv['last_name']) ?></td>
                    <td style="color:var(--color-text-muted);font-size:13px;"><?= e($inv['email']) ?></td>
                    <td>
                      <?php if ($inv['role'] === 'superadmin'): ?>
                        <span class="badge" style="background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;">Super Admin</span>
                      <?php else: ?>
                        <span class="badge badge-info">Admin</span>
                      <?php endif; ?>
                    </td>
                    <td style="font-size:13px;color:var(--color-text-muted);"><?= e($inv['invited_by_first'] . ' ' . $inv['invited_by_last']) ?></td>
                    <td style="font-size:12px;color:var(--color-text-muted);"><?= date('M d, Y', strtotime($inv['created_at'])) ?></td>
                    <td>
                      <span style="font-size:12px;color:<?= $isExpired ? '#DC2626' : 'var(--color-text-muted)' ?>;">
                        <?= $isExpired ? '⚠ Expired' : date('M d, Y', strtotime($inv['expires_at'])) ?>
                      </span>
                    </td>
                    <td>
                      <div style="display:flex;gap:6px;align-items:center;">
                        <button class="btn btn-outline" style="font-size:11px;padding:4px 10px;" onclick="copyInviteLink('<?= e($inv['token']) ?>')">
                          <i data-lucide="copy" style="width:11px;height:11px;vertical-align:-1px;margin-right:3px;"></i>
                          Copy Link
                        </button>
                        <form action="<?= $base ?>/api/admin/admin-action.php" method="POST" style="display:inline;" onsubmit="return confirm('Revoke this invitation?');">
                          <?= csrfField() ?>
                          <input type="hidden" name="invite_id" value="<?= (int)$inv['id'] ?>">
                          <input type="hidden" name="action" value="revoke_invite">
                          <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 10px;">Revoke</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- /admin-content -->
  </div><!-- /admin-main -->
</div><!-- /admin-layout -->

<!-- ── Invite Modal ──────────────────────────────────────────── -->
<div id="inviteModal" class="modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="inviteModalTitle">
  <div class="modal-panel" style="max-width:460px;">
    <div class="modal-header">
      <h2 id="inviteModalTitle" class="modal-title">
        <i data-lucide="user-plus" style="width:18px;height:18px;vertical-align:-3px;color:var(--color-primary);margin-right:6px;"></i>
        Invite New Administrator
      </h2>
      <button class="modal-close" onclick="closeInviteModal()" aria-label="Close">&times;</button>
    </div>
    <form action="<?= $base ?>/api/admin/admin-action.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="invite">
      <div class="modal-body" style="display:flex;flex-direction:column;gap:16px;">
        <p style="font-size:13px;color:var(--color-text-muted);margin:0;">
          An invitation link will be generated. Share it with the person you want to give admin access.
          The link expires in <strong>48 hours</strong>.
        </p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="form-group" style="margin:0;">
            <label class="form-label" for="inv_first_name">First Name <span style="color:var(--color-danger)">*</span></label>
            <input type="text" id="inv_first_name" name="first_name" class="form-input" required placeholder="Juan">
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label" for="inv_last_name">Last Name <span style="color:var(--color-danger)">*</span></label>
            <input type="text" id="inv_last_name" name="last_name" class="form-input" required placeholder="Dela Cruz">
          </div>
        </div>
        <div class="form-group" style="margin:0;">
          <label class="form-label" for="inv_email">Email Address <span style="color:var(--color-danger)">*</span></label>
          <input type="email" id="inv_email" name="email" class="form-input" required placeholder="admin@example.com">
        </div>
        <div class="form-group" style="margin:0;">
          <label class="form-label" for="inv_role">Admin Role <span style="color:var(--color-danger)">*</span></label>
          <select id="inv_role" name="role" class="form-input" required>
            <option value="admin">Admin — can manage donations, requests, users</option>
            <option value="superadmin">Super Admin — full system access</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeInviteModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" style="display:flex;align-items:center;gap:6px;">
          <i data-lucide="send" style="width:14px;height:14px;"></i>
          Generate Invitation
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openInviteModal() {
  document.getElementById('inviteModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeInviteModal() {
  document.getElementById('inviteModal').style.display = 'none';
  document.body.style.overflow = '';
}
document.getElementById('inviteModal').addEventListener('click', function(e) {
  if (e.target === this) closeInviteModal();
});

function copyInviteLink(token) {
  const url = `${location.origin}<?= $base ?>/pages/accept-invitation.php?token=${token}`;
  navigator.clipboard.writeText(url).then(() => {
    // Temporary toast
    const toast = document.createElement('div');
    toast.textContent = '✓ Link copied to clipboard';
    Object.assign(toast.style, {
      position:'fixed', bottom:'24px', left:'50%', transform:'translateX(-50%)',
      background:'#065F46', color:'#fff', padding:'10px 20px', borderRadius:'8px',
      fontSize:'13px', fontWeight:'600', zIndex:'9999', boxShadow:'0 4px 12px rgba(0,0,0,0.15)'
    });
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
  }).catch(() => {
    prompt('Copy this invitation link:', `${location.origin}<?= $base ?>/pages/accept-invitation.php?token=${token}`);
  });
}
</script>

<style>
.admin-section-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 20px;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
