<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$reportType = trim($_GET['type'] ?? 'donations');
$startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));
$categoryId = (int)($_GET['category_id'] ?? 0);
$export = trim($_GET['export'] ?? '');

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order ASC, name ASC')->fetchAll();

// CSV Export Handler
if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="iCareDonate_' . $reportType . '_report_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');

    if ($reportType === 'donations') {
        fputcsv($output, ['ID', 'Title', 'Category', 'Donor', 'Total Qty', 'Reserved', 'Claimed', 'Status', 'Date Posted']);
        $stmt = $pdo->prepare('
          SELECT d.id, d.title, c.name AS category_name, CONCAT(u.first_name, " ", u.last_name) AS donor_name,
                 d.quantity_total, d.quantity_reserved, d.quantity_claimed, d.status, d.created_at
          FROM donations d
          LEFT JOIN categories c ON d.category_id = c.id
          LEFT JOIN users u ON d.donor_id = u.id
          WHERE DATE(d.created_at) BETWEEN ? AND ?
          ' . ($categoryId > 0 ? ' AND d.category_id = ' . $categoryId : '') . '
          ORDER BY d.created_at DESC
        ');
        $stmt->execute([$startDate, $endDate]);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            fputcsv($output, $row);
        }
    } elseif ($reportType === 'requests') {
        fputcsv($output, ['ID', 'Donation Resource', 'Requester', 'Qty Requested', 'Qty Matched', 'Urgency', 'Status', 'Date Submitted']);
        $stmt = $pdo->prepare('
          SELECT r.id, d.title AS donation_title, CONCAT(u.first_name, " ", u.last_name) AS req_name,
                 r.quantity_requested, r.quantity_matched, r.urgency_level, r.status, r.created_at
          FROM requests r
          JOIN donations d ON r.donation_id = d.id
          JOIN users u ON r.requester_id = u.id
          WHERE DATE(r.created_at) BETWEEN ? AND ?
          ' . ($categoryId > 0 ? ' AND d.category_id = ' . $categoryId : '') . '
          ORDER BY r.created_at DESC
        ');
        $stmt->execute([$startDate, $endDate]);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            fputcsv($output, $row);
        }
    } elseif ($reportType === 'transactions') {
        fputcsv($output, ['Txn ID', 'Donation', 'Donor', 'Requester', 'Quantity', 'Pickup Method', 'Status', 'Created Date']);
        $stmt = $pdo->prepare('
          SELECT t.id, d.title, CONCAT(ud.first_name, " ", ud.last_name), CONCAT(ur.first_name, " ", ur.last_name),
                 t.quantity, t.pickup_method, t.status, t.created_at
          FROM transactions t
          JOIN donations d ON t.donation_id = d.id
          JOIN users ud ON t.donor_id = ud.id
          JOIN users ur ON t.requester_id = ur.id
          WHERE DATE(t.created_at) BETWEEN ? AND ?
          ORDER BY t.created_at DESC
        ');
        $stmt->execute([$startDate, $endDate]);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            fputcsv($output, $row);
        }
    } elseif ($reportType === 'claims') {
        fputcsv($output, ['Txn ID', 'Claim Code', 'Donation', 'Requester', 'Quantity', 'Deadline', 'Status']);
        $stmt = $pdo->prepare('
          SELECT t.id, CONCAT("IC-", LPAD(t.id, 4, "0"), "-D", t.donation_id), d.title, CONCAT(ur.first_name, " ", ur.last_name),
                 t.quantity, t.claim_deadline, t.status
          FROM transactions t
          JOIN donations d ON t.donation_id = d.id
          JOIN users ur ON t.requester_id = ur.id
          WHERE DATE(t.created_at) BETWEEN ? AND ?
          ORDER BY t.created_at DESC
        ');
        $stmt->execute([$startDate, $endDate]);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            fputcsv($output, $row);
        }
    } elseif ($reportType === 'users') {
        fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'City', 'Role', 'Status', 'Registered Date']);
        $stmt = $pdo->prepare('
          SELECT id, CONCAT(first_name, " ", last_name), email, phone, city, role, status, created_at
          FROM users
          WHERE DATE(created_at) BETWEEN ? AND ?
          ORDER BY created_at DESC
        ');
        $stmt->execute([$startDate, $endDate]);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            fputcsv($output, $row);
        }
    } elseif ($reportType === 'support') {
        fputcsv($output, ['ID', 'Subject', 'Reporter/User', 'Email', 'Status', 'Date Submitted']);
        $stmt = $pdo->prepare('
          SELECT id, subject, CONCAT(u.first_name, " ", u.last_name), email, status, created_at
          FROM support_tickets st
          LEFT JOIN users u ON st.user_id = u.id
          WHERE DATE(st.created_at) BETWEEN ? AND ?
          ORDER BY st.created_at DESC
        ');
        $stmt->execute([$startDate, $endDate]);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            fputcsv($output, $row);
        }
    }

    fclose($output);
    exit;
}

