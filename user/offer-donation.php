<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
requireLogin();

$userId = (int)$_SESSION['user_id'];

// Fetch user defaults
$userStmt = $pdo->prepare('SELECT first_name, last_name, phone, barangay, city FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$userProfile = $userStmt->fetch() ?: [];

$categories = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll();

$pageTitle = 'Offer a Donation — iCareDonate';
$extraCss = 'user.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="user-layout">
  <?php require_once __DIR__ . '/../includes/nav-user.php'; ?>

  <div class="user-main">
    <header class="user-topbar">
      <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-text); margin: 0;">Post a Donation</h1>
      </div>
      <div>
        <a href="/CommunityDono/user/donations.php" class="btn btn-outline" style="font-size: 13.5px; padding: 6px 14px;">
          &larr; My Donations
        </a>
      </div>
    </header>

    <div class="user-content" style="max-width: 840px; margin: 0 auto;">
      <?php renderFlash(); ?>

      <div class="card" style="padding: 32px; border-radius: var(--radius-lg); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm);">
        
        <div style="margin-bottom: 28px; text-align: center;">
          <h2 style="font-family: var(--font-brand); font-size: 28px; color: var(--color-primary); margin-bottom: 6px;">Share Surplus Resources</h2>
          <p style="color: var(--color-text-muted); font-size: 14px;">Provide clear details so community members and moderators can verify your listing.</p>
        </div>

        <form id="donation-form" action="/CommunityDono/api/donations/create.php" method="POST" enctype="multipart/form-data" data-validate>
          <?= csrfField() ?>

          <!-- 1. Donation Information -->
          <div style="margin-bottom: 28px;">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--color-text); border-bottom: 1px solid var(--color-border); padding-bottom: 8px; margin-bottom: 16px;">
              1. Donation Information
            </h3>

            <div class="form-group" style="margin-bottom: 16px;">
              <label class="form-label" for="title">Donation Title / Item Name</label>
              <input type="text" id="title" name="title" class="form-input" required placeholder="e.g. 5 Elementary School Backpacks with Notebooks" maxlength="200">
            </div>

            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 16px; margin-bottom: 16px;">
              <div class="form-group">
                <label class="form-label" for="category_id">Donation Category</label>
                <select id="category_id" name="category_id" class="form-input" required>
                  <option value="">Select Category</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label" for="condition_type">Donation Condition</label>
                <select id="condition_type" name="condition_type" class="form-input" required>
                  <option value="new">New (Unopened / Unused)</option>
                  <option value="good" selected>Good (Fully functional, minor cosmetic wear)</option>
                  <option value="fair">Used (Well-loved, working condition)</option>
                </select>
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1.2fr; gap: 16px; margin-bottom: 16px;">
              <div class="form-group">
                <label class="form-label" for="quantity_total">Quantity</label>
                <input type="number" id="quantity_total" name="quantity_total" class="form-input" required min="1" value="1">
              </div>

              <div class="form-group">
                <label class="form-label" for="unit">Unit</label>
                <input type="text" id="unit" name="unit" class="form-input" required placeholder="e.g. pcs, sets, packs, kg" value="pcs">
              </div>

              <div class="form-group">
                <label class="form-label" for="expiration_date_trigger">Expiration Date (If Food/Meds)</label>
                <div class="icd-input-wrapper">
                  <input type="hidden" id="expiration_date" name="expiration_date" value="">
                  <div id="expiration_date_trigger" class="icd-picker-trigger" role="button" tabindex="0" aria-haspopup="dialog" aria-expanded="false">
                    <span id="expiration_date_display" class="icd-picker-display is-placeholder">N/A or Select Date</span>
                    <i data-lucide="calendar" class="icd-field-icon"></i>
                  </div>
                  <div id="expiration_date_popover" class="icd-popover icd-calendar-popover" role="dialog" aria-label="Select Expiration Date"></div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="description">Item Description</label>
              <textarea id="description" name="description" class="form-input" rows="4" placeholder="Detail the item sizes, specifications, condition notes, and any relevant details..."></textarea>
            </div>
          </div>

          <!-- 2. Personal & Contact Information -->
          <div style="margin-bottom: 28px;">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--color-text); border-bottom: 1px solid var(--color-border); padding-bottom: 8px; margin-bottom: 16px;">
              2. Personal &amp; Contact Information
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label" for="donor_contact_name">Donor Contact Name</label>
                <input type="text" id="donor_contact_name" name="donor_contact_name" class="form-input" required value="<?= e(($userProfile['first_name'] ?? '') . ' ' . ($userProfile['last_name'] ?? '')) ?>">
              </div>

              <div class="form-group">
                <label class="form-label" for="donor_contact_number">Contact Phone Number</label>
                <input type="tel" id="donor_contact_number" name="donor_contact_number" class="form-input" required placeholder="09171234567" value="<?= e($userProfile['phone'] ?? '') ?>">
              </div>
            </div>
          </div>

          <!-- 3. Delivery Method, Location & Schedule (Rebuilt Unified iCareDonate Component) -->
          <div style="margin-bottom: 28px;">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--color-text); border-bottom: 1px solid var(--color-border); padding-bottom: 8px; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
              <i data-lucide="map-pin" style="width:18px;height:18px;color:var(--color-primary);"></i>
              3. Delivery Method, Location &amp; Schedule
            </h3>

            <!-- Delivery Method -->
            <div class="form-group" style="margin-bottom: 18px;">
              <label class="form-label" for="pickup_method">Delivery Method</label>
              <div class="icd-input-wrapper">
                <select id="pickup_method" name="pickup_method" class="form-input" required>
                  <option value="both">Pick-up or Delivery Available</option>
                  <option value="pickup">Pick-up Only (Community / Barangay Meeting Point)</option>
                  <option value="delivery">Delivery Only (Courier)</option>
                </select>
              </div>
            </div>

            <!-- Structured Location Field -->
            <div class="form-group" style="margin-bottom: 18px;">
              <label class="form-label" for="pickup_address">Pickup / Donation Location</label>
              <div class="icd-input-wrapper">
                <input type="text" id="pickup_address" name="pickup_address" class="icd-input-iconic" required placeholder="e.g. Barangay Hall Entrance, Community Center, or Street Address" value="<?= e($userProfile['barangay'] ? $userProfile['barangay'] . ' Community Drop Point' : '') ?>">
                <i data-lucide="map-pin" class="icd-field-icon"></i>
              </div>
              <div class="icd-preset-chips">
                <span style="font-size:11.5px;color:var(--color-text-xmuted);display:inline-flex;align-items:center;">Quick presets:</span>
                <button type="button" class="icd-preset-chip" onclick="selectLocationPreset('Barangay Hall Entrance / Lobby')">🏛 Barangay Hall</button>
                <button type="button" class="icd-preset-chip" onclick="selectLocationPreset('Community Distribution Center')">🤝 Community Center</button>
                <button type="button" class="icd-preset-chip" onclick="selectLocationPreset('Designated Drop-off Hub')">📦 Drop-off Hub</button>
                <button type="button" class="icd-preset-chip" onclick="selectLocationPreset('Home / Residential Address')">🏠 Home / Residence</button>
              </div>
            </div>

            <!-- Structured City / Municipality & Barangay / Area Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
              <div class="form-group">
                <label class="form-label" for="pickup_city">City / Municipality</label>
                <div class="icd-input-wrapper">
                  <input type="text" id="pickup_city" name="pickup_city" class="icd-input-iconic" required placeholder="Pasig City" value="<?= e($userProfile['city'] ?? '') ?>">
                  <i data-lucide="building-2" class="icd-field-icon"></i>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label" for="pickup_barangay">Barangay / Area</label>
                <div class="icd-input-wrapper">
                  <input type="text" id="pickup_barangay" name="pickup_barangay" class="icd-input-iconic" placeholder="San Antonio" value="<?= e($userProfile['barangay'] ?? '') ?>">
                  <i data-lucide="compass" class="icd-field-icon"></i>
                </div>
              </div>
            </div>

            <!-- Unified Date & Available Time Controls Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
              
              <!-- 1. Custom Date Picker Component -->
              <div class="form-group">
                <label class="form-label" for="pickup_date_trigger">Available Date</label>
                <div class="icd-input-wrapper">
                  <input type="hidden" id="pickup_date" name="pickup_date" value="<?= date('Y-m-d') ?>">
                  <div id="pickup_date_trigger" class="icd-picker-trigger" role="button" tabindex="0" aria-haspopup="dialog" aria-expanded="false">
                    <span id="pickup_date_display" class="icd-picker-display"><?= date('M d, Y') ?></span>
                    <i data-lucide="calendar" class="icd-field-icon"></i>
                  </div>
                  <div id="pickup_date_popover" class="icd-popover icd-calendar-popover" role="dialog" aria-label="Select Date"></div>
                </div>
              </div>

              <!-- 2. Custom Available Time Component -->
              <div class="form-group">
                <label class="form-label" for="pickup_time_trigger">Available Time</label>
                <div class="icd-input-wrapper">
                  <input type="hidden" id="pickup_time" name="pickup_time" value="09:00">
                  <div id="pickup_time_trigger" class="icd-picker-trigger" role="button" tabindex="0" aria-haspopup="dialog" aria-expanded="false">
                    <span id="pickup_time_display" class="icd-picker-display">09:00 AM</span>
                    <i data-lucide="clock" class="icd-field-icon"></i>
                  </div>
                  <div id="pickup_time_popover" class="icd-popover icd-time-popover" role="dialog" aria-label="Select Time"></div>
                </div>
              </div>

            </div>

            <!-- Location Notes / Instructions -->
            <div class="form-group">
              <label class="form-label" for="pickup_notes">Pickup Location Notes / Instructions (Optional)</label>
              <textarea id="pickup_notes" name="pickup_notes" class="form-input" rows="2" placeholder="e.g. Available weekdays 4pm-7pm near Barangay Hall lobby, please notify 30 mins before arrival..."></textarea>
            </div>
          </div>

          <!-- 4. Image or Video Upload -->
          <div style="margin-bottom: 32px;">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--color-text); border-bottom: 1px solid var(--color-border); padding-bottom: 8px; margin-bottom: 16px;">
              4. Media Upload (Photos or Video Link)
            </h3>

            <div class="form-group" style="margin-bottom: 16px;">
              <label class="form-label">Upload Photos (Max 5 images)</label>
              <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="form-input" data-preview-target="#image-preview-grid">
              <div id="image-preview-grid" class="image-preview-grid" style="margin-top: 12px;"></div>
            </div>

            <div class="form-group">
              <label class="form-label" for="video_url">Video Demonstration Link (Optional)</label>
              <input type="url" id="video_url" name="video_url" class="form-input" placeholder="https://youtube.com/... or cloud video link">
            </div>
          </div>

          <!-- Action buttons with Donation Preview -->
          <div style="display: flex; gap: 12px; justify-content: flex-end; align-items: center;">
            <a href="/CommunityDono/user/donations.php" class="btn btn-outline">Cancel</a>
            <button type="button" class="btn btn-soft" onclick="openDonationPreview();" style="border: 1px solid var(--color-border);">
              <i data-lucide="eye" style="width:14px;height:14px;"></i> Donation Preview
            </button>
            <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-size: 15px;">
              Submit for Verification &rarr;
            </button>
          </div>

        </form>
      </div>

    </div>
  </div>
