<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Fetch active FAQs from the database — Single Source of Truth
$faqs = [];
try {
    $stmt = $pdo->query('SELECT id, question, answer, category FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Failed to fetch FAQs: ' . $e->getMessage());
}

$pageTitle = 'Guides & FAQs';
$extraCss = 'public.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav-public.php';
?>

<main style="padding: 56px 0 80px; background: var(--color-bg);">
  <div class="container" style="max-width: 800px; margin: 0 auto; padding: 0 20px;">
    
    <div style="text-align: center; margin-bottom: 40px;">
      <span class="badge badge-info" style="margin-bottom: 12px;">Support</span>
      <h1 style="font-family: var(--font-heading); font-size: 36px; color: var(--color-text); margin-bottom: 12px; line-height: 1.2;">
        Guides &amp; FAQs
      </h1>
      <p style="font-size: 16px; color: var(--color-text-muted); max-width: 600px; margin: 0 auto;">
        Find answers to common questions about item donations, requesting assistance, safety standards, and account management.
      </p>
    </div>

    <!-- Accessible Accordion (collapsed by default, fully keyboard operable) -->
    <div class="faq-accordion" role="region" aria-label="Frequently Asked Questions">
      <?php if (empty($faqs)): ?>
        <div class="card" style="padding: 32px; text-align: center; border-radius: var(--radius-lg); border: 1px solid var(--border); background: var(--surface);">
          <p style="color: var(--color-text-muted); margin: 0;">No FAQs are currently published.</p>
        </div>
      <?php else: ?>
        <?php foreach ($faqs as $faq): ?>
          <details class="faq-item">
            <summary class="faq-summary">
              <span><?= e($faq['question']) ?></span>
              <svg class="faq-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </summary>
            <div class="faq-content">
              <p><?= e($faq['answer']) ?></p>
            </div>
          </details>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="card" style="padding: 32px; margin-top: 48px; text-align: center; border-radius: var(--radius-lg); border: 1px solid var(--border); background: var(--surface); box-shadow: var(--shadow-sm);">
      <h3 style="font-family: var(--font-heading); font-size: 20px; font-weight: 700; color: var(--color-text); margin-bottom: 8px;">Still have questions?</h3>
      <p style="color: var(--color-text-muted); font-size: 14.5px; margin-bottom: 20px;">Our community support team is always here to assist you.</p>
      <a href="/CommunityDono/pages/contact.php" class="btn btn-primary" style="border-radius: var(--radius-full); padding: 10px 26px;">Contact Support</a>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
