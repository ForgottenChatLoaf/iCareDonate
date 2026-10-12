-- =============================================================
-- Migration: add_category_image_and_emergency_category.sql
-- Purpose  : (1) Add nullable image_path column to categories
--            (2) Populate image_path for existing 9 categories
--            (3) Insert "Emergency & Disaster Relief" if missing
-- Idempotent: safe to run multiple times
-- =============================================================
USE `icare_donate`;

-- 1. Add image_path column (IF NOT EXISTS guard)
ALTER TABLE `categories`
  ADD COLUMN IF NOT EXISTS `image_path` VARCHAR(300) NULL DEFAULT NULL AFTER `icon`;

-- 2. Update image_path for existing categories
UPDATE `categories` SET `image_path` = 'assets/img/categories/food-groceries.jpg'          WHERE `slug` = 'food-groceries';
UPDATE `categories` SET `image_path` = 'assets/img/categories/school-supplies.jpg'         WHERE `slug` = 'school-supplies';
UPDATE `categories` SET `image_path` = 'assets/img/categories/clothing-shoes.avif'         WHERE `slug` = 'clothing-shoes';
UPDATE `categories` SET `image_path` = 'assets/img/categories/medical-first-aid.jpg'       WHERE `slug` = 'medical-first-aid';
UPDATE `categories` SET `image_path` = NULL                                                  WHERE `slug` = 'household-goods';
UPDATE `categories` SET `image_path` = 'assets/img/categories/school-supplies.jpg'         WHERE `slug` = 'books-educational';
UPDATE `categories` SET `image_path` = 'assets/img/categories/toys-games.avif'             WHERE `slug` = 'toys-games';
UPDATE `categories` SET `image_path` = 'assets/img/categories/electronics-gadgets.avif'    WHERE `slug` = 'electronics-gadgets';
UPDATE `categories` SET `image_path` = 'assets/img/categories/other-essentials.jpg'        WHERE `slug` = 'other-essentials';

-- 3. Insert Emergency & Disaster Relief (idempotent)
INSERT INTO `categories` (`name`, `slug`, `description`, `icon`, `image_path`, `sort_order`, `is_active`)
SELECT
    'Emergency & Disaster Relief',
    'emergency-disaster-relief',
    'Support individuals and communities affected by disasters and emergencies by providing essential items they need during difficult times.',
    'flame',
    'assets/img/categories/emergency-disaster-relief.jpg',
    10,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `categories` WHERE `slug` = 'emergency-disaster-relief'
);

-- 4. Update image_path for emergency category if it already exists but has no image
UPDATE `categories`
SET `image_path` = 'assets/img/categories/emergency-disaster-relief.jpg'
WHERE `slug` = 'emergency-disaster-relief'
  AND (`image_path` IS NULL OR `image_path` = '');
