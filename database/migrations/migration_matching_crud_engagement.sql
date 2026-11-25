-- =============================================================================
-- iCareDonate — Resource Matching, Complete CRUD & Community Engagement Migration
-- File: database/migration_matching_crud_engagement.sql
--
-- CRITICAL: Zero automatic demo data, zero fake donations, zero fake requests,
-- zero fake users. Only schema structures, indexes, foreign keys, and legitimate
-- achievement definitions.
-- =============================================================================

USE `icaredonate`;

-- 1. Extend donations & requests for partial fulfillment and category matching
ALTER TABLE `donations`
    ADD COLUMN IF NOT EXISTS `allocated_quantity` INT NOT NULL DEFAULT 0 AFTER `quantity`,
    ADD COLUMN IF NOT EXISTS `campaign_id` INT NULL AFTER `category_id`;

ALTER TABLE `requests`
    ADD COLUMN IF NOT EXISTS `category` VARCHAR(100) NULL AFTER `item_title`,
    ADD COLUMN IF NOT EXISTS `fulfilled_quantity` INT NOT NULL DEFAULT 0 AFTER `quantity`;

-- 2. CAMPAIGNS (Managed by Campaign Administrator & Head Administrator)
CREATE TABLE IF NOT EXISTS `campaigns` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `title`           VARCHAR(255) NOT NULL,
    `slug`            VARCHAR(255) NOT NULL UNIQUE,
    `description`     TEXT NULL,
    `category_id`     INT NULL,
    `category`        VARCHAR(100) NULL,
    `target_quantity` INT NOT NULL DEFAULT 100,
    `target_unit`     VARCHAR(50) NOT NULL DEFAULT 'items',
    `start_date`      DATE NULL,
    `end_date`        DATE NULL,
    `status`          ENUM('draft', 'active', 'completed', 'cancelled', 'archived') NOT NULL DEFAULT 'active',
    `created_by`      INT NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_campaigns_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_campaigns_creator`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_campaigns_status` (`status`),
    INDEX `idx_campaigns_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. DONATION ALLOCATIONS (Resource Matching & Allocation Lifecycle)
CREATE TABLE IF NOT EXISTS `donation_allocations` (
    `id`                 INT AUTO_INCREMENT PRIMARY KEY,
    `donation_id`        INT NOT NULL,
    `request_id`         INT NOT NULL,
    `allocated_quantity` INT NOT NULL,
    `match_score`        DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `match_classification` ENUM('Strong Match', 'Potential Match', 'Weak Match', 'Manual') NOT NULL DEFAULT 'Manual',
    `match_explanation`  TEXT NULL,
    `status`             ENUM(
        'suggested',
        'pending_review',
        'approved',
        'preparing',
        'ready_for_handover',
        'completed',
        'cancelled',
        'rejected'
    ) NOT NULL DEFAULT 'pending_review',
    `approved_by`        INT NULL,
    `rejection_reason`   TEXT NULL,
    `notes`              TEXT NULL,
    `completed_at`       DATETIME NULL,
    `created_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_alloc_donation`
        FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_alloc_request`
        FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_alloc_approver`
        FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_alloc_donation` (`donation_id`),
    INDEX `idx_alloc_request` (`request_id`),
    INDEX `idx_alloc_status` (`status`),
    INDEX `idx_alloc_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. RESOURCE MATCH LOGS (Immutable allocation transition history)
CREATE TABLE IF NOT EXISTS `resource_match_logs` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `allocation_id`   INT NULL,
    `donation_id`     INT NOT NULL,
    `request_id`      INT NOT NULL,
    `action`          VARCHAR(80) NOT NULL,
    `actor_id`        INT NULL,
    `previous_status` VARCHAR(50) NULL,
    `new_status`      VARCHAR(50) NULL,
    `details`         TEXT NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_match_log_alloc`
        FOREIGN KEY (`allocation_id`) REFERENCES `donation_allocations` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_match_log_actor`
        FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_match_log_alloc` (`allocation_id`),
    INDEX `idx_match_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ACHIEVEMENTS (System configuration for verified participation milestones)
CREATE TABLE IF NOT EXISTS `achievements` (
    `id`                 INT AUTO_INCREMENT PRIMARY KEY,
    `slug`               VARCHAR(80) NOT NULL UNIQUE,
    `title`              VARCHAR(120) NOT NULL,
    `description`        VARCHAR(255) NOT NULL,
    `icon`               VARCHAR(50) NOT NULL DEFAULT 'award',
    `badge_color`        VARCHAR(30) NOT NULL DEFAULT '#1F4FA3',
    `criteria_type`      VARCHAR(60) NOT NULL,
    `criteria_threshold` INT NOT NULL DEFAULT 1,
    `is_active`          TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. USER ACHIEVEMENTS (Earned strictly from verified application outcomes)
CREATE TABLE IF NOT EXISTS `user_achievements` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`        INT NOT NULL,
    `achievement_id` INT NOT NULL,
    `earned_reason`  VARCHAR(255) NOT NULL,
    `earned_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_user_achievement` (`user_id`, `achievement_id`),
    CONSTRAINT `fk_ua_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ua_achievement`
        FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE,
    INDEX `idx_ua_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. USER RECOGNITION PREFERENCES (Privacy-safe recognition opt-in/out)
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `recognition_opt_in` TINYINT(1) NOT NULL DEFAULT 1 AFTER `province`;

-- Legitimate System Configuration: Achievement Definitions (No fake user awards)
INSERT INTO `achievements` (`slug`, `title`, `description`, `icon`, `badge_color`, `criteria_type`, `criteria_threshold`, `is_active`)
VALUES
    ('first-contribution',     'First Contribution',     'Completed your first verified donation.',                         'heart',       '#1F4FA3', 'verified_donations',    1,  1),
    ('community-helper',       'Community Helper',       'Completed 3 or more verified donations for community families.',  'users',       '#0F766E', 'verified_donations',    3,  1),
    ('first-successful-match', 'First Successful Match', 'Had a donation successfully allocated and delivered to a need.',  'check-circle','#15803D', 'completed_allocations', 1,  1),
    ('consistent-contributor', 'Consistent Contributor', 'Maintained verified contributions across multiple months.',       'calendar',    '#7E22CE', 'active_months',         2,  1),
    ('campaign-supporter',     'Campaign Supporter',     'Contributed verified resources to an active community campaign.', 'flag',        '#D97706', 'campaign_donations',    1,  1),
    ('community-champion',     'Community Champion',     'Reached 10 verified completed donations on iCareDonate.',         'award',       '#B45309', 'verified_donations',    10, 1)
ON DUPLICATE KEY UPDATE
    `title`              = VALUES(`title`),
    `description`        = VALUES(`description`),
    `criteria_threshold` = VALUES(`criteria_threshold`),
    `is_active`          = VALUES(`is_active`);
