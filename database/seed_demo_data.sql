-- ============================================================
-- iCareDonate -- Comprehensive Demo / Defense Seed
-- database/seed_demo_data.sql
--
-- Seeds realistic demonstration data for ALL admin pages:
--   Dashboard, Donations, Requests, Transactions/Handovers,
--   Users, Support Tickets, Audit Logs, and Notifications.
--
-- Safe to re-run: uses NOT EXISTS guards on every INSERT.
-- Resolves user IDs dynamically via email -- no hardcoded IDs.
-- Target demo accounts (seeded by seed_runner.php):
--   Admin:     admin@icaredonate.test     (Admin@1234)
--   Donor:     donor@icaredonate.test     (Donor@1234)
--   Requester: requester@icaredonate.test (Requester@1234)
-- ============================================================

-- ============================================================
-- 1. DEMO USERS (extra donors and requesters for variety)
-- ============================================================

INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `created_at`)
VALUES
  ('Juan Dela Cruz',   'juan@demo.test',   '$2y$10$7JGU6jYUc1h3WsZP9UUnweHpgAsHOHaNCGFUecXxoaPr.RTZg3gOm', 'donor',     NOW() - INTERVAL 45 DAY),
  ('Ana Reyes',        'ana@demo.test',    '$2y$10$7JGU6jYUc1h3WsZP9UUnweHpgAsHOHaNCGFUecXxoaPr.RTZg3gOm', 'requester', NOW() - INTERVAL 40 DAY),
  ('Carlo Mendoza',    'carlo@demo.test',  '$2y$10$7JGU6jYUc1h3WsZP9UUnweHpgAsHOHaNCGFUecXxoaPr.RTZg3gOm', 'donor',     NOW() - INTERVAL 32 DAY),
  ('Rosa Santos',      'rosa@demo.test',   '$2y$10$7JGU6jYUc1h3WsZP9UUnweHpgAsHOHaNCGFUecXxoaPr.RTZg3gOm', 'requester', NOW() - INTERVAL 28 DAY),
  ('Miguel Torres',    'miguel@demo.test', '$2y$10$7JGU6jYUc1h3WsZP9UUnweHpgAsHOHaNCGFUecXxoaPr.RTZg3gOm', 'donor',     NOW() - INTERVAL 20 DAY),
  ('Lena Garcia',      'lena@demo.test',   '$2y$10$7JGU6jYUc1h3WsZP9UUnweHpgAsHOHaNCGFUecXxoaPr.RTZg3gOm', 'requester', NOW() - INTERVAL 14 DAY),
  ('Ben Aquino',       'ben@demo.test',    '$2y$10$7JGU6jYUc1h3WsZP9UUnweHpgAsHOHaNCGFUecXxoaPr.RTZg3gOm', 'donor',     NOW() - INTERVAL 10 DAY),
  ('Grace Villanueva', 'grace@demo.test',  '$2y$10$7JGU6jYUc1h3WsZP9UUnweHpgAsHOHaNCGFUecXxoaPr.RTZg3gOm', 'requester', NOW() - INTERVAL 5 DAY)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `role` = VALUES(`role`);

-- ============================================================
-- 2. DONATIONS (pending, approved, archived, rejected)
-- ============================================================

-- PENDING donations (Dashboard queue + Donations > Pending tab)
INSERT INTO `donations` (`donor_id`, `title`, `description`, `category`, `quantity`, `condition`, `location`, `barangay`, `city`, `handover_method`, `status`, `created_at`)
SELECT u.id,
  'Elementary School Backpacks and Stationery Kit',
  'Ten brand-new sturdy backpacks packed with spiral notebooks, ballpens, pencils, erasers, and rulers. Ideal for Grades 1 to 4.',
  'school-supplies', 10, 'new', 'Balintawak, Quezon City', 'Balintawak', 'Quezon City', 'pickup', 'pending',
  NOW() - INTERVAL 3 DAY
FROM `users` u WHERE u.email = 'donor@icaredonate.test'
AND NOT EXISTS (SELECT 1 FROM `donations` d WHERE d.donor_id = u.id AND d.title = 'Elementary School Backpacks and Stationery Kit');

