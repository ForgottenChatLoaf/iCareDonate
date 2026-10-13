-- Optional: Uncomment if running on a local server with root privileges
-- CREATE DATABASE IF NOT EXISTS `icare_donate` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `icare_donate`;

-- 1. categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `icon` VARCHAR(50),
  `sort_order` SMALLINT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30),
  `barangay` VARCHAR(150),
  `city` VARCHAR(100),
  `province` VARCHAR(100),
  `role` ENUM('user','admin','superadmin') NOT NULL DEFAULT 'user',
  `status` ENUM('pending_verification','active','suspended','banned') NOT NULL DEFAULT 'pending_verification',
  `email_verified_at` DATETIME,
  `suspension_reason` TEXT,
  `suspended_at` DATETIME,
  `suspended_by` INT UNSIGNED,
  `banned_at` DATETIME,
  `banned_by` INT UNSIGNED,
  `profile_photo` VARCHAR(500),
  `last_login_at` DATETIME,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`suspended_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`banned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. user_sessions
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` VARCHAR(128) PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(500),
  `payload` TEXT,
  `last_activity` DATETIME,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_sessions_user` (`user_id`),
  INDEX `idx_user_sessions_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. email_verifications
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(128) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_email_verifications_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. password_resets
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(128) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_password_resets_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. donations
CREATE TABLE IF NOT EXISTS `donations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `donor_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `quantity_total` INT UNSIGNED NOT NULL,
  `quantity_reserved` INT UNSIGNED NOT NULL DEFAULT 0,
  `quantity_claimed` INT UNSIGNED NOT NULL DEFAULT 0,
  `unit` VARCHAR(50) DEFAULT 'item',
  `condition_type` ENUM('new','like_new','good','fair','poor') NOT NULL,
  `expiration_date` DATE,
  `pickup_method` ENUM('pickup','delivery','both') NOT NULL,
  `pickup_address` VARCHAR(500),
  `pickup_barangay` VARCHAR(150),
  `pickup_city` VARCHAR(100),
  `pickup_province` VARCHAR(100),
  `pickup_notes` TEXT,
  `pickup_schedule` TEXT,
  `status` ENUM(
    'draft',
    'pending_verification',
    'needs_information',
    'approved',
    'rejected',
    'fully_allocated',
    'expired',
    'cancelled',
    'archived'
  ) NOT NULL DEFAULT 'draft',
  `admin_notes` TEXT,
  `rejection_reason` TEXT,
  `info_request` TEXT,
  `submitted_at` DATETIME,
  `approved_at` DATETIME,
  `approved_by` INT UNSIGNED,
  `archived_at` DATETIME DEFAULT NULL,
  `archived_by` INT UNSIGNED DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  `archived_prev_status` VARCHAR(50) DEFAULT NULL,
  `expires_at` DATETIME,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`archived_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_donations_donor` (`donor_id`),
  INDEX `idx_donations_category` (`category_id`),
  INDEX `idx_donations_status` (`status`),
  INDEX `idx_donations_archived` (`status`, `archived_at`),
  INDEX `idx_donations_expires` (`expires_at`),
  INDEX `idx_donations_browse` (`status`, `category_id`, `created_at`),
  INDEX `idx_donations_pending` (`status`, `submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. donation_images
CREATE TABLE IF NOT EXISTS `donation_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `donation_id` INT UNSIGNED NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100),
  `file_size` INT UNSIGNED,
  `sort_order` TINYINT DEFAULT 0,
  `is_primary` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`donation_id`) REFERENCES `donations`(`id`) ON DELETE CASCADE,
  INDEX `idx_donation_images_donation` (`donation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. requests
CREATE TABLE IF NOT EXISTS `requests` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `requester_id` INT UNSIGNED NOT NULL,
  `donation_id` INT UNSIGNED NOT NULL,
  `quantity_requested` INT UNSIGNED NOT NULL,
  `quantity_matched` INT UNSIGNED DEFAULT 0,
  `purpose` TEXT NOT NULL,
  `urgency_level` ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  `status` ENUM(
    'submitted',
    'proof_review',
    'needs_information',
    'approved',
    'rejected',
    'matched',
    'pickup_scheduled',
    'claimed',
    'completed',
    'cancelled',
    'expired'
  ) NOT NULL DEFAULT 'submitted',
  `admin_notes` TEXT,
  `rejection_reason` TEXT,
  `info_request` TEXT,
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` DATETIME,
  `reviewed_by` INT UNSIGNED,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`requester_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`donation_id`) REFERENCES `donations`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_requests_requester` (`requester_id`),
  INDEX `idx_requests_donation` (`donation_id`),
  INDEX `idx_requests_status` (`status`),
  INDEX `idx_requests_pending` (`status`, `submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. request_proofs
