# iCareDonate — Project Inventory

**Generated Date:** 2026-10-13  
**Current Git Branch:** `fix/deployment-readiness`  
**Base Commit:** `ab4b82b fix(security): final deployment safety fixes`  
**Snapshot Branch:** `archive/pre-phase0-snapshot-20261013`  
**Snapshot Archive:** `scratch/pre_phase0_snapshot.zip`  

---

## 1. Overview & Technology Stack

**iCareDonate** is an item-based community donation platform built for transparency, dignified receiving, and efficient donation matching.

- **Frontend:** HTML5, Vanilla CSS3 (Custom Design System), Vanilla JavaScript (ES6+)
- **Backend:** PHP 8+ (Vanilla procedural + modular OOP helpers, Prepared PDO statements)
- **Database:** MySQL 8.0+ / MariaDB 10.4+ via XAMPP
- **Web Server:** Apache 2.4+ with mod_rewrite enabled
- **Security:** CSRF tokens, Session fixation defense, Bcrypt password hashing (`PASSWORD_BCRYPT`), Role-based access control (RBAC), Private upload shielding via `.htaccess`.

---

## 2. Comprehensive File & Directory Inventory

### Category 1: Frontend Presentation
Clean, framework-free HTML, CSS, and JS presentations located under `frontend/`.

