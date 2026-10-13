<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_BASE . '/user/transactions.php');
    exit;
}

verifyCsrf();

$userId = (int)$_SESSION['user_id'];
$txId = (int)($_POST['transaction_id'] ?? 0);
$action = trim($_POST['action'] ?? '');

if ($txId <= 0 || !in_array($action, ['claim', 'complete'], true)) {
    setFlash('error', 'Invalid request.');
    header('Location: ' . APP_BASE . '/user/transactions.php');
    exit;
}

// Fetch transaction
$stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ?');
$stmt->execute([$txId]);
$tx = $stmt->fetch();

if (!$tx || ((int)$tx['donor_id'] !== $userId && (int)$tx['requester_id'] !== $userId && !isAdmin())) {
    setFlash('error', 'Transaction not found or access denied.');
    header('Location: ' . APP_BASE . '/user/transactions.php');
    exit;
}

try {
    $pdo->beginTransaction();

    if ($action === 'claim') {
        $upd = $pdo->prepare('UPDATE transactions SET status = "claimed", claimed_at = NOW() WHERE id = ?');
        $upd->execute([$txId]);

        // Audit & Notification
        logAudit($pdo, $userId, 'user', 'TRANSACTION_STATUS_CHANGED', 'transaction', $txId, ['status' => $tx['status']], ['status' => 'claimed'], 'Item marked as claimed');
        createNotification($pdo, (int)$tx['donor_id'], 'TRANSACTION_CLAIMED', 'Item Claimed by Requester', 'The requester has claimed the matched item for transaction #' . $txId . '.');
        setFlash('success', 'Item marked as claimed/received.');
    } elseif ($action === 'complete') {
        $upd = $pdo->prepare('UPDATE transactions SET status = "completed", completed_at = NOW() WHERE id = ?');
        $upd->execute([$txId]);

        // Update donation claimed stock
        $dStmt = $pdo->prepare('
            UPDATE donations
            SET quantity_reserved = GREATEST(0, quantity_reserved - ?),
                quantity_claimed = quantity_claimed + ?
            WHERE id = ?
        ');
        $dStmt->execute([(int)$tx['quantity'], (int)$tx['quantity'], (int)$tx['donation_id']]);

        // Update request status to completed
        $rStmt = $pdo->prepare('
            UPDATE requests
            SET status = "completed",
                quantity_matched = quantity_matched + ?
            WHERE id = (SELECT request_id FROM matches WHERE id = ?)
        ');
        $rStmt->execute([(int)$tx['quantity'], (int)$tx['match_id']]);

        logAudit($pdo, $userId, 'user', 'TRANSACTION_COMPLETED', 'transaction', $txId, ['status' => $tx['status']], ['status' => 'completed'], 'Handover fully completed');
        createNotification($pdo, (int)$tx['donor_id'], 'TRANSACTION_COMPLETED', 'Donation Handover Completed!', 'Thank you! The donation cycle for transaction #' . $txId . ' has been completed.');
        createNotification($pdo, (int)$tx['requester_id'], 'TRANSACTION_COMPLETED', 'Assistance Handover Completed!', 'Your assistance item handover for transaction #' . $txId . ' is now marked as complete.');
        
        setFlash('success', 'Handover successfully completed! Thank you for supporting the community.');
    }

    $pdo->commit();
    header('Location: ' . APP_BASE . '/user/transactions.php');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    setFlash('error', 'Failed to update transaction.');
    header('Location: ' . APP_BASE . '/user/transactions.php');
    exit;
}
