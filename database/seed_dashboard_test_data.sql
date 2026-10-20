-- iCareDonate — Manual Test Data Seed
-- database/seed_dashboard_test_data.sql
--
-- This script safely seeds clearly identified test records for demonstration accounts.
-- It resolves ownership dynamically via email (NO hardcoded numeric IDs).
-- It does NOT create or alter user passwords.
-- It is safe to re-run without creating duplicates.
--
-- Target test accounts:
--   Donor:     donor@icaredonate.test
--   Requester: requester@icaredonate.test

-- 1. Seed Donations for Donor Account (donor@icaredonate.test)
INSERT INTO `donations` (`user_id`, `title`, `category`, `description`, `quantity`, `unit`, `condition_type`, `status`, `pickup_method`, `pickup_city`, `pickup_barangay`)
SELECT u.id,
       'Elementary Backpacks & Notebook Bundle',
       'school-supplies',
       '[TEST SEED] 10 brand-new sturdy backpacks with spiral notebooks and pencils for elementary students.',
       10,
       'sets',
       'new',
       'approved',
       'pickup',
       'Quezon City',
       'Barangay Central'
FROM `users` u
WHERE LOWER(u.email) = 'donor@icaredonate.test'
  AND NOT EXISTS (
      SELECT 1 FROM `donations` d WHERE d.user_id = u.id AND d.title = 'Elementary Backpacks & Notebook Bundle'
  );

INSERT INTO `donations` (`user_id`, `title`, `category`, `description`, `quantity`, `unit`, `condition_type`, `status`, `pickup_method`, `pickup_city`, `pickup_barangay`)
SELECT u.id,
       'Family Rice & Canned Goods Care Packs',
       'food-groceries',
       '[TEST SEED] 5 care packs with rice, corned beef, and sardines.',
       5,
       'packs',
       'new',
       'completed',
       'both',
       'Pasig City',
       'San Antonio'
FROM `users` u
WHERE LOWER(u.email) = 'donor@icaredonate.test'
  AND NOT EXISTS (
      SELECT 1 FROM `donations` d WHERE d.user_id = u.id AND d.title = 'Family Rice & Canned Goods Care Packs'
  );

-- 2. Seed Assistance Requests for Requester Account (requester@icaredonate.test)
INSERT INTO `requests` (`user_id`, `item_title`, `quantity`, `unit`, `reason`, `proof_type`, `status`)
SELECT u.id,
       'Emergency Food Care Packs',
       2,
       'packs',
       '[TEST SEED] Emergency food packs needed for senior citizen household.',
       'Barangay Indigency Certificate',
       'under_review'
FROM `users` u
WHERE LOWER(u.email) = 'requester@icaredonate.test'
  AND NOT EXISTS (
      SELECT 1 FROM `requests` r WHERE r.user_id = u.id AND r.item_title = 'Emergency Food Care Packs'
  );

INSERT INTO `requests` (`user_id`, `item_title`, `quantity`, `unit`, `reason`, `proof_type`, `status`)
SELECT u.id,
       'Elementary School Uniforms & Shoes',
       1,
       'sets',
       '[TEST SEED] School uniform replacement for Grade 4 elementary student.',
       'Public School Enrollment Certificate',
       'completed'
FROM `users` u
WHERE LOWER(u.email) = 'requester@icaredonate.test'
  AND NOT EXISTS (
      SELECT 1 FROM `requests` r WHERE r.user_id = u.id AND r.item_title = 'Elementary School Uniforms & Shoes'
  );

-- 3. Seed Notifications for Donor Account
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `created_at`)
SELECT u.id,
       'Donation Listing Approved',
       'Your listing "Elementary Backpacks & Notebook Bundle" was verified and published to the public directory.',
       'donation',
       0,
       NOW() - INTERVAL 1 DAY
FROM `users` u
WHERE LOWER(u.email) = 'donor@icaredonate.test'
  AND NOT EXISTS (
      SELECT 1 FROM `notifications` n WHERE n.user_id = u.id AND n.title = 'Donation Listing Approved'
  );

INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `created_at`)
SELECT u.id,
       'Pickup Scheduled with Coordinator',
       'A community member has been matched with your Care Packs donation. Handover is set for tomorrow.',
       'match',
       0,
       NOW() - INTERVAL 2 HOUR
FROM `users` u
WHERE LOWER(u.email) = 'donor@icaredonate.test'
  AND NOT EXISTS (
      SELECT 1 FROM `notifications` n WHERE n.user_id = u.id AND n.title = 'Pickup Scheduled with Coordinator'
  );

-- 4. Seed Notifications for Requester Account
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `created_at`)
SELECT u.id,
       'Request Under Review',
       'Your assistance request for "Emergency Food Care Packs" is being verified by our community coordinators.',
       'request',
       0,
       NOW() - INTERVAL 3 HOUR
FROM `users` u
WHERE LOWER(u.email) = 'requester@icaredonate.test'
  AND NOT EXISTS (
      SELECT 1 FROM `notifications` n WHERE n.user_id = u.id AND n.title = 'Request Under Review'
  );

INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `created_at`)
SELECT u.id,
       'Welcome to iCareDonate!',
       'Your account has been initialized. You can now request assistance or browse community donations.',
       'system',
       1,
       NOW() - INTERVAL 3 DAY
FROM `users` u
WHERE LOWER(u.email) = 'requester@icaredonate.test'
  AND NOT EXISTS (
      SELECT 1 FROM `notifications` n WHERE n.user_id = u.id AND n.title = 'Welcome to iCareDonate!'
  );
