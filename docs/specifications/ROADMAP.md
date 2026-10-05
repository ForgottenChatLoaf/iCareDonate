# iCareDonate — Full Development Roadmap

## 0. FINAL TECHNOLOGY ARCHITECTURE

Lock this in before development:

```text
┌──────────────────────────────────────────────┐
│                  FRONTEND                    │
│                                              │
│       HTML + CSS + JavaScript                │
│       Static UI / Pages / Components         │
│                                              │
└─────────────────────┬────────────────────────┘
                      │
                HTTP / REST API
                      │
┌─────────────────────▼────────────────────────┐
│                  BACKEND                     │
│                                              │
│             PHP + Laravel                    │
│       Routes / Controllers / Models          │
│       Authentication / Business Logic        │
│                                              │
└─────────────────────┬────────────────────────┘
                      │
                 Eloquent ORM
                      │
┌─────────────────────▼────────────────────────┐
│                   DATABASE                   │
│                                              │
│                    MySQL                     │
│                                              │
└──────────────────────────────────────────────┘
```

### Development tools

```text
VS Code
Git + GitHub
XAMPP → MySQL + phpMyAdmin
Laravel → Backend
Browser → Frontend testing
```

**Apache is optional for the Laravel development server.**

You can develop Laravel with:

```bash
php artisan serve
```

while XAMPP provides MySQL.

---

# PHASE 1 — PROJECT FOUNDATION

Before building individual pages, establish the project structure.

## 1.1 Create the repositories

Recommended:

```text
iCareDonate/
│
├── frontend/
│
└── backend/
```

### Frontend

```text
frontend/
│
├── pages/
│   ├── public/
│   ├── auth/
│   ├── user/
│   └── admin/
│
├── assets/
│   ├── images/
│   ├── icons/
│   └── fonts/
│
├── css/
│   ├── variables.css
│   ├── reset.css
│   ├── typography.css
│   ├── layout.css
│   ├── components.css
│   └── responsive.css
│
└── js/
    ├── api.js
    ├── auth.js
    ├── components.js
    ├── validation.js
    └── pages/
```

### Laravel backend

```text
backend/
│
├── app/
│   ├── Models/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   └── Services/
│
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
│
├── routes/
│   ├── api.php
│   └── web.php
│
├── storage/
├── config/
├── resources/
├── .env
└── artisan
```

---

# PHASE 2 — BRAND & DESIGN SYSTEM

This needs to happen **before mass page development**.

Your attached brand palette becomes the visual authority.

## 2.1 Centralize colors

Use CSS variables.

```css
:root {
    --primary: #09C4B8;
    --primary-dark: #0F766E;
    --soft-mint: #D9F7F1;

    --coral: #F2603F;
    --coral-dark: #C94832;
    --soft-coral: #FFE8E0;

    --background: #FFFFFF;
    --surface: #FFFFFF;
    --surface-soft: #F0FAF8;

    --text: #17363A;
    --text-soft: #46636A;
    --text-muted: #758B80;

    --border: #D9ECEB;
    --border-strong: #B8DDD8;

    --success: #1BA05C;
    --warning: #B65C00;
    --danger: #E02D20;
    --info: #2F6BFF;
}
```

Use the exact values from your approved brand palette.

## 2.2 Typography

Use:

```text
Brand:
Lilita One
```

Only for:

* Logo
* Brand name
* Very limited display text

Main headings:

```text
Plus Jakarta Sans
```

Body/UI:

```text
Outfit
```

Therefore:

```css
--font-brand: "Lilita One", sans-serif;
--font-heading: "Plus Jakarta Sans", sans-serif;
--font-body: "Outfit", sans-serif;
```

## 2.3 Build reusable components

Create:

* Buttons
* Inputs
* Selects
* Checkboxes
* Radio buttons
* Cards
* Badges
* Alerts
* Toasts
* Modals
* Tables
* Pagination
* Tabs
* Dropdowns
* Navigation
* Sidebar
* Breadcrumbs
* Empty states
* Loading states
* Error states
* Confirmation dialogs

**Do not individually style every page from scratch.**

---

# PHASE 3 — WIREFRAME → IMPLEMENTATION MAP

Your wireframe should become the **source of truth**.

Create a checklist containing every screen.

### Public

```text
Home
About Us
Careers
Organizational Reports
Community Impact
Volunteers
Sponsors
Events
How It Works
Donation Categories
Available Donations
Donation Preview
Contact Us
Help Center
Guides
FAQs
Legal Information
```

### Authentication

```text
Login
Signup
Email Verification
Forgot Password
Reset Password
Locked/Suspended
Error
Maintenance
```

### User

