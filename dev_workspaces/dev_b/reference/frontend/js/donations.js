/**
 * iCareDonate — Donations Browsing, Filtering & Details Renderer
 * frontend/js/donations.js
 */

(function() {
  'use strict';

  const FALLBACK_IMAGE = 'assets/images/placeholder.svg';

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getAvailableQty(item) {
    if (item.quantity_available !== undefined) {
      return Number(item.quantity_available);
    }
    const total = Number(item.quantity_total || item.quantity || 0);
    const reserved = Number(item.quantity_reserved || 0);
    const claimed = Number(item.quantity_claimed || 0);
    return Math.max(0, total - reserved - claimed);
  }

  /**
   * Generates donation card HTML matching index.php & browse.php
   */
  function createDonationCardHTML(item) {
    const available = getAvailableQty(item);
    const categoryName = item.category_name || item.categoryName || 'General Aid';
    const city = item.pickup_city || item.location || 'Pasig City';
    const img = item.image_url || item.image || FALLBACK_IMAGE;
    const title = item.title || 'Donation Item';
    const id = item.id;

    return `
      <div class="card reveal-on-scroll" data-id="${id}" style="border-radius: 20px; border: 1px solid var(--sky); background: var(--surface); overflow: hidden; display: flex; flex-direction: column; box-shadow: var(--shadow-sm); padding: 0; transition: transform 0.2s, box-shadow 0.2s;">
        <div style="height: 170px; position: relative; overflow: hidden; background: var(--surface-soft);">
          <img 
            src="${img}" 
            alt="${escapeHtml(title)}" 
            style="width: 100%; height: 100%; object-fit: cover; display: block;" 
            loading="lazy" 
            onerror="this.onerror=null; this.src='${FALLBACK_IMAGE}';"
          >
          <span style="position: absolute; top: 12px; left: 12px; background: rgba(20, 33, 61, 0.82); color: #fff; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 999px; backdrop-filter: blur(4px);">
            ${escapeHtml(categoryName)}
          </span>
          <span style="position: absolute; bottom: 12px; right: 12px; background: var(--cobalt); color: #fff; font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 999px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
            ${available} left
          </span>
        </div>
        <div style="padding: 20px 22px 22px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
          <div>
            <div style="margin-bottom: 8px;">
              <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;background:var(--sky, #DCE8F7);color:var(--cobalt, #1F4FA3);padding:2px 8px;border-radius:999px;" title="Identity and proof reviewed by the iCareDonate admin team.">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                Verified Donor
              </span>
            </div>
            <h3 style="font-size: 18px; margin: 0 0 8px; color: var(--cobalt); font-weight: 700;">
              ${escapeHtml(title)}
            </h3>
            <p class="sm mut" style="font-size: 13.5px; color: var(--slate); margin: 0 0 16px;">
              ${escapeHtml(categoryName)} &bull; Pick-up only &bull; ${escapeHtml(city)}
            </p>
          </div>
          <a href="donation-details.html?id=${id}" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 13.5px; padding: 10px 16px; text-decoration: none; display: inline-flex; border-radius: 999px;">
            View details &amp; request
          </a>
        </div>
      </div>
    `;
  }

  /**
   * Initializes donations browsing & filtering on donations.html
   */
  async function initDonationsPage() {
    const gallery = document.getElementById('donations-gallery');
    if (!gallery) return;

    const searchInput = document.getElementById('donations-search');
    const sortSelect = document.getElementById('donations-sort');
    const categorySelect = document.getElementById('donations-cat-select');
    const pillsWrap = document.getElementById('category-pills-wrap') || document.querySelector('.category-pills-wrap');
    const resultsCount = document.getElementById('donations-count');
    const emptyState = document.getElementById('donations-empty');

    const urlParams = new URLSearchParams(window.location.search);
    let selectedCategory = urlParams.get('category') || 'all';

    gallery.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 48px 20px; color: var(--text-muted);">
        <div style="font-size: 32px; margin-bottom: 8px;">⏳</div>
        <p>Loading verified community donations...</p>
      </div>
    `;

    // Fetch live donations from MySQL API
    let allDonations = [];
    try {
      if (window.ICD_API && typeof window.ICD_API.fetchDonations === 'function') {
        allDonations = await window.ICD_API.fetchDonations();
      }
    } catch (err) {
      console.warn('Live donations fetch failed, falling back to local store:', err);
    }

    if (!allDonations || allDonations.length === 0) {
      allDonations = (window.ICD_DATA && window.ICD_DATA.donations) ? window.ICD_DATA.donations : [];
    }

    // Synchronize initial category selection
    if (categorySelect && selectedCategory !== 'all') {
      categorySelect.value = selectedCategory;
    }
    if (pillsWrap) {
      pillsWrap.querySelectorAll('.category-pill-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-cat') === selectedCategory);
      });
    }

    function render() {
      const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
      const sort = sortSelect ? sortSelect.value : 'newest';

      let filtered = allDonations.filter(item => {
        const catSlug = item.category_slug || item.categorySlug || (item.category_name ? item.category_name.toLowerCase().replace(/[^a-z0-9]+/g, '-') : '');
        const catId = String(item.category_id || item.categoryId || '');

        const matchesCat = (selectedCategory === 'all' || 
                            catSlug === selectedCategory || 
                            catId === selectedCategory);
        
        const title = (item.title || '').toLowerCase();
        const desc = (item.description || '').toLowerCase();
        const location = (item.pickup_city || item.location || '').toLowerCase();
        const catName = (item.category_name || item.categoryName || '').toLowerCase();
        const donor = (item.donor_name || item.donorName || '').toLowerCase();

        const matchesQuery = !query || 
                             title.includes(query) ||
                             desc.includes(query) ||
                             location.includes(query) ||
                             catName.includes(query) ||
                             donor.includes(query);

        return matchesCat && matchesQuery;
      });

      // Sorting
      if (sort === 'qty-desc') {
        filtered.sort((a, b) => getAvailableQty(b) - getAvailableQty(a));
      } else if (sort === 'title-asc') {
        filtered.sort((a, b) => (a.title || '').localeCompare(b.title || ''));
      } else {
        // default newest (by ID desc)
        filtered.sort((a, b) => Number(b.id) - Number(a.id));
      }

      if (resultsCount) {
        resultsCount.textContent = `${filtered.length} verified item${filtered.length === 1 ? '' : 's'} available`;
      }

      if (filtered.length === 0) {
        gallery.innerHTML = '';
        if (emptyState) emptyState.classList.add('is-active');
      } else {
        if (emptyState) emptyState.classList.remove('is-active');
        gallery.innerHTML = filtered.map(createDonationCardHTML).join('');
      }

      // Re-trigger scroll-reveal observer if available
      if (window.ICD_MAIN && window.ICD_MAIN.refreshScrollReveal) {
        window.ICD_MAIN.refreshScrollReveal();
      }
    }

    if (searchInput) {
      searchInput.addEventListener('input', render);
    }

    if (sortSelect) {
      sortSelect.addEventListener('change', render);
    }

    if (categorySelect) {
      categorySelect.addEventListener('change', function() {
        selectedCategory = this.value;
        if (pillsWrap) {
          pillsWrap.querySelectorAll('.category-pill-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-cat') === selectedCategory);
          });
        }
        render();
      });
    }

    if (pillsWrap) {
      pillsWrap.addEventListener('click', function(e) {
        const btn = e.target.closest('.category-pill-btn');
        if (!btn) return;
        pillsWrap.querySelectorAll('.category-pill-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedCategory = btn.getAttribute('data-cat') || 'all';
        if (categorySelect) categorySelect.value = selectedCategory;
        render();
      });
    }

    // When live categories finish loading into the DOM
    window.addEventListener('icd:categories-loaded', function() {
      if (categorySelect && selectedCategory !== 'all') {
        categorySelect.value = selectedCategory;
      }
      if (pillsWrap) {
        pillsWrap.querySelectorAll('.category-pill-btn').forEach(btn => {
          btn.classList.toggle('active', btn.getAttribute('data-cat') === selectedCategory);
        });
      }
      render();
    });

    render();
  }

  /**
   * Initializes donation details view on donation-details.html
   */
  async function initDonationDetailsPage() {
    const detailsContainer = document.getElementById('donation-details-view');
    if (!detailsContainer) return;

    const urlParams = new URLSearchParams(window.location.search);
    const rawId = urlParams.get('id');
    const id = parseInt(rawId, 10);

    // Validate ID strictly: must be present and a positive integer
    if (!rawId || isNaN(id) || id <= 0) {
      detailsContainer.innerHTML = `
        <div class="donations-empty-state is-active">
          <div class="donations-empty-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--coral, #E0553D)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 12px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          </div>
          <h2 class="donations-empty-title">Invalid or Missing Donation ID</h2>
          <p class="donations-empty-desc">Please choose a valid item listing from our community donations directory.</p>
          <a href="donations.html" class="btn btn-primary">Browse Available Donations</a>
        </div>
      `;
      return;
    }

    detailsContainer.innerHTML = `
      <div style="text-align: center; padding: 60px 20px; color: var(--text-muted);">
        <div style="margin-bottom: 12px;">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary, #1F4FA3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <p>Loading donation details...</p>
      </div>
    `;

    let item = null;
    let fetchError = false;
    try {
      if (window.ICD_API && typeof window.ICD_API.fetchDonationById === 'function') {
        item = await window.ICD_API.fetchDonationById(id);
      }
    } catch (err) {
      console.warn('API fetch by ID failed:', err);
      fetchError = true;
    }

    if (!item) {
      const errorTitle = fetchError ? 'Unable to Load Donation' : 'Donation Not Found';
      const errorDesc = fetchError
        ? 'Could not connect to the donation server. Please check your network and try again.'
        : 'The item you are searching for may have already been claimed, expired, or does not exist.';

      detailsContainer.innerHTML = `
        <div class="donations-empty-state is-active">
          <div class="donations-empty-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--coral, #E0553D)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 12px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          </div>
          <h2 class="donations-empty-title">${errorTitle}</h2>
          <p class="donations-empty-desc">${errorDesc}</p>
          <a href="donations.html" class="btn btn-primary">Browse Available Donations</a>
        </div>
      `;
      return;
    }

    // Set page title dynamically
    document.title = `${item.title} — iCareDonate`;

    const breadcrumbCurrent = document.getElementById('breadcrumb-item-title');
    if (breadcrumbCurrent) breadcrumbCurrent.textContent = item.title;

    const available = getAvailableQty(item);
    const categoryName = item.category_name || item.categoryName || 'General Aid';
    const categorySlug = item.category_slug || item.categorySlug || 'all';
    const donorName = item.donor_name || item.donorName || 'Verified Community Donor';
    const verifiedDate = item.created_at ? new Date(item.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : (item.verifiedDate || 'Recently Verified');
    const location = (item.pickup_barangay ? item.pickup_barangay + ', ' : '') + (item.pickup_city || item.location || 'Metro Manila');
    const pickupType = item.pickup_method === 'delivery' ? 'Direct Delivery' : (item.pickup_method === 'both' ? 'Pick-up or Delivery' : 'Direct Local Pick-up');
    const condition = item.condition_type === 'new' ? 'Brand New & Sealed' : (item.condition_type === 'like_new' ? 'Like New' : (item.condition_type === 'good' ? 'Good Condition' : (item.condition_type === 'fair' ? 'Fair Condition' : 'Clean & Verified')));
    const img = item.image_url || item.image || FALLBACK_IMAGE;
    const instructions = item.instructions || `Pick-up arrangement will be coordinated at the designated public barangay hall in ${escapeHtml(item.pickup_city || 'the locality')} once request is verified.`;

    const hasMultipleImages = Array.isArray(item.images) && item.images.length > 1;
    const galleryThumbsHtml = hasMultipleImages ? `
      <div class="details-gallery-thumbs" style="display: flex; gap: 8px; padding: 12px; background: var(--surface-soft); overflow-x: auto; border-top: 1px solid var(--border);">
        ${item.images.map((gImg, idx) => `
          <button type="button" class="details-thumb-btn ${idx === 0 ? 'active' : ''}" data-src="${escapeHtml(gImg.url)}" style="border: 2px solid ${idx === 0 ? 'var(--cobalt)' : 'var(--border)'}; border-radius: 8px; overflow: hidden; padding: 0; background: none; cursor: pointer; width: 60px; height: 60px; flex-shrink: 0; transition: border-color 0.2s;">
            <img src="${escapeHtml(gImg.url)}" alt="Thumbnail ${idx + 1}" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.onerror=null; this.src='${FALLBACK_IMAGE}';">
          </button>
        `).join('')}
      </div>
    ` : '';

    const isAuthed = typeof ICD_MAIN !== 'undefined' && typeof ICD_MAIN.isUserAuthenticated === 'function' 
      ? ICD_MAIN.isUserAuthenticated() 
      : (Boolean(localStorage.getItem('icd_token')) && Boolean(localStorage.getItem('icd_user')));
    const reqTarget = `user/request-donation.html?donation_id=${item.id}&category=${encodeURIComponent(categorySlug)}`;
    const reqUrl = isAuthed ? reqTarget : `signup.html?redirect=${encodeURIComponent(reqTarget)}&action=request`;

    detailsContainer.innerHTML = `
      <div class="details-layout">
        <div class="details-media">
          <img 
            src="${img}" 
            alt="${escapeHtml(item.title)}" 
            class="details-img-main"
            onerror="this.onerror=null; this.src='${FALLBACK_IMAGE}';"
          >
          ${galleryThumbsHtml}
        </div>

        <div class="details-content">
          <div class="details-tags">
            <span class="badge badge-sky">${escapeHtml(categoryName)}</span>
            <span class="badge badge-success">Admin Verified</span>
            <span class="badge badge-amber">${available} ${escapeHtml(item.unit || 'items')} available</span>
          </div>

          <h1 class="details-title">${escapeHtml(item.title)}</h1>

          <div class="details-meta-row">
            <span><strong>Donor:</strong> ${escapeHtml(donorName)}</span>
            <span><strong>Verified:</strong> ${escapeHtml(verifiedDate)}</span>
          </div>

          <h3 class="details-desc-title">Item Description</h3>
          <p class="details-desc">${escapeHtml(item.description || 'No detailed description provided.')}</p>

          <div class="details-specs">
            <div class="spec-item">
              <span class="spec-label">Location</span>
              <span class="spec-val">${escapeHtml(location)}</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Fulfillment</span>
              <span class="spec-val">${escapeHtml(pickupType)}</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Condition</span>
              <span class="spec-val">${escapeHtml(condition)}</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Cost</span>
              <span class="spec-val" style="color:var(--success);font-weight:700;">100% Free</span>
            </div>
          </div>

          <div style="background:var(--surface-soft); border-left:4px solid var(--cobalt); padding:14px 16px; border-radius:var(--radius-sm); margin-bottom:24px;">
            <strong style="color:var(--cobalt); font-size:14px; display:block; margin-bottom:4px;">Pick-up Instructions:</strong>
            <p style="font-size:13px; color:var(--text-muted); margin:0;">${escapeHtml(instructions)}</p>
          </div>

          <div class="details-actions">
            <a href="${reqUrl}" class="btn btn-donate btn-lg">
              Request this item &rarr;
            </a>
            <a href="donations.html" class="btn btn-outline">
              Back to all items
            </a>
          </div>
        </div>
      </div>
    `;

    if (hasMultipleImages) {
      const thumbBtns = detailsContainer.querySelectorAll('.details-thumb-btn');
      const mainImg = detailsContainer.querySelector('.details-img-main');
      thumbBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          thumbBtns.forEach(b => b.style.borderColor = 'var(--border)');
          this.style.borderColor = 'var(--cobalt)';
          const src = this.getAttribute('data-src');
          if (mainImg && src) mainImg.src = src;
        });
      });
    }
  }

  /**
   * Initializes featured donations preview on index.html
   */
  async function initHomePreviewDonations() {
    const previewGrid = document.getElementById('home-donations-preview');
    if (!previewGrid) return;

    previewGrid.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 36px 20px; color: var(--text-muted);">
        <p>Loading recently approved donations...</p>
      </div>
    `;

    let items = [];
    try {
      if (window.ICD_API && typeof window.ICD_API.fetchDonations === 'function') {
        items = await window.ICD_API.fetchDonations({ limit: 6 });
      }
    } catch (err) {
      console.warn('Live preview fetch failed, using fallback:', err);
    }

    if (!items || items.length === 0) {
      items = (window.ICD_DATA && window.ICD_DATA.donations) ? window.ICD_DATA.donations.slice(0, 6) : [];
    }

    if (items.length === 0) {
      previewGrid.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-muted);">
          <p>No recently approved donations available at this time.</p>
        </div>
      `;
      return;
    }

    previewGrid.innerHTML = items.map(createDonationCardHTML).join('');

    if (window.ICD_MAIN && typeof window.ICD_MAIN.refreshScrollReveal === 'function') {
      window.ICD_MAIN.refreshScrollReveal();
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    initDonationsPage();
    initDonationDetailsPage();
    initHomePreviewDonations();
  });
})();
