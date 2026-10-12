# iCareDonate — PHP to Decoupled Architecture Migration Map

> **Purpose:** Comprehensive inventory and architectural mapping of all 92 legacy PHP files before creating/editing files in `frontend/` and `backend/`.  
> **Safety Rule:** No existing PHP file shall be modified or deleted until its HTML structure, JavaScript interactions, and backend database/business logic are safely ported and tested.

---

## 1. Migration Categories Overview

```text
Existing PHP File
   │
   ├── [1] UI / Page Only ───────────────→ HTML (frontend/pages/ & components/)
   │
   ├── [2] Browser Interaction ──────────→ JavaScript (frontend/js/ & frontend/js/pages/)
   │
   ├── [3] Database Logic ───────────────→ Laravel Models & Eloquent Queries (backend/app/Models/)
   │
   ├── [4] Backend Processing ───────────→ Laravel Controllers, FormRequests & Services (backend/app/...)
   │
   ├── [5] DB / Environment Config ──────→ Laravel .env & config/ (backend/config/, backend/.env)
   │
   └── [6] Obsolete / Duplicate ─────────→ Deprecate / Remove Later (once verified)
```

---

## 2. Global Includes & Configurations

| # | Current File | Category Breakdown | Destination / Migration Action |
|---|---|---|---|
| 1 | `config/database.php` | **[5] DB Config** | Migrates to `backend/.env` (`DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=icare_donate`) and Laravel's `backend/config/database.php`. |
| 2 | `includes/auth.php` | **[4] Backend Processing** & **[2] Browser JS** | Procedural session check functions (`requireLogin()`, `requireAdmin()`, `currentUserId()`) migrate to: <br>• Backend: Laravel Sanctum middleware (`backend/app/Http/Middleware/Authenticate.php`, `EnsureUserIsAdmin.php`)<br>• Frontend: `frontend/js/auth.js` (`Auth.getUser()`, `Auth.isAuthenticated()`, `Auth.requireAuth()`). |
| 3 | `includes/functions.php` | **[4] Backend Processing** (helpers) & **[2] Browser JS** | Core utility helpers (`sanitize()`, `formatDate()`, `timeAgo()`, `generateRefCode()`, `logAudit()`, `sendNotification()`):<br>• Backend: `backend/app/Services/AuditLogService.php`, `backend/app/Services/NotificationService.php`, `backend/app/Helpers/helpers.php`<br>• Frontend: `frontend/js/components.js` & `frontend/js/validation.js` (DOM sanitization and formatting). |
| 4 | `includes/header.php` | **[1] UI / HTML** & **[2] JS** | Shared `<head>` tags, meta viewports, Google Fonts links (`Outfit`, `Plus Jakarta Sans`, `Lilita One`), CSS imports (`css/variables.css`, `layout.css`). Migrates to shared HTML template header snippet or client-side layout component in `frontend/js/components.js`. |
| 5 | `includes/footer.php` | **[1] UI / HTML** & **[2] JS** | Shared footer DOM, copyright, navigation links, and dynamic year script. Migrates to `frontend/pages/` static footers or `components.js` footer injector. |
| 6 | `includes/nav-public.php` | **[1] UI / HTML** & **[2] JS** | Public navigation bar (Logo, links to Browse, How It Works, About, Contact, Login/Signup buttons, mobile hamburger menu). Migrates to `frontend/pages/public/` header component & `frontend/js/components.js`. |
| 7 | `includes/nav-user.php` | **[1] UI / HTML** & **[2] JS** | User portal top navigation bar, avatar dropdown, unread notifications badge, user menu toggles. Migrates to `frontend/pages/user/` header layout and `frontend/js/components.js`. |
| 8 | `includes/nav-admin.php` | **[1] UI / HTML** & **[2] JS** | Admin portal sidebar & top navigation, active tab states, admin status indicators. Migrates to `frontend/pages/admin/` navigation layout and `frontend/js/components.js`. |
| 9 | `cron/expire-transactions.php`| **[4] Backend Processing** | Expired transaction cleanup script. Migrates to Laravel Artisan command: `backend/app/Console/Commands/ExpireTransactions.php` (scheduled via `routes/console.php`). |
| 10 | `database/seed_test_accounts.php` | **[3] Database Logic** & **[4] Processing** | Test user/admin seeder. Migrates to `backend/database/seeders/TestAccountsSeeder.php`. |

---

## 3. Public Pages (`pages/` & Root)