| File / Folder Path | Responsibility | Current State |
| :--- | :--- | :--- |
| `frontend/index.html` | Public landing page (Hero, Categories, Impact, Recent Donations) | Complete markup; utilizes mock data & clean vanilla styling. |
| `frontend/about.html` | About Us & Mission statement | Complete markup; brand story, values, team. |
| `frontend/how-it-works.html` | Step-by-step workflow for donors and requesters | Complete markup; illustrated steps. |
| `frontend/categories.html` | Browse all donation categories | Complete markup; grid layout with active counts. |
| `frontend/donations.html` | Public donation catalog with filters and search | Complete markup; category filters, status chips. |
| `frontend/donation-details.html` | Detailed view of single donation item with claim action | Complete markup; image gallery, donor info, request modal. |
| `frontend/donate.html` | Public entry to offer a donation | Complete markup; redirects or multi-step form. |
| `frontend/request.html` | Public entry to request assistance/items | Complete markup; request requirements & guidelines. |
| `frontend/contact.html` | Public contact inquiry form | Complete markup; form validation & contact channels. |
| `frontend/contact-support.html` | Support request interface | Complete markup; ticket inquiry inputs. |
| `frontend/faqs.html` | Frequently Asked Questions with accordion UI | Complete markup; categorized question list. |
| `frontend/help-center.html` | Help center overview and guides | Complete markup; search bar and knowledge articles. |
| `frontend/careers.html` | Volunteer and career opportunities | Complete markup; listings & mission alignment. |
| `frontend/mission.html` | Platform mission & community impact | Complete markup; values and community metrics. |
| `frontend/terms.html` | Terms and Conditions | Complete markup; platform rules and user liabilities. |
| `frontend/privacy.html` | Privacy Policy | Complete markup; data collection and privacy practices. |
| `frontend/403.html` | Access Forbidden error page | Complete markup; return home navigation. |
| `frontend/404.html` | Page Not Found error page | Complete markup; return home navigation. |
| `frontend/login.html` | Unified login interface (Donor / Requester / Admin) | Complete markup; split brand-card, client-side validation. |
| `frontend/signup.html` | Unified user registration | Complete markup; role selection, client-side validation. |
| `frontend/forgot-password.html` | Password recovery initiation | Complete markup; email form & client feedback. |
| `frontend/reset-password.html` | Password reset completion | Complete markup; token validation & password confirmation. |
| `frontend/admin-login.html` | Dedicated admin login entry | Complete markup; redirects to unified login or standalone form. |
| `frontend/admin/index.html` | Admin Dashboard overview (KPIs, Urgent Queue, Activity Feed) | Complete markup; 23-page admin suite foundation. |
| `frontend/admin/donations.html` | Admin Donation Management & Archival Queue | Complete markup; status filters, archive/restore modal. |
| `frontend/admin/donation-review.html`| Admin Detailed Item Inspection & Decision | Complete markup; approve, reject, archive actions. |
| `frontend/admin/requests.html` | Admin Request verification queue | Complete markup; urgency filter, proof review trigger. |
| `frontend/admin/request-review.html` | Admin Beneficiary Proof Inspection | Complete markup; proof image view, approve/reject. |
| `frontend/admin/transactions.html` | Admin Transaction & Handover tracking | Complete markup; lifecycle stages, QR/code tracking. |
| `frontend/admin/claims.html` | Admin Claim pass & verification overview | Complete markup; claim code verification status. |
| `frontend/admin/interviews.html` | Admin Beneficiary interview scheduling & outcomes | Complete markup; interview outcome logging. |
| `frontend/admin/matches.html` | Admin Donation-Request matching utility | Complete markup; match recommendations. |
| `frontend/admin/users.html` | Admin User directory & moderation control | Complete markup; role filters, status badges. |
| `frontend/admin/user-detail.html` | Admin User deep-dive & suspension/ban cascade | Complete markup; donor/requester activity & moderation controls. |
| `frontend/admin/moderation.html` | Admin Flagged items & content reports | Complete markup; report resolution actions. |
| `frontend/admin/support.html` | Admin Support ticket management | Complete markup; status tracking and replies. |
| `frontend/admin/inquiries.html` | Admin Contact inquiries list | Complete markup; read/unread status, message view. |
| `frontend/admin/categories.html` | Admin Category management | Complete markup; add/edit modal, item count badges. |
| `frontend/admin/faqs.html` | Admin FAQ management | Complete markup; add/edit dialog, category grouping. |
| `frontend/admin/reports.html` | Admin Incident & System reports | Complete markup; filterable data table & export mock. |
| `frontend/admin/statistics.html` | Admin Analytics & Platform Metrics | Complete markup; pure CSS/JS bar charts, metric cards. |
| `frontend/admin/admins.html` | Admin Account management | Complete markup; superadmin access, create/deactivate admin. |
| `frontend/admin/profile.html` | Admin Profile & Security settings | Complete markup; profile update & password change. |
| `frontend/admin/logs.html` | Admin Audit trail & System logs | Complete markup; paginated event stream with filters. |
| `frontend/admin/login.html` | Admin Login portal view | Complete markup; matches brand styling. |
| `frontend/admin/dashboard.html` | Admin Dashboard redirect stub | Redirects to `index.html`. |
| `frontend/user/index.html` | User entry redirect stub | Redirects to `dashboard.html`. |
| `frontend/user/dashboard.html` | User Dashboard (My Stats, Active Donations, Requests) | Complete markup; user KPI cards & active items. |
| `frontend/user/browse-donations.html`| User Donation browsing view | Complete markup; quick search & claim requests. |
| `frontend/user/donations.html` | User's posted donations listing | Complete markup; status chips, edit/archive links. |
| `frontend/user/offer-donation.html` | User Create Donation form | Complete markup; category selector, condition, image upload mock. |
| `frontend/user/edit-donation.html` | User Edit Donation form | Complete markup; pre-populated inputs for editing item. |
| `frontend/user/requests.html` | User's active assistance requests | Complete markup; status tracking, urgency tags. |
| `frontend/user/request-donation.html`| User Create Request form | Complete markup; proof upload mock, urgency selection. |
| `frontend/user/request-details.html` | User Request detail & fulfillment status | Complete markup; match progress & pickup details. |
| `frontend/user/transactions.html` | User Transactions & Pick-up passes | Complete markup; QR/claim code display & status timeline. |
| `frontend/user/history.html` | User Completed transaction history | Complete markup; past donations & received items. |
| `frontend/user/notifications.html` | User Notification feed | Complete markup; read/unread markers, action links. |
| `frontend/user/support.html` | User Support ticket submission | Complete markup; ticket history & new inquiry form. |
| `frontend/user/profile.html` | User Profile details | Complete markup; personal details & verification badge. |
| `frontend/user/settings.html` | User Account & security settings | Complete markup; notification toggles, password update. |
| `frontend/includes/` | Modular HTML partials (`admin-sidebar.html`, `user-sidebar.html`, `brand-sidebar.html`, `public-header.html`, `public-footer.html`, `topbar-user-btn.html`) | Clean HTML reference components for UI consistency. |
| `frontend/css/` | Design system stylesheets (`variables.css`, `base.css`, `layout.css`, `components.css`, `pages/*.css`) | Core brand styles; zero dependencies; CSS custom properties. |
| `frontend/js/` | Vanilla JS interactions (`mock-data.js`, `admin-portal.js`, `user-portal.js`, `donations.js`, `forms.js`, `nav.js`, `data.js`) | Client-side reactive mock store, modals, toasts, nav logic. |

---

