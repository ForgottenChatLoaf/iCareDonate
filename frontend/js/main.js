/**
 * iCareDonate — Main Application Script
 * frontend/js/main.js
 */

(function() {
  'use strict';

  // ============================================================
  // TOAST NOTIFICATION SYSTEM
  // ============================================================
  function showToast(title, message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.setAttribute('aria-live', 'polite');
      container.setAttribute('aria-atomic', 'true');
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.setAttribute('role', 'alert');

    toast.innerHTML = `
      <div class="toast-content">
        <div class="toast-title">${title}</div>
        <div class="toast-msg">${message}</div>
      </div>
      <button type="button" class="toast-close" aria-label="Close notification">&times;</button>
    `;

    container.appendChild(toast);

    function removeToast() {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px) scale(0.95)';
      setTimeout(() => {
        if (toast.parentElement) toast.parentElement.removeChild(toast);
      }, 250);
    }

    const closeBtn = toast.querySelector('.toast-close');
    if (closeBtn) closeBtn.addEventListener('click', removeToast);

    setTimeout(removeToast, 4500);
  }

  // ============================================================
  // BACK TO TOP BUTTON
  // ============================================================
  function initBackToTop() {
    let btn = document.querySelector('.btn-back-to-top');
    if (!btn) {
      btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn-back-to-top';
      btn.setAttribute('aria-label', 'Back to top of page');
      btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>';
      document.body.appendChild(btn);
    }

    function toggleBtn() {
      if (window.scrollY > 300) {
        btn.classList.add('visible');
      } else {
        btn.classList.remove('visible');
      }
    }

    window.addEventListener('scroll', toggleBtn, { passive: true });
    toggleBtn();

    btn.addEventListener('click', function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  // ============================================================
  // SCROLL REVEAL OBSERVER
  // ============================================================
  let scrollObserver = null;

  function initScrollReveal() {
    if (!('IntersectionObserver' in window)) {
      document.querySelectorAll('.reveal-on-scroll').forEach(el => el.classList.add('is-visible'));
      return;
    }

    scrollObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, {
      rootMargin: '0px 0px -40px 0px',
      threshold: 0.1
    });

    document.querySelectorAll('.reveal-on-scroll').forEach(el => {
      scrollObserver.observe(el);
    });
  }

  function refreshScrollReveal() {
    if (!scrollObserver) return;
    document.querySelectorAll('.reveal-on-scroll:not(.is-visible)').forEach(el => {
      scrollObserver.observe(el);
    });
  }

  // ============================================================
  // TABS CONTROLLER (Login / Signup)
  // ============================================================
  function initTabs() {
    document.querySelectorAll('.tab-group').forEach(tabGroup => {
      const tabButtons = tabGroup.querySelectorAll('.tab-btn');
      const tabPanels = tabGroup.querySelectorAll('.tab-panel');

      tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
          const target = this.getAttribute('data-tab');

          tabButtons.forEach(b => {
            b.classList.remove('active');
            b.setAttribute('aria-selected', 'false');
          });
          tabPanels.forEach(p => {
            p.classList.remove('active');
            p.setAttribute('hidden', '');
          });

          this.classList.add('active');
          this.setAttribute('aria-selected', 'true');

          const activePanel = tabGroup.querySelector(`.tab-panel[data-tab="${target}"]`);
          if (activePanel) {
            activePanel.classList.add('active');
            activePanel.removeAttribute('hidden');
          }
        });
      });
    });
  }

  // ============================================================
  // ACCORDION CONTROLLER (FAQs)
  // ============================================================
  function initAccordions() {
    document.querySelectorAll('.accordion-header').forEach(header => {
      header.addEventListener('click', function() {
        const item = this.closest('.accordion-item');
        const isOpen = item.classList.contains('open');

        // Close other items in the same accordion if needed
        const parentAccordion = this.closest('.accordion');
        if (parentAccordion && !parentAccordion.hasAttribute('data-multi-open')) {
          parentAccordion.querySelectorAll('.accordion-item').forEach(other => {
            if (other !== item) other.classList.remove('open');
          });
        }

        item.classList.toggle('open', !isOpen);
        this.setAttribute('aria-expanded', !isOpen);
      });
    });
  }

  // ============================================================
  // GLOBAL IMAGE FALLBACK SAFEGUARD
  // ============================================================
  function initImageFallbacks() {
    const isInSubdir = window.location.pathname.includes('/user') || window.location.pathname.includes('/admin');
    const FALLBACK_SRC = isInSubdir ? '../assets/images/placeholder.svg' : 'assets/images/placeholder.svg';
    function attachFallback(img) {
      if (img.dataset.hasFallback) return;
      img.dataset.hasFallback = 'true';
      img.addEventListener('error', function() {
        if (!this.src.endsWith('placeholder.svg')) {
          this.src = FALLBACK_SRC;
        }
      });
      if (img.complete && img.naturalWidth === 0 && img.src && !img.src.endsWith('placeholder.svg')) {
        img.src = FALLBACK_SRC;
      }
    }

    document.querySelectorAll('img').forEach(attachFallback);

    if (window.MutationObserver) {
      const observer = new MutationObserver(mutations => {
        mutations.forEach(m => {
          m.addedNodes.forEach(node => {
            if (node.nodeType === 1) {
              if (node.tagName === 'IMG') attachFallback(node);
              else if (node.querySelectorAll) node.querySelectorAll('img').forEach(attachFallback);
            }
          });
        });
      });
      observer.observe(document.body, { childList: true, subtree: true });
    }
  }

  // ============================================================
  // CATEGORY ACTION MODAL & AUTH NAVIGATION (PHASE 2)
  // ============================================================
  function isUserAuthenticated() {
    try {
      return !!(localStorage.getItem('icd_token') && localStorage.getItem('icd_user'));
    } catch (e) {
      return false;
    }
  }

  function initCategoryActionModal() {
    let modalBackdrop = document.getElementById('category-action-modal');
    if (!modalBackdrop) {
      modalBackdrop = document.createElement('div');
      modalBackdrop.id = 'category-action-modal';
      modalBackdrop.className = 'modal-backdrop';
      modalBackdrop.setAttribute('aria-hidden', 'true');
      modalBackdrop.setAttribute('role', 'dialog');
      modalBackdrop.setAttribute('aria-modal', 'true');
      modalBackdrop.setAttribute('aria-labelledby', 'cat-modal-title');
      modalBackdrop.innerHTML = `
        <div class="modal" style="max-width: 440px; padding: 28px 24px; text-align: center;">
          <div style="display: flex; justify-content: flex-end; margin-top: -8px; margin-right: -4px;">
            <button type="button" class="modal-close" id="cat-modal-close" aria-label="Close dialog" style="font-size: 26px;">&times;</button>
          </div>
          <div style="width: 52px; height: 52px; border-radius: 50%; background: var(--sky); color: var(--cobalt); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
          </div>
          <h3 id="cat-modal-title" style="margin-bottom: 6px; font-size: 1.25rem; color: var(--cobalt); font-weight: 700;">Participate in Giving</h3>
          <p id="cat-modal-desc" style="font-size: 14px; color: var(--slate); margin-bottom: 22px; line-height: 1.5;">How would you like to participate in <strong id="cat-modal-catname" style="color: var(--navy);">this aid area</strong>?</p>
          <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 18px;">
            <a id="cat-modal-donate-btn" href="signup.html?redirect=user/offer-donation.html" class="btn btn-donate btn-block" style="justify-content: center; padding: 13px; font-size: 15px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect width="20" height="5" x="2" y="7"/><line x1="12" x2="12" y1="22" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
              Offer a Donation
            </a>
            <a id="cat-modal-request-btn" href="signup.html?redirect=user/request-donation.html" class="btn btn-primary btn-block" style="justify-content: center; padding: 13px; font-size: 15px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
              Request Assistance
            </a>
          </div>
          <div id="cat-modal-auth-hint" style="font-size: 13px; color: var(--text-muted);">
            Already have an account? <a id="cat-modal-login-btn" href="login.html" style="color: var(--cobalt); font-weight: 600; text-decoration: underline;">Log in</a>
          </div>
        </div>
      `;
      document.body.appendChild(modalBackdrop);
    }

    function closeModal() {
      modalBackdrop.classList.remove('open');
      modalBackdrop.setAttribute('aria-hidden', 'true');
    }

    modalBackdrop.addEventListener('click', function(e) {
      if (e.target === modalBackdrop || e.target.id === 'cat-modal-close' || e.target.closest('#cat-modal-close')) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function(e) {
      if ((e.key === 'Escape' || e.key === 'Esc') && modalBackdrop.classList.contains('open')) {
        closeModal();
      }
    });

    // Delegated click handler on any .category-action-btn
    document.addEventListener('click', function(e) {
      const btn = e.target.closest('.category-action-btn');
      if (!btn) return;
      e.preventDefault();

      const catSlug = btn.getAttribute('data-category') || '';
      const catName = btn.getAttribute('data-category-name') || 'this category';
      const authed = isUserAuthenticated();

      const titleEl = document.getElementById('cat-modal-catname');
      const donateBtn = document.getElementById('cat-modal-donate-btn');
      const requestBtn = document.getElementById('cat-modal-request-btn');
      const loginBtn = document.getElementById('cat-modal-login-btn');
      const hintEl = document.getElementById('cat-modal-auth-hint');

      if (titleEl) titleEl.textContent = catName;

      if (authed) {
        if (donateBtn) donateBtn.href = catSlug ? `user/offer-donation.html?category=${encodeURIComponent(catSlug)}` : 'user/offer-donation.html';
        if (requestBtn) requestBtn.href = catSlug ? `user/request-donation.html?category=${encodeURIComponent(catSlug)}` : 'user/request-donation.html';
        if (hintEl) hintEl.style.display = 'none';
      } else {
        const donateRedirect = catSlug ? `user/offer-donation.html?category=${encodeURIComponent(catSlug)}` : 'user/offer-donation.html';
        const requestRedirect = catSlug ? `user/request-donation.html?category=${encodeURIComponent(catSlug)}` : 'user/request-donation.html';
        if (donateBtn) donateBtn.href = `signup.html?redirect=${encodeURIComponent(donateRedirect)}&action=donate`;
        if (requestBtn) requestBtn.href = `signup.html?redirect=${encodeURIComponent(requestRedirect)}&action=request`;
        if (loginBtn) loginBtn.href = `login.html?redirect=${encodeURIComponent(donateRedirect)}`;
        if (hintEl) hintEl.style.display = 'block';
      }

      modalBackdrop.classList.add('open');
      modalBackdrop.setAttribute('aria-hidden', 'false');
      const closeBtn = document.getElementById('cat-modal-close');
      if (closeBtn) closeBtn.focus();
    });
  }

  function initAuthActionLinks() {
    const authed = isUserAuthenticated();
    const startBtns = document.querySelectorAll('#home-start-donating-btn, #hero-donate-btn');
    const reqBtns = document.querySelectorAll('#hero-request-btn');

    if (authed) {
      startBtns.forEach(b => {
        b.href = 'user/offer-donation.html';
      });
      reqBtns.forEach(b => {
        b.href = 'user/request-donation.html';
      });
    } else {
      startBtns.forEach(b => {
        b.href = 'signup.html?redirect=user/offer-donation.html';
      });
      reqBtns.forEach(b => {
        b.href = 'signup.html?redirect=user/request-donation.html';
      });
    }
  }

  // ============================================================
  // DOM READY INITIALIZATION
  // ============================================================
  document.addEventListener('DOMContentLoaded', function() {
    initBackToTop();
    initScrollReveal();
    initTabs();
    initAccordions();
    initImageFallbacks();
    initCategoryActionModal();
    initAuthActionLinks();
  });

  window.ICD_MAIN = {
    showToast: showToast,
    refreshScrollReveal: refreshScrollReveal,
    isUserAuthenticated: isUserAuthenticated
  };
})();
