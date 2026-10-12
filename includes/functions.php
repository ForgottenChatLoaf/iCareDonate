<?php
declare(strict_types=1);
// includes/functions.php
require_once __DIR__ . '/../config/app.php';

/**
 * HTML-escape output. Use on every piece of user-supplied data.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Truncate a string by word count.
 */
function truncateWords(string $text, int $limit = 20, string $ellipsis = '...'): string
{
    $words = preg_split('/\s+/', trim($text));
    if (empty($words) || count($words) <= $limit) {
        return $text;
    }
    return implode(' ', array_slice($words, 0, $limit)) . $ellipsis;
}

/**
 * Generate or return existing CSRF token for the session.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from POST. Exits with 403 on failure.
 */
function verifyCsrf(): void
{
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request token. Please go back and try again.');
    }
}

/**
 * Render a hidden CSRF input field.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Flash message helpers
 */
function setFlash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $_SESSION['flash'] = [
        'type'    => $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : 'info'),
        'message' => $message,
    ];
}

function flash(string $key, string $message, string $type = 'info'): void
{
    setFlash($type, $message);
}

function getFlash(?string $key = null): ?array
{
    if (!empty($_SESSION['flash'])) {
        $msg = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $msg;
    }
    return null;
}

/**
 * Render any queued flash alert banners
 */
function renderFlash(): void
{
    $flash = getFlash();
    if (!$flash) {
        return;
    }

    $typeClass = match($flash['type'] ?? 'info') {
        'success'         => 'alert-success',
        'danger', 'error' => 'alert-danger alert-error',
        'warning'         => 'alert-warning',
        default           => 'alert-info',
    };

    $iconName = match($flash['type'] ?? 'info') {
        'success' => 'check-circle',
        'danger', 'error' => 'alert-circle',
        'warning' => 'alert-triangle',
        default   => 'info',
    };

    echo sprintf(
        '<div class="alert %s alert--dismissible" role="alert" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; border-radius: 8px; font-size: 13.5px; animation: slideDown 0.25s ease;">
           <div style="display: flex; align-items: center; gap: 10px;">
             <i data-lucide="%s" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
             <span>%s</span>
           </div>
           <button type="button" onclick="this.parentElement.remove()" style="background: none; border: 0; font-size: 18px; line-height: 1; cursor: pointer; color: inherit; opacity: 0.7; padding: 0 4px;" aria-label="Dismiss">&times;</button>
         </div>',
        e($typeClass),
        $iconName,
        e($flash['message'])
    );
}

/**
 * Centralized public donation visibility rule.
 * Explicit whitelist: only approved listings (not expired) are visible to the public.
 */
function publicDonationWhereClause(string $alias = 'd'): string
{
    $prefix = $alias !== '' ? $alias . '.' : '';
    return "({$prefix}status = 'approved' AND ({$prefix}expires_at IS NULL OR {$prefix}expires_at > NOW()))";
}

/**
 * Escape wildcard characters for SQL LIKE queries to ensure literal search matching.
 */
function escapeLike(string $pattern, string $escapeChar = '\\'): string
{
    return str_replace(
        [$escapeChar, '%', '_'],
        [$escapeChar . $escapeChar, $escapeChar . '%', $escapeChar . '_'],
        $pattern
    );
}

/**
 * Sanitize text cell for safe CSV export (defends against spreadsheet formula injection).
 */
function sanitizeCsvCell(?string $value): string
{
    $val = $value ?? '';
    if ($val !== '' && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $val;
    }
    return $val;
}

/**
 * Centralized donation status transition function.
 */