### Category 2: Mixed PHP Presentation and Backend Logic
Legacy PHP files that currently output HTML while executing PHP logic and database queries. *Must be preserved until respective frontend pages are fully connected.*

| File Path | Description | Integration Target |
| :--- | :--- | :--- |
| `index.php` | Root landing page rendering dynamic stats & categories | `frontend/index.html` + `api/v1/stats.php` / `api/v1/categories.php` |
| `pages/about.php` | About page with dynamic stats | `frontend/about.html` |
| `pages/browse.php` | Legacy donation browser | `frontend/donations.html` |
| `pages/categories.php` | Legacy category listing | `frontend/categories.html` |
| `pages/donation-details.php` | Legacy donation detail view with request submission | `frontend/donation-details.html` |
| `pages/how-it-works.php` | Legacy how-it-works explanation | `frontend/how-it-works.html` |
| `pages/contact.php` | Contact form with mail/DB insertion | `frontend/contact.html` + `api/v1/contact.php` |
| `pages/faq.php` | Legacy FAQ view | `frontend/faqs.html` + `api/v1/faqs.php` |
| `pages/login.php` | Unified login page (HTML + POST authentication logic) | `frontend/login.html` + `api/v1/auth/login.php` |
| `pages/signup.php` | Registration page (HTML + POST account creation) | `frontend/signup.html` + `api/v1/auth/register.php` |
| `pages/forgot-password.php` | Password reset initiation | `frontend/forgot-password.html` + `api/auth/forgot-password.php` |
| `pages/reset-password.php` | Password reset confirmation | `frontend/reset-password.html` + `api/auth/reset-password.php` |
| `pages/legal/terms.php` | Terms of service | `frontend/terms.html` |
| `pages/legal/privacy.php` | Privacy policy | `frontend/privacy.html` |
| `pages/403.php` | 403 error page | `frontend/403.html` |
| `pages/404.php` | 404 error page | `frontend/404.html` |
| `admin/index.php` | Legacy admin dashboard with SQL counts | `frontend/admin/index.html` + `api/v1/admin/dashboard.php` |
| `admin/donations.php` | Legacy admin donation list & archival operations | `frontend/admin/donations.html` + `api/v1/admin/donations.php` |
| `admin/donation-review.php`| Legacy item review handler | `frontend/admin/donation-review.html` + `api/v1/admin/donations.php` |
| `admin/requests.php` | Legacy request review queue | `frontend/admin/requests.html` + `api/v1/admin/requests.php` |
| `admin/request-review.php` | Legacy request inspection | `frontend/admin/request-review.html` + `api/v1/admin/requests.php` |
| `admin/users.php` | Legacy user management | `frontend/admin/users.html` + `api/v1/admin/users.php` |
| `admin/user-detail.php` | Legacy user profile & moderation action | `frontend/admin/user-detail.html` + `api/v1/admin/users.php` |
| `admin/transactions.php` | Legacy transactions tracker | `frontend/admin/transactions.html` + `api/v1/admin/transactions.php` |
| `admin/claims.php` | Legacy claims management | `frontend/admin/claims.html` |
| `admin/interviews.php` | Legacy interview scheduler | `frontend/admin/interviews.html` + `api/v1/admin/interviews.php` |
| `admin/categories.php` | Legacy category management | `frontend/admin/categories.html` + `api/v1/admin/categories.php` |
| `admin/faqs.php` | Legacy FAQ manager | `frontend/admin/faqs.html` + `api/v1/admin/faqs.php` |
| `admin/reports.php` | Legacy reports and CSV generator | `frontend/admin/reports.html` + `api/v1/admin/reports.php` |
| `admin/statistics.php` | Legacy analytics display | `frontend/admin/statistics.html` + `api/v1/admin/statistics.php` |
| `admin/admins.php` | Legacy admin account management | `frontend/admin/admins.html` + `api/v1/admin/admins.php` |
| `admin/profile.php` | Legacy admin profile edit | `frontend/admin/profile.html` + `api/v1/admin/profile.php` |
| `admin/logs.php` | Legacy audit logs viewer | `frontend/admin/logs.html` + `api/v1/admin/logs.php` |
| `admin/inquiries.php` | Legacy contact submissions viewer | `frontend/admin/inquiries.html` |
| `admin/moderation.php` | Legacy content moderation queue | `frontend/admin/moderation.html` |
| `admin/matches.php` | Legacy donation matching queue | `frontend/admin/matches.html` |
| `user/dashboard.php` | Legacy user dashboard | `frontend/user/dashboard.html` + `api/v1/user/dashboard.php` |
| `user/donations.php` | Legacy user donations list | `frontend/user/donations.html` + `api/v1/user/donations.php` |
| `user/offer-donation.php`| Legacy donation post form + handler | `frontend/user/offer-donation.html` + `api/donations/create.php` |
| `user/edit-donation.php` | Legacy donation edit form + handler | `frontend/user/edit-donation.html` + `api/donations/update.php` |
| `user/requests.php` | Legacy user requests list | `frontend/user/requests.html` + `api/v1/user/requests.php` |
| `user/request-donation.php`| Legacy request submission form + handler | `frontend/user/request-donation.html` |
| `user/request-details.php`| Legacy request detail view | `frontend/user/request-details.html` |
| `user/transactions.php` | Legacy user transactions & claim passes | `frontend/user/transactions.html` + `api/v1/user/claims.php` |
| `user/history.php` | Legacy completed transactions history | `frontend/user/history.html` + `api/v1/user/history.php` |
| `user/notifications.php` | Legacy notifications page | `frontend/user/notifications.html` + `api/v1/notifications.php` |
| `user/support.php` | Legacy user support ticket page | `frontend/user/support.html` + `api/v1/support/tickets.php` |
| `user/profile.php` | Legacy user profile view & edit | `frontend/user/profile.html` + `api/v1/user/profile.php` |
| `user/settings.php` | Legacy user settings & password change | `frontend/user/settings.html` + `api/v1/user/change-password.php` |

