/**
 * iCareDonate — User Portal Interactive Client Engine
 * frontend/js/user-portal.js
 * Vanilla JavaScript UI behaviors for all user pages
 */

(function() {
  'use strict';

  // Highlight active sidebar navigation based on data-nav attribute
  function setupActiveNav() {
    const path = window.location.pathname;
    const page = path.split('/').pop().replace('.html', '') || 'dashboard';
    
    document.querySelectorAll('.sidebar-link[data-nav]').forEach(link => {
      const navKey = link.getAttribute('data-nav');
      if (navKey === page || (page === 'index' && navKey === 'dashboard')) {
        link.classList.add('sidebar-link--active');
      } else {
        link.classList.remove('sidebar-link--active');
      }
    });
  }

  // Topbar dropdown toggle
  function setupTopbarUser() {
    const btn = document.getElementById('topbarUserMenuBtn');
    const dd = document.getElementById('topbarDropdownMenu');
    if (!btn || !dd) return;

    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      const open = dd.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      const chev = btn.querySelector('.topbar-chevron');
      if (chev) chev.style.transform = open ? 'rotate(180deg)' : '';
    });

    document.addEventListener('click', function(e) {
      if (!btn.contains(e.target) && !dd.contains(e.target)) {
        dd.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
        const chev = btn.querySelector('.topbar-chevron');
        if (chev) chev.style.transform = '';
      }
    });
  }

  // Flash Message Helper
  window.showPortalFlash = function(type, message) {
    const existing = document.getElementById('portal-flash-msg');
    if (existing) existing.remove();

    const flash = document.createElement('div');
    flash.id = 'portal-flash-msg';
    flash.className = `alert alert-${type === 'success' ? 'success' : 'error'}`;
    flash.style.cssText = 'margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; border-radius: 8px; font-size: 13.5px;';
    flash.innerHTML = `
      <div style="display:flex;align-items:center;gap:10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
        <span>${message}</span>
      </div>
      <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-size:18px;line-height:1;color:inherit;">&times;</button>
    `;

    const container = document.querySelector('.user-content') || document.querySelector('.admin-content');
    if (container) {
      container.insertBefore(flash, container.firstChild);
      setTimeout(() => { if (flash.parentElement) flash.remove(); }, 6000);
    }
  };

  document.addEventListener('DOMContentLoaded', function() {
    setupActiveNav();
    setupTopbarUser();
  });

})();