INSERT INTO `donations` (`donor_id`, `title`, `description`, `category`, `quantity`, `condition`, `location`, `barangay`, `city`, `handover_method`, `status`, `created_at`)
SELECT u.id,
  'Family Emergency Food Pack (10 Packs)',
  'Non-perishable family food packs containing 2kg rice, 3 canned goods (corned beef, sardines), and instant noodles. Enough for a family of 5 for 3 days.',
  'food-groceries', 10, 'new', 'San Jose Del Monte', 'Minuyan', 'Bulacan', 'pickup', 'pending',
  NOW() - INTERVAL 1 DAY
FROM `users` u WHERE u.email = 'juan@demo.test'
AND NOT EXISTS (SELECT 1 FROM `donations` d WHERE d.donor_id = u.id AND d.title = 'Family Emergency Food Pack (10 Packs)');

INSERT INTO `donations` (`donor_id`, `title`, `description`, `category`, `quantity`, `condition`, `location`, `barangay`, `city`, `handover_method`, `status`, `created_at`)
SELECT u.id,
  'Medical First Aid Kit (Sealed)',
  'Factory-sealed first aid kit with bandages, antiseptic, cotton balls, surgical tape, and pain reliever tablets.',
  'medical-first-aid', 5, 'new', 'Cubao, Quezon City', 'Socorro', 'Quezon City', 'meetup', 'pending',
  NOW() - INTERVAL 6 HOUR
FROM `users` u WHERE u.email = 'miguel@demo.test'
AND NOT EXISTS (SELECT 1 FROM `donations` d WHERE d.donor_id = u.id AND d.title = 'Medical First Aid Kit (Sealed)');

-- APPROVED donations (active listings browsable by requesters)
INSERT INTO `donations` (`donor_id`, `title`, `description`, `category`, `quantity`, `condition`, `location`, `barangay`, `city`, `handover_method`, `status`, `created_at`)
SELECT u.id,
  'Childrens Storybooks Bundle (20 Books)',
  'Collection of 20 Tagalog and English illustrated storybooks for ages 4 to 10. Very good condition, pages intact.',
  'books-educational', 20, 'like_new', 'Marikina Heights', 'Marikina Heights', 'Marikina', 'pickup', 'approved',
  NOW() - INTERVAL 10 DAY
FROM `users` u WHERE u.email = 'donor@icaredonate.test'
AND NOT EXISTS (SELECT 1 FROM `donations` d WHERE d.donor_id = u.id AND d.title = 'Childrens Storybooks Bundle (20 Books)');

INSERT INTO `donations` (`donor_id`, `title`, `description`, `category`, `quantity`, `condition`, `location`, `barangay`, `city`, `handover_method`, `status`, `created_at`)
SELECT u.id,
  'Winter Jackets and Hoodies (Adult Sizes)',
  'Assorted adult jackets and hoodies, clean and ready for use. Sizes small to extra-large available.',
  'clothing-shoes', 15, 'good', 'Taguig City', 'Ususan', 'Taguig', 'meetup', 'approved',
  NOW() - INTERVAL 8 DAY
FROM `users` u WHERE u.email = 'carlo@demo.test'
AND NOT EXISTS (SELECT 1 FROM `donations` d WHERE d.donor_id = u.id AND d.title = 'Winter Jackets and Hoodies (Adult Sizes)');

INSERT INTO `donations` (`donor_id`, `title`, `description`, `category`, `quantity`, `condition`, `location`, `barangay`, `city`, `handover_method`, `status`, `created_at`)
SELECT u.id,
  'Bedsheets and Pillowcases Set',
  '8 sets of clean double-bed bedsheets with matching pillowcases. Freshly laundered.',
  'household-goods', 8, 'good', 'Pasig City', 'San Antonio', 'Pasig', 'pickup', 'approved',
  NOW() - INTERVAL 5 DAY
FROM `users` u WHERE u.email = 'ben@demo.test'
AND NOT EXISTS (SELECT 1 FROM `donations` d WHERE d.donor_id = u.id AND d.title = 'Bedsheets and Pillowcases Set');