| # | Current File | Line Count / Content | Category Breakdown & Target Mapping |
|---|---|---|---|
| 11 | `index.php` | 18.5 KB | **[1] UI / HTML**: Hero section, stats banner, featured donations grid, category carousel, impact stories, CTAs → `frontend/pages/public/index.html`<br>**[2] JS**: Dynamic category filtering, stats counter animation, API fetch → `frontend/js/pages/home.js`<br>**[3 & 4] Backend**: Featured donations and public stats endpoints → `GET /api/v1/public/home-summary` in `PublicController.php`. |
| 12 | `pages/about.php` | 3.8 KB | **[1] UI / HTML**: Mission, vision, core values, team info → `frontend/pages/public/about.html`<br>**[2] JS**: Mobile nav toggles → `frontend/js/components.js`. |
| 13 | `pages/browse.php` | 10.0 KB | **[1] UI / HTML**: Donation search bar, category filters, item cards grid, pagination, modal preview → `frontend/pages/public/browse.html`<br>**[2] JS**: Search debouncing, category/status query params, dynamic grid rendering → `frontend/js/pages/browse.html`<br>**[3 & 4] Backend**: Full-text search and category filtering → `GET /api/v1/donations` in `DonationController.php`. |
| 14 | `pages/donation-details.php` | 11.2 KB | **[1] UI / HTML**: Image gallery, item condition badge, donor bio, quantity progress bar, Request/Claim CTA button → `frontend/pages/public/donation-details.html`<br>**[2] JS**: Image carousel switcher, request modal launcher → `frontend/js/pages/donation-details.js`<br>**[3 & 4] Backend**: Single item fetch with images and donor info → `GET /api/v1/donations/{id}`. |
| 15 | `pages/categories.php` | 3.6 KB | **[1] UI / HTML**: Grid of donation categories with icons and descriptions → `frontend/pages/public/categories.html`<br>**[2] JS**: Dynamic category list fetch → `frontend/js/pages/categories.js`<br>**[3 & 4] Backend**: Active category list → `GET /api/v1/categories`. |
| 16 | `pages/how-it-works.php` | 7.1 KB | **[1] UI / HTML**: 4-step donor & requester flow diagrams, FAQs, guidelines → `frontend/pages/public/how-it-works.html`<br>**[2] JS**: Interactive tabs (Donor vs Requester) → `frontend/js/components.js`. |
| 17 | `pages/contact.php` | 7.1 KB | **[1] UI / HTML**: Inquiry form (Name, Email, Subject, Category, Message), office location details → `frontend/pages/public/contact.html`<br>**[2] JS**: Form validation & submission handler → `frontend/js/pages/contact.js`<br>**[3 & 4] Backend**: Inquiry storing & notification → `POST /api/v1/contact-inquiries` in `ContactController.php`. |
| 18 | `pages/faq.php` | 3.1 KB | **[1] UI / HTML**: Categorized accordion FAQs → `frontend/pages/public/faq.html`<br>**[2] JS**: Accordion open/collapse animations → `frontend/js/components.js`<br>**[3 & 4] Backend**: FAQ list endpoint → `GET /api/v1/faqs`. |
| 19 | `pages/careers.php` | 2.7 KB | **[1] UI / HTML**: Volunteer and internship opportunities listing → `frontend/pages/public/careers.html`. |
| 20 | `pages/reports.php` | 6.2 KB | **[1] UI / HTML**: Public community impact numbers, charts, downloadable reports → `frontend/pages/public/reports.html`<br>**[2] JS**: Impact metrics counter, PDF download trigger → `frontend/js/pages/reports.js`<br>**[3 & 4] Backend**: Aggregated impact metrics endpoint → `GET /api/v1/public/impact-stats`. |
| 21 | `pages/org-reports.php` | 70 B | **[6] Obsolete / Duplicate**: Alias `require_once __DIR__ . '/reports.php'`. → Remove / redirect to `frontend/pages/public/reports.html`. |
| 22 | `pages/legal/privacy.php` | 2.3 KB | **[1] UI / HTML**: Privacy Policy static legal document → `frontend/pages/public/privacy.html`. |
| 23 | `pages/legal/terms.php` | 2.5 KB | **[1] UI / HTML**: Terms of Service static legal document → `frontend/pages/public/terms.html`. |
| 24 | `pages/403.php` | 948 B | **[1] UI / HTML**: Access Forbidden error state page with Back to Home button → `frontend/pages/public/403.html`. |
| 25 | `pages/404.php` | 983 B | **[1] UI / HTML**: Not Found error state page with Search button → `frontend/pages/public/404.html`. |

