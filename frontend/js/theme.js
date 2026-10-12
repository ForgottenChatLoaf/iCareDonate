/**
 * iCareDonate — Theme Controller (Dark / Light Mode)
 * frontend/js/theme.js
 */

(function() {
  'use strict';

  const STORAGE_KEY = 'icd-theme';

  function getPreferredTheme() {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored === 'dark' || stored === 'light') return stored;
    } catch(e) {}
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      return 'dark';
    }
    return 'light';
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    
    // Update theme-color meta tag
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
      meta.setAttribute('content', theme === 'dark' ? '#0B1324' : '#1F4FA3');
    }

    // Update all toggle buttons in header and footer
    const toggles = document.querySelectorAll('[data-theme-toggle]');
    toggles.forEach(btn => {
      btn.setAttribute('aria-pressed', theme === 'dark');
      btn.setAttribute('title', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
      
      const iconSpan = btn.querySelector('.theme-icon');
      if (iconSpan) {
        if (theme === 'dark') {
          iconSpan.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>';
        } else {
          iconSpan.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>';
        }
      }

      const textSpan = btn.querySelector('.theme-toggle-text');
      if (textSpan) {
        textSpan.textContent = theme === 'dark' ? 'Light mode' : 'Dark mode';
      }
    });

    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch(e) {}
  }

  function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    applyTheme(next);
  }

  // Set initial theme without waiting
  const initialTheme = getPreferredTheme();
  document.documentElement.setAttribute('data-theme', initialTheme);

  // Initialize event listeners when DOM is loaded
  document.addEventListener('DOMContentLoaded', function() {
    applyTheme(getPreferredTheme());

    document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
      btn.addEventListener('click', toggleTheme);
    });

    // Listen for OS system theme changes if user has no saved preference
    if (window.matchMedia) {
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (!localStorage.getItem(STORAGE_KEY)) {
          applyTheme(e.matches ? 'dark' : 'light');
        }
      });
    }
  });

  window.ICD_THEME = {
    get: () => document.documentElement.getAttribute('data-theme') || 'light',
    toggle: toggleTheme,
    set: applyTheme
  };
})();
