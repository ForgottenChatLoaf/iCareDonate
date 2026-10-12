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
    const FALLBACK_SRC = 'assets/images/placeholder.svg';
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
  // DOM READY INITIALIZATION
  // ============================================================
  document.addEventListener('DOMContentLoaded', function() {
    initBackToTop();
    initScrollReveal();
    initTabs();
    initAccordions();
    initImageFallbacks();
  });

  window.ICD_MAIN = {
    showToast: showToast,
    refreshScrollReveal: refreshScrollReveal
  };
})();