---

## 4. Authentication Pages (`pages/`)

| # | Current File | Line Count / Content | Category Breakdown & Target Mapping |
|---|---|---|---|
| 26 | `pages/login.php` | 15.6 KB | **[1] UI / HTML**: Unified sign-in form (Email, Password, Remember Me, Forgot Password link) → `frontend/pages/auth/login.html`<br>**[2] JS**: Form validation, async submit to API, storing Sanctum token/session, redirect based on role (`user` vs `admin`) → `frontend/js/pages/login.js`<br>**[3 & 4] Backend**: Credentials validation, rate-limiting, token issuance → `POST /api/v1/auth/login` in `AuthController.php`. |
| 27 | `pages/signup.php` | 5.8 KB | **[1] UI / HTML**: Registration form (First Name, Last Name, Email, Password, Location, Terms checkbox) → `frontend/pages/auth/signup.html`<br>**[2] JS**: Real-time password strength check, form submit handler → `frontend/js/pages/signup.js`<br>**[3 & 4] Backend**: User creation, email verification token dispatch → `POST /api/v1/auth/register` in `AuthController.php`. |
| 28 | `pages/forgot-password.php` | 2.2 KB | **[1] UI / HTML**: Email entry form for password reset link → `frontend/pages/auth/forgot-password.html`<br>**[2] JS**: Async submission & confirmation banner → `frontend/js/pages/forgot-password.js`<br>**[3 & 4] Backend**: Reset token generation & email dispatch → `POST /api/v1/auth/forgot-password`. |
| 29 | `pages/reset-password.php` | 3.0 KB | **[1] UI / HTML**: New password and confirm password form → `frontend/pages/auth/reset-password.html`<br>**[2] JS**: Token verification from query params, client validation → `frontend/js/pages/reset-password.js`<br>**[3 & 4] Backend**: Reset token validation & password hash update → `POST /api/v1/auth/reset-password`. |
| 30 | `pages/accept-invitation.php` | 8.9 KB | **[1] UI / HTML**: Admin invitation activation form (Name, new password setup) → `frontend/pages/auth/accept-invitation.html`<br>**[2] JS**: Invitation token verification and activation submit → `frontend/js/pages/accept-invitation.js`<br>**[3 & 4] Backend**: Invitation token validation, admin account activation → `POST /api/v1/admin/invitations/accept`. |

---

## 5. User Portal Pages (`user/`)

