-- iCareDonate — Migration: Admin Functionalization & Audit Logging
-- database/migration_admin_functionalization.sql
-- MySQL 8.4 & MariaDB compatible.
-- Safe, idempotent migration: preserves all existing data and tables.
-- Run in Aiven Console / MySQL client.

-- 1. Ensure `status` and moderation columns exist on `users` table
-- (Users may be active, pending_verification, suspended, or banned)
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `phone` VARCHAR(50) NULL AFTER `email`,
    ADD COLUMN IF NOT EXISTS `city` VARCHAR(100) NULL AFTER `phone`,
    ADD COLUMN IF NOT EXISTS `barangay` VARCHAR(100) NULL AFTER `city`,
    ADD COLUMN IF NOT EXISTS `status` ENUM('active', 'pending_verification', 'suspended', 'banned') NOT NULL DEFAULT 'active' AFTER `role`,
    ADD COLUMN IF NOT EXISTS `status_reason` TEXT NULL AFTER `status`;

-- 2. Ensure moderation and proof columns exist on `donations` table
ALTER TABLE `donations`
    ADD COLUMN IF NOT EXISTS `image_url` VARCHAR(255) NULL AFTER `description`,
    ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `archived_reason` TEXT NULL AFTER `rejection_reason`;

-- 3. Ensure proof inspection and match columns exist on `requests` table
ALTER TABLE `requests`
    ADD COLUMN IF NOT EXISTS `urgency` ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Medium' AFTER `quantity`,
    ADD COLUMN IF NOT EXISTS `proof_file_path` VARCHAR(255) NULL AFTER `proof_type`,
    ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT NULL AFTER `status`;

-- 4. Audit Logs Table (Persistent record of consequential administrator actions)
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `action`        VARCHAR(100) NOT NULL,
    `actor_id`      INT NULL,
    `actor_name`    VARCHAR(255) NOT NULL DEFAULT 'Administrator',
    `actor_role`    VARCHAR(50) NOT NULL DEFAULT 'admin',
    `target_type`   VARCHAR(50) NOT NULL,
    `target_id`     INT NULL,
    `details`       TEXT NULL,
    `reason`        TEXT NULL,
    `ip_address`    VARCHAR(45) NULL DEFAULT '127.0.0.1',
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_logs_action` (`action`),
    INDEX `idx_audit_logs_actor` (`actor_id`),
    INDEX `idx_audit_logs_target` (`target_type`, `target_id`),
    INDEX `idx_audit_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Master Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(100) NOT NULL UNIQUE,
    `slug`          VARCHAR(100) NOT NULL UNIQUE,
    `description`   TEXT NULL,
    `icon`          VARCHAR(50) NULL DEFAULT 'package',
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`    INT NOT NULL DEFAULT 0,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_categories_slug` (`slug`),
    INDEX `idx_categories_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Seed default categories if table is empty
INSERT INTO `categories` (`name`, `slug`, `description`, `icon`, `is_active`, `sort_order`)
VALUES
    ('Food & Groceries', 'food-groceries', 'Non-perishable food, canned goods, rice packs, and basic groceries.', 'utensils', 1, 1),
    ('School Supplies', 'school-supplies', 'Backpacks, notebooks, pens, pencils, and educational kits for students.', 'book-open', 1, 2),
    ('Clothing & Shoes', 'clothing-shoes', 'Clean clothing, jackets, work boots, school uniforms, and wearable items.', 'shirt', 1, 3),
    ('Medical & First Aid', 'medical-first-aid', 'First aid kits, antiseptic supplies, basic healthcare goods, and OTC wellness items.', 'heart-pulse', 1, 4),
    ('Household Essentials', 'household-goods', 'Kitchen utensils, beddings, clean blankets, emergency flashlights, and tools.', 'home', 1, 5),
    ('Books & Educational', 'books-educational', 'Curriculum textbooks, dictionaries, storybooks, and literacy materials.', 'book', 1, 6),
    ('Toys & Children', 'toys-games', 'Developmental toys, games, and infant supplies for displaced families.', 'smile', 1, 7),
    ('Electronics & Devices', 'electronics-gadgets', 'Study lamps, radios, power banks, and functional student devices.', 'cpu', 1, 8),
    ('Emergency Disaster Relief', 'other-essentials', 'Hygiene care packs, water containers, tarps, and urgent relief items.', 'shield-alert', 1, 9)
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `is_active` = VALUES(`is_active`), `sort_order` = VALUES(`sort_order`);

-- 7. Seed initial admin audit log entry if table is empty
INSERT INTO `audit_logs` (`action`, `actor_id`, `actor_name`, `actor_role`, `target_type`, `target_id`, `details`, `reason`, `ip_address`, `created_at`)
SELECT 'SYSTEM_INITIALIZATION', 1, 'System Administrator', 'admin', 'system', 1, 'Admin moderation and audit system activated.', 'Platform initialization', '127.0.0.1', NOW()
WHERE NOT EXISTS (SELECT 1 FROM `audit_logs` WHERE `action` = 'SYSTEM_INITIALIZATION');
