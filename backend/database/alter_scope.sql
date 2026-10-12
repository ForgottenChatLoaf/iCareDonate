USE `icare_donate`;

-- 1. Create interviews table
CREATE TABLE IF NOT EXISTS `interviews` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `request_id` INT UNSIGNED NOT NULL,
  `requester_id` INT UNSIGNED NOT NULL,
  `scheduled_date` DATE NOT NULL,
  `scheduled_time` TIME NOT NULL,
  `method` ENUM('phone_call','video_call','in_person') NOT NULL DEFAULT 'phone_call',
  `meeting_link_or_venue` VARCHAR(500),
  `interview_questions` TEXT,
  `admin_notes` TEXT,
  `result` ENUM('pending','verified','further_review','not_verified') NOT NULL DEFAULT 'pending',
  `conducted_by` INT UNSIGNED,
  `conducted_at` DATETIME,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`requester_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  INDEX `idx_interviews_request` (`request_id`),
  INDEX `idx_interviews_requester` (`requester_id`),
  INDEX `idx_interviews_result` (`result`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Alter users table for settings
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `username` VARCHAR(100) AFTER `last_name`,
  ADD COLUMN IF NOT EXISTS `privacy_profile_visibility` ENUM('public','members','hidden') DEFAULT 'public' AFTER `profile_photo`,
  ADD COLUMN IF NOT EXISTS `privacy_contact_visibility` ENUM('matched_only','members','hidden') DEFAULT 'matched_only' AFTER `privacy_profile_visibility`,
  ADD COLUMN IF NOT EXISTS `privacy_show_area` TINYINT(1) DEFAULT 1 AFTER `privacy_contact_visibility`,
  ADD COLUMN IF NOT EXISTS `privacy_device_location` TINYINT(1) DEFAULT 0 AFTER `privacy_show_area`,
  ADD COLUMN IF NOT EXISTS `pref_language` VARCHAR(20) DEFAULT 'en' AFTER `privacy_device_location`,
  ADD COLUMN IF NOT EXISTS `pref_country` VARCHAR(100) DEFAULT 'Philippines' AFTER `pref_language`,
  ADD COLUMN IF NOT EXISTS `pref_timezone` VARCHAR(50) DEFAULT 'Asia/Manila' AFTER `pref_country`,
  ADD COLUMN IF NOT EXISTS `pref_date_format` VARCHAR(20) DEFAULT 'M d, Y' AFTER `pref_timezone`,
  ADD COLUMN IF NOT EXISTS `google_linked` TINYINT(1) DEFAULT 0 AFTER `pref_date_format`,
  ADD COLUMN IF NOT EXISTS `facebook_linked` TINYINT(1) DEFAULT 0 AFTER `google_linked`;

-- 3. Alter requests table for interview workflow
ALTER TABLE `requests`
  ADD COLUMN IF NOT EXISTS `interview_status` ENUM('not_required','pending','scheduled','completed') DEFAULT 'not_required' AFTER `urgency_level`,
  ADD COLUMN IF NOT EXISTS `no_interview_reason` TEXT AFTER `interview_status`,
  ADD COLUMN IF NOT EXISTS `delivery_address` VARCHAR(500) AFTER `no_interview_reason`,
  ADD COLUMN IF NOT EXISTS `contact_number` VARCHAR(30) AFTER `delivery_address`;

-- 4. Alter support_tickets table for attachments
ALTER TABLE `support_tickets`
  ADD COLUMN IF NOT EXISTS `attachment_path` VARCHAR(500) AFTER `message`;

-- 5. Alter donations table for media and contact
ALTER TABLE `donations`
  ADD COLUMN IF NOT EXISTS `donor_contact_name` VARCHAR(150) AFTER `pickup_schedule`,
  ADD COLUMN IF NOT EXISTS `donor_contact_number` VARCHAR(30) AFTER `donor_contact_name`,
  ADD COLUMN IF NOT EXISTS `pickup_date` DATE AFTER `donor_contact_number`,
  ADD COLUMN IF NOT EXISTS `pickup_time` TIME AFTER `pickup_date`,
  ADD COLUMN IF NOT EXISTS `video_url` VARCHAR(500) AFTER `pickup_time`;

-- 6. Create admin_invitations table
CREATE TABLE IF NOT EXISTS `admin_invitations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin','superadmin') NOT NULL DEFAULT 'admin',
  `token` VARCHAR(100) NOT NULL UNIQUE,
  `status` ENUM('pending','accepted','expired','revoked') NOT NULL DEFAULT 'pending',
  `invited_by` INT UNSIGNED NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `accepted_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`invited_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;