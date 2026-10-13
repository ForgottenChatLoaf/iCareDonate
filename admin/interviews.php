<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireAdmin();

$adminId = (int)$_SESSION['user_id'];

// Handle schedule new interview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'schedule') {
    verifyCsrf();
    $requestId = (int)($_POST['request_id'] ?? 0);
    $scheduledDate = trim($_POST['scheduled_date'] ?? '');
    $scheduledTime = trim($_POST['scheduled_time'] ?? '');
    $method = in_array($_POST['method'] ?? '', ['phone_call', 'video_call', 'in_person'], true) ? $_POST['method'] : 'phone_call';
    $venue = trim($_POST['meeting_link_or_venue'] ?? '');
    $notes = trim($_POST['admin_notes'] ?? '');

    if ($requestId > 0 && !empty($scheduledDate) && !empty($scheduledTime)) {
        try {
            $rStmt = $pdo->prepare('SELECT requester_id FROM requests WHERE id = ?');
            $rStmt->execute([$requestId]);
            $req = $rStmt->fetch();

            if ($req) {
                $stmt = $pdo->prepare('
                    INSERT INTO interviews (request_id, requester_id, scheduled_date, scheduled_time, method, meeting_link_or_venue, admin_notes, result, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, "pending", ?)
                ');
                $stmt->execute([$requestId, (int)$req['requester_id'], $scheduledDate, $scheduledTime, $method, $venue ?: null, $notes ?: null, $adminId]);

                // Update request interview_status to scheduled
                $upd = $pdo->prepare('UPDATE requests SET interview_status = "scheduled" WHERE id = ?');
                $upd->execute([$requestId]);

                createNotification(
                    $pdo, (int)$req['requester_id'], 'INTERVIEW_SCHEDULED',
                    'Verification Interview Scheduled',
                    'A verification interview has been scheduled on ' . date('F j, Y', strtotime($scheduledDate)) . ' at ' . date('g:i A', strtotime($scheduledTime)) . ' (' . ucfirst(str_replace('_', ' ', $method)) . ').'
                );

                logAudit($pdo, $adminId, 'admin', 'INTERVIEW_SCHEDULED', 'interview', (int)$pdo->lastInsertId(), null, null, 'Scheduled verification interview');
                setFlash('success', 'Verification interview scheduled and requester notified.');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            setFlash('error', 'Failed to schedule interview.');
        }
    }
    header('Location: ' . APP_BASE . '/admin/interviews.php');
    exit;
}

// Handle update interview result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_result') {
    verifyCsrf();
    $interviewId = (int)($_POST['interview_id'] ?? 0);
    $result = in_array($_POST['result'] ?? '', ['pending', 'verified', 'further_review', 'not_verified'], true) ? $_POST['result'] : 'pending';
    $notes = trim($_POST['admin_notes'] ?? '');

    if ($interviewId > 0) {
        try {
            $upd = $pdo->prepare('
                UPDATE interviews
                SET result = ?, admin_notes = ?, conducted_by = ?, conducted_at = NOW()
                WHERE id = ?
            ');
            $upd->execute([$result, $notes ?: null, $adminId, $interviewId]);

            logAudit($pdo, $adminId, 'admin', 'INTERVIEW_RESULT_UPDATED', 'interview', $interviewId, null, ['result' => $result], 'Updated interview verification result');
            setFlash('success', 'Interview result recorded.');
        } catch (Exception $e) {
            error_log($e->getMessage());
            setFlash('error', 'Failed to update result.');
        }
    }
    header('Location: ' . APP_BASE . '/admin/interviews.php');
    exit;
}

// Fetch all scheduled interviews
$interviews = [];
try {
    $stmt = $pdo->query('
        SELECT i.*, r.quantity_requested, r.purpose,
               d.title AS donation_title,
               u.first_name, u.last_name, u.email AS requester_email, u.phone AS requester_phone
        FROM interviews i
        JOIN requests r ON i.request_id = r.id
        JOIN donations d ON r.donation_id = d.id
        JOIN users u ON i.requester_id = u.id
        ORDER BY i.scheduled_date DESC, i.scheduled_time DESC
    ');
    $interviews = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

// Fetch pending requests that need interview scheduling
$pendingRequests = [];
try {
    $stmt = $pdo->query('
        SELECT r.id, r.quantity_requested, d.title AS donation_title, u.first_name, u.last_name
        FROM requests r
        JOIN donations d ON r.donation_id = d.id
        JOIN users u ON r.requester_id = u.id
        WHERE r.status IN ("submitted", "proof_review")
        ORDER BY r.submitted_at ASC
    ');
    $pendingRequests = $stmt->fetchAll();
} catch (Exception $e) {
    error_log($e->getMessage());
}

$pageTitle = 'Interviews & Verification — iCareDonate Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/nav-admin.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Requester Interviews &amp; Verification</h1>
      </div>
    </header>

    <div class="admin-content">
      <?php renderFlash(); ?>

      <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; align-items: start;">
        
        <!-- Left: Interviews List -->
        <div>
          <div class="admin-table-container">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Requester</th>
                  <th>Item Requested</th>
                  <th>Date &amp; Method</th>
                  <th>Result</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($interviews)): ?>
                  <tr>
                    <td colspan="6" style="text-align: center; padding: 32px; color: var(--color-text-muted);">No verification interviews on schedule.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($interviews as $iv): ?>
                    <tr>
                      <td><strong>#<?= (int)$iv['id'] ?></strong></td>
                      <td>
                        <strong><?= e($iv['first_name'] . ' ' . $iv['last_name']) ?></strong>
                        <div style="font-size: 11.5px; color: var(--color-text-muted);"><?= e($iv['requester_phone'] ?? $iv['requester_email']) ?></div>
                      </td>
                      <td><?= e($iv['donation_title']) ?></td>
                      <td>
                        <?= date('M d, Y', strtotime($iv['scheduled_date'])) ?> <?= date('g:i A', strtotime($iv['scheduled_time'])) ?>
                        <div style="font-size: 11px; color: var(--color-primary); font-weight: 600;"><?= ucfirst(str_replace('_', ' ', $iv['method'])) ?></div>
                      </td>
                      <td>
                        <?php
                          $badgeClass = match($iv['result']) {
                            'verified' => 'badge-approved',
                            'not_verified' => 'badge-rejected',
                            'further_review' => 'badge-accent',
                            default => 'badge-pending',
                          };
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= ucfirst(str_replace('_', ' ', $iv['result'])) ?></span>
                      </td>
                      <td>
                        <button type="button" class="btn btn-outline" style="font-size: 11px; padding: 4px 8px;" onclick="openResultModal(<?= (int)$iv['id'] ?>, '<?= e($iv['result']) ?>', '<?= e($iv['admin_notes'] ?? '') ?>');">
                          Record Result
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Right: Schedule New Interview & Questions Guide (Scope 6.5) -->
        <div>
          <!-- Schedule Form -->
          <div class="card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 24px; box-shadow: var(--shadow-sm);">
            <h2 style="font-size: 16px; font-weight: 700; color: var(--color-text); margin: 0 0 16px;"><i data-lucide="calendar-plus" style="width:16px;height:16px;vertical-align:middle;margin-right:6px;"></i> Schedule Verification Interview</h2>

            <form action="<?= $base ?>/admin/interviews.php" method="POST" data-validate>
              <?= csrfField() ?>
              <input type="hidden" name="action" value="schedule">

              <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="request_id">Select Assistance Request</label>
                <select id="request_id" name="request_id" class="form-input" required>
                  <option value="">Choose Pending Request...</option>
                  <?php foreach ($pendingRequests as $pr): ?>
                    <option value="<?= (int)$pr['id'] ?>">
                      #<?= (int)$pr['id'] ?> — <?= e($pr['first_name'] . ' ' . $pr['last_name']) ?> (<?= e($pr['donation_title']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <!-- Date Component -->
                <div class="form-group">
                  <label class="form-label" for="scheduled_date_trigger">Date</label>
                  <div class="icd-input-wrapper">
                    <input type="hidden" id="scheduled_date" name="scheduled_date" value="<?= date('Y-m-d') ?>" required>
                    <div id="scheduled_date_trigger" class="icd-picker-trigger" role="button" tabindex="0" aria-haspopup="dialog" aria-expanded="false">
                      <span id="scheduled_date_display" class="icd-picker-display"><?= date('M d, Y') ?></span>
                      <i data-lucide="calendar" class="icd-field-icon"></i>
                    </div>
                    <div id="scheduled_date_popover" class="icd-popover icd-calendar-popover" role="dialog" aria-label="Select Date"></div>
                  </div>
                </div>

                <!-- Time Component -->
                <div class="form-group">
                  <label class="form-label" for="scheduled_time_trigger">Time</label>
                  <div class="icd-input-wrapper">
                    <input type="hidden" id="scheduled_time" name="scheduled_time" value="09:00" required>
                    <div id="scheduled_time_trigger" class="icd-picker-trigger" role="button" tabindex="0" aria-haspopup="dialog" aria-expanded="false">
                      <span id="scheduled_time_display" class="icd-picker-display">09:00 AM</span>
                      <i data-lucide="clock" class="icd-field-icon"></i>
                    </div>
                    <div id="scheduled_time_popover" class="icd-popover icd-time-popover" role="dialog" aria-label="Select Time"></div>
                  </div>
                </div>
              </div>

              <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="method">Interview Method</label>
                <select id="method" name="method" class="form-input" required>
                  <option value="phone_call">Phone Call</option>
                  <option value="video_call">Online Video Call (Google Meet / Zoom)</option>
                  <option value="in_person">In-Person (Barangay Hall / Community Center)</option>
                </select>
              </div>

              <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="meeting_link_or_venue">Meeting Link / Venue Address</label>
                <input type="text" id="meeting_link_or_venue" name="meeting_link_or_venue" class="form-input" placeholder="e.g. https://meet.google.com/... or Barangay Hall Desk">
              </div>

              <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 10px;">
                Schedule &amp; Notify Requester
              </button>
            </form>
          </div>

          <!-- Interview Questions Evaluation Guide (Scope 6.5) -->
          <div class="card" style="padding: 24px; border-radius: var(--radius-lg); background: var(--color-tint-teal); border: 1px solid rgba(31, 79, 163, 0.12);">
            <h3 style="font-size: 15px; font-weight: 700; color: var(--color-primary); margin: 0 0 10px;"><i data-lucide="clipboard-check" style="width:15px;height:15px;vertical-align:middle;margin-right:6px;"></i> Evaluation Questions Guide</h3>
            <ul style="font-size: 13px; color: var(--color-text); margin: 0; padding-left: 18px; line-height: 1.6;">
              <li>How many individuals in your household will use this item?</li>
              <li>Can you confirm your residency / barangay address?</li>
              <li>What is your preferred handover or pickup schedule?</li>
              <li>Have you previously received similar community allocations?</li>
            </ul>
          </div>
        </div>

      </div>

    </div>
  </div>
</div>

<!-- Modal to Record Result -->
<div id="result-modal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; padding: 20px;">
  <div class="modal-panel card" style="max-width: 480px; width: 100%; padding: 28px; border-radius: var(--radius-lg); background: #FFFFFF;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0;">Record Interview Verification Result</h3>
      <button type="button" onclick="document.getElementById('result-modal').style.display='none';" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
    </div>

    <form action="<?= $base ?>/admin/interviews.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="update_result">
      <input type="hidden" id="modal-interview-id" name="interview_id" value="">

      <div class="form-group" style="margin-bottom: 16px;">
        <label class="form-label" for="modal-result">Verification Result</label>
        <select id="modal-result" name="result" class="form-input" required>
          <option value="verified">Verified (Approved for Matching)</option>
          <option value="further_review">Further Review Needed</option>
          <option value="not_verified">Not Verified / Rejected</option>
          <option value="pending">Pending</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 20px;">
        <label class="form-label" for="modal-notes">Admin Interview Notes</label>
        <textarea id="modal-notes" name="admin_notes" class="form-input" rows="3" placeholder="Summary of interview answers and findings..."></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" onclick="document.getElementById('result-modal').style.display='none';" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Verification Result</button>
      </div>
    </form>
  </div>
</div>

<script src="<?= $base ?>/assets/js/date-time-picker.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.lucide) { lucide.createIcons(); }

  // 1. Custom Date Picker
  initIcdDatePicker({
    trigger: 'scheduled_date_trigger',
    popover: 'scheduled_date_popover',
    display: 'scheduled_date_display',
    hidden: 'scheduled_date',
    minDate: '<?= date('Y-m-d') ?>',
    placeholder: 'Select date'
  });

  // 2. Custom Time Picker
  initIcdTimePicker({
    trigger: 'scheduled_time_trigger',
    popover: 'scheduled_time_popover',
    display: 'scheduled_time_display',
    hidden: 'scheduled_time'
  });
});
</script>

<script>
function openResultModal(id, result, notes) {
  document.getElementById('modal-interview-id').value = id;
  document.getElementById('modal-result').value = result;
  document.getElementById('modal-notes').value = notes;
  document.getElementById('result-modal').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
