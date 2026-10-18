/**
 * iCareDonate — Comprehensive Client-Side Mock Data Store
 * frontend/js/mock-data.js
 * Strictly zero emojis. Fully realistic demonstration records.
 */

(function(window) {
  'use strict';

  // 1. Current Mock Session Users
  const currentUser = {
    id: 12,
    name: 'Maria Santos',
    firstName: 'Maria',
    lastName: 'Santos',
    email: 'maria.santos@example.com',
    phone: '0917-555-0192',
    barangay: 'Barangay Central',
    city: 'Quezon City',
    province: 'Metro Manila',
    role: 'user',
    status: 'active',
    joined: 'Jan 15, 2026',
    initials: 'MS'
  };

  const currentAdmin = {
    id: 1,
    name: 'Eleanor Vance',
    firstName: 'Eleanor',
    lastName: 'Vance',
    email: 'admin.vance@icaredonate.org',
    role: 'superadmin',
    status: 'active',
    initials: 'EV'
  };

  // 2. Categories Master List
  const mockCategories = [
    { id: 1, name: 'Food & Groceries', slug: 'food-groceries', sort_order: 1, count: 24 },
    { id: 2, name: 'School Supplies', slug: 'school-supplies', sort_order: 2, count: 18 },
    { id: 3, name: 'Clothing & Shoes', slug: 'clothing-shoes', sort_order: 3, count: 32 },
    { id: 4, name: 'Medical & First Aid', slug: 'medical-first-aid', sort_order: 4, count: 12 },
    { id: 5, name: 'Household Essentials', slug: 'household-goods', sort_order: 5, count: 15 },
    { id: 6, name: 'Books & Educational', slug: 'books-educational', sort_order: 6, count: 20 },
    { id: 7, name: 'Toys & Children', slug: 'toys-games', sort_order: 7, count: 9 },
    { id: 8, name: 'Electronics & Devices', slug: 'electronics-gadgets', sort_order: 8, count: 7 },
    { id: 9, name: 'Emergency Disaster Relief', slug: 'other-essentials', sort_order: 9, count: 14 }
  ];

  // 3. Mock Donations (Demonstration Records Across All 9 Categories)
  // Clearly identifiable as demo data. Easy to remove or disable.
  let mockDonations = [
    {
      id: 101,
      title: 'Elementary Backpacks & Notebook Bundle',
      category_id: 2,
      category_slug: 'school-supplies',
      category_name: 'School Supplies',
      donor_id: 12,
      donor_name: 'Maria Santos',
      donor_email: 'maria.santos@example.com',
      quantity_total: 10,
      quantity_reserved: 2,
      quantity_claimed: 1,
      available_quantity: 7,
      unit: 'sets',
      condition_type: 'new',
      pickup_method: 'pickup',
      pickup_city: 'Quezon City',
      pickup_barangay: 'Barangay Central',
      description: 'Brand new sturdy backpacks with 10 spiral notebooks, pencils, crayons, and pencil cases. Ideal for elementary school learners.',
      primary_image: 'assets/images/approved_donations/school-supplies.jpg',
      images: [{ file_path: 'assets/images/approved_donations/school-supplies.jpg' }],
      status: 'approved',
      is_demo: true,
      created_at: '2026-10-10 09:30:00'
    },
    {
      id: 102,
      title: 'Family Rice & Canned Goods Care Packs',
      category_id: 1,
      category_slug: 'food-groceries',
      category_name: 'Food & Groceries',
      donor_id: 14,
      donor_name: 'David Tan',
      donor_email: 'david.tan@example.com',
      quantity_total: 15,
      quantity_reserved: 4,
      quantity_claimed: 3,
      available_quantity: 8,
      unit: 'packs',
      condition_type: 'new',
      pickup_method: 'both',
      pickup_city: 'Pasig City',
      pickup_barangay: 'San Antonio',
      description: 'Care packs containing 5kg premium well-milled rice, 6 cans of corned beef, 4 cans of sardines, and vegetable broth bouillon.',
      primary_image: 'assets/images/approved_donations/food-groceries.jpg',
      images: [{ file_path: 'assets/images/approved_donations/food-groceries.jpg' }],
      status: 'approved',
      is_demo: true,
      created_at: '2026-10-11 14:15:00'
    },
    {
      id: 103,
      title: 'Children Rain Jackets & Durable Walking Shoes',
      category_id: 3,
      category_slug: 'clothing-shoes',
      category_name: 'Clothing & Shoes',
      donor_id: 12,
      donor_name: 'Maria Santos',
      donor_email: 'maria.santos@example.com',
      quantity_total: 12,
      quantity_reserved: 2,
      quantity_claimed: 2,
      available_quantity: 8,
      unit: 'pairs',
      condition_type: 'like_new',
      pickup_method: 'pickup',
      pickup_city: 'Marikina City',
      pickup_barangay: 'Barangka',
      description: 'Clean, sanitized water-resistant hooded rain jackets and rubber walking shoes for boys and girls aged 6 to 11.',
      primary_image: 'assets/images/approved_donations/clothing-shoes.avif',
      images: [{ file_path: 'assets/images/approved_donations/clothing-shoes.avif' }],
      status: 'approved',
      is_demo: true,
      created_at: '2026-10-12 11:20:00'
    },
    {
      id: 104,
      title: 'Household First Aid Kits & Antiseptic Care Packs',
      category_id: 4,
      category_slug: 'medical-first-aid',
      category_name: 'Medical & First Aid',
      donor_id: 18,
      donor_name: 'Dr. Roberto Gomez',
      donor_email: 'r.gomez@healthclinic.org',
      quantity_total: 20,
      quantity_reserved: 5,
      quantity_claimed: 3,
      available_quantity: 12,
      unit: 'kits',
      condition_type: 'new',
      pickup_method: 'delivery',
      pickup_city: 'Manila',
      pickup_barangay: 'Ermita',
      description: 'Complete basic hygiene and emergency care kit with 70% alcohol, povidone-iodine, sterile cotton pads, bandages, and digital thermometer.',
      primary_image: 'assets/images/approved_donations/medical-first-aid.jpg',
      images: [{ file_path: 'assets/images/approved_donations/medical-first-aid.jpg' }],
      status: 'approved',
      is_demo: true,
      created_at: '2026-10-12 16:45:00'
    },
    {
      id: 105,
      title: 'Secondary School Science & Math Reference Books',
      category_id: 6,
      category_slug: 'books-educational',
      category_name: 'Books & Educational',
      donor_id: 22,
      donor_name: 'Elena Cruz',
      donor_email: 'elena.cruz@teacher.org',
      quantity_total: 16,
      quantity_reserved: 2,
      quantity_claimed: 4,
      available_quantity: 10,
      unit: 'books',
      condition_type: 'good',
      pickup_method: 'pickup',
      pickup_city: 'Makati City',
      pickup_barangay: 'Poblacion',
      description: 'Grade 7–10 high school science and mathematics textbooks with clear illustrations and problem exercises. Pristine condition.',
      primary_image: 'assets/images/approved_donations/school-supplies-alt.avif',
      images: [{ file_path: 'assets/images/approved_donations/school-supplies-alt.avif' }],
      status: 'approved',
      is_demo: true,
      created_at: '2026-10-09 10:00:00'
    },
    {
      id: 106,
      title: 'Stainless Steel Cookware Set & Electric Kettle',
      category_id: 5,
      category_slug: 'household-goods',
      category_name: 'Household Goods',
      donor_id: 25,
      donor_name: 'Bayanihan Home Helpers',
      donor_email: 'homecare@bayanihan.org',
      quantity_total: 6,
      quantity_reserved: 1,
      quantity_claimed: 1,
      available_quantity: 4,
      unit: 'sets',
      condition_type: 'good',
      pickup_method: 'pickup',
      pickup_city: 'San Juan City',
      pickup_barangay: 'Greenhills',
      description: 'Deep soup pot, frying pan with lid, silicone ladle, and rapid-boil 1.8L electric kettle for family food preparation.',
      primary_image: 'assets/images/approved_donations/other-essentials.jpg',
      images: [{ file_path: 'assets/images/approved_donations/other-essentials.jpg' }],
      status: 'approved',
      is_demo: true,
      created_at: '2026-10-08 14:00:00'
    },
    {
      id: 107,
      title: 'Student Study Tablet with Charger & Stand',
      category_id: 8,
      category_slug: 'electronics-gadgets',
      category_name: 'Electronics & Devices',
      donor_id: 31,
      donor_name: 'Tech Outreach Foundation',
      donor_email: 'outreach@techcare.ph',
      quantity_total: 5,
      quantity_reserved: 2,
      quantity_claimed: 1,
      available_quantity: 2,
      unit: 'units',
      condition_type: 'like_new',
      pickup_method: 'both',
      pickup_city: 'Taguig City',
      pickup_barangay: 'Fort Bonifacio',
      description: '10-inch Android learning tablet with charger, screen protector, and protective folding cover. Fully tested battery and WiFi.',
      primary_image: 'assets/images/approved_donations/electronics-gadgets.avif',
      images: [{ file_path: 'assets/images/approved_donations/electronics-gadgets.avif' }],
      status: 'approved',
      is_demo: true,
      created_at: '2026-10-07 13:10:00'
    },
    {
      id: 108,
      title: 'Wooden Building Blocks & Early Childhood Learning Sets',
      category_id: 7,
      category_slug: 'toys-games',
      category_name: 'Toys & Children',
      donor_id: 34,
      donor_name: 'Joyful Play Foundation',
      donor_email: 'contact@joyfulplay.org',
      quantity_total: 8,
      quantity_reserved: 1,
      quantity_claimed: 2,
      available_quantity: 5,
      unit: 'sets',
      condition_type: 'good',
      pickup_method: 'pickup',
      pickup_city: 'Mandaluyong City',
      pickup_barangay: 'Wack-Wack',
      description: 'Solid wooden stacking blocks, geometric shape sorters, and early alphabet flashcards for preschool and daycare learners.',
      primary_image: 'assets/images/approved_donations/toys-games.avif',
      images: [{ file_path: 'assets/images/approved_donations/toys-games.avif' }],
      status: 'approved',
      is_demo: true,
      created_at: '2026-10-06 11:30:00'
    },
    {
      id: 109,
      title: 'Emergency Solar Flashlights & Heavy-Duty Blankets',
      category_id: 9,
      category_slug: 'other-essentials',
      category_name: 'Emergency Disaster Relief',
      donor_id: 38,
      donor_name: 'Disaster Preparedness Circle',
      donor_email: 'relief@preparedness.org',
      quantity_total: 14,
      quantity_reserved: 3,
      quantity_claimed: 2,
      available_quantity: 9,
      unit: 'bundles',
      condition_type: 'new',
      pickup_method: 'both',
      pickup_city: 'Rizal Province',
      pickup_barangay: 'San Mateo',
      description: 'Hand-crank solar LED flashlights paired with thermal emergency blankets and whistle beacons for flood and typhoon preparation.',
      primary_image: 'assets/images/approved_donations/emergency-disaster-relief.jpg',
      images: [{ file_path: 'assets/images/approved_donations/emergency-disaster-relief.jpg' }],
      status: 'approved',
      is_demo: true,
      created_at: '2026-10-05 10:15:00'
    }
  ];

  // 4. Mock Requests
  let mockRequests = [
    {
      id: 201,
      donation_id: 101,
      donation_title: 'Elementary Backpacks & Notebook Bundle',
      requester_id: 12,
      requester_name: 'Maria Santos',
      quantity_requested: 2,
      unit: 'sets',
      reason: 'Assistance for 2 elementary school children whose family lost school supplies during typhoon flooding.',
      proof_type: 'Barangay Indigency Certificate',
      proof_file: 'indigency_cert_sample.pdf',
      status: 'approved',
      submitted_at: '2026-10-11 10:20:00'
    },
    {
      id: 202,
      donation_id: 102,
      donation_title: 'Family Rice & Canned Goods Care Packs',
      requester_id: 45,
      requester_name: 'Carlos Mendoza',
      quantity_requested: 2,
      unit: 'packs',
      reason: 'Immediate sustenance for single-parent household with elderly grandmother.',
      proof_type: 'Solo Parent ID',
      proof_file: 'solo_parent_id.jpg',
      status: 'under_review',
      submitted_at: '2026-10-12 08:45:00'
    },
    {
      id: 203,
      donation_id: 107,
      donation_title: 'Working Android Tablet for Distance Education',
      requester_id: 52,
      requester_name: 'Leah Bautista',
      quantity_requested: 1,
      unit: 'units',
      reason: 'Senior high school STEM student preparing for college entrance exams without laptop.',
      proof_type: 'Public School Certificate of Enrollment',
      proof_file: 'school_enrollment.pdf',
      status: 'approved',
      submitted_at: '2026-10-08 17:30:00'
    }
  ];

  // 5. Mock Transactions / Handover Logs
  let mockTransactions = [
    {
      id: 301,
      donation_id: 101,
      donation_title: 'Elementary Backpacks & Notebook Bundle',
      request_id: 201,
      donor_id: 12,
      donor_name: 'Maria Santos',
      donor_phone: '0917-555-0192',
      requester_id: 45,
      requester_name: 'Carlos Mendoza',
      requester_phone: '0928-555-4819',
      quantity: 2,
      unit: 'sets',
      pickup_method: 'pickup',
      meeting_venue: 'Barangay Central Multi-Purpose Hall',
      claim_deadline: '2026-10-18 17:00:00',
      status: 'claimed',
      created_at: '2026-10-11 15:00:00'
    },
    {
      id: 302,
      donation_id: 107,
      donation_title: 'Working Android Tablet for Distance Education',
      request_id: 203,
      donor_id: 31,
      donor_name: 'Tech Outreach Foundation',
      donor_phone: '0918-555-7731',
      requester_id: 52,
      requester_name: 'Leah Bautista',
      requester_phone: '0933-555-2244',
      quantity: 1,
      unit: 'units',
      pickup_method: 'both',
      meeting_venue: 'Taguig Community Library Front Desk',
      claim_deadline: '2026-10-16 16:00:00',
      status: 'completed',
      created_at: '2026-10-09 11:30:00'
    }
  ];

  // 6. Mock Users Database (for Admin & Moderation)
  let mockUsers = [
    {
      id: 12,
      firstName: 'Maria',
      lastName: 'Santos',
      email: 'maria.santos@example.com',
      phone: '0917-555-0192',
      city: 'Quezon City',
      barangay: 'Barangay Central',
      role: 'user',
      status: 'active',
      donationsCount: 2,
      requestsCount: 1,
      transactionsCount: 1,
      reportsCount: 0,
      registered: '2026-01-15',
      moderation_note: ''
    },
    {
      id: 14,
      firstName: 'David',
      lastName: 'Tan',
      email: 'david.tan@example.com',
      phone: '0920-555-3810',
      city: 'Pasig City',
      barangay: 'San Antonio',
      role: 'user',
      status: 'active',
      donationsCount: 4,
      requestsCount: 0,
      transactionsCount: 2,
      reportsCount: 0,
      registered: '2026-02-04',
      moderation_note: ''
    },
    {
      id: 25,
      firstName: 'Victor',
      lastName: 'Ramos',
      email: 'victor.ramos@sample.com',
      phone: '0919-555-9011',
      city: 'Caloocan',
      barangay: 'Grace Park',
      role: 'user',
      status: 'suspended',
      donationsCount: 1,
      requestsCount: 3,
      transactionsCount: 0,
      reportsCount: 2,
      registered: '2026-02-28',
      moderation_note: 'Suspended for repeatedly listing items with inaccurate expiry dates. Associated active listings have been archived.'
    },
    {
      id: 31,
      firstName: 'Tech Outreach',
      lastName: 'Foundation',
      email: 'outreach@techcare.ph',
      phone: '0918-555-7731',
      city: 'Taguig City',
      barangay: 'Fort Bonifacio',
      role: 'user',
      status: 'active',
      donationsCount: 5,
      requestsCount: 0,
      transactionsCount: 3,
      reportsCount: 0,
      registered: '2025-11-12',
      moderation_note: ''
    },
    {
      id: 45,
      firstName: 'Carlos',
      lastName: 'Mendoza',
      email: 'carlos.m@example.com',
      phone: '0928-555-4819',
      city: 'Quezon City',
      barangay: 'Batasan Hills',
      role: 'user',
      status: 'active',
      donationsCount: 0,
      requestsCount: 2,
      transactionsCount: 1,
      reportsCount: 0,
      registered: '2026-03-01',
      moderation_note: ''
    },
    {
      id: 99,
      firstName: 'Suspicious',
      lastName: 'Account',
      email: 'spammer99@fake.xyz',
      phone: '0999-999-9999',
      city: 'Unknown',
      barangay: 'None',
      role: 'user',
      status: 'banned',
      donationsCount: 0,
      requestsCount: 0,
      transactionsCount: 0,
      reportsCount: 6,
      registered: '2026-04-01',
      moderation_note: 'Banned permanently for fraudulent verification submissions and policy violation.'
    }
  ];

  // 7. Store API Object
  window.MockStore = {
    getCurrentUser: () => currentUser,
    getCurrentAdmin: () => currentAdmin,
    getCategories: () => [...mockCategories],
    
    getPublicDonations: (q = '', catId = '', cond = '', page = 1, perPage = 12) => {
      let filtered = mockDonations.filter(d => d.status === 'approved');
      if (q) {
        const query = String(q).toLowerCase().trim();
        filtered = filtered.filter(d =>
          d.title.toLowerCase().includes(query) ||
          d.description.toLowerCase().includes(query) ||
          (d.pickup_city && d.pickup_city.toLowerCase().includes(query)) ||
          (d.category_name && d.category_name.toLowerCase().includes(query))
        );
      }
      if (catId && catId !== 'all' && catId !== '') {
        filtered = filtered.filter(d => String(d.category_id) === String(catId));
      }
      if (cond && cond !== 'all' && cond !== '') {
        filtered = filtered.filter(d => {
          const itemCond = String(d.condition_type || '').toLowerCase().replace(/\s+/g, '_');
          return itemCond === String(cond).toLowerCase().replace(/\s+/g, '_');
        });
      }
      const total = filtered.length;
      const p = Math.max(1, Number(page) || 1);
      const pp = Math.max(1, Number(perPage) || 12);
      const start = (p - 1) * pp;
      const paginated = filtered.slice(start, start + pp);
      return {
        data: paginated,
        total: total,
        page: p,
        per_page: pp,
        is_mock: true
      };
    },

    getDonations: (filter = 'all', query = '', catId = 0) => {
      return mockDonations.filter(d => {
        // Status filter
        if (filter === 'all' && d.status === 'archived') return false;
        if (filter !== 'all' && d.status !== filter) return false;
        // Search query
        if (query) {
          const q = query.toLowerCase();
          const matchTitle = d.title.toLowerCase().includes(q);
          const matchDonor = d.donor_name.toLowerCase().includes(q);
          const matchCity = d.pickup_city.toLowerCase().includes(q);
          if (!matchTitle && !matchDonor && !matchCity) return false;
        }
        // Category filter
        if (catId > 0 && d.category_id !== Number(catId)) return false;
        return true;
      });
    },

    getDonationById: (id) => mockDonations.find(d => d.id === Number(id)),

    getDonationCounts: () => {
      const counts = { pending_verification: 0, approved: 0, archived: 0, all: 0 };
      mockDonations.forEach(d => {
        if (d.status === 'pending_verification') counts.pending_verification++;
        if (d.status === 'approved') counts.approved++;
        if (d.status === 'archived') counts.archived++;
        if (d.status !== 'archived') counts.all++;
      });
      return counts;
    },

    archiveDonation: (id, reason) => {
      const donation = mockDonations.find(d => d.id === Number(id));
      if (!donation) return { success: false, message: 'Donation not found' };
      donation.archived_prev_status = donation.status;
      donation.status = 'archived';
      donation.archive_reason = reason || 'Archived by administrative moderation team.';
      donation.archived_at = new Date().toISOString().replace('T', ' ').substring(0, 19);
      donation.archived_by = currentAdmin.name;
      return { success: true, message: `Donation #${id} has been archived successfully.` };
    },

    restoreDonation: (id) => {
      const donation = mockDonations.find(d => d.id === Number(id));
      if (!donation) return { success: false, message: 'Donation not found' };
      const restoredStatus = donation.archived_prev_status || 'pending_verification';
      donation.status = restoredStatus;
      donation.archive_reason = '';
      donation.archived_at = null;
      donation.archived_by = null;
      donation.archived_prev_status = '';
      return { success: true, message: `Donation #${id} has been restored to status '${restoredStatus}'.` };
    },

    getUsers: (filter = 'all', query = '') => {
      return mockUsers.filter(u => {
        if (filter !== 'all' && u.status !== filter) return false;
        if (query) {
          const q = query.toLowerCase();
          const matchName = (u.firstName + ' ' + u.lastName).toLowerCase().includes(q);
          const matchEmail = u.email.toLowerCase().includes(q);
          const matchCity = u.city.toLowerCase().includes(q);
          if (!matchName && !matchEmail && !matchCity) return false;
        }
        return true;
      });
    },

    getUserById: (id) => mockUsers.find(u => u.id === Number(id)),

    getUserCounts: () => {
      const counts = { all: mockUsers.length, active: 0, pending_verification: 0, suspended: 0, banned: 0 };
      mockUsers.forEach(u => {
        if (counts[u.status] !== undefined) counts[u.status]++;
      });
      return counts;
    },

    moderateUser: (userId, action, reason) => {
      const user = mockUsers.find(u => u.id === Number(userId));
      if (!user) return { success: false, message: 'User account not found' };
      
      const userName = user.firstName + ' ' + user.lastName;
      let cascadeCount = 0;

      if (action === 'suspend') {
        user.status = 'suspended';
        user.moderation_note = reason || 'Suspended by administrative team.';
        // Cascade: Archive active donations
        mockDonations.forEach(d => {
          if (d.donor_id === user.id && d.status !== 'archived') {
            d.archived_prev_status = d.status;
            d.status = 'archived';
            d.archive_reason = `Cascade archive: Account of ${userName} was suspended (${reason}).`;
            d.archived_at = new Date().toISOString().replace('T', ' ').substring(0, 19);
            d.archived_by = currentAdmin.name;
            cascadeCount++;
          }
        });
        return {
          success: true,
          message: `Account for ${userName} suspended. ${cascadeCount} associated active donation(s) archived automatically.`
        };
      }

      if (action === 'ban') {
        user.status = 'banned';
        user.moderation_note = reason || 'Permanently banned for terms violation.';
        // Cascade: Archive active donations
        mockDonations.forEach(d => {
          if (d.donor_id === user.id && d.status !== 'archived') {
            d.archived_prev_status = d.status;
            d.status = 'archived';
            d.archive_reason = `Cascade archive: Account of ${userName} was banned permanently (${reason}).`;
            d.archived_at = new Date().toISOString().replace('T', ' ').substring(0, 19);
            d.archived_by = currentAdmin.name;
            cascadeCount++;
          }
        });
        return {
          success: true,
          message: `Account for ${userName} banned permanently. ${cascadeCount} active donation(s) archived.`
        };
      }

      if (action === 'activate') {
        user.status = 'active';
        user.moderation_note = '';
        return {
          success: true,
          message: `Account for ${userName} reactivated. Note: Previously archived donations remain archived and must be reviewed individually.`
        };
      }

      return { success: false, message: 'Unknown moderation action.' };
    },

    getRequests: () => [...mockRequests],
    getTransactions: () => [...mockTransactions]
  };

})(window);
