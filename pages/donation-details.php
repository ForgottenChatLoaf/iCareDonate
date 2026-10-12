<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// Fetch donation details
$publicWhere = publicDonationWhereClause('d');
$stmt = $pdo->prepare("
    SELECT d.*, c.name AS category_name,
           u.first_name, u.last_name, u.city AS donor_city, u.barangay AS donor_barangay
    FROM donations d
    JOIN categories c ON d.category_id = c.id
    JOIN users u ON d.donor_id = u.id
    WHERE d.id = ? AND {$publicWhere}
    LIMIT 1
");
$stmt->execute([$id]);
$donation = $stmt->fetch();

if (!$donation) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// Fetch images
$imgStmt = $pdo->prepare('SELECT file_path, file_name, is_primary FROM donation_images WHERE donation_id = ? ORDER BY is_primary DESC, sort_order ASC');
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll();

$available = (int)$donation['quantity_total'] - (int)$donation['quantity_reserved'] - (int)$donation['quantity_claimed'];

$pageTitle = e($donation['title']) . ' — iCareDonate';
$extraCss = 'public.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav-public.php';
?>

<main style="padding: 40px 0 60px; background: var(--color-bg); min-height: 80vh;">
  <div class="container" style="max-width: 1040px; margin: 0 auto; padding: 0 20px;">
    
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
      <a href="/CommunityDono/pages/browse.php" style="color: var(--color-primary); font-size: 14px; text-decoration: none; font-weight: 500;">
        &larr; Back to Browse Listings
      </a>
      
      <?php if (isLoggedIn()): ?>
        <button type="button" data-modal-open="report-modal" style="background: none; border: none; color: var(--color-text-muted); font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 4px;">
          <i data-lucide="flag" style="width:14px;height:14px;"></i> Report this listing
        </button>
      <?php endif; ?>
    </div>

    <?php renderFlash(); ?>

    <div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 36px; align-items: start;">
      
      <!-- Left: Images Gallery & Description -->
      <div>
        <div class="card" style="padding: 0; overflow: hidden; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 24px;">
          <div style="height: 360px; background: #F8FAFC; display: flex; align-items: center; justify-content: center; position: relative;">
            <?php if (!empty($images)): ?>
              <img id="main-image-view" src="/CommunityDono/<?= e($images[0]['file_path']) ?>" alt="<?= e($donation['title']) ?>" style="width: 100%; height: 100%; object-fit: contain;">
            <?php else: ?>
              <div style="color:#CBD5E1;"><i data-lucide="gift" style="width:64px;height:64px;"></i></div>
            <?php endif; ?>
          </div>

          <?php if (count($images) > 1): ?>
            <div style="display: flex; gap: 8px; padding: 12px; background: #FFFFFF; border-top: 1px solid var(--color-border); overflow-x: auto;">
              <?php foreach ($images as $img): ?>
                <img src="/CommunityDono/<?= e($img['file_path']) ?>" alt="Thumbnail"
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; cursor: pointer; border: 2px solid transparent;"
                     onclick="document.getElementById('main-image-view').src=this.src;">
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="card" style="padding: 28px; border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
          <h2 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin-bottom: 14px;">Item Description</h2>
          <div style="font-size: 15px; color: var(--color-text); line-height: 1.7; white-space: pre-line;">
            <?= e($donation['description'] ?? 'No detailed description provided.') ?>
          </div>
        </div>
      </div>

      <!-- Right: Summary & Request Box -->
      <div>
        <div class="card" style="padding: 32px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); box-shadow: var(--shadow-md); position: sticky; top: 90px;">
          
          <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
            <span class="badge badge-info" style="font-size: 12px;"><?= e($donation['category_name']) ?></span>
            <span class="badge badge-approved" style="font-size: 12px;"><i data-lucide="check" style="width:11px;height:11px;"></i> Verified by Admin</span>
          </div>

          <h1 style="font-family: var(--font-brand); font-size: 28px; color: var(--color-text); line-height: 1.2; margin-bottom: 16px;">
            <?= e($donation['title']) ?>
          </h1>

          <div style="background: var(--color-tint-teal); padding: 16px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; font-weight: 600;">Available Quantity</div>
              <div style="font-size: 24px; font-weight: 700; color: var(--color-primary);">
                <?= $available ?> <span style="font-size: 15px; font-weight: 500;"><?= e($donation['unit']) ?></span>
              </div>
            </div>
            <div style="text-align: right;">
              <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; font-weight: 600;">Condition</div>
              <div style="font-size: 15px; font-weight: 600; color: var(--color-text);">
                <?= ucfirst(str_replace('_', ' ', $donation['condition_type'])) ?>
              </div>
            </div>
          </div>

          <!-- Logistics details -->
          <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px; margin-bottom: 28px; color: var(--color-text);">
            <div style="display: flex; gap: 10px;">
              <i data-lucide="map-pin" style="width:16px;height:16px;color:var(--color-primary);flex-shrink:0;"></i>
              <div>
                <strong>Location:</strong> <?= e($donation['pickup_barangay'] ? $donation['pickup_barangay'] . ', ' : '') ?><?= e($donation['pickup_city'] ?? 'Metro Manila') ?>
              </div>
            </div>

            <div style="display: flex; gap: 10px;">
              <i data-lucide="truck" style="width:16px;height:16px;color:var(--color-primary);flex-shrink:0;"></i>
              <div>
                <strong>Handover:</strong> <?= ucfirst($donation['pickup_method']) ?>
              </div>
            </div>

            <div style="display: flex; gap: 10px;">
              <i data-lucide="calendar" style="width:16px;height:16px;color:var(--color-primary);flex-shrink:0;"></i>
              <div>
                <strong>Posted:</strong> <?= date('F j, Y', strtotime($donation['created_at'])) ?>
              </div>
            </div>
          </div>

          <!-- Action CTA -->
          <?php if ($available <= 0): ?>
            <button disabled class="btn btn-outline" style="width: 100%; justify-content: center; padding: 14px; opacity: 0.6;">
              Fully Allocated
            </button>
          <?php elseif (isLoggedIn()): ?>
            <a href="/CommunityDono/user/request-donation.php?id=<?= (int)$donation['id'] ?>" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; font-size: 16px;">
              Request This Item
            </a>
          <?php else: ?>
            <a href="/CommunityDono/pages/login.php?redirect=<?= urlencode('/CommunityDono/user/request-donation.php?id=' . $donation['id']) ?>" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; font-size: 16px;">
              Log In to Request Item
            </a>
            <div style="text-align: center; font-size: 13px; color: var(--color-text-muted); margin-top: 10px;">
              Need an account? <a href="/CommunityDono/pages/signup.php" style="color: var(--color-primary);">Sign up free</a>
            </div>
          <?php endif; ?>

        </div>
      </div>

    </div>
  </div>
</main>

<!-- Problem Report Modal -->
<?php if (isLoggedIn()): ?>
  <div id="report-modal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; padding: 20px;">
    <div class="modal-panel card" style="max-width: 480px; width: 100%; padding: 28px; border-radius: var(--radius-lg); background: #FFFFFF; box-shadow: var(--shadow-lg);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Report this Donation Listing</h3>
        <button type="button" data-modal-close style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--color-text-muted);">&times;</button>
      </div>

      <form action="/CommunityDono/api/reports/create.php" method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="target_type" value="donation">
        <input type="hidden" name="target_id" value="<?= (int)$donation['id'] ?>">

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="report-reason">Reason for Reporting</label>
          <select id="report-reason" name="reason" class="form-input" required>
            <option value="inappropriate_item">Prohibited or inappropriate item</option>
            <option value="misleading_info">Misleading description or fake photos</option>
            <option value="cash_solicitation">Attempting to sell or solicit cash</option>
            <option value="expired_goods">Expired food or damaged/unsafe goods</option>
            <option value="other">Other policy violation</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
          <label class="form-label" for="report-desc">Additional Details</label>
          <textarea id="report-desc" name="description" class="form-input" rows="3" placeholder="Please provide any helpful context for the moderation team..."></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" data-modal-close class="btn btn-outline">Cancel</button>
          <button type="submit" class="btn btn-danger">Submit Report</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
