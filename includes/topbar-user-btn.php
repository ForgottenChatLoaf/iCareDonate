<?php
// includes/topbar-user-btn.php
// Usage: $topbarBtnId = 'myBtnId'; $profileUrl = '/...'; $isAdmin = true; require_once ...;
$_topbarBtnId = $topbarBtnId ?? 'adminUserMenuBtn';
$_dropdownId  = 'dd_' . $_topbarBtnId;
$_profileUrl  = $profileUrl  ?? '/CommunityDono/admin/profile.php';
$_isAdmin     = $isAdmin     ?? false;
$_userName    = e($_SESSION['user_name'] ?? ($_isAdmin ? 'Admin' : 'User'));
$_nameParts   = explode(' ', trim($_SESSION['user_name'] ?? 'AU'));
$_initials    = strtoupper(substr($_nameParts[0] ?? 'A', 0, 1) . substr($_nameParts[count($_nameParts) - 1] ?? 'U', 0, 1));
?>
<div style="position: relative;" id="<?= $_topbarBtnId ?>_wrap">
  <button type="button" class="admin-user-btn" id="<?= $_topbarBtnId ?>"
          aria-haspopup="true" aria-expanded="false" aria-controls="<?= $_dropdownId ?>">
    <span class="user-avatar-circle"><?= $_initials ?></span>
    <span><?= $_userName ?></span>
    <i data-lucide="chevron-down" style="width:13px;height:13px;opacity:0.55;" class="topbar-chevron"></i>
  </button>
  <div class="topbar-dropdown" id="<?= $_dropdownId ?>" role="menu" aria-label="Account menu">
    <a href="<?= $_profileUrl ?>" role="menuitem">
      <i data-lucide="<?= $_isAdmin ? 'shield' : 'user' ?>" style="width:15px;height:15px;opacity:0.7;"></i>
      <?= $_isAdmin ? 'Admin Profile' : 'My Profile' ?>
    </a>
    <?php if ($_isAdmin): ?>
    <a href="/CommunityDono/admin/profile.php" role="menuitem">
      <i data-lucide="settings" style="width:15px;height:15px;opacity:0.7;"></i> Account Settings
    </a>
    <?php else: ?>
    <a href="/CommunityDono/user/settings.php" role="menuitem">
      <i data-lucide="settings" style="width:15px;height:15px;opacity:0.7;"></i> Settings
    </a>
    <?php endif; ?>
    <a href="/CommunityDono/api/auth/logout.php" class="dropdown-danger" role="menuitem">
      <i data-lucide="log-out" style="width:15px;height:15px;"></i> Log Out
    </a>
  </div>
</div>
<script>
(function() {
  var btn = document.getElementById('<?= $_topbarBtnId ?>');
  var dd  = document.getElementById('<?= $_dropdownId ?>');
  if (!btn || !dd) return;

  function openDropdown() {
    dd.classList.add('is-open');
    btn.setAttribute('aria-expanded', 'true');
    var chev = btn.querySelector('.topbar-chevron');
    if (chev) chev.style.transform = 'rotate(180deg)';
  }

  function closeDropdown() {
    dd.classList.remove('is-open');
    btn.setAttribute('aria-expanded', 'false');
    var chev = btn.querySelector('.topbar-chevron');
    if (chev) chev.style.transform = '';
  }

  btn.addEventListener('click', function(e) {
    e.stopPropagation();
    if (dd.classList.contains('is-open')) {
      closeDropdown();
    } else {
      openDropdown();
    }
  });

  /* Close when clicking outside */
  document.addEventListener('click', function(e) {
    if (!btn.contains(e.target) && !dd.contains(e.target)) {
      closeDropdown();
    }
  });

  /* Close on Escape key */
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && dd.classList.contains('is-open')) {
      closeDropdown();
      btn.focus();
    }
  });
})();
</script>

