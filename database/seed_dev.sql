-- ============================================================
-- database/seed_dev.sql
-- iCareDonate LOCAL DEVELOPMENT Seeder
--
-- ⚠ WARNING: DO NOT import this file on production hosting.
--   It contains demo accounts with known passwords,
--   sample donations, and placeholder data for local testing.
--
-- Usage (local XAMPP only):
--   1. Import schema.sql first
--   2. Import seed_production.sql (categories + FAQs)
--   3. Import THIS file (seed_dev.sql) last
--
-- Demo credentials (password: Password123!):
--   superadmin: admin@icaredonate.org
--   user/donor: donor@icaredonate.org
--   user/req:   requester@icaredonate.org
-- ============================================================

-- Demo User Accounts
-- Password hash = password_hash('Password123!', PASSWORD_BCRYPT)
INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `barangay`, `city`, `province`, `role`, `status`, `email_verified_at`) VALUES
(1, 'admin@icaredonate.org',     '$2y$10$ElABAam1kfmFsz4Nadbg0OzCyQIav2ap0L4LE6BNcRejen3mAgjDW', 'System', 'Admin',    '09171234567', 'Central',    'Quezon City', 'Metro Manila', 'superadmin', 'active', NOW()),
(2, 'donor@icaredonate.org',     '$2y$10$ElABAam1kfmFsz4Nadbg0OzCyQIav2ap0L4LE6BNcRejen3mAgjDW', 'Maria',  'Santos',   '09189876543', 'San Antonio','Pasig City',  'Metro Manila', 'user',       'active', NOW()),
(3, 'requester@icaredonate.org', '$2y$10$ElABAam1kfmFsz4Nadbg0OzCyQIav2ap0L4LE6BNcRejen3mAgjDW', 'Juan',   'Dela Cruz','09195554321', 'Poblacion',  'Makati City', 'Metro Manila', 'user',       'active', NOW())
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`), `password_hash` = VALUES(`password_hash`);

-- Sample Donations (requires users + categories to exist)
INSERT INTO `donations` (`id`, `donor_id`, `category_id`, `title`, `description`, `quantity_total`, `quantity_reserved`, `quantity_claimed`, `unit`, `condition_type`, `pickup_method`, `pickup_barangay`, `pickup_city`, `pickup_province`, `status`, `approved_at`, `approved_by`) VALUES
(1, 2, 2, 'Elementary School Backpacks & Notebooks Set', 'Brand new backpacks filled with notebooks, pens, pencils, rulers, and coloring sets suitable for Grade 1-6 students.', 25, 0, 0, 'sets', 'new', 'both', 'San Antonio', 'Pasig City', 'Metro Manila', 'approved', NOW(), 1),
(2, 2, 1, 'Assorted Canned Goods and Rice Sacks', 'Nutritious canned vegetables, tuna, sardines, and 5kg rice packs sealed and safe for family consumption.', 40, 5, 0, 'packs', 'new', 'pickup', 'San Antonio', 'Pasig City', 'Metro Manila', 'approved', NOW(), 1),
(3, 2, 8, 'Working Lenovo Student Laptops with Chargers', 'Gently used Lenovo ThinkPad laptops, re-imaged with Windows 10 and educational tools. Perfect for distance learning or college students.', 5, 0, 0, 'units', 'good', 'pickup', 'San Antonio', 'Pasig City', 'Metro Manila', 'approved', NOW(), 1),
(4, 2, 4, 'Community First Aid Kits & Hygiene Packs', 'Complete first aid boxes containing antiseptic, bandages, thermometers, face masks, and antibacterial soap.', 30, 0, 0, 'kits', 'new', 'delivery', 'San Antonio', 'Pasig City', 'Metro Manila', 'approved', NOW(), 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);
