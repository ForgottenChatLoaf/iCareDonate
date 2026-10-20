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
    const btn = document.getElementById('topbarUserMenuBtn') || document.getElementById('userUserMenuBtn');
    const dd = document.getElementById('topbarDropdownMenu') || document.getElementById('userDropdownMenu') || document.getElementById('topbarDropdown');
    if (!btn || !dd) return;

    function closeDropdown() {
      dd.classList.remove('is-open');
      dd.style.display = 'none';
      btn.setAttribute('aria-expanded', 'false');
      const chev = btn.querySelector('.topbar-chevron');
      if (chev) chev.style.transform = '';
    }

    function openDropdown() {
      dd.classList.add('is-open');
      dd.style.display = 'flex';
      btn.setAttribute('aria-expanded', 'true');
      const chev = btn.querySelector('.topbar-chevron');
      if (chev) chev.style.transform = 'rotate(180deg)';
    }

    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      if (dd.classList.contains('is-open') || dd.style.display === 'flex') {
        closeDropdown();
      } else {
        openDropdown();
      }
    });

    document.addEventListener('click', function(e) {
      if (!btn.contains(e.target) && !dd.contains(e.target)) {
        closeDropdown();
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeDropdown();
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
          <p style="font-size:13.5px;color:#64748B;line-height:1.55;margin:0 0 24px;">Are you sure you want to log out of your account?</p>
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
        modal.classList.remove('open');
        modal.style.display = 'none';
        modal.style.opacity = '0';
        modal.style.visibility = 'hidden';
        document.body.style.overflow = '';
      }

      function openModal(e) {
        if (e) e.preventDefault();
        modal.classList.add('open');
        modal.style.display = 'flex';
        modal.style.opacity = '1';
        modal.style.visibility = 'visible';
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
        const isSubdir = window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/');
        window.location.href = isSubdir ? '../logout.html' : 'logout.html';
      });

      // Bind all logout triggers
      document.addEventListener('click', function(e) {
        const trigger = e.target.closest('.sidebar-link--logout, #sidebarLogoutBtn, .btn-logout, a.dropdown-danger, a[href*="logout.php"], a[href*="logout.html"]');
        if (trigger) {
          openModal(e);
        }
      });
    }
  }

  // Auth & Profile Guard for User Portal
  function checkUserSession() {
    // If PHP session is injected into window.ICD_SESSION, sync it to localStorage
    if (window.ICD_SESSION && window.ICD_SESSION.loggedIn) {
      try {
        if (!localStorage.getItem('icd_token')) {
          localStorage.setItem('icd_token', 'session-' + Date.now());
        }
        if (!localStorage.getItem('icd_user')) {
          const parts = (window.ICD_SESSION.name || 'Maria Santos').split(' ');
          localStorage.setItem('icd_user', JSON.stringify({
            id: 1,
            first_name: parts[0] || 'Maria',
            last_name: parts.slice(1).join(' ') || 'Santos',
            email: 'user@icaredonate.test',
            role: window.ICD_SESSION.role || 'user'
          }));
        }
      } catch(e) {}
    }

    let token = null;
    let user = null;
    try {
      token = localStorage.getItem('icd_token');
      user = JSON.parse(localStorage.getItem('icd_user') || 'null');
    } catch(e) {}

    if (!token || !user) {
      const isSubdir = window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/');
      const page = window.location.pathname.split('/').pop() || 'dashboard.html';
      const target = isSubdir ? `user/${page}${window.location.search}` : page;
      window.location.href = isSubdir ? `../login.html?redirect=${encodeURIComponent(target)}` : `login.html?redirect=${encodeURIComponent(target)}`;
      return false;
    }

    const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'Community Member';
    const initials = ((user.first_name ? user.first_name[0] : '') + (user.last_name ? user.last_name[0] : '')).toUpperCase() || 'CM';

    document.querySelectorAll('#topbarUserName, #user-topbar-name, .topbar-user-name, #user-sidebar-name, [data-user-name]').forEach(el => el.textContent = fullName);
    document.querySelectorAll('#topbarUserRole, .topbar-user-role').forEach(el => el.textContent = (user.role === 'admin' || user.role === 'superadmin') ? 'Administrator' : 'Verified Member');
    document.querySelectorAll('#topbarUserAvatar, #topbarUserInitials, .topbar-avatar, .user-avatar-circle').forEach(el => {
      if (el.tagName === 'SPAN' || el.classList.contains('topbar-avatar-initials') || el.classList.contains('user-avatar-circle')) el.textContent = initials;
    });

    // Also update any raw span inside #topbarUserMenuBtn
    document.querySelectorAll('#topbarUserMenuBtn > span:not(.user-avatar-circle)').forEach(el => {
      if (!el.querySelector('svg') && !el.classList.contains('topbar-chevron')) {
        el.textContent = fullName;
      }
    });

    return true;
  }

  document.addEventListener('DOMContentLoaded', function() {
    if (checkUserSession()) {
      setupActiveNav();
      setupTopbarUser();
      initLogoutModal();
    }
  });

})();
