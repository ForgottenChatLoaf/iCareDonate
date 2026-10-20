-- iCareDonate — Minimal Authentication Schema
-- database/schema.sql
-- Compatible with MySQL 8.0+ and MariaDB 10.4+ (XAMPP default)

CREATE DATABASE IF NOT EXISTS `icaredonate` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `icaredonate`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('donor', 'requester', 'admin') NOT NULL DEFAULT 'donor',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Seed Accounts for Local Demonstration & Testing
-- Passwords are all hashed with PHP password_hash() using PASSWORD_DEFAULT (Bcrypt)
-- Default credentials:
-- 1. Superadmin: admin@icaredonate.org     / Admin@1234
-- 2. Test Admin: admin@icaredonate.test    / Admin@1234
-- 3. Donor:      donor@icaredonate.test    / Donor@1234
-- 4. Requester:  requester@icaredonate.test/ Requester@1234
-- 5. User:       user@icaredonate.test     / User@1234

INSERT INTO `users` (`name`, `email`, `password_hash`, `role`)
VALUES
    ('System Administrator', 'admin@icaredonate.org', '$2y$10$7JGU6jYUc1h3WsZP9UUnweHpgAsHOHaNCGFUecXxoaPr.RTZg3gOm', 'admin'),
    ('Admin Officer', 'admin@icaredonate.test', '$2y$10$7JGU6jYUc1h3WsZP9UUnweHpgAsHOHaNCGFUecXxoaPr.RTZg3gOm', 'admin'),
    ('David Tan', 'donor@icaredonate.test', '$2y$10$EdiY6BNLP3V5wZaWzMETaecwhE73zqrLX9EQIYhze.jHCrzqKb6N.', 'donor'),
    ('Maria Santos', 'requester@icaredonate.test', '$2y$10$rD/Tmmp1BphpNI39YxkuNulgl4B6Warj49rJjnwLInOWGkC6eo2/q', 'requester'),
    ('Alex Rivera', 'user@icaredonate.test', '$2y$10$EdiY6BNLP3V5wZaWzMETaecwhE73zqrLX9EQIYhze.jHCrzqKb6N.', 'donor')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `password_hash` = VALUES(`password_hash`),
    `role` = VALUES(`role`);