---

### Category 3: Backend Handlers and API Endpoints
Stateless or session-authenticated JSON/POST handlers. Ready for frontend AJAX/Fetch consumption.

| Endpoint Path | Method | Responsibility | Authentication / RBAC |
| :--- | :--- | :--- | :--- |
| `api/v1/bootstrap.php` | Shared | Initializes environment, session, JSON headers, error handler | N/A |
| `api/v1/auth/login.php` | POST | Authenticates credentials, sets session, regenerates ID | Public |
| `api/v1/auth/logout.php` | POST | Destroys session, clears cookies | Authenticated |
| `api/v1/auth/register.php`| POST | Validates input, hashes password, creates user record | Public |
| `api/v1/auth/me.php` | GET | Returns current authenticated user state & role | Authenticated |
| `api/v1/categories.php` | GET | Returns list of donation categories with item counts | Public |
| `api/v1/donations.php` | GET/POST | Lists approved donations with filters; creates new donation | Public GET / User POST |
| `api/v1/stats.php` | GET | Returns community impact metrics & active counts | Public |
| `api/v1/contact.php` | POST | Validates contact inquiries and persists to database | Public |
| `api/v1/notifications.php`| GET/POST | Fetches user notifications, marks as read | Authenticated |
| `api/v1/user/dashboard.php`| GET | Returns summary stats and active items for logged-in user | User |
| `api/v1/user/donations.php`| GET | Returns donations owned by current user | User |
| `api/v1/user/requests.php` | GET/POST | Returns user requests; submits new request with proof | User |
| `api/v1/user/claims.php` | GET/POST | Fetches active pickup claims & confirms handovers | User |
| `api/v1/user/history.php` | GET | Returns historical completed transactions | User |
| `api/v1/user/profile.php` | GET/POST | Gets and updates user profile data | User |
| `api/v1/user/change-password.php`| POST | Validates old password and sets new password | User |
| `api/v1/admin/dashboard.php`| GET | Aggregated system metrics, urgent queues, recent logs | Admin / Superadmin |
| `api/v1/admin/donations.php`| GET/POST | Lists all donations; updates status (approve, reject, archive) | Admin / Superadmin |
| `api/v1/admin/requests.php` | GET/POST | Manages beneficiary requests and proof reviews | Admin / Superadmin |
| `api/v1/admin/users.php` | GET/POST | Manages users; handles suspension/ban cascade | Admin / Superadmin |
| `api/v1/admin/categories.php`| GET/POST | Creates, updates, or archives categories | Admin / Superadmin |
| `api/v1/admin/faqs.php` | GET/POST | Creates, updates, or deletes FAQs | Admin / Superadmin |
| `api/v1/admin/interviews.php`| GET/POST| Schedules and records beneficiary interview results | Admin / Superadmin |
| `api/v1/admin/reports.php` | GET | Fetches report records & data feeds | Admin / Superadmin |
| `api/v1/admin/statistics.php`| GET | Extended platform metrics and time-series summaries | Admin / Superadmin |
| `api/v1/admin/support.php` | GET/POST | Manages support tickets and updates ticket status | Admin / Superadmin |
| `api/v1/admin/transactions.php`| GET | System-wide transaction lifecycle monitor | Admin / Superadmin |
| `api/v1/admin/admins.php` | GET/POST | Lists admin accounts; superadmin creates/deactivates | Superadmin |
| `api/v1/admin/profile.php` | GET/POST | Admin personal profile and credentials | Admin / Superadmin |
| `api/v1/admin/logs.php` | GET | Audit log records with pagination and filters | Superadmin |
| `api/v1/support/tickets.php`| GET/POST| User support ticket submission and history | Authenticated |
| `api/auth/forgot-password.php`| POST | Issues password reset token | Public |
| `api/auth/reset-password.php` | POST | Validates token and resets password | Public |
| `api/donations/create.php`| POST | Handles multipart donation submission with images | User |
| `api/donations/update.php`| POST | Updates donation metadata and status | User / Admin |

