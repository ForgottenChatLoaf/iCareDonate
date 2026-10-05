// iCareDonate — Application Interactions & Vanilla UI Enhancements
document.addEventListener('DOMContentLoaded', () => {

    // 0. Initialise Lucide icons (CDN loaded before this script)
    if (window.lucide) lucide.createIcons();

    // 1. Theme Toggle — uses Lucide sun / moon icons
    const themeBtn = document.getElementById('theme-toggle');
    const savedTheme = localStorage.getItem('icaredonate_theme');

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        updateThemeIcon(theme);
    }

    if (savedTheme) {
        applyTheme(savedTheme);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        applyTheme('dark');
    }

    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            const target = current === 'dark' ? 'light' : 'dark';
            localStorage.setItem('icaredonate_theme', target);
            applyTheme(target);
        });
    }

    function updateThemeIcon(theme) {
        if (!themeBtn) return;
        // Replace inner icon element with the correct Lucide icon
        themeBtn.innerHTML = theme === 'dark'
            ? '<i data-lucide="sun"   style="width:16px;height:16px;display:block;"></i>'
            : '<i data-lucide="moon" style="width:16px;height:16px;display:block;"></i>';
        if (window.lucide) lucide.createIcons({ nodes: [themeBtn] });
    }

    // 2. Mobile Menu Toggle
    const mobileToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
        });
    }

    // 3. Password Visibility Toggle
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.closest('.password-wrapper')?.querySelector('input');
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.textContent = 'Hide';
                } else {
                    input.type = 'password';
                    btn.textContent = 'Show';
                }
            }
        });
    });

    // 4. Image Previews for File Inputs
    document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
        const previewTargetId = input.getAttribute('data-preview');
        const previewEl = document.getElementById(previewTargetId);
        if (previewEl) {
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (re) => {
                        previewEl.src = re.target.result;
                        previewEl.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });

    // 5. Modal Handlers
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.add('active');
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('active');
    };

    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.remove('active');
        });
    });

    // 6. Flash Alert Auto-dismiss
    document.querySelectorAll('.alert-auto-dismiss').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });
});
