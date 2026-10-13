/**
 * iCareDonate — Data Layer & Live API Connector
 * frontend/js/data.js
 */

const ICD_API_BASE = (function() {
  const origin = window.location.origin;
  if (window.location.pathname.includes('/CommunityDono/')) {
    return `${origin}/CommunityDono/api/v1`;
  }
  return '/api/v1';
})();

// UI presentation metadata for categories
const CATEGORY_META = {
  'food-groceries': {
    headerClass: 'category-card__header--food',
    iconSvg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
    accepted: 'Canned goods, rice sacks, powdered milk, instant oatmeal',
    image: 'assets/images/categories/food-groceries.jpg'
  },
  'school-supplies': {
    headerClass: 'category-card__header--education',
    iconSvg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    accepted: 'School bags, notebooks, crayons, calculators',
    image: 'assets/images/categories/school-supplies.jpg'
  },
  'clothing-shoes': {
    headerClass: 'category-card__header--clothing',
    iconSvg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/></svg>',
    accepted: 'School uniforms, jackets, sneakers, baby clothes',
    image: 'assets/images/categories/clothing-shoes.avif'
  },
  'medical-first-aid': {
    headerClass: 'category-card__header--medical',
    iconSvg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="M12 8v8"/><path d="M8 12h8"/></svg>',
    accepted: 'Basic hygiene kits, bandages, OTC medicines, vitamins',
    image: 'assets/images/categories/medical-first-aid.jpg'
  },
  'household-goods': {
    headerClass: 'category-card__header--household',
    iconSvg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
    accepted: 'Cookware, thermal blankets, electric fans, utensils',
    image: 'assets/images/categories/other-essentials.jpg'
  },
  'books-educational': {
    headerClass: 'category-card__header--books',
    iconSvg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
    accepted: 'Textbooks, storybooks, reference materials, dictionaries',
    image: 'assets/images/categories/school-supplies-alt.avif'
  },
  'toys-games': {
    headerClass: 'category-card__header--toys',
    iconSvg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="8" cy="12" r="2"/><path d="M14 12h4"/><path d="M16 10v4"/></svg>',
    accepted: 'Educational puzzles, building blocks, children storybooks',
    image: 'assets/images/categories/toys-games.avif'
  },
  'electronics-gadgets': {
    headerClass: 'category-card__header--tech',
    iconSvg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>',
    accepted: 'Working laptops, tablets, chargers, power banks',
    image: 'assets/images/categories/electronics-gadgets.avif'
  },
  'other-essentials': {
    headerClass: 'category-card__header--essentials',
    iconSvg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
    accepted: 'Emergency supplies, flashlights, batteries, basic needs',
    image: 'assets/images/categories/emergency-disaster-relief.jpg'
  }
};

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function formatCategoryTitle(name) {
  const words = String(name).trim().split(/\s+/);
  if (words.length <= 1) return escapeHtml(name);
  if (words.length === 2) return `${escapeHtml(words[0])}<br>${escapeHtml(words[1])}`;
  if (words.length === 3 && words[1] === '&') {
    return `${escapeHtml(words[0])} &amp;<br>${escapeHtml(words[2])}`;
  }
  return `${escapeHtml(words.slice(0, 2).join(' '))}<br>${escapeHtml(words.slice(2).join(' '))}`;
}