```text
Dashboard
Post Donation
Donation Preview
My Donations
Edit/Resubmit Donation
Browse Donations
Donation Details
Request Donation
Requester Verification
My Requests
Edit Request
Cancel Request
Claim/Pick-up
History
Notifications
Messages/Support
Report Problem
Profile
Settings
Sessions
```

### Admin

```text
Admin Dashboard
Users
Moderation
Verification Interviews
Donations Review
Requests Review
Matches
Claims Monitoring
Transactions
Categories
Problem Reports
Statistics
Reports
Settings
Audit Log
Contact Inquiries
Admin Management
Admin Login/Access
```

Every item should eventually have:

```text
WIREFRAME
   ↓
HTML PAGE
   ↓
CSS
   ↓
JS
   ↓
API
   ↓
LARAVEL
   ↓
MYSQL
```

---

# PHASE 4 — DATABASE DESIGN

Do this **before connecting pages to the backend**.

Design the MySQL schema around actual system entities.

Potential core tables:

```text
users
roles
permissions
role_permissions

admin_invitations
sessions
email_verifications
password_resets

donations
donation_categories
donation_images

donation_requests
request_verifications

matches
claims
transactions

notifications
messages
support_tickets
problem_reports

contact_inquiries

reports
audit_logs
```

Potential user statuses:

```text
pending
active
suspended
disabled
```

Donation statuses:

```text
draft
pending_review
approved
rejected
available
reserved
claimed
completed
cancelled
```

Request statuses:

```text
pending
under_review
approved
rejected
matched
cancelled
completed
```

Don't finalize every table blindly. Build the schema based on the actual wireframe workflows.

---

# PHASE 5 — LARAVEL BACKEND FOUNDATION

Initialize Laravel.

```bash
composer create-project laravel/laravel backend
```

Configure:

```text
.env
```

for MySQL.

Example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=icare_donate
DB_USERNAME=root
DB_PASSWORD=
```

Do **not** commit `.env` to GitHub.

Create:

```text
Migrations
Models
Controllers
Form Requests
Middleware
Services
API Routes
Seeders
Factories
```

---

# PHASE 6 — AUTHENTICATION & AUTHORIZATION

Build this before donation/request functionality.

## Authentication

Implement:

```text
Signup
Login
Logout
Email verification
Forgot password
Reset password
Session management
Account status
```

## Authorization

Do **not** simply check:

```text
if user == admin
```

Build proper roles/permissions.

Example:

```text
User
Admin
Super Admin
```

Potential permissions:

```text
users.view
users.suspend

donations.view
donations.approve
donations.reject

requests.view
requests.approve
requests.reject

reports.view
reports.export

admins.invite
admins.manage
admins.suspend
```

---

# PHASE 7 — ADMIN INVITATION SYSTEM

Do not create a public:

```text
"Sign up as Admin"
```

Instead:

```text
Super Admin
     ↓
Admin Management
     ↓
Invite Admin
     ↓
Admin email
     ↓
Invitation link
     ↓
Verify email
     ↓
Create password
     ↓
Account activated
```

Statuses:

```text
invited
active
suspended
disabled
```

Record every administrative action in:

```text
audit_logs
```

---

# PHASE 8 — FRONTEND FOUNDATION

Now convert your existing UI into proper HTML pages.

Important:

### HTML

Contains:

```text
Structure
Forms
Tables
Cards
Navigation
Content
```

### CSS

Contains:

```text
Branding
Layout
Responsive design
Components
Animations
States
```

### JavaScript

Contains:

```text
Interactions
Validation
Modal control
API requests
Dynamic data
Notifications
```

### Laravel

Contains:

```text
Authentication
Database
Business logic
Validation
Authorization
API
File handling
```

**Don't put Laravel/PHP business logic inside your HTML files.**

---

# PHASE 9 — API ARCHITECTURE

Your HTML/JS frontend communicates with Laravel.

For example:

```text
login.html
     ↓
login.js
     ↓
POST /api/login
     ↓
Laravel
     ↓
User authentication
     ↓
