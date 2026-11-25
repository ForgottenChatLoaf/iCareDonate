-- iCareDonate — Migration: Dashboard & Notifications Tables
-- database/migration_dashboard_notifications.sql
-- MySQL 8.4-compatible schema migration
--
-- Safe migration: preserves all existing users and tables.
-- Run this migration manually in your MySQL client / Aiven Console.
-- DO NOT drop existing tables.

-- 1. Donations Table
CREATE TABLE IF NOT EXISTS `donations` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`           INT NOT NULL,
    `title`             VARCHAR(255) NOT NULL,
    `category`          VARCHAR(100) NOT NULL DEFAULT 'other-essentials',
    `description`       TEXT NULL,
    `quantity`          INT NOT NULL DEFAULT 1,
    `unit`              VARCHAR(50) NOT NULL DEFAULT 'items',
    `condition_type`    VARCHAR(50) NOT NULL DEFAULT 'good',
    `status`            ENUM('pending', 'approved', 'claimed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `pickup_method`     VARCHAR(50) NOT NULL DEFAULT 'pickup',
    `pickup_city`       VARCHAR(100) NULL,
    `pickup_barangay`   VARCHAR(100) NULL,
    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_donations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_donations_user_id` (`user_id`),
    INDEX `idx_donations_status` (`status`),
    INDEX `idx_donations_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Assistance Requests Table
CREATE TABLE IF NOT EXISTS `requests` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`           INT NOT NULL,
    `donation_id`       INT NULL,
    `item_title`        VARCHAR(255) NOT NULL,
    `quantity`          INT NOT NULL DEFAULT 1,
    `unit`              VARCHAR(50) NOT NULL DEFAULT 'items',
    `reason`            TEXT NULL,
    `proof_type`        VARCHAR(100) NULL,
    `status`            ENUM('pending', 'under_review', 'approved', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    `requester_name`    VARCHAR(255) NULL,
    `contact_number`    VARCHAR(50) NULL,
    `barangay`          VARCHAR(100) NULL,
    `city`              VARCHAR(100) NULL,
    `complete_address`  TEXT NULL,
    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_requests_donation` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE SET NULL,
    INDEX `idx_requests_user_id` (`user_id`),
    INDEX `idx_requests_status` (`status`),
    INDEX `idx_requests_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`           INT NOT NULL,
    `title`             VARCHAR(255) NOT NULL,
    `message`           TEXT NOT NULL,
    `type`              VARCHAR(50) NOT NULL DEFAULT 'system',
    `is_read`           TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_notifications_user_read` (`user_id`, `is_read`),
    INDEX `idx_notifications_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. User Sessions Table (Persistent Authentication Tokens across proxy / restarts)
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`           INT NOT NULL,
    `token`             VARCHAR(64) NOT NULL UNIQUE,
    `expires_at`        DATETIME NOT NULL,
    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_user_sessions_token` (`token`),
    INDEX `idx_user_sessions_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
