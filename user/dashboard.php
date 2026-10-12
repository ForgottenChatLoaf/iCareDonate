<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

$userId = (int)$_SESSION['user_id'];

// 1. Fetch user metrics
$userStats = [
    'my_donations' => 0,
    'my_requests'  => 0,
    'in_progress'  => 0,
    'completed'    => 0,
];

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM donations WHERE donor_id = ?');
    $stmt->execute([$userId]);
    $userStats['my_donations'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM requests WHERE requester_id = ?');
    $stmt->execute([$userId]);
    $userStats['my_requests'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE (donor_id = ? OR requester_id = ?) AND status IN ("created", "pending_claim", "claimed")');
    $stmt->execute([$userId, $userId]);
    $userStats['in_progress'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE (donor_id = ? OR requester_id = ?) AND status = "completed"');
    $stmt->execute([$userId, $userId]);
    $userStats['completed'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    error_log($e->getMessage());
}

// 2. Fetch active transactions / handovers
$activeTransactions = [];
try {
    $stmt = $pdo->prepare('
        SELECT t.*, d.title AS donation_title,
               donor.first_name AS donor_name, req.first_name AS req_name
        FROM transactions t
        JOIN donations d ON t.donation_id = d.id
        JOIN users donor ON t.donor_id = donor.id
        JOIN users req ON t.requester_id = req.id
        WHERE (t.donor_id = ? OR t.requester_id = ?) AND t.status IN ("created", "pending_claim", "claimed")
        ORDER BY t.claim_deadline ASC
        LIMIT 5
    ');
    $stmt->execute([$userId, $userId]);
    $activeTransactions = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

// 3. Fetch recent donations
$myRecentDonations = [];
try {
    $stmt = $pdo->prepare('
        SELECT d.*, c.name AS category_name
        FROM donations d
        JOIN categories c ON d.category_id = c.id
        WHERE d.donor_id = ?
        ORDER BY d.created_at DESC
        LIMIT 4
    ');
    $stmt->execute([$userId]);
    $myRecentDonations = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

// 4. Fetch recent requests
$myRecentRequests = [];
try {
    $stmt = $pdo->prepare('
        SELECT r.*, d.title AS donation_title
        FROM requests r
        JOIN donations d ON r.donation_id = d.id
        WHERE r.requester_id = ?
        ORDER BY r.submitted_at DESC
        LIMIT 4
    ');
    $stmt->execute([$userId]);
    $myRecentRequests = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

// 4. Recent activity — aggregate donations + requests + completed transactions
$recentActivity = [];
try {
    $dStmt = $pdo->prepare("SELECT 'donation' AS type, title, status, created_at AS ts FROM donations WHERE donor_id = ? ORDER BY created_at DESC LIMIT 4");
    $dStmt->execute([$userId]);
    foreach ($dStmt->fetchAll() as $row) {
        $recentActivity[] = ['type' => 'donation', 'label' => 'You posted a new donation request for \'' . $row['title'] . '\'', 'ts' => $row['ts'], 'status' => $row['status']];
    }
    $rStmt = $pdo->prepare("SELECT r.status, d.title AS t, r.submitted_at AS ts FROM requests r JOIN donations d ON r.donation_id = d.id WHERE r.requester_id = ? ORDER BY r.submitted_at DESC LIMIT 4");
    $rStmt->execute([$userId]);
    foreach ($rStmt->fetchAll() as $row) {
        $recentActivity[] = ['type' => 'request', 'label' => 'You placed a request for \'' . $row['t'] . '\'', 'ts' => $row['ts'], 'status' => $row['status']];
    }
    $tStmt = $pdo->prepare("SELECT t.status, d.title AS t, t.updated_at AS ts FROM transactions t JOIN donations d ON t.donation_id = d.id WHERE (t.donor_id = ? OR t.requester_id = ?) AND t.status = 'completed' ORDER BY t.updated_at DESC LIMIT 3");
    $tStmt->execute([$userId, $userId]);
    foreach ($tStmt->fetchAll() as $row) {
        $recentActivity[] = ['type' => 'completed', 'label' => 'Completed delivery verification for \'' . $row['t'] . '\'', 'ts' => $row['ts'], 'status' => 'completed'];
    }
    usort($recentActivity, fn($a, $b) => strtotime($b['ts']) - strtotime($a['ts']));
    $recentActivity = array_slice($recentActivity, 0, 6);
} catch (Exception $e) { error_log($e->getMessage()); }

$pageTitle = 'Dashboard';
$extraCss  = 'user.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="user-layout">
  <?php require_once __DIR__ . '/../includes/nav-user.php'; ?>

  <div class="user-main">
    <header class="user-topbar">
      <div>
        <h1 style="font-size:20px;font-weight:700;color:var(--color-text);margin:0;">Dashboard</h1>
      </div>
    </header>

    <div class="user-content" style="padding:28px 32px;">
      <?php renderFlash(); ?>

      <!-- Welcome Banner -->
      <div style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%); color: #fff; border-radius: var(--radius-lg); padding: 24px 28px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; gap: 16px;">
        <div>
          <div style="font-weight: 700; font-size: 20px; margin-bottom: 4px; display:flex; align-items:center; gap:8px;"><i data-lucide="sparkles" style="width:20px;height:20px;opacity:0.85;flex-shrink:0;"></i> Welcome back, <?= e($_SESSION['user_name'] ?? 'Neighbor') ?>!</div>
          <div style="font-size: 14px; opacity: 0.85;">Here is your donation activity overview — every share makes a difference.</div>
        </div>
        <div style="background: rgba(255,255,255,0.18); border-radius: var(--radius-md); padding: 12px 16px; text-align: center; flex-shrink: 0; min-width: 80px;">
          <div style="font-size: 28px; font-weight: 800; line-height: 1;"><?= $userStats['completed'] ?></div>
          <div style="font-size: 11px; opacity: 0.85; margin-top: 2px;">Completed</div>
        </div>
      </div>

      <!-- 4 Stat Cards -->
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px;">

        <div style="background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:18px 20px;">
          <div style="width:38px;height:38px;border-radius:var(--radius-md);background:var(--color-tint-teal);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i data-lucide="gift" style="width:20px;height:20px;color:var(--color-primary);"></i>
          </div>
          <div style="font-size:26px;font-weight:800;color:var(--color-text);line-height:1;margin-bottom:4px;"><?= $userStats['my_donations'] ?></div>
          <div style="font-size:12px;color:var(--color-text-muted);">My Donations</div>
        </div>

        <div style="background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:18px 20px;">
          <div style="width:38px;height:38px;border-radius:var(--radius-md);background:#FFF0EC;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i data-lucide="clipboard-list" style="width:20px;height:20px;color:var(--color-accent);"></i>
          </div>
          <div style="font-size:26px;font-weight:800;color:var(--color-text);line-height:1;margin-bottom:4px;"><?= $userStats['my_requests'] ?></div>
          <div style="font-size:12px;color:var(--color-text-muted);">My Requests</div>
        </div>

        <div style="background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:18px 20px;">
          <div style="width:38px;height:38px;border-radius:var(--radius-md);background:#FEF3C7;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i data-lucide="loader" style="width:20px;height:20px;color:#D97706;"></i>
          </div>
          <div style="font-size:26px;font-weight:800;color:var(--color-text);line-height:1;margin-bottom:4px;"><?= $userStats['in_progress'] ?></div>
          <div style="font-size:12px;color:var(--color-text-muted);">In Progress</div>
        </div>

        <div style="background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:18px 20px;">
          <div style="width:38px;height:38px;border-radius:var(--radius-md);background:#DCFCE7;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i data-lucide="check-circle-2" style="width:20px;height:20px;color:#16A34A;"></i>
          </div>
          <div style="font-size:26px;font-weight:800;color:var(--color-text);line-height:1;margin-bottom:4px;"><?= $userStats['completed'] ?></div>
          <div style="font-size:12px;color:var(--color-text-muted);">Completed</div>
        </div>

      </div>

      <!-- Quick Actions -->
      <div style="margin-bottom:28px;">
        <div style="font-size:15px;font-weight:700;color:var(--color-text);margin-bottom:14px;">Quick Actions</div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">

          <a href="<?= $base ?>/user/offer-donation.php" style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px 12px;background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-lg);text-decoration:none;color:var(--color-text);font-size:13px;font-weight:600;transition:all 0.15s ease;" onmouseover="this.style.borderColor='var(--color-primary)';this.style.background='var(--color-tint-teal)'" onmouseout="this.style.borderColor='var(--color-border)';this.style.background='#fff'">
            <div style="width:44px;height:44px;border-radius:var(--radius-md);background:var(--color-primary);display:flex;align-items:center;justify-content:center;">
              <i data-lucide="heart-handshake" style="width:22px;height:22px;color:#fff;"></i>
            </div>
            Donate Now
          </a>

          <a href="<?= $base ?>/user/browse-donations.php" style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px 12px;background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-lg);text-decoration:none;color:var(--color-text);font-size:13px;font-weight:600;transition:all 0.15s ease;" onmouseover="this.style.borderColor='#D97706';this.style.background='#FEF3C7'" onmouseout="this.style.borderColor='var(--color-border)';this.style.background='#fff'">
            <div style="width:44px;height:44px;border-radius:var(--radius-md);background:#D97706;display:flex;align-items:center;justify-content:center;">
              <i data-lucide="search" style="width:22px;height:22px;color:#fff;"></i>
            </div>
            Browse Items
          </a>

          <a href="<?= $base ?>/user/requests.php" style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px 12px;background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-lg);text-decoration:none;color:var(--color-text);font-size:13px;font-weight:600;transition:all 0.15s ease;" onmouseover="this.style.borderColor='#7C3AED';this.style.background='#F5F3FF'" onmouseout="this.style.borderColor='var(--color-border)';this.style.background='#fff'">
            <div style="width:44px;height:44px;border-radius:var(--radius-md);background:#7C3AED;display:flex;align-items:center;justify-content:center;">
              <i data-lucide="clipboard-list" style="width:22px;height:22px;color:#fff;"></i>
            </div>
            My Requests
          </a>

          <a href="<?= $base ?>/user/notifications.php" style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px 12px;background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-lg);text-decoration:none;color:var(--color-text);font-size:13px;font-weight:600;transition:all 0.15s ease;" onmouseover="this.style.borderColor='var(--color-accent)';this.style.background='var(--color-tint-coral)'" onmouseout="this.style.borderColor='var(--color-border)';this.style.background='#fff'">
            <div style="width:44px;height:44px;border-radius:var(--radius-md);background:var(--color-accent);display:flex;align-items:center;justify-content:center;">
              <i data-lucide="bell" style="width:22px;height:22px;color:#fff;"></i>
            </div>
            Notifications
          </a>

        </div>
      </div>

      <!-- Recent Activity -->
      <div>
        <div style="font-size:15px;font-weight:700;color:var(--color-text);margin-bottom:14px;">Recent Activity</div>
        <div style="background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-lg);overflow:hidden;">
          <?php if (empty($recentActivity)): ?>
            <div style="padding:40px 24px;text-align:center;">
              <div style="width:52px;height:52px;border-radius:50%;background:var(--color-tint-teal);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <i data-lucide="activity" style="width:24px;height:24px;color:var(--color-primary);"></i>
              </div>
              <div style="font-size:14px;font-weight:600;color:var(--color-text);margin-bottom:6px;">No activity yet</div>
              <div style="color:var(--color-text-muted);font-size:13px;">Start by posting a donation or browsing available items.</div>
            </div>
          <?php else: ?>
            <?php foreach ($recentActivity as $i => $act):
              $icn = match($act['type']) { 'completed' => 'check-circle', 'request' => 'clipboard-list', default => 'gift' };
              $clrBg = match($act['type']) { 'completed' => '#DCFCE7', 'request' => '#FEF3C7', default => 'var(--color-tint-teal)' };
              $clrFg = match($act['type']) { 'completed' => '#16A34A', 'request' => '#D97706', default => 'var(--color-primary)' };
              $ago = timeAgo($act['ts']);
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;<?= $i < count($recentActivity) - 1 ? 'border-bottom:1px solid var(--color-border);' : '' ?>gap:16px;">
              <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:36px;height:36px;border-radius:50%;background:<?= $clrBg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <i data-lucide="<?= $icn ?>" style="width:16px;height:16px;color:<?= $clrFg ?>;"></i>
                </div>
                <span style="font-size:13.5px;color:var(--color-text);"><?= e($act['label']) ?></span>
              </div>
              <span style="font-size:12px;color:var(--color-text-muted);white-space:nowrap;flex-shrink:0;background:var(--color-admin-canvas);padding:3px 8px;border-radius:20px;"><?= e($ago) ?></span>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

