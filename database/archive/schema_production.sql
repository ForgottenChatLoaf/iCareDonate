-- iCareDonate — Production Schema
-- database/schema_production.sql
--
-- Import this into your Render MySQL service after creation.
-- Do NOT include this file in automated deploys.
-- Run once manually via MySQL client or Render shell.
--
-- Usage:
--   mysql -h <RENDER_MYSQL_HOST> -P 3306 -u icaredonate_app -p icaredonate_prod < database/schema_production.sql

CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(255)    NOT NULL,
    `email`         VARCHAR(255)    NOT NULL UNIQUE,
    `password_hash` VARCHAR(255)    NOT NULL,
    `role`          ENUM('donor', 'requester', 'admin') NOT NULL DEFAULT 'donor',
    `created_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE: No seed accounts are included in this production schema.
-- Create your first administrator securely via CLI:
--   php setup_admin.php "Admin Name" admin@yourdomain.com "YourSecurePassword"
-- (Run from the Render shell or via SSH — never expose setup_admin.php to the web)
