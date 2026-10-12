<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pageTitle = 'Terms of Service';
$extraCss = 'public.css';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/nav-public.php';
?>

<style>
.legal-container {
  max-width: 780px;
  margin: 0 auto;
  padding: 0 20px;
}
.legal-card {
  padding: 40px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  background: var(--surface);
  box-shadow: var(--shadow-sm);
  color: var(--color-text);
  font-size: 15px;
  line-height: 1.75;
}
.legal-card h1 {
  font-family: var(--font-heading);
  font-size: 32px;
  color: var(--color-text);
  margin-top: 0;
  margin-bottom: 8px;
  line-height: 1.25;
}
.legal-card h2 {
  font-family: var(--font-heading);
  font-size: 20px;
  font-weight: 700;
  color: var(--cobalt);
  margin-top: 36px;
  margin-bottom: 12px;
  scroll-margin-top: 90px;
}
.legal-card p {
  margin-top: 0;
  margin-bottom: 14px;
}
.legal-card ul {
  margin-top: 0;
  margin-bottom: 16px;
  padding-left: 24px;
}
.legal-card li {
  margin-bottom: 6px;
}
.legal-card a {
  color: var(--cobalt);
  text-decoration: underline;
}
.legal-card a:hover {
  color: var(--brand-primary-dark);
}
@media (max-width: 640px) {
  .legal-card {
    padding: 24px 20px;
  }
  .legal-card h1 {
    font-size: 26px;
  }
  .legal-card h2 {
    font-size: 18px;
  }
}
</style>