| # | Current File | Line Count / Content | Category Breakdown & Target Mapping |
|---|---|---|---|
| 31 | `user/index.php` | 92 B | **[6] Obsolete / Duplicate**: Header redirect to `dashboard.php`. In static frontend, routing or `index.html` redirects to `dashboard.html`. |
| 32 | `user/dashboard.php` | 15.2 KB | **[1] UI / HTML**: User summary cards (Donated items, Active requests, Completed matches), recent activity timeline, urgent alerts → `frontend/pages/user/dashboard.html`<br>**[2] JS**: Fetch user metrics and recent activities → `frontend/js/pages/user-dashboard.js`<br>**[3 & 4] Backend**: Dashboard aggregated metrics → `GET /api/v1/user/dashboard` in `UserDashboardController.php`. |
| 33 | `user/offer-donation.php` | 20.2 KB | **[1] UI / HTML**: Multi-step donation submission form (Category, Title, Condition, Quantity, Pickup/Drop-off address, Schedule, Multi-image upload dropzone) → `frontend/pages/user/post-donation.html`<br>**[2] JS**: Step wizard navigation, client image preview & compression, form submit → `frontend/js/pages/post-donation.js`<br>**[3 & 4] Backend**: FormRequest validation, image storage, draft/pending status record → `POST /api/v1/donations` in `DonationController.php`. |
| 34 | `user/post-donation.php` | 77 B | **[6] Obsolete / Duplicate**: Alias requiring `offer-donation.php`. Unified into `frontend/pages/user/post-donation.html`. |
| 35 | `user/donations.php` | 6.0 KB | **[1] UI / HTML**: Table/cards list of user's submitted donations with status badges (`pending_review`, `approved`, `reserved`, `claimed`), action buttons → `frontend/pages/user/my-donations.html`<br>**[2] JS**: Tab switching by status, pagination, cancel/delete modal → `frontend/js/pages/my-donations.js`<br>**[3 & 4] Backend**: Authenticated user's donations list → `GET /api/v1/user/donations`. |
| 36 | `user/edit-donation.php` | 5.8 KB | **[1] UI / HTML**: Edit donation details form (if pending or rejected for resubmission) → `frontend/pages/user/edit-donation.html`<br>**[2] JS**: Pre-populate fields, image manager, submit changes → `frontend/js/pages/edit-donation.js`<br>**[3 & 4] Backend**: Donation update & status state-machine → `PUT /api/v1/donations/{id}`. |
| 37 | `user/browse-donations.php` | 11.3 KB | **[1] UI / HTML**: Authenticated view of available donations with one-click "Request This Item" button → `frontend/pages/user/browse-donations.html`<br>**[2] JS**: Filter and request modal launcher → `frontend/js/pages/user-browse.js`<br>**[3 & 4] Backend**: Shared endpoint with public browse with requester eligibility checks → `GET /api/v1/donations?available=1`. |
| 38 | `user/request-donation.php` | 7.9 KB | **[1] UI / HTML**: Donation request form (Item needed, reason/need statement, quantity, urgency level, proof document upload) → `frontend/pages/user/request-donation.html`<br>**[2] JS**: Proof upload preview, validation, submit → `frontend/js/pages/request-donation.js`<br>**[3 & 4] Backend**: Proof storage in private storage, create request in `donation_requests` → `POST /api/v1/requests` in `RequestController.php`. |
| 39 | `user/requests.php` | 5.4 KB | **[1] UI / HTML**: Table of user's donation requests, status badges (`under_review`, `approved`, `matched`, `completed`), action buttons → `frontend/pages/user/my-requests.html`<br>**[2] JS**: Status filtering, cancel request modal → `frontend/js/pages/my-requests.js`<br>**[3 & 4] Backend**: User requests list → `GET /api/v1/user/requests`. |
| 40 | `user/request-details.php` | 6.7 KB | **[1] UI / HTML**: Detailed view of request status, verification feedback, match details, pickup instructions → `frontend/pages/user/request-details.html`<br>**[2] JS**: Dynamic details fetch and claim status update → `frontend/js/pages/request-details.js`<br>**[3 & 4] Backend**: Single request view endpoint → `GET /api/v1/requests/{id}`. |
| 41 | `user/transactions.php` | 8.4 KB | **[1] UI / HTML**: Active claims, pickup codes/QR, donor contact info (masked), status stepper (`reserved` → `scheduled` → `claimed`) → `frontend/pages/user/claims.html`<br>**[2] JS**: Confirm pickup button handler, countdown timer for pickup window → `frontend/js/pages/claims.js`<br>**[3 & 4] Backend**: Claims & transactions fetch/update → `GET /api/v1/user/claims`, `POST /api/v1/claims/{id}/complete`. |
| 42 | `user/history.php` | 9.2 KB | **[1] UI / HTML**: Chronological timeline of all completed donations and fulfilled requests, impact receipts → `frontend/pages/user/history.html`<br>**[2] JS**: Filter by year/type, printable receipt popup → `frontend/js/pages/history.js`<br>**[3 & 4] Backend**: User transaction history endpoint → `GET /api/v1/user/history`. |
| 43 | `user/notifications.php` | 3.5 KB | **[1] UI / HTML**: Notification inbox, unread highlights, Mark All As Read button → `frontend/pages/user/notifications.html`<br>**[2] JS**: Real-time polling or mark-as-read click handlers → `frontend/js/pages/notifications.js`<br>**[3 & 4] Backend**: Notifications CRUD → `GET /api/v1/notifications`, `PATCH /api/v1/notifications/{id}/read`. |
| 44 | `user/profile.php` | 4.0 KB | **[1] UI / HTML**: User profile info (Name, Avatar, Phone, Location Barangay/City, Member Since) → `frontend/pages/user/profile.html`<br>**[2] JS**: Avatar upload preview, profile form submit → `frontend/js/pages/profile.js`<br>**[3 & 4] Backend**: Profile update → `GET /api/v1/user/profile`, `PUT /api/v1/user/profile`. |
| 45 | `user/settings.php` | 16.9 KB | **[1] UI / HTML**: Account settings tabs (Security/Password, Region, Privacy preferences, Active login sessions) → `frontend/pages/user/settings.html`<br>**[2] JS**: Password change handler, terminate session button, region dropdowns → `frontend/js/pages/settings.js`<br>**[3 & 4] Backend**: Password reset, session revocation, privacy update → `PUT /api/v1/user/settings/...`. |
| 46 | `user/support.php` | 9.5 KB | **[1] UI / HTML**: Support ticket submission form, list of open/closed inquiries, message thread → `frontend/pages/user/support.html`<br>**[2] JS**: Ticket submit, reply thread fetch → `frontend/js/pages/support.js`<br>**[3 & 4] Backend**: Ticket creation & replies → `POST /api/v1/support/tickets`, `GET /api/v1/support/tickets`. |

