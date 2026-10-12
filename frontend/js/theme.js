/**
 * iCareDonate — Theme Controller (Light Mode Enforced)
 * frontend/js/theme.js
 */

(function() {
  'use strict';

  try {
    localStorage.removeItem('icd-theme');
  } catch(e) {}

  document.documentElement.setAttribute('data-theme', 'light');

  const meta = document.querySelector('meta[name="theme-color"]');
  if (meta) {
    meta.setAttribute('content', '#1F4FA3');
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.documentElement.setAttribute('data-theme', 'light');
    // Hide any theme toggle elements
    document.querySelectorAll('[data-theme-toggle], .footer-theme-toggle-wrap, .btn-theme-toggle').forEach(el => {
      el.style.display = 'none';
    });
  });

  window.ICD_THEME = {
    get: () => 'light',
    toggle: () => {},
    set: () => {}
  };
})();