const ICD_DATA = {
  // Populated live from MySQL via api/v1/categories.php
  categories: [],

  donations: [
    {
      id: 1,
      title: 'Elementary School Backpacks & Notebooks Set',
      categoryId: 2,
      categorySlug: 'school-supplies',
      categoryName: 'School Supplies',
      image: 'assets/images/categories/school-supplies.jpg',
      quantity: 25,
      location: 'Pasig City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Elena Ramos',
      donorRole: 'Verified Community Donor',
      verifiedDate: 'October 10, 2026',
      description: 'Brand new sturdy elementary school backpacks equipped with 5 spiral notebooks, a pack of pencils, crayons, and an eraser each. Ideal for incoming grade 1–4 public school learners.',
      instructions: 'Pick-up is arranged at Barangay San Antonio Hall between 9:00 AM and 4:00 PM on weekdays after request approval.'
    },
    {
      id: 2,
      title: 'Assorted Canned Goods and Rice Sacks',
      categoryId: 3,
      categorySlug: 'food-groceries',
      categoryName: 'Food & Groceries',
      image: 'assets/images/categories/food-groceries.jpg',
      quantity: 35,
      location: 'Quezon City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Community Kitchen Group',
      donorRole: 'Verified Local Organization',
      verifiedDate: 'October 8, 2026',
      description: 'Care packages each containing 5kg well-milled rice, 6 cans of sardines, 4 cans of corned beef, and 3 packs of instant noodles. Packaged in sealed eco-bags.',
      instructions: 'Please bring a valid community ID. Pickup coordination via admin dispatch confirmation.'
    },
    {
      id: 3,
      title: 'Working Lenovo Student Laptops with Chargers',
      categoryId: 6,
      categorySlug: 'electronics-gadgets',
      categoryName: 'Electronics & Gadgets',
      image: 'assets/images/categories/school-supplies-alt.avif',
      quantity: 5,
      location: 'Makati City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'TechForGood PH',
      donorRole: 'Corporate CSR Partner',
      verifiedDate: 'October 6, 2026',
      description: 'Refurbished Lenovo ThinkPads with clean Windows 10 installations, webcam, WiFi, and functional chargers. Intended for senior high school or college students in online classes.',
      instructions: 'Proof of student enrollment required upon pickup verification.'
    },
    {
      id: 4,
      title: 'Emergency Family First-Aid & Hygiene Kits',
      categoryId: 4,
      categorySlug: 'medical-hygiene',
      categoryName: 'Healthcare & Hygiene',
      image: 'assets/images/categories/medical-first-aid.jpg',
      quantity: 40,
      location: 'Taguig City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Red Crescent Volunteer Circle',
      donorRole: 'Medical NGO',
      verifiedDate: 'October 5, 2026',
      description: 'Includes alcohol spray, sterile gauze, povidone-iodine, paracetamol strips, oral rehydration salts, bath soap, and toothbrush sets.',
      instructions: 'Available for immediate pickup by registered community healthcare coordinators or affected families.'
    },
    {
      id: 5,
      title: 'Children’s Rain Jackets & Durable Rubber Shoes',
      categoryId: 5,
      categorySlug: 'clothing-shoes',
      categoryName: 'Clothing & Shoes',
      image: 'assets/images/categories/clothing-shoes.avif',
      quantity: 30,
      location: 'Marikina City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Maria Santos',
      donorRole: 'Verified Community Donor',
      verifiedDate: 'October 4, 2026',
      description: 'Clean, gently used and new rain gear for kids aged 5 to 12. Includes water-resistant hoodies, boots, and non-slip athletic shoes.',
      instructions: 'Pick-up at Marikina Sports Center administrative gate.'
    },
    {
      id: 6,
      title: 'Heavy-Duty Thermal Blankets & Disaster Cots',
      categoryId: 1,
      categorySlug: 'emergency',
      categoryName: 'Emergency & Disaster Relief',
      image: 'assets/images/categories/emergency-disaster-relief.jpg',
      quantity: 18,
      location: 'Rizal Province',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Disaster Preparedness Fund',
      donorRole: 'Community Relief Group',
      verifiedDate: 'October 3, 2026',
      description: 'Thick thermal wool blankets and collapsible ground mats suitable for families affected by recent seasonal flooding or localized fires.',
      instructions: 'Dispatched through local disaster risk reduction officers.'
    },
    {
      id: 7,
      title: 'Wooden Building Blocks & Early Learning Puzzles',
      categoryId: 8,
      categorySlug: 'toys-games',
      categoryName: 'Toys & Games',
      image: 'assets/images/categories/toys-games.avif',
      quantity: 15,
      location: 'Mandaluyong City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Joyful Play Foundation',
      donorRole: 'Verified NGO',
      verifiedDate: 'October 2, 2026',
      description: 'Non-toxic wooden blocks, shape sorters, alphabet cards, and children storybooks suitable for day care centers and low-income nursery learners.',
      instructions: 'Priority given to day-care teachers and registered mothers.'
    },
    {
      id: 8,
      title: 'Stainless Steel Cookware Sets & Electric Kettles',
      categoryId: 7,
      categorySlug: 'household-goods',
      categoryName: 'Household Goods',
      image: 'assets/images/categories/other-essentials.jpg',
      quantity: 12,
      location: 'San Juan City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Bayanihan Home Helpers',
      donorRole: 'Community Group',
      verifiedDate: 'October 1, 2026',
      description: 'Durable frying pans, cooking pots, ladles, and energy-efficient 1.8L electric kettles for families establishing new temporary living quarters.',
      instructions: 'Standard pickup with Barangay endorsement.'
    }
  ],

  faqs: [
    {
      q: 'Is iCareDonate 100% free to use?',
      a: 'Yes! iCareDonate is a strictly non-monetary public service platform. No fees are ever charged for donating, requesting, or picking up items.'
    },
    {
      q: 'How does item verification work?',
      a: 'Every donation listing and aid request submitted is reviewed by an administrator before appearing publicly. We verify item condition, donor details, and proof of need to prevent fraud.'
    },
    {
      q: 'How do pick-ups and handovers take place?',
      a: 'All exchanges occur via local, direct pick-ups at designated public locations (such as barangay halls, community centers, or verified meeting points) ensuring complete safety and zero shipping costs.'
    },
    {
      q: 'What proof is required when requesting an item?',
      a: 'Depending on the requested aid, proof may include a valid student ID (for learning devices), an endorsement letter from local leaders, or a photo/document describing the immediate situation.'
    },
    {
      q: 'Can I donate used items?',
      a: 'Yes, provided they are clean, fully functioning, and safe. We do not accept broken equipment, expired goods, or soiled garments.'
    },
    {
      q: 'How long does admin verification take?',
      a: 'Most submissions are verified within 4 to 24 hours. Once verified, you will receive notification and your post will be published on the platform.'
    }
  ],

  stats: {
    approvedDonations: '24+',
    itemsClaimed: '58+',
    communityUsers: '100+',
    volunteers: '58',
    sponsors: '12'
  }
};

