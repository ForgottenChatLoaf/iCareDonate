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

  // Logout Confirmation Overlay Modal
  function initLogoutModal() {
    let modal = document.getElementById('logoutConfirmModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'logoutConfirmModal';
      modal.className = 'modal-backdrop';
      modal.style.cssText = 'position:fixed;inset:0;background:rgba(20,33,61,0.65);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px;';
      modal.setAttribute('role', 'dialog');
      modal.setAttribute('aria-modal', 'true');
      modal.setAttribute('aria-labelledby', 'logoutModalTitle');
      modal.innerHTML = `
        <div style="background:#FFFFFF;border-radius:18px;max-width:390px;width:100%;padding:28px 24px;text-align:center;box-shadow:0 20px 45px rgba(20,33,61,0.25);border:1px solid #E2E8F0;">
          <div style="width:52px;height:52px;border-radius:50%;background:#FEE2E2;color:#DC2626;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" x2="9" y1="12" y2="12"/>
            </svg>
          </div>
          <h3 id="logoutModalTitle" style="font-size:18px;font-weight:700;color:#1E293B;margin:0 0 8px;font-family:var(--font-heading, inherit);">Confirm Logout</h3>
          <p style="font-size:13.5px;color:#64748B;line-height:1.55;margin:0 0 24px;">Are you sure you want to log out of your administrative account?</p>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <button type="button" id="logoutModalCancelBtn" class="btn" style="padding:10px 16px;font-size:14px;font-weight:600;border-radius:10px;border:1px solid #CBD5E1;color:#475569;background:#FFFFFF;cursor:pointer;">
              Cancel
            </button>
            <button type="button" id="logoutModalConfirmBtn" class="btn" style="padding:10px 16px;font-size:14px;font-weight:600;border-radius:10px;background:#E0553D;color:#FFFFFF;border:none;cursor:pointer;">
              Log Out
            </button>
          </div>
        </div>
      `;
      document.body.appendChild(modal);

      function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
      }

      function openModal(e) {
        if (e) e.preventDefault();
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
      }

      modal.querySelector('#logoutModalCancelBtn').addEventListener('click', closeModal);
      modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
      });
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
      });

      modal.querySelector('#logoutModalConfirmBtn').addEventListener('click', function() {
        try {
          localStorage.removeItem('icd_token');
          localStorage.removeItem('icd_user');
          sessionStorage.clear();
        } catch(e) {}
        window.location.href = '../../logout.php';
      });

      // Bind all logout triggers
      document.addEventListener('click', function(e) {
        const trigger = e.target.closest('.sidebar-link--logout, #sidebarLogoutBtn, .btn-logout, a.dropdown-danger, a[href*="logout.php"], a[href*="logout.html"], a[href*="login.html"]');
        if (trigger && (trigger.classList.contains('sidebar-link--logout') || trigger.id === 'sidebarLogoutBtn' || trigger.classList.contains('dropdown-danger') || trigger.textContent.toLowerCase().includes('log out') || trigger.textContent.toLowerCase().includes('logout'))) {
          openModal(e);
        }
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    setupAdminNav();
    setupAdminTopbarUser();
    initLogoutModal();
  });

})();