---

### Category 4: Shared Authentication and Security Infrastructure

| File Path | Responsibility | Security Features |
| :--- | :--- | :--- |
| `includes/auth.php` | Session management, RBAC enforcement, session integrity | `requireLogin()`, `requireRole()`, `getCurrentUser()`, CSRF validation tokens. |
| `includes/functions.php` | Sanitization, system auditing, flash messaging, utilities | `logActivity()`, `sanitize()`, `e()`, secure token generator, flash helper. |
| `config/app.php` | Core platform settings, base URL resolution, environment | Dynamic base path detection, security headers, upload directory configurations. |
| `config/database.php` | PDO Database singleton connection | UTF-8 MB4 charset, `PDO::ATTR_ERRMODE_EXCEPTION`, prepared statements default. |
| `.htaccess` | Root Apache configuration | Rewrites clean URLs, denies directory indexing (`Options -Indexes`), sets X-Frame-Options & MIME protection. |
| `uploads/private/.htaccess`| Private uploads directory shielding | `Require all denied` to prevent direct HTTP access to proof documents. |
| `includes/header.php` | Shared HTML head, viewport meta, global stylesheets | Standardizes metadata and design system inclusion. |
| `includes/footer.php` | Shared public footer and script tags | Standardizes footer links, branding, and global JS. |
| `includes/nav-admin.php` | Admin navigation partial for legacy PHP views | Role-aware menu items (superadmin vs admin). |
| `includes/nav-user.php` | User navigation partial for legacy PHP views | User-specific links (Donations, Requests, Account). |
| `includes/nav-public.php` | Public header navigation for legacy PHP views | Responsive menu with dynamic auth state. |
| `includes/brand-sidebar.php`| Shared branding sidebar for split authentication views | Consistent brand identity rendering. |

---

### Category 5: Database Definitions and Migrations

| File Path | Responsibility | Notes |
| :--- | :--- | :--- |
| `database/schema.sql` | Authoritative database schema | Complete DDL for `users`, `categories`, `donations`, `donation_images`, `requests`, `matches`, `transactions`, `interviews`, `audit_logs`, `faqs`, `support_tickets`, `notifications`. |
| `database/database.sql` | Full database export / baseline snapshot | Contains structure and baseline data. |
| `database/seed_production.sql` | Clean production lookups and essential defaults | Default categories, roles, and configuration parameters. Zero mock/sample data. |
| `database/seed_dev.sql` | Development demo records | Demo users, test donations, sample requests. **Must never run on production.** |
| `database/seed_faqs.sql` | Initial FAQ knowledge records | Default categorized questions and answers. |
| `database/add_archived_status.sql`| Migration script for donation archival | Adds `archived` ENUM value, `archived_prev_status`, `archive_reason`, `archived_at` columns and index. |
| `database/rollback_archived_status.sql`| Rollback script for archival schema | Reverts archival columns and ENUM if ever necessary. |
| `database/add_category_image_and_emergency.sql`| Schema extension | Adds category image paths and emergency request indicators. |
| `database/alter_scope.sql` | Incremental column alterations | Historical patch tracking schema changes. |

---

### Category 6: Static Assets

