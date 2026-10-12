<?php
// includes/footer.php
$base = '/CommunityDono';
$isPublic = !str_contains($_SERVER['PHP_SELF'], '/admin/') && !str_contains($_SERVER['PHP_SELF'], '/user/');
?>

<?php if ($isPublic): ?>
  <footer class="footer-public" role="contentinfo">
    <div class="container footer-public__container">
      <div class="footer-public__grid">
        
        <!-- Col 1: Brand / Home -->
        <div class="footer-public__col">
          <a href="<?= $base ?>/" class="logo footer-logo" aria-label="iCareDonate — Home">
            <img src="<?= $base ?>/icaredonate-icon.svg" class="logo-icon" alt="iCareDonate" width="36" height="36">
            <span class="logo-wordmark">i<em>Care</em>Donate</span>
          </a>
          <p class="footer-tagline">Give what you can. Help where it matters.</p>
        </div>

        <!-- Col 2: About -->
        <div class="footer-public__col">
          <strong class="footer-heading">About</strong>
          <ul class="footer-links">
            <li><a href="<?= $base ?>/pages/about.php">Our mission</a></li>
            <li><a href="<?= $base ?>/pages/careers.php">Careers</a></li>
          </ul>
        </div>

        <!-- Col 3: Support -->
        <div class="footer-public__col">
          <strong class="footer-heading">Support</strong>
          <ul class="footer-links">
            <li><a href="<?= $base ?>/pages/how-it-works.php">Help center</a></li>
            <li><a href="<?= $base ?>/pages/faq.php">Guides and FAQs</a></li>
            <li><a href="<?= $base ?>/pages/contact.php">Contact support</a></li>
          </ul>
        </div>

        <!-- Col 4: Legal -->
        <div class="footer-public__col">
          <strong class="footer-heading">Legal</strong>
          <ul class="footer-links">
            <li><a href="<?= $base ?>/pages/legal/terms.php">Terms of service</a></li>
            <li><a href="<?= $base ?>/pages/legal/privacy.php">Privacy policy</a></li>
            <li><a href="<?= $base ?>/pages/login.php">Admin log in</a></li>
          </ul>
        </div>

      </div>

      <!-- Bottom Bar -->
      <div class="footer-public__bottom">
        <span>&copy; <?= date('Y') ?> iCareDonate. All rights reserved.</span>
      </div>
    </div>
  </footer>
<?php endif; ?>

  <script src="<?= $base ?>/assets/js/main.js" defer></script>
  <?php if (!empty($extraJs)): ?>
  <script src="<?= $base ?>/assets/js/<?= e($extraJs) ?>" defer></script>
  <?php endif; ?>
  <script>
    // Init Lucide icons
    document.addEventListener('DOMContentLoaded', () => { if(window.lucide) lucide.createIcons(); });
  </script>
</body>
</html>