---

## 6. Admin Portal Pages (`admin/`)

| # | Current File | Line Count / Content | Category Breakdown & Target Mapping |
|---|---|---|---|
| 47 | `admin/login.php` | 140 B | **[6] Obsolete / Duplicate**: Header redirect to unified login page. Admin logs in via `frontend/pages/auth/login.html`. |
| 48 | `admin/index.php` | 29.7 KB | **[1] UI / HTML**: Master dashboard with KPI metric cards, pending items badge, activity stream, quick actions → `frontend/pages/admin/dashboard.html`<br>**[2] JS**: KPI data polling, chart rendering, quick action modal triggers → `frontend/js/pages/admin-dashboard.js`<br>**[3 & 4] Backend**: Aggregated admin metrics → `GET /api/v1/admin/dashboard-stats` in `AdminDashboardController.php`. |
| 49 | `admin/donations.php` | 6.8 KB | **[1] UI / HTML**: Full donations table with status filters, search, quick approve/reject actions → `frontend/pages/admin/donations.html`<br>**[2] JS**: Table sorting, pagination, review modal trigger → `frontend/js/pages/admin-donations.js`<br>**[3 & 4] Backend**: Paginated donation list with filters → `GET /api/v1/admin/donations`. |
| 50 | `admin/donation-review.php` | 9.8 KB | **[1] UI / HTML**: Detailed item audit view: donor profile, high-res photos, condition assessment, approve/reject form with rejection reason dropdown → `frontend/pages/admin/donation-review.html`<br>**[2] JS**: Approve/Reject API handler, confirmation dialog → `frontend/js/pages/admin-donation-review.js`<br>**[3 & 4] Backend**: Status change + audit log + notification trigger → `POST /api/v1/admin/donations/{id}/decision` in `AdminDonationController.php`. |
| 51 | `admin/requests.php` | 26.5 KB | **[1] UI / HTML**: Donation requests queue, urgency indicators, verification status, proof attachment preview → `frontend/pages/admin/requests.html`<br>**[2] JS**: Filter by urgency/status, launch review drawer → `frontend/js/pages/admin-requests.js`<br>**[3 & 4] Backend**: Paginated requests list → `GET /api/v1/admin/requests`. |
| 52 | `admin/request-review.php` | 9.3 KB | **[1] UI / HTML**: Detailed requester verification modal/page: inspect proof doc, requester history, accept/reject buttons with reason input → `frontend/pages/admin/request-review.html`<br>**[2] JS**: Decision submission, document zoom/preview → `frontend/js/pages/admin-request-review.js`<br>**[3 & 4] Backend**: Request decision endpoint → `POST /api/v1/admin/requests/{id}/decision`. |
| 53 | `admin/matches.php` | 3.2 KB | **[1] UI / HTML**: Intelligent donation-to-request pairing screen, compatibility scores, manual pairing tools → `frontend/pages/admin/matches.html`<br>**[2] JS**: Match creation modal, dynamic match suggestion preview → `frontend/js/pages/admin-matches.js`<br>**[3 & 4] Backend**: Matchmaking engine, transaction initiation → `POST /api/v1/admin/matches/create` in `MatchController.php`. |
| 54 | `admin/claims.php` | 9.7 KB | **[1] UI / HTML**: Claims and pickup monitoring dashboard, overdue pickups alert, completion status → `frontend/pages/admin/claims.html`<br>**[2] JS**: Status filtering, override/cancel claim dialog → `frontend/js/pages/admin-claims.js`<br>**[3 & 4] Backend**: Claims monitoring query → `GET /api/v1/admin/claims`. |
| 55 | `admin/transactions.php` | 11.9 KB | **[1] UI / HTML**: Completed and expired transaction ledger, proof of handover records → `frontend/pages/admin/transactions.html`<br>**[2] JS**: Date range filtering, export to CSV/PDF → `frontend/js/pages/admin-transactions.js`<br>**[3 & 4] Backend**: Transactions audit ledger → `GET /api/v1/admin/transactions`. |
| 56 | `admin/users.php` | 7.8 KB | **[1] UI / HTML**: Users management directory (User ID, Name, Role, Status, Total Donations, Actions) → `frontend/pages/admin/users.html`<br>**[2] JS**: Search by name/email, suspend/reactivate modal launcher → `frontend/js/pages/admin-users.js`<br>**[3 & 4] Backend**: User list and status mutation → `GET /api/v1/admin/users`, `PATCH /api/v1/admin/users/{id}/status`. |
| 57 | `admin/user-detail.php` | 21.0 KB | **[1] UI / HTML**: Comprehensive user dossier: donations history, requests history, reports against user, active sessions, admin notes → `frontend/pages/admin/user-detail.html`<br>**[2] JS**: Tab switching, add admin note handler, suspend action → `frontend/js/pages/admin-user-detail.js`<br>**[3 & 4] Backend**: User deep details with relations → `GET /api/v1/admin/users/{id}`. |
| 58 | `admin/moderation.php` | 6.4 KB | **[1] UI / HTML**: Flagged items queue, problem reports from users, dismiss or take punitive action → `frontend/pages/admin/moderation.html`<br>**[2] JS**: Resolve report dialog, action execution → `frontend/js/pages/admin-moderation.js`<br>**[3 & 4] Backend**: Problem reports resolution → `GET /api/v1/admin/reports`, `POST /api/v1/admin/reports/{id}/resolve`. |
| 59 | `admin/interviews.php` | 17.2 KB | **[1] UI / HTML**: Requester verification interviews schedule, outcome recording form (verified, rejected, follow-up needed) → `frontend/pages/admin/interviews.html`<br>**[2] JS**: Date/time appointment picker, outcome submit → `frontend/js/pages/admin-interviews.js`<br>**[3 & 4] Backend**: Verification schedule & notes → `GET/POST /api/v1/admin/verifications`. |
| 60 | `admin/categories.php` | 4.5 KB | **[1] UI / HTML**: Category management table, Add Category modal, edit name/icon/order, toggle active → `frontend/pages/admin/categories.html`<br>**[2] JS**: Add/Edit category modal logic, drag/reorder categories → `frontend/js/pages/admin-categories.js`<br>**[3 & 4] Backend**: Category CRUD → `backend/app/Http/Controllers/Api/AdminCategoryController.php`. |
| 61 | `admin/faqs.php` | 11.6 KB | **[1] UI / HTML**: FAQ CRUD manager (Question, Answer, Category, Display Order) → `frontend/pages/admin/faqs.html`<br>**[2] JS**: Modal editor and async save → `frontend/js/pages/admin-faqs.js`<br>**[3 & 4] Backend**: FAQ management endpoints → `GET/POST/PUT/DELETE /api/v1/admin/faqs`. |
| 62 | `admin/inquiries.php` | 9.4 KB | **[1] UI / HTML**: Contact Us messages inbox, reply composer, status tag (`new`, `replied`, `closed`) → `frontend/pages/admin/inquiries.html`<br>**[2] JS**: Message preview drawer, reply mailer form → `frontend/js/pages/admin-inquiries.js`<br>**[3 & 4] Backend**: Contact inquiries list and reply mailer → `GET /api/v1/admin/inquiries`, `POST /api/v1/admin/inquiries/{id}/reply`. |
| 63 | `admin/support.php` | 7.0 KB | **[1] UI / HTML**: Admin support desk: user tickets queue, priority badges, internal notes → `frontend/pages/admin/support.html`<br>**[2] JS**: Ticket thread view, reply submit → `frontend/js/pages/admin-support.js`<br>**[3 & 4] Backend**: Admin ticket management → `GET /api/v1/admin/support/tickets`. |
| 64 | `admin/admins.php` | 17.0 KB | **[1] UI / HTML**: Admin team directory, Invite New Admin modal (Email, Role: Admin vs Super Admin), revoke invite, deactivate admin → `frontend/pages/admin/admins.html`<br>**[2] JS**: Send invitation form handler, permissions modal → `frontend/js/pages/admin-management.js`<br>**[3 & 4] Backend**: Admin invitation generator, token emailer, status toggle → `POST /api/v1/admin/invitations` in `AdminInvitationController.php`. |
| 65 | `admin/profile.php` | 10.3 KB | **[1] UI / HTML**: Admin personal profile & security settings (Password change, 2FA setup) → `frontend/pages/admin/profile.html`<br>**[2] JS**: Profile form submit, password change → `frontend/js/pages/admin-profile.js`<br>**[3 & 4] Backend**: Admin profile update → `PUT /api/v1/admin/profile`. |
| 66 | `admin/statistics.php` | 11.9 KB | **[1] UI / HTML**: Analytical charts (Donations over time, fulfillment rate, regional breakdown, top categories) → `frontend/pages/admin/statistics.html`<br>**[2] JS**: Chart.js / SVG data visualization rendering, time-range filter → `frontend/js/pages/admin-statistics.js`<br>**[3 & 4] Backend**: Aggregated statistical metrics → `GET /api/v1/admin/stats/analytics`. |
| 67 | `admin/reports.php` | 24.6 KB | **[1] UI / HTML**: Report generation tool (Date range, report type, filter by category/location, Export CSV / PDF) → `frontend/pages/admin/reports.html`<br>**[2] JS**: Form submit, export trigger → `frontend/js/pages/admin-reports.js`<br>**[3 & 4] Backend**: CSV / PDF export generation service → `backend/app/Services/ReportExportService.php`. |
| 68 | `admin/logs.php` | 3.3 KB | **[1] UI / HTML**: System audit trail log table (Timestamp, Admin User, Action, Target Entity, IP address, Details diff) → `frontend/pages/admin/audit-logs.html`<br>**[2] JS**: Search by keyword, filter by admin user or date → `frontend/js/pages/admin-audit-logs.js`<br>**[3 & 4] Backend**: Audit log query endpoint → `GET /api/v1/admin/audit-logs` in `AuditLogController.php`. |