-- ARCHIVED donation (for Donations > Archived tab)
INSERT INTO `donations` (`donor_id`, `title`, `description`, `category`, `quantity`, `condition`, `location`, `barangay`, `city`, `handover_method`, `status`, `archived_reason`, `created_at`)
SELECT u.id,
  'Old Wooden School Desks (Set of 4)',
  'Four single-seater wooden school desks. Slightly worn surfaces but structurally sound.',
  'household-goods', 4, 'fair', 'Caloocan City', 'Grace Park', 'Caloocan', 'pickup', 'archived',
  'Item condition did not meet quality standards for community distribution.',
  NOW() - INTERVAL 20 DAY
FROM `users` u WHERE u.email = 'donor@icaredonate.test'
AND NOT EXISTS (SELECT 1 FROM `donations` d WHERE d.donor_id = u.id AND d.title = 'Old Wooden School Desks (Set of 4)');

-- REJECTED donation (for Donations > Rejected tab)
INSERT INTO `donations` (`donor_id`, `title`, `description`, `category`, `quantity`, `condition`, `location`, `barangay`, `city`, `handover_method`, `status`, `rejection_reason`, `created_at`)
SELECT u.id,
  'Expired Canned Goods Lot',
  'Assorted canned goods from pantry clearout.',
  'food-groceries', 30, 'poor', 'Malabon City', 'Longos', 'Malabon', 'pickup', 'rejected',
  'Items submitted were found to be past their safe consumption date upon inspection.',
  NOW() - INTERVAL 15 DAY
FROM `users` u WHERE u.email = 'juan@demo.test'
AND NOT EXISTS (SELECT 1 FROM `donations` d WHERE d.donor_id = u.id AND d.title = 'Expired Canned Goods Lot');

-- ============================================================
-- 3. REQUESTS (pending, approved, completed)
-- ============================================================

INSERT INTO `requests` (`requester_id`, `donation_id`, `message`, `quantity`, `urgency`, `proof_type`, `status`, `created_at`)
SELECT u.id, d.id,
  'Our family of 6 recently lost our belongings in a fire. We urgently need school supplies for 3 children returning to school next week.',
  3, 'High', 'Barangay Indigency Certificate', 'pending',
  NOW() - INTERVAL 2 DAY
FROM `users` u
JOIN `donations` d ON d.title = 'Elementary School Backpacks and Stationery Kit'
WHERE u.email = 'requester@icaredonate.test'
  AND NOT EXISTS (
    SELECT 1 FROM `requests` r WHERE r.requester_id = u.id AND r.donation_id = d.id
  );

INSERT INTO `requests` (`requester_id`, `donation_id`, `message`, `quantity`, `urgency`, `proof_type`, `status`, `created_at`)
SELECT u.id, d.id,
  'Senior citizen household with no regular income. Requesting food assistance for my elderly mother and two grandchildren.',
  2, 'Critical', 'DSWD Assistance Certificate', 'pending',
  NOW() - INTERVAL 1 DAY
FROM `users` u
JOIN `donations` d ON d.title = 'Family Emergency Food Pack (10 Packs)'
WHERE u.email = 'ana@demo.test'
  AND NOT EXISTS (
    SELECT 1 FROM `requests` r WHERE r.requester_id = u.id AND r.donation_id = d.id
  );

INSERT INTO `requests` (`requester_id`, `donation_id`, `message`, `quantity`, `urgency`, `proof_type`, `status`, `created_at`)
SELECT u.id, d.id,
  'First aid supplies needed for our barangay health station which ran out during last months flooding.',
  3, 'High', 'Barangay Health Station Certificate', 'pending',
  NOW() - INTERVAL 5 HOUR
FROM `users` u
JOIN `donations` d ON d.title = 'Medical First Aid Kit (Sealed)'
WHERE u.email = 'rosa@demo.test'
  AND NOT EXISTS (
    SELECT 1 FROM `requests` r WHERE r.requester_id = u.id AND r.donation_id = d.id
  );

-- APPROVED request (matched, awaiting handover -- shows in Transactions)
INSERT INTO `requests` (`requester_id`, `donation_id`, `message`, `quantity`, `urgency`, `proof_type`, `status`, `created_at`, `updated_at`)
SELECT u.id, d.id,
  'Public elementary school teacher requesting books for reading corner in classroom of 42 students.',
  10, 'Medium', 'DepEd School ID and Assignment Letter', 'approved',
  NOW() - INTERVAL 12 DAY, NOW() - INTERVAL 7 DAY
