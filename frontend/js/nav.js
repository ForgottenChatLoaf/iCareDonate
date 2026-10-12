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

      // Check for match
      const isMatch = (pageFilename === '' && (targetFilename === 'index.html' || targetFilename === '')) ||
                      (pageFilename === targetFilename) ||
                      (pageFilename === 'index.html' && targetFilename === 'index.html');

      if (isMatch) {
        link.classList.add('active');
        link.setAttribute('aria-current', 'page');
      } else {
        link.classList.remove('active');
        link.removeAttribute('aria-current');
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    initHeaderScroll();
    initMobileMenu();
    highlightActiveLink();
  });
})();
