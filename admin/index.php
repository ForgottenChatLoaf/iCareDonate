<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

// 1. Fetch system statistics for wireframe 4 stat cards
$stats = [
    'pending_donations' => 0,
    'pending_requests'  => 0,
    'active_matches'    => 0,
    'open_concerns'     => 0,
];

try {
    $stats['pending_donations'] = (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'pending_verification'")->fetchColumn();
    $stats['pending_requests']  = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status IN ('submitted', 'proof_review')")->fetchColumn();
    $stats['active_matches']    = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status IN ('created', 'pending_claim', 'claimed')")->fetchColumn();
    $stats['open_concerns']     = (int)$pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open', 'in_progress')")->fetchColumn();
} catch (Exception $e) {
    error_log($e->getMessage());
}

// 2. Fetch Recent Activities feed
$recentActivities = [];
try {
    $dStmt = $pdo->query("
        SELECT CONCAT(u.first_name, ' ', u.last_name, ' submitted donation D-', d.id) AS title,
               d.created_at
        FROM donations d
        JOIN users u ON d.donor_id = u.id
        ORDER BY d.created_at DESC
        LIMIT 4
    ");
    foreach ($dStmt->fetchAll() as $row) {
        $recentActivities[] = ['title' => $row['title'], 'created_at' => $row['created_at']];
    }

    $rStmt = $pdo->query("
        SELECT CONCAT(u.first_name, ' ', u.last_name, ' submitted request R-', r.id) AS title,
               r.created_at
        FROM requests r
        JOIN users u ON r.requester_id = u.id
        ORDER BY r.created_at DESC
        LIMIT 4
    ");
    foreach ($rStmt->fetchAll() as $row) {
        $recentActivities[] = ['title' => $row['title'], 'created_at' => $row['created_at']];
    }

    $tStmt = $pdo->query("
        SELECT CONCAT('Transaction T-', t.id, ' is ', REPLACE(t.status, '_', ' ')) AS title,
               t.created_at
        FROM transactions t
        ORDER BY t.created_at DESC
        LIMIT 4
    ");
    foreach ($tStmt->fetchAll() as $row) {
        $recentActivities[] = ['title' => $row['title'], 'created_at' => $row['created_at']];
    }

    usort($recentActivities, function($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });
    $recentActivities = array_slice($recentActivities, 0, 6);
} catch (Exception $e) {
    error_log($e->getMessage());
}

// 3. Fetch latest pending donation for wireframe "Donation Details â€” D-1025" panel
$activeDonation = null;
try {
    $detailStmt = $pdo->query("
        SELECT d.*, c.name AS category_name,
               u.first_name, u.last_name, u.email AS donor_email, u.phone AS donor_phone,
               u.barangay AS donor_barangay, u.city AS donor_city,
               (SELECT file_path FROM donation_images WHERE donation_id = d.id ORDER BY is_primary DESC LIMIT 1) AS primary_image
        FROM donations d
        JOIN categories c ON d.category_id = c.id
        JOIN users u ON d.donor_id = u.id
        WHERE d.status = 'pending_verification'
        ORDER BY d.created_at ASC
        LIMIT 1
    ");
    $activeDonation = $detailStmt->fetch();
} catch (Exception $e) {
    error_log($e->getMessage());
}

// 4. Fetch pending requests queue for dashboard Action Center
$dashboardRequests = [];
try {
    $rDashStmt = $pdo->query("
        SELECT r.*, d.title AS donation_title, d.quantity_total, d.quantity_reserved, d.quantity_claimed, d.unit,
               u.first_name, u.last_name, u.email AS requester_email, u.phone AS requester_phone,
               (SELECT file_path FROM request_proofs WHERE request_id = r.id LIMIT 1) AS proof_path
        FROM requests r
        JOIN donations d ON r.donation_id = d.id
        JOIN users u ON r.requester_id = u.id
        WHERE r.status IN ('submitted', 'proof_review')
        ORDER BY r.submitted_at ASC
        LIMIT 4
    ");
    $dashboardRequests = $rDashStmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$pageTitle = 'Dashboard — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <!-- Sidebar -->
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <!-- Main Content -->
  <div class="admin-main">

    <!-- Topbar -->
    <header class="admin-topbar">
      <h1 class="admin-topbar__title">Dashboard</h1>
      <?php
        $topbarBtnId = 'adminUserMenuBtn';
        $profileUrl  = '/CommunityDono/admin/profile.php';
        $isAdmin     = true;
        require_once __DIR__ . '/../includes/topbar-user-btn.php';
      ?>
    </header>


    <div class="admin-content">
      <?php renderFlash(); ?>

      <!-- Welcome Banner -->
      <div style="background:linear-gradient(135deg,var(--brand-primary) 0%,var(--brand-primary-dark) 100%);color:var(--on-primary);border-radius:16px;padding:24px 28px;margin-bottom:28px;display:flex;justify-content:space-between;align-items:center;gap:16px;box-shadow:var(--shadow-md);">
        <div>
          <div style="font-weight:800;font-size:20px;margin-bottom:4px;">Admin Dashboard</div>
          <div style="font-size:14px;opacity:0.85;">Overview &bull; Action queues &bull; Moderation controls</div>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <a href="/CommunityDono/admin/donations.php?status=pending_verification" style="background:rgba(255,255,255,0.2);color:#fff;padding:8px 18px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;border:1px solid rgba(255,255,255,0.3);display:inline-flex;align-items:center;gap:6px;transition:all 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
            <i data-lucide="gift" style="width:15px;height:15px;"></i> Review Donations (<?= (int)$stats['pending_donations'] ?>)
          </a>
          <a href="/CommunityDono/admin/requests.php?status=pending" style="background:rgba(255,255,255,0.1);color:#fff;padding:8px 18px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;border:1px solid rgba(255,255,255,0.25);display:inline-flex;align-items:center;gap:6px;transition:all 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
            <i data-lucide="clipboard-list" style="width:15px;height:15px;"></i> Review Requests (<?= (int)$stats['pending_requests'] ?>)
          </a>
        </div>
      </div>

      <!-- 4 Stat Cards (Clickable Links) -->
      <div class="admin-stats-row">

        <a href="/CommunityDono/admin/donations.php?status=pending_verification" class="admin-stat-card" style="text-decoration:none;color:inherit;cursor:pointer;">
          <div class="admin-stat-card__icon" style="background:var(--sky);">
            <i data-lucide="gift" style="width:20px;height:20px;color:var(--cobalt);"></i>
          </div>
          <div class="admin-stat-card__label">Pending Donations</div>
          <div class="admin-stat-card__value"><?= (int)$stats['pending_donations'] ?></div>
        </a>

        <a href="/CommunityDono/admin/requests.php?status=pending" class="admin-stat-card" style="text-decoration:none;color:inherit;cursor:pointer;">
          <div class="admin-stat-card__icon" style="background:#FAE9E7;">
            <i data-lucide="clipboard-list" style="width:20px;height:20px;color:var(--coral);"></i>
          </div>
          <div class="admin-stat-card__label">Pending Requests</div>
          <div class="admin-stat-card__value"><?= (int)$stats['pending_requests'] ?></div>
        </a>

        <a href="/CommunityDono/admin/transactions.php" class="admin-stat-card" style="text-decoration:none;color:inherit;cursor:pointer;">
          <div class="admin-stat-card__icon" style="background:#FEF6E0;">
            <i data-lucide="arrow-right-left" style="width:20px;height:20px;color:var(--gold);"></i>
          </div>
          <div class="admin-stat-card__label">Active Transactions</div>
          <div class="admin-stat-card__value"><?= (int)$stats['active_matches'] ?></div>
        </a>

        <a href="/CommunityDono/admin/support.php" class="admin-stat-card" style="text-decoration:none;color:inherit;cursor:pointer;">
          <div class="admin-stat-card__icon" style="background:#FAE9E7;">
            <i data-lucide="message-circle-warning" style="width:20px;height:20px;color:var(--rejected);"></i>
          </div>
          <div class="admin-stat-card__label">Open Concerns</div>
          <div class="admin-stat-card__value"><?= (int)$stats['open_concerns'] ?></div>
        </a>

      </div>

      <!-- Dashboard Two-Column Grid -->
      <div class="admin-dashboard-grid">

        <!-- Left Column: Quick Actions + Recent Activity -->
        <div>

          <!-- Quick Actions (Icon Grid) -->
          <div class="admin-section-title">Quick Actions</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:26px;">

            <a href="/CommunityDono/admin/donations.php" class="admin-action-card" style="border-color:var(--sky);" onmouseover="this.style.background='var(--sky)';this.style.borderColor='var(--cobalt)'" onmouseout="this.style.background='#fff';this.style.borderColor='var(--sky)'">
              <div style="width:40px;height:40px;border-radius:10px;background:var(--cobalt);display:flex;align-items:center;justify-content:center;">
                <i data-lucide="gift" style="width:20px;height:20px;color:#fff;"></i>
              </div>
              <span style="font-size:12.5px;">Review Donations</span>
            </a>

            <a href="/CommunityDono/admin/requests.php" class="admin-action-card" style="border-color:#FAE9E7;" onmouseover="this.style.background='#FAE9E7';this.style.borderColor='var(--coral)'" onmouseout="this.style.background='#fff';this.style.borderColor='#FAE9E7'">
              <div style="width:40px;height:40px;border-radius:10px;background:var(--coral);display:flex;align-items:center;justify-content:center;">
                <i data-lucide="clipboard-list" style="width:20px;height:20px;color:#fff;"></i>
              </div>
              <span style="font-size:12.5px;">Review Requests</span>
            </a>

            <a href="/CommunityDono/admin/users.php" class="admin-action-card" style="border-color:#F5F3FF;" onmouseover="this.style.background='#F5F3FF';this.style.borderColor='#7C3AED'" onmouseout="this.style.background='#fff';this.style.borderColor='#F5F3FF'">
              <div style="width:40px;height:40px;border-radius:10px;background:#7C3AED;display:flex;align-items:center;justify-content:center;">
                <i data-lucide="users" style="width:20px;height:20px;color:#fff;"></i>
              </div>
              <span style="font-size:12.5px;">Manage Users</span>
            </a>

            <a href="/CommunityDono/admin/transactions.php" class="admin-action-card" style="border-color:#FEF3C7;" onmouseover="this.style.background='#FEF3C7';this.style.borderColor='#D97706'" onmouseout="this.style.background='#fff';this.style.borderColor='#FEF3C7'">
              <div style="width:40px;height:40px;border-radius:10px;background:#D97706;display:flex;align-items:center;justify-content:center;">
                <i data-lucide="arrow-right-left" style="width:20px;height:20px;color:#fff;"></i>
              </div>
              <span style="font-size:12.5px;">Transactions</span>
            </a>

          </div>

          <!-- Recent Activities Feed -->
          <div class="admin-section-title">Recent Activities</div>
          <div class="admin-activities-box">
            <?php if (empty($recentActivities)): ?>
              <div style="padding:32px 20px;text-align:center;">
                <div style="width:44px;height:44px;border-radius:50%;background:var(--brand-primary-light);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                  <i data-lucide="activity" style="width:22px;height:22px;color:var(--brand-primary);"></i>
                </div>
                <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:4px;">No recent activity</div>
                <div style="font-size:12px;color:var(--text-muted);">Activity will appear here as donations and requests come in.</div>
              </div>
            <?php else: ?>
              <?php
                $actColors = [
                  ['bg'=>'var(--sky)','fg'=>'var(--cobalt)','icon'=>'gift'],
                  ['bg'=>'#FFF0EC','fg'=>'#C94B32','icon'=>'clipboard-list'],
                  ['bg'=>'#FEF3C7','fg'=>'#D97706','icon'=>'arrow-right-left'],
                  ['bg'=>'#F5F3FF','fg'=>'#7C3AED','icon'=>'user'],
                  ['bg'=>'#DCFCE7','fg'=>'#16A34A','icon'=>'check-circle'],
                  ['bg'=>'#FEE2E2','fg'=>'#DC2626','icon'=>'alert-circle'],
                ];
                $actIdx = 0;
              ?>
              <?php foreach ($recentActivities as $i => $act):
                $c = $actColors[$actIdx % count($actColors)]; $actIdx++;
              ?>
              <div class="admin-activity-row" style="<?= $i < count($recentActivities) - 1 ? '' : '' ?>">
                <div class="admin-activity-dot" style="background:<?= $c['bg'] ?>;">
                  <i data-lucide="<?= $c['icon'] ?>" style="width:16px;height:16px;color:<?= $c['fg'] ?>;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                  <div class="admin-activity-title"><?= e($act['title']) ?></div>
                  <div class="admin-activity-meta"><?= date('M d, g:i A', strtotime($act['created_at'])) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div>

        <!-- Right Column: Donation Review Panel -->
        <div>
          <div class="admin-card">
            <?php if ($activeDonation): ?>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border);">
                <div>
                  <h2 style="font-size:15px;font-weight:700;margin:0 0 3px;color:var(--text);">
                    Donation Details — D-<?= (int)$activeDonation['id'] ?>
                  </h2>
                  <span style="font-size:11px;background:var(--warning-bg);color:var(--warning);padding:2px 9px;border-radius:20px;font-weight:700;display:inline-flex;align-items:center;gap:4px;"><i data-lucide="clock" style="width:11px;height:11px;"></i> Pending Review</span>
                </div>
                <a href="/CommunityDono/admin/donation-review.php?id=<?= (int)$activeDonation['id'] ?>" class="admin-btn admin-btn--outline admin-btn--sm">
                  <i data-lucide="maximize-2" style="width:12px;height:12px;"></i> Full Review
                </a>
              </div>

              <!-- Donation Info -->
              <div class="admin-detail-section">
                <div class="admin-detail-section-title">Donation Information</div>
                <div class="admin-kv-grid">
                  <div class="admin-kv-item">
                    <div class="admin-kv-label">Title</div>
                    <div class="admin-kv-value"><?= e($activeDonation['title']) ?></div>
                  </div>
                  <div class="admin-kv-item">
                    <div class="admin-kv-label">Category</div>
                    <div class="admin-kv-value"><?= e($activeDonation['category_name']) ?></div>
                  </div>
                  <div class="admin-kv-item">
                    <div class="admin-kv-label">Quantity</div>
                    <div class="admin-kv-value"><?= (int)$activeDonation['quantity_total'] ?> <?= e($activeDonation['unit']) ?></div>
                  </div>
                  <div class="admin-kv-item">
                    <div class="admin-kv-label">Condition</div>
                    <div class="admin-kv-value"><?= ucfirst(str_replace('_', ' ', $activeDonation['condition_type'])) ?></div>
                  </div>
                </div>
                <?php if (!empty($activeDonation['description'])): ?>
                <div class="admin-kv-item" style="grid-column:span 2;">
                  <div class="admin-kv-label">Description</div>
                  <div class="admin-kv-value" style="font-weight:400;font-size:12.5px;line-height:1.5;"><?= nl2br(e($activeDonation['description'])) ?></div>
                </div>
                <?php endif; ?>
              </div>

              <!-- Donor Info -->
              <div class="admin-detail-section">
                <div class="admin-detail-section-title">Donor Information</div>
                <div class="admin-kv-grid">
                  <div class="admin-kv-item">
                    <div class="admin-kv-label">Name</div>
                    <div class="admin-kv-value"><?= e($activeDonation['first_name'] . ' ' . $activeDonation['last_name']) ?></div>
                  </div>
                  <div class="admin-kv-item">
                    <div class="admin-kv-label">Email</div>
                    <div class="admin-kv-value"><?= e($activeDonation['donor_email']) ?></div>
                  </div>
                  <div class="admin-kv-item">
                    <div class="admin-kv-label">Contact</div>
                    <div class="admin-kv-value"><?= e($activeDonation['donor_phone'] ?? 'N/A') ?></div>
                  </div>
                  <div class="admin-kv-item">
                    <div class="admin-kv-label">Area</div>
                    <div class="admin-kv-value"><?= e($activeDonation['donor_city'] ?? 'Metro Manila') ?></div>
                  </div>
                </div>
              </div>

              <!-- Item Media -->
              <div class="admin-detail-section">
                <div class="admin-detail-section-title">Item Media</div>
                <?php
                  $previewImg = getDonationImage($activeDonation['primary_image'] ?? null, $activeDonation['category_name'] ?? '', (int)$activeDonation['id']);
                ?>
                <div style="border-radius:10px;overflow:hidden;height:150px;margin-bottom:14px;position:relative;">
                  <img src="<?= e($previewImg) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                  <?php if (empty($activeDonation['primary_image'])): ?>
                    <div style="position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,0.6);color:#fff;font-size:10px;padding:2px 8px;border-radius:4px;">No photo uploaded â€” placeholder shown</div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Review Form -->
              <div class="admin-detail-section">
                <div class="admin-detail-section-title">Admin Review</div>
                <div class="admin-notice-box">
                  <i data-lucide="info" style="width:14px;height:14px;vertical-align:middle;margin-right:6px;"></i>
                  <strong>Rejection reason required</strong> when choosing to reject a donation.
                </div>

                <form action="/CommunityDono/api/admin/approve-donation.php" method="POST">
                  <?= csrfField() ?>
                  <input type="hidden" name="donation_id" value="<?= (int)$activeDonation['id'] ?>">

                  <div style="margin-bottom:10px;">
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:0.04em;">Rejection Reason</label>
                    <select name="reason_preset" id="rejectionPreset" class="admin-select" onchange="if(this.value){document.getElementById('reasonInput').value=this.value;}">
                      <option value="">Select preset reason…</option>
                      <option value="Expired item or invalid expiration date">Expired item or invalid expiration date</option>
                      <option value="Inappropriate or non-permissible donation item">Inappropriate or non-permissible donation item</option>
                      <option value="Insufficient information provided">Insufficient information provided</option>
                      <option value="Item condition does not meet safety standards">Item condition does not meet safety standards</option>
                    </select>
                  </div>

                  <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:0.04em;">Additional Details</label>
                    <input type="text" name="reason" id="reasonInput" class="admin-search-input" placeholder="Optional / required for rejection">
                  </div>

                  <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="submit" class="admin-btn admin-btn--primary">
                      <i data-lucide="check-circle" style="width:14px;height:14px;"></i> Approve
                    </button>
                    <button type="submit" formaction="/CommunityDono/api/admin/reject-donation.php" class="admin-btn admin-btn--danger" onclick="if(!document.getElementById('reasonInput').value.trim()){alert('Rejection reason required.');return false;}">
                      <i data-lucide="x-circle" style="width:14px;height:14px;"></i> Reject
                    </button>
                    <a href="/CommunityDono/admin/donations.php" class="admin-btn admin-btn--outline">
                      <i data-lucide="message-square" style="width:14px;height:14px;"></i> Clarify
                    </a>
                  </div>
                </form>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($dashboardRequests)): ?>
            <div class="admin-card" style="margin-top: <?= $activeDonation ? '20px' : '0' ?>;">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid var(--border);">
                <div>
                  <h2 style="font-size:15px;font-weight:700;margin:0 0 2px;color:var(--text);display:flex;align-items:center;gap:6px;">
                    <i data-lucide="clipboard-list" style="width:16px;height:16px;color:var(--brand-accent-dark);"></i>
                    Action Required: Pending Requests
                  </h2>
                  <span style="font-size:11.5px;color:var(--text-muted);">
                    <?= count($dashboardRequests) ?> request(s) awaiting verification &amp; matching
                  </span>
                </div>
                <a href="/CommunityDono/admin/requests.php?status=pending" class="admin-btn admin-btn--outline admin-btn--sm">
                  View All (<?= (int)$stats['pending_requests'] ?>) &rarr;
                </a>
              </div>

              <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach ($dashboardRequests as $dr): ?>
                  <div style="padding:14px;background:var(--surface-soft);border:1px solid var(--border);border-radius:10px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;gap:8px;">
                      <div>
                        <span style="font-size:11px;font-weight:700;color:var(--brand-primary);background:var(--brand-primary-light);padding:2px 7px;border-radius:4px;">
                          R-<?= (int)$dr['id'] ?>
                        </span>
                        <strong style="font-size:13.5px;color:var(--text);margin-left:5px;"><?= e($dr['donation_title']) ?></strong>
                      </div>
                      <span style="font-size:11px;background:var(--brand-accent-light);color:var(--brand-accent-dark);padding:2px 8px;border-radius:12px;font-weight:700;white-space:nowrap;">
                        Qty: <?= (int)$dr['quantity_requested'] ?> <?= e($dr['unit']) ?>
                      </span>
                    </div>

                    <div style="font-size:12.5px;color:var(--text-muted);margin-bottom:8px;">
                      Requester: <strong style="color:var(--text);"><?= e($dr['first_name'] . ' ' . $dr['last_name']) ?></strong> &bull; <?= e($dr['requester_email']) ?>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;padding-top:8px;border-top:1px dashed var(--border);font-size:12px;">
                      <span>
                        <?php if (!empty($dr['proof_path'])): ?>
                          <i data-lucide="paperclip" style="width:12px;height:12px;color:var(--success);vertical-align:middle;"></i> <strong style="color:var(--success);">Proof Document Attached</strong>
                        <?php else: ?>
                          <span style="color:var(--text-muted);">Standard Request</span>
                        <?php endif; ?>
                      </span>
                      <a href="/CommunityDono/admin/requests.php?status=pending" class="admin-btn admin-btn--primary admin-btn--sm" style="padding:5px 12px;font-size:12px;">
                        <i data-lucide="zap" style="width:12px;height:12px;"></i> Review &amp; Match
                      </a>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!$activeDonation && empty($dashboardRequests)): ?>
            <div class="admin-card">
              <div style="text-align:center;padding:48px 20px;">
                <div style="width:56px;height:56px;border-radius:50%;background:var(--brand-primary-light);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                  <i data-lucide="check-circle-2" style="width:26px;height:26px;color:var(--brand-primary);"></i>
                </div>
                <h3 style="font-size:16px;font-weight:700;color:var(--text);margin:0 0 8px;">All Queues Clear</h3>
                <p style="font-size:13px;color:var(--text-muted);margin:0 0 20px;">No pending donations or requests requiring moderation at this time.</p>
                <div style="display:flex;justify-content:center;gap:10px;">
                  <a href="/CommunityDono/admin/donations.php?status=all" class="admin-btn admin-btn--outline">View All Donations</a>
                  <a href="/CommunityDono/admin/requests.php?status=all" class="admin-btn admin-btn--outline">Check Requests Queue</a>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <!-- Footer -->
    <footer class="admin-footer">
      <div>&copy; 2025 iCareDonate Community Platform. All rights reserved.</div>
      <div class="admin-footer-links">
        <a href="/CommunityDono/pages/legal/privacy.php">Privacy Policy</a>
        <a href="/CommunityDono/pages/legal/terms.php">Terms of Service</a>
        <a href="/CommunityDono/pages/faq.php">Help Center</a>
      </div>
    </footer>

  </div>
</div>

<script>
document.addEventListener('click', function(e) {
  var menu = document.getElementById('adminDropdownMenu');
  var btn  = document.getElementById('adminUserMenuBtn');
  if (menu && btn && !btn.contains(e.target) && !menu.contains(e.target)) {
    menu.style.display = 'none';
  }
});
document.getElementById('adminUserMenuBtn')?.addEventListener('click', function(e) {
  e.stopPropagation();
  var menu = document.getElementById('adminDropdownMenu');
  if (menu) menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