JSON response
```

Example:

```javascript
fetch("/api/login", {
    method: "POST",
    headers: {
        "Content-Type": "application/json"
    },
    body: JSON.stringify({
        email,
        password
    })
});
```

Create a centralized:

```text
js/api.js
```

instead of scattering API logic everywhere.

---

# PHASE 10 — PUBLIC WEBSITE

Build public pages first.

Order:

### 10.1 Core

```text
Home
About
How It Works
Donation Categories
Available Donations
Contact
```

### 10.2 Information

```text
Community Impact
Volunteers
Sponsors
Events
Reports
```

### 10.3 Support

```text
Help Center
Guides
FAQs
Legal
```

Then connect dynamic content to Laravel where needed.

---

# PHASE 11 — USER SYSTEM

Implement in this order:

### 11.1 Dashboard

```text
User information
Donation statistics
Request statistics
Notifications
Recent activity
```

### 11.2 Donations

```text
Post Donation
Preview
Submit
My Donations
Edit
Resubmit
Cancel
```

### 11.3 Browse

```text
Browse donations
Filters
Search
Categories
Donation details
Request donation
```

### 11.4 Requests

```text
Create request
Verification
My Requests
Edit
Cancel
Status
```

### 11.5 Matching

```text
Matching
Reservation
Claim/pick-up
Completion
History
```

### 11.6 Communication

```text
Notifications
Messages
Support
Problem reports
```

### 11.7 Account

```text
Profile
Settings
Sessions
Security
```

---

# PHASE 12 — ADMIN SYSTEM

After the user workflow works, build administration.

## Dashboard

Show:

```text
Users
Donations
Requests
Matches
Pending reviews
Claims
Reports
Problems
```

## Moderation

```text
Users
Donations
Requests
Verification
```

## Operations

```text
Matches
Claims
Transactions
Categories
```

## Communication

```text
Contact inquiries
Problem reports
Notifications
```

## Analytics

```text
Statistics
Reports
Exports
```

## Security

```text
Admin Management
Roles
Permissions
Sessions
Audit Logs
Settings
```

---

# PHASE 13 — CUSTOM UI COMPONENTS

This is especially important based on your current screenshots.

Do **not** use ugly browser-default controls for important UI.

Build custom components for:

### Date picker

```text
Calendar
Month navigation
Date selection
```

### Time picker

Instead of the browser's ugly wheel:

```text
Hour     Minute     AM/PM
  09       30          AM
```

styled using your iCareDonate design system.

### Location

Use a proper application-style:

```text
Location
[ Search location................ ]

[ Select region ]
[ Select city   ]
[ Address       ]
```

rather than relying on inconsistent browser controls.

---

# PHASE 14 — STANDARDIZED STATES

Every major page needs:

```text
Loading
Success
Empty
Error
Unauthorized
Forbidden
Not Found
Offline/Network Error
```

For example:

```text
No donations available

There are currently no donations matching your filters.

[ Clear Filters ]
```

Don't leave blank screens.

---

# PHASE 15 — SECURITY

This should be treated as a **core development phase**, not an afterthought.

Implement:

```text
Password hashing
Authentication
Authorization
CSRF protection where applicable
Input validation
SQL injection protection
Prepared/Eloquent queries
XSS protection
Rate limiting
Secure sessions
Email verification
Password reset security
File upload validation
Access control
Audit logs
```

For uploads:

```text
Allowed MIME types
File size limits
Safe filenames
Storage outside public execution paths where appropriate
Authorization checks
```

Never trust frontend validation alone.

---

# PHASE 16 — RESPONSIVE DESIGN

Test:

```text
Desktop
Laptop
Tablet
Mobile
```

At minimum:

```text
320px
375px
768px
1024px
1280px+
```

Pay special attention to:

```text
Admin tables
Sidebar
Forms
Modals
Date/time controls
Donation cards
Dashboard
Navigation
```

---

# PHASE 17 — INTEGRATION

Now connect everything.

```text
HTML
 ↓
JavaScript
 ↓
Laravel API
 ↓
Models
 ↓
MySQL
```

Test complete workflows.

### Donation workflow

```text
User logs in
 ↓
Post donation
 ↓
Upload images
 ↓
Submit
 ↓
Admin reviews
 ↓
Admin approves
 ↓
Donation becomes available
 ↓
Another user requests
 ↓
Verification
 ↓
Match
 ↓
Claim
 ↓
Complete
 ↓
History updated
```

### Admin workflow

```text
Admin login
 ↓
Dashboard
 ↓
Review donation
 ↓
Approve/reject
 ↓
Reason recorded
 ↓
User notified
 ↓
Audit log created
```

---

# PHASE 18 — TESTING

Create a test matrix.

### Authentication

```text
✓ Signup
✓ Login
✓ Logout
✓ Verification
✓ Password reset
✓ Suspended account
✓ Invalid credentials
```

### Donations

```text
✓ Create
✓ Edit
✓ Submit
✓ Approve
✓ Reject
✓ Resubmit
✓ Cancel
✓ Complete
```

### Requests

```text
✓ Create
✓ Verify
✓ Approve
✓ Reject
✓ Cancel
✓ Match
```

### Admin

```text
✓ Permissions
✓ Admin invitation
✓ User suspension
✓ Audit log
✓ Reports
```

### Security

```text
✓ Unauthorized API request
✓ Wrong role
✓ Invalid input
✓ Malicious upload
✓ Expired session
```

---

# PHASE 19 — GITHUB / TEAM DEVELOPMENT

Your GitHub repository should contain the source code, **not secrets**.

Recommended:

```text
iCareDonate/
│
├── frontend/
├── backend/
├── database/
├── docs/
└── README.md
```

Use branches:

```text
main
develop
feature/auth
feature/donations
feature/admin
feature/frontend
```

Example workflow:

```text
Developer
   ↓
