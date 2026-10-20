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
  document.documentElement.classList.add('js-enabled');

  // Early auth check to prevent flash of wrong navigation state
  try {
    const token = localStorage.getItem('icd_token');
    const user = JSON.parse(localStorage.getItem('icd_user') || 'null');
    if (token && user) {
      document.documentElement.setAttribute('data-auth', 'true');
    }
  } catch(e) {}

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
