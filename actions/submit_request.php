<?php
/**
 * iCareDonate — Assistance Request Submission Handler (POST only)
 * actions/submit_request.php
 *
 * Validates request data, enforces the pending submission restriction,
 * collects and verifies requester personal & contact details, and persists
 * to MySQL requests table with transaction safety and notification generation.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed. POST required.']);
    exit;
}

// 1. Authenticate Requester
$userId = getAuthenticatedUserId();
if ($userId === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to submit an assistance request.']);
    exit;
}

// 2. Database Connection
$pdo = getDB();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Database service is currently unavailable. Please try again shortly.']);
    exit;
}

// Read payload from POST or JSON
$rawInput = file_get_contents('php://input');
$jsonBody = json_decode($rawInput, true) ?? [];
$mergedInput = !empty($_POST) ? $_POST : $jsonBody;

if (!icd_verify_csrf_request($mergedInput)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security verification failed (invalid CSRF token). Please refresh and try again.']);
    exit;
}

if (icd_is_honeypot_triggered($mergedInput)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Submission could not be verified. Please try again.']);
    exit;
}

icd_enforce_rate_limit('submit_request', 10, 900, $userId);

// Verify account is active
$stCheck = $pdo->prepare('SELECT status FROM users WHERE id = :uid LIMIT 1');
$stCheck->execute([':uid' => $userId]);
$uStatus = strtolower((string)($stCheck->fetchColumn() ?: 'active'));
if ($uStatus === 'suspended' || $uStatus === 'banned') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Your account is currently ' . $uStatus . ' and cannot submit requests.']);
    exit;
}

$donationId      = isset($_POST['donation_id']) ? (int)$_POST['donation_id'] : (isset($jsonBody['donation_id']) ? (int)$jsonBody['donation_id'] : null);
$itemTitle       = trim((string)($_POST['item_title'] ?? ($jsonBody['item_title'] ?? '')));
$category        = trim((string)($_POST['category'] ?? ($jsonBody['category'] ?? '')));
$quantity        = (int)($_POST['quantity'] ?? ($_POST['quantity_needed'] ?? ($jsonBody['quantity'] ?? ($jsonBody['quantity_needed'] ?? 1))));
$unit            = trim((string)($_POST['unit'] ?? ($jsonBody['unit'] ?? 'packs')));
$reason          = trim((string)($_POST['reason'] ?? ($_POST['situation'] ?? ($jsonBody['reason'] ?? ($jsonBody['situation'] ?? '')))));
$proofType       = trim((string)($_POST['proof_type'] ?? ($jsonBody['proof_type'] ?? 'Certificate of Indigency')));
$urgencyRaw      = trim((string)($_POST['urgency'] ?? ($jsonBody['urgency'] ?? 'Medium')));

$urgencyMap = [
    'standard' => 'Medium',
    'low'      => 'Low',
    'medium'   => 'Medium',
    'high'     => 'High',
    'critical' => 'Critical',
];
$urgency = $urgencyMap[strtolower($urgencyRaw)] ?? 'Medium';

// Requester Personal & Contact Information
$requesterName   = trim((string)($_POST['requester_name'] ?? ($jsonBody['requester_name'] ?? '')));
$contactNumber   = trim((string)($_POST['contact_number'] ?? ($_POST['requester_phone'] ?? ($jsonBody['contact_number'] ?? ($jsonBody['requester_phone'] ?? '')))));
$barangay        = trim((string)($_POST['barangay'] ?? ($jsonBody['barangay'] ?? '')));
$city            = trim((string)($_POST['city'] ?? ($jsonBody['city'] ?? '')));
$completeAddress = trim((string)($_POST['complete_address'] ?? ($_POST['requester_address'] ?? ($jsonBody['complete_address'] ?? ($jsonBody['requester_address'] ?? '')))));

if ($completeAddress !== '' && ($barangay === '' || $city === '')) {
    $addrParts = array_map('trim', explode(',', $completeAddress));
    if (count($addrParts) >= 2) {
        $barangay = $barangay ?: $addrParts[0];
        $city     = $city ?: implode(', ', array_slice($addrParts, 1));
    } else {
        $barangay = $barangay ?: $addrParts[0];
        $city     = $city ?: $addrParts[0];
    }
}

if ($itemTitle === '' && $category !== '') {
    $itemTitle = ucwords(str_replace('-', ' ', $category)) . ' Assistance';
}

// 3. Fallback: Retrieve missing personal info from users table if not passed
if ($requesterName === '') {
    try {
        $stmtUser = $pdo->prepare('SELECT name, email FROM users WHERE id = :uid LIMIT 1');
        $stmtUser->execute([':uid' => $userId]);
        $uRow = $stmtUser->fetch();
        if ($uRow && !empty($uRow['name'])) {
            $requesterName = (string)$uRow['name'];
        }
    } catch (PDOException $e) {}
}

// 4. Server-Side Validation: Personal & Contact Information
$errors = [];

if ($requesterName === '' || mb_strlen($requesterName) < 2) {
    $errors[] = 'Please provide your full legal name for request verification.';
}

// Philippine mobile number or standard phone validation: min 10 digits
$cleanPhone = preg_replace('/[^0-9+]/', '', $contactNumber);
if ($contactNumber === '' || strlen($cleanPhone) < 10) {
    $errors[] = 'Please provide an active mobile contact number (e.g. 0917-555-0192) so community coordinators can reach you.';
}

if ($barangay === '') {
    $errors[] = 'Please provide your Barangay or locality for coordination.';
}

if ($city === '') {
    $errors[] = 'Please specify your City or Municipality.';
}

if ($completeAddress === '' || mb_strlen($completeAddress) < 5) {
    $errors[] = 'Please provide your complete residential or delivery coordination address.';
} elseif (mb_strlen($completeAddress) > 1000) {
    $errors[] = 'Complete address must not exceed 1,000 characters.';
}

// 5. Server-Side Validation: Request Details
if ($itemTitle === '') {
    $itemTitle = 'Community Assistance Resource';
}

if ($quantity < 1) {
    $errors[] = 'Requested quantity must be at least 1.';
}

if ($reason === '' || mb_strlen($reason) < 10) {
    $errors[] = 'Please describe the specific situation or purpose of your request (at least 10 characters).';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $errors[0],
        'errors'  => $errors,
    ]);
    exit;
}

// 6. Enforce Business Rule: Pending Submission Restriction (Atomic Check)
try {
    $pdo->beginTransaction();

    // Check for existing pending donations
    $stmtCheckDon = $pdo->prepare("SELECT id, title FROM donations WHERE donor_id = :uid AND status = 'pending' FOR UPDATE");
    $stmtCheckDon->execute([':uid' => $userId]);
    $pendingDon = $stmtCheckDon->fetch();

    if ($pendingDon) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'code'    => 'PENDING_RESTRICTION',
            'message' => 'You already have a donation submission ("' . htmlspecialchars($pendingDon['title']) . '") awaiting admin review. Please wait for the review to finish before submitting another donation or resource request.',
        ]);
        exit;
    }

    // Check for existing pending requests
    $stmtCheckReq = $pdo->prepare("
        SELECT r.id, COALESCE(d.title, 'Assistance Request') AS item_title
        FROM requests r
        LEFT JOIN donations d ON r.donation_id = d.id
        WHERE r.requester_id = :uid AND r.status = 'pending'
        FOR UPDATE
    ");
    $stmtCheckReq->execute([':uid' => $userId]);
    $pendingReq = $stmtCheckReq->fetch();

    if ($pendingReq) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'code'    => 'PENDING_RESTRICTION',
            'message' => 'You already have an assistance request ("' . htmlspecialchars($pendingReq['item_title']) . '") awaiting admin review. Please wait for the review to finish before submitting another donation or resource request.',
        ]);
        exit;
    }

    // Handle optional proof file upload
    $proofFilePath = null;
    $proofFile = $_FILES['proof_file'] ?? ($_FILES['proof'] ?? ($_FILES['proof_doc'] ?? null));
    if ($proofFile && is_array($proofFile) && !empty($proofFile['name']) && $proofFile['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/proofs';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
            @file_put_contents($uploadDir . '/.htaccess', "Options -ExecCGI\nphp_flag engine off\n<FilesMatch \"\.(php|phtml|php5|php7|php8)$\">\nOrder allow,deny\nDeny from all\n</FilesMatch>");
        }
        $ext = strtolower(pathinfo($proofFile['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'webp'], true)) {
            $safeName = 'proof_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            if (move_uploaded_file($proofFile['tmp_name'], $uploadDir . '/' . $safeName)) {
                $proofFilePath = 'uploads/proofs/' . $safeName;
            }
        }
    }

    // If donationId passed, verify it exists in donations table; otherwise null
    $finalDonationId = null;
    if ($donationId !== null && $donationId > 0) {
        $checkDid = $pdo->prepare('SELECT id FROM donations WHERE id = :id LIMIT 1');
        $checkDid->execute([':id' => $donationId]);
        if ($checkDid->fetchColumn()) {
            $finalDonationId = $donationId;
        }
    }

    $compiledMessage = $reason;

    // 7. Insert the Assistance Request into MySQL
    $insertStmt = $pdo->prepare("
        INSERT INTO requests (
            requester_id, donation_id, item_title, message, quantity, unit, urgency,
            proof_type, proof_file_path, contact_number, barangay, city, complete_address,
            status, created_at
        ) VALUES (
            :uid, :did, :item_title, :msg, :qty, :unit, :urgency,
            :proof, :file_path, :contact, :brgy, :city, :addr,
            'pending', NOW()
        )
    ");

    $insertStmt->execute([
        ':uid'        => $userId,
        ':did'        => $finalDonationId,
        ':item_title' => $itemTitle,
        ':msg'        => $compiledMessage,
        ':qty'        => $quantity,
        ':unit'       => $unit ?: 'items',
        ':urgency'    => $urgency,
        ':proof'      => $proofType ?: 'Certificate of Indigency',
        ':file_path'  => $proofFilePath,
        ':contact'    => $contactNumber,
        ':brgy'       => $barangay,
        ':city'       => $city,
        ':addr'       => $completeAddress,
    ]);

    $newRequestId = (int)$pdo->lastInsertId();

    // 8. Generate Automated System Notification for Requester
    try {
        $stmtNotif = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
            VALUES (:uid, :title, :msg, 'request', 0, NOW())
        ");
        $stmtNotif->execute([
            ':uid'   => $userId,
            ':title' => 'Assistance Request Submitted',
            ':msg'   => 'Your assistance request for "' . $itemTitle . '" (#' . $newRequestId . ') was submitted and is pending verification by community coordinators.',
        ]);
    } catch (PDOException $e) {
        // Notification failure is non-fatal to the request transaction
    }

    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'success'    => true,
        'message'    => 'Assistance request submitted successfully! Community coordinators will verify your information.',
        'request_id' => $newRequestId,
        'redirect'   => 'requests.html',
    ]);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error submitting request: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to submit your request at this time. Please try again.',
    ]);
    exit;
}