feature branch
   ↓
Commit
   ↓
Push
   ↓
Pull Request
   ↓
Review
   ↓
Merge
```

Never put:

```text
.env
database passwords
API secrets
private keys
```

into GitHub.

---

# PHASE 20 — LOCAL DEVELOPMENT

Your team can work like this:

```text
GitHub
   ↓
Clone repository
   ↓
Install PHP/Composer
   ↓
Install Node if needed
   ↓
Start XAMPP MySQL
   ↓
Create database
   ↓
Configure .env
   ↓
Run migrations
   ↓
Start Laravel
   ↓
Open frontend
```

For Laravel:

```bash
php artisan migrate
```

Then:

```bash
php artisan serve
```

---

# PHASE 21 — DEPLOYMENT

Once everything works locally:

```text
                    INTERNET
                       │
                   Cloudflare
                       │
                       ▼
              PHP/Laravel Hosting
                       │
              ┌────────┴────────┐
              │                 │
          Laravel API         Storage
              │
              ▼
             MySQL
```

Cloudflare can provide things such as:

```text
DNS
HTTPS
CDN
Security
Caching
```

But **Cloudflare/GitHub Pages alone won't run your Laravel + PHP + MySQL application**.

GitHub remains your:

```text
Source control
Team collaboration
Version history
```

---

# PHASE 22 — FINAL QA

Before calling iCareDonate complete:

### Design

```text
□ Brand palette is consistent
□ Typography is consistent
□ No random colors
□ No random fonts
□ Admin matches the main brand
□ Auth pages match the brand
□ Mobile layout works
```

### Frontend

```text
□ Every wireframe screen exists
□ Every button has behavior
□ Forms work
□ Validation works
□ Loading states exist
□ Empty states exist
□ Error states exist
```

### Backend

```text
□ Laravel API works
□ Authentication works
□ Authorization works
□ Database relationships work
□ Validation works
□ File uploads are secure
```

### Admin

```text
□ Admin management works
□ Invitations work
□ Permissions work
□ Moderation works
□ Audit logs work
□ Reports work
```

### Security

```text
□ Passwords hashed
□ Secrets excluded from Git
□ Unauthorized requests blocked
□ Role restrictions enforced server-side
□ Uploads validated
□ Sessions secured
```

---

# THE ACTUAL DEVELOPMENT ORDER

If you want the **short version your team can follow**, use this exact order:

```text
01 — Wireframe Audit
        ↓
02 — Finalize Brand Design System
        ↓
03 — Frontend Folder Structure
        ↓
04 — Laravel Project Setup
        ↓
05 — MySQL Database Architecture
        ↓
06 — Laravel Models + Migrations
        ↓
07 — Authentication
        ↓
08 — Roles + Permissions
        ↓
09 — Admin Invitation System
        ↓
10 — Reusable Frontend Components
        ↓
11 — Public Pages
        ↓
12 — User Dashboard
        ↓
13 — Donation System
        ↓
14 — Request System
        ↓
15 — Verification
        ↓
16 — Matching
        ↓
17 — Claim/Pick-up
        ↓
18 — Notifications + Messages
        ↓
19 — Profile + Settings
        ↓
20 — Admin Dashboard
        ↓
21 — Moderation
        ↓
22 — Reports + Statistics
        ↓
23 — Audit Logs
        ↓
24 — Security Hardening
        ↓
25 — Responsive Pass
        ↓
26 — Full Integration Testing
        ↓
27 — GitHub Team Testing
        ↓
28 — Production Hosting
        ↓
29 — Cloudflare
        ↓
30 — Final QA
        ↓
31 — Launch
```

## Most important correction from the old roadmap

Your architecture is now officially:

> **Frontend: HTML + CSS + JavaScript**  
> **Backend: PHP + Laravel**  
> **Database: MySQL**  
> **Web server/infrastructure: Apache or Laravel's development server locally; production PHP-compatible hosting**  
> **GitHub: source control/collaboration**  
> **Cloudflare: DNS/security/CDN layer, not your Laravel backend**  

And **do not start converting/deleting your existing PHP files yet**. First map each existing PHP file into one of these categories:

```text
PHP file
   │
   ├── UI/page only ──────────→ HTML
   │
   ├── Browser interaction ──→ JavaScript
   │
   ├── Database logic ───────→ Laravel Model/Controller
   │
   ├── Backend processing ────→ Laravel Service/Controller
   │
   ├── DB configuration ──────→ Laravel .env/config
   │
   └── obsolete/duplicate ───→ Remove later
```

That way, **we migrate the existing work instead of accidentally destroying functionality you've already built.**
