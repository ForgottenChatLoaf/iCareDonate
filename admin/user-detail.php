<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    setFlash('error', 'User not specified.');
    header('Location: /CommunityDono/admin/users.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'User not found.');
    header('Location: /CommunityDono/admin/users.php');
    exit;
}

// User stats
$donCountStmt = $pdo->prepare('SELECT COUNT(*) FROM donations WHERE donor_id = ?');
$donCountStmt->execute([$userId]);
$totalDonations = (int)$donCountStmt->fetchColumn();

$reqCountStmt = $pdo->prepare('SELECT COUNT(*) FROM requests WHERE requester_id = ?');
$reqCountStmt->execute([$userId]);
$totalRequests = (int)$reqCountStmt->fetchColumn();

$txCountStmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE donor_id = ? OR requester_id = ?');
$txCountStmt->execute([$userId, $userId]);
$totalTransactions = (int)$txCountStmt->fetchColumn();

$repCountStmt = $pdo->prepare('SELECT COUNT(*) FROM problem_reports WHERE target_type = "user" AND target_id = ?');
$repCountStmt->execute([$userId]);
$totalReports = (int)$repCountStmt->fetchColumn();

// Recent donations
$donStmt = $pdo->prepare('
  SELECT d.*, c.name AS category_name 
  FROM donations d 
  LEFT JOIN categories c ON d.category_id = c.id 
  WHERE d.donor_id = ? 
  ORDER BY d.created_at DESC 
  LIMIT 5
');
$donStmt->execute([$userId]);
$recentDonations = $donStmt->fetchAll();

// Recent requests
$reqStmt = $pdo->prepare('
  SELECT r.*, d.title AS donation_title, c.name AS category_name 
  FROM requests r 
  JOIN donations d ON r.donation_id = d.id 
  LEFT JOIN categories c ON d.category_id = c.id 
  WHERE r.requester_id = ? 
  ORDER BY r.created_at DESC 
  LIMIT 5
');
$reqStmt->execute([$userId]);
$recentRequests = $reqStmt->fetchAll();

// Recent audit logs for this user
$auditStmt = $pdo->prepare('
  SELECT a.*, u.first_name AS admin_fn, u.last_name AS admin_ln 
  FROM audit_logs a 
  LEFT JOIN users u ON a.actor_id = u.id 
  WHERE a.target_type = "user" AND a.target_id = ? 
  ORDER BY a.created_at DESC 
  LIMIT 10
');
$auditStmt->execute([$userId]);
$auditLogs = $auditStmt->fetchAll();

$pageTitle = 'User Details: ' . $user['first_name'] . ' ' . $user['last_name'] . ' — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div style="display: flex; align-items: center; gap: 12px;">
        <a href="/CommunityDono/admin/users.php" class="btn btn-outline" style="font-size: 12px; padding: 4px 10px;">&larr; Users List</a>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">
          User Profile: <?= e($user['first_name'] . ' ' . $user['last_name']) ?> (#<?= (int)$user['id'] ?>)
        </h1>
      </div>
      <div>
        <?= renderBadge($user['status']) ?>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <div class="grid grid--2col" style="gap: 20px; margin-bottom: 24px;">
        <!-- Left: Account Info -->
        <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
          <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px; color: var(--color-primary);">Account Information</h2>
          
          <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid var(--color-border-light);">
              <td style="padding: 8px 0; color: var(--color-text-muted); width: 140px;">Full Name</td>
              <td style="padding: 8px 0; font-weight: 600;"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--color-border-light);">
              <td style="padding: 8px 0; color: var(--color-text-muted);">Email Address</td>
              <td style="padding: 8px 0;"><?= e($user['email']) ?></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--color-border-light);">
              <td style="padding: 8px 0; color: var(--color-text-muted);">Contact Phone</td>
              <td style="padding: 8px 0;"><?= e($user['phone'] ?? 'Not provided') ?></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--color-border-light);">
              <td style="padding: 8px 0; color: var(--color-text-muted);">Address</td>
              <td style="padding: 8px 0;"><?= e(($user['barangay'] ?? '') . ($user['city'] ? ', ' . $user['city'] : '') . ($user['province'] ? ', ' . $user['province'] : '')) ?: 'Metro Manila' ?></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--color-border-light);">
              <td style="padding: 8px 0; color: var(--color-text-muted);">Role</td>
              <td style="padding: 8px 0;"><span class="badge badge-info"><?= ucfirst($user['role']) ?></span></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--color-border-light);">
              <td style="padding: 8px 0; color: var(--color-text-muted);">Registered</td>
              <td style="padding: 8px 0;"><?= date('F j, Y g:i A', strtotime($user['created_at'])) ?></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--color-border-light);">
              <td style="padding: 8px 0; color: var(--color-text-muted);">Last Login</td>
              <td style="padding: 8px 0;"><?= !empty($user['last_login_at']) ? date('M j, Y g:i A', strtotime($user['last_login_at'])) : 'Never' ?></td>
            </tr>
            <tr>
              <td style="padding: 8px 0; color: var(--color-text-muted);">Email Verified</td>
              <td style="padding: 8px 0;">
                <?php if (!empty($user['email_verified_at'])): ?>
                  <span class="badge badge-success">Verified (<?= date('M d, Y', strtotime($user['email_verified_at'])) ?>)</span>
                <?php else: ?>
                  <span class="badge badge-warning">Unverified</span>
                <?php endif; ?>
              </td>
            </tr>
          </table>

          <?php if (!empty($user['suspension_reason'])): ?>
            <div style="margin-top: 16px; padding: 12px; background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: var(--radius-sm); font-size: 12.5px; color: #991B1B;">
              <strong>Moderation Note:</strong> <?= e($user['suspension_reason']) ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Right: Moderation Actions & Stats -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
          <!-- Quick stats -->
          <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
            <div class="card" style="padding: 14px; text-align: center; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
              <div style="font-family: var(--font-brand); font-size: 24px; color: var(--color-primary);"><?= $totalDonations ?></div>
              <div style="font-size: 11.5px; color: var(--color-text-muted);">Donations</div>
            </div>
            <div class="card" style="padding: 14px; text-align: center; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
              <div style="font-family: var(--font-brand); font-size: 24px; color: var(--color-primary);"><?= $totalRequests ?></div>
              <div style="font-size: 11.5px; color: var(--color-text-muted);">Requests</div>
            </div>
            <div class="card" style="padding: 14px; text-align: center; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
              <div style="font-family: var(--font-brand); font-size: 24px; color: var(--color-primary);"><?= $totalTransactions ?></div>
              <div style="font-size: 11.5px; color: var(--color-text-muted);">Transactions</div>
            </div>
            <div class="card" style="padding: 14px; text-align: center; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
              <div style="font-family: var(--font-brand); font-size: 24px; color: #DC2626;"><?= $totalReports ?></div>
              <div style="font-size: 11.5px; color: var(--color-text-muted);">Reports</div>
            </div>
          </div>

          <!-- Moderation Control Box -->
          <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md); background: #F8FAFC;">
            <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; color: var(--color-text);">Moderation Controls</h2>
            
            <?php if ($user['role'] === 'superadmin' || (int)$user['id'] === (int)$_SESSION['user_id']): ?>
              <p style="font-size: 13px; color: var(--color-text-muted);">This account is protected from administrative moderation actions.</p>
            <?php else: ?>
              <p style="font-size: 12.5px; color: var(--color-text-muted); margin-bottom: 16px;">
                Apply warnings, temporary suspensions, or permanent bans. Each action is recorded in the audit log.
              </p>

              <!-- Warning Form -->
              <form action="/CommunityDono/api/admin/user-action.php" method="POST" style="margin-bottom: 16px;">
                <?= csrfField() ?>
                <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                <input type="hidden" name="action" value="warn">
                <input type="hidden" name="redirect" value="/CommunityDono/admin/user-detail.php?id=<?= (int)$user['id'] ?>">
                <div style="display: flex; gap: 8px;">
                  <input type="text" name="reason" placeholder="Warning reason / message to user..." class="form-input" style="font-size: 12px; height: 34px;" required>
                  <button type="submit" class="btn btn-warning" style="font-size: 12px; padding: 6px 14px; white-space: nowrap;">
                    Issue Warning
                  </button>
                </div>
              </form>

              <!-- Status Action Buttons -->
              <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <?php if ($user['status'] === 'active'): ?>
                  <!-- Suspend Trigger -->
                  <button type="button" onclick="document.getElementById('suspend-box').style.display = 'block'" class="btn btn-danger" style="font-size: 12px; padding: 6px 14px;">
                    Suspend Account
                  </button>
                  <!-- Ban Trigger -->
                  <button type="button" onclick="document.getElementById('ban-box').style.display = 'block'" class="btn" style="background: #111827; color: #fff; font-size: 12px; padding: 6px 14px;">
                    Permanent Ban
                  </button>
                <?php else: ?>
                  <form action="/CommunityDono/api/admin/user-action.php" method="POST" style="display: inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                    <input type="hidden" name="action" value="activate">
                    <input type="hidden" name="redirect" value="/CommunityDono/admin/user-detail.php?id=<?= (int)$user['id'] ?>">
                    <button type="submit" class="btn btn-success" style="font-size: 12px; padding: 6px 16px;">
                      Reactivate / Unban Account
                    </button>
                  </form>
                <?php endif; ?>
                
                <a href="/CommunityDono/admin/interviews.php?user_id=<?= (int)$user['id'] ?>" class="btn btn-outline" style="font-size: 12px; padding: 6px 14px;">
                  Schedule Interview
                </a>
              </div>

              <!-- Collapsible Suspend Box -->
              <div id="suspend-box" style="display: none; margin-top: 14px; padding: 14px; background: #fff; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
                <form action="/CommunityDono/api/admin/user-action.php" method="POST">
                  <?= csrfField() ?>
                  <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                  <input type="hidden" name="action" value="suspend">
                  <input type="hidden" name="redirect" value="/CommunityDono/admin/user-detail.php?id=<?= (int)$user['id'] ?>">
                  <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Reason for Suspension (required):</label>
                  <textarea name="reason" rows="2" class="form-input" style="font-size: 12px; margin-bottom: 8px;" placeholder="e.g. Unverified donation listings or fake requests..." required></textarea>
                  <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="document.getElementById('suspend-box').style.display = 'none'" class="btn btn-outline" style="font-size: 11.5px; padding: 4px 10px;">Cancel</button>
                    <button type="submit" class="btn btn-danger" style="font-size: 11.5px; padding: 4px 12px;">Confirm Suspension</button>
                  </div>
                </form>
              </div>

              <!-- Collapsible Ban Box -->
              <div id="ban-box" style="display: none; margin-top: 14px; padding: 14px; background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: var(--radius-sm);">
                <form action="/CommunityDono/api/admin/user-action.php" method="POST">
                  <?= csrfField() ?>
                  <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                  <input type="hidden" name="action" value="ban">
                  <input type="hidden" name="redirect" value="/CommunityDono/admin/user-detail.php?id=<?= (int)$user['id'] ?>">
                  <label style="display: block; font-size: 12px; font-weight: 600; color: #991B1B; margin-bottom: 4px;">Reason for Permanent Ban (required):</label>
                  <textarea name="reason" rows="2" class="form-input" style="font-size: 12px; margin-bottom: 8px;" placeholder="e.g. Repeated Terms of Service violations, harassment..." required></textarea>
                  <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="document.getElementById('ban-box').style.display = 'none'" class="btn btn-outline" style="font-size: 11.5px; padding: 4px 10px;">Cancel</button>
                    <button type="submit" class="btn" style="background: #991B1B; color: #fff; font-size: 11.5px; padding: 4px 12px;">Confirm Ban</button>
                  </div>
                </form>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- User's Recent Donations -->
      <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md); margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
          <h2 style="font-size: 16px; font-weight: 700; color: var(--color-text); margin: 0;">User's Donations (<?= $totalDonations ?>)</h2>
        </div>
        <?php if (empty($recentDonations)): ?>
          <p style="font-size: 13px; color: var(--color-text-muted);">No donations posted yet.</p>
        <?php else: ?>
          <div class="admin-table-container">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Item Title</th>
                  <th>Category</th>
                  <th>Qty Total</th>
                  <th>Status</th>
                  <th>Submitted</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentDonations as $d): ?>
                  <tr>
                    <td>D-<?= (int)$d['id'] ?></td>
                    <td><strong><?= e($d['title']) ?></strong></td>
                    <td><?= e($d['category_name'] ?? 'General') ?></td>
                    <td><?= (int)$d['quantity_total'] ?> <?= e($d['unit'] ?? 'items') ?></td>
                    <td><?= renderBadge($d['status']) ?></td>
                    <td><?= date('M d, Y', strtotime($d['created_at'])) ?></td>
                    <td>
                      <a href="/CommunityDono/admin/donation-review.php?id=<?= (int)$d['id'] ?>" class="btn btn-outline" style="font-size: 11px; padding: 3px 8px;">
                        Review
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- User's Recent Requests -->
      <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md); margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
          <h2 style="font-size: 16px; font-weight: 700; color: var(--color-text); margin: 0;">User's Requests (<?= $totalRequests ?>)</h2>
        </div>
        <?php if (empty($recentRequests)): ?>
          <p style="font-size: 13px; color: var(--color-text-muted);">No requests submitted yet.</p>
        <?php else: ?>
          <div class="admin-table-container">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Donation Resource</th>
                  <th>Qty Needed</th>
                  <th>Urgency</th>
                  <th>Status</th>
                  <th>Submitted</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentRequests as $r): ?>
                  <tr>
                    <td>R-<?= (int)$r['id'] ?></td>
                    <td><strong><?= e($r['donation_title']) ?></strong></td>
                    <td><?= (int)$r['quantity_requested'] ?></td>
                    <td><span class="badge badge-<?= $r['urgency_level'] === 'high' ? 'danger' : ($r['urgency_level'] === 'medium' ? 'warning' : 'info') ?>"><?= ucfirst($r['urgency_level']) ?></span></td>
                    <td><?= renderBadge($r['status']) ?></td>
                    <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                    <td>
                      <a href="/CommunityDono/admin/request-review.php?id=<?= (int)$r['id'] ?>" class="btn btn-outline" style="font-size: 11px; padding: 3px 8px;">
                        Review
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Moderation & Audit History -->
      <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
        <h2 style="font-size: 16px; font-weight: 700; color: var(--color-text); margin-bottom: 14px;">Audit & Moderation History</h2>
        <?php if (empty($auditLogs)): ?>
          <p style="font-size: 13px; color: var(--color-text-muted);">No moderation actions recorded for this user.</p>
        <?php else: ?>
          <div class="admin-table-container">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Date & Time</th>
                  <th>Action</th>
                  <th>Admin Responsible</th>
                  <th>Details / Reason</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($auditLogs as $a): ?>
                  <tr>
                    <td style="font-size: 12px;"><?= date('M d, Y g:i A', strtotime($a['created_at'])) ?></td>
                    <td><span class="badge badge-info"><?= e($a['action']) ?></span></td>
                    <td><?= e(($a['admin_fn'] ?? 'System') . ' ' . ($a['admin_ln'] ?? '')) ?></td>
                    <td style="font-size: 12px;"><?= e($a['reason'] ?? 'None recorded') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
