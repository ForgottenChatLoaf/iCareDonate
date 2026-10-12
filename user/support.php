<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

$userId = (int)$_SESSION['user_id'];

// Handle new concern submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_concern') {
    verifyCsrf();
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $attachmentPath = null;

    if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/private/support/' . $userId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $orig = basename($_FILES['attachment']['name']);
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'docx'], true)) {
            $random = bin2hex(random_bytes(16)) . '.' . $ext;
            $dest = $uploadDir . '/' . $random;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $dest)) {
                $attachmentPath = 'uploads/private/support/' . $userId . '/' . $random;
            }
        }
    }

    if (!empty($subject) && !empty($message)) {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO support_tickets (user_id, subject, message, attachment_path, status, created_at)
                VALUES (?, ?, ?, ?, "open", NOW())
            ');
            $stmt->execute([$userId, $subject, $message, $attachmentPath]);
            setFlash('success', 'Your support inquiry has been submitted! Our team will respond shortly.');
        } catch (Exception $e) {
            error_log($e->getMessage());
            setFlash('error', 'Failed to submit inquiry.');
        }
    }
    header('Location: ' . APP_BASE . '/user/support.php');
    exit;
}

// Fetch user's concerns
$myConcerns = [];
try {
    $stmt = $pdo->prepare('SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    $myConcerns = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

// Fetch user's interview schedules
$myInterviews = [];
try {
    $stmt = $pdo->prepare('
        SELECT i.*, d.title AS donation_title
        FROM interviews i
        JOIN requests r ON i.request_id = r.id
        JOIN donations d ON r.donation_id = d.id
        WHERE i.requester_id = ?
        ORDER BY i.scheduled_date ASC, i.scheduled_time ASC
    ');
    $stmt->execute([$userId]);
    $myInterviews = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$pageTitle = 'Messages & Support — iCareDonate';
$extraCss = 'user.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="user-layout">
  <?php require_once __DIR__ . '/../includes/nav-user.php'; ?>

  <div class="user-main">
    <header class="user-topbar">
      <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0;">Messages &amp; Support</h1>
      </div>
      <div>
        <a href="<?= $base ?>/pages/faq.php" class="btn btn-outline" style="font-size: 13px; padding: 6px 14px;" target="_blank">
          Help &amp; FAQs ↗
        </a>
      </div>
    </header>

    <div class="user-content" style="max-width: 900px; margin: 0 auto;">
      <?php renderFlash(); ?>

      <!-- 1. Verification Interview Schedule Section (Scope 2.6) -->
      <?php if (!empty($myInterviews)): ?>
        <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 2px solid var(--color-primary); background: var(--color-tint-teal); margin-bottom: 28px;">
          <h2 style="font-size: 17px; font-weight: 700; color: var(--color-primary); margin: 0 0 12px;">
            <i data-lucide="calendar-clock" style="width:17px;height:17px;vertical-align:middle;margin-right:6px;"></i> Your Verification Interview Schedules
          </h2>
          <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php foreach ($myInterviews as $iv): ?>
              <div style="background: #FFF; padding: 14px 18px; border-radius: var(--radius-md); border: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                  <div style="font-weight: 700; font-size: 15px; color: var(--color-text);"><?= e($iv['donation_title']) ?></div>
                  <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 2px;">
                    Date: <strong><?= date('F j, Y', strtotime($iv['scheduled_date'])) ?></strong> at <strong><?= date('g:i A', strtotime($iv['scheduled_time'])) ?></strong> · Method: <?= ucfirst(str_replace('_', ' ', $iv['method'])) ?>
                  </div>
                  <?php if (!empty($iv['meeting_link_or_venue'])): ?>
                    <div style="font-size: 12.5px; color: var(--color-primary); margin-top: 4px;">
                      Venue / Link: <?= e($iv['meeting_link_or_venue']) ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div>
                  <span class="badge badge-info">Result: <?= ucfirst(str_replace('_', ' ', $iv['result'])) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- 2. Send a Concern / Support Request -->
      <div class="card" style="padding: 28px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 32px; box-shadow: var(--shadow-sm);">
        <h2 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0 0 16px;">
          Send a Support Concern or Inquiry
        </h2>

        <form action="<?= $base ?>/user/support.php" method="POST" enctype="multipart/form-data" data-validate>
          <?= csrfField() ?>
          <input type="hidden" name="action" value="send_concern">

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="subject">Subject</label>
            <input type="text" id="subject" name="subject" class="form-input" required placeholder="What do you need assistance with?">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="message">Detailed Message</label>
            <textarea id="message" name="message" class="form-input" rows="4" required placeholder="Describe your concern or question..."></textarea>
          </div>

          <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label">Optional Attachment (Image or PDF)</label>
            <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.docx" class="form-input">
          </div>

          <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 14px;">
            Submit Concern
          </button>
        </form>
      </div>

      <!-- 3. My Submitted Concerns & Admin Responses -->
      <div class="card" style="padding: 28px; border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
        <h2 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0 0 16px;">
          My Concerns History
        </h2>

        <?php if (empty($myConcerns)): ?>
          <p style="color: var(--color-text-muted); font-size: 14px; margin: 0;">You haven't submitted any support inquiries yet.</p>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 14px;">
            <?php foreach ($myConcerns as $c): ?>
              <div style="padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: #F8FAFC;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                  <div style="font-weight: 700; font-size: 15px; color: var(--color-text);"><?= e($c['subject']) ?></div>
                  <div><?= renderBadge($c['status']) ?></div>
                </div>

                <div style="font-size: 13.5px; color: var(--color-text); margin-bottom: 10px; white-space: pre-line;">
                  <?= e($c['message']) ?>
                </div>

                <?php if (!empty($c['admin_reply'])): ?>
                  <div style="background: var(--color-tint-teal); padding: 12px; border-radius: var(--radius-md); border: 1px solid rgba(31, 79, 163, 0.12); font-size: 13px; color: var(--color-text); margin-top: 8px;">
                    <strong>Admin Reply (<?= date('M d, Y', strtotime($c['replied_at'] ?? $c['updated_at'])) ?>):</strong>
                    <div style="margin-top: 4px;"><?= e($c['admin_reply']) ?></div>
                  </div>
                <?php endif; ?>

                <div style="font-size: 11.5px; color: var(--color-text-muted); margin-top: 8px;">
                  Filed on <?= date('M d, Y g:i A', strtotime($c['created_at'])) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
