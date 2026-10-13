<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$adminId = (int)$_SESSION['user_id'];

// Handle reply or status change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $inquiryId = (int)($_POST['inquiry_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    try {
        if ($action === 'reply') {
            $reply = trim($_POST['admin_reply'] ?? '');
            if ($inquiryId > 0 && !empty($reply)) {
                $stmt = $pdo->prepare('UPDATE support_tickets SET admin_reply = ?, replied_at = NOW(), assigned_to = ?, status = "resolved" WHERE id = ?');
                $stmt->execute([$reply, $adminId, $inquiryId]);
                logAudit($pdo, $adminId, 'admin', 'INQUIRY_REPLIED', 'support_ticket', $inquiryId, null, ['reply' => $reply], 'Responded to public inquiry');
                setFlash('success', 'Reply recorded and inquiry resolved.');
            }
        } elseif ($action === 'status') {
            $newStatus = trim($_POST['status'] ?? 'open');
            if ($inquiryId > 0 && in_array($newStatus, ['open', 'in_progress', 'resolved', 'closed'], true)) {
                $stmt = $pdo->prepare('UPDATE support_tickets SET status = ?, assigned_to = ? WHERE id = ?');
                $stmt->execute([$newStatus, $adminId, $inquiryId]);
                logAudit($pdo, $adminId, 'admin', 'INQUIRY_STATUS_CHANGED', 'support_ticket', $inquiryId, null, ['status' => $newStatus], 'Updated inquiry status');
                setFlash('success', 'Inquiry status updated.');
            }
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        setFlash('error', 'Failed to update inquiry: ' . $e->getMessage());
    }

    header('Location: ' . APP_BASE . '/admin/inquiries.php');
    exit;
}

$statusFilter = trim($_GET['status'] ?? 'all');
$where = '1=1';
$params = [];

if ($statusFilter !== 'all' && in_array($statusFilter, ['open', 'in_progress', 'resolved', 'closed'], true)) {
    $where .= ' AND st.status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("
  SELECT st.*, u.first_name, u.last_name, admin.first_name AS admin_fn
  FROM support_tickets st
  LEFT JOIN users u ON st.user_id = u.id
  LEFT JOIN users admin ON st.assigned_to = admin.id
  WHERE $where
  ORDER BY st.created_at DESC
");
$stmt->execute($params);
$inquiries = $stmt->fetchAll();

$counts = [
    'all' => (int)$pdo->query('SELECT COUNT(*) FROM support_tickets')->fetchColumn(),
    'open' => (int)$pdo->query('SELECT COUNT(*) FROM support_tickets WHERE status = "open"')->fetchColumn(),
    'in_progress' => (int)$pdo->query('SELECT COUNT(*) FROM support_tickets WHERE status = "in_progress"')->fetchColumn(),
    'resolved' => (int)$pdo->query('SELECT COUNT(*) FROM support_tickets WHERE status = "resolved"')->fetchColumn(),
];

$pageTitle = 'Contact Inquiries Inbox — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Contact & Public Inquiries Inbox</h1>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <!-- Filter tabs -->
      <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
        <a href="<?= $base ?>/admin/inquiries.php?status=all" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          All Messages (<?= $counts['all'] ?>)
        </a>
        <a href="<?= $base ?>/admin/inquiries.php?status=open" class="btn <?= $statusFilter === 'open' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          New / Open (<?= $counts['open'] ?>)
        </a>
        <a href="<?= $base ?>/admin/inquiries.php?status=in_progress" class="btn <?= $statusFilter === 'in_progress' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          In Progress (<?= $counts['in_progress'] ?>)
        </a>
        <a href="<?= $base ?>/admin/inquiries.php?status=resolved" class="btn <?= $statusFilter === 'resolved' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 12px; padding: 5px 12px; border-radius: 99px;">
          Resolved (<?= $counts['resolved'] ?>)
        </a>
      </div>

      <div class="admin-table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>From</th>
              <th>Email</th>
              <th>Subject</th>
              <th>Status</th>
              <th>Received</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($inquiries)): ?>
              <tr>
                <td colspan="7" style="text-align: center; color: var(--color-text-muted); padding: 24px;">No inquiries found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($inquiries as $inq): ?>
                <tr>
                  <td>#<?= (int)$inq['id'] ?></td>
                  <td>
                    <strong><?= e($inq['first_name'] ? ($inq['first_name'] . ' ' . $inq['last_name']) : 'Public Visitor') ?></strong>
                  </td>
                  <td><?= e($inq['email'] ?? '—') ?></td>
                  <td><?= e($inq['subject']) ?></td>
                  <td><?= renderBadge($inq['status']) ?></td>
                  <td><?= date('M d, Y g:i A', strtotime($inq['created_at'])) ?></td>
                  <td style="white-space: nowrap;">
                    <button type="button" onclick='openInquiry(<?= json_encode($inq) ?>)' class="btn btn-outline" style="font-size: 11px; padding: 4px 8px;">
                      View & Reply
                    </button>
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

<!-- View / Reply Modal -->
<div id="inquiry-modal" class="modal" style="display: none;">
  <div class="modal__overlay" onclick="document.getElementById('inquiry-modal').style.display='none'"></div>
  <div class="modal__card" style="max-width: 580px; padding: 24px;">
    <h2 style="font-size: 17px; font-weight: 700; margin-bottom: 12px;" id="modal-subject">Inquiry Details</h2>
    
    <div style="background: #F8FAFC; padding: 12px 14px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 13px;">
      <div style="margin-bottom: 4px;"><strong>From:</strong> <span id="modal-from"></span> (<span id="modal-email"></span>)</div>
      <div style="margin-bottom: 4px;"><strong>Date:</strong> <span id="modal-date"></span></div>
      <div><strong>Status:</strong> <span id="modal-status"></span></div>
    </div>

    <div style="margin-bottom: 16px;">
      <label style="display: block; font-size: 12px; font-weight: 700; color: var(--color-text-muted); margin-bottom: 4px;">Message Body:</label>
      <div id="modal-message" style="background: #fff; border: 1px solid var(--color-border); padding: 12px; border-radius: var(--radius-sm); font-size: 13px; line-height: 1.5; white-space: pre-wrap;"></div>
    </div>

    <form action="<?= $base ?>/admin/inquiries.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="reply">
      <input type="hidden" name="inquiry_id" id="modal-id">

      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Admin Response / Notes (required to resolve):</label>
        <textarea name="admin_reply" id="modal-reply" rows="3" class="form-input" placeholder="Type your response to the sender..." required></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 8px;">
        <button type="button" onclick="document.getElementById('inquiry-modal').style.display='none'" class="btn btn-outline">Close</button>
        <button type="submit" class="btn btn-primary">Send Response & Mark Resolved</button>
      </div>
    </form>
  </div>
</div>

<script>
function openInquiry(inq) {
  document.getElementById('modal-id').value = inq.id;
  document.getElementById('modal-subject').textContent = inq.subject;
  document.getElementById('modal-from').textContent = (inq.first_name ? (inq.first_name + ' ' + inq.last_name) : 'Public Visitor');
  document.getElementById('modal-email').textContent = inq.email || 'No email';
  document.getElementById('modal-date').textContent = inq.created_at;
  document.getElementById('modal-status').textContent = inq.status;
  document.getElementById('modal-message').textContent = inq.message;
  document.getElementById('modal-reply').value = inq.admin_reply || '';
  document.getElementById('inquiry-modal').style.display = 'grid';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
