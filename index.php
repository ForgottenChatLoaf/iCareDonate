<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Fetch live categories
$categories = [];
try {
    $stmt = $pdo->query('SELECT id, name, slug, icon, description FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC');
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

// 2. Fetch live featured available donations (approved, with remaining quantity)
$featuredDonations = [];
try {
    $publicWhere = publicDonationWhereClause('d');
    $stmt = $pdo->query("
        SELECT d.id, d.title, d.description, d.quantity_total, d.quantity_reserved, d.quantity_claimed,
               d.unit, d.condition_type, d.pickup_city, d.pickup_barangay, d.created_at,
               c.name AS category_name,
               (SELECT file_path FROM donation_images WHERE donation_id = d.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS primary_image
        FROM donations d
        JOIN categories c ON d.category_id = c.id
        WHERE {$publicWhere} AND (d.quantity_total - d.quantity_reserved - d.quantity_claimed) > 0
        ORDER BY d.created_at DESC
        LIMIT 6
    ");
    $featuredDonations = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

// 3. Platform Impact statistics
$stats = [
    'donations_count' => 0,
    'claimed_items'   => 0,
    'community_users' => 0,
];
try {
    $statsWhere = publicDonationWhereClause('');
    $stats['donations_count'] = (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE {$statsWhere}")->fetchColumn();
    $stats['claimed_items']   = (int)$pdo->query('SELECT COALESCE(SUM(quantity_claimed), 0) FROM donations')->fetchColumn();
    $stats['community_users'] = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$pageTitle = 'iCareDonate — Give what you can. Help where it matters.';
$extraCss = 'public.css';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav-public.php';
?>

<main style="padding: 32px 0 72px;">
  <div class="container" style="max-width: 1040px; margin: 0 auto; padding: 0 20px;">
    
    <!-- Hero Section (Wireframe structure + Brand guide visuals) -->
    <section id="v-home">
      <div class="hero">
        <div>
          <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
            <span class="logo" style="display: inline-flex; align-items: center; gap: 8px;">
              <img src="<?= $base ?>/icaredonate-icon.svg" class="logo-icon" width="34" height="34" alt="iCareDonate">
              <span class="logo-wordmark" style="font-size: 1.45rem;">i<em>Care</em>Donate</span>
            </span>
            <span class="badge" style="background: var(--sky); color: var(--cobalt); border: 1px solid var(--border); font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 999px; display: inline-flex; align-items: center; gap: 5px;">
              <i data-lucide="shield-check" style="width:14px;height:14px;"></i> Verified Giving
            </span>
          </div>

          <h1>Give with care. Receive with dignity.</h1>
          <p class="lead">
            iCareDonate connects people who have items to share with individuals and communities who need them. Every donation and request is verified by an admin.
          </p>

          <div class="btns">
            <?php if (isLoggedIn()): ?>
              <a href="<?= $base ?>/user/offer-donation.php" class="btn-donate"><i data-lucide="gift" style="width:18px;height:18px;"></i> Donate now</a>
              <a href="<?= $base ?>/pages/browse.php" class="btn-request"><i data-lucide="search" style="width:18px;height:18px;"></i> Request help</a>
            <?php else: ?>
              <a href="<?= $base ?>/pages/login.php" class="btn-donate"><i data-lucide="gift" style="width:18px;height:18px;"></i> Donate now</a>
              <a href="<?= $base ?>/pages/login.php" class="btn-request"><i data-lucide="search" style="width:18px;height:18px;"></i> Request help</a>
            <?php endif; ?>
          </div>

          <div class="trust" style="color: var(--slate); font-size: 13.5px; margin-top: 24px; display: flex; align-items: center; gap: 18px; flex-wrap: wrap;">
            <span style="display: inline-flex; align-items: center; gap: 6px;">
              <i data-lucide="check-circle" style="width:16px;height:16px;color:var(--cobalt);"></i> Verified requests
            </span>
            <span style="display: inline-flex; align-items: center; gap: 6px;">
              <i data-lucide="heart" style="width:16px;height:16px;color:var(--coral);"></i> 100% Free platform
            </span>
            <span style="display: inline-flex; align-items: center; gap: 6px;">
              <i data-lucide="map-pin" style="width:16px;height:16px;color:var(--cobalt);"></i> Direct local pick-up
            </span>
          </div>
        </div>

        <div class="hframe" style="overflow: hidden; border-radius: 20px; border: 1px solid var(--sky); position: relative; min-height: 320px; box-shadow: var(--shadow-md);">
          <img src="https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=800&q=80" alt="Community members giving donation box" style="width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0;">
          <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(20,33,61,0.08) 0%, rgba(20,33,61,0.78) 100%); display: flex; flex-direction: column; justify-content: flex-end; padding: 24px; color: #fff;">
            <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(31,79,163,0.92); backdrop-filter: blur(6px); padding: 5px 14px; border-radius: 999px; font-size: 11px; font-weight: 700; width: fit-content; margin-bottom: 8px; letter-spacing: 0.05em; color: #fff;">
              <i data-lucide="heart" style="width: 12px; height: 12px; color: var(--amber);"></i> DIRECT COMMUNITY IMPACT
            </div>
            <p style="font-size: 14px; margin: 0; font-weight: 500; color: #F8FAFC; text-shadow: 0 1px 3px rgba(0,0,0,0.4); line-height: 1.45;">
              Bringing neighbors together through transparent, dignified giving across local communities.
            </p>
          </div>
        </div>
      </div>

      <!-- How it Works (3 Steps from Wireframe) -->
      <div id="home-steps" style="margin-bottom: 48px;">
        <div class="steps">
          <div class="step">
            <div class="n" style="background: var(--cobalt); color: #fff;">1</div>
            <h3>Post</h3>
            <p class="sm mut">Donors list an item. Requesters post what they need, with proof.</p>
          </div>
          <div class="step">
            <div class="n" style="background: var(--cobalt); color: #fff;">2</div>
            <h3>Admin verifies</h3>
            <p class="sm mut">Every post is checked before it goes live.</p>
          </div>
          <div class="step">
            <div class="n" style="background: var(--cobalt); color: #fff;">3</div>
            <h3>Match and claim</h3>
            <p class="sm mut">Matched items are claimed through pick-up.</p>
          </div>
        </div>
        <p style="margin-top: 18px; text-align: center;">
          <a href="<?= $base ?>/pages/how-it-works.php" class="btn btn-outline" style="font-size: 14px; padding: 10px 24px; border-radius: 999px;">
            See how it works &rarr;
          </a>
        </p>
      </div>
    </section>

    <!-- Categories & Needs -->
    <section style="margin-top: 48px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
          <h2 style="margin: 0 0 6px;">Community Giving Categories</h2>
          <p class="lead" style="margin: 0; font-size: 14.5px;">Essential aid areas where your contribution directly changes lives.</p>
        </div>
        <a href="<?= $base ?>/pages/categories.php" class="btn btn-outline" style="font-size: 13.5px; padding: 8px 18px; border-radius: 999px;">View all &rarr;</a>
      </div>

      <div class="g" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 48px; align-items: stretch;">
        <div class="card" style="padding: 24px; border-radius: 20px; border: 1px solid var(--sky); background: var(--surface); box-shadow: var(--shadow-sm); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; height: 100%;">
          <div style="height: 80px; border-radius: 14px; background: #FEF6E0; color: #BC7F08; display: grid; place-items: center; margin-bottom: 16px; flex-shrink: 0;">
            <i data-lucide="flame" style="width: 32px; height: 32px;"></i>
          </div>
          <h3 style="margin: 0 0 8px; font-size: 19px;">Emergency &amp; Disaster Relief</h3>
          <p class="sm mut" style="font-size: 13.5px; color: var(--slate); margin-bottom: 12px; line-height: 1.55; min-height: 42px;">Essential immediate sustenance, clean water, and shelter supplies for urgent relief.</p>
          <p style="font-size: 12.5px; color: var(--navy); margin-bottom: 20px;"><strong style="color: var(--cobalt);">Accepted:</strong> Food packs, canned goods, blankets</p>
          <a href="<?= $base ?>/pages/browse.php?category=1" class="btn btn-primary" style="margin-top: auto; width: 100%; justify-content: center; font-size: 13.5px; padding: 10px 16px; border-radius: 999px; text-decoration: none;">Donate / Request</a>
        </div>

        <div class="card" style="padding: 24px; border-radius: 20px; border: 1px solid var(--sky); background: var(--surface); box-shadow: var(--shadow-sm); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; height: 100%;">
          <div style="height: 80px; border-radius: 14px; background: #FAE9E7; color: var(--coral); display: grid; place-items: center; margin-bottom: 16px; flex-shrink: 0;">
            <i data-lucide="heart-pulse" style="width: 32px; height: 32px;"></i>
          </div>
          <h3 style="margin: 0 0 8px; font-size: 19px;">Healthcare &amp; Hygiene Kits</h3>
          <p class="sm mut" style="font-size: 13.5px; color: var(--slate); margin-bottom: 12px; line-height: 1.55; min-height: 42px;">Hygiene and basic wellness kits supporting vulnerable community members and seniors.</p>
          <p style="font-size: 12.5px; color: var(--navy); margin-bottom: 20px;"><strong style="color: var(--cobalt);">Accepted:</strong> Hygiene packs, first-aid, vitamins</p>
          <a href="<?= $base ?>/pages/browse.php?category=4" class="btn btn-primary" style="margin-top: auto; width: 100%; justify-content: center; font-size: 13.5px; padding: 10px 16px; border-radius: 999px; text-decoration: none;">Donate / Request</a>
        </div>

        <div class="card" style="padding: 24px; border-radius: 20px; border: 1px solid var(--sky); background: var(--surface); box-shadow: var(--shadow-sm); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; height: 100%;">
          <div style="height: 80px; border-radius: 14px; background: var(--sky); color: var(--cobalt); display: grid; place-items: center; margin-bottom: 16px; flex-shrink: 0;">
            <i data-lucide="book-open" style="width: 32px; height: 32px;"></i>
          </div>
          <h3 style="margin: 0 0 8px; font-size: 19px;">Education &amp; Youth Support</h3>
          <p class="sm mut" style="font-size: 13.5px; color: var(--slate); margin-bottom: 12px; line-height: 1.55; min-height: 42px;">Learning materials, books, and backpacks empowering young students in local schools.</p>
          <p style="font-size: 12.5px; color: var(--navy); margin-bottom: 20px;"><strong style="color: var(--cobalt);">Accepted:</strong> School bags, notebooks, tablets</p>
          <a href="<?= $base ?>/pages/browse.php?category=2" class="btn btn-primary" style="margin-top: auto; width: 100%; justify-content: center; font-size: 13.5px; padding: 10px 16px; border-radius: 999px; text-decoration: none;">Donate / Request</a>
        </div>
      </div>
    </section>

    <!-- Platform Impact Statistics (Brand Navy Band with Amber Numbers) -->
    <section style="background: var(--navy); border-radius: 20px; padding: 40px 32px; margin-bottom: 48px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; text-align: center; color: #fff; box-shadow: var(--shadow-md);">
      <div>
        <div style="font-family: var(--font-heading); font-size: 44px; line-height: 1; color: var(--amber); margin-bottom: 8px; font-weight: 700;"><?= number_format($stats['donations_count']) ?>+</div>
        <div style="font-size: 14px; color: var(--sky); font-weight: 500;">Approved Donations</div>
      </div>
      <div>
        <div style="font-family: var(--font-heading); font-size: 44px; line-height: 1; color: var(--amber); margin-bottom: 8px; font-weight: 700;"><?= number_format($stats['claimed_items']) ?>+</div>
        <div style="font-size: 14px; color: var(--sky); font-weight: 500;">Items Claimed &amp; Received</div>
      </div>
      <div>
        <div style="font-family: var(--font-heading); font-size: 44px; line-height: 1; color: var(--amber); margin-bottom: 8px; font-weight: 700;"><?= number_format($stats['community_users']) ?>+</div>
        <div style="font-size: 14px; color: var(--sky); font-weight: 500;">Active Community Members</div>
      </div>
    </section>

    <!-- Available Donations -->
    <?php if (!empty($featuredDonations)): ?>
      <section style="margin-top: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
          <div>
            <h2 style="margin: 0 0 6px;">Recently Approved Donations</h2>
            <p class="lead" style="margin: 0; font-size: 14.5px;">Verified supplies available for community request and claim.</p>
          </div>
          <a href="<?= $base ?>/pages/browse.php" class="btn btn-outline" style="font-size: 13.5px; padding: 8px 18px; border-radius: 999px;">Browse all &rarr;</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 22px; margin-bottom: 48px;">
          <?php foreach ($featuredDonations as $d): ?>
            <?php 
              $available = (int)$d['quantity_total'] - (int)$d['quantity_reserved'] - (int)$d['quantity_claimed'];
              $itemImg = getDonationImage($d['primary_image'] ?? null, $d['category_name'] ?? '', (int)$d['id']);
            ?>
            <div class="card" style="border-radius: 20px; border: 1px solid var(--sky); background: var(--surface); overflow: hidden; display: flex; flex-direction: column; box-shadow: var(--shadow-sm); padding: 0; transition: transform 0.2s, box-shadow 0.2s;">
              <div style="height: 170px; position: relative; overflow: hidden; background: var(--surface-soft);">
                <img src="<?= e($itemImg) ?>" alt="<?= e($d['title']) ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                <span style="position: absolute; top: 12px; left: 12px; background: rgba(20, 33, 61, 0.82); color: #fff; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 999px; backdrop-filter: blur(4px);">
                  <?= e($d['category_name']) ?>
                </span>
                <span style="position: absolute; bottom: 12px; right: 12px; background: var(--cobalt); color: #fff; font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 999px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
                  <?= $available ?> left
                </span>
              </div>
              <div style="padding: 20px 22px 22px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                  <h3 style="font-size: 18px; margin: 0 0 8px; color: var(--cobalt);">
                    <?= e($d['title']) ?>
                  </h3>
                  <p class="sm mut" style="font-size: 13.5px; color: var(--slate); margin: 0 0 16px;">
                    <?= e($d['category_name']) ?> &bull; Pick-up only &bull; <?= e($d['pickup_city'] ?? 'Metro Manila') ?>
                  </p>
                </div>
                <a href="<?= $base ?>/pages/donation-details.php?id=<?= (int)$d['id'] ?>" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 13.5px; padding: 10px 16px; text-decoration: none; display: inline-flex; border-radius: 999px;">
                  View details &amp; request
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