// Fetch report data for UI
$reportData = [];
$summaryStats = [];

if ($reportType === 'donations') {
    $where = 'DATE(d.created_at) BETWEEN ? AND ?';
    $params = [$startDate, $endDate];
    if ($categoryId > 0) {
        $where .= ' AND d.category_id = ?';
        $params[] = $categoryId;
    }
    $stmt = $pdo->prepare("
      SELECT d.*, c.name AS category_name, CONCAT(u.first_name, ' ', u.last_name) AS donor_name
      FROM donations d
      LEFT JOIN categories c ON d.category_id = c.id
      LEFT JOIN users u ON d.donor_id = u.id
      WHERE $where
      ORDER BY d.created_at DESC
    ");
    $stmt->execute($params);
    $reportData = $stmt->fetchAll();

    $summaryStats = [
        'Total Listings' => count($reportData),
        'Total Quantity' => array_sum(array_column($reportData, 'quantity_total')),
        'Approved' => count(array_filter($reportData, fn($r) => $r['status'] === 'approved')),
        'Completed/Allocated' => count(array_filter($reportData, fn($r) => $r['status'] === 'fully_allocated')),
    ];
} elseif ($reportType === 'requests') {
    $where = 'DATE(r.created_at) BETWEEN ? AND ?';
    $params = [$startDate, $endDate];
    if ($categoryId > 0) {
        $where .= ' AND d.category_id = ?';
        $params[] = $categoryId;
    }
    $stmt = $pdo->prepare("
      SELECT r.*, d.title AS donation_title, c.name AS category_name, CONCAT(u.first_name, ' ', u.last_name) AS req_name
      FROM requests r
      JOIN donations d ON r.donation_id = d.id
      LEFT JOIN categories c ON d.category_id = c.id
      JOIN users u ON r.requester_id = u.id
      WHERE $where
      ORDER BY r.created_at DESC
    ");
    $stmt->execute($params);
    $reportData = $stmt->fetchAll();

    $summaryStats = [
        'Total Requests' => count($reportData),
        'High Urgency' => count(array_filter($reportData, fn($r) => $r['urgency_level'] === 'high')),
        'Approved/Matched' => count(array_filter($reportData, fn($r) => in_array($r['status'], ['approved', 'matched', 'completed']))),
        'Completed' => count(array_filter($reportData, fn($r) => $r['status'] === 'completed')),
    ];
} elseif ($reportType === 'transactions') {
    $stmt = $pdo->prepare("
      SELECT t.*, d.title AS donation_title, CONCAT(ud.first_name, ' ', ud.last_name) AS donor_name, CONCAT(ur.first_name, ' ', ur.last_name) AS req_name
      FROM transactions t
      JOIN donations d ON t.donation_id = d.id
      JOIN users ud ON t.donor_id = ud.id
      JOIN users ur ON t.requester_id = ur.id
      WHERE DATE(t.created_at) BETWEEN ? AND ?
      ORDER BY t.created_at DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $reportData = $stmt->fetchAll();

    $summaryStats = [
        'Total Matches' => count($reportData),
        'Units Allocated' => array_sum(array_column($reportData, 'quantity')),
        'Active Claims' => count(array_filter($reportData, fn($r) => in_array($r['status'], ['pending_claim', 'claimed']))),
        'Completed' => count(array_filter($reportData, fn($r) => $r['status'] === 'completed')),
    ];
} elseif ($reportType === 'claims') {
    $stmt = $pdo->prepare("
      SELECT t.*, d.title AS donation_title, CONCAT(ur.first_name, ' ', ur.last_name) AS req_name
      FROM transactions t
      JOIN donations d ON t.donation_id = d.id
      JOIN users ur ON t.requester_id = ur.id
      WHERE DATE(t.created_at) BETWEEN ? AND ?
      ORDER BY t.created_at DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $reportData = $stmt->fetchAll();

    $summaryStats = [
        'Total Claims' => count($reportData),
        'Ready for Pickup' => count(array_filter($reportData, fn($r) => $r['status'] === 'pending_claim')),
        'Claimed / In Transit' => count(array_filter($reportData, fn($r) => $r['status'] === 'claimed')),
        'Expired / Overdue' => count(array_filter($reportData, fn($r) => $r['status'] === 'expired')),
    ];
} elseif ($reportType === 'users') {
    $stmt = $pdo->prepare("
      SELECT * FROM users WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $reportData = $stmt->fetchAll();

    $summaryStats = [
        'Total Registered' => count($reportData),
        'Active Accounts' => count(array_filter($reportData, fn($r) => $r['status'] === 'active')),
        'Verified Emails' => count(array_filter($reportData, fn($r) => !empty($r['email_verified_at']))),
        'Flagged / Suspended' => count(array_filter($reportData, fn($r) => in_array($r['status'], ['suspended', 'banned']))),
    ];
} elseif ($reportType === 'support') {
    $stmt = $pdo->prepare("
      SELECT st.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name
      FROM support_tickets st
      LEFT JOIN users u ON st.user_id = u.id
      WHERE DATE(st.created_at) BETWEEN ? AND ?
      ORDER BY st.created_at DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $reportData = $stmt->fetchAll();

    $summaryStats = [
        'Total Concerns' => count($reportData),
        'Open Tickets' => count(array_filter($reportData, fn($r) => $r['status'] === 'open')),
        'In Progress' => count(array_filter($reportData, fn($r) => $r['status'] === 'in_progress')),
        'Resolved' => count(array_filter($reportData, fn($r) => $r['status'] === 'resolved')),
    ];
}

$pageTitle = 'Reports & Accountability — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
@media print {
  body { background: #fff !important; color: #000 !important; }
  .admin-sidebar, .admin-topbar, .report-controls, .btn, .footer-public, .logo--admin { display: none !important; }
  .admin-layout, .admin-main, .admin-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
  .card { border: none !important; box-shadow: none !important; padding: 0 !important; }
  .print-header { display: block !important; margin-bottom: 20px; border-bottom: 2px solid #111; padding-bottom: 10px; }
  .print-header h1 { font-size: 22pt; margin: 0; color: #111; }
  .print-header p { font-size: 10pt; color: #555; margin: 4px 0 0; }
  .admin-table { font-size: 9pt !important; width: 100% !important; }
  .admin-table th, .admin-table td { padding: 4px 6px !important; }
}
.print-header { display: none; }
</style>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Reports & Organizational Data</h1>
      </div>
      <div style="display: flex; gap: 8px;">
        <button type="button" onclick="window.print()" class="btn btn-outline" style="font-size: 12px; padding: 5px 12px; display: inline-flex; align-items: center; gap: 4px;">
          <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Print / PDF
        </button>
        <a href="/CommunityDono/admin/reports.php?type=<?= urlencode($reportType) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&category_id=<?= $categoryId ?>&export=csv" class="btn btn-primary" style="font-size: 12px; padding: 5px 12px; display: inline-flex; align-items: center; gap: 4px;">
          <i data-lucide="download" style="width: 14px; height: 14px;"></i> Export CSV
        </a>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <!-- Print Only Header -->
      <div class="print-header">
        <h1>iCareDonate — <?= ucfirst($reportType) ?> Report</h1>
        <p>Period: <?= date('M d, Y', strtotime($startDate)) ?> to <?= date('M d, Y', strtotime($endDate)) ?> | Generated: <?= date('F j, Y, g:i A') ?> by <?= e($_SESSION['user_name'] ?? 'Admin') ?></p>
      </div>

      <!-- Report Controls & Filters -->
      <div class="card report-controls" style="padding: 18px 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md); margin-bottom: 20px;">
        <form method="GET" action="/CommunityDono/admin/reports.php" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end;">
          <div>
            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--color-text-muted); margin-bottom: 4px;">Report Type</label>
            <select name="type" class="form-input" style="height: 34px; font-size: 12px; min-width: 160px;" onchange="this.form.submit()">
              <option value="donations" <?= $reportType === 'donations' ? 'selected' : '' ?>>Donations Report</option>
              <option value="requests" <?= $reportType === 'requests' ? 'selected' : '' ?>>Requests Report</option>
              <option value="transactions" <?= $reportType === 'transactions' ? 'selected' : '' ?>>Transactions Report</option>
              <option value="claims" <?= $reportType === 'claims' ? 'selected' : '' ?>>Claims & Pickups</option>
              <option value="users" <?= $reportType === 'users' ? 'selected' : '' ?>>User Activity Report</option>
              <option value="support" <?= $reportType === 'support' ? 'selected' : '' ?>>Support & Inquiries</option>
            </select>
          </div>

          <div>
            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--color-text-muted); margin-bottom: 4px;">From Date</label>
            <input type="date" name="start_date" value="<?= e($startDate) ?>" class="form-input" style="height: 34px; font-size: 12px;">
          </div>

          <div>
            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--color-text-muted); margin-bottom: 4px;">To Date</label>
            <input type="date" name="end_date" value="<?= e($endDate) ?>" class="form-input" style="height: 34px; font-size: 12px;">
          </div>

          <?php if (in_array($reportType, ['donations', 'requests'])): ?>
            <div>
              <label style="display: block; font-size: 11px; font-weight: 700; color: var(--color-text-muted); margin-bottom: 4px;">Category</label>
              <select name="category_id" class="form-input" style="height: 34px; font-size: 12px;">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                    <?= e($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div>
            <button type="submit" class="btn btn-outline" style="height: 34px; font-size: 12px; padding: 0 16px;">
              Filter Data
            </button>
          </div>
        </form>
      </div>

      <!-- KPI Summary Cards -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px;">
        <?php foreach ($summaryStats as $label => $val): ?>
          <div class="card" style="padding: 16px; border: 1px solid var(--color-border); border-radius: var(--radius-md); text-align: center; background: #fff;">
            <div style="font-family: var(--font-brand); font-size: 26px; color: var(--color-primary); line-height: 1.1; margin-bottom: 4px;">
              <?= is_numeric($val) ? number_format($val) : e($val) ?>
            </div>
            <div style="font-size: 12px; color: var(--color-text-muted); font-weight: 500;"><?= e($label) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Report Tabular Data -->
      <div class="card" style="padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-md); background: #fff;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
          <h2 style="font-size: 16px; font-weight: 700; color: var(--color-text); margin: 0;">
            <?= ucfirst($reportType) ?> Detailed Records (<?= count($reportData) ?> rows)
          </h2>
        </div>

        <div class="admin-table-container">
          <table class="admin-table">
            <thead>
              <?php if ($reportType === 'donations'): ?>
                <tr>
                  <th>ID</th>
                  <th>Resource Title</th>
                  <th>Category</th>
                  <th>Donor</th>
                  <th>Total Qty</th>
                  <th>Reserved</th>
                  <th>Claimed</th>
                  <th>Status</th>
                  <th>Posted Date</th>
                </tr>
              <?php elseif ($reportType === 'requests'): ?>
                <tr>
                  <th>ID</th>
                  <th>Resource Needed</th>
                  <th>Category</th>
                  <th>Requester</th>
                  <th>Qty Needed</th>
                  <th>Urgency</th>
                  <th>Status</th>
                  <th>Submitted</th>
                </tr>
              <?php elseif ($reportType === 'transactions'): ?>
                <tr>
                  <th>Txn ID</th>
                  <th>Donation Item</th>
                  <th>Donor</th>
                  <th>Requester</th>
                  <th>Qty</th>
                  <th>Method</th>
                  <th>Status</th>
                  <th>Date</th>
                </tr>
              <?php elseif ($reportType === 'claims'): ?>
                <tr>
                  <th>Txn ID</th>
                  <th>Claim Code</th>
                  <th>Donation Resource</th>
                  <th>Requester</th>
                  <th>Allocated Qty</th>
                  <th>Deadline</th>
                  <th>Status</th>
                </tr>
              <?php elseif ($reportType === 'users'): ?>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Location</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Registered</th>
                </tr>
              <?php elseif ($reportType === 'support'): ?>
                <tr>
                  <th>ID</th>
                  <th>Subject</th>
                  <th>User</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Submitted</th>
                </tr>
              <?php endif; ?>
            </thead>
            <tbody>
              <?php if (empty($reportData)): ?>
                <tr>
                  <td colspan="9" style="text-align: center; color: var(--color-text-muted); padding: 24px;">No records match the selected date range and filters.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($reportData as $row): ?>
                  <?php if ($reportType === 'donations'): ?>
                    <tr>
                      <td>D-<?= (int)$row['id'] ?></td>
                      <td><strong><?= e($row['title']) ?></strong></td>
                      <td><?= e($row['category_name'] ?? 'General') ?></td>
                      <td><?= e($row['donor_name'] ?? 'Anonymous') ?></td>
                      <td><?= (int)$row['quantity_total'] ?> <?= e($row['unit'] ?? '') ?></td>
                      <td><?= (int)$row['quantity_reserved'] ?></td>
                      <td><?= (int)$row['quantity_claimed'] ?></td>
                      <td><?= renderBadge($row['status']) ?></td>
                      <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                  <?php elseif ($reportType === 'requests'): ?>
                    <tr>
                      <td>R-<?= (int)$row['id'] ?></td>
                      <td><strong><?= e($row['donation_title']) ?></strong></td>
                      <td><?= e($row['category_name'] ?? 'General') ?></td>
                      <td><?= e($row['req_name'] ?? 'User') ?></td>
                      <td><?= (int)$row['quantity_requested'] ?></td>
                      <td><span class="badge badge-<?= $row['urgency_level'] === 'high' ? 'danger' : 'info' ?>"><?= ucfirst($row['urgency_level']) ?></span></td>
                      <td><?= renderBadge($row['status']) ?></td>
                      <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                  <?php elseif ($reportType === 'transactions'): ?>
                    <tr>
                      <td>T-<?= (int)$row['id'] ?></td>
                      <td><strong><?= e($row['donation_title']) ?></strong></td>
                      <td><?= e($row['donor_name']) ?></td>
                      <td><?= e($row['req_name']) ?></td>
                      <td><?= (int)$row['quantity'] ?></td>
                      <td><?= ucfirst($row['pickup_method']) ?></td>
                      <td><?= renderBadge($row['status']) ?></td>
                      <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                  <?php elseif ($reportType === 'claims'): ?>
                    <tr>
                      <td>T-<?= (int)$row['id'] ?></td>
                      <td><span style="font-family: monospace; font-weight: 700;">IC-<?= str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT) ?>-D<?= $row['donation_id'] ?></span></td>
                      <td><strong><?= e($row['donation_title']) ?></strong></td>
                      <td><?= e($row['req_name']) ?></td>
                      <td><?= (int)$row['quantity'] ?></td>
                      <td><?= date('M d, Y', strtotime($row['claim_deadline'])) ?></td>
                      <td><?= renderBadge($row['status']) ?></td>
                    </tr>
                  <?php elseif ($reportType === 'users'): ?>
                    <tr>
                      <td>#<?= (int)$row['id'] ?></td>
                      <td><strong><?= e($row['first_name'] . ' ' . $row['last_name']) ?></strong></td>
                      <td><?= e($row['email']) ?></td>
                      <td><?= e($row['city'] ?? 'Metro Manila') ?></td>
                      <td><span class="badge badge-info"><?= ucfirst($row['role']) ?></span></td>
                      <td><?= renderBadge($row['status']) ?></td>
                      <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                  <?php elseif ($reportType === 'support'): ?>
                    <tr>
                      <td>#<?= (int)$row['id'] ?></td>
                      <td><strong><?= e($row['subject']) ?></strong></td>
                      <td><?= e($row['user_name'] ?? 'Visitor') ?></td>
                      <td><?= e($row['email'] ?? '—') ?></td>
                      <td><?= renderBadge($row['status']) ?></td>
                      <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                  <?php endif; ?>
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
