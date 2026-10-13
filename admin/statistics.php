<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

// Metrics queries
$stats = [
    'users_total' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'users_active' => (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn(),
    'users_flagged' => (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status IN ("suspended", "banned")')->fetchColumn(),
    'donations_total' => (int)$pdo->query('SELECT COUNT(*) FROM donations')->fetchColumn(),
    'donations_active' => (int)$pdo->query('SELECT COUNT(*) FROM donations WHERE status = "approved"')->fetchColumn(),
    'donations_completed' => (int)$pdo->query('SELECT COUNT(*) FROM donations WHERE status = "fully_allocated"')->fetchColumn(),
    'requests_total' => (int)$pdo->query('SELECT COUNT(*) FROM requests')->fetchColumn(),
    'requests_pending' => (int)$pdo->query('SELECT COUNT(*) FROM requests WHERE status IN ("submitted", "proof_review")')->fetchColumn(),
    'requests_completed' => (int)$pdo->query('SELECT COUNT(*) FROM requests WHERE status = "completed"')->fetchColumn(),
    'transactions_total' => (int)$pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn(),
    'transactions_completed' => (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE status = "completed"')->fetchColumn(),
    'claims_active' => (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE status IN ("pending_claim", "claimed")')->fetchColumn(),
    'claims_expired' => (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE status = "expired"')->fetchColumn(),
    'tickets_open' => (int)$pdo->query('SELECT COUNT(*) FROM support_tickets WHERE status = "open"')->fetchColumn(),
];

// Donations by category
$catStats = $pdo->query('
  SELECT c.name, COUNT(d.id) AS count, COALESCE(SUM(d.quantity_total), 0) AS total_qty
  FROM categories c
  LEFT JOIN donations d ON c.id = d.category_id
  GROUP BY c.id, c.name
  ORDER BY count DESC
')->fetchAll();

// Request urgency breakdown
$urgencyStats = $pdo->query('
  SELECT urgency_level, COUNT(*) as count 
  FROM requests 
  GROUP BY urgency_level
')->fetchAll(PDO::FETCH_KEY_PAIR);

// Recent activity audit summary
$recentAudits = $pdo->query('
  SELECT a.*, u.first_name, u.last_name 
  FROM audit_logs a 
  LEFT JOIN users u ON a.actor_id = u.id 
  ORDER BY a.created_at DESC 
  LIMIT 6
')->fetchAll();

$pageTitle = 'Statistics & Analytics — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Platform Statistics & Analytics</h1>
      </div>
      <div>
        <a href="<?= $base ?>/admin/reports.php" class="btn btn-primary" style="font-size: 12px; padding: 5px 12px;">
          Generate Detailed Reports &rarr;
        </a>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <!-- High-Level KPI Grid -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px;">
        <div class="card" style="padding: 18px; border: 1px solid var(--color-border); border-radius: var(--radius-md); text-align: center;">
          <div style="font-family: var(--font-brand); font-size: 32px; color: var(--color-primary); line-height: 1;"><?= $stats['users_total'] ?></div>
          <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">Registered Users</div>
          <div style="font-size: 11px; color: #10B981; font-weight: 600; margin-top: 2px;"><?= $stats['users_active'] ?> active</div>
        </div>

        <div class="card" style="padding: 18px; border: 1px solid var(--color-border); border-radius: var(--radius-md); text-align: center;">
          <div style="font-family: var(--font-brand); font-size: 32px; color: var(--color-primary); line-height: 1;"><?= $stats['donations_total'] ?></div>
          <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">Donations Posted</div>
          <div style="font-size: 11px; color: var(--color-primary); font-weight: 600; margin-top: 2px;"><?= $stats['donations_active'] ?> active live</div>
        </div>

        <div class="card" style="padding: 18px; border: 1px solid var(--color-border); border-radius: var(--radius-md); text-align: center;">
          <div style="font-family: var(--font-brand); font-size: 32px; color: var(--color-primary); line-height: 1;"><?= $stats['requests_total'] ?></div>
          <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">Requests Submitted</div>
          <div style="font-size: 11px; color: #F59E0B; font-weight: 600; margin-top: 2px;"><?= $stats['requests_pending'] ?> pending review</div>
        </div>

        <div class="card" style="padding: 18px; border: 1px solid var(--color-border); border-radius: var(--radius-md); text-align: center;">
          <div style="font-family: var(--font-brand); font-size: 32px; color: #10B981; line-height: 1;"><?= $stats['transactions_completed'] ?></div>
          <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">Completed Matches</div>
          <div style="font-size: 11px; color: var(--color-text-muted); margin-top: 2px;"><?= $stats['transactions_total'] ?> total matches</div>
        </div>

        <div class="card" style="padding: 18px; border: 1px solid var(--color-border); border-radius: var(--radius-md); text-align: center;">
          <div style="font-family: var(--font-brand); font-size: 32px; color: var(--color-accent); line-height: 1;"><?= $stats['claims_active'] ?></div>
          <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">Active Claims</div>
          <div style="font-size: 11px; color: #EF4444; font-weight: 600; margin-top: 2px;"><?= $stats['claims_expired'] ?> expired</div>
        </div>
      </div>

      <!-- Category & Urgency Distribution -->
      <div class="grid grid--2col" style="gap: 20px; margin-bottom: 24px;">
        <!-- Categories breakdown -->
        <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
          <h2 style="font-size: 15px; font-weight: 700; color: var(--color-text); margin-bottom: 16px;">Donations by Category</h2>
          <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php 
            $maxCatCount = max(1, ...array_column($catStats, 'count'));
            foreach ($catStats as $cat): 
              $percent = round(($cat['count'] / $maxCatCount) * 100);
            ?>
              <div>
                <div style="display: flex; justify-content: space-between; font-size: 12.5px; margin-bottom: 4px;">
                  <span style="font-weight: 600;"><?= e($cat['name']) ?></span>
                  <span style="color: var(--color-text-muted);"><?= (int)$cat['count'] ?> listings (<?= (int)$cat['total_qty'] ?> units)</span>
                </div>
                <div style="height: 8px; background: #F1F5F9; border-radius: 4px; overflow: hidden;">
                  <div style="height: 100%; width: <?= $percent ?>%; background: var(--color-primary); border-radius: 4px;"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Request Urgency & Status Matrix -->
        <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
          <h2 style="font-size: 15px; font-weight: 700; color: var(--color-text); margin-bottom: 16px;">Request Urgency Distribution</h2>
          
          <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px;">
            <div style="padding: 14px; background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: var(--radius-sm); text-align: center;">
              <div style="font-family: var(--font-brand); font-size: 24px; color: #DC2626;"><?= (int)($urgencyStats['high'] ?? 0) ?></div>
              <div style="font-size: 11.5px; font-weight: 600; color: #991B1B;">High Urgency</div>
            </div>
            <div style="padding: 14px; background: #FFFBEB; border: 1px solid #FCD34D; border-radius: var(--radius-sm); text-align: center;">
              <div style="font-family: var(--font-brand); font-size: 24px; color: #D97706;"><?= (int)($urgencyStats['medium'] ?? 0) ?></div>
              <div style="font-size: 11.5px; font-weight: 600; color: #92400E;">Medium Urgency</div>
            </div>
            <div style="padding: 14px; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: var(--radius-sm); text-align: center;">
              <div style="font-family: var(--font-brand); font-size: 24px; color: #2563EB;"><?= (int)($urgencyStats['low'] ?? 0) ?></div>
              <div style="font-size: 11.5px; font-weight: 600; color: #1E40AF;">Low Urgency</div>
            </div>
          </div>

          <h3 style="font-size: 13.5px; font-weight: 700; color: var(--color-text); margin-bottom: 10px;">Platform Health Summary</h3>
          <ul style="font-size: 12.5px; color: var(--color-text); line-height: 1.8; margin: 0; padding-left: 18px;">
            <li><strong>Fulfillment Rate:</strong> <?= $stats['transactions_total'] > 0 ? round(($stats['transactions_completed'] / $stats['transactions_total']) * 100) : 100 ?>% of matched transactions completed successfully.</li>
            <li><strong>Community Trust:</strong> <?= $stats['users_flagged'] ?> flagged/suspended accounts under moderation.</li>
            <li><strong>Support Inbox:</strong> <?= $stats['tickets_open'] ?> unresolved support queries pending reply.</li>
          </ul>
        </div>
      </div>

      <!-- Recent System Activities -->
      <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
          <h2 style="font-size: 15px; font-weight: 700; color: var(--color-text); margin: 0;">Recent Activity Log</h2>
          <a href="<?= $base ?>/admin/logs.php" style="font-size: 12px; color: var(--color-primary); text-decoration: none;">View Full Audit Log &rarr;</a>
        </div>
        <div class="admin-table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Administrator</th>
                <th>Action</th>
                <th>Target</th>
                <th>Note</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentAudits as $a): ?>
                <tr>
                  <td style="font-size: 12px;"><?= date('M d, Y g:i A', strtotime($a['created_at'])) ?></td>
                  <td><strong><?= e(($a['first_name'] ?? 'System') . ' ' . ($a['last_name'] ?? '')) ?></strong></td>
                  <td><span class="badge badge-info"><?= e($a['action']) ?></span></td>
                  <td><?= e($a['target_type']) ?> #<?= (int)$a['target_id'] ?></td>
                  <td style="font-size: 12px;"><?= e($a['reason'] ?? '—') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
