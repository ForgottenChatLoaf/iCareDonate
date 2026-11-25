-- iCareDonate — Full Production Migration v2
-- database/migration_complete.sql
-- MySQL 8.4 compatible (Aiven). No stored procedures. Pure SQL only.
-- Safe to run multiple times (idempotent).

-- =====================================================
-- 1. BASE TABLES
-- =====================================================

CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(255)    NOT NULL,
    `email`         VARCHAR(255)    NOT NULL UNIQUE,
    `password_hash` VARCHAR(255)    NOT NULL,
    `role`          ENUM('donor', 'requester', 'admin') NOT NULL DEFAULT 'donor',
    `created_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `donations` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `donor_id`        INT NOT NULL,
    `title`           VARCHAR(255) NOT NULL,
    `description`     TEXT NULL,
    `category`        VARCHAR(100) NULL,
    `quantity`        INT NOT NULL DEFAULT 1,
    `condition`       ENUM('new','like_new','good','fair','poor') NOT NULL DEFAULT 'good',
    `location`        VARCHAR(255) NULL,
    `barangay`        VARCHAR(100) NULL,
    `city`            VARCHAR(100) NULL,
    `handover_method` ENUM('pickup','meetup','delivery') NOT NULL DEFAULT 'pickup',
    `status`          ENUM('pending','approved','archived','rejected') NOT NULL DEFAULT 'pending',
    `image_url`       VARCHAR(255) NULL,
    `rejection_reason` TEXT NULL,
    `archived_reason`  TEXT NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_donations_donor`    (`donor_id`),
    INDEX `idx_donations_status`   (`status`),
    INDEX `idx_donations_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `requests` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `requester_id`     INT NOT NULL,
    `donation_id`      INT NOT NULL,
    `message`          TEXT NULL,
    `quantity`         INT NOT NULL DEFAULT 1,
    `urgency`          ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    `proof_type`       VARCHAR(100) NULL,
    `proof_file_path`  VARCHAR(255) NULL,
    `status`           ENUM('pending','approved','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
    `rejection_reason` TEXT NULL,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_requests_requester` (`requester_id`),
    INDEX `idx_requests_donation`  (`donation_id`),
    INDEX `idx_requests_status`    (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `handovers` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `request_id`    INT NOT NULL,
    `donation_id`   INT NOT NULL,
    `donor_id`      INT NOT NULL,
    `requester_id`  INT NOT NULL,
    `status`        ENUM('scheduled','completed','no_show','cancelled') NOT NULL DEFAULT 'scheduled',
    `scheduled_at`  DATETIME NULL,
    `completed_at`  DATETIME NULL,
    `notes`         TEXT NULL,
    `confirmed_by`  INT NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_handovers_request`   (`request_id`),
    INDEX `idx_handovers_status`    (`status`),
    INDEX `idx_handovers_donor`     (`donor_id`),
    INDEX `idx_handovers_requester` (`requester_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT NOT NULL,
    `type`         VARCHAR(50) NOT NULL,
    `title`        VARCHAR(255) NOT NULL,
    `message`      TEXT NOT NULL,
    `is_read`      TINYINT(1) NOT NULL DEFAULT 0,
    `related_type` VARCHAR(50) NULL,
    `related_id`   INT NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_notifications_user`    (`user_id`),
    INDEX `idx_notifications_read`    (`is_read`),
    INDEX `idx_notifications_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_tickets` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT NULL,
    `name`        VARCHAR(255) NOT NULL,
    `email`       VARCHAR(255) NOT NULL,
    `subject`     VARCHAR(255) NOT NULL,
    `category`    VARCHAR(100) NOT NULL DEFAULT 'general',
    `message`     TEXT NOT NULL,
    `status`      ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
    `priority`    ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    `admin_reply` TEXT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tickets_user`   (`user_id`),
    INDEX `idx_tickets_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    INDEX `idx_audit_logs_action`  (`action`),
    INDEX `idx_audit_logs_actor`   (`actor_id`),
    INDEX `idx_audit_logs_target`  (`target_type`, `target_id`),
    INDEX `idx_audit_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL UNIQUE,
    `slug`        VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `icon`        VARCHAR(50)  NULL DEFAULT 'package',
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `sort_order`  INT          NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_categories_slug`   (`slug`),
    INDEX `idx_categories_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. SEEDS
-- =====================================================

INSERT INTO `categories` (`name`, `slug`, `description`, `icon`, `is_active`, `sort_order`)
VALUES
    ('Food & Groceries',      'food-groceries',    'Non-perishable food, canned goods, rice packs, and basic groceries.',            'utensils',     1, 1),
    ('School Supplies',       'school-supplies',   'Backpacks, notebooks, pens, pencils, and educational kits for students.',        'book-open',    1, 2),
    ('Clothing & Shoes',      'clothing-shoes',    'Clean clothing, jackets, work boots, school uniforms, and wearable items.',      'shirt',        1, 3),
    ('Medical & First Aid',   'medical-first-aid', 'First aid kits, antiseptic supplies, basic healthcare goods.',                  'heart-pulse',  1, 4),
    ('Household Essentials',  'household-goods',   'Kitchen utensils, beddings, clean blankets, emergency flashlights, and tools.', 'home',         1, 5),
    ('Books & Educational',   'books-educational', 'Curriculum textbooks, dictionaries, storybooks, and literacy materials.',       'book',         1, 6),
    ('Toys & Children',       'toys-games',        'Developmental toys, games, and infant supplies for displaced families.',        'smile',        1, 7),
    ('Electronics & Devices', 'electronics-gadgets','Study lamps, radios, power banks, and functional student devices.',           'cpu',          1, 8),
    ('Emergency Relief',      'other-essentials',  'Hygiene care packs, water containers, tarps, and urgent relief items.',        'shield-alert', 1, 9)
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`),
    `is_active`   = VALUES(`is_active`),
    `sort_order`  = VALUES(`sort_order`);

INSERT INTO `audit_logs` (`action`, `actor_id`, `actor_name`, `actor_role`, `target_type`, `target_id`, `details`, `reason`, `ip_address`, `created_at`)
SELECT 'SYSTEM_INITIALIZATION', 1, 'System Administrator', 'admin', 'system', 1,
       'Full database schema activated.', 'Platform initialization', '127.0.0.1', NOW()
WHERE NOT EXISTS (SELECT 1 FROM `audit_logs` WHERE `action` = 'SYSTEM_INITIALIZATION');

INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `created_at`)
VALUES
    ('System Administrator', 'admin@icaredonate.org',  '$2y$10$7JGU6jYUc1h3WsZP9UUnweHpgAsHOHaNCGFUecXxoaPr.RTZg3gOm', 'admin', NOW()),
    ('Admin Officer',        'admin@icaredonate.test', '$2y$10$7JGU6jYUc1h3WsZP9UUnweHpgAsHOHaNCGFUecXxoaPr.RTZg3gOm', 'admin', NOW())
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `role` = VALUES(`role`);
