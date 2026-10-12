<?php
declare(strict_types=1);
$pageTitle = '404 Page Not Found — iCareDonate';
$extraCss = 'public.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav-public.php';
?>
<main style="min-height: 60vh; display: flex; align-items: center; justify-content: center; text-align: center; padding: 40px 20px;">
  <div style="max-width: 500px;">
    <div style="font-family: var(--font-brand); font-size: 72px; color: var(--color-primary); line-height: 1;">404</div>
    <h1 style="font-family: var(--font-brand); font-size: 28px; color: var(--color-text); margin: 16px 0 8px;">Page Not Found</h1>
    <p style="color: var(--color-text-muted); font-size: 15px; margin-bottom: 24px;">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
    <a href="/CommunityDono/" class="btn btn-primary">Return to Homepage</a>
  </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>