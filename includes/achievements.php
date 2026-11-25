<?php
/**
 * iCareDonate — Verified Achievement Evaluation Helper
 * includes/achievements.php
 *
 * Awards achievements strictly from verified, completed database outcomes.
 * Prevents duplicate awards via UNIQUE(user_id, achievement_id) and creates
 * deduplicated notifications. Never rewards unverified or repeated clicks.
 */

declare(strict_types=1);

/**
 * Evaluates and awards any newly qualified achievements for a user.
 *
 * @return array<int, array<string, mixed>> Newly awarded achievements
 */
function icd_evaluate_user_achievements(PDO $pdo, int $userId): array {
    if ($userId <= 0) {
        return [];
    }

    $newlyAwarded = [];

    try {
        // Compute verified participation metrics from real records
        $stmtDon = $pdo->prepare("
            SELECT
                COUNT(*) AS verified_donations,
                COUNT(DISTINCT DATE_FORMAT(created_at, '%Y-%m')) AS active_months,
                SUM(CASE WHEN campaign_id IS NOT NULL THEN 1 ELSE 0 END) AS campaign_donations
            FROM donations
            WHERE donor_id = :uid AND status IN ('approved', 'claimed', 'completed')
        ");
        $stmtDon->execute([':uid' => $userId]);
        $donStats = $stmtDon->fetch() ?: [];

        $stmtAlloc = $pdo->prepare("
            SELECT COUNT(*)
            FROM donation_allocations da
            INNER JOIN donations d ON da.donation_id = d.id
            WHERE d.donor_id = :uid AND da.status = 'completed'
        ");
        $stmtAlloc->execute([':uid' => $userId]);
        $completedAllocs = (int)$stmtAlloc->fetchColumn();

        $metrics = [
            'verified_donations'    => (int)($donStats['verified_donations'] ?? 0),
            'active_months'         => (int)($donStats['active_months'] ?? 0),
            'campaign_donations'    => (int)($donStats['campaign_donations'] ?? 0),
            'completed_allocations' => $completedAllocs,
        ];

        // Fetch active achievement definitions not yet earned by this user
        $stmtPending = $pdo->prepare("
            SELECT a.*
            FROM achievements a
            WHERE a.is_active = 1
              AND NOT EXISTS (
                  SELECT 1 FROM user_achievements ua
                  WHERE ua.user_id = :uid AND ua.achievement_id = a.id
              )
        ");
        $stmtPending->execute([':uid' => $userId]);
        $candidates = $stmtPending->fetchAll();

        $stmtAward = $pdo->prepare("
            INSERT IGNORE INTO user_achievements (user_id, achievement_id, earned_reason, earned_at)
            VALUES (:uid, :aid, :reason, NOW())
        ");
        $stmtNotif = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_type, related_id, is_read, created_at)
            VALUES (:uid, :title, :msg, 'achievement', 'achievement', :aid, 0, NOW())
        ");

        foreach ($candidates as $ach) {
            $type = (string)$ach['criteria_type'];
            $threshold = (int)$ach['criteria_threshold'];
            $currentVal = $metrics[$type] ?? 0;

            if ($currentVal >= $threshold) {
                $reason = "Verified {$type} reached {$currentVal} (target: {$threshold}).";
                $stmtAward->execute([
                    ':uid'    => $userId,
                    ':aid'    => (int)$ach['id'],
                    ':reason' => $reason,
                ]);

                if ($stmtAward->rowCount() > 0) {
                    $stmtNotif->execute([
                        ':uid'   => $userId,
                        ':title' => 'Achievement Unlocked: ' . $ach['title'],
                        ':msg'   => 'Thank you for your verified community contribution! You earned "' . $ach['title'] . '": ' . $ach['description'],
                        ':aid'   => (int)$ach['id'],
                    ]);
                    $newlyAwarded[] = [
                        'id'    => (int)$ach['id'],
                        'slug'  => $ach['slug'],
                        'title' => $ach['title'],
                    ];
                }
            }
        }
    } catch (PDOException $e) {
        error_log('Achievement evaluation error: ' . $e->getMessage());
    }

    return $newlyAwarded;
}
