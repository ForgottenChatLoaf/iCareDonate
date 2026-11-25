-- =============================================================================
-- iCareDonate — CRUD Repair, Support Messaging & Password Reset Migration
-- File: database/migration_crud_support_security.sql
-- Idempotent migration: safe to run multiple times on any target database.
-- =============================================================================

-- 1. Expand users.role ENUM to include support_admin and add department column
ALTER TABLE `users`
    MODIFY COLUMN `role` ENUM(
        'donor',
        'requester',
        'admin',
        'head_admin',
        'moderator',
        'superadmin',
        'donation_admin',
        'campaign_admin',
        'support_admin'
    ) NOT NULL DEFAULT 'donor';

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `department` VARCHAR(120) NULL AFTER `barangay`;

-- 2. Secure single-use password reset tokens table
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL DEFAULT NULL,
    `created_by` INT NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_password_resets_token_hash` (`token_hash`),
    KEY `idx_password_resets_user` (`user_id`),
    CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Internal Customer Support Conversations
CREATE TABLE IF NOT EXISTS `support_conversations` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `assigned_admin_id` INT NULL DEFAULT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'general',
    `donation_id` INT NULL DEFAULT NULL,
    `request_id` INT NULL DEFAULT NULL,
    `status` ENUM('open', 'waiting_support', 'waiting_user', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    `escalated_to_role` VARCHAR(50) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `resolved_at` DATETIME NULL DEFAULT NULL,
    `closed_at` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sc_user_status` (`user_id`, `status`),
    KEY `idx_sc_assigned` (`assigned_admin_id`),
    KEY `idx_sc_donation` (`donation_id`),
    KEY `idx_sc_request` (`request_id`),
    CONSTRAINT `fk_sc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sc_assigned` FOREIGN KEY (`assigned_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_sc_donation` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_sc_request` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Internal Customer Support Messages (including private internal notes)
CREATE TABLE IF NOT EXISTS `support_messages` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `conversation_id` INT NOT NULL,
    `sender_id` INT NOT NULL,
    `sender_type` ENUM('user', 'support_admin', 'admin', 'system') NOT NULL DEFAULT 'user',
    `message` TEXT NOT NULL,
    `is_internal_note` TINYINT(1) NOT NULL DEFAULT 0,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sm_conv_created` (`conversation_id`, `created_at`),
    KEY `idx_sm_sender` (`sender_id`),
    CONSTRAINT `fk_sm_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `support_conversations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sm_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
