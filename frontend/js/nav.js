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

  function updateAuthNavigation() {
    let user = null;
    let token = null;
    try {
      token = localStorage.getItem('icd_token');
      user = JSON.parse(localStorage.getItem('icd_user') || 'null');
    } catch(e) {}

    const isSub = window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/');
    const logoutTarget = isSub ? '../logout.html' : 'logout.html';

    const authBtns = document.querySelectorAll('.btn-pub-auth, .mobile-link--accent');
    if (token && user && authBtns.length > 0) {
      const dashboardUrl = (user.role === 'admin' || user.role === 'superadmin') ? 'admin/index.html' : 'user/dashboard.html';
      const label = `${user.first_name || 'My'} Dashboard`;
      authBtns.forEach(btn => {
        btn.href = isSub ? ('../' + dashboardUrl) : dashboardUrl;
        btn.textContent = label;
        btn.title = `Logged in as ${user.first_name} ${user.last_name || ''}`;
      });

      // Desktop public actions — append clean logout button if not already present
      const authActions = document.querySelector('.nav-public__actions');
      if (authActions && !document.getElementById('nav-pub-logout-btn')) {
        const logoutBtn = document.createElement('a');
        logoutBtn.id = 'nav-pub-logout-btn';
        logoutBtn.href = logoutTarget;
        logoutBtn.className = 'btn-pub-logout';
        logoutBtn.textContent = 'Log out';
        logoutBtn.style.cssText = 'display:inline-flex;align-items:center;padding:7px 14px;margin-left:8px;font-size:13.5px;font-weight:600;color:#DC2626;background:#FEE2E2;border:1px solid #FECACA;border-radius:8px;text-decoration:none;transition:all 0.2s ease;';
        logoutBtn.addEventListener('mouseenter', function() { this.style.background = '#FCA5A5'; this.style.color = '#7F1D1D'; });
        logoutBtn.addEventListener('mouseleave', function() { this.style.background = '#FEE2E2'; this.style.color = '#DC2626'; });
        logoutBtn.addEventListener('click', function(e) {
          e.preventDefault();
          try {
            localStorage.removeItem('icd_token');
            localStorage.removeItem('icd_user');
            sessionStorage.clear();
          } catch(err) {}
          window.location.href = logoutTarget;
        });
        authActions.appendChild(logoutBtn);
      }

      // Mobile drawer — append logout link if not present
      const mobileMenu = document.querySelector('.mobile-menu__links');
      if (mobileMenu && !document.getElementById('mobile-pub-logout-link')) {
        const mobLogout = document.createElement('a');
        mobLogout.id = 'mobile-pub-logout-link';
        mobLogout.href = logoutTarget;
        mobLogout.className = 'mobile-link mobile-link--logout';
        mobLogout.style.color = '#DC2626';
        mobLogout.style.fontWeight = '600';
        mobLogout.textContent = 'Log out';
        mobLogout.addEventListener('click', function(e) {
          e.preventDefault();
          try {
            localStorage.removeItem('icd_token');
            localStorage.removeItem('icd_user');
            sessionStorage.clear();
          } catch(err) {}
          window.location.href = logoutTarget;
        });
        mobileMenu.appendChild(mobLogout);
      }
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    initHeaderScroll();
    initMobileMenu();
    highlightActiveLink();
    updateAuthNavigation();
  });
})();
