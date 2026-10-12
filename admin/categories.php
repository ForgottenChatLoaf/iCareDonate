<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

// Handle add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? slugify($name));
    $desc = trim($_POST['description'] ?? '');

    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description, is_active) VALUES (?, ?, ?, 1)');
            $stmt->execute([$name, $slug, $desc ?: null]);
            setFlash('success', 'Category added successfully.');
        } catch (Exception $e) {
            error_log($e->getMessage());
            setFlash('error', 'Category name or slug already exists.');
        }
    }
    header('Location: /CommunityDono/admin/categories.php');
    exit;
}

$categories = $pdo->query('
    SELECT c.*, (SELECT COUNT(*) FROM donations WHERE category_id = c.id) AS donations_count
    FROM categories c
    ORDER BY c.sort_order ASC, c.name ASC
')->fetchAll();

$pageTitle = 'Manage Categories — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Donation Categories</h1>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; align-items: start;">
        
        <!-- Categories Table -->
        <div class="admin-table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Order</th>
                <th>Category Name</th>
                <th>Slug</th>
                <th>Listings</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $cat): ?>
                <tr>
                  <td><?= (int)$cat['sort_order'] ?></td>
                  <td><strong><?= e($cat['name']) ?></strong></td>
                  <td><code><?= e($cat['slug']) ?></code></td>
                  <td><?= (int)$cat['donations_count'] ?> items</td>
                  <td><?= $cat['is_active'] ? '<span class="badge badge-approved">Active</span>' : '<span class="badge badge-neutral">Inactive</span>' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Add Category Form -->
        <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
          <h2 style="font-size: 16px; font-weight: 700; color: var(--color-text); margin-bottom: 16px;">+ Add New Category</h2>
          
          <form action="/CommunityDono/admin/categories.php" method="POST" data-validate>
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">

            <div class="form-group" style="margin-bottom: 14px;">
              <label class="form-label" for="name">Category Name</label>
              <input type="text" id="name" name="name" class="form-input" required placeholder="e.g. Baby Care & Formula">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
              <label class="form-label" for="slug">URL Slug (Optional)</label>
              <input type="text" id="slug" name="slug" class="form-input" placeholder="e.g. baby-care-formula">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
              <label class="form-label" for="description">Description</label>
              <textarea id="description" name="description" class="form-input" rows="3" placeholder="Brief description of items in this category..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 10px;">
              Create Category
            </button>
          </form>
        </div>

      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
