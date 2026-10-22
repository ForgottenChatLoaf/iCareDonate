/* ============================================================
   iCareDonate Wireframe Helper Script (wireframe/assets/wireframe.js)
   Provides minimal interactions for demonstrating wireframe workflows:
   - Mobile sidebar toggle & backdrop
   - Topbar user dropdown toggle
   - Tab switching
   - Modal opening and closing
   - Filter/search demonstration
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Sidebar Toggle for Mobile View
  const toggleBtn = document.querySelector('.wf-topbar__toggle');
  const sidebar = document.querySelector('.wf-sidebar');

  if (toggleBtn && sidebar) {
    let backdrop = document.querySelector('.wf-sidebar-backdrop');
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.className = 'wf-sidebar-backdrop';
      backdrop.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.3);z-index:95;';
      document.body.appendChild(backdrop);
    }

    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('is-open');
      backdrop.style.display = sidebar.classList.contains('is-open') ? 'block' : 'none';
    });

    backdrop.addEventListener('click', function () {
      sidebar.classList.remove('is-open');
      backdrop.style.display = 'none';
    });
  }

  // 2. Tab Switching
  const tabContainers = document.querySelectorAll('.wf-tabs');
  tabContainers.forEach(container => {
    const tabs = container.querySelectorAll('.wf-tab');
    tabs.forEach(tab => {
      tab.addEventListener('click', function (e) {
        // If it's a wireframe button or link with no href, prevent default
        if (!this.getAttribute('href') || this.getAttribute('href').startsWith('#')) {
          e.preventDefault();
        }
        tabs.forEach(t => t.classList.remove('is-active'));
        this.classList.add('is-active');

        // Optional target pane toggle
        const targetId = this.getAttribute('data-target');
        if (targetId) {
          const panes = document.querySelectorAll('[data-pane]');
          panes.forEach(p => p.style.display = 'none');
          const targetPane = document.getElementById(targetId);
          if (targetPane) targetPane.style.display = 'block';
        }
      });
    });
  });

  // 3. Modal Open and Close Demonstrations
  window.wfOpenModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = 'flex';
    }
  };

  window.wfCloseModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = 'none';
    }
  };

  // Close modals when clicking backdrop or close buttons
  document.querySelectorAll('.wf-modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', function (e) {
      if (e.target === this) {
        this.style.display = 'none';
      }
    });
  });

  document.querySelectorAll('.wf-modal__close, [data-close-modal]').forEach(btn => {
    btn.addEventListener('click', function () {
      const modal = this.closest('.wf-modal-backdrop');
      if (modal) {
        modal.style.display = 'none';
      }
    });
  });

  // 4. Form Submit Prevention for Wireframe Demos
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      alert('[Wireframe Notice]: Form submitted in preview mode. No live backend actions are executed.');
    });
  });
});