---

## 7. Procedural API Handlers (`api/`)

All existing scripts in `api/` are raw procedural PHP endpoints connecting directly via PDO. They map directly into **Laravel API Controllers and FormRequests** (`backend/app/Http/Controllers/Api/...`), while their client call sites map to centralized functions in `frontend/js/api.js`.

| # | Current File | Target Laravel Controller & Route | Primary Responsibilities |
|---|---|---|---|
| 69 | `api/auth/login.php` | `POST /api/v1/auth/login` → `AuthController@login` | Validate email & password, verify user status (`active`), issue Sanctum personal access token or session, update `last_login_at`. |
| 70 | `api/auth/register.php` | `POST /api/v1/auth/register` → `AuthController@register` | Validate payload (`RegisterRequest`), hash password with `bcrypt`, create user, create verification token, dispatch notification. |
| 71 | `api/auth/logout.php` | `POST /api/v1/auth/logout` → `AuthController@logout` | Revoke current user Sanctum access token, destroy session, log audit event. |
| 72 | `api/auth/forgot-password.php` | `POST /api/v1/auth/forgot-password` → `PasswordResetController@sendResetLink` | Check email, generate cryptographic token in `password_resets`, send email with reset link. |
| 73 | `api/auth/reset-password.php` | `POST /api/v1/auth/reset-password` → `PasswordResetController@reset` | Verify reset token & expiry, update password hash, purge token, invalidate existing sessions. |
| 74 | `api/donations/create.php` | `POST /api/v1/donations` → `DonationController@store` | Validate donation fields (`StoreDonationRequest`), securely store images in storage disk, insert `donations` and `donation_images`, set status to `pending_review`. |
| 75 | `api/donations/update.php` | `PUT /api/v1/donations/{id}` → `DonationController@update` | Check ownership & eligibility (must be `draft` or `rejected`), update fields, re-submit to `pending_review`. |
| 76 | `api/requests/create.php` | `POST /api/v1/requests` → `RequestController@store` | Validate need description, urgency, quantity, upload proof document to secure private disk, set status to `under_review`. |
| 77 | `api/admin/approve-donation.php` | `POST /api/v1/admin/donations/{id}/approve` → `AdminDonationController@approve` | Check admin permission, update donation status to `approved` (and `available`), log audit entry, dispatch notification to donor. |
| 78 | `api/admin/reject-donation.php` | `POST /api/v1/admin/donations/{id}/reject` → `AdminDonationController@reject` | Store rejection reason note, set donation status to `rejected`, notify donor with explanation, log audit entry. |
| 79 | `api/admin/delete-donation.php` | `DELETE /api/v1/admin/donations/{id}` → `AdminDonationController@destroy` | Soft-delete donation record, audit action. |
| 80 | `api/admin/decide-request.php` | `POST /api/v1/admin/requests/{id}/decision` → `AdminRequestController@decide` | Approve or reject request with verification notes, update status, notify requester. |
| 81 | `api/admin/delete-request.php` | `DELETE /api/v1/admin/requests/{id}` → `AdminRequestController@destroy` | Soft-delete request, log action in `audit_logs`. |
| 82 | `api/admin/match.php` | `POST /api/v1/admin/matches` → `MatchController@store` | Atomic database transaction: pair `donation_id` and `request_id`, reserve item quantity, create `matches` and `claims` record, generate unique claim PIN/QR code. |
| 83 | `api/admin/admin-action.php` | `POST /api/v1/admin/actions` → `AdminActionController@execute` | Central dispatcher for bulk actions: batch approve, batch reject, invite admin, export trigger. |
| 84 | `api/admin/user-action.php` | `POST /api/v1/admin/users/{id}/action` → `AdminUserController@executeAction` | Suspend user, ban user, reactivate user, log reason in `audit_logs`. |
| 85 | `api/notifications/unread-count.php`| `GET /api/v1/notifications/unread-count` → `NotificationController@unreadCount` | Return JSON `{ count: X }` for header badge real-time counter. |
| 86 | `api/reports/create.php` | `POST /api/v1/reports` → `ProblemReportController@store` | Create problem report on item, claim, or user with category and description. |
| 87 | `api/transactions/update-status.php` | `POST /api/v1/transactions/{id}/status` → `TransactionController@updateStatus` | Update transaction lifecycle: `scheduled` → `claimed` → `completed`. Release quantity or finalize handover. |
| 88 | `api/user/change-password.php` | `POST /api/v1/user/change-password` → `UserSecurityController@changePassword` | Validate current password, enforce strong new password, update hash. |
| 89 | `api/user/link-account.php` | `POST /api/v1/user/link-account` → `UserProfileController@linkSocialAccount` | Manage external/social provider linking. |
| 90 | `api/user/update-privacy.php` | `POST /api/v1/user/privacy` → `UserProfileController@updatePrivacy` | Save privacy settings (e.g. show full name vs first name only, phone visibility). |
| 91 | `api/user/update-profile.php` | `POST /api/v1/user/profile` → `UserProfileController@update` | Update first name, last name, phone, bio, upload profile picture. |
| 92 | `api/user/update-region.php` | `POST /api/v1/user/region` → `UserProfileController@updateRegion` | Update barangay, city, and province for localized donation matching. |

