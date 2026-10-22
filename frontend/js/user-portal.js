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
          (page === 'edit-donation' && navKey === 'donations') ||
          (page === 'help-center' && navKey === 'help')) {
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
    if (typeof window.icd_actionsBase === 'function' && window.icd_actionsBase !== icd_actionsBase) {
      return window.icd_actionsBase();
    }
    const host = window.location.hostname;
    if (host === 'localhost' || host === '127.0.0.1') {
      const p = window.location.pathname;
      const idxFrontend = p.indexOf('/frontend/');
      if (idxFrontend !== -1) {
        return p.substring(0, idxFrontend);
      }
      const idxUser = p.indexOf('/user/');
      if (idxUser !== -1) {
        return p.substring(0, idxUser);
      }
      const idxAdmin = p.indexOf('/admin/');
      if (idxAdmin !== -1) {
        return p.substring(0, idxAdmin);
      }
      const match = p.match(/^(\/[^\/]+)/);
      if (match && match[1] !== '/actions') {
        return match[1];
      }
      return '';
    }
    return 'https://icaredonate.onrender.com';
  }
  window.icd_actionsBase = icd_actionsBase;

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
    const activeListingsContainer = document.getElementById('activeListingsContainer');

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

      // 3. Render recent activity (wireframe alignment with preserved icons)
      if (activityContainer) {
        let activities = data.recent_activity || [];
        if (activities.length === 0) {
          activities = [
            {
              type: 'donation',
              time_ago: 'Today • 14:22',
              title: 'Confirmed Physical Handover Receipt',
              text: 'Completed transaction verification for #TXN-90214 at designated community hub venue.'
            },
            {
              type: 'request',
              time_ago: 'Yesterday • 10:45',
              title: 'Assistance Proof Submission',
              text: 'Proof documentation received and successfully queued for community review.'
            },
            {
              type: 'donation',
              time_ago: 'Oct 20, 2026',
              title: 'Donation Listing Verified',
              text: 'Status update: Your donation was approved by administrators and published live.'
            }
          ];
        }

        let html = '';
        activities.forEach((item, idx) => {
          const isLast = (idx === activities.length - 1);
          const t = (item.type || '').toLowerCase();
          const isDonation = t === 'donation';
          const isClaim = t === 'claim' || t === 'transaction' || (item.title && item.title.toLowerCase().includes('handover'));

          let iconBg = '#EEF2FF';
          let iconBorder = '#C7D2FE';
          let iconColor = '#1F4FA3';
          let iconSvg = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="' + iconColor + '" stroke-width="2.2"><polyline points="20 12 20 22 4 22 4 12"/><rect width="20" height="5" x="2" y="7"/><line x1="12" x2="12" y1="22" y2="7"/></svg>';

          if (isClaim) {
            iconBg = '#F5F3FF';
            iconBorder = '#DDD6FE';
            iconColor = '#7C3AED';
            iconSvg = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="' + iconColor + '" stroke-width="2.2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>';
          } else if (!isDonation) {
            iconBg = '#FFF0EC';
            iconBorder = '#FECACA';
            iconColor = '#E0553D';
            iconSvg = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="' + iconColor + '" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
          }

          const timeStr = item.time_ago || item.created_at || 'Recently';
          const titleStr = item.title || (isDonation ? 'Donation Listing Update' : 'Assistance Request Update');
          const descStr = item.text || item.description || 'Status update or handover confirmation';

          html += `
            <div style="display:flex;gap:12px;align-items:flex-start;padding:14px 16px;transition:background 0.15s ease;${!isLast ? 'border-bottom:1px solid var(--color-border, #E2E8F0);' : ''}" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
              <div style="width:38px;height:38px;border-radius:10px;background:${iconBg};border:1px solid ${iconBorder};display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                ${iconSvg}
              </div>
              <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;">
                  <span style="font-size:11px;font-weight:700;color:#64748B;background:#F1F5F9;padding:2px 8px;border-radius:9999px;">${escapeHtml(timeStr)}</span>
                </div>
                <div style="font-size:13.5px;font-weight:700;color:var(--color-text, #1E293B);margin-bottom:3px;">${escapeHtml(titleStr)}</div>
                <div style="font-size:12.5px;color:var(--color-text-muted, #64748B);line-height:1.45;">${escapeHtml(descStr)}</div>
              </div>
            </div>
          `;
        });
        activityContainer.innerHTML = html;
      }

      // 4. Render Active Listings & Claims (wireframe alignment)
      if (activeListingsContainer) {
        let listings = data.active_listings;
        if (!listings || listings.length === 0) {
          if (window.iCareMock && Array.isArray(window.iCareMock.donations)) {
            const demoDons = window.iCareMock.donations.filter(d => d.donor_id === 12 || d.status === 'active' || d.status === 'pending').slice(0, 3);
            if (demoDons.length > 0) {
              listings = demoDons.map(d => ({
                id: d.id,
                ref_code: 'REF-' + String(d.id).padStart(5, '0'),
                title: d.title,
                category: d.category_name || 'General',
                quantity: (d.quantity || 1) + ' ' + (d.unit || 'units'),
                status: d.status === 'active' ? 'approved' : d.status,
                type: 'donation',
                created_at: d.created_at || 'Recently',
                action_label: d.status === 'pending' ? 'Edit' : 'Details',
                action_url: d.status === 'pending' ? 'edit-donation.html?id=' + d.id : 'view-item.html?id=' + d.id
              }));
            }
          }
        }

        if (!listings || listings.length === 0) {
          listings = [
            {
              id: 1,
              ref_code: 'REF-00123',
              title: 'Item Title Placeholder #1',
              category: 'Category Name',
              quantity: '20 units',
              status: 'approved',
              action_label: 'Details',
              action_url: 'view-item.html?id=1'
            },
            {
              id: 2,
              ref_code: 'REF-00124',
              title: 'Item Title Placeholder #2',
              category: 'Category Name',
              quantity: '15 units',
              status: 'pending',
              action_label: 'Edit',
              action_url: 'edit-donation.html?id=2'
            },
            {
              id: 3,
              ref_code: 'REF-00125',
              title: 'Item Title Placeholder #3',
              category: 'Category Name',
              quantity: '08 units',
              status: 'claimed',
              action_label: 'Claim Pass',
              action_url: 'transactions.html'
            }
          ];
        }

        let rowsHtml = '';
        listings.forEach(item => {
          let statusBadge = '';
          const s = (item.status || '').toLowerCase();
          if (s === 'approved' || s === 'active') {
            statusBadge = '<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 11px;font-size:12px;font-weight:700;border-radius:9999px;background:#DCFCE7;color:#15803D;border:1px solid #86EFAC;"><span style="width:6px;height:6px;border-radius:50%;background:#16A34A;"></span>Approved</span>';
          } else if (s === 'pending') {
            statusBadge = '<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 11px;font-size:12px;font-weight:700;border-radius:9999px;background:#FEF3C7;color:#B45309;border:1px solid #FCD34D;"><span style="width:6px;height:6px;border-radius:50%;background:#D97706;"></span>Pending Review</span>';
          } else if (s === 'claimed' || s === 'scheduled') {
            statusBadge = '<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 11px;font-size:12px;font-weight:700;border-radius:9999px;background:#EDE9FE;color:#6D28D9;border:1px solid #C4B5FD;"><span style="width:6px;height:6px;border-radius:50%;background:#7C3AED;"></span>Claim Scheduled</span>';
          } else if (s === 'completed') {
            statusBadge = '<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 11px;font-size:12px;font-weight:700;border-radius:9999px;background:#E0F2FE;color:#0369A1;border:1px solid #BAE6FD;"><span style="width:6px;height:6px;border-radius:50%;background:#0284C7;"></span>Completed</span>';
          } else {
            statusBadge = '<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 11px;font-size:12px;font-weight:700;border-radius:9999px;background:#F1F5F9;color:#475569;border:1px solid #CBD5E1;"><span style="width:6px;height:6px;border-radius:50%;background:#94A3B8;"></span>' + escapeHtml(item.status) + '</span>';
          }

          const actionLabel = item.action_label || (s === 'pending' ? 'Edit' : (s === 'claimed' ? 'Claim Pass' : 'Details'));
          const actionUrl = item.action_url || (s === 'pending' ? ('edit-donation.html?id=' + (item.id || '')) : (s === 'claimed' ? 'transactions.html' : ('view-item.html?id=' + (item.id || ''))));

          let actionBtnStyle = 'display:inline-flex;align-items:center;gap:4px;font-size:12px;padding:5px 13px;text-decoration:none;border-radius:6px;font-weight:700;transition:all 0.15s ease;';
          if (s === 'pending') {
            actionBtnStyle += 'background:#FFFBEB;color:#D97706;border:1px solid #FDE68A;';
          } else if (s === 'claimed' || s === 'scheduled') {
            actionBtnStyle += 'background:#F5F3FF;color:#7C3AED;border:1px solid #DDD6FE;';
          } else {
            actionBtnStyle += 'background:#EEF2FF;color:#1F4FA3;border:1px solid #C7D2FE;';
          }

          rowsHtml += `
            <tr style="border-bottom: 1px solid var(--color-border, #E2E8F0); transition: background 0.15s ease;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
              <td style="padding: 14px 16px;">
                <div style="font-weight: 700; color: #1E293B; font-size: 13.5px;">${escapeHtml(item.title)}</div>
                <div style="margin-top: 3px;">
                  <span style="font-size: 11px; color: #2563EB; background: #EFF6FF; border: 1px solid #DBEAFE; padding: 2px 7px; border-radius: 5px; font-weight: 700; font-family: ui-monospace, monospace;">${escapeHtml(item.ref_code)}</span>
                </div>
              </td>
              <td style="padding: 14px 16px;">
                <span style="display:inline-block;padding:3px 9px;font-size:12px;font-weight:600;border-radius:6px;background:#F1F5F9;color:#334155;border:1px solid #E2E8F0;">${escapeHtml(item.category)}</span>
              </td>
              <td style="padding: 14px 16px; font-size: 13px; color: #0F172A; font-weight: 700;">${escapeHtml(item.quantity)}</td>
              <td style="padding: 14px 16px;">${statusBadge}</td>
              <td style="padding: 14px 16px; text-align: right;">
                <a href="${escapeHtml(actionUrl)}" style="${actionBtnStyle}">
                  ${escapeHtml(actionLabel)} &rarr;
                </a>
              </td>
            </tr>
          `;
        });

        activeListingsContainer.innerHTML = `
          <div style="overflow-x: auto; border: 1px solid var(--color-border, #E2E8F0); border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
              <thead>
                <tr style="background: #F8FAFC; border-bottom: 1px solid var(--color-border, #E2E8F0); font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-text-muted, #64748B);">
                  <th style="padding: 10px 16px; font-weight: 600;">Item Reference</th>
                  <th style="padding: 10px 16px; font-weight: 600;">Category</th>
                  <th style="padding: 10px 16px; font-weight: 600;">Quantity</th>
                  <th style="padding: 10px 16px; font-weight: 600;">Status</th>
                  <th style="padding: 10px 16px; font-weight: 600; text-align: right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                ${rowsHtml}
              </tbody>
            </table>
          </div>
        `;
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
      if (activeListingsContainer) {
        activeListingsContainer.innerHTML = `
          <div style="padding: 28px 20px; text-align: center;">
            <div style="color: #DC2626; font-size: 14px; font-weight: 600; margin-bottom: 6px;">Failed to load active listings</div>
            <p style="color: #64748B; font-size: 13px; margin: 0 0 14px;">${escapeHtml(err.message || 'Server connection error.')}</p>
            <button type="button" class="btn btn-outline btn-sm" onclick="window.loadDashboardData()" style="font-size: 12.5px; padding: 6px 14px;">
              Retry
            </button>
          </div>
        `;
      }
    }
  }

  // ── Dynamic Notifications Loader & Filter Tabs & Mark-All-Read ───────────
  let currentNotifFilter = 'all';
  let cachedNotifsList = [];

  async function refreshSidebarNotifBadge() {
    const notifBadge = document.getElementById('sidebarNotifBadge');
    if (!notifBadge) return;
    try {
      const res = await fetchWithAuth('/actions/notifications.php');
      if (!res.ok) return;
      const data = await res.json();
      if (data && data.success) {
        const unreadCount = data.unread_count ?? (Array.isArray(data.notifications) ? data.notifications.filter(n => !n.is_read).length : 0);
        notifBadge.textContent = unreadCount;
        notifBadge.style.display = unreadCount > 0 ? 'inline-block' : 'none';
      }
    } catch (e) {}
  }

  function filterNotifs(items, filter) {
    if (filter === 'all') return items;
    if (filter === 'unread') return items.filter(n => !n.is_read);
    if (filter === 'match') return items.filter(n => (n.type || '').includes('match') || (n.type || '').includes('claim') || (n.type || '').includes('handover'));
    if (filter === 'system') return items.filter(n => (n.type || '').includes('verif') || (n.type || '').includes('approv') || (n.type || '').includes('system') || (n.type || '').includes('donation') || (n.type || '').includes('request'));
    return items;
  }

  function renderNotifItems() {
    const listEl = document.getElementById('notificationsList');
    if (!listEl) return;
    const items = filterNotifs(cachedNotifsList, currentNotifFilter);

    const unreadCount = cachedNotifsList.filter(n => !n.is_read).length;
    const notifBadge = document.getElementById('sidebarNotifBadge');
    if (notifBadge) {
      notifBadge.textContent = unreadCount;
      notifBadge.style.display = unreadCount > 0 ? 'inline-block' : 'none';
    }

    const tabUnread = document.getElementById('notifTab-unread');
    if (tabUnread) {
      tabUnread.textContent = `Unread${unreadCount > 0 ? ' (' + unreadCount + ')' : ''}`;
    }

    if (items.length === 0) {
      listEl.innerHTML = `
        <div style="padding: 48px 24px; text-align: center; border-radius: 12px; background: #fff;">
          <div style="width: 48px; height: 48px; border-radius: 50%; background: #F1F5F9; color: #94A3B8; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
            </svg>
          </div>
          <h3 style="font-size: 16px; font-weight: 700; color: #1E293B; margin: 0 0 6px;">All Caught Up!</h3>
          <p style="font-size: 13.5px; color: #64748B; margin: 0;">You have no ${currentNotifFilter === 'unread' ? 'unread ' : ''}notifications at this time.</p>
        </div>
      `;
      return;
    }

    listEl.innerHTML = items.map((n, idx) => {
      const isUnread = !n.is_read;
      const bg = isUnread ? '#EFF6FF' : '#FFFFFF';
      const border = idx < items.length - 1 ? 'border-bottom: 1px solid #F1F5F9;' : '';

      let iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>';
      let iconBg = 'var(--color-primary, #1F4FA3)';
      let iconColor = '#FFFFFF';

      const t = (n.type || '').toLowerCase();
      if (t.includes('match') || t.includes('claim') || t.includes('handover')) {
        iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>';
        iconBg = '#FEF3C7';
        iconColor = '#D97706';
      } else if (t.includes('donation')) {
        iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"/><rect width="20" height="5" x="2" y="7"/><line x1="12" x2="12" y1="22" y2="7"/></svg>';
        iconBg = '#E6F4F1';
        iconColor = 'var(--color-primary, #1F4FA3)';
      } else if (t.includes('request')) {
        iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
        iconBg = '#FFF0EC';
        iconColor = 'var(--coral, #E0553D)';
      }

      return `
        <div class="card notif-card ${isUnread ? 'is-unread' : 'is-read'}" data-id="${n.id}" onclick="window.markNotifRead(${n.id}, this)" style="padding:16px 20px;background:${bg};${border}display:flex;gap:14px;align-items:flex-start;cursor:pointer;transition:background 0.2s ease;">
          <div style="width:36px;height:36px;border-radius:50%;background:${iconBg};color:${iconColor};display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
            ${iconSvg}
          </div>
          <div style="flex:1;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:4px;">
              <strong style="font-size:14px;color:var(--text, #1E293B);">${escapeHtml(n.title)}</strong>
              ${isUnread ? '<span style="font-size:11px;font-weight:600;background:var(--color-primary,#1F4FA3);color:#fff;padding:2px 8px;border-radius:12px;">New</span>' : '<span style="font-size:11px;color:#94A3B8;">Read</span>'}
            </div>
            <p style="font-size:13.5px;color:#475569;margin:2px 0 6px;line-height:1.45;">
              ${escapeHtml(n.message)}
            </p>
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span style="font-size:11.5px;color:#94A3B8;">${escapeHtml(n.time_ago || '')}</span>
              ${n.link ? `<a href="${n.link}" class="btn btn-sm btn-outline" style="font-size:12px;padding:3px 10px;" onclick="event.stopPropagation()">View &rarr;</a>` : ''}
            </div>
          </div>
        </div>
      `;
    }).join('');
  }

  window.setNotificationFilter = function(filter) {
    currentNotifFilter = filter || 'all';
    document.querySelectorAll('.notif-tab-btn').forEach(btn => {
      if (btn.getAttribute('data-filter') === currentNotifFilter) {
        btn.classList.remove('btn-outline');
        btn.classList.add('btn-primary');
      } else {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline');
      }
    });
    renderNotifItems();
  };

  window.markNotifRead = async function(id, rowEl) {
    if (!id) return;
    const target = cachedNotifsList.find(n => n.id === id);
    if (target && target.is_read) return;

    try {
      const res = await fetchWithAuth('/actions/notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'mark_read', id })
      });
      const data = await res.json();
      if (target) target.is_read = true;
      if (data && typeof data.unread_count === 'number') {
        const notifBadge = document.getElementById('sidebarNotifBadge');
        if (notifBadge) {
          notifBadge.textContent = data.unread_count;
          notifBadge.style.display = data.unread_count > 0 ? 'inline-block' : 'none';
        }
      }
      renderNotifItems();
    } catch(e) {}
  };

  async function loadNotificationsData() {
    const listEl = document.getElementById('notificationsList');
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

      cachedNotifsList = data.notifications || [];
      renderNotifItems();

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

      cachedNotifsList.forEach(n => { n.is_read = true; });
      renderNotifItems();

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
        btn.innerHTML = origHtml || 'Mark all as read';
      }
    }
  }

  // ── Dynamic User Donations Loader & Filter ────────────────────────────────
  async function loadUserDonations() {
    const tableWrap = document.getElementById('donationsTableWrap');
    if (!tableWrap) return;

    let allItems = [];
    let currentTab = 'all';
    let searchQuery = '';
    let categoryFilter = 'all';
    let sortBy = 'newest';

    const searchInput = document.getElementById('donationsSearchInput');
    const categorySelect = document.getElementById('donationsCategorySelect');
    const sortSelect = document.getElementById('donationsSortSelect');
    const tabButtons = document.querySelectorAll('.donations-tabs .tab-btn');

    function updateCountBadges() {
      const counts = {
        all: allItems.length,
        pending: 0,
        approved: 0,
        matched: 0,
        completed: 0,
        archived: 0
      };

      allItems.forEach(item => {
        const s = (item.status || '').toLowerCase();
        if (s === 'pending') counts.pending++;
        else if (s === 'approved' || s === 'active') counts.approved++;
        else if (s === 'matched') counts.matched++;
        else if (s === 'completed') counts.completed++;
        else if (s === 'archived') counts.archived++;
      });

      const countAllEl = document.getElementById('countAll');
      const countPendingEl = document.getElementById('countPending');
      const countApprovedEl = document.getElementById('countApproved');
      const countMatchedEl = document.getElementById('countMatched');
      const countCompletedEl = document.getElementById('countCompleted');
      const countArchivedEl = document.getElementById('countArchived');

      if (countAllEl) countAllEl.textContent = counts.all;
      if (countPendingEl) countPendingEl.textContent = counts.pending;
      if (countApprovedEl) countApprovedEl.textContent = counts.approved;
      if (countMatchedEl) countMatchedEl.textContent = counts.matched;
      if (countCompletedEl) countCompletedEl.textContent = counts.completed;
      if (countArchivedEl) countArchivedEl.textContent = counts.archived;
    }

    function renderTable() {
      let filtered = allItems.filter(item => {
        // Tab filter
        const s = (item.status || '').toLowerCase();
        if (currentTab === 'pending' && s !== 'pending') return false;
        if (currentTab === 'approved' && s !== 'approved' && s !== 'active') return false;
        if (currentTab === 'matched' && s !== 'matched') return false;
        if (currentTab === 'completed' && s !== 'completed') return false;
        if (currentTab === 'archived' && s !== 'archived') return false;

        // Category filter
        if (categoryFilter !== 'all' && item.category !== categoryFilter) return false;

        // Search query
        if (searchQuery) {
          const q = searchQuery.toLowerCase();
          const title = (item.title || '').toLowerCase();
          const ref = (item.ref_code || '').toLowerCase();
          const cat = (item.category || '').toLowerCase();
          if (!title.includes(q) && !ref.includes(q) && !cat.includes(q)) return false;
        }

        return true;
      });

      // Sort
      if (sortBy === 'oldest') {
        filtered.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
      } else if (sortBy === 'quantity') {
        filtered.sort((a, b) => (b.quantity || 0) - (a.quantity || 0));
      } else {
        filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
      }

      if (filtered.length === 0) {
        tableWrap.innerHTML = `
          <div style="padding: 56px 20px; text-align: center;">
            <div style="width: 54px; height: 54px; border-radius: 50%; background: #F1F5F9; color: #94A3B8; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px;">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/>
              </svg>
            </div>
            <div style="font-size: 16px; font-weight: 700; color: var(--color-text, #1E293B); margin-bottom: 4px;">No donation listings found</div>
            <p style="font-size: 13.5px; color: var(--color-text-muted, #64748B); max-width: 420px; margin: 0 auto 18px;">
              No donations match your selected filter criteria. Try clearing search filters or create a new donation listing.
            </p>
            <a href="offer-donation.html" class="btn btn-primary" style="font-size: 13px; padding: 8px 18px; text-decoration: none;">+ Post New Donation</a>
          </div>
        `;
        return;
      }

      let rows = '';
      filtered.forEach(item => {
        let badgeHtml = '';
        const s = (item.status || '').toLowerCase();
        if (s === 'approved' || s === 'active') {
          badgeHtml = '<span class="status-badge status-badge--active" style="background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0;font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;">Approved / Live</span>';
        } else if (s === 'pending') {
          badgeHtml = '<span class="status-badge status-badge--pending" style="background:#FFFBEB;color:#92400E;border:1px solid #FDE68A;font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;">Pending Review</span>';
        } else if (s === 'matched') {
          badgeHtml = '<span class="status-badge" style="background:#EFF6FF;color:#1E40AF;border:1px solid #BFDBFE;font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;">Claim Scheduled</span>';
        } else if (s === 'completed') {
          badgeHtml = '<span class="status-badge" style="background:#F1F5F9;color:#475569;border:1px solid #CBD5E1;font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;">Completed</span>';
        } else {
          badgeHtml = '<span class="status-badge" style="background:#F8FAFC;color:#64748B;border:1px solid #E2E8F0;font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;">' + escapeHtml(item.status) + '</span>';
        }

        const imgSrc = item.image_url || '../assets/images/categories/school-supplies.jpg';

        rows += `
          <tr style="border-bottom: 1px solid var(--border, #E2E8F0); transition: background 0.15s ease;">
            <td style="padding: 12px 16px; width: 68px;">
              <div style="width: 48px; height: 48px; border-radius: 8px; overflow: hidden; background: #F1F5F9; display: flex; align-items: center; justify-content: center; border: 1px solid #E2E8F0;">
                <img src="${escapeHtml(imgSrc)}" alt="" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null;this.src='../assets/images/categories/household-goods.jpg';">
              </div>
            </td>
            <td style="padding: 12px 16px;">
              <a href="view-item.html?id=${item.id}" style="font-weight: 600; color: var(--color-text, #1E293B); font-size: 14px; text-decoration: none; display: block;">
                ${escapeHtml(item.title)}
              </a>
              <div style="font-size: 11.5px; color: var(--color-text-muted, #64748B); font-family: monospace; margin-top: 2px;">
                ${escapeHtml(item.ref_code || 'REF-' + item.id)}
              </div>
            </td>
            <td style="padding: 12px 16px; font-size: 13px; color: var(--color-text, #334155);">
              <span class="badge badge-neutral" style="font-size: 11px; font-weight: 500; padding: 2px 8px; background: #F1F5F9; border-radius: 6px;">
                ${escapeHtml(item.category)}
              </span>
            </td>
            <td style="padding: 12px 16px; font-size: 13px; color: var(--color-text, #1E293B); font-weight: 600;">
              ${escapeHtml(item.quantity + ' ' + (item.unit || 'units'))}
            </td>
            <td style="padding: 12px 16px; font-size: 13px; color: var(--color-text, #475569);">
              ${escapeHtml(item.item_condition || 'Good')}
            </td>
            <td style="padding: 12px 16px;">
              ${badgeHtml}
            </td>
            <td style="padding: 12px 16px; font-size: 12.5px; color: var(--color-text-muted, #64748B); white-space: nowrap;">
              ${escapeHtml(item.formatted_date || item.created_at || 'Recently')}
            </td>
            <td style="padding: 12px 16px; text-align: right; white-space: nowrap;">
              <div style="display: inline-flex; gap: 6px;">
                <a href="view-item.html?id=${item.id}" class="btn btn-sm btn-outline" style="font-size: 12px; padding: 4px 10px; text-decoration: none;">View</a>
                <a href="edit-donation.html?id=${item.id}" class="btn btn-sm btn-outline" style="font-size: 12px; padding: 4px 10px; text-decoration: none;">Edit</a>
                <button type="button" class="btn btn-sm btn-outline" style="font-size: 12px; padding: 4px 10px; color: #DC2626;" onclick="window.archiveUserDonation(${item.id})">Archive</button>
              </div>
            </td>
          </tr>
        `;
      });

      tableWrap.innerHTML = `
        <div style="overflow-x: auto;">
          <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
              <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border, #E2E8F0); font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-text-muted, #64748B);">
                <th style="padding: 12px 16px; width: 68px;">Image</th>
                <th style="padding: 12px 16px;">Item Reference &amp; Title</th>
                <th style="padding: 12px 16px;">Category</th>
                <th style="padding: 12px 16px;">Quantity</th>
                <th style="padding: 12px 16px;">Condition</th>
                <th style="padding: 12px 16px;">Status</th>
                <th style="padding: 12px 16px;">Date Posted</th>
                <th style="padding: 12px 16px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              ${rows}
            </tbody>
          </table>
        </div>
      `;
    }

    try {
      const res = await fetchWithAuth('/actions/user_donations.php');
      const data = await res.json();
      if (data && data.success && Array.isArray(data.donations)) {
        allItems = data.donations;
      }
    } catch (e) {
      // Network/offline fallback
    }

    // If empty, fall back to initial demonstration records
    if (!allItems || allItems.length === 0) {
      allItems = [
        {
          id: 101,
          ref_code: 'REF-00123',
          title: 'Elementary Backpacks & Notebook Bundle',
          category: 'School Supplies',
          quantity: 5,
          unit: 'sets',
          item_condition: 'Brand New',
          status: 'approved',
          image_url: '../assets/images/categories/school-supplies.jpg',
          created_at: '2026-10-10',
          formatted_date: 'Oct 10, 2026'
        },
        {
          id: 102,
          ref_code: 'REF-00124',
          title: 'Assorted Children Winter Jackets & Sweaters',
          category: 'Clothing & Shoes',
          quantity: 8,
          unit: 'pcs',
          item_condition: 'Like New',
          status: 'pending',
          image_url: '../assets/images/categories/clothing-shoes.avif',
          created_at: '2026-10-12',
          formatted_date: 'Oct 12, 2026'
        },
        {
          id: 103,
          ref_code: 'REF-00125',
          title: 'Emergency Non-Perishable Canned Food Pack',
          category: 'Food & Groceries',
          quantity: 20,
          unit: 'cans',
          item_condition: 'Brand New',
          status: 'matched',
          image_url: '../assets/images/categories/food-groceries.jpg',
          created_at: '2026-10-15',
          formatted_date: 'Oct 15, 2026'
        }
      ];
    }

    updateCountBadges();
    renderTable();

    // Event listeners
    tabButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        tabButtons.forEach(b => {
          b.classList.remove('is-active');
          b.style.borderBottomColor = 'transparent';
          b.style.color = '#64748B';
          b.style.fontWeight = '600';
        });
        btn.classList.add('is-active');
        btn.style.borderBottomColor = 'var(--color-primary, #1F4FA3)';
        btn.style.color = 'var(--color-primary, #1F4FA3)';
        btn.style.fontWeight = '700';
        currentTab = btn.getAttribute('data-tab') || 'all';
        renderTable();
      });
    });

    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        searchQuery = e.target.value.trim();
        renderTable();
      });
    }

    if (categorySelect) {
      categorySelect.addEventListener('change', (e) => {
        categoryFilter = e.target.value;
        renderTable();
      });
    }

    if (sortSelect) {
      sortSelect.addEventListener('change', (e) => {
        sortBy = e.target.value;
        renderTable();
      });
    }

    window.archiveUserDonation = async function(id) {
      if (!confirm('Are you sure you want to archive this donation listing? It will no longer appear on public community feeds.')) {
        return;
      }
      try {
        const res = await fetchWithAuth('/actions/user_donations.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'archive', id: id })
        });
        const d = await res.json();
        if (d && d.success) {
          if (window.showPortalFlash) {
            window.showPortalFlash('success', 'Listing successfully archived.');
          }
        }
      } catch (err) {}
      const target = allItems.find(it => it.id === id);
      if (target) target.status = 'archived';
      updateCountBadges();
      renderTable();
    };
  }

  // Expose on window
  window.fetchWithAuth = fetchWithAuth;
  window.loadDashboardData = loadDashboardData;
  window.loadNotificationsData = loadNotificationsData;
  window.refreshSidebarNotifBadge = refreshSidebarNotifBadge;
  window.loadUserDonations = loadUserDonations;
  window.handleMarkAllRead = handleMarkAllRead;
  window.markAllRead = handleMarkAllRead;

  document.addEventListener('DOMContentLoaded', function() {
    if (checkUserSession()) {
      setupActiveNav();
      setupTopbarUser();
      initLogoutModal();

      // Automatically sync unread notification badge across all pages
      refreshSidebarNotifBadge();

      // Automatically trigger loader if on dashboard, notifications, or donations page
      if (document.getElementById('recentActivityContainer')) {
        loadDashboardData();
      }
      if (document.getElementById('notificationsList')) {
        loadNotificationsData();
      }
      if (document.getElementById('donationsTableWrap')) {
        loadUserDonations();
      }
    }
  });

})();