function transitionDonationStatus(
    PDO    $pdo,
    int    $donationId,
    string $newStatus,
    int    $adminId,
    string $reason = ''
): void {
    $allowed = [
        'draft'                => ['pending_verification', 'archived'],
        'pending_verification' => ['needs_information', 'approved', 'rejected', 'archived'],
        'needs_information'    => ['pending_verification', 'archived'],
        'approved'             => ['fully_allocated', 'expired', 'cancelled', 'archived'],
        'fully_allocated'      => ['expired', 'cancelled', 'archived'],
        'rejected'             => ['archived'],
        'cancelled'            => ['archived'],
        'expired'              => ['archived'],
        'archived'             => ['draft', 'pending_verification', 'needs_information', 'approved', 'rejected', 'fully_allocated', 'cancelled', 'expired'],
    ];

    $stmt = $pdo->prepare("SELECT status FROM donations WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => $donationId]);
    $current = $stmt->fetchColumn();

    if ($current === false) {
        throw new RuntimeException("Donation #$donationId not found.");
    }

    if (!in_array($newStatus, $allowed[$current] ?? [], true)) {
        throw new RuntimeException("Invalid status transition: $current → $newStatus");
    }

    $extras = '';
    $params = [':status' => $newStatus, ':id' => $donationId];

    if ($newStatus === 'approved') {
        $extras = ', approved_at = NOW(), approved_by = :approver';
        $params[':approver'] = $adminId;
    } elseif ($newStatus === 'rejected') {
        $extras = ', rejection_reason = :reason';
        $params[':reason'] = $reason;
    } elseif ($newStatus === 'needs_information') {
        $extras = ', info_request = :reason';
        $params[':reason'] = $reason;
    } elseif ($newStatus === 'archived') {
        $extras = ', archived_at = NOW(), archived_by = :archiver, archive_reason = :reason, archived_prev_status = :prev_status';
        $params[':archiver']    = $adminId;
        $params[':reason']      = $reason;
        $params[':prev_status'] = $current;
    }

    if ($current === 'archived' && $newStatus !== 'archived') {
        // Clearing archive metadata on restoration
        $extras .= ', archived_at = NULL, archived_by = NULL, archive_reason = NULL, archived_prev_status = NULL';
    }

    $stmt = $pdo->prepare("UPDATE donations SET status = :status, updated_at = NOW() $extras WHERE id = :id");
    $stmt->execute($params);

    logAudit($pdo, $adminId, 'admin', 'DONATION_STATUS_CHANGED', 'donation', $donationId, ['status' => $current], ['status' => $newStatus], $reason);
}

/**
 * Centralized request status transition.
 */
function transitionRequestStatus(
    PDO    $pdo,
    int    $requestId,
    string $newStatus,
    int    $adminId,
    string $reason = ''
): void {
    $allowed = [
        'submitted'         => ['proof_review', 'rejected'],
        'proof_review'      => ['needs_information', 'approved', 'rejected'],
        'needs_information' => ['proof_review'],
        'approved'          => ['matched', 'cancelled'],
        'matched'           => ['pickup_scheduled', 'claimed', 'cancelled'],
        'pickup_scheduled'  => ['claimed', 'cancelled'],
        'claimed'           => ['completed'],
    ];

    $stmt = $pdo->prepare("SELECT status FROM requests WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => $requestId]);
    $current = $stmt->fetchColumn();

    if ($current === false) {
        throw new RuntimeException("Request #$requestId not found.");
    }

    if (!in_array($newStatus, $allowed[$current] ?? [], true)) {
        throw new RuntimeException("Invalid status transition: $current → $newStatus");
    }

    $extras = ', reviewed_at = NOW(), reviewed_by = :reviewer';
    $params = [':status' => $newStatus, ':id' => $requestId, ':reviewer' => $adminId];

    if ($newStatus === 'rejected') {
        $extras .= ', rejection_reason = :reason';
        $params[':reason'] = $reason;
    } elseif ($newStatus === 'needs_information') {
        $extras .= ', info_request = :reason';
        $params[':reason'] = $reason;
    }

    $stmt = $pdo->prepare("UPDATE requests SET status = :status, updated_at = NOW() $extras WHERE id = :id");
    $stmt->execute($params);

    logAudit($pdo, $adminId, 'admin', 'REQUEST_STATUS_CHANGED', 'request', $requestId, ['status' => $current], ['status' => $newStatus], $reason);
}

/**
 * Write an audit log entry.
 */
function logAudit(
    PDO    $pdo,
    int    $actorId,
    string $actorRole,
    string $action,
    string $targetType,
    int    $targetId,
    ?array $oldValue = null,
    ?array $newValue = null,
    ?string $reason  = null
): void {
    writeAuditLog($pdo, [
        'actor_id'    => $actorId,
        'actor_role'  => $actorRole,
        'action'      => $action,
        'target_type' => $targetType,
        'target_id'   => $targetId,
        'old_value'   => $oldValue ? json_encode($oldValue) : null,
        'new_value'   => $newValue ? json_encode($newValue) : null,
        'reason'      => $reason,
    ]);
}

function writeAuditLog(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs
            (actor_id, actor_role, action, target_type, target_id, old_value, new_value, reason, ip_address, user_agent, created_at)
        VALUES
            (:actor_id, :actor_role, :action, :target_type, :target_id, :old_value, :new_value, :reason, :ip, :ua, NOW())
    ");
    $stmt->execute([
        ':actor_id'    => $data['actor_id']    ?? null,
        ':actor_role'  => $data['actor_role']  ?? 'system',
        ':action'      => $data['action']      ?? '',
        ':target_type' => $data['target_type'] ?? '',
        ':target_id'   => $data['target_id']   ?? 0,
        ':old_value'   => $data['old_value']   ?? null,
        ':new_value'   => $data['new_value']   ?? null,
        ':reason'      => $data['reason']      ?? null,
        ':ip'          => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ':ua'          => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System',
    ]);
}

/**
 * Create a user notification.
 */
function createNotification(
    PDO    $pdo,
    int    $userId,
    string $type,
    string $title,
    string $message,
    ?string $relatedType = null,
    ?int    $relatedId   = null
): void {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message, related_type, related_id, created_at)
        VALUES (:user_id, :type, :title, :message, :related_type, :related_id, NOW())
    ");
    $stmt->execute([
        ':user_id'      => $userId,
        ':type'         => $type,
        ':title'        => $title,
        ':message'      => $message,
        ':related_type' => $relatedType,
        ':related_id'   => $relatedId,
    ]);
}

/**
 * Get unread notification count.
 */
function getUnreadNotificationCount(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
    $stmt->execute([':uid' => $userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Render a status badge HTML element with multi-type support.
 */
function renderBadge(string|array $badge): string
{
    if (is_string($badge)) {
        $status = strtolower($badge);
        $badge = match($status) {
            'draft'                => ['label' => 'Draft',                'class' => 'badge-neutral'],
            'pending_verification',
            'submitted',
            'proof_review',
            'pending'              => ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'badge-pending'],
            'needs_information'    => ['label' => 'Needs Info',           'class' => 'badge-pending'],
            'approved',
            'active',
            'verified',
            'resolved'             => ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'badge-approved'],
            'matched',
            'in_progress'          => ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'badge-info'],
            'pickup_scheduled',
            'pending_claim',
            'scheduled'            => ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'badge-info'],
            'claimed'              => ['label' => 'Claimed / In Transit', 'class' => 'badge-approved'],
            'completed'            => ['label' => 'Completed',            'class' => 'badge-approved'],
            'fully_allocated'      => ['label' => 'Fully Allocated',      'class' => 'badge-info'],
            'suspended',
            'flagged'              => ['label' => ucfirst($status),       'class' => 'badge-pending'],
            'rejected',
            'banned',
            'dismissed'            => ['label' => ucfirst($status),       'class' => 'badge-rejected'],
            'cancelled',
            'closed',
            'expired'              => ['label' => ucfirst($status),       'class' => 'badge-neutral'],
            'archived'             => ['label' => 'Archived',              'class' => 'badge-neutral'],
            default                => ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'badge-neutral'],
        };
    }

    return sprintf(
        '<span class="badge %s" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 9px; border-radius: 99px; font-size: 11.5px; font-weight: 600;"><span class="badge-dot" aria-hidden="true" style="width: 6px; height: 6px; border-radius: 50%%; background: currentColor; display: inline-block;"></span> %s</span>',
        e($badge['class'] ?? 'badge-neutral'),
        e($badge['label'] ?? 'Status')
    );
}