CREATE TABLE IF NOT EXISTS `request_proofs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `request_id` INT UNSIGNED NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100),
  `file_size` INT UNSIGNED,
  `description` TEXT,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE CASCADE,
  INDEX `idx_request_proofs_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. matches
CREATE TABLE IF NOT EXISTS `matches` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `donation_id` INT UNSIGNED NOT NULL,
  `request_id` INT UNSIGNED NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `match_notes` TEXT,
  `status` ENUM('active','cancelled') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`donation_id`) REFERENCES `donations`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  INDEX `idx_matches_donation` (`donation_id`),
  INDEX `idx_matches_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. transactions
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `match_id` INT UNSIGNED NOT NULL UNIQUE,
  `donor_id` INT UNSIGNED NOT NULL,
  `requester_id` INT UNSIGNED NOT NULL,
  `donation_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `status` ENUM(
    'created',
    'pending_claim',
    'claimed',
    'completed',
    'cancelled',
    'expired'
  ) NOT NULL DEFAULT 'created',
  `pickup_method` ENUM('pickup','delivery') NOT NULL,
  `pickup_date` DATE,
  `pickup_time` TIME,
  `pickup_address` VARCHAR(500),
  `claim_deadline` DATETIME NOT NULL,
  `claimed_at` DATETIME,
  `completed_at` DATETIME,
  `cancelled_at` DATETIME,
  `cancel_reason` TEXT,
  `cancelled_by` INT UNSIGNED,
  `admin_notes` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`requester_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`donation_id`) REFERENCES `donations`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`cancelled_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_transactions_donor` (`donor_id`),
  INDEX `idx_transactions_requester` (`requester_id`),
  INDEX `idx_transactions_status` (`status`),
  INDEX `idx_transactions_deadline` (`claim_deadline`),
  INDEX `idx_transactions_expiring` (`status`, `claim_deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `related_type` VARCHAR(50),
  `related_id` INT UNSIGNED,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` DATETIME,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_notifications_user` (`user_id`),
  INDEX `idx_notifications_unread` (`user_id`, `is_read`),
  INDEX `idx_notifications_type` (`type`),
  INDEX `idx_notifications_unread_count` (`user_id`, `is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. support_tickets
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `email` VARCHAR(255),
  `status` ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `assigned_to` INT UNSIGNED,
  `admin_reply` TEXT,
  `replied_at` DATETIME,
  `closed_at` DATETIME,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_support_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. problem_reports
CREATE TABLE IF NOT EXISTS `problem_reports` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reporter_id` INT UNSIGNED NOT NULL,
  `target_type` ENUM('donation','user') NOT NULL,
  `target_id` INT UNSIGNED NOT NULL,
  `reason` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `status` ENUM('pending','reviewed','resolved','dismissed') NOT NULL DEFAULT 'pending',
  `reviewed_by` INT UNSIGNED,
  `admin_notes` TEXT,
  `resolved_at` DATETIME,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_problem_reports_status` (`status`),
  INDEX `idx_problem_reports_target` (`target_type`, `target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `actor_id` INT UNSIGNED,
  `actor_role` VARCHAR(20),
  `action` VARCHAR(100) NOT NULL,
  `target_type` VARCHAR(50) NOT NULL,
  `target_id` INT UNSIGNED NOT NULL,
  `old_value` JSON,
  `new_value` JSON,
  `reason` TEXT,
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(500),
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`actor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_audit_actor` (`actor_id`),
  INDEX `idx_audit_target` (`target_type`, `target_id`),
  INDEX `idx_audit_action` (`action`),
  INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. faqs
CREATE TABLE IF NOT EXISTS `faqs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `question` VARCHAR(500) NOT NULL,
  `answer` TEXT NOT NULL,
  `category` VARCHAR(100),
  `sort_order` SMALLINT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- IMPORTANT: schema.sql creates TABLE STRUCTURES ONLY.
-- No user accounts, passwords, sample donations, or demo data
-- are included here. For data seeding, use:
--   database/seed_production.sql  -- safe for production
--   database/seed_dev.sql         -- local development only
-- ============================================================