/**
 * iCareDonate — Wireframe JavaScript (wireframe/js/wireframe.js)
 * Provides mobile menu toggle, interactive collapsible handlers,
 * lucide icon rendering, and wireframe navigation utilities.
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Mobile Menu Toggle
  const hamburgerBtn = document.getElementById('nav-hamburger') || document.querySelector('.nav-hamburger');
  const mobileMenu = document.getElementById('mobile-menu') || document.querySelector('.mobile-menu');

  if (hamburgerBtn && mobileMenu) {
    hamburgerBtn.addEventListener('click', function () {
      const isExpanded = hamburgerBtn.getAttribute('aria-expanded') === 'true';
      hamburgerBtn.setAttribute('aria-expanded', !isExpanded);
      if (isExpanded) {
        mobileMenu.setAttribute('hidden', '');
      } else {
        mobileMenu.removeAttribute('hidden');
      }
    });
  }

  // 2. Ensure all reveal-on-scroll elements are visible immediately
  document.querySelectorAll('.reveal-on-scroll').forEach(function (el) {
    el.classList.add('is-visible');
    el.style.opacity = '1';
    el.style.transform = 'none';
  });

  // 3. Render Lucide icons if library is loaded
  if (window.lucide && typeof window.lucide.createIcons === 'function') {
    try {
      window.lucide.createIcons();
    } catch (e) {}
  }

  // 4. Wireframe placeholder notification for non-nav forms
  const forms = document.querySelectorAll('form');
  forms.forEach(function (form) {
    if (!form.getAttribute('action') || form.getAttribute('action') === '#' || form.getAttribute('action').includes('login_process')) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        alert('[Wireframe Preview: Form submission placeholder]');
      });
    }
  });
});
