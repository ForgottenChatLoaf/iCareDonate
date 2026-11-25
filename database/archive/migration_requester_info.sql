-- iCareDonate — Migration: Requester Contact & Fulfillment Information
-- database/migration_requester_info.sql
--
-- Adds requester coordination and distribution contact fields to requests table.
-- Compatible with MySQL 8.0+ and MariaDB 10.4+.
-- Safe migration: preserves all existing data and tables.

USE `icaredonate`;

-- Add requester personal information columns if they do not exist
ALTER TABLE `requests`
    ADD COLUMN IF NOT EXISTS `requester_name`   VARCHAR(255) NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `contact_number`   VARCHAR(50)  NULL AFTER `requester_name`,
    ADD COLUMN IF NOT EXISTS `barangay`         VARCHAR(100) NULL AFTER `contact_number`,
    ADD COLUMN IF NOT EXISTS `city`             VARCHAR(100) NULL AFTER `barangay`,
    ADD COLUMN IF NOT EXISTS `complete_address` TEXT         NULL AFTER `city`;
