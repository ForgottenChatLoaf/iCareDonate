/**
 * iCareDonate — Form Validation, File Previews, & Submission Handler
 * frontend/js/forms.js
 */

(function() {
  'use strict';

  const API_BASE_URL = (function() {
    const origin = window.location.origin;
    if (window.location.pathname.includes('/CommunityDono/')) {
      return `${origin}/CommunityDono/api`;
    }
    return '/api';
  })();

  // ============================================================
  // PREFILL FORM FROM URL PARAMETERS
  // ============================================================
  function prefillFromQueryParams() {
    const params = new URLSearchParams(window.location.search);
    const category = params.get('category');
    const donationId = params.get('donation_id');

    if (category) {
      const catSelect = document.querySelector('select[name="category"], select#category');
      if (catSelect) {
        // Try finding option by value or slug
        Array.from(catSelect.options).forEach(opt => {
          if (opt.value.toLowerCase() === category.toLowerCase() || 
              opt.text.toLowerCase().includes(category.toLowerCase())) {
            opt.selected = true;
          }
        });
      }
    }

    if (donationId) {
      const hiddenItemInput = document.querySelector('input[name="donation_id"], input#donation_id');
      if (hiddenItemInput) hiddenItemInput.value = donationId;
    }
  }

  // ============================================================
  // PASSWORD VISIBILITY TOGGLE
  // ============================================================
  function initPasswordToggles() {
    document.querySelectorAll('.btn-toggle-password').forEach(btn => {
      btn.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = targetId ? document.getElementById(targetId) : this.closest('.input-password-wrap').querySelector('input');
        if (!input) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        
        this.innerHTML = isPassword 
          ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>'
          : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
      });
    });
  }

  // ============================================================
  // FILE UPLOAD VALIDATION & PREVIEW
  // ============================================================
  function initFileUploads() {
    document.querySelectorAll('.file-dropzone').forEach(dropzone => {
      const fileInput = dropzone.querySelector('input[type="file"]');
      const previewWrap = dropzone.parentElement.querySelector('.file-preview-wrap');
      if (!fileInput || !previewWrap) return;

      const thumb = previewWrap.querySelector('.file-preview-thumb');
      const nameEl = previewWrap.querySelector('.file-preview-name');
      const sizeEl = previewWrap.querySelector('.file-preview-size');
      const removeBtn = previewWrap.querySelector('.file-preview-remove');

      function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
      }

      function handleFile(file) {
        if (!file) return;

        // Size limit: 5MB
        const MAX_SIZE = 5 * 1024 * 1024;
        if (file.size > MAX_SIZE) {
          if (window.ICD_MAIN) {
            window.ICD_MAIN.showToast('File too large', 'Please upload a photo under 5MB.', 'error');
          }
          fileInput.value = '';
          return;
        }

        // Preview thumbnail
        if (file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = function(e) {
            if (thumb) thumb.src = e.target.result;
          };
          reader.readAsDataURL(file);
        } else {
          if (thumb) thumb.src = 'assets/images/placeholder.svg';
        }

        if (nameEl) nameEl.textContent = file.name;
        if (sizeEl) sizeEl.textContent = formatSize(file.size);
        previewWrap.classList.add('has-file');
      }

      fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
          handleFile(this.files[0]);
        }
      });

      // Drag and drop events
      ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, e => {
          e.preventDefault();
          dropzone.classList.add('dragover');
        });
      });

      ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, e => {
          e.preventDefault();
          dropzone.classList.remove('dragover');
        });
      });

      dropzone.addEventListener('drop', e => {
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
          fileInput.files = e.dataTransfer.files;
          handleFile(e.dataTransfer.files[0]);
        }
      });

      if (removeBtn) {
        removeBtn.addEventListener('click', function(e) {
          e.preventDefault();
          fileInput.value = '';
          previewWrap.classList.remove('has-file');
          if (thumb) thumb.src = '';
        });
      }
    });
  }

  // ============================================================
  // FORM VALIDATION & SUBMISSION
  // ============================================================
  function validateField(input) {
    const errorEl = input.parentElement.querySelector('.form-error');
    let isValid = true;
    let message = '';

    const val = input.value.trim();

    // Required check
    if (input.hasAttribute('required') && !val) {
      isValid = false;
      message = 'This field is required.';
    } 
    // Email check
    else if (input.type === 'email' && val) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(val)) {
        isValid = false;
        message = 'Please enter a valid email address.';
      }
    } 
    // Minimum length check
    else if (input.minLength && val.length < input.minLength) {
      isValid = false;
      message = `Must be at least ${input.minLength} characters.`;
    }
    // Password match check
    else if (input.id === 'signup-confirm-password' || input.name === 'confirm_password') {
      const pass = document.getElementById('signup-password') || document.querySelector('input[name="password"]');
      if (pass && pass.value !== val) {
        isValid = false;
        message = 'Passwords do not match.';
      }
    }

    if (!isValid) {
      input.classList.add('is-invalid');
      if (errorEl) {
        errorEl.textContent = message;
        errorEl.style.display = 'block';
      }
    } else {
      input.classList.remove('is-invalid');
      if (errorEl) {
        errorEl.style.display = 'none';
      }
    }

    return isValid;
  }

  function handleFormSubmit(form, successTitle, successMsg, redirectUrl) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();

      let isFormValid = true;
      const inputs = form.querySelectorAll('input:not([type="hidden"]), select, textarea');
      inputs.forEach(input => {
        if (!validateField(input)) {
          isFormValid = false;
        }
      });

      if (!isFormValid) {
        if (window.ICD_MAIN) {
          window.ICD_MAIN.showToast('Validation Error', 'Please check highlighted fields.', 'error');
        }
        return;
      }

      // Submission UI state
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalBtnHTML = submitBtn ? submitBtn.innerHTML : '';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="btn-spinner"></span> Submitting...';
      }

      // Simulate API submission
      // TODO: connect to PHP endpoint via fetch(`${API_BASE_URL}/...`, { method: 'POST', body: new FormData(form) })
      setTimeout(() => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnHTML;
        }

        if (window.ICD_MAIN) {
          window.ICD_MAIN.showToast(successTitle, successMsg, 'success');
        }

        form.reset();
        const preview = form.querySelector('.file-preview-wrap');
        if (preview) preview.classList.remove('has-file');

        if (redirectUrl) {
          setTimeout(() => {
            window.location.href = redirectUrl;
          }, 1500);
        }
      }, 1000);
    });

    // Real-time error clearance on input
    form.querySelectorAll('input, select, textarea').forEach(input => {
      input.addEventListener('input', function() {
        if (this.classList.contains('is-invalid')) {
          validateField(this);
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    prefillFromQueryParams();
    initPasswordToggles();
    initFileUploads();

    // Wire forms
    const donateForm = document.getElementById('donate-form');
    if (donateForm) {
      handleFormSubmit(
        donateForm, 
        'Donation Listing Submitted!', 
        'Your donation has been sent to administrators for review. Thank you for your generosity!',
        'donations.html'
      );
    }

    const requestForm = document.getElementById('request-form');
    if (requestForm) {
      handleFormSubmit(
        requestForm, 
        'Request Submitted!', 
        'Your request and proof documents are being verified by our community coordinators.',
        'donations.html'
      );
    }

    const contactForm = document.getElementById('contact-form');
    if (contactForm) {
      handleFormSubmit(
        contactForm, 
        'Message Sent!', 
        'Thank you for contacting iCareDonate. Our support team will reply within 24 hours.'
      );
    }

    const supportForm = document.getElementById('support-form');
    if (supportForm) {
      handleFormSubmit(
        supportForm, 
        'Support Ticket Created!', 
        'Your help request has been logged. Ticket #ICD-' + Math.floor(1000 + Math.random() * 9000)
      );
    }

    const loginForm = document.getElementById('user-login-form');
    if (loginForm) {
      loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const emailInput = document.getElementById('login-email');
        const passInput = document.getElementById('login-password');
        if (!validateField(emailInput) || !validateField(passInput)) {
          return;
        }

        const submitBtn = loginForm.querySelector('button[type="submit"]');
        const origText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="btn-spinner"></span> Logging in...';

        try {
          const res = await fetch(`${API_BASE_URL}/v1/auth/login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
              email: emailInput.value.trim(),
              password: passInput.value
            })
          });

          const data = await res.json();
          if (!res.ok) {
            throw new Error(data.message || 'Login failed. Please check your credentials.');
          }

          if (data.token) localStorage.setItem('icd_token', data.token);
          if (data.user) localStorage.setItem('icd_user', JSON.stringify(data.user));

          if (window.ICD_MAIN) {
            window.ICD_MAIN.showToast('Welcome back!', `Logged in as ${data.user.first_name} ${data.user.last_name}`, 'success');
          }

          setTimeout(() => {
            if (data.user.role === 'admin' || data.user.role === 'superadmin') {
              window.location.href = '/CommunityDono/admin/';
            } else {
              window.location.href = 'index.html';
            }
          }, 1000);
        } catch (err) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
          if (window.ICD_MAIN) {
            window.ICD_MAIN.showToast('Login Failed', err.message, 'error');
          } else {
            alert(err.message);
          }
        }
      });
    }

    const signupForm = document.getElementById('user-signup-form');
    if (signupForm) {
      signupForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const nameInput = document.getElementById('signup-name');
        const emailInput = document.getElementById('signup-email');
        const phoneInput = document.getElementById('signup-phone');
        const passInput = document.getElementById('signup-password');
        const confirmInput = document.getElementById('signup-confirm-password');

        let valid = true;
        [nameInput, emailInput, phoneInput, passInput, confirmInput].forEach(inp => {
          if (inp && !validateField(inp)) valid = false;
        });
        if (!valid) return;

        const fullName = (nameInput ? nameInput.value : '').trim();
        const parts = fullName.split(/\s+/);
        const firstName = parts[0] || 'Community';
        const lastName = parts.slice(1).join(' ') || 'Member';

        const submitBtn = signupForm.querySelector('button[type="submit"]');
        const origText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="btn-spinner"></span> Creating account...';

        try {
          const res = await fetch(`${API_BASE_URL}/v1/auth/register.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
              first_name: firstName,
              last_name: lastName,
              email: emailInput.value.trim(),
              phone: phoneInput ? phoneInput.value.trim() : '',
              password: passInput.value,
              confirm_password: confirmInput ? confirmInput.value : passInput.value
            })
          });

          const data = await res.json();
          if (!res.ok) {
            const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Registration failed.');
            throw new Error(msg);
          }

          if (data.token) localStorage.setItem('icd_token', data.token);
          if (data.user) localStorage.setItem('icd_user', JSON.stringify(data.user));

          if (window.ICD_MAIN) {
            window.ICD_MAIN.showToast('Account Created!', 'Welcome to iCareDonate community!', 'success');
          }

          setTimeout(() => {
            window.location.href = 'index.html';
          }, 1200);
        } catch (err) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
          if (window.ICD_MAIN) {
            window.ICD_MAIN.showToast('Registration Error', err.message, 'error');
          } else {
            alert(err.message);
          }
        }
      });
    }

    const adminLoginForm = document.getElementById('admin-login-form');
    if (adminLoginForm) {
      handleFormSubmit(
        adminLoginForm, 
        'Admin Verified', 
        'Administrative session initialized successfully.',
        'index.html'
      );
    }
  });

  window.ICD_FORMS = {
    API_BASE_URL: API_BASE_URL,
    validateField: validateField
  };
})();
