<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pageTitle = 'How It Works — iCareDonate';
$extraCss = 'public.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav-public.php';
?>

<main style="padding: 40px 0 80px;">
  <div class="container" style="max-width: 960px; margin: 0 auto; padding: 0 20px;">
    
    <div style="margin-bottom: 32px;">
      <h1 style="margin-bottom: 8px;">How it works</h1>
      <p class="lead" style="margin-bottom: 0;">Everything you need to know about posting items, requesting aid, and safe handovers.</p>
    </div>

    <!-- Who we serve (Wireframe Row 1) -->
    <div class="card" style="padding: 28px; border-radius: 20px; border: 1px solid var(--sky); margin-bottom: 24px; display: grid; grid-template-columns: 240px 1fr; gap: 28px; align-items: center; box-shadow: var(--shadow-sm);">
      <!-- Real community photo replacing icon panel -->
      <div style="position: relative; border-radius: 16px; height: 180px; overflow: hidden; flex-shrink: 0;">
        <img
          src="/CommunityDono/assets/images/who-we-serve.jpg"
          alt="Community families receiving donated supplies at a local distribution point"
          style="width: 100%; height: 100%; object-fit: cover; display: block;"
        >
        <!-- Subtle overlay badge at bottom -->
        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(0deg, rgba(20,33,61,0.72) 0%, transparent 100%); padding: 10px 12px;">
          <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; color: #fff; letter-spacing: 0.04em; text-transform: uppercase;">
            <i data-lucide="heart" style="width:11px;height:11px;color:var(--amber);"></i>
            Community Families
          </span>
        </div>
      </div>
      <div>
        <h3 style="font-size: 22px; color: var(--cobalt); margin-bottom: 10px;">Who we serve</h3>
        <p style="font-size: 15px; color: var(--slate); line-height: 1.65; margin-bottom: 8px;">
          iCareDonate serves low-income households, disaster-affected communities, students requiring educational resources, and local charitable initiatives that require essential material aid.
        </p>
        <p style="font-size: 15px; color: var(--slate); line-height: 1.65; margin: 0;">
          Our platform connects requesters directly with community members offering surplus items, eliminating intermediaries and ensuring dignified support.
        </p>
      </div>
    </div>

    <!-- What we do (Wireframe Row 2 - Reversed) -->
    <div class="card" style="padding: 28px; border-radius: 20px; border: 1px solid var(--sky); margin-bottom: 40px; display: grid; grid-template-columns: 1fr 240px; gap: 28px; align-items: center; box-shadow: var(--shadow-sm);">
      <div>
        <h3 style="font-size: 22px; color: var(--cobalt); margin-bottom: 10px;">What we do</h3>
        <p style="font-size: 15px; color: var(--slate); line-height: 1.65; margin-bottom: 8px;">
          We manage the end-to-end verification, matching, and monitoring of physical item donations and community requests.
        </p>
        <p style="font-size: 15px; color: var(--slate); line-height: 1.65; margin: 0;">
          Every posted item and request is reviewed by administrators before going live, guaranteeing transparency, fair quantity allocation, and reliable pickup coordination.
        </p>
      </div>
      <!-- Real community photo replacing coral handshake icon -->
      <div style="position: relative; border-radius: 16px; height: 180px; overflow: hidden; flex-shrink: 0;">
        <img
          src="/CommunityDono/assets/images/what-we-do.jpg"
          alt="Volunteers distributing donated supplies to a community member"
          style="width: 100%; height: 100%; object-fit: cover; display: block;"
        >
        <!-- Subtle overlay badge at bottom -->
        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(0deg, rgba(20,33,61,0.72) 0%, transparent 100%); padding: 10px 12px;">
          <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; color: #fff; letter-spacing: 0.04em; text-transform: uppercase;">
            <i data-lucide="shield-check" style="width:11px;height:11px;color:var(--amber);"></i>
            Every Donation Counts
          </span>
        </div>
      </div>
    </div>

    <!-- 3-Step Process (Wireframe Spec) -->
    <div style="margin-bottom: 40px;">
      <h2 style="font-size: 26px; color: var(--cobalt); margin-bottom: 20px;">The 3-Step Process</h2>
      
      <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <div class="card" style="padding: 24px; border-radius: 20px; border: 1px solid var(--sky); box-shadow: var(--shadow-sm);">
          <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--cobalt); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; margin-bottom: 14px; font-family: var(--font-heading);">1</div>
          <h3 style="font-size: 19px; color: var(--cobalt); margin-bottom: 8px;">Post</h3>
          <p style="font-size: 14px; color: var(--slate); line-height: 1.6; margin: 0;">Donors list an item with photos and condition. Requesters post what they need, with proof of need.</p>
        </div>

        <div class="card" style="padding: 24px; border-radius: 20px; border: 1px solid var(--sky); box-shadow: var(--shadow-sm);">
          <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--cobalt); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; margin-bottom: 14px; font-family: var(--font-heading);">2</div>
          <h3 style="font-size: 19px; color: var(--cobalt); margin-bottom: 8px;">Admin verifies</h3>
          <p style="font-size: 14px; color: var(--slate); line-height: 1.6; margin: 0;">Every post and request is checked by the administration team before it goes live on the platform.</p>
        </div>

        <div class="card" style="padding: 24px; border-radius: 20px; border: 1px solid var(--sky); box-shadow: var(--shadow-sm);">
          <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--cobalt); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; margin-bottom: 14px; font-family: var(--font-heading);">3</div>
          <h3 style="font-size: 19px; color: var(--cobalt); margin-bottom: 8px;">Match and claim</h3>
          <p style="font-size: 14px; color: var(--slate); line-height: 1.6; margin: 0;">Matched items are allocated and claimed securely through verified pick-up schedules and claim codes.</p>
        </div>
      </div>
    </div>

    <!-- Safety and Guidelines Note -->
    <div class="card" style="padding: 28px; border-radius: 20px; background: var(--sky); border: 1px solid var(--border); margin-top: 32px; box-shadow: var(--shadow-sm);">
      <h3 style="font-size: 18px; font-weight: 700; color: var(--cobalt); margin: 0 0 10px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="shield-check" style="width:20px;height:20px;color:var(--cobalt);"></i>
        <span>Safety &amp; Community Standards</span>
      </h3>
      <p style="font-size: 15px; color: var(--navy); line-height: 1.65; margin: 0;">
        iCareDonate prohibits cash solicitations. All item listings and requests are 100% free of charge. Physical handovers are conducted at designated community centers or verified pickup venues.
      </p>
    </div>

    <div style="text-align: center; margin-top: 40px; display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;">
      <a href="/CommunityDono/pages/categories.php" class="btn btn-primary" style="padding: 12px 28px; border-radius: 999px;">Browse categories</a>
      <a href="/CommunityDono/user/offer-donation.php" class="btn btn-donate" style="padding: 12px 28px;">Donate an item now</a>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
