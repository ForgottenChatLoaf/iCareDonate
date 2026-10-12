CREATE DATABASE IF NOT EXISTS `icare_donate` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `icare_donate`;

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

-- 17. Seed Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `sort_order`, `is_active`) VALUES
(1, 'Food & Groceries', 'food-groceries', 'Canned goods, dry rations, packaged staples, baby food', 'utensils', 1, 1),
(2, 'School Supplies', 'school-supplies', 'Notebooks, pens, backpacks, books, art materials', 'book-open', 2, 1),
(3, 'Clothing & Shoes', 'clothing-shoes', 'Clean wearables, uniforms, shoes, jackets for all ages', 'shirt', 3, 1),
(4, 'Medical & First Aid', 'medical-first-aid', 'Basic hygiene items, bandages, OTC medicines, medical aids', 'heart-pulse', 4, 1),
(5, 'Household Goods', 'household-goods', 'Kitchenware, bedding, small appliances, organizers', 'home', 5, 1),
(6, 'Books & Educational', 'books-educational', 'Textbooks, storybooks, reference materials', 'book', 6, 1),
(7, 'Toys & Games', 'toys-games', 'Puzzles, board games, stuffed toys, outdoor gear', 'smile', 7, 1),
(8, 'Electronics & Gadgets', 'electronics-gadgets', 'Laptops, tablets, chargers, working electronics', 'laptop', 8, 1),
(9, 'Other Essentials', 'other-essentials', 'Miscellaneous essential goods and community necessities', 'box', 9, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 18. Seed Default Users (Password: Password123!)
-- Password hash generated with password_hash('Password123!', PASSWORD_BCRYPT)
INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `barangay`, `city`, `province`, `role`, `status`, `email_verified_at`) VALUES
(1, 'admin@icaredonate.org', '$2y$10$ElABAam1kfmFsz4Nadbg0OzCyQIav2ap0L4LE6BNcRejen3mAgjDW', 'System', 'Admin', '09171234567', 'Central', 'Quezon City', 'Metro Manila', 'superadmin', 'active', NOW()),
(2, 'donor@icaredonate.org', '$2y$10$ElABAam1kfmFsz4Nadbg0OzCyQIav2ap0L4LE6BNcRejen3mAgjDW', 'Maria', 'Santos', '09189876543', 'San Antonio', 'Pasig City', 'Metro Manila', 'user', 'active', NOW()),
(3, 'requester@icaredonate.org', '$2y$10$ElABAam1kfmFsz4Nadbg0OzCyQIav2ap0L4LE6BNcRejen3mAgjDW', 'Juan', 'Dela Cruz', '09195554321', 'Poblacion', 'Makati City', 'Metro Manila', 'user', 'active', NOW())
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`), `password_hash` = VALUES(`password_hash`);

-- Sample Donations for demo
INSERT INTO `donations` (`id`, `donor_id`, `category_id`, `title`, `description`, `quantity_total`, `quantity_reserved`, `quantity_claimed`, `unit`, `condition_type`, `pickup_method`, `pickup_barangay`, `pickup_city`, `pickup_province`, `status`, `approved_at`, `approved_by`) VALUES
(1, 2, 2, 'Elementary School Backpacks & Notebooks Set', 'Brand new backpacks filled with notebooks, pens, pencils, rulers, and coloring sets suitable for Grade 1-6 students.', 25, 0, 0, 'sets', 'new', 'both', 'San Antonio', 'Pasig City', 'Metro Manila', 'approved', NOW(), 1),
(2, 2, 1, 'Assorted Canned Goods and Rice Sacks', 'Nutritious canned vegetables, tuna, sardines, and 5kg rice packs sealed and safe for family consumption.', 40, 5, 0, 'packs', 'new', 'pickup', 'San Antonio', 'Pasig City', 'Metro Manila', 'approved', NOW(), 1),
(3, 2, 8, 'Working Lenovo Student Laptops with Chargers', 'Gently used Lenovo ThinkPad laptops, re-imaged with Windows 10 and educational tools. Perfect for distance learning or college students.', 5, 0, 0, 'units', 'good', 'pickup', 'San Antonio', 'Pasig City', 'Metro Manila', 'approved', NOW(), 1),
(4, 2, 4, 'Community First Aid Kits & Hygiene Packs', 'Complete first aid boxes containing antiseptic, bandages, thermometers, face masks, and antibacterial soap.', 30, 0, 0, 'kits', 'new', 'delivery', 'San Antonio', 'Pasig City', 'Metro Manila', 'approved', NOW(), 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Sample FAQs
INSERT INTO `faqs` (`id`, `question`, `answer`, `category`, `sort_order`, `is_active`) VALUES
(1, 'How do I donate items?', 'Sign up for a free account, click "Offer Donation", fill out the item details and upload clear photos. Once approved by our team, your donation becomes visible to community members in need.', 'Donating', 1, 1),
(2, 'Is iCareDonate free to use?', 'Yes, 100% free! We never charge donors or requesters any fees for listing, requesting, or claiming items.', 'General', 2, 1),
(3, 'How are requests verified?', 'Requesters submit a short statement of need and optional proof document. Our administration team reviews every request to ensure safe and fair community distribution.', 'Requesting', 3, 1),
(4, 'How does pickup/delivery work?', 'Once matched, the donor and requester receive clear pickup instructions, contact details, and deadline tracking.', 'Logistics', 4, 1)
ON DUPLICATE KEY UPDATE `question` = VALUES(`question`);