FROM `users` u
JOIN `donations` d ON d.title = 'Childrens Storybooks Bundle (20 Books)'
WHERE u.email = 'lena@demo.test'
  AND NOT EXISTS (
    SELECT 1 FROM `requests` r WHERE r.requester_id = u.id AND r.donation_id = d.id
  );

-- COMPLETED request (fulfilled -- shows in Transactions > Completed)
INSERT INTO `requests` (`requester_id`, `donation_id`, `message`, `quantity`, `urgency`, `proof_type`, `status`, `created_at`, `updated_at`)
SELECT u.id, d.id,
  'Lost all clothing in typhoon flooding. Family of 4 adults requesting warm jackets for the incoming cold season.',
  4, 'High', 'Barangay Calamity Certification', 'completed',
  NOW() - INTERVAL 25 DAY, NOW() - INTERVAL 18 DAY
FROM `users` u
JOIN `donations` d ON d.title = 'Winter Jackets and Hoodies (Adult Sizes)'
WHERE u.email = 'grace@demo.test'
  AND NOT EXISTS (
    SELECT 1 FROM `requests` r WHERE r.requester_id = u.id AND r.donation_id = d.id AND r.status = 'completed'
  );

-- ============================================================
-- 4. HANDOVERS (transactions for Transactions admin page)
-- ============================================================

INSERT INTO `handovers` (`request_id`, `donation_id`, `donor_id`, `requester_id`, `status`, `scheduled_at`, `completed_at`, `notes`, `created_at`)
SELECT r.id, r.donation_id, d.donor_id, r.requester_id,
  'completed',
  NOW() - INTERVAL 20 DAY, NOW() - INTERVAL 18 DAY,
  'Verified handover of 4 jackets. Beneficiary signed release form. Goods in excellent condition.',
  NOW() - INTERVAL 22 DAY
FROM `requests` r
JOIN `donations` d ON r.donation_id = d.id
WHERE r.status = 'completed' AND d.title = 'Winter Jackets and Hoodies (Adult Sizes)'
  AND NOT EXISTS (SELECT 1 FROM `handovers` h WHERE h.request_id = r.id);

INSERT INTO `handovers` (`request_id`, `donation_id`, `donor_id`, `requester_id`, `status`, `scheduled_at`, `completed_at`, `notes`, `created_at`)
SELECT r.id, r.donation_id, d.donor_id, r.requester_id,
  'scheduled',
  NOW() + INTERVAL 2 DAY, NULL,
  'Scheduled interview and pickup for approved storybooks request. Coordinator to be present.',
  NOW() - INTERVAL 6 DAY
FROM `requests` r
JOIN `donations` d ON r.donation_id = d.id
WHERE r.status = 'approved' AND d.title = 'Childrens Storybooks Bundle (20 Books)'
  AND NOT EXISTS (SELECT 1 FROM `handovers` h WHERE h.request_id = r.id);

-- ============================================================
-- 5. SUPPORT TICKETS (Support Desk admin page)
-- ============================================================

INSERT INTO `support_tickets` (`user_id`, `name`, `email`, `subject`, `category`, `message`, `status`, `priority`, `created_at`)
SELECT u.id, 'David Tan', u.email,
  'How do I edit my donation listing after submitting?',
  'donation',
  'I submitted my donation listing yesterday but I made a typo in the description. I cannot find an edit button anywhere. Can you please help me update it?',
  'open', 'medium', NOW() - INTERVAL 2 DAY
FROM `users` u WHERE u.email = 'donor@icaredonate.test'
AND NOT EXISTS (SELECT 1 FROM `support_tickets` t WHERE t.email = 'donor@icaredonate.test' AND t.subject LIKE '%edit my donation%');

INSERT INTO `support_tickets` (`user_id`, `name`, `email`, `subject`, `category`, `message`, `status`, `priority`, `created_at`)
SELECT u.id, 'Maria Santos', u.email,
  'My request status has not changed in 5 days',
  'request',
  'I submitted my assistance request with proof 5 days ago and the status is still showing pending. I urgently need the items for my children. Please check my application.',
  'in_progress', 'high', NOW() - INTERVAL 5 DAY
