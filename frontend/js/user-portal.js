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
      if (navKey === page || 
          (page === 'index' && navKey === 'dashboard') ||
          (page === 'settings' && navKey === 'profile') ||
          (page === 'request-details' && navKey === 'requests') ||
          (page === 'request-donation' && navKey === 'requests') ||
          (page === 'edit-donation' && navKey === 'donations')) {
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
        const isSubdir = window.location.pathname.includes('/user') || window.location.pathname.includes('/admin');
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
      const isSubdir = window.location.pathname.includes('/user') || window.location.pathname.includes('/admin');
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

    document.documentElement.classList.add('portal-hydrated');
    return true;
  }

  // Synchronous early hydration if elements already parsed
  try {
    checkUserSession();
  } catch(e) {}

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function icd_actionsBase() {
    if (typeof window.icd_actionsBase === 'function') {
      return window.icd_actionsBase();
    }
    const host = window.location.hostname;
    if (host === 'localhost' || host === '127.0.0.1') {
      const p = window.location.pathname;
      const idx = p.indexOf('/frontend/');
      if (idx !== -1) {
        return p.substring(0, idx);
      }
      return '';
    }
    return 'https://icaredonate.onrender.com';
  }

  // ── Authenticated API Client ──────────────────────────────────────────────
  async function fetchWithAuth(url, options = {}) {
    let token = null;
    try {
      token = localStorage.getItem('icd_token');
    } catch(e) {}

    const headers = Object.assign({
      'Accept': 'application/json'
    }, options.headers || {});

    if (token) {
      headers['Authorization'] = 'Bearer ' + token;
    }

    const fullUrl = (typeof url === 'string' && url.startsWith('http')) ? url : (icd_actionsBase() + url);

    const opts = Object.assign({}, options, {
      credentials: 'include',
      headers: headers
    });

    let res;
    try {
      res = await fetch(fullUrl, opts);
    } catch (netErr) {
      throw new Error('Network error: Unable to reach server. Please check your connection.');
    }

    if (res.status === 401) {
      // Session expired or invalid
      try {
        localStorage.removeItem('icd_token');
        localStorage.removeItem('icd_user');
      } catch(e) {}
      const isSubdir = window.location.pathname.includes('/user') || window.location.pathname.includes('/admin');
      const page = window.location.pathname.split('/').pop() || 'dashboard.html';
      const target = isSubdir ? `user/${page}${window.location.search}` : page;
      window.location.href = isSubdir ? `../login.html?redirect=${encodeURIComponent(target)}` : `login.html?redirect=${encodeURIComponent(target)}`;
      throw new Error('Session expired. Please log in again.');
    }

    return res;
  }

  // ── Dynamic Dashboard Data Loader ─────────────────────────────────────────
  async function loadDashboardData() {
    const activityContainer = document.getElementById('recentActivityContainer');
    const welcomeUserEl = document.getElementById('welcomeUserName');
    const statCompletedBadge = document.getElementById('statCompletedBadge');
    const statMyDonations = document.getElementById('statMyDonations');
    const statMyRequests = document.getElementById('statMyRequests');
    const statInProgress = document.getElementById('statInProgress');
    const statCompleted = document.getElementById('statCompleted');
    const notifBadge = document.getElementById('sidebarNotifBadge');

    if (activityContainer) {
      activityContainer.innerHTML = `
        <div style="padding: 36px 20px; text-align: center; color: var(--color-text-muted, #64748B);">
          <div class="btn-spinner" style="display:inline-block; width:22px; height:22px; border:2.5px solid #CBD5E1; border-top-color:var(--color-primary, #1F4FA3); border-radius:50%; animation:spin 0.8s linear infinite; margin-bottom:10px;"></div>
          <div style="font-size:13.5px;">Loading your recent activity...</div>
        </div>
      `;
    }

    try {
      const res = await fetchWithAuth('/actions/user_dashboard.php');
      let data;
      try {
        data = await res.json();
      } catch (jsonErr) {
        throw new Error(`Server returned status ${res.status} with non-JSON response.`);
      }

      if (!res.ok || !data || !data.success) {
        throw new Error((data && data.message) ? data.message : 'Failed to retrieve dashboard records.');
      }

      // 1. User name
      const user = data.user || {};
      const firstName = user.first_name || (user.name ? user.name.split(' ')[0] : 'Member');
      if (welcomeUserEl) {
        welcomeUserEl.textContent = firstName;
      }

      // Sync updated user into localStorage
      try {
        const stored = JSON.parse(localStorage.getItem('icd_user') || '{}');
        const updated = Object.assign({}, stored, user);
        localStorage.setItem('icd_user', JSON.stringify(updated));
      } catch(e) {}

      // 2. Aggregate statistics
      const stats = data.stats || {};
      if (statMyDonations) statMyDonations.textContent = stats.donations ?? 0;
      if (statMyRequests) statMyRequests.textContent = stats.requests ?? 0;
      if (statInProgress) statInProgress.textContent = stats.in_progress ?? 0;
      if (statCompleted) statCompleted.textContent = stats.completed ?? 0;
      if (statCompletedBadge) statCompletedBadge.textContent = stats.completed ?? 0;

      // Sidebar notifications badge
      if (notifBadge) {
        const unread = stats.unread_notifications || 0;
        notifBadge.textContent = unread;
        notifBadge.style.display = unread > 0 ? 'inline-block' : 'none';
      }

      // 3. Render recent activity
      if (activityContainer) {
        const activities = data.recent_activity || [];
        if (activities.length === 0) {
          activityContainer.innerHTML = `
            <div style="padding: 44px 20px; text-align: center;">
              <div style="width: 48px; height: 48px; border-radius: 50%; background: #F1F5F9; color: #94A3B8; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
              </div>
              <div style="font-size: 15px; font-weight: 700; color: var(--color-text, #1E293B); margin-bottom: 4px;">No recent activity recorded</div>
              <p style="font-size: 13.5px; color: var(--color-text-muted, #64748B); max-width: 380px; margin: 0 auto 16px;">
                You haven't posted any donations or submitted requests yet. When you do, your real tracking updates will show here.
              </p>
              <div style="display:flex; justify-content:center; gap:10px; flex-wrap:wrap;">
                <a href="offer-donation.html" class="btn btn-sm btn-primary" style="font-size:12.5px; padding:7px 14px; text-decoration:none;">Post a Donation</a>
                <a href="browse-donations.html" class="btn btn-sm btn-outline" style="font-size:12.5px; padding:7px 14px; text-decoration:none;">Browse Items</a>
              </div>
            </div>
          `;
        } else {
          let html = '';
          activities.forEach((item, idx) => {
            const isLast = (idx === activities.length - 1);
            const isDonation = item.type === 'donation';
            const iconBg = isDonation ? 'var(--color-tint-teal, #E6F4F1)' : '#FFF0EC';
            const iconColor = isDonation ? 'var(--color-primary, #1F4FA3)' : 'var(--coral, #E0553D)';
            const iconSvg = isDonation
              ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="' + iconColor + '" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"/><rect width="20" height="5" x="2" y="7"/><line x1="12" x2="12" y1="22" y2="7"/></svg>'
              : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="' + iconColor + '" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';

            html += `
              <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;${!isLast ? 'border-bottom:1px solid var(--color-border, #E2E8F0);' : ''}gap:16px;">
                <div style="display:flex;align-items:center;gap:14px;">
                  <div style="width:36px;height:36px;border-radius:50%;background:${iconBg};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    ${iconSvg}
                  </div>
                  <span style="font-size:13.5px;color:var(--color-text, #1E293B);">${escapeHtml(item.text)}</span>
                </div>
                <span style="font-size:12px;color:var(--color-text-muted, #64748B);white-space:nowrap;flex-shrink:0;background:#F8FAFC;padding:3px 8px;border-radius:20px;">${escapeHtml(item.time_ago || '')}</span>
              </div>
            `;
          });
          activityContainer.innerHTML = html;
        }
      }

    } catch (err) {
      if (activityContainer) {
        activityContainer.innerHTML = `
          <div style="padding: 28px 20px; text-align: center;">
            <div style="color: #DC2626; font-size: 14px; font-weight: 600; margin-bottom: 6px;">Failed to load activity</div>
            <p style="color: #64748B; font-size: 13px; margin: 0 0 14px;">${escapeHtml(err.message || 'Server connection error.')}</p>
            <button type="button" class="btn btn-outline btn-sm" onclick="window.loadDashboardData()" style="font-size: 12.5px; padding: 6px 14px;">
              Retry
            </button>
          </div>
        `;
      }
    }
  }

  // ── Dynamic Notifications Loader & Mark-All-Read ──────────────────────────
  async function loadNotificationsData() {
    const listEl = document.getElementById('notificationsList');
    const notifBadge = document.getElementById('sidebarNotifBadge');
    if (!listEl) return;

    listEl.innerHTML = `
      <div style="padding: 36px 20px; text-align: center; color: var(--color-text-muted, #64748B);">
        <div class="btn-spinner" style="display:inline-block; width:22px; height:22px; border:2.5px solid #CBD5E1; border-top-color:var(--color-primary, #1F4FA3); border-radius:50%; animation:spin 0.8s linear infinite; margin-bottom:10px;"></div>
        <div style="font-size:13.5px;">Loading notifications...</div>
      </div>
    `;

    try {
      const res = await fetchWithAuth('/actions/notifications.php');
      let data;
      try {
        data = await res.json();
      } catch (jsonErr) {
        throw new Error(`Server returned status ${res.status} with non-JSON response.`);
      }

      if (!res.ok || !data || !data.success) {
        throw new Error((data && data.message) ? data.message : 'Unable to load notifications.');
      }

      const notifs = data.notifications || [];
      const unreadCount = data.unread_count ?? 0;

      if (notifBadge) {
        notifBadge.textContent = unreadCount;
        notifBadge.style.display = unreadCount > 0 ? 'inline-block' : 'none';
      }

      if (notifs.length === 0) {
        listEl.innerHTML = `
          <div class="card" style="padding: 48px 24px; text-align: center; border-radius: 12px; background: #fff; border: 1px solid var(--border, #E2E8F0);">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: #F1F5F9; color: #94A3B8; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
              </svg>
            </div>
            <h3 style="font-size: 16px; font-weight: 700; color: #1E293B; margin: 0 0 6px;">All Caught Up!</h3>
            <p style="font-size: 13.5px; color: #64748B; margin: 0;">You have no notifications at this time.</p>
          </div>
        `;
        return;
      }

      let html = '';
      notifs.forEach(n => {
        const isUnread = !n.is_read;
        const bg = isUnread ? '#E6F4F1' : '#FFFFFF';
        const border = isUnread ? '#CBD5E1' : '#E2E8F0';

        let iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>';
        let iconBg = 'var(--color-primary, #1F4FA3)';
        let iconColor = '#FFFFFF';

        if (n.type === 'match') {
          iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>';
          iconBg = '#FEF3C7';
          iconColor = '#D97706';
        } else if (n.type === 'donation') {
          iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"/><rect width="20" height="5" x="2" y="7"/><line x1="12" x2="12" y1="22" y2="7"/></svg>';
        } else if (n.type === 'request') {
          iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
          iconBg = '#FFF0EC';
          iconColor = 'var(--coral, #E0553D)';
        }

        html += `
          <div class="card notif-card ${isUnread ? 'is-unread' : 'is-read'}" data-id="${n.id}" style="padding:18px 20px;border-radius:12px;border:1px solid ${border};background:${bg};display:flex;gap:16px;align-items:flex-start;transition:background 0.2s ease;">
            <div style="width:36px;height:36px;border-radius:50%;background:${iconBg};color:${iconColor};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              ${iconSvg}
            </div>
            <div style="flex:1;">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                <div style="font-weight:700;font-size:14.5px;color:var(--text, #1E293B);">${escapeHtml(n.title)}</div>
                ${isUnread ? '<span style="font-size:11px;font-weight:600;background:var(--color-primary,#1F4FA3);color:#fff;padding:2px 8px;border-radius:12px;">New</span>' : ''}
              </div>
              <p style="font-size:13.5px;color:#334155;margin:4px 0 6px;">
                ${escapeHtml(n.message)}
              </p>
              <span style="font-size:11.5px;color:#64748B;">${escapeHtml(n.time_ago || '')}</span>
            </div>
          </div>
        `;
      });

      listEl.innerHTML = html;

    } catch (err) {
      listEl.innerHTML = `
        <div style="padding: 32px 20px; text-align: center;">
          <div style="color: #DC2626; font-size: 14px; font-weight: 600; margin-bottom: 6px;">Unable to load notifications</div>
          <p style="color: #64748B; font-size: 13px; margin: 0 0 14px;">${escapeHtml(err.message || 'Server connection error.')}</p>
          <button type="button" class="btn btn-outline btn-sm" onclick="window.loadNotificationsData()" style="font-size: 12.5px; padding: 6px 14px;">
            Retry
          </button>
        </div>
      `;
    }
  }

  async function handleMarkAllRead() {
    const btn = document.getElementById('markAllReadBtn');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Updating...';
    }

    try {
      const res = await fetchWithAuth('/actions/notifications.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ action: 'mark_all_read' })
      });

      const data = await res.json();
      if (!res.ok || !data || !data.success) {
        throw new Error((data && data.message) ? data.message : 'Server failed to mark notifications as read.');
      }

      // Visually update cards in DOM
      document.querySelectorAll('.notif-card').forEach(c => {
        c.style.background = '#FFFFFF';
        c.style.borderColor = '#E2E8F0';
        c.classList.remove('is-unread');
        c.classList.add('is-read');
        const badge = c.querySelector('span[style*="border-radius:12px"]');
        if (badge) badge.remove();
      });

      // Update sidebar badge
      const notifBadge = document.getElementById('sidebarNotifBadge');
      if (notifBadge) {
        notifBadge.textContent = '0';
        notifBadge.style.display = 'none';
      }

      if (window.showPortalFlash) {
        window.showPortalFlash('success', 'All notifications marked as read.');
      }

    } catch (err) {
      if (window.showPortalFlash) {
        window.showPortalFlash('error', err.message || 'Failed to mark notifications as read.');
      } else {
        alert(err.message || 'Failed to mark notifications as read.');
      }
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = origHtml;
      }
    }
  }

  // Expose on window
  window.fetchWithAuth = fetchWithAuth;
  window.loadDashboardData = loadDashboardData;
  window.loadNotificationsData = loadNotificationsData;
  window.handleMarkAllRead = handleMarkAllRead;
  window.markAllRead = handleMarkAllRead;

  document.addEventListener('DOMContentLoaded', function() {
    if (checkUserSession()) {
      setupActiveNav();
      setupTopbarUser();
      initLogoutModal();

      // Automatically trigger loader if on dashboard or notifications page
      if (document.getElementById('recentActivityContainer')) {
        loadDashboardData();
      }
      if (document.getElementById('notificationsList')) {
        loadNotificationsData();
      }
    }
  });

})();
