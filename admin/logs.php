<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireSuperAdmin();

// ── Filters ─────────────────────────────────────────────────────────────────
$filterAction = trim($_GET['action'] ?? '');
$filterFrom   = trim($_GET['from'] ?? '');
$filterTo     = trim($_GET['to'] ?? '');
$page         = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 50;
$offset       = ($page - 1) * $perPage;

// ── Build WHERE ──────────────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if (!empty($filterAction)) {
    $where[]  = 'a.action = ?';
    $params[] = $filterAction;
}
if (!empty($filterFrom)) {
    $where[]  = 'DATE(a.created_at) >= ?';
    $params[] = $filterFrom;
}
if (!empty($filterTo)) {
    $where[]  = 'DATE(a.created_at) <= ?';
    $params[] = $filterTo;
}

$whereSql = implode(' AND ', $where);

// ── CSV Export (Superadmin authorized, protected against CSV formula injection) ─
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM
    fputcsv($output, ['ID', 'Timestamp', 'Action', 'Actor ID', 'Actor Role', 'Actor Name', 'Target Type', 'Target ID', 'Reason', 'IP Address']);

    $exportStmt = $pdo->prepare("
        SELECT a.*, u.first_name, u.last_name, u.email
        FROM audit_logs a
        LEFT JOIN users u ON a.actor_id = u.id
        WHERE $whereSql
        ORDER BY a.created_at DESC
        LIMIT 5000
    ");
    $exportStmt->execute($params);

    while ($row = $exportStmt->fetch()) {
        $actorName = $row['actor_id'] ? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) : 'System';
        fputcsv($output, [
            $row['id'],
            $row['created_at'],
            sanitizeCsvCell($row['action']),
            $row['actor_id'] ?? 'N/A',
            sanitizeCsvCell($row['actor_role'] ?? 'system'),
            sanitizeCsvCell($actorName),
            sanitizeCsvCell($row['target_type']),
            $row['target_id'],
            sanitizeCsvCell($row['reason'] ?? ''),
            sanitizeCsvCell($row['ip_address'] ?? '')
        ]);
    }
    fclose($output);
    exit;
}

// ── Count ────────────────────────────────────────────────────────────────────
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a WHERE $whereSql");
$countStmt->execute($params);
$totalLogs  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalLogs / $perPage));

// ── Distinct action types for filter dropdown ────────────────────────────────
$actionTypes = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);

