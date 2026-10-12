/**
 * assets/js/auth.js
 * Authentication interactions: password visibility toggle, Caps Lock detection, dev prefill
 */

(function () {
  'use strict';

  // 1. Password show / hide toggle
  var toggleBtn  = document.getElementById('toggle-password');
  var passInput  = document.getElementById('password');
  var eyeIcon    = document.getElementById('pw-eye');
  var eyeOffIcon = document.getElementById('pw-eye-off');

  if (toggleBtn && passInput && eyeIcon && eyeOffIcon) {
    toggleBtn.addEventListener('click', function () {
      var isHidden = passInput.getAttribute('type') === 'password';
      passInput.setAttribute('type', isHidden ? 'text' : 'password');
      eyeIcon.style.display    = isHidden ? 'none'   : 'inline';
      eyeOffIcon.style.display = isHidden ? 'inline' : 'none';
      toggleBtn.setAttribute('aria-label',   isHidden ? 'Hide password' : 'Show password');
      toggleBtn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
    });
  }

  // 2. Caps Lock detection
  var capsWarn = document.getElementById('caps-lock-warning');
  if (passInput && capsWarn) {
    function checkCaps(e) {
      if (typeof e.getModifierState === 'function') {
        capsWarn.classList.toggle('active', e.getModifierState('CapsLock'));
      }
    }
    passInput.addEventListener('keydown', checkCaps);
    passInput.addEventListener('keyup',   checkCaps);
    passInput.addEventListener('blur', function () { capsWarn.classList.remove('active'); });
  }

  // 3. Quick test credentials prefill (dev mode)
  function prefill(email, pass) {
    var eEl = document.getElementById('email');
    var pEl = document.getElementById('password');
    if (!eEl || !pEl) return;
    eEl.value = email;
    pEl.value = pass;
    [eEl, pEl].forEach(function (el) {
      el.classList.add('qt-filled');
      el.addEventListener('input', function () { el.classList.remove('qt-filled'); }, { once: true });
    });
    var panel = document.getElementById('quick-test-panel');
    if (panel) {
      panel.style.borderColor = 'var(--cobalt)';
      setTimeout(function () { panel.style.borderColor = ''; }, 700);
    }
  }

  document.querySelectorAll('.qt-btn[data-email]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      prefill(btn.dataset.email, btn.dataset.pass);
    });
  });
})();