</div>

<!-- Donation Preview Modal -->
<div id="preview-modal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; padding: 20px;">
  <div class="modal-panel card" style="max-width: 600px; width: 100%; padding: 28px; border-radius: var(--radius-lg); background: #FFFFFF; max-height: 85vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="font-size: 20px; font-weight: 700; color: var(--color-primary); margin: 0;">Donation Listing Preview</h3>
      <button type="button" onclick="document.getElementById('preview-modal').style.display='none';" style="background: none; border: none; font-size: 22px; cursor: pointer;">&times;</button>
    </div>

    <div id="preview-content" style="display: flex; flex-direction: column; gap: 14px; font-size: 14px;"></div>

    <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 10px;">
      <button type="button" onclick="document.getElementById('preview-modal').style.display='none';" class="btn btn-outline">Back to Edit</button>
      <button type="button" onclick="document.getElementById('preview-modal').style.display='none'; document.getElementById('donation-form').submit();" class="btn btn-primary">
        Confirm &amp; Submit Listing
      </button>
    </div>
  </div>
</div>

<script src="/CommunityDono/assets/js/date-time-picker.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Ensure lucide renders any dynamic icons
  if (window.lucide) { lucide.createIcons(); }

  // 1. Initialize custom iCareDonate Expiration Date Picker (optional)
  initIcdDatePicker({
    trigger: 'expiration_date_trigger',
    popover: 'expiration_date_popover',
    display: 'expiration_date_display',
    hidden: 'expiration_date',
    minDate: '<?= date('Y-m-d') ?>',
    placeholder: 'N/A or Select Date'
  });

  // 2. Initialize custom iCareDonate Pickup Date Picker
  initIcdDatePicker({
    trigger: 'pickup_date_trigger',
    popover: 'pickup_date_popover',
    display: 'pickup_date_display',
    hidden: 'pickup_date',
    minDate: '<?= date('Y-m-d') ?>',
    placeholder: 'Select date'
  });

  // 3. Initialize custom iCareDonate Time Picker
  initIcdTimePicker({
    trigger: 'pickup_time_trigger',
    popover: 'pickup_time_popover',
    display: 'pickup_time_display',
    hidden: 'pickup_time'
  });
});

