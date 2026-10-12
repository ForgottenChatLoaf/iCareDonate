<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

$search   = trim($_GET['q'] ?? '');
$category = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : null;
$status   = trim($_GET['status'] ?? '');
$page     = max(1, (int)($_GET['p'] ?? 1));
$perPage  = 9;
$offset   = ($page - 1) * $perPage;

$categories = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll();

$where  = [publicDonationWhereClause('d'), '(d.quantity_total - d.quantity_reserved - d.quantity_claimed) > 0'];
$params = [];

if (!empty($search)) {
    $where[] = '(d.title LIKE ? OR d.description LIKE ? OR d.pickup_city LIKE ?)';
    $escaped = '%' . escapeLike($search) . '%';
    $params[] = $escaped;
    $params[] = $escaped;
    $params[] = $escaped;
}
if ($category) {
    $where[] = 'd.category_id = ?';
    $params[] = $category;
}

$whereSql   = implode(' AND ', $where);
$countStmt  = $pdo->prepare("SELECT COUNT(*) FROM donations d WHERE $whereSql");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $perPage));

$stmt = $pdo->prepare("
    SELECT d.id, d.title, d.quantity_total, d.quantity_reserved, d.quantity_claimed,
           d.unit, d.condition_type, d.expiration_date, d.pickup_city, d.created_at,
           c.name AS category_name,
           (SELECT file_path FROM donation_images WHERE donation_id = d.id
            ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS primary_image
    FROM donations d
    JOIN categories c ON d.category_id = c.id
    WHERE $whereSql
    ORDER BY d.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$donations = $stmt->fetchAll();

function condLabel(string $c): string {
    return match($c) {
        'new'      => 'New',
        'like_new' => 'Like New',
        'good'     => 'Good',
        'fair'     => 'Fair',
        'poor'     => 'Poor',
        default    => ucfirst($c),
    };
}

$pageTitle = 'Browse Donations';
$extraCss  = 'user.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="user-layout">
  <?php require_once __DIR__ . '/../includes/nav-user.php'; ?>

  <div class="user-main">
    <header class="user-topbar">
      <div>
        <h1 style="font-size:20px;font-weight:700;color:var(--color-text);margin:0;">Browse Donations</h1>
      </div>
      <div style="display:flex;gap:12px;align-items:center;">
        <a href="<?= $base ?>/user/offer-donation.php" class="btn btn-primary" style="font-size:13px;padding:8px 16px;">
          <i data-lucide="plus" style="width:15px;height:15px;"></i> Post Donation
        </a>
      </div>
    </header>

    <div class="user-content" style="padding:28px 32px;">

      <!-- Search bar -->
      <form method="GET" action="" id="browse-form">
        <div style="position:relative;margin-bottom:20px;">
          <i data-lucide="search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:17px;height:17px;color:var(--color-text-muted);pointer-events:none;"></i>
          <input
            type="text" name="q" id="search-q" value="<?= e($search) ?>"
            placeholder="Search donations (e.g., 'soup', 'jackets', 'supplies')..."
            class="form-input"
            style="padding-left:42px;height:44px;font-size:14px;border-radius:var(--radius-md);"
            autocomplete="off"
          >
          <input type="hidden" name="category" id="cat-hidden" value="<?= $category ?? '' ?>">
        </div>

        <!-- Category pills + Status row -->
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <button type="button" class="browse-pill <?= $category === null ? 'browse-pill--active' : '' ?>" onclick="setCat(null)">All</button>
            <?php foreach ($categories as $cat): ?>
              <button type="button" class="browse-pill <?= $category === (int)$cat['id'] ? 'browse-pill--active' : '' ?>" onclick="setCat(<?= (int)$cat['id'] ?>)"><?= e($cat['name']) ?></button>
            <?php endforeach; ?>
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <label style="font-size:13px;color:var(--color-text-muted);font-weight:500;">Status:</label>
            <select name="status" class="form-input form-select--sm" style="height:36px;font-size:13px;padding:0 34px 0 12px;min-width:110px;" onchange="document.getElementById('browse-form').submit()">
              <option value="">All</option>
              <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Available</option>
            </select>
          </div>
        </div>
      </form>

      <!-- Results count -->
      <div style="font-size:13px;color:var(--color-text-muted);margin-bottom:18px;">
        Showing <strong><?= count($donations) ?></strong> of <strong><?= $totalItems ?></strong> available donations
        <?php if (!empty($search)): ?> for "<strong><?= e($search) ?></strong>"<?php endif; ?>
      </div>

      <!-- Card grid -->
      <?php if (empty($donations)): ?>
        <div style="text-align:center;padding:72px 20px;background:#fff;border-radius:var(--radius-lg);border:1px solid var(--color-border);">
          <i data-lucide="search-x" style="width:48px;height:48px;color:var(--color-text-muted);margin-bottom:12px;"></i>
          <h3 style="font-size:18px;margin-bottom:8px;color:var(--color-text);">No donations found</h3>
          <p style="color:var(--color-text-muted);font-size:14px;margin-bottom:20px;">Try a different keyword or clear the filters.</p>
          <a href="<?= $base ?>/user/browse-donations.php" class="btn btn-outline">Clear Filters</a>
        </div>
      <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
          <?php foreach ($donations as $d):
            $available = (int)$d['quantity_total'] - (int)$d['quantity_reserved'] - (int)$d['quantity_claimed'];
            $expLabel  = !empty($d['expiration_date']) ? date('M Y', strtotime($d['expiration_date'])) : 'N/A';
          ?>
          <?php
            $cardImg = getDonationImage($d['primary_image'] ?? null, $d['category_name'] ?? '', (int)$d['id']);
          ?>
          <div class="browse-card">
            <div class="browse-card__img">
              <img src="<?= e($cardImg) ?>" alt="<?= e($d['title']) ?>">
              <span class="browse-card__cat"><?= e(strtoupper($d['category_name'])) ?></span>
            </div>
            <div class="browse-card__body">
              <h3 class="browse-card__title"><?= e($d['title']) ?></h3>
              <div class="browse-card__meta-row">
                <span><i data-lucide="package" style="width:12px;height:12px;vertical-align:middle;margin-right:3px;"></i><?= e(condLabel($d['condition_type'])) ?></span>
                <span><i data-lucide="map-pin" style="width:12px;height:12px;vertical-align:middle;margin-right:3px;"></i><?= e($d['pickup_city'] ?? 'Metro Manila') ?></span>
              </div>
              <div class="browse-card__avail">
                <span class="browse-card__qty"><?= $available ?></span>
                <span class="browse-card__unit"><?= e($d['unit'] ?? 'unit') ?> available</span>
              </div>
            </div>
            <div class="browse-card__footer">
              <a href="<?= $base ?>/pages/donation-details.php?id=<?= (int)$d['id'] ?>" class="browse-card__btn">View Details</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
          <div style="display:flex;justify-content:center;gap:8px;margin-top:36px;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>&category=<?= $category ?>"
                 class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>"
                 style="width:38px;height:38px;padding:0;display:inline-flex;align-items:center;justify-content:center;font-weight:600;"><?= $i ?></a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>

    </div>
  </div>
</div>

<style>
.browse-pill {
  padding: 6px 16px;
  border: 1px solid var(--color-border-strong);
  border-radius: var(--radius-full);
  background: var(--color-surface);
  color: var(--color-text-muted);
  font-size: 13.5px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
  font-family: var(--font-body);
  line-height: 1;
}
.browse-pill:hover { border-color: var(--color-primary); color: var(--color-primary); }
.browse-pill--active { background: var(--color-text); border-color: var(--color-text); color: #fff; font-weight: 600; }

.browse-card {
  background: #fff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-sm);
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.browse-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
.browse-card__img {
  position: relative;
  height: 190px;
  background: #F1F5F9;
  overflow: hidden;
}
.browse-card__img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.browse-card__cat {
  position: absolute;
  bottom: 10px; left: 10px;
  background: rgba(0,0,0,0.55);
  color: #fff;
  font-size: 10px; font-weight: 700; letter-spacing: 0.08em;
  padding: 3px 8px; border-radius: 4px;
  backdrop-filter: blur(4px);
}
.browse-card__body { padding: 16px 16px 8px; flex: 1; }
.browse-card__title { font-size: 15px; font-weight: 700; color: var(--color-text); margin-bottom: 8px; line-height: 1.35; }
.browse-card__meta { font-size: 13px; color: var(--color-text-muted); line-height: 1.7; }
.browse-card__meta-row { display: flex; gap: 10px; font-size: 12px; color: var(--color-text-muted); margin-bottom: 10px; flex-wrap: wrap; }
.browse-card__avail { display: flex; align-items: baseline; gap: 4px; padding: 6px 10px; background: var(--color-tint-teal); border-radius: var(--radius-sm); }
.browse-card__qty { font-size: 20px; font-weight: 800; color: var(--color-primary); line-height: 1; }
.browse-card__unit { font-size: 12px; color: var(--color-text-muted); }
.browse-card__footer { padding: 12px 16px 16px; border-top: 1px solid var(--color-border); }
.browse-card__btn {
  display: block; width: 100%; text-align: center;
  padding: 9px 0;
  border: 1px solid var(--color-border-strong);
  border-radius: var(--radius-md);
  color: var(--color-text); font-size: 13.5px; font-weight: 600;
  text-decoration: none;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
}
.browse-card__btn:hover { background: var(--color-text); border-color: var(--color-text); color: #fff; text-decoration: none; }
</style>
<script>
function setCat(id) {
  document.getElementById('cat-hidden').value = id === null ? '' : id;
  document.getElementById('browse-form').submit();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
