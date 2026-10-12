<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pageTitle = 'Careers & Volunteer Opportunities — iCareDonate';
$extraCss = 'public.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav-public.php';
?>

<main style="padding: 60px 0 80px; background: var(--color-bg);">
  <div class="container" style="max-width: 860px; margin: 0 auto; padding: 0 20px;">
    
    <div style="text-align: center; margin-bottom: 48px;">
      <span class="badge badge-info" style="margin-bottom: 12px;">Join Our Mission</span>
      <h1 style="font-family: var(--font-brand); font-size: 40px; color: var(--color-text); margin-bottom: 12px;">
        Work &amp; Volunteer with iCareDonate
      </h1>
      <p style="font-size: 16px; color: var(--color-text-muted); max-width: 600px; margin: 0 auto; line-height: 1.6;">
        Help us build technology and grassroots logistics networks that make community mutual aid seamless and dignified.
      </p>
    </div>

    <div class="card" style="padding: 32px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 32px;">
      <h2 style="font-size: 20px; font-weight: 700; color: var(--color-primary); margin: 0 0 12px;">
        Volunteer Community Coordinators
      </h2>
      <p style="font-size: 14.5px; color: var(--color-text); line-height: 1.6; margin-bottom: 16px;">
        We are actively seeking barangay coordinators across Metro Manila to assist in verifying proof documents, coordinating distribution days, and conducting occasional verification interviews.
      </p>
      <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <span class="badge badge-approved">Remote / Hybrid</span>
        <span class="badge badge-neutral">Flexible Hours</span>
        <span class="badge badge-info">Community Impact</span>
      </div>
    </div>

    <div class="card" style="padding: 32px; border-radius: var(--radius-lg); text-align: center; background: var(--color-tint-teal); border: 1px solid rgba(31, 79, 163, 0.12);">
      <h3 style="font-size: 18px; font-weight: 700; color: var(--color-text); margin: 0 0 8px;">Want to collaborate or join?</h3>
      <p style="font-size: 14px; color: var(--color-text-muted); margin-bottom: 20px;">Send our operations team a message with your background and area of interest.</p>
      <a href="/CommunityDono/pages/contact.php" class="btn btn-primary">Contact Operations Team</a>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
