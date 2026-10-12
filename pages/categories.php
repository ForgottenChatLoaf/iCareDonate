<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Fetch active categories with donation counts
$categories = [];
try {
    $pubWhere = publicDonationWhereClause('');
    $stmt = $pdo->query("
        SELECT c.*,
               (SELECT COUNT(*) FROM donations WHERE category_id = c.id AND {$pubWhere} AND (quantity_total - quantity_reserved - quantity_claimed) > 0) AS active_items_count
        FROM categories c
        WHERE c.is_active = 1
        ORDER BY c.sort_order ASC, c.name ASC
    ");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$pageTitle = 'Donation Categories — iCareDonate';
$extraCss = 'public.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav-public.php';
?>

<main style="padding: 48px 0 80px; min-height: 80vh;">
  <div class="container" style="max-width: 1040px; margin: 0 auto; padding: 0 20px;">
    
    <div style="text-align: center; margin-bottom: 48px;">
      <span class="badge" style="background: var(--sky); color: var(--cobalt); border: 1px solid var(--border); font-size: 12px; font-weight: 600; padding: 4px 14px; border-radius: 999px; display: inline-block; margin-bottom: 12px;">Resource Directory</span>
      <h1 style="font-size: clamp(2rem, 4vw, 42px); margin-bottom: 12px;">
        Browse by Donation Category
      </h1>
      <p class="lead" style="max-width: 600px; margin: 0 auto; font-size: 16px;">
        Discover available physical goods, equipment, and staples categorized for fast community assistance.
      </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(310px, 1fr)); gap: 24px;">
      <?php foreach ($categories as $cat): ?>
        <a href="/CommunityDono/pages/browse.php?category=<?= (int)$cat['id'] ?>" class="card" style="padding: 28px; text-decoration: none; border-radius: 20px; border: 1px solid var(--sky); background: var(--surface); display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s, box-shadow 0.2s; box-shadow: var(--shadow-sm);">
          
          <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 18px;">
              <div style="width: 52px; height: 52px; border-radius: 14px; background: var(--sky); color: var(--cobalt); display: flex; align-items: center; justify-content: center;">
                <i data-lucide="package" style="width:26px;height:26px;"></i>
              </div>
              <span class="badge" style="font-size: 11.5px; background: var(--sky); color: var(--cobalt); border: 1px solid var(--border); font-weight: 600; padding: 3px 10px; border-radius: 999px;">
                <?= (int)$cat['active_items_count'] ?> available
              </span>
            </div>

            <h2 style="font-size: 20px; font-weight: 700; color: var(--cobalt); margin: 0 0 10px;">
              <?= e($cat['name']) ?>
            </h2>

            <p style="font-size: 14.5px; color: var(--slate); line-height: 1.6; margin: 0 0 20px;">
              <?= e($cat['description'] ?? 'Essential community resources and materials.') ?>
            </p>
          </div>

          <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 16px; border-top: 1px solid var(--sky); font-size: 14px; font-weight: 600; color: var(--cobalt);">
            <span>View <?= e($cat['name']) ?> Listings</span>
            <span>&rarr;</span>
          </div>

        </a>
      <?php endforeach; ?>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
