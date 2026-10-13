<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$adminId = (int)$_SESSION['user_id'];

// Handle reply or status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    verifyCsrf();
    $ticketId = (int)$_POST['ticket_id'];
    $reply    = trim($_POST['admin_reply'] ?? '');
    $status   = in_array($_POST['status'] ?? '', ['open', 'in_progress', 'resolved', 'closed'], true) ? $_POST['status'] : 'resolved';

    try {
        $stmt = $pdo->prepare('SELECT * FROM support_tickets WHERE id = ?');
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();

        if ($ticket) {
            $upd = $pdo->prepare('
                UPDATE support_tickets
                SET status = ?, assigned_to = ?, admin_reply = ?, replied_at = NOW(),
                    closed_at = CASE WHEN ? IN ("resolved", "closed") THEN NOW() ELSE NULL END
                WHERE id = ?
            ');
            $upd->execute([$status, $adminId, $reply ?: null, $status, $ticketId]);

            // Notify user if ticket has a user_id
            if (!empty($ticket['user_id'])) {
                createNotification(
                    $pdo, (int)$ticket['user_id'], 'ADMIN_MESSAGE',
                    'Support Ticket Update: ' . $ticket['subject'],
                    'Your inquiry has been updated: ' . ($reply ?: 'Status changed to ' . ucfirst($status))
                );
            }

            logAudit($pdo, $adminId, 'admin', 'SUPPORT_TICKET_UPDATED', 'support_ticket', $ticketId, null, ['status' => $status], 'Updated support ticket');
            setFlash('success', 'Ticket updated successfully.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        setFlash('error', 'Failed to update ticket.');
    }
    header('Location: ' . APP_BASE . '/admin/support.php');
    exit;
}

// Fetch all support tickets
$tickets = [];
try {
    $stmt = $pdo->query('
        SELECT s.*, u.first_name, u.last_name, u.email AS user_account_email
        FROM support_tickets s
        LEFT JOIN users u ON s.user_id = u.id
        ORDER BY FIELD(s.status, "open", "in_progress", "resolved", "closed"), s.created_at DESC
    ');
    $tickets = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$pageTitle = 'Support Desk — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Community Support Desk</h1>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php if (empty($tickets)): ?>
          <div class="card" style="padding: 40px; text-align: center;">
            <p style="color: var(--color-text-muted);">No support tickets or inquiries filed.</p>
          </div>
        <?php else: ?>
          <?php foreach ($tickets as $t): ?>
            <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
              
              <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 12px; flex-wrap: wrap;">
                <div>
                  <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 4px;">
                    <span class="badge badge-info">Ticket #<?= (int)$t['id'] ?></span>
                    <span style="font-size: 12px; color: var(--color-text-muted);">
                      From: <strong><?= e($t['email'] ?? $t['user_account_email'] ?? 'Anonymous') ?></strong> · <?= date('M d, Y g:i A', strtotime($t['created_at'])) ?>
                    </span>
                  </div>

                  <h3 style="font-size: 17px; font-weight: 700; color: var(--color-text); margin: 0 0 6px;">
                    <?= e($t['subject']) ?>
                  </h3>
                </div>

                <div>
                  <?= renderBadge($t['status']) ?>
                </div>
              </div>

              <!-- Message Body -->
              <div style="background: #F8FAFC; padding: 14px 16px; border-radius: var(--radius-md); font-size: 13.5px; color: var(--color-text); margin-bottom: 14px; border: 1px solid var(--color-border); white-space: pre-line;">
                <?= e($t['message']) ?>
              </div>

              <!-- Existing reply if any -->
              <?php if (!empty($t['admin_reply'])): ?>
                <div style="background: var(--color-tint-teal); padding: 12px 14px; border-radius: var(--radius-md); font-size: 13px; color: var(--color-text); margin-bottom: 14px; border: 1px solid rgba(31, 79, 163, 0.12);">
                  <strong>Admin Response:</strong> <?= e($t['admin_reply']) ?>
                </div>
              <?php endif; ?>

              <!-- Reply & Status Form -->
              <form action="<?= $base ?>/admin/support.php" method="POST" style="padding-top: 14px; border-top: 1px solid var(--color-border); display: flex; flex-direction: column; gap: 10px;">
                <?= csrfField() ?>
                <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">

                <div style="display: grid; grid-template-columns: 1fr 140px; gap: 12px;">
                  <input type="text" name="admin_reply" placeholder="Type a response to this ticket..." class="form-input" style="height: 38px; font-size: 13px;">
                  <select name="status" class="form-input" style="height: 38px; font-size: 13px;">
                    <option value="in_progress" <?= $t['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="resolved" <?= $t['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    <option value="closed" <?= $t['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                    <option value="open" <?= $t['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                  </select>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                  <button type="submit" class="btn btn-primary" style="font-size: 12px; padding: 6px 16px;">
                    Update Ticket &amp; Send Reply
                  </button>
                </div>
              </form>

            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
