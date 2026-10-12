/**
 * iCareDonate — Donations Browsing, Filtering & Details Renderer
 * frontend/js/donations.js
 */

(function() {
  'use strict';

  const FALLBACK_IMAGE = 'assets/images/placeholder.svg';

  function createDonationCardHTML(item) {
    return `
      <article class="donation-card reveal-on-scroll" data-id="${item.id}">
        <div class="donation-card__media">
          <img 
            src="${item.image}" 
            alt="${item.title}" 
            class="donation-card__img" 
            loading="lazy" 
            onerror="this.onerror=null; this.src='${FALLBACK_IMAGE}';"
          >
          <span class="donation-card__cat-pill">${item.categoryName}</span>
          <span class="donation-card__qty-pill">${item.quantity} available</span>
        </div>
        <div class="donation-card__body">
          <div>
            <h3 class="donation-card__title">${item.title}</h3>
            <p class="donation-card__meta">
              <span>📍 ${item.location}</span> &bull; 
              <span>${item.pickupType}</span>
            </p>
          </div>
          <a href="donation-details.html?id=${item.id}" class="btn btn-outline btn-block">
            View details &amp; request
          </a>
        </div>
      </article>
    `;
  }

  function initDonationsPage() {
    const gallery = document.getElementById('donations-gallery');
    if (!gallery) return;

    const searchInput = document.getElementById('donations-search');
    const sortSelect = document.getElementById('donations-sort');
    const categorySelect = document.getElementById('donations-cat-select');
    const pillButtons = document.querySelectorAll('.category-pill-btn');
    const resultsCount = document.getElementById('donations-count');
    const emptyState = document.getElementById('donations-empty');

    const urlParams = new URLSearchParams(window.location.search);
    let selectedCategory = urlParams.get('category') || 'all';

    // If query string has category, activate matching pill / select
    if (categorySelect && selectedCategory !== 'all') {
      categorySelect.value = selectedCategory;
    }
    pillButtons.forEach(btn => {
      if (btn.getAttribute('data-cat') === selectedCategory) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });

    function render() {
      const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
      const sort = sortSelect ? sortSelect.value : 'newest';

      let filtered = ICD_DATA.donations.filter(item => {
        const matchesCat = (selectedCategory === 'all' || 
                            item.categorySlug === selectedCategory || 
                            item.categoryId.toString() === selectedCategory);
        
        const matchesQuery = !query || 
                             item.title.toLowerCase().includes(query) ||
                             item.location.toLowerCase().includes(query) ||
                             item.categoryName.toLowerCase().includes(query) ||
                             item.description.toLowerCase().includes(query);

        return matchesCat && matchesQuery;
      });

      // Sorting
      if (sort === 'qty-desc') {
        filtered.sort((a, b) => b.quantity - a.quantity);
      } else if (sort === 'title-asc') {
        filtered.sort((a, b) => a.title.localeCompare(b.title));
      } else {
        // default newest (by ID desc)
        filtered.sort((a, b) => b.id - a.id);
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
        pillButtons.forEach(btn => {
          btn.classList.toggle('active', btn.getAttribute('data-cat') === selectedCategory);
        });
        render();
      });
    }

    pillButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        pillButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        selectedCategory = this.getAttribute('data-cat') || 'all';
        if (categorySelect) categorySelect.value = selectedCategory;
        render();
      });
    });

    render();
  }

  function initDonationDetailsPage() {
    const detailsContainer = document.getElementById('donation-details-view');
    if (!detailsContainer) return;

    const urlParams = new URLSearchParams(window.location.search);
    const id = parseInt(urlParams.get('id'), 10) || 1;
    const item = ICD_DATA.donations.find(d => d.id === id);

    if (!item) {
      detailsContainer.innerHTML = `
        <div class="donations-empty-state">
          <div class="donations-empty-icon">⚠️</div>
          <h2 class="donations-empty-title">Donation Not Found</h2>
          <p class="donations-empty-desc">The item you are searching for may have already been claimed or does not exist.</p>
          <a href="donations.html" class="btn btn-primary">Browse Available Donations</a>
        </div>
      `;
      return;
    }

    // Set page title dynamically
    document.title = `${item.title} — iCareDonate`;

    const breadcrumbCurrent = document.getElementById('breadcrumb-item-title');
    if (breadcrumbCurrent) breadcrumbCurrent.textContent = item.title;

    detailsContainer.innerHTML = `
      <div class="details-layout">
        <div class="details-media">
          <img 
            src="${item.image}" 
            alt="${item.title}" 
            class="details-img-main"
            onerror="this.onerror=null; this.src='${FALLBACK_IMAGE}';"
          >
        </div>

        <div class="details-content">
          <div class="details-tags">
            <span class="badge badge-sky">${item.categoryName}</span>
            <span class="badge badge-success">Admin Verified</span>
            <span class="badge badge-amber">${item.quantity} available</span>
          </div>

          <h1 class="details-title">${item.title}</h1>

          <div class="details-meta-row">
            <span><strong>Donor:</strong> ${item.donorName} (${item.donorRole})</span>
            <span><strong>Verified:</strong> ${item.verifiedDate}</span>
          </div>

          <h3 class="details-desc-title">Item Description</h3>
          <p class="details-desc">${item.description}</p>

          <div class="details-specs">
            <div class="spec-item">
              <span class="spec-label">Location</span>
              <span class="spec-val">${item.location}</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Fulfillment</span>
              <span class="spec-val">${item.pickupType}</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Condition</span>
              <span class="spec-val">Clean &amp; Verified</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Cost</span>
              <span class="spec-val" style="color:var(--success);font-weight:700;">100% Free</span>
            </div>
          </div>

          <div style="background:var(--surface-soft); border-left:4px solid var(--cobalt); padding:14px 16px; border-radius:var(--radius-sm); margin-bottom:24px;">
            <strong style="color:var(--cobalt); font-size:14px; display:block; margin-bottom:4px;">Pick-up Instructions:</strong>
            <p style="font-size:13px; color:var(--text-muted); margin:0;">${item.instructions}</p>
          </div>

          <div class="details-actions">
            <a href="request.html?donation_id=${item.id}&category=${item.categorySlug}" class="btn btn-donate btn-lg">
              Request this item &rarr;
            </a>
            <a href="donations.html" class="btn btn-outline">
              Back to all items
            </a>
          </div>
        </div>
      </div>
    `;
  }

  function initHomePreviewDonations() {
    const previewGrid = document.getElementById('home-donations-preview');
    if (!previewGrid) return;

    // Show first 3 donations
    const previewItems = ICD_DATA.donations.slice(0, 3);
    previewGrid.innerHTML = previewItems.map(createDonationCardHTML).join('');
  }

  document.addEventListener('DOMContentLoaded', function() {
    initDonationsPage();
    initDonationDetailsPage();
    initHomePreviewDonations();
  });
})();
