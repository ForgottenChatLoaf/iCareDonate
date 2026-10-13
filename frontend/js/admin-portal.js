/**
 * iCareDonate — Admin Portal Interactive Engine
 * frontend/js/admin-portal.js
 * Handles client-side search, filtering, archival workflow, and user moderation
 */

(function() {
  'use strict';

  // Highlight active sidebar navigation based on data-nav attribute
  function setupAdminNav() {
    const path = window.location.pathname;
    const page = path.split('/').pop().replace('.html', '') || 'index';
    
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
  function setupAdminTopbarUser() {
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

  // Flash Notification Helper
  window.showAdminFlash = function(type, message) {
    const existing = document.getElementById('admin-flash-msg');
    if (existing) existing.remove();

    const flash = document.createElement('div');
    flash.id = 'admin-flash-msg';
    flash.className = `alert alert-${type === 'success' ? 'success' : type === 'info' ? 'info' : 'error'}`;
    flash.style.cssText = 'margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; border-radius: 8px; font-size: 13.5px; z-index: 100;';
    
    let iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>';
    if (type === 'info') {
      iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="16" y2="12"/><line x1="12" x2="12.01" y1="8" y2="8"/></svg>';
    }

    flash.innerHTML = `
      <div style="display:flex;align-items:center;gap:10px;">
        ${iconSvg}
        <span>${message}</span>
      </div>
      <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-size:18px;line-height:1;color:inherit;">&times;</button>
    `;

    const container = document.querySelector('.admin-content');
    if (container) {
      container.insertBefore(flash, container.firstChild);
      setTimeout(() => { if (flash.parentElement) flash.remove(); }, 6000);
    }
  };

  document.addEventListener('DOMContentLoaded', function() {
    setupAdminNav();
    setupAdminTopbarUser();
  });

})();
