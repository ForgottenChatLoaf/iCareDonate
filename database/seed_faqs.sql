-- ============================================================
-- database/seed_faqs.sql
-- iCareDonate Official 16 FAQs Seeder
-- ============================================================

USE `icare_donate`;

DELETE FROM `faqs`;

INSERT INTO `faqs` (`id`, `question`, `answer`, `category`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '1. What is iCareDonate?', 'iCareDonate is an item-based donation platform that connects people who want to donate useful items with individuals or communities who need support.', 'General', 1, 1, NOW(), NOW()),
(2, '2. Who can use iCareDonate?', 'iCareDonate is for people who want to donate useful items and individuals who need assistance. Users must have an active account to participate in donations and requests.', 'General', 2, 1, NOW(), NOW()),
(3, '3. How can I donate an item?', 'After creating and verifying your account, you can post a donation by providing the required information about the item, including its title, category, quantity, condition, pickup method, and pickup location. You can also provide photos, an expiration date, and a description.', 'Donating', 3, 1, NOW(), NOW()),
(4, '4. Are donations immediately visible on the website?', 'No. Submitted donations are reviewed by an administrator first. A donation becomes available for others to browse only after it has been approved.', 'Donating', 4, 1, NOW(), NOW()),
(5, '5. How can I request a donated item?', 'You can request an approved donation that has available quantity. You must provide the required request information and upload proof of need for administrator review.', 'Requesting', 5, 1, NOW(), NOW()),
(6, '6. Why do I need to provide proof of need?', 'Proof of need allows administrators to review requests and help ensure that support is provided appropriately. Proof documents are kept private and are not publicly displayed.', 'Requesting', 6, 1, NOW(), NOW()),
(7, '7. Can I request more items than what is available?', 'No. A request cannot exceed the available quantity of the donation.', 'Requesting', 7, 1, NOW(), NOW()),
(8, '8. Can I request the same donation more than once?', 'You cannot have multiple active requests for the same donation.', 'Requesting', 8, 1, NOW(), NOW()),
(9, '9. What happens after I submit a request?', 'Your request is reviewed by an administrator. If it is approved and a match is made, the donation quantity is reserved and the donation process can proceed toward claiming and completion.', 'Requesting', 9, 1, NOW(), NOW()),
(10, '10. How does iCareDonate protect my personal information?', 'iCareDonate limits the visibility of personal information. For example, contact information is not publicly displayed and is only shared with matched parties when the match has been confirmed and the information is needed.', 'Privacy & Safety', 10, 1, NOW(), NOW()),
(11, '11. Can I edit my donation?', 'You can edit a donation while it is in draft or when additional information has been requested. A donation cannot be edited while it is pending verification.', 'Donating', 11, 1, NOW(), NOW()),
(12, '12. What happens if my donation is rejected?', 'If an administrator rejects a donation, the donor is notified and the reason for the rejection is provided.', 'Donating', 12, 1, NOW(), NOW()),
(13, '13. Can I report a donation?', 'Yes. Active users can report donations that they believe are inappropriate, fraudulent, or misleading. Administrators review reported content and may take appropriate moderation action.', 'Moderation', 13, 1, NOW(), NOW()),
(14, '14. Can my account be suspended or banned?', 'Yes. Administrators may issue warnings, remove content, suspend accounts, or ban users when moderation rules are violated.', 'Moderation', 14, 1, NOW(), NOW()),
(15, '15. Can I delete my account?', 'Yes. Users can request account deletion. Personal information is anonymized while necessary transaction records may be preserved for accountability. Any active transactions must be resolved or cancelled before account deletion.', 'Account', 15, 1, NOW(), NOW()),
(16, '16. What should I do if I need help?', 'You can contact iCareDonate through the available contact or support channels provided on the website.', 'Support', 16, 1, NOW(), NOW());
