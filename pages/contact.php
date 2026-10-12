<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$submitted = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        try {
            $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $stmt = $pdo->prepare('
                INSERT INTO support_tickets (user_id, subject, message, email, status, created_at)
                VALUES (?, ?, ?, ?, "open", NOW())
            ');
            $stmt->execute([$userId, "[$name] " . $subject, $message, $email]);
            $submitted = true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            $error = 'Failed to submit inquiry. Please try again later.';
        }
    }
}

$pageTitle = 'Contact Us — iCareDonate';
$extraCss = 'public.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav-public.php';
?>

<main style="padding: 40px 0 80px;">
  <div class="container" style="max-width: 960px; margin: 0 auto; padding: 0 20px;">
    
    <div style="margin-bottom: 28px;">
      <h1 style="margin-bottom: 8px;">Contact us</h1>
      <p class="lead" style="margin-bottom: 0;">Have a question or need assistance with donations or requests? Get in touch.</p>
    </div>

    <?php if ($submitted): ?>
      <div class="card" style="padding: 48px; text-align: center; border-radius: 20px; border: 1px solid var(--sky); box-shadow: var(--shadow-sm);">
        <div style="color: var(--cobalt); margin-bottom: 16px;"><i data-lucide="circle-check-big" style="width:52px;height:52px;"></i></div>
        <h2 style="font-size: 24px; color: var(--cobalt); margin-bottom: 10px;">Thanks. We received your inquiry.</h2>
        <p style="color: var(--slate); font-size: 15.5px; margin-bottom: 24px;">
          A team coordinator will review and respond to <strong><?= e($email) ?></strong>.
        </p>
        <a href="/CommunityDono/" class="btn btn-primary" style="border-radius: 999px; padding: 12px 28px;">Return to Homepage</a>
      </div>
    <?php else: ?>
      
      <?php if (!empty($error)): ?>
        <div class="alert alert-error" style="margin-bottom: 20px;">
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <div style="display: grid; grid-template-columns: 320px 1fr; gap: 24px;">
        <!-- Left Column: Contact Cards -->
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <div class="card" style="padding: 22px 24px; border-radius: 20px; border: 1px solid var(--sky); box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; color: var(--cobalt);">
              <i data-lucide="phone" style="width: 18px; height: 18px;"></i>
              <b style="font-size: 14.5px; color: var(--navy); font-family: var(--font-heading);">Phone number</b>
            </div>
            <p style="font-size: 14px; color: var(--slate); margin: 0;">+63 (02) 8123-4567</p>
          </div>

          <div class="card" style="padding: 22px 24px; border-radius: 20px; border: 1px solid var(--sky); box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; color: var(--cobalt);">
              <i data-lucide="map-pin" style="width: 18px; height: 18px;"></i>
              <b style="font-size: 14.5px; color: var(--navy); font-family: var(--font-heading);">Address / location</b>
            </div>
            <p style="font-size: 14px; color: var(--slate); margin: 0;">Metro Manila, Philippines</p>
          </div>

          <div class="card" style="padding: 22px 24px; border-radius: 20px; border: 1px solid var(--sky); box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; color: var(--cobalt);">
              <i data-lucide="mail" style="width: 18px; height: 18px;"></i>
              <b style="font-size: 14.5px; color: var(--navy); font-family: var(--font-heading);">Email address</b>
            </div>
            <p style="font-size: 14px; color: var(--slate); margin: 0;">support@icaredonate.org</p>
          </div>
        </div>

        <!-- Right Column: Inquiry Form -->
        <div class="card" style="padding: 32px; border-radius: 20px; border: 1px solid var(--sky); box-shadow: var(--shadow-sm);">
          <h3 style="font-size: 22px; color: var(--cobalt); margin-bottom: 20px;">Inquiry form</h3>
          
          <form method="POST" action="/CommunityDono/pages/contact.php" data-validate>
            <?= csrfField() ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
              <div class="form-group">
                <label class="form-label" for="name">Name</label>
                <input type="text" id="name" name="name" class="form-input" required placeholder="Your full name" value="<?= e($_SESSION['user_name'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-input" required placeholder="you@example.com" value="<?= e($_SESSION['user_email'] ?? '') ?>">
              </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
              <label class="form-label" for="subject">Type</label>
              <select id="subject" name="subject" class="form-input" required>
                <option value="General">General</option>
                <option value="Partnership or sponsorship">Partnership or sponsorship</option>
                <option value="Volunteer">Volunteer</option>
                <option value="Media">Media</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
              <label class="form-label" for="message">Message</label>
              <textarea id="message" name="message" class="form-input" rows="4" required placeholder="Write your inquiry message here..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 14.5px;">
              Send inquiry
            </button>
          </form>
        </div>
      </div>

    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