/**
 * Time ago helper
 */
function timeAgo(string $datetime): string
{
    $now  = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);

    if ($diff->y > 0) return $diff->y . ' year'  . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day'   . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour'  . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min'   . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}

/**
 * Format a date nicely.
 */
function niceDate(string $datetime): string
{
    return (new DateTime($datetime))->format('M j, Y');
}

/**
 * Return a donation image URL.
 * If user uploaded a custom image, use that.
 * Otherwise, return authentic real online donation photos from Unsplash based on category.
 */
function getDonationImage(?string $customPath, string $category = '', int|string $seed = 0): string
{
    if (!empty($customPath)) {
        if (str_starts_with($customPath, 'http://') || str_starts_with($customPath, 'https://')) {
            return $customPath;
        }
        return appUrl('/' . ltrim($customPath, '/'));
    }

    $cat = strtolower(trim($category));

    $foodPics = [
        'https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=600&q=80', // Food drive box
        'https://images.unsplash.com/photo-1578357078586-491adf1aa5ba?auto=format&fit=crop&w=600&q=80', // Canned goods & groceries
        'https://images.unsplash.com/photo-1534723452862-4c874018d66d?auto=format&fit=crop&w=600&q=80', // Fresh groceries & pantry
        'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=600&q=80', // Food staples
    ];

    $schoolPics = [
        'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=600&q=80', // School backpacks & supplies
        'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=600&q=80', // Study books & notebooks
        'https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=600&q=80', // Classroom stationeries
        'https://images.unsplash.com/photo-1513542789411-b6a5d4f31634?auto=format&fit=crop&w=600&q=80', // Art & school materials
    ];

    $clothingPics = [
        'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?auto=format&fit=crop&w=600&q=80', // Clean folded clothes
        'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?auto=format&fit=crop&w=600&q=80', // Apparel donation
        'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?auto=format&fit=crop&w=600&q=80', // Warm jackets
        'https://images.unsplash.com/photo-1479064555552-3ef4979f8908?auto=format&fit=crop&w=600&q=80', // Clothing pack
    ];

    $medicalPics = [
        'https://images.unsplash.com/photo-1603398938378-e54eab446dde?auto=format&fit=crop&w=600&q=80', // First aid kit
        'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=600&q=80', // Health care supplies
        'https://images.unsplash.com/photo-1584017911766-d451b3d0e843?auto=format&fit=crop&w=600&q=80', // Hygiene & essentials
    ];

    $electronicPics = [
        'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=600&q=80', // Laptop / education tech
        'https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?auto=format&fit=crop&w=600&q=80', // Working laptop
    ];

    $generalPics = [
        'https://images.unsplash.com/photo-1532629345422-7515f3d16bb9?auto=format&fit=crop&w=600&q=80', // Donation box / charity
        'https://images.unsplash.com/photo-1567057419565-4349c49d8a04?auto=format&fit=crop&w=600&q=80', // Care package
        'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=600&q=80', // Community volunteering
        'https://images.unsplash.com/photo-1593113646773-028c64a8f1b8?auto=format&fit=crop&w=600&q=80', // Charity sorting
    ];

    $hash = abs(crc32((string)$seed));

    if (str_contains($cat, 'food') || str_contains($cat, 'grocer') || str_contains($cat, 'canned')) {
        return $foodPics[$hash % count($foodPics)];
    }
    if (str_contains($cat, 'school') || str_contains($cat, 'supplies') || str_contains($cat, 'book') || str_contains($cat, 'educat')) {
        return $schoolPics[$hash % count($schoolPics)];
    }
    if (str_contains($cat, 'cloth') || str_contains($cat, 'apparel') || str_contains($cat, 'shoe') || str_contains($cat, 'wear')) {
        return $clothingPics[$hash % count($clothingPics)];
    }
    if (str_contains($cat, 'medic') || str_contains($cat, 'first aid') || str_contains($cat, 'health') || str_contains($cat, 'hygiene')) {
        return $medicalPics[$hash % count($medicalPics)];
    }
    if (str_contains($cat, 'electron') || str_contains($cat, 'gadget') || str_contains($cat, 'laptop')) {
        return $electronicPics[$hash % count($electronicPics)];
    }

    return $generalPics[$hash % count($generalPics)];
}

// Official Last Updated date for legal policies
if (!defined('LEGAL_LAST_UPDATED')) {
    define('LEGAL_LAST_UPDATED', 'October 11, 2026');
}