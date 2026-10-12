<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$adminId = (int)$_SESSION['user_id'];

// Handle POST actions: add, edit, delete, toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = trim($_POST['action'] ?? '');

    try {
        if ($action === 'add') {
            $question = trim($_POST['question'] ?? '');
            $answer = trim($_POST['answer'] ?? '');
            $category = trim($_POST['category'] ?? 'General');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            if (empty($question) || empty($answer)) {
                setFlash('error', 'Question and Answer cannot be empty.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO faqs (question, answer, category, sort_order, is_active) VALUES (?, ?, ?, ?, 1)');
                $stmt->execute([$question, $answer, $category, $sortOrder]);
                logAudit($pdo, $adminId, 'admin', 'FAQ_CREATED', 'faq', (int)$pdo->lastInsertId(), null, ['question' => $question], 'Added new FAQ');
                setFlash('success', 'FAQ added successfully.');
            }
        } elseif ($action === 'edit') {
            $id = (int)($_POST['faq_id'] ?? 0);
            $question = trim($_POST['question'] ?? '');
            $answer = trim($_POST['answer'] ?? '');
            $category = trim($_POST['category'] ?? 'General');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($id <= 0 || empty($question) || empty($answer)) {
                setFlash('error', 'Invalid FAQ data.');
            } else {
                $stmt = $pdo->prepare('UPDATE faqs SET question = ?, answer = ?, category = ?, sort_order = ?, is_active = ? WHERE id = ?');
                $stmt->execute([$question, $answer, $category, $sortOrder, $isActive, $id]);
                logAudit($pdo, $adminId, 'admin', 'FAQ_UPDATED', 'faq', $id, null, ['question' => $question], 'Updated FAQ');
                setFlash('success', 'FAQ updated successfully.');
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['faq_id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare('DELETE FROM faqs WHERE id = ?');
                $stmt->execute([$id]);
                logAudit($pdo, $adminId, 'admin', 'FAQ_DELETED', 'faq', $id, null, null, 'Deleted FAQ');
                setFlash('success', 'FAQ deleted successfully.');
            }
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        setFlash('error', 'An error occurred while saving the FAQ.');
    }

    header('Location: /CommunityDono/admin/faqs.php');
    exit;
}

$faqs = $pdo->query('SELECT * FROM faqs ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = 'FAQ Management — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Frequently Asked Questions (FAQs)</h1>
      </div>
      <div>
        <button type="button" onclick="document.getElementById('add-faq-modal').style.display = 'grid'" class="btn btn-primary" style="font-size: 12px; padding: 5px 14px;">
          + Add New FAQ
        </button>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <div class="admin-table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width: 60px;">Order</th>
              <th>Question</th>
              <th>Category</th>
              <th>Answer Preview</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($faqs)): ?>
              <tr>
                <td colspan="6" style="text-align: center; color: var(--color-text-muted); padding: 24px;">No FAQs created yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($faqs as $f): ?>
                <tr>
                  <td><?= (int)$f['sort_order'] ?></td>
                  <td><strong><?= e($f['question']) ?></strong></td>
                  <td><span class="badge badge-info"><?= e($f['category'] ?? 'General') ?></span></td>
                  <td style="font-size: 12px; color: var(--color-text-muted); max-width: 320px;">
                    <?= e(mb_strimwidth($f['answer'], 0, 80, '...')) ?>
                  </td>
                  <td>
                    <?php if ($f['is_active']): ?>
                      <span class="badge badge-success">Active</span>
                    <?php else: ?>
                      <span class="badge badge-neutral">Hidden</span>
                    <?php endif; ?>
                  </td>
                  <td style="white-space: nowrap;">
                    <button type="button" onclick='openEditFaq(<?= json_encode($f) ?>)' class="btn btn-outline" style="font-size: 11px; padding: 4px 8px;">
                      Edit
                    </button>
                    <form action="/CommunityDono/admin/faqs.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="faq_id" value="<?= (int)$f['id'] ?>">
                      <button type="submit" class="btn btn-danger" style="font-size: 11px; padding: 4px 8px;">
                        Delete
                      </button>
                    </form>
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

<!-- Add FAQ Modal -->
<div id="add-faq-modal" class="modal" style="display: none;">
  <div class="modal__overlay" onclick="document.getElementById('add-faq-modal').style.display='none'"></div>
  <div class="modal__card" style="max-width: 540px; padding: 24px;">
    <h2 style="font-size: 17px; font-weight: 700; margin-bottom: 16px;">Add New FAQ</h2>
    <form action="/CommunityDono/admin/faqs.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add">

      <div style="margin-bottom: 12px;">
        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Question (required):</label>
        <input type="text" name="question" class="form-input" placeholder="e.g. How do I verify my account?" required>
      </div>

      <div style="margin-bottom: 12px;">
        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Category:</label>
        <select name="category" class="form-input">
          <option value="General">General</option>
          <option value="Donating">Donating</option>
          <option value="Requesting">Requesting</option>
          <option value="Verification">Verification</option>
          <option value="Logistics">Logistics</option>
        </select>
      </div>

      <div style="margin-bottom: 12px;">
        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Sort Order:</label>
        <input type="number" name="sort_order" value="1" class="form-input" style="width: 100px;">
      </div>

      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Answer (required):</label>
        <textarea name="answer" rows="4" class="form-input" placeholder="Detailed explanation..." required></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 8px;">
        <button type="button" onclick="document.getElementById('add-faq-modal').style.display='none'" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary">Save FAQ</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit FAQ Modal -->
<div id="edit-faq-modal" class="modal" style="display: none;">
  <div class="modal__overlay" onclick="document.getElementById('edit-faq-modal').style.display='none'"></div>
  <div class="modal__card" style="max-width: 540px; padding: 24px;">
    <h2 style="font-size: 17px; font-weight: 700; margin-bottom: 16px;">Edit FAQ</h2>
    <form action="/CommunityDono/admin/faqs.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="faq_id" id="edit-faq-id">

      <div style="margin-bottom: 12px;">
        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Question:</label>
        <input type="text" name="question" id="edit-faq-question" class="form-input" required>
      </div>

      <div style="margin-bottom: 12px;">
        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Category:</label>
        <select name="category" id="edit-faq-category" class="form-input">
          <option value="General">General</option>
          <option value="Donating">Donating</option>
          <option value="Requesting">Requesting</option>
          <option value="Verification">Verification</option>
          <option value="Logistics">Logistics</option>
        </select>
      </div>

      <div style="display: flex; gap: 16px; margin-bottom: 12px; align-items: center;">
        <div>
          <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Sort Order:</label>
          <input type="number" name="sort_order" id="edit-faq-order" class="form-input" style="width: 100px;">
        </div>
        <div style="margin-top: 20px;">
          <label style="font-size: 13px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
            <input type="checkbox" name="is_active" id="edit-faq-active" value="1">
            Display on Public FAQ Page
          </label>
        </div>
      </div>

      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Answer:</label>
        <textarea name="answer" id="edit-faq-answer" rows="4" class="form-input" required></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 8px;">
        <button type="button" onclick="document.getElementById('edit-faq-modal').style.display='none'" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditFaq(faq) {
  document.getElementById('edit-faq-id').value = faq.id;
  document.getElementById('edit-faq-question').value = faq.question;
  document.getElementById('edit-faq-category').value = faq.category || 'General';
  document.getElementById('edit-faq-order').value = faq.sort_order;
  document.getElementById('edit-faq-answer').value = faq.answer;
  document.getElementById('edit-faq-active').checked = faq.is_active == 1;
  document.getElementById('edit-faq-modal').style.display = 'grid';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