FROM `users` u WHERE u.email = 'requester@icaredonate.test'
AND NOT EXISTS (SELECT 1 FROM `support_tickets` t WHERE t.email = 'requester@icaredonate.test' AND t.subject LIKE '%not changed%');

INSERT INTO `support_tickets` (`user_id`, `name`, `email`, `subject`, `category`, `message`, `status`, `priority`, `admin_reply`, `created_at`, `updated_at`)
SELECT u.id, 'Ana Reyes', u.email,
  'Cannot upload proof document - file too large',
  'technical',
  'The system keeps rejecting my proof document upload. I have a PDF of my DSWD certificate but it says file too large. What is the maximum allowed size?',
  'resolved', 'low',
  'Thank you for reaching out! The maximum file size is 5MB. If your PDF is larger, please try compressing it at ilovepdf.com. Your upload should then succeed.',
  NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 8 DAY
FROM `users` u WHERE u.email = 'ana@demo.test'
AND NOT EXISTS (SELECT 1 FROM `support_tickets` t WHERE t.email = 'ana@demo.test' AND t.subject LIKE '%file too large%');

INSERT INTO `support_tickets` (`user_id`, `name`, `email`, `subject`, `category`, `message`, `status`, `priority`, `created_at`)
VALUES (
  NULL, 'Barangay Longos Coordinator', 'bcoord.longos@gmail.com',
  'Partnership inquiry for regular donation drives',
  'partnership',
  'Good day. Our barangay social welfare office would like to partner with iCareDonate for quarterly donation drives. We have a network of 200+ needy families. How do we become an official partner?',
  'open', 'medium', NOW() - INTERVAL 1 DAY
);

-- ============================================================
-- 6. AUDIT LOGS (Audit Logs admin page)
-- ============================================================

INSERT INTO `audit_logs` (`action`, `actor_id`, `actor_name`, `actor_role`, `target_type`, `target_id`, `details`, `reason`, `ip_address`, `created_at`)
SELECT 'DONATION_APPROVED', u.id, u.name, 'admin', 'donation', d.id,
  CONCAT('Approved donation listing: ', d.title),
  'Listing meets quality and safety standards for community distribution.',
  '127.0.0.1', NOW() - INTERVAL 10 DAY
FROM `users` u
JOIN `donations` d ON d.title = 'Childrens Storybooks Bundle (20 Books)'
WHERE u.email = 'admin@icaredonate.test'
  AND NOT EXISTS (SELECT 1 FROM `audit_logs` al WHERE al.action = 'DONATION_APPROVED' AND al.target_id = d.id);

INSERT INTO `audit_logs` (`action`, `actor_id`, `actor_name`, `actor_role`, `target_type`, `target_id`, `details`, `reason`, `ip_address`, `created_at`)
SELECT 'DONATION_APPROVED', u.id, u.name, 'admin', 'donation', d.id,
  CONCAT('Approved donation listing: ', d.title),
  'Verified listing for clothing distribution. Items in good condition.',
  '127.0.0.1', NOW() - INTERVAL 8 DAY
FROM `users` u
JOIN `donations` d ON d.title = 'Winter Jackets and Hoodies (Adult Sizes)'
WHERE u.email = 'admin@icaredonate.test'
  AND NOT EXISTS (SELECT 1 FROM `audit_logs` al WHERE al.action = 'DONATION_APPROVED' AND al.target_id = d.id);

INSERT INTO `audit_logs` (`action`, `actor_id`, `actor_name`, `actor_role`, `target_type`, `target_id`, `details`, `reason`, `ip_address`, `created_at`)
SELECT 'DONATION_REJECTED', u.id, u.name, 'admin', 'donation', d.id,
  CONCAT('Rejected donation listing: ', d.title),
  'Items submitted were found to be past their safe consumption date.',
  '127.0.0.1', NOW() - INTERVAL 15 DAY
FROM `users` u
JOIN `donations` d ON d.title = 'Expired Canned Goods Lot'
WHERE u.email = 'admin@icaredonate.test'
  AND NOT EXISTS (SELECT 1 FROM `audit_logs` al WHERE al.action = 'DONATION_REJECTED' AND al.target_id = d.id);