/**
 * iCareDonate — API Connector Module
 */
window.ICD_API = {
  baseUrl: ICD_API_BASE,

  /**
   * Fetches active categories from MySQL API
   * @param {boolean} force - Force cache bypass
   * @returns {Promise<Array>}
   */
  async fetchCategories(force = false) {
    if (!force && Array.isArray(ICD_DATA.categories) && ICD_DATA.categories.length > 0) {
      return ICD_DATA.categories;
    }

    try {
      const response = await fetch(`${ICD_API_BASE}/categories.php`, {
        headers: { 'Accept': 'application/json' }
      });
      if (!response.ok) {
        throw new Error(`Server returned HTTP ${response.status} (${response.statusText})`);
      }
      const result = await response.json();
      if (!result || !Array.isArray(result.data)) {
        throw new Error('Invalid JSON format: missing data array');
      }

      const enriched = result.data.map(item => {
        const meta = CATEGORY_META[item.slug] || {
          headerClass: 'category-card__header--education',
          iconSvg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
          accepted: 'Clean, verified essential items in usable condition',
          image: 'assets/images/placeholder.svg'
        };
        return {
          id: Number(item.id),
          name: item.name,
          slug: item.slug,
          description: item.description || '',
          icon: item.icon || 'tag',
          sort_order: Number(item.sort_order || 0),
          is_active: Number(item.is_active !== undefined ? item.is_active : 1),
          headerClass: meta.headerClass,
          iconSvg: meta.iconSvg,
          accepted: meta.accepted,
          image: meta.image
        };
      });

      ICD_DATA.categories = enriched;
      return enriched;
    } catch (err) {
      console.error('Failed to load categories from API:', err);
      throw err;
    }
  },

  /**
   * Fetches platform impact statistics from MySQL
   * @returns {Promise<Object>}
   */
  async fetchStats() {
    try {
      const response = await fetch(`${ICD_API_BASE}/stats.php`, {
        headers: { 'Accept': 'application/json' }
      });
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      const json = await response.json();
      if (json && json.data) {
        ICD_DATA.stats = {
          approvedDonations: `${json.data.donations_count}+`,
          itemsClaimed: `${json.data.claimed_items}+`,
          communityUsers: `${json.data.community_users}+`
        };
        return json.data;
      }
      return { donations_count: 7, claimed_items: 0, community_users: 10 };
    } catch (err) {
      console.warn('Failed to fetch platform stats from API:', err);
      return { donations_count: 7, claimed_items: 0, community_users: 10 };
    }
  },

  /**
   * Fetches donations from MySQL API
   * @param {Object} params - Query parameters (e.g. { limit: 6 })
   * @returns {Promise<Array>}
   */
  async fetchDonations(params = {}) {
    try {
      const url = new URL(`${ICD_API_BASE}/donations.php`, window.location.origin);
      if (params.limit) url.searchParams.set('limit', params.limit);
      if (params.id) url.searchParams.set('id', params.id);

      const response = await fetch(url.toString(), {
        headers: { 'Accept': 'application/json' }
      });
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      const json = await response.json();
      const list = json && Array.isArray(json.data) ? json.data : [];
      if (!params.limit && !params.id && list.length > 0) {
        ICD_DATA.donations = list;
      }
      return list;
    } catch (err) {
      console.error('Failed to load donations from API:', err);
      throw err;
    }
  },

  /**
   * Fetches single donation by ID from MySQL API
   * @param {number|string} id - Donation ID
   * @returns {Promise<Object|null>}
   */
  async fetchDonationById(id) {
    try {
      const response = await fetch(`${ICD_API_BASE}/donations.php?id=${encodeURIComponent(id)}`, {
        headers: { 'Accept': 'application/json' }
      });
      if (!response.ok) {
        if (response.status === 404) return null;
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      const json = await response.json();
      return json && json.data ? json.data : null;
    } catch (err) {
      console.error(`Failed to load donation ${id} from API:`, err);
      throw err;
    }
  }
};

/**
 * Renders categories dynamically into #categories-grid on categories.html
 */
async function renderCategoriesPage() {
  const grid = document.getElementById('categories-grid');
  if (!grid) return;

  grid.innerHTML = `
    <div id="categories-loading" style="grid-column: 1 / -1; text-align: center; padding: 48px 20px; color: var(--text-muted);">
      <div style="font-size: 32px; margin-bottom: 8px;">⏳</div>
      <p style="font-size: 15px; margin: 0;">Loading verified community categories...</p>
    </div>
  `;

  try {
    const categories = await window.ICD_API.fetchCategories();
    if (!categories || categories.length === 0) {
      grid.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 48px 20px;">
          <div style="margin-bottom: 12px;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
          </div>
          <h3 style="margin-bottom: 8px;">No categories available</h3>
          <p style="color: var(--text-muted); margin: 0;">Categories have not been added to the system yet.</p>
        </div>
      `;
      return;
    }

    grid.innerHTML = categories.map(cat => `
      <article class="category-card reveal-on-scroll">
        <div class="category-card__header ${cat.headerClass}">
          <div>
            <div class="category-card__tag">Category</div>
            <h3 class="category-card__title">${formatCategoryTitle(cat.name)}</h3>
          </div>
          <div class="category-card__icon">${cat.iconSvg}</div>
        </div>
        <div class="category-card__body">
          <h4 class="category-card__name">${escapeHtml(cat.name)}</h4>
          <p class="category-card__desc">${escapeHtml(cat.description)}</p>
          <div class="category-card__accepted">
            <strong>Accepted:</strong> ${escapeHtml(cat.accepted)}
          </div>
          <div class="btn-row">
            <a href="donations.html?category=${encodeURIComponent(cat.slug)}" class="btn btn-outline btn-block">
              Browse items
            </a>
            <a href="donate.html?category=${encodeURIComponent(cat.slug)}" class="btn btn-primary btn-block">
              Donate
            </a>
          </div>
        </div>
      </article>
    `).join('');

    if (window.ICD_MAIN && typeof window.ICD_MAIN.refreshScrollReveal === 'function') {
      window.ICD_MAIN.refreshScrollReveal();
    }
  } catch (err) {
    grid.innerHTML = `
      <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 48px 20px;">
        <div style="margin-bottom: 12px;">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--coral, #E0553D)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <h3 style="margin-bottom: 8px;">Unable to load categories</h3>
        <p style="color: var(--text-muted); margin-bottom: 20px;">
          Failed to fetch live categories from the server (${escapeHtml(err.message)}).
        </p>
        <button type="button" class="btn btn-primary" id="retry-categories-btn">Retry</button>
      </div>
    `;
    document.getElementById('retry-categories-btn')?.addEventListener('click', () => {
      renderCategoriesPage();
    });
  }
}

/**
 * Populates category filter pills and select dropdown on donations.html
 */
async function renderDonationsCategoryFilters() {
  const select = document.getElementById('donations-cat-select');
  const pillsWrap = document.getElementById('category-pills-wrap') || document.querySelector('.category-pills-wrap');
  if (!select && !pillsWrap) return;

  const urlParams = new URLSearchParams(window.location.search);
  const activeCategory = urlParams.get('category') || 'all';

  try {
    const categories = await window.ICD_API.fetchCategories();

    if (select) {
      select.innerHTML = '<option value="all">All Categories</option>' +
        categories.map(cat => `
          <option value="${escapeHtml(cat.slug)}" ${activeCategory === cat.slug ? 'selected' : ''}>
            ${escapeHtml(cat.name)}
          </option>
        `).join('');
    }

    if (pillsWrap) {
      pillsWrap.innerHTML = `
        <button type="button" class="category-pill-btn ${activeCategory === 'all' ? 'active' : ''}" data-cat="all">All</button>
      ` + categories.map(cat => `
        <button type="button" class="category-pill-btn ${activeCategory === cat.slug ? 'active' : ''}" data-cat="${escapeHtml(cat.slug)}">
          ${escapeHtml(cat.name)}
        </button>
      `).join('');
    }

    window.dispatchEvent(new CustomEvent('icd:categories-loaded', { detail: categories }));
  } catch (err) {
    console.error('Failed to populate donation category filters:', err);
  }
}

/**
 * Populates real impact statistics from MySQL into .impact-band on index.html
 */
async function renderHomepageStats() {
  const statDonations = document.getElementById('stat-donations');
  const statClaimed = document.getElementById('stat-claimed');
  const statUsers = document.getElementById('stat-users');

  if (!statDonations && !document.querySelector('.impact-band')) return;

  try {
    const stats = await window.ICD_API.fetchStats();
    if (statDonations) statDonations.textContent = `${stats.donations_count}+`;
    if (statClaimed) statClaimed.textContent = `${stats.claimed_items}+`;
    if (statUsers) statUsers.textContent = `${stats.community_users}+`;

    const numEls = document.querySelectorAll('.impact-band .impact-stat-number');
    if (numEls.length >= 3) {
      numEls[0].textContent = `${stats.donations_count}+`;
      numEls[1].textContent = `${stats.claimed_items}+`;
      numEls[2].textContent = `${stats.community_users}+`;
    }
  } catch (err) {
    console.error('Failed to render homepage impact stats:', err);
  }
}

/**
 * Renders homepage featured categories from live categories API
 */
async function renderHomepageCategories() {
  const grid = document.getElementById('home-categories-grid') || document.querySelector('.category-home-grid');
  if (!grid) return;

  try {
    const categories = await window.ICD_API.fetchCategories();
    if (!categories || categories.length === 0) return;

    // Display up to 3 categories on homepage preview
    const previewCats = categories.slice(0, 3);
    grid.innerHTML = previewCats.map(cat => `
      <article class="category-card reveal-on-scroll">
        <div class="category-card__header ${cat.headerClass}">
          <div>
            <div class="category-card__tag">Category</div>
            <h3 class="category-card__title">${formatCategoryTitle(cat.name)}</h3>
          </div>
          <div class="category-card__icon">${cat.iconEmoji}</div>
        </div>
        <div class="category-card__body">
          <h4 class="category-card__name">${escapeHtml(cat.name)}</h4>
          <p class="category-card__desc">${escapeHtml(cat.description)}</p>
          <div class="category-card__accepted">
            <strong>Accepted:</strong> ${escapeHtml(cat.accepted)}
          </div>
          <a href="donations.html?category=${encodeURIComponent(cat.slug)}" class="btn btn-primary btn-block">
            Donate / Request
          </a>
        </div>
      </article>
    `).join('');

    if (window.ICD_MAIN && typeof window.ICD_MAIN.refreshScrollReveal === 'function') {
      window.ICD_MAIN.refreshScrollReveal();
    }
  } catch (err) {
    console.warn('Failed to render homepage categories from API:', err);
  }
}

// Auto-initialize components on page load
document.addEventListener('DOMContentLoaded', function() {
  if (document.getElementById('categories-grid')) {
    renderCategoriesPage();
  }
  if (document.getElementById('home-categories-grid') || document.querySelector('.category-home-grid')) {
    renderHomepageCategories();
  }
  if (document.getElementById('category-pills-wrap') || document.getElementById('donations-cat-select')) {
    renderDonationsCategoryFilters();
  }
  if (document.querySelector('.impact-band')) {
    renderHomepageStats();
  }
});