<main style="padding: 48px 0 80px; background: var(--color-bg);">
  <div class="container legal-container">
    
    <article class="legal-card">
      <h1>iCareDonate — Terms of Service</h1>

      <!-- TODO: confirm official Last Updated date -->
      <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 24px;">Last Updated: <?= e(LEGAL_LAST_UPDATED) ?></p>

      <p>Welcome to <strong>iCareDonate</strong>, an item-based donation platform developed by <strong>Ambagan Inc.</strong> These Terms of Service explain the rules and responsibilities for using iCareDonate.</p>

      <p>By creating an account or using iCareDonate, you agree to follow these Terms. If you do not agree with these Terms, please do not use the platform.</p>

      <h2 id="terms-1">1. About iCareDonate</h2>
      <p>iCareDonate is a community resource-donation platform designed to connect people who have useful physical items with individuals or communities who need support.</p>
      <p>The platform focuses on <strong>physical or in-kind donations</strong>, such as food, school supplies, clothing, medical items, household goods, and other physical resources.</p>
      <p>Cash or monetary donations, online payments, and digital goods are outside the scope of the current platform.</p>

      <h2 id="terms-2">2. User Accounts</h2>
      <p>Users must create an account to participate in donation and requesting activities.</p>
      <p>All new accounts must complete email verification before they can perform actions on the platform. A user may register only one account using the same email address.</p>
      <p>Users are responsible for providing accurate information and for keeping their account credentials secure.</p>
      <p>Administrative accounts are not created through the public registration process.</p>

      <h2 id="terms-3">3. User Roles</h2>
      <p>Users may participate as donors, requesters, or both.</p>
      <p><strong>Donors</strong> can post items for donation.</p>
      <p><strong>Requesters</strong> can browse available donations and submit requests for items they need.</p>
      <p>Users may act as both donors and requesters when their account allows it. Administrative accounts have separate access and responsibilities.</p>

      <h2 id="terms-4">4. Donations</h2>
      <p>Only active accounts may post donations.</p>
      <p>When posting a donation, users must provide the required information, including:</p>
      <ul>
        <li>Item title</li>
        <li>Category</li>
        <li>Quantity</li>
        <li>Condition</li>
        <li>Pickup method</li>
        <li>Pickup location</li>
      </ul>
      <p>Users may also provide photos, an expiration date, and a description.</p>
      <p>A donation is initially saved as a draft and must be submitted for administrator verification.</p>
      <p>Donations are <strong>not publicly available until approved by an administrator.</strong> An administrator may request additional information or reject a donation and provide a reason.</p>

      <h2 id="terms-5">5. Requests for Donations</h2>
      <p>Users may request an item only when the donation has been approved and has available quantity.</p>
      <p>A request cannot exceed the available quantity of the donation. Users also cannot submit multiple active requests for the same donation. Suspended or banned accounts cannot submit requests.</p>
      <p>Requesters must provide proof of need as part of their request. Administrators review this information before approving a request.</p>
      <p>Proof-of-need documents are private and are not publicly accessible.</p>

      <h2 id="terms-6">6. Matching and Transactions</h2>
      <p>iCareDonate facilitates the connection between donors and requesters based on available donations and submitted requests. Matching may consider factors such as:</p>
      <ul>
        <li>Category or item type</li>
        <li>Available quantity</li>
        <li>Location proximity</li>
        <li>Request urgency</li>
        <li>Request date</li>
        <li>Requester verification status</li>
      </ul>
      <p>A request may be matched with all or part of the available quantity of a donation. When a match is established, the corresponding donation quantity may be reserved for the requester.</p>

      <h2 id="terms-7">7. Claiming and Completion</h2>
      <p>Transactions have a claim deadline. The default claim period is <strong>7 days from transaction creation</strong>, although this may be configurable by an administrator.</p>
      <p>If a transaction is cancelled or the claim deadline passes without a claim being recorded, the reserved quantity may be returned to the donation's available quantity.</p>
      <p>A completed transaction is recorded in the history of the donor and requester.</p>

      <h2 id="terms-8">8. User Responsibilities</h2>
      <p>Users agree to:</p>
      <ul>
        <li>Provide truthful and accurate information.</li>
        <li>Provide legitimate information when submitting donations or requests.</li>
        <li>Use the platform only for its intended donation purposes.</li>
        <li>Respect other users.</li>
        <li>Provide valid proof of need when requesting donations.</li>
        <li>Avoid submitting misleading, fraudulent, or inappropriate donation listings.</li>
        <li>Keep their account credentials secure.</li>
      </ul>
      <p>Users should not misuse the platform or attempt to bypass its verification, moderation, or security processes.</p>

      <h2 id="terms-9">9. Prohibited Activities</h2>
      <p>Users must not use iCareDonate to:</p>
      <ul>
        <li>Submit fraudulent or misleading donation listings.</li>
        <li>Provide false information in donation or request submissions.</li>
        <li>Submit inappropriate content.</li>
        <li>Abuse the donation or request system.</li>
        <li>Attempt to access another user's account or private information.</li>
        <li>Attempt to access private proof-of-need documents.</li>
        <li>Circumvent account suspensions or bans.</li>
        <li>Use the platform for purposes outside its intended donation activities.</li>
      </ul>

      <h2 id="terms-10">10. Reports and Moderation</h2>
      <p>Active users may report donation listings they believe are inappropriate, fraudulent, or misleading.</p>
      <p>Reports are reviewed by administrators. Depending on the situation, administrators may issue a warning, remove content, suspend an account, or ban an account. Moderation actions are recorded together with the reason for the action.</p>

      <h2 id="terms-11">11. Account Suspension and Banning</h2>
      <p>An account may be suspended or banned when necessary to enforce the platform's rules.</p>
      <p>A <strong>suspended account</strong> may be restricted from posting, requesting, or accessing certain platform functions.</p>
      <p>A <strong>banned account</strong> is blocked from logging in.</p>
      <p>Administrators must record a reason for suspending or banning an account.</p>

      <h2 id="terms-12">12. Privacy and Personal Information</h2>
      <p>iCareDonate limits the visibility of users' personal information.</p>
      <p>Personal contact information is not publicly displayed. Certain information may become available to matched parties only after a match has been confirmed and when necessary for the transaction.</p>
      <p>Proof-of-need documents are kept private and are not publicly accessible.</p>
      <p>For more information about how personal information is collected, used, protected, retained, and deleted, please refer to the <a href="/CommunityDono/pages/legal/privacy.php"><strong>iCareDonate Privacy Policy.</strong></a></p>

      <h2 id="terms-13">13. Account Deletion</h2>
      <p>Users may request deletion of their account.</p>
      <p>When an account is deleted, personal information is anonymized while certain transaction records may be retained for accountability. Active transactions must be resolved or cancelled before account deletion can be processed.</p>

      <h2 id="terms-14">14. Changes to These Terms</h2>
      <p>iCareDonate may update these Terms of Service when necessary to reflect changes to the platform, its policies, or its operations.</p>
      <p>Any updated version will include a <strong>revised Last Updated date</strong>.</p>

      <h2 id="terms-15">15. Contact Us</h2>
      <p>If you have questions or concerns about these Terms of Service, please contact iCareDonate through the <a href="/CommunityDono/pages/contact.php">official contact information provided on the website</a>.</p>
    </article>

  </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