| Directory / File Path | Responsibility | Notes |
| :--- | :--- | :--- |
| `icaredonate-icon.svg` | Primary brand SVG vector emblem | Clean vector asset for logo wordmark. |
| `assets/images/` & `assets/img/` | Site imagery, category icons, sample items | Preserved for donation category illustrations and wireframe references. |
| `assets/css/` | Legacy stylesheets | Retained until full retirement of legacy PHP templates. |
| `assets/js/` | Legacy JavaScript libraries | Retained for legacy template backwards compatibility. |
| `frontend/css/` | Modern Vanilla CSS design system | Clean, token-driven stylesheets with zero external dependencies. |
| `frontend/js/` | Modern Vanilla JavaScript interactions | Client-side reactive mock store, modals, toasts, nav logic. |

---

### Category 7: Scheduled or Command-Line Utilities

| File Path | Execution Context | Responsibility | Safety & Safeguards |
| :--- | :--- | :--- | :--- |
| `cron/expire-transactions.php` | CLI / OS Cron Job | Sweeps expired pickup deadlines (`claim_deadline < NOW()`), marks status `expired`, restores reserved item quantities back to donation pool, updates linked requests. | Transactional (`beginTransaction`/`commit`), uses `FOR UPDATE` row locking. Safe for repeated runs. CLI recommended. |
| `scripts/create_admin.php` | CLI Only | Securely creates a new administrator or superadministrator account using standard PHP `password_hash()`. | Direct CLI execution only. Protected by `scripts/.htaccess`. |
| `scripts/generate_admin_sql.php`| CLI Only | Generates raw SQL `INSERT` statements with bcrypt hashes for manual database initialization. | CLI utility; output should not be committed to Git. |
| `database/seed_test_accounts.php`| CLI / Dev Setup | Seeds standard testing accounts for local test cycles. | CLI development utility; never executed in production. |

---

### Category 8: Documentation

| File Path | Description |
| :--- | :--- |
| `README.md` | General project documentation and overview. |
| `WIREFRAME_CHECKLIST.md` | Initial frontend requirement checklist based on design wireframes. |
| `docs/PROJECT_INVENTORY.md` | Comprehensive file and architecture inventory (this document). |
| `docs/DEPLOYMENT.md` | Deployment readiness report and configuration requirements. |
| `docs/ARCHITECTURE_PLAN.md` | Architectural strategy and design patterns. |
| `docs/brand_palette (1).html` | Reference visual color palette document. |
| `docs/iCareDonate wireframe.html` | Original interactive wireframe prototype. |
| `docs/content draft (1).pdf` | Platform copy and content draft documentation. |
| `frontend/includes/README.md` | Architecture and usage instructions for frontend partials. |
| `old_docs/` | Archived design and specification documents. |

---

### Category 9: Unused or Potentially Obsolete Files Requiring Verification

| File Path | Proposed Status | Verification Notes |
| :--- | :--- | :--- |
| `backend/.env` & `backend/.env.example` | Redundant / Sample | The project relies on `config/database.php` and `config/app.php`. These `.env` files are not referenced by root PHP files, but will remain untouched until Phase 13. |
| `scratch/` (25+ test scripts) | Scratch / Local test tools | Contains development audit routines (`check_db.php`, `seed_working_data.php`, etc.). Ignored by Git. |
| `frontend/admin-dashboard.html` | Prototype / Deprecated | Early prototype of admin dashboard. Superseded by modular `frontend/admin/index.html`. Kept in place until Phase 13. |
| `frontend/user-dashboard.html` | Prototype / Deprecated | Early prototype of user dashboard. Superseded by modular `frontend/user/dashboard.html`. Kept in place until Phase 13. |
| `frontend/admin/dashboard.html` | Redirect Stub | 331-byte stub redirecting to `index.html`. Functional redirect, safe to preserve. |
| `user/post-donation.php` | Redirect Stub | 77-byte stub redirecting to `offer-donation.php`. Safe to preserve. |
| `pages/org-reports.php` | Stub | 70-byte placeholder redirecting to `reports.php`. Safe to preserve. |
| `tests/comprehensive_archive_restore_test.php` | Test Utility | Test suite verifying database transactional rollback and donation archival. Retain for automated testing. |

---

## 3. Preservation and Integration Roadmap Summary

1. **Frontend Perfection First:** The visual presentation, zero-emoji policy, SVG icon standardization, animations, and mock workflows in `frontend/` will be completely verified before touching the database or modifying backend PHP.
2. **Dual-Database Safety:** Database changes will only occur against an isolated test database copy (`icare_test_deploy` / `icare_integration_test`) before touching `icare_donate`.
3. **No Premature Deletions:** Legacy PHP files in `pages/`, `admin/`, and `user/` remain fully intact and operational until their frontend replacements are completely integrated and verified via acceptance testing.