// ── Main query ────────────────────────────────────────────────────────────────
$logs = [];
try {
    $stmt = $pdo->prepare("
        SELECT a.*, u.first_name, u.last_name, u.email
        FROM audit_logs a
        LEFT JOIN users u ON a.actor_id = u.id
        WHERE $whereSql
        ORDER BY a.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

// Action badge colour mapping
function actionBadgeClass(string $action): string
{
    return match(true) {
        str_contains($action, 'ARCHIVED')   => 'badge-pending',
        str_contains($action, 'RESTORED')   => 'badge-approved',
        str_contains($action, 'DELETED')    => 'badge-rejected',
        str_contains($action, 'APPROVED')   => 'badge-approved',
        str_contains($action, 'REJECTED')   => 'badge-rejected',
        str_contains($action, 'SUSPEND')    => 'badge-rejected',
        str_contains($action, 'BAN')        => 'badge-rejected',
        default                             => 'badge-info',
    };
}

$pageTitle = 'Security & Audit Logs — iCareDonate Admin';
$extraCss  = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Audit &amp; Security Ledger</h1>
      </div>
      <div>
        <span style="font-size: 12px; color: var(--color-text-muted);"><?= number_format($totalLogs) ?> total log entries</span>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <!-- ── Filters ─────────────────────────────────────────────────────── -->
      <div class="card" style="padding: 16px 20px; margin-bottom: 20px; border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
        <form method="GET" action="<?= $base ?>/admin/logs.php" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">

          <div style="min-width: 220px; flex: 1;">
            <label style="font-size: 11px; font-weight: 600; color: var(--color-text-muted); display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.04em;">Action Type</label>
            <select name="action" class="form-input" style="height: 36px; font-size: 13px;">
              <option value="">All Actions</option>
              <?php foreach ($actionTypes as $act): ?>
                <option value="<?= e($act) ?>" <?= $filterAction === $act ? 'selected' : '' ?>><?= e($act) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label style="font-size: 11px; font-weight: 600; color: var(--color-text-muted); display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.04em;">From Date</label>
            <input type="date" name="from" value="<?= e($filterFrom) ?>" class="form-input" style="height: 36px; font-size: 13px;">
          </div>

          <div>
            <label style="font-size: 11px; font-weight: 600; color: var(--color-text-muted); display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.04em;">To Date</label>
            <input type="date" name="to" value="<?= e($filterTo) ?>" class="form-input" style="height: 36px; font-size: 13px;">
          </div>

          <div style="display: flex; gap: 8px; align-items: flex-end;">
            <button type="submit" class="btn btn-primary" style="height: 36px; font-size: 13px; padding: 0 16px;">Filter</button>
            <?php if (!empty($filterAction) || !empty($filterFrom) || !empty($filterTo)): ?>
              <a href="<?= $base ?>/admin/logs.php" class="btn btn-outline" style="height: 36px; font-size: 13px; padding: 0 14px;">Clear</a>
            <?php endif; ?>
            <a href="<?= $base ?>/admin/logs.php?export=csv&action=<?= urlencode($filterAction) ?>&from=<?= urlencode($filterFrom) ?>&to=<?= urlencode($filterTo) ?>"
               class="btn btn-outline"
               style="height: 36px; font-size: 13px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px;">
              <i data-lucide="download" style="width:13px;height:13px;"></i> Export CSV
            </a>
          </div>
        </form>
      </div>

      <div class="admin-table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Action</th>
              <th>Actor</th>
              <th>Target</th>
              <th>Details / Reason</th>
              <th>IP Address</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr>
                <td colspan="6" style="text-align: center; padding: 32px; color: var(--color-text-muted);">No audit events found for these filters.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td style="white-space: nowrap; font-size: 12px; color: var(--color-text-muted);">
                    <?= date('M d, Y H:i:s', strtotime($log['created_at'])) ?>
                  </td>
                  <td>
                    <span class="badge <?= actionBadgeClass($log['action']) ?>" style="font-family: monospace; font-size: 11px;">
                      <?= e($log['action']) ?>
                    </span>
                  </td>
                  <td>
                    <?= $log['actor_id']
                        ? e(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) . ' <span style="font-size: 11px; color: var(--color-text-muted);">(' . e($log['actor_role'] ?? 'user') . ')</span>'
                        : '<em style="color: #94A3B8;">System</em>' ?>
                  </td>
                  <td>
                    <?= e($log['target_type']) ?> #<?= (int)$log['target_id'] ?>
                  </td>
                  <td style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($log['reason'] ?? '') ?>">
                    <?= e($log['reason'] ?? '—') ?>
                  </td>
                  <td style="font-family: monospace; font-size: 12px; color: var(--color-text-muted);">
                    <?= e($log['ip_address'] ?? '127.0.0.1') ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 8px; margin-top: 24px; flex-wrap: wrap;">
          <?php for ($i = 1; $i <= min($totalPages, 20); $i++): ?>
            <a href="?p=<?= $i ?>&action=<?= urlencode($filterAction) ?>&from=<?= urlencode($filterFrom) ?>&to=<?= urlencode($filterTo) ?>"
               class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>"
               style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600;">
              <?= $i ?>
            </a>
          <?php endfor; ?>
          <?php if ($totalPages > 20): ?>
            <span style="align-self: center; font-size: 12px; color: var(--color-text-muted);">… <?= number_format($totalPages) ?> pages total</span>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
