-- ============================================================
-- Migration Rollback: Remove 'archived' status and archive metadata
-- Engine: MariaDB / MySQL compatible.
-- Safe procedure:
-- 1. Handles any records currently in 'archived' status by safely
--    reverting them to 'archived_prev_status' (or fallback 'cancelled').
-- 2. Removes foreign key and index.
-- 3. Drops archive tracking columns.
-- 4. Reverts the donations status ENUM to its original 8 values.
-- ============================================================

USE `icare_donate`;

-- Step 1: Safely revert any existing archived records to their previous status
-- (or fallback to 'cancelled' if previous status is NULL/invalid)
UPDATE `donations`
SET `status` = COALESCE(
  CASE
    WHEN `archived_prev_status` IN ('draft','pending_verification','needs_information','approved','rejected','fully_allocated','expired','cancelled')
    THEN `archived_prev_status`
    ELSE 'cancelled'
  END,
  'cancelled'
)
WHERE `status` = 'archived';

-- Step 2: Drop foreign key constraint if it exists
SET @fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'donations'
    AND CONSTRAINT_NAME = 'fk_donations_archived_by'
);

SET @sql_drop_fk = IF(@fk_exists > 0,
  'ALTER TABLE `donations` DROP FOREIGN KEY `fk_donations_archived_by`',
  'SELECT "fk_donations_archived_by does not exist"'
);

PREPARE stmt_drop_fk FROM @sql_drop_fk;
EXECUTE stmt_drop_fk;
DEALLOCATE PREPARE stmt_drop_fk;

-- Step 3: Drop index if it exists
SET @idx_exists = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'donations'
    AND INDEX_NAME = 'idx_donations_archived'
);

SET @sql_drop_idx = IF(@idx_exists > 0,
  'DROP INDEX `idx_donations_archived` ON `donations`',
  'SELECT "idx_donations_archived does not exist"'
);

PREPARE stmt_drop_idx FROM @sql_drop_idx;
EXECUTE stmt_drop_idx;
DEALLOCATE PREPARE stmt_drop_idx;

-- Step 4: Drop archive tracking columns
ALTER TABLE `donations`
  DROP COLUMN IF EXISTS `archived_prev_status`,
  DROP COLUMN IF EXISTS `archive_reason`,
  DROP COLUMN IF EXISTS `archived_by`,
  DROP COLUMN IF EXISTS `archived_at`;

-- Step 5: Revert donations status ENUM to original 8 states
ALTER TABLE `donations`
  MODIFY COLUMN `status` ENUM(
    'draft',
    'pending_verification',
    'needs_information',
    'approved',
    'rejected',
    'fully_allocated',
    'expired',
    'cancelled'
  ) NOT NULL DEFAULT 'draft';
