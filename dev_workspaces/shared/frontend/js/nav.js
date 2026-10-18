/**
 * iCareDonate — Navigation, Sticky Header & Mobile Drawer
 * frontend/js/nav.js
 */

(function() {
  'use strict';

  function initHeaderScroll() {
    const header = document.querySelector('.header-public');
    if (!header) return;

    function onScroll() {
      if (window.scrollY > 20) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  function initMobileMenu() {
    const hamburger = document.getElementById('nav-hamburger');
    const mobileMenu = document.getElementById('mobile-menu');
    if (!hamburger || !mobileMenu) return;

    function openMenu() {
      hamburger.setAttribute('aria-expanded', 'true');
      mobileMenu.classList.add('open');
      mobileMenu.removeAttribute('hidden');
    }

    function closeMenu() {
      hamburger.setAttribute('aria-expanded', 'false');
      mobileMenu.classList.remove('open');
      mobileMenu.setAttribute('hidden', '');
    }

    hamburger.addEventListener('click', function(e) {
      e.stopPropagation();
      const isOpen = hamburger.getAttribute('aria-expanded') === 'true';
      if (isOpen) {
        closeMenu();
      } else {
        openMenu();
      }
    });

    // Close when clicking outside header
    document.addEventListener('click', function(e) {
      const header = document.querySelector('.header-public');
      if (header && !header.contains(e.target)) {
        closeMenu();
      }
    });

    // Close when Esc key is pressed
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' || e.key === 'Esc') {
        closeMenu();
      }
    });

    // Close on window resize if expanded
    window.addEventListener('resize', function() {
      if (window.innerWidth > 900) {
        closeMenu();
      }
    }, { passive: true });
  }

  function highlightActiveLink() {
    const currentPath = window.location.pathname.toLowerCase();
    const pageFilename = currentPath.split('/').pop() || 'index.html';

    const links = document.querySelectorAll('.nav-link, .mobile-link');
    links.forEach(link => {
      const href = link.getAttribute('href');
      if (!href) return;
      const targetFilename = href.split('/').pop().split('?')[0].toLowerCase();

      // Check for match (including child/subpage mappings)
      const isMatch = (pageFilename === '' && (targetFilename === 'index.html' || targetFilename === '')) ||
                      (pageFilename === targetFilename) ||
                      (pageFilename === 'index.html' && targetFilename === 'index.html') ||
                      ((pageFilename === 'mission.html' || pageFilename === 'careers.html') && targetFilename === 'about.html') ||
                      ((pageFilename === 'donation-details.html') && targetFilename === 'donations.html') ||
                      ((pageFilename === 'contact-support.html') && targetFilename === 'contact.html') ||
                      ((pageFilename === 'help-center.html' || pageFilename === 'faqs.html') && targetFilename === 'how-it-works.html');

      if (isMatch) {
        link.classList.add('active');
        link.classList.add('nav-link--active');
        link.classList.add('mobile-link--active');
        link.setAttribute('aria-current', 'page');
      } else {
        link.classList.remove('active');
        link.classList.remove('nav-link--active');
        link.classList.remove('mobile-link--active');
        link.removeAttribute('aria-current');
      }
    });
  }

  function initAuthNav() {
    const token = localStorage.getItem('icd_token');
    const userStr = localStorage.getItem('icd_user');
    let user = null;
    try {
      if (userStr) user = JSON.parse(userStr);
    } catch (e) {}

    const authActions = document.querySelector('.nav-public__actions');
    const mobileLinks = document.querySelector('.mobile-menu__links');

    if (token && user) {
      // Desktop navbar update
      if (authActions) {
        const authBtn = authActions.querySelector('.btn-pub-auth');
        if (authBtn) {
          const isStaff = user.role === 'admin' || user.role === 'superadmin';
          authBtn.href = isStaff ? 'admin-dashboard.html' : 'user-dashboard.html';
          authBtn.textContent = isStaff ? 'Admin Panel' : 'Dashboard';
        }

        if (!authActions.querySelector('.btn-nav-logout')) {
          const logoutBtn = document.createElement('button');
          logoutBtn.type = 'button';
          logoutBtn.className = 'nav-link btn-nav-logout';
          logoutBtn.style.cssText = 'background:none; border:none; cursor:pointer; font-size:13.5px; color:var(--text-muted); font-family:inherit; padding:8px 12px;';
          logoutBtn.textContent = 'Log out';
          logoutBtn.addEventListener('click', handleLogout);
          authActions.appendChild(logoutBtn);
        }
      }

      // Mobile drawer update
      if (mobileLinks) {
        const authMobile = mobileLinks.querySelector('a[href="login.html"]');
        if (authMobile) {
          const isStaff = user.role === 'admin' || user.role === 'superadmin';
          authMobile.href = isStaff ? 'admin-dashboard.html' : 'user-dashboard.html';
          authMobile.textContent = isStaff ? 'Admin Panel' : 'Dashboard';
        }

        if (!mobileLinks.querySelector('.btn-mobile-logout')) {
          const mobileLogout = document.createElement('a');
          mobileLogout.href = '#';
          mobileLogout.className = 'mobile-link mobile-link--muted btn-mobile-logout';
          mobileLogout.textContent = 'Log out';
          mobileLogout.addEventListener('click', function(e) {
            e.preventDefault();
            handleLogout();
          });
          mobileLinks.appendChild(mobileLogout);
        }
      }
    }
  }

  async function handleLogout() {
    const token = localStorage.getItem('icd_token');
    try {
      if (token) {
        // Resolve API base dynamically — same pattern as forms.js and data.js
        var apiBase = (function() {
          var origin = window.location.origin;
          if (window.location.pathname.includes('/CommunityDono/')) {
            return origin + '/CommunityDono/api';
          }
          return '/api';
        })();
        await fetch(apiBase + '/v1/auth/logout.php', {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${token}` }
        });
      }
    } catch (e) {
      console.warn('Logout API call error:', e);
    }
    localStorage.removeItem('icd_token');
    localStorage.removeItem('icd_user');
    window.location.reload();
  }

  document.addEventListener('DOMContentLoaded', function() {
    initHeaderScroll();
    initMobileMenu();
    highlightActiveLink();
    initAuthNav();
  });
})();