INSERT INTO `audit_logs` (`action`, `actor_id`, `actor_name`, `actor_role`, `target_type`, `target_id`, `details`, `reason`, `ip_address`, `created_at`)
SELECT 'REQUEST_APPROVED', u.id, u.name, 'admin', 'request', r.id,
  'Approved beneficiary assistance request after proof verification.',
  'DSWD certificate verified. Applicant qualifies for community assistance.',
  '127.0.0.1', NOW() - INTERVAL 7 DAY
FROM `users` u
JOIN `requests` r ON r.status = 'approved'
JOIN `donations` d ON r.donation_id = d.id AND d.title = 'Childrens Storybooks Bundle (20 Books)'
WHERE u.email = 'admin@icaredonate.test'
  AND NOT EXISTS (SELECT 1 FROM `audit_logs` al WHERE al.action = 'REQUEST_APPROVED' AND al.target_id = r.id);

INSERT INTO `audit_logs` (`action`, `actor_id`, `actor_name`, `actor_role`, `target_type`, `target_id`, `details`, `reason`, `ip_address`, `created_at`)
SELECT 'HANDOVER_CONFIRMED', u.id, u.name, 'admin', 'handover', h.id,
  'Confirmed goods handover. Items delivered to verified beneficiary.',
  'Beneficiary attended scheduled pickup. Release form signed.',
  '127.0.0.1', NOW() - INTERVAL 18 DAY
FROM `users` u
JOIN `handovers` h ON h.status = 'completed'
WHERE u.email = 'admin@icaredonate.test'
  AND NOT EXISTS (SELECT 1 FROM `audit_logs` al WHERE al.action = 'HANDOVER_CONFIRMED' AND al.target_id = h.id);

INSERT INTO `audit_logs` (`action`, `actor_id`, `actor_name`, `actor_role`, `target_type`, `target_id`, `details`, `reason`, `ip_address`, `created_at`)
SELECT 'USER_LOGIN', u.id, u.name, 'admin', 'user', u.id,
  'Administrator logged in to the admin portal.',
  NULL, '127.0.0.1', NOW() - INTERVAL 1 HOUR
FROM `users` u WHERE u.email = 'admin@icaredonate.test'
  AND NOT EXISTS (SELECT 1 FROM `audit_logs` al WHERE al.action = 'USER_LOGIN' AND al.actor_id = u.id);

-- ============================================================
-- 7. NOTIFICATIONS (user portal)
-- ============================================================

INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `is_read`, `created_at`)
SELECT u.id, 'donation',
  'Donation Listing Approved',
  'Your donation "Childrens Storybooks Bundle (20 Books)" was verified and published to the community directory.',
  0, NOW() - INTERVAL 10 DAY
FROM `users` u WHERE u.email = 'donor@icaredonate.test'
  AND NOT EXISTS (SELECT 1 FROM `notifications` n WHERE n.user_id = u.id AND n.title = 'Donation Listing Approved' AND n.message LIKE '%Storybooks%');

INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `is_read`, `created_at`)
SELECT u.id, 'request',
  'Assistance Request Approved',
  'Your request for the storybooks donation has been approved! A coordinator will contact you to schedule handover.',
  0, NOW() - INTERVAL 7 DAY
FROM `users` u WHERE u.email = 'lena@demo.test'
  AND NOT EXISTS (SELECT 1 FROM `notifications` n WHERE n.user_id = u.id AND n.title = 'Assistance Request Approved');

INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `is_read`, `created_at`)
SELECT u.id, 'match',
  'Handover Completed',
  'The goods handover for your request has been confirmed. Thank you for trusting iCareDonate!',
  1, NOW() - INTERVAL 18 DAY
FROM `users` u WHERE u.email = 'grace@demo.test'
  AND NOT EXISTS (SELECT 1 FROM `notifications` n WHERE n.user_id = u.id AND n.title = 'Handover Completed');

INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `is_read`, `created_at`)
SELECT u.id, 'donation',
  'New Donation Match Available',
  'A new donation matching your requested items has been listed in your area. Browse now to request assistance.',
  0, NOW() - INTERVAL 12 HOUR
FROM `users` u WHERE u.email = 'requester@icaredonate.test'
  AND NOT EXISTS (SELECT 1 FROM `notifications` n WHERE n.user_id = u.id AND n.title = 'New Donation Match Available');
