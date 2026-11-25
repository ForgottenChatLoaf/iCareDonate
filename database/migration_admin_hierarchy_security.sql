-- =============================================================================
-- iCareDonate — Head Administrator Normalization & Security Columns Migration
-- File: database/migration_admin_hierarchy_security.sql
-- =============================================================================

USE `icaredonate`;

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password_hash`,
    ADD COLUMN IF NOT EXISTS `last_login_at` DATETIME NULL AFTER `updated_at`;

-- Normalize existing Head Administrator account (no duplicate created)
UPDATE `users`
SET `role` = 'head_admin',
    `name` = 'Ethan McClarence P. Gacud',
    `first_name` = 'Ethan McClarence',
    `last_name` = 'Gacud'
WHERE `email` = 'admin@icaredonate.org';
