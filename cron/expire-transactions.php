<?php
declare(strict_types=1);

/**
 * cron/expire-transactions.php
 * Automated scheduled task: Finds transactions where claim_deadline < NOW()
 * and status is 'created' or 'pending_claim'.
 *
 * Releases reserved donation stock, marks transactions expired,
 * and notifies both parties.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting transaction expiration sweep...\n";

try {
    $pdo->beginTransaction();

    // Find all expired active transactions
    $stmt = $pdo->query('
        SELECT t.*, d.title AS donation_title
        FROM transactions t
        JOIN donations d ON t.donation_id = d.id
        WHERE t.status IN ("created", "pending_claim")
          AND t.claim_deadline < NOW()
        FOR UPDATE
    ');
    $expiredList = $stmt->fetchAll();

    $count = count($expiredList);
    echo "Found {$count} expired transactions to process.\n";

    foreach ($expiredList as $tx) {
        $txId = (int)$tx['id'];
        $donationId = (int)$tx['donation_id'];
        $quantity = (int)$tx['quantity'];
        $donorId = (int)$tx['donor_id'];
        $requesterId = (int)$tx['requester_id'];

        // 1. Mark transaction expired
        $updTx = $pdo->prepare('UPDATE transactions SET status = "expired", updated_at = NOW() WHERE id = ?');
        $updTx->execute([$txId]);

        // 2. Release reserved quantity back to donation pool
        $updDonation = $pdo->prepare('
            UPDATE donations
            SET quantity_reserved = GREATEST(0, quantity_reserved - ?)
            WHERE id = ?
        ');
        $updDonation->execute([$quantity, $donationId]);

        // 3. Mark request as expired
        $updReq = $pdo->prepare('
            UPDATE requests
            SET status = "expired"
            WHERE id = (SELECT request_id FROM matches WHERE id = ?)
        ');
        $updReq->execute([$tx['match_id']]);

        // 4. Create Audit Log (system actor = null)
        logAudit(
            $pdo, null, 'system', 'TRANSACTION_STATUS_CHANGED', 'transaction', $txId,
            ['status' => $tx['status']], ['status' => 'expired'],
            'Automatically expired due to missed claim deadline. Released ' . $quantity . ' items back to available inventory.'
        );

        // 5. Notify both parties
        createNotification(
            $pdo, $donorId, 'TRANSACTION_CANCELLED',
            'Handover Claim Expired',
            'The claim window for "' . $tx['donation_title'] . '" (Tx #' . $txId . ') expired without pickup. The ' . $quantity . ' item(s) have been returned to your available donation listing.'
        );

        createNotification(
            $pdo, $requesterId, 'CLAIM_EXPIRING',
            'Claim Deadline Passed',
            'Your claim window for "' . $tx['donation_title'] . '" (Tx #' . $txId . ') has expired. The reserved items have been released back to the community pool.'
        );

        echo " - Expired Tx #{$txId} ({$tx['donation_title']}), released {$quantity} items.\n";
    }

    $pdo->commit();
    echo "[" . date('Y-m-d H:i:s') . "] Transaction expiration sweep completed successfully.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    echo "ERROR during expiration sweep: " . $e->getMessage() . "\n";
}
