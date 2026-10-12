<?php
declare(strict_types=1);
$pageTitle = '403 Access Forbidden — iCareDonate';
$extraCss = 'public.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav-public.php';
?>
<main style="min-height: 60vh; display: flex; align-items: center; justify-content: center; text-align: center; padding: 40px 20px;">
  <div style="max-width: 500px;">
    <div style="font-family: var(--font-brand); font-size: 72px; color: var(--color-accent); line-height: 1;">403</div>
    <h1 style="font-family: var(--font-brand); font-size: 28px; color: var(--color-text); margin: 16px 0 8px;">Access Forbidden</h1>
    <p style="color: var(--color-text-muted); font-size: 15px; margin-bottom: 24px;">You do not have permission to view this page or perform this action.</p>
    <a href="<?= $base ?>/" class="btn btn-primary">Return to Homepage</a>
  </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>