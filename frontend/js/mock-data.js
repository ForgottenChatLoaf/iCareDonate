/**
 * iCareDonate — Mock Data Store Purged
 * File: frontend/js/mock-data.js
 *
 * NOTE: In strict accordance with Project Phase 3 (Complete Mock Data Removal),
 * all fabricated donation records, simulated metrics, fake donors, and mock transactions
 * have been permanently purged. All platform activity is now driven exclusively by the
 * relational MySQL database via PHP API actions.
 *
 * Safe empty interfaces are preserved below to avoid breaking any legacy references.
 */

(function(window) {
  'use strict';

  window.MOCK_DATA = {
    currentUser: null,
    currentAdmin: null,
    categories: [],
    browseDonations: [],
    userDonations: [],
    requests: [],
    transactions: [],
    activityLog: []
  };

  window.MockStore = {
    getDonations: function() { return []; },
    getDonationById: function() { return null; },
    getCategories: function() { return []; },
    getUserDonations: function() { return []; },
    getRequests: function() { return []; },
    getTransactions: function() { return []; },
    getActivityLog: function() { return []; },
    addDonation: function() { return false; },
    addRequest: function() { return false; }
  };

  window.iCareMock = {
    donations: [],
    users: [],
    requests: []
  };
})(window);
