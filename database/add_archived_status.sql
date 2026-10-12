-- ============================================================
-- Migration: Add 'archived' donation status + archive metadata
-- Run this BEFORE deploying the PHP archive/restore feature.
-- Engine: MariaDB / MySQL compatible.
-- Safe to rerun: uses idempotent existence checks.
-- ============================================================

USE `icare_donate`;

-- 1. Modify donations status ENUM to include 'archived'
ALTER TABLE `donations`
  MODIFY COLUMN `status` ENUM(
    'draft',
    'pending_verification',
    'needs_information',
    'approved',
    'rejected',
    'fully_allocated',
    'expired',
    'cancelled',
    'archived'
  ) NOT NULL DEFAULT 'draft';

-- 2. Add archive tracking columns
ALTER TABLE `donations`
  ADD COLUMN IF NOT EXISTS `archived_at`          DATETIME     DEFAULT NULL AFTER `approved_by`,
  ADD COLUMN IF NOT EXISTS `archived_by`          INT UNSIGNED DEFAULT NULL AFTER `archived_at`,
  ADD COLUMN IF NOT EXISTS `archive_reason`       TEXT         DEFAULT NULL AFTER `archived_by`,
  ADD COLUMN IF NOT EXISTS `archived_prev_status`  VARCHAR(50)  DEFAULT NULL AFTER `archive_reason`;

-- 3. Add FK for archived_by -> users (safe check)
SET @fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'donations'
    AND CONSTRAINT_NAME = 'fk_donations_archived_by'
);

SET @sql_add_fk = IF(@fk_exists = 0,
  'ALTER TABLE `donations` ADD CONSTRAINT `fk_donations_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users`(`id`) ON DELETE SET NULL',
  'SELECT "fk_donations_archived_by already exists"'
);

PREPARE stmt_add_fk FROM @sql_add_fk;
EXECUTE stmt_add_fk;
DEALLOCATE PREPARE stmt_add_fk;

-- 4. Add index for efficient archived listing queries
SET @idx_exists = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'donations'
    AND INDEX_NAME = 'idx_donations_archived'
);

SET @sql_add_idx = IF(@idx_exists = 0,
  'CREATE INDEX `idx_donations_archived` ON `donations` (`status`, `archived_at`)',
  'SELECT "idx_donations_archived already exists"'
);

PREPARE stmt_add_idx FROM @sql_add_idx;
EXECUTE stmt_add_idx;
DEALLOCATE PREPARE stmt_add_idx;
