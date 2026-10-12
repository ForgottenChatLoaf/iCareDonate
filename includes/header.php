<?php
declare(strict_types=1);
//  includes/header.php

// $pageTitle   — set by the including file
// $extraCss    — additional CSS file name (without path) e.g. 'public.css'
// $bodyClass   — optional body class

$pageTitle ??= 'iCareDonate';
$extraCss  ??= '';
$bodyClass ??= '';
$base = '/CommunityDono';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — iCareDonate</title>
  <meta name="description" content="<?= e($metaDesc ?? 'Give what you can. Help where it matters. iCareDonate connects donors with communities that need them.') ?>">
  <meta name="theme-color" content="#1F4FA3">
  <script>
    (function() {
      try {
        var saved = localStorage.getItem('icd-theme');
        var theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);
        var meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.setAttribute('content', theme === 'dark' ? '#5B8DE8' : '#1F4FA3');
      } catch(e) {}
    })();
  </script>

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="<?= $base ?>/icaredonate-icon.svg">

  <!-- Google Fonts: Poppins (headings/buttons/wordmark) + Inter (body/forms) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js" defer></script>

  <!-- Design system CSS (Cache-busted) - Isolated PHP Stylesheets -->
  <link rel="stylesheet" href="<?= $base ?>/assets/css/php/tokens.css?v=<?= file_exists(__DIR__ . '/../assets/css/php/tokens.css') ? filemtime(__DIR__ . '/../assets/css/php/tokens.css') : '1' ?>">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/php/base.css?v=<?= file_exists(__DIR__ . '/../assets/css/php/base.css') ? filemtime(__DIR__ . '/../assets/css/php/base.css') : '1' ?>">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/php/components.css?v=<?= file_exists(__DIR__ . '/../assets/css/php/components.css') ? filemtime(__DIR__ . '/../assets/css/php/components.css') : '1' ?>">

  <?php if ($extraCss): ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/php/<?= e($extraCss) ?>?v=<?= file_exists(__DIR__ . '/../assets/css/php/' . $extraCss) ? filemtime(__DIR__ . '/../assets/css/php/' . $extraCss) : '1' ?>">
  <?php endif; ?>

  <?php if (!empty($extraHead)): ?>
  <?= $extraHead ?>
  <?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?>">