---

## 8. Summary Table of Migration Counts

| Target Layer | Description | Exact PHP Sources Count |
|---|---|---|
| **[1] UI / HTML** | Static page templates & layout views in `frontend/pages/` | **41** page views extracted |
| **[2] Browser JS** | Page interaction controllers & centralized `api.js` in `frontend/js/` | **41** client modules extracted |
| **[3] Database Logic** | Eloquent Models (`User`, `Donation`, `Request`, `Claim`, `Category`, etc.) | Extracted from all **24** API files & **30** portal pages |
| **[4] Backend Processing** | Laravel API Controllers, FormRequests, Middleware, and Services | **24** legacy API files + **1** cron job + session auth |
| **[5] DB Config** | Laravel `.env` and `config/database.php` | **1** (`config/database.php`) |
| **[6] Obsolete / Duplicate** | Redirects and alias require files to deprecate safely | **5** (`user/index.php`, `user/post-donation.php`, `pages/org-reports.php`, `admin/login.php`, test script) |

---

## 9. Safe Step-by-Step Migration Order

1. **Keep Existing Directory Active**: Leave `c:\xampp\htdocs\CommunityDono` intact so current functionality is never broken.
2. **Create New Project Subdirectories**:
   - `frontend/` (Pure HTML, CSS, JS)
   - `backend/` (Laravel API project initialized via Composer)
3. **Extract CSS & Components**:
   - Move approved brand tokens and design system into `frontend/css/variables.css`, `frontend/css/components.css`, etc.
4. **Port HTML & Client JS**:
   - Convert public pages first (Phase 10), then User Portal (Phase 11), then Admin Portal (Phase 12).
5. **Port API Endpoints to Laravel**:
   - Map each of the 24 procedural endpoints from `api/` into clean Laravel Controllers and Eloquent models with validated FormRequests.
6. **Connect `frontend/js/api.js` to Laravel**:
   - Point API client to `http://127.0.0.1:8000/api/v1/` and verify all tests in Phase 18.