function selectLocationPreset(preset) {
  var input = document.getElementById('pickup_address');
  if (input) {
    input.value = preset;
    input.focus();
    // Dispatch change event for validation if attached
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }
}

function openDonationPreview() {
  const form = document.getElementById('donation-form');
  const title = form.title.value || 'Untitled Donation';
  const cat = form.category_id.options[form.category_id.selectedIndex]?.text || 'Uncategorized';
  const cond = form.condition_type.options[form.condition_type.selectedIndex]?.text || 'Good';
  const qty = form.quantity_total.value || '1';
  const unit = form.unit.value || 'pcs';
  const desc = form.description.value || 'No description entered.';
  const donorName = form.donor_contact_name.value || 'Not provided';
  const donorPhone = form.donor_contact_number.value || 'Not provided';
  const location = form.pickup_address.value || 'Not specified';
  const city = form.pickup_city.value || '';
  const barangay = form.pickup_barangay.value || '';
  const method = form.pickup_method.options[form.pickup_method.selectedIndex]?.text || 'Pickup';
  const dateDisp = document.getElementById('pickup_date_display')?.textContent || 'Flexible';
  const timeDisp = document.getElementById('pickup_time_display')?.textContent || 'Flexible';

  const fullLocation = [location, barangay, city].filter(Boolean).join(', ');

  const html = `
    <div style="background: var(--color-tint-teal); padding: 16px; border-radius: var(--radius-md);">
      <div style="font-size: 11px; color: var(--color-primary); font-weight: 700; text-transform: uppercase;">${cat}</div>
      <div style="font-size: 18px; font-weight: 700; color: var(--color-text); margin-top: 4px;">${title}</div>
      <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 2px;">Quantity: <strong>${qty} ${unit}</strong> · Condition: <strong>${cond}</strong></div>
    </div>
    <div><strong>Description:</strong><p style="color: var(--color-text-muted); margin: 4px 0 0;">${desc}</p></div>
    <div style="background: #F8FAFC; padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--color-border); display: grid; gap: 8px;">
      <div><strong>Donor:</strong> ${donorName} (&#128222; ${donorPhone})</div>
      <div><strong>Delivery Method:</strong> ${method}</div>
      <div><strong>Location:</strong> 📍 ${fullLocation}</div>
      <div><strong>Schedule:</strong> 📅 ${dateDisp} at ◷ ${timeDisp}</div>
    </div>
  `;
  document.getElementById('preview-content').innerHTML = html;
  document.getElementById('preview-modal').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
