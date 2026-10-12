/**
 * js/script.js
 * iCareDonate — Master Frontend JavaScript
 * Handles:
 *  - Theme switcher (Dark / Light mode with localStorage)
 *  - Responsive navigation & mobile menu toggling
 *  - FAQ accordions & collapsible sections
 *  - Modal dialogues
 *  - Form validation helpers
 *  - Progressive enhancement for homepage dynamic data
 */

(function () {
  'use strict';

  /* ── 1. Lucide & Fallback Icon Renderer ────────────────── */
  function initIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      window.lucide.createIcons();
    }
  }

  /* ── 2. Theme (Light Mode Enforced) ───────────────────── */
  function initTheme() {
    document.documentElement.setAttribute('data-theme', 'light');
    try { localStorage.removeItem('icd-theme'); } catch (e) {}
    var metaTheme = document.querySelector('meta[name="theme-color"]');
    if (metaTheme) metaTheme.setAttribute('content', '#1F4FA3');
  }

  /* ── 3. Responsive Mobile Navigation ───────────────────── */
  function initMobileNav() {
    var hamburger = document.getElementById('nav-hamburger') || document.querySelector('.nav-hamburger');
    var mobileMenu = document.getElementById('mobile-menu') || document.querySelector('.mobile-menu');

    if (hamburger && mobileMenu && !hamburger.dataset.navBound) {
      hamburger.dataset.navBound = 'true';
      hamburger.addEventListener('click', function () {
        var isHidden = mobileMenu.hasAttribute('hidden');
        if (isHidden) {
          mobileMenu.removeAttribute('hidden');
          hamburger.setAttribute('aria-expanded', 'true');
        } else {
          mobileMenu.setAttribute('hidden', '');
          hamburger.setAttribute('aria-expanded', 'false');
        }
      });
    }
  }

  /* ── 4. Accessible Modal Windows ───────────────────────── */
  function initModals() {
    document.querySelectorAll('[data-modal-open]').forEach(function (trigger) {
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        var modalId = trigger.getAttribute('data-modal-open');
        var modal = document.getElementById(modalId);
        if (modal) {
          modal.style.display = 'grid';
          document.body.style.overflow = 'hidden';
        }
      });
    });

    document.querySelectorAll('[data-modal-close], .modal__overlay').forEach(function (closer) {
      closer.addEventListener('click', function (e) {
        var modal = closer.closest('.modal, [role="dialog"]');
        if (modal) {
          modal.style.display = 'none';
          document.body.style.overflow = '';
        }
      });
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal, [role="dialog"]').forEach(function (modal) {
          if (modal.style.display !== 'none' && modal.style.display !== '') {
            modal.style.display = 'none';
            document.body.style.overflow = '';
          }
        });
      }
    });
  }

  /* ── 5. Flash Alerts Auto-Dismiss ──────────────────────── */
  function initAlerts() {
    var alerts = document.querySelectorAll('.alert--dismissible, .alert-dismissible');
    alerts.forEach(function (alert) {
      setTimeout(function () {
        alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-6px)';
        setTimeout(function () { alert.remove(); }, 400);
      }, 5000);
    });
  }

  /* ── 6. Client-Side Form Validation Helpers ────────────── */
  function initFormValidation() {
    var forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(function (form) {
      form.addEventListener('submit', function (e) {
        var requiredInputs = form.querySelectorAll('[required]');
        var hasError = false;

        requiredInputs.forEach(function (input) {
          if (!input.value.trim()) {
            hasError = true;
            input.classList.add('form-input--error');
          } else {
            input.classList.remove('form-input--error');
          }
        });

        if (hasError) {
          e.preventDefault();
          alert('Please fill out all required fields before submitting.');
        }
      });
    });
  }

  /* ── 7. Progressive Enhancement: Live Stats on index.html ── */
  function initDynamicEnhancement() {
    var statsSection = document.getElementById('platform-impact-stats');
    if (statsSection) {
      // Attempt to fetch fresh numbers if server is online
      fetch('/CommunityDono/api/v1/donations.php?action=stats')
        .then(function (res) {
          if (res.ok) return res.json();
          throw new Error('API offline');
        })
        .then(function (data) {
          if (data && data.stats) {
            var elDonations = document.getElementById('stat-donations-count');
            var elClaimed   = document.getElementById('stat-claimed-items');
            var elUsers     = document.getElementById('stat-community-users');
            if (elDonations && data.stats.donations_count !== undefined) {
              elDonations.textContent = Number(data.stats.donations_count).toLocaleString() + '+';
            }
            if (elClaimed && data.stats.claimed_items !== undefined) {
              elClaimed.textContent = Number(data.stats.claimed_items).toLocaleString() + '+';
            }
            if (elUsers && data.stats.community_users !== undefined) {
              elUsers.textContent = Number(data.stats.community_users).toLocaleString() + '+';
            }
          }
        })
        .catch(function () {
          // Silent fallback to static numbers already in the HTML
        });
    }
  }

  /* ── Initialize on DOMContentLoaded ────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    initIcons();
    initTheme();
    initMobileNav();
    initModals();
    initAlerts();
    initFormValidation();
    initDynamicEnhancement();
  });

})();
