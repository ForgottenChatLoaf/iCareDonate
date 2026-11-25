-- =============================================================================
-- iCareDonate — Reconstructed Production Relational Database Schema
-- File: database/reconstructed_schema.sql
-- Compatibility: MySQL 8.0+, MariaDB 10.4+ (InnoDB engine, utf8mb4)
-- Enforces referential integrity, foreign key constraints, and indexing.
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `icaredonate`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `icaredonate`;

-- -----------------------------------------------------------------------------
-- 1. USERS & AUTHENTICATION
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(255) NOT NULL,
    `first_name`    VARCHAR(100) NULL,
    `last_name`     VARCHAR(100) NULL,
    `email`         VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `phone`         VARCHAR(50)  NULL,
    `role`          ENUM('donor', 'requester', 'admin', 'head_admin', 'moderator', 'superadmin', 'donation_admin', 'campaign_admin') NOT NULL DEFAULT 'donor',
    `status`        ENUM('active', 'pending_verification', 'suspended', 'banned') NOT NULL DEFAULT 'active',
    `status_reason` TEXT NULL,
    `city`          VARCHAR(100) NULL,
    `barangay`      VARCHAR(100) NULL,
    `province`      VARCHAR(100) NULL DEFAULT 'Metro Manila',
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. USER SESSIONS (Persistent Bearer Token Hashes)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT NOT NULL,
    `token`      VARCHAR(64) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_user_sessions_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_sessions_token` (`token`),
    INDEX `idx_sessions_user_expires` (`user_id`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. CATEGORIES MASTER
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL UNIQUE,
    `slug`        VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `icon`        VARCHAR(50)  NULL DEFAULT 'package',
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `sort_order`  INT          NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_categories_slug` (`slug`),
    INDEX `idx_categories_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. DONATIONS (Listings offered by Donors)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `donations` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `donor_id`         INT NOT NULL,
    `title`            VARCHAR(255) NOT NULL,
    `description`      TEXT NULL,
    `category_id`      INT NULL,
    `category`         VARCHAR(100) NOT NULL DEFAULT 'other-essentials',
    `quantity`         INT NOT NULL DEFAULT 1,
    `unit`             VARCHAR(50) NOT NULL DEFAULT 'items',
    `condition`        ENUM('new', 'like_new', 'good', 'fair', 'poor') NOT NULL DEFAULT 'good',
    `location`         VARCHAR(255) NULL,
    `barangay`         VARCHAR(100) NULL,
    `city`             VARCHAR(100) NULL,
    `handover_method`  ENUM('pickup', 'meetup', 'delivery') NOT NULL DEFAULT 'pickup',
    `status`           ENUM('pending', 'approved', 'claimed', 'completed', 'cancelled', 'rejected', 'archived') NOT NULL DEFAULT 'pending',
    `image_url`        VARCHAR(255) NULL,
    `rejection_reason` TEXT NULL,
    `archived_reason`  TEXT NULL,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_donations_donor`
        FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_donations_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
    INDEX `idx_donations_donor` (`donor_id`),
    INDEX `idx_donations_status` (`status`),
    INDEX `idx_donations_category` (`category`),
    INDEX `idx_donations_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. REQUESTS (Beneficiary assistance requests)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `requests` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `requester_id`     INT NOT NULL,
    `donation_id`      INT NULL,
    `item_title`       VARCHAR(255) NULL,
    `message`          TEXT NULL,
    `quantity`         INT NOT NULL DEFAULT 1,
    `unit`             VARCHAR(50) NOT NULL DEFAULT 'items',
    `urgency`          ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Medium',
    `proof_type`       VARCHAR(100) NULL,
    `proof_file_path`  VARCHAR(255) NULL,
    `contact_number`   VARCHAR(50)  NULL,
    `barangay`         VARCHAR(100) NULL,
    `city`             VARCHAR(100) NULL,
    `complete_address` TEXT NULL,
    `status`           ENUM('pending', 'under_review', 'approved', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    `rejection_reason` TEXT NULL,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_requests_requester`
        FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_requests_donation`
        FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE SET NULL,
    INDEX `idx_requests_requester` (`requester_id`),
    INDEX `idx_requests_donation` (`donation_id`),
    INDEX `idx_requests_status` (`status`),
    INDEX `idx_requests_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. HANDOVERS (Matched physical exchanges and transaction pipeline)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `handovers` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `request_id`   INT NOT NULL,
    `donation_id`  INT NOT NULL,
    `donor_id`     INT NOT NULL,
    `requester_id` INT NOT NULL,
    `status`       ENUM('scheduled', 'in_progress', 'completed', 'no_show', 'cancelled') NOT NULL DEFAULT 'scheduled',
    `scheduled_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `notes`        TEXT NULL,
    `confirmed_by` INT NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_handovers_request`
        FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_handovers_donation`
        FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_handovers_donor`
        FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_handovers_requester`
        FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_handovers_confirmed_by`
        FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_handovers_status` (`status`),
    INDEX `idx_handovers_request` (`request_id`),
    INDEX `idx_handovers_donation` (`donation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compatibility View: alias 'transactions' to 'handovers'
CREATE OR REPLACE VIEW `transactions` AS SELECT * FROM `handovers`;

