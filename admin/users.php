<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

$where = '1=1';
$params = [];

if (!empty($search)) {
    $where .= ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR city LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter !== 'all' && in_array($statusFilter, ['active', 'pending_verification', 'suspended', 'banned'], true)) {
    $where .= ' AND status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Counts for filter pills
$counts = [
    'all' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'active' => (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn(),
    'pending_verification' => (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status = "pending_verification"')->fetchColumn(),
    'suspended' => (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status = "suspended"')->fetchColumn(),
    'banned' => (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status = "banned"')->fetchColumn(),
];

$pageTitle = 'User Management — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">User Accounts Management</h1>
      </div>
      <div>
        <form method="GET" action="/CommunityDono/admin/users.php" style="display: flex; gap: 8px;">
          <?php if ($statusFilter !== 'all'): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
          <?php endif; ?>
          <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by name, email, city..." class="form-input" style="height: 32px; font-size: 12px; width: 220px;">
          <button type="submit" class="btn btn-outline" style="font-size: 12px; padding: 4px 10px;">Search</button>
        </form>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <!-- Filter tabs -->
      <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
        <a href="/CommunityDono/admin/users.php?status=all<?= $search ? '&q='.urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          All Users (<?= $counts['all'] ?>)
        </a>
        <a href="/CommunityDono/admin/users.php?status=active<?= $search ? '&q='.urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          Active (<?= $counts['active'] ?>)
        </a>
        <a href="/CommunityDono/admin/users.php?status=pending_verification<?= $search ? '&q='.urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'pending_verification' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          Pending (<?= $counts['pending_verification'] ?>)
        </a>
        <a href="/CommunityDono/admin/users.php?status=suspended<?= $search ? '&q='.urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'suspended' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          Suspended (<?= $counts['suspended'] ?>)
        </a>
        <a href="/CommunityDono/admin/users.php?status=banned<?= $search ? '&q='.urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'banned' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          Banned (<?= $counts['banned'] ?>)
        </a>
      </div>

      <div class="admin-table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>User Name</th>
              <th>Email</th>
              <th>Location</th>
              <th>Role</th>
              <th>Status</th>
              <th>Registered</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="8" style="text-align: center; color: var(--color-text-muted); padding: 24px;">No users found matching your criteria.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td>#<?= (int)$u['id'] ?></td>
                  <td>
                    <a href="/CommunityDono/admin/user-detail.php?id=<?= (int)$u['id'] ?>" style="font-weight: 600; color: var(--color-primary); text-decoration: none;">
                      <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                    </a>
                  </td>
                  <td><?= e($u['email']) ?></td>
                  <td><?= e($u['city'] ?? 'Metro Manila') ?></td>
                  <td><span class="badge badge-info"><?= ucfirst($u['role']) ?></span></td>
                  <td><?= renderBadge($u['status']) ?></td>
                  <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                  <td style="white-space: nowrap;">
                    <a href="/CommunityDono/admin/user-detail.php?id=<?= (int)$u['id'] ?>" class="btn btn-outline" style="font-size: 11px; padding: 4px 8px;">
                      View Details
                    </a>
                    <?php if ($u['role'] !== 'superadmin' && (int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                      <?php if ($u['status'] === 'active'): ?>
                        <form action="/CommunityDono/api/admin/user-action.php" method="POST" style="display: inline;" onsubmit="return confirm('Suspend this user account?');">
                          <?= csrfField() ?>
                          <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                          <input type="hidden" name="action" value="suspend">
                          <button type="submit" class="btn btn-danger" style="font-size: 11px; padding: 4px 8px;">
                            Suspend
                          </button>
                        </form>
                      <?php elseif ($u['status'] === 'suspended' || $u['status'] === 'banned'): ?>
                        <form action="/CommunityDono/api/admin/user-action.php" method="POST" style="display: inline;">
                          <?= csrfField() ?>
                          <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                          <input type="hidden" name="action" value="activate">
                          <button type="submit" class="btn btn-success" style="font-size: 11px; padding: 4px 8px;">
                            Reactivate
                          </button>
                        </form>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
