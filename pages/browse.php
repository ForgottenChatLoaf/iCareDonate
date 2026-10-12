<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Search and filter params
$search   = trim($_GET['q'] ?? '');
$category = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : null;
$cond     = trim($_GET['condition'] ?? '');
$page     = max(1, (int)($_GET['p'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

// Fetch categories for filter dropdown
$categories = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll();

// Build query using centralized explicit public visibility rule
$where = [publicDonationWhereClause('d'), '(d.quantity_total - d.quantity_reserved - d.quantity_claimed) > 0'];
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

if (!empty($cond) && in_array($cond, ['new', 'like_new', 'good', 'fair', 'poor'], true)) {
    $where[] = 'd.condition_type = ?';
    $params[] = $cond;
}

$whereSql = implode(' AND ', $where);

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM donations d WHERE $whereSql");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $perPage));

// Fetch items
$query = "
    SELECT d.id, d.title, d.description, d.quantity_total, d.quantity_reserved, d.quantity_claimed,
           d.unit, d.condition_type, d.pickup_city, d.pickup_barangay, d.created_at,
           c.name AS category_name,
           (SELECT file_path FROM donation_images WHERE donation_id = d.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS primary_image
    FROM donations d
    JOIN categories c ON d.category_id = c.id
    WHERE $whereSql
    ORDER BY d.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$donations = $stmt->fetchAll();

$pageTitle = 'Browse Available Donations — iCareDonate';
$extraCss = 'public.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav-public.php';
?>

<main style="padding: 40px 0 60px; min-height: 80vh;">
  <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
    
    <div style="margin-bottom: 32px;">
      <h1 style="margin-bottom: 8px;">Browse Available Items</h1>
      <p class="lead" style="margin-bottom: 0;">Discover verified resources offered by caring neighbors and community organizations.</p>
    </div>

    <!-- Search & Filters Card -->
    <div class="card" style="padding: 26px; margin-bottom: 36px; border-radius: 20px; border: 1px solid var(--sky); box-shadow: var(--shadow-sm);">
      <form method="GET" action="/CommunityDono/pages/browse.php" style="display: grid; grid-template-columns: 2fr 1.2fr 1fr auto; gap: 14px; align-items: end;">
        
        <div class="form-group">
          <label class="form-label" for="search-input">Search Keywords</label>
          <input type="text" id="search-input" name="q" value="<?= e($search) ?>" class="form-input" placeholder="Search by item name, description, city...">
        </div>

        <div class="form-group">
          <label class="form-label" for="cat-select">Category</label>
          <select id="cat-select" name="category" class="form-input">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= $category === (int)$cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="cond-select">Condition</label>
          <select id="cond-select" name="condition" class="form-input">
            <option value="">Any Condition</option>
            <option value="new" <?= $cond === 'new' ? 'selected' : '' ?>>Brand New</option>
            <option value="like_new" <?= $cond === 'like_new' ? 'selected' : '' ?>>Like New</option>
            <option value="good" <?= $cond === 'good' ? 'selected' : '' ?>>Good Condition</option>
            <option value="fair" <?= $cond === 'fair' ? 'selected' : '' ?>>Fair</option>
          </select>
        </div>

        <div style="display: flex; gap: 8px;">
          <button type="submit" class="btn btn-primary" style="height: 44px; padding: 0 22px; border-radius: 999px;">
            Filter
          </button>
          <?php if (!empty($search) || $category || !empty($cond)): ?>
            <a href="/CommunityDono/pages/browse.php" class="btn btn-outline" style="height: 44px; padding: 0 18px; display: inline-flex; align-items: center; border-radius: 999px;">
              Reset
            </a>
          <?php endif; ?>
        </div>

      </form>
    </div>

    <!-- Results Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
      <div style="font-size: 14.5px; color: var(--slate);">
        Showing <strong><?= count($donations) ?></strong> of <strong><?= $totalItems ?></strong> available items
      </div>
    </div>

    <!-- Items Grid -->
    <?php if (empty($donations)): ?>
      <div class="card" style="padding: 60px 20px; text-align: center; border-radius: 20px; border: 1px solid var(--sky);">
        <div style="margin-bottom: 14px; color: var(--slate);"><i data-lucide="search-x" style="width:52px;height:52px;"></i></div>
        <h3 style="font-size: 22px; color: var(--cobalt); margin-bottom: 8px;">No matches found</h3>
        <p style="color: var(--slate); font-size: 15px; margin-bottom: 24px;">Try another keyword or adjust your category filter.</p>
        <a href="/CommunityDono/pages/browse.php" class="btn btn-outline" style="border-radius: 999px; padding: 10px 24px;">Clear All Filters</a>
      </div>
    <?php else: ?>
      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 24px;">
        <?php foreach ($donations as $d):
            $available = (int)$d['quantity_total'] - (int)$d['quantity_reserved'] - (int)$d['quantity_claimed'];
            $itemImg = getDonationImage($d['primary_image'] ?? null, $d['category_name'] ?? '', (int)$d['id']);
          ?>
          <div class="card" style="border-radius: 20px; border: 1px solid var(--sky); overflow: hidden; display: flex; flex-direction: column; box-shadow: var(--shadow-sm); padding: 0; transition: transform 0.2s, box-shadow 0.2s;">

            <div style="height: 190px; position: relative; overflow: hidden; background: var(--surface-soft);">
              <img src="<?= e($itemImg) ?>" alt="<?= e($d['title']) ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;">
              <span style="position: absolute; top: 12px; left: 12px; background: rgba(20, 33, 61, 0.82); color: #fff; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 999px; backdrop-filter: blur(4px);">
                <?= e($d['category_name']) ?>
              </span>
              <span style="position: absolute; bottom: 12px; right: 12px; background: var(--cobalt); color: #fff; font-size: 11.5px; font-weight: 700; padding: 4px 12px; border-radius: 999px;">
                <?= $available ?> left
              </span>
            </div>

            <div style="padding: 20px 22px 18px; flex: 1; display: flex; flex-direction: column;">
              <h3 style="font-size: 17px; font-weight: 700; color: var(--cobalt); margin-bottom: 8px; line-height: 1.35;">
                <a href="/CommunityDono/pages/donation-details.php?id=<?= (int)$d['id'] ?>" style="color: inherit; text-decoration: none;">
                  <?= e($d['title']) ?>
                </a>
              </h3>

              <p style="font-size: 13.5px; color: var(--slate); line-height: 1.55; margin-bottom: 16px; flex: 1;">
                <?= e(truncateWords($d['description'] ?? '', 14)) ?>
              </p>

              <div style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--slate); margin-bottom: 16px;">
                <i data-lucide="map-pin" style="width:14px;height:14px;color:var(--cobalt);flex-shrink:0;"></i>
                <?= e($d['pickup_city'] ?? 'Metro Manila') ?>
                &nbsp;·&nbsp;
                <?= e(ucwords(str_replace('_', ' ', $d['condition_type'] ?? ''))) ?>
              </div>

              <a href="/CommunityDono/pages/donation-details.php?id=<?= (int)$d['id'] ?>" class="btn btn-outline" style="width: 100%; justify-content: center; padding: 10px 16px; font-size: 13.5px; border-radius: 999px;">
                View &amp; Request
              </a>
            </div>

          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 8px; margin-top: 40px;">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>&category=<?= $category ?>&condition=<?= urlencode($cond) ?>"
               class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>"
               style="width: 40px; height: 40px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-weight: 600;">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
