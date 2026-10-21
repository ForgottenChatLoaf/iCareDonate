<?php
/**
 * iCareDonate — Donation Submission Handler (POST only)
 * actions/submit_donation.php
 *
 * Validates donation listing fields, enforces the pending submission restriction,
 * and persists to MySQL donations table with transaction safety and notification generation.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed. POST required.']);
    exit;
}

// 1. Authenticate Donor
$userId = getAuthenticatedUserId();
if ($userId === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to submit a donation listing.']);
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

$title          = trim((string)($_POST['title'] ?? ($jsonBody['title'] ?? '')));
$category       = trim((string)($_POST['category'] ?? ($jsonBody['category'] ?? 'other-essentials')));
$description    = trim((string)($_POST['description'] ?? ($jsonBody['description'] ?? '')));
$quantity       = (int)($_POST['quantity_total'] ?? ($_POST['quantity'] ?? ($jsonBody['quantity_total'] ?? ($jsonBody['quantity'] ?? 1))));
$unit           = trim((string)($_POST['unit'] ?? ($jsonBody['unit'] ?? 'pcs')));
$conditionType  = trim((string)($_POST['condition_type'] ?? ($jsonBody['condition_type'] ?? 'Good')));
$pickupMethod   = trim((string)($_POST['pickup_method'] ?? ($jsonBody['pickup_method'] ?? 'both')));
$pickupCity     = trim((string)($_POST['pickup_city'] ?? ($jsonBody['pickup_city'] ?? '')));
$pickupBarangay = trim((string)($_POST['pickup_barangay'] ?? ($jsonBody['pickup_barangay'] ?? '')));

// Validation
$errors = [];
if ($title === '' || mb_strlen($title) < 3) {
    $errors[] = 'Please provide a clear item title for your donation (at least 3 characters).';
}

if ($quantity < 1) {
    $errors[] = 'Quantity must be at least 1.';
}

if ($pickupCity === '') {
    $errors[] = 'Please specify the city/municipality for handover coordination.';
}

if ($pickupBarangay === '') {
    $errors[] = 'Please specify the barangay for handover coordination.';
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

// 3. Handle Optional Item Photo Upload
$imageUrl = null;
$uploadError = null;
$uploadedFile = $_FILES['item_photo'] ?? ($_FILES['image'] ?? null);

if ($uploadedFile && is_array($uploadedFile) && !empty($uploadedFile['name'])) {
    if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
        // Size validation (Max 5MB)
        $maxBytes = 5 * 1024 * 1024;
        if ($uploadedFile['size'] > $maxBytes) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Uploaded photo exceeds the 5MB size limit.']);
            exit;
        }

        // Verify MIME type using finfo (not extension alone)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $uploadedFile['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes, true)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid file format. Please upload a JPG, PNG, or WEBP image.']);
            exit;
        }

        $ext = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg'
        };

        $uploadDir = __DIR__ . '/../uploads/donations';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
            @file_put_contents($uploadDir . '/.htaccess', "Options -ExecCGI\nphp_flag engine off\n<FilesMatch \"\.(php|phtml|php5|php7|php8)$\">\nOrder allow,deny\nDeny from all\n</FilesMatch>");
        }

        $safeFileName = 'don_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $safeFileName;

        if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
            $imageUrl = 'uploads/donations/' . $safeFileName;
        }
    } elseif ($uploadedFile['error'] !== UPLOAD_ERR_NO_FILE) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Photo upload failed. Please try again.']);
        exit;
    }
}

// 4. Enforce Business Rule: Pending Submission Restriction (Atomic Check)
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

    // Normalize condition enum ('new','like_new','good','fair','poor')
    $condLower = strtolower(str_replace([' ', '-'], '_', $conditionType));
    $validConditions = ['new', 'like_new', 'good', 'fair', 'poor'];
    $condition = in_array($condLower, $validConditions, true) ? $condLower : 'good';

    // Normalize handover_method enum ('pickup','meetup','delivery')
    $methodLower = strtolower($pickupMethod);
    $validMethods = ['pickup', 'meetup', 'delivery'];
    $handoverMethod = in_array($methodLower, $validMethods, true) ? $methodLower : 'pickup';

    $location = trim($pickupBarangay . ', ' . $pickupCity);

    // 5. Insert the Donation Record
    $insertStmt = $pdo->prepare("
        INSERT INTO donations (
            donor_id, title, category, description, image_url, quantity,
            `condition`, location, barangay, city, handover_method, status, created_at
        ) VALUES (
            :uid, :title, :cat, :desc, :img, :qty,
            :cond, :loc, :brgy, :city, :method, 'pending', NOW()
        )
    ");
    $insertStmt->execute([
        ':uid'    => $userId,
        ':title'  => $title,
        ':cat'    => $category,
        ':desc'   => $description,
        ':img'    => $imageUrl,
        ':qty'    => $quantity,
        ':cond'   => $condition,
        ':loc'    => $location,
        ':brgy'   => $pickupBarangay,
        ':city'   => $pickupCity,
        ':method' => $handoverMethod,
    ]);

    $newDonationId = (int)$pdo->lastInsertId();

    // 5. Generate Automated System Notification for Donor
    try {
        $stmtNotif = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
            VALUES (:uid, :title, :msg, 'donation', 0, NOW())
        ");
        $stmtNotif->execute([
            ':uid'   => $userId,
            ':title' => 'Donation Listing Submitted',
            ':msg'   => 'Your donation listing for "' . $title . '" (#' . $newDonationId . ') was submitted and is pending verification by community coordinators.',
        ]);
    } catch (PDOException $e) {}

    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'success'     => true,
        'message'     => 'Donation listing submitted successfully! Community moderators will review and publish your listing.',
        'donation_id' => $newDonationId,
        'redirect'    => 'donations.html',
    ]);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error submitting donation: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to submit your donation at this time. Please try again.',
    ]);
    exit;
}