-- -----------------------------------------------------------------------------
-- 7. NOTIFICATIONS (User and system alert messages)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT NOT NULL,
    `type`         VARCHAR(50)  NOT NULL DEFAULT 'system',
    `title`        VARCHAR(255) NOT NULL,
    `message`      TEXT NOT NULL,
    `is_read`      TINYINT(1)   NOT NULL DEFAULT 0,
    `related_type` VARCHAR(50)  NULL,
    `related_id`   INT NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notifications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_notifications_user_read` (`user_id`, `is_read`),
    INDEX `idx_notifications_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. SUPPORT TICKETS
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `support_tickets` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT NULL,
    `name`        VARCHAR(255) NOT NULL,
    `email`       VARCHAR(255) NOT NULL,
    `subject`     VARCHAR(255) NOT NULL,
    `category`    VARCHAR(100) NOT NULL DEFAULT 'general',
    `message`     TEXT NOT NULL,
    `status`      ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    `priority`    ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    `admin_reply` TEXT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_support_tickets_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_tickets_user` (`user_id`),
    INDEX `idx_tickets_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. AUDIT LOGS (Persistent compliance trail for administrative actions)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `action`      VARCHAR(100) NOT NULL,
    `actor_id`    INT NULL,
    `actor_name`  VARCHAR(255) NOT NULL DEFAULT 'Administrator',
    `actor_role`  VARCHAR(50)  NOT NULL DEFAULT 'admin',
    `target_type` VARCHAR(50)  NOT NULL,
    `target_id`   INT NULL,
    `details`     TEXT NULL,
    `reason`      TEXT NULL,
    `ip_address`  VARCHAR(45)  NULL DEFAULT '127.0.0.1',
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_logs_actor`
        FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_audit_logs_action` (`action`),
    INDEX `idx_audit_logs_actor` (`actor_id`),
    INDEX `idx_audit_logs_target` (`target_type`, `target_id`),
    INDEX `idx_audit_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. FAQS (Platform content)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `faqs` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `category`   VARCHAR(100) NOT NULL DEFAULT 'general',
    `question`   VARCHAR(255) NOT NULL,
    `answer`     TEXT NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_faqs_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. INTERVIEWS (Beneficiary verification schedules)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `interviews` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `requester_id` INT NOT NULL,
    `admin_id`     INT NULL,
    `scheduled_at` DATETIME NULL,
    `status`       ENUM('pending', 'scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `notes`        TEXT NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_interviews_requester`
        FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_interviews_admin`
        FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_interviews_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SYSTEM CONFIGURATION SEED DATA (Legitimate Configuration Only - No Mock Activity)
-- =============================================================================

-- Master Category Taxonomies
INSERT INTO `categories` (`name`, `slug`, `description`, `icon`, `is_active`, `sort_order`)
VALUES
    ('Food & Groceries',          'food-groceries',      'Non-perishable food, canned goods, rice packs, and basic groceries.',            'utensils',     1, 1),
    ('School Supplies',           'school-supplies',     'Backpacks, notebooks, pens, pencils, and educational kits for students.',        'book-open',    1, 2),
    ('Clothing & Shoes',          'clothing-shoes',      'Clean clothing, jackets, work boots, school uniforms, and wearable items.',      'shirt',        1, 3),
    ('Medical & First Aid',       'medical-first-aid',   'First aid kits, antiseptic supplies, basic healthcare goods, and OTC items.',     'heart-pulse',  1, 4),
    ('Household Essentials',      'household-goods',     'Kitchen utensils, beddings, clean blankets, emergency flashlights, and tools.', 'home',         1, 5),
    ('Books & Educational',       'books-educational',   'Curriculum textbooks, dictionaries, storybooks, and literacy materials.',       'book',         1, 6),
    ('Toys & Children',           'toys-games',          'Developmental toys, games, and infant supplies for displaced families.',        'smile',        1, 7),
    ('Electronics & Devices',     'electronics-gadgets', 'Study lamps, radios, power banks, and functional student devices.',             'cpu',          1, 8),
    ('Emergency Disaster Relief', 'other-essentials',    'Hygiene care packs, water containers, tarps, and urgent relief items.',         'shield-alert', 1, 9)
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`),
    `is_active`   = VALUES(`is_active`),
    `sort_order`  = VALUES(`sort_order`);

-- Initial Head Administrator Provisioning (Bcrypt Hash)
-- Note: Additional administrators (Donation Admin, Campaign Admin, Moderator)
-- are provisioned securely by the Head Administrator via the Admin Portal
-- (never via public registration or mock donor/requester seed accounts).
INSERT INTO `users` (`name`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `status`, `city`, `barangay`)
VALUES
    ('Head Administrator', 'Head', 'Administrator', 'admin@icaredonate.org', '$2y$10$7JGU6jYUc1h3WsZP9UUnweHpgAsHOHaNCGFUecXxoaPr.RTZg3gOm', 'admin', 'active', 'Quezon City', 'Central')
ON DUPLICATE KEY UPDATE
    `name`   = VALUES(`name`),
    `role`   = VALUES(`role`),
    `status` = VALUES(`status`);

-- System Initialization Audit Log
INSERT INTO `audit_logs` (`action`, `actor_id`, `actor_name`, `actor_role`, `target_type`, `target_id`, `details`, `reason`, `ip_address`, `created_at`)
SELECT 'SYSTEM_INITIALIZATION', 1, 'Head Administrator', 'admin', 'system', 1,
       'Clean-slate relational schema initialized with Head Administrator and category taxonomy.', 'Schema reconstruction', '127.0.0.1', NOW()
WHERE NOT EXISTS (SELECT 1 FROM `audit_logs` WHERE `action` = 'SYSTEM_INITIALIZATION');
