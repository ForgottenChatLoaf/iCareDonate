# iCareDonate

> **Give what you can. Help where it matters.**

iCareDonate is a community resource-sharing platform that connects people who have useful physical items to donate with individuals and communities who need them. It is strictly an item-based mutual aid platform, **not** a cash or monetary solicitation website.

**Core Workflow:**
Donor lists physical item → Admin reviews and verifies listing → Requester submits request with private proof of need → Admin matches & schedules handover → Item claimed & completed.

---

## Technology Stack

The project is built entirely using standard web technologies with no build tools or complex frameworks:

| Layer | Technology |
|---|---|
| **Frontend** | HTML5 (Semantic Structure) |
| **Styling** | CSS3 (Vanilla Design System with CSS Custom Properties) |
| **Client-Side Logic** | Vanilla JavaScript (ES6, no external frameworks) |
| **Backend** | Plain PHP 8.2+ (Procedural & OOP with PDO Prepared Statements) |
| **Database** | MySQL 8.0 / MariaDB (18-table normalised relational schema) |
| **Local Environment** | Apache HTTP Server & MySQL via XAMPP |
| **Version Control** | Git & GitHub |

---

## Master Brand Color Palette

Visual identity is derived from CSS custom property tokens defined in `frontend/css/variables.css`:

| Token | Hex | Role & Usage |
|---|---|---|
| **Primary (Cobalt)** | `#1F4FA3` | Navbar, primary headings, brand mark |
| **Accent / CTA (Coral)** | `#E0553D` | Main action callouts ("Donate Now") |
| **Amber** | `#F2B84B` | Highlight badges, impact statistic numbers |
| **Gold** | `#BC7F08` | Brand wordmark accent on light surfaces |
| **Background (Cream)** | `#FBF7F2` | Page background surface |
| **Surface Soft (Sky)** | `#E3EEFB` | Information panels, cards, section bands |
| **Body Text (Navy)** | `#14213D` | Primary body typography, footer |
| **Muted Text (Slate)** | `#5A6781` | Helper text, secondary descriptions |

---

## Project Folder Structure

```text
CommunityDono/
|
+-- index.php                   # Dynamic PHP homepage (entry point under XAMPP)
+-- .htaccess                   # Apache URL routing, DirectoryIndex, security headers
+-- README.md                   # Project documentation and setup manual
+-- WIREFRAME_CHECKLIST.md      # Wireframe implementation audit & feature checklist
+-- icaredonate-icon.svg        # Official brand SVG icon
|
+-- frontend/                   # Static HTML pages (design review & standalone demos)
|   +-- index.html              # Static homepage
|   +-- about.html
|   +-- how-it-works.html
|   +-- categories.html
|   +-- donations.html
|   +-- donation-details.html
|   +-- donate.html
|   +-- request.html
|   +-- contact.html
|   +-- faqs.html
|   +-- careers.html
|   +-- mission.html
|   +-- help-center.html
|   +-- contact-support.html
|   +-- login.html
|   +-- signup.html
|   +-- forgot-password.html
|   +-- reset-password.html
|   +-- admin-login.html
|   +-- admin-dashboard.html
|   +-- user-dashboard.html
|   +-- privacy.html
|   +-- terms.html
|   +-- 403.html
|   +-- 404.html
|   +-- css/
|   |   +-- variables.css       # CSS custom property tokens (colors, typography)
|   |   +-- base.css            # Reset, typography, container layout
|   |   +-- components.css      # Buttons, cards, badges, alerts, modals
|   |   +-- layout.css          # Grid, flex layout helpers
|   |   \-- pages/              # Per-page stylesheet overrides
|   \-- js/
|       +-- forms.js            # Form validation and submission helpers
|       \-- theme.js            # Light/dark theme toggle
|
+-- pages/                      # Dynamic PHP pages (live database-driven)
|   +-- about.php
|   +-- browse.php              # Donation search, filter, and pagination
|   +-- categories.php
|   +-- how-it-works.php
|   +-- contact.php             # Contact form with support ticket submission
|   +-- faq.php
|   +-- careers.php
|   +-- donation-details.php
|   +-- login.php
|   +-- signup.php
|   +-- forgot-password.php
|   +-- reset-password.php
|   +-- accept-invitation.php   # Admin staff invitation processor
|   +-- reports.php
|   +-- 403.php
|   +-- 404.php
|   \-- legal/
|       +-- privacy.php
|       \-- terms.php
|
+-- admin/                      # Protected Administrator Management Portal
|   +-- index.php               # Admin Dashboard (stats, quick actions, activity feed)
|   +-- donations.php           # Donation review queue (Approve, Reject, Archive)
|   +-- donation-review.php     # Detailed single-donation inspection
|   +-- requests.php            # Request review & proof-of-need inspection
|   +-- request-review.php      # Proof verification & match decision
|   +-- transactions.php        # Transaction monitoring & handover tracking
|   +-- claims.php              # Claims confirmation
|   +-- matches.php             # Donor-to-requester matching console
|   +-- interviews.php          # Requester interview scheduler & logs
|   +-- users.php               # User moderation & account status control
|   +-- moderation.php          # Suspension & ban administration
|   +-- user-detail.php         # User inspection card
|   +-- support.php             # Support tickets & inquiries console
|   +-- inquiries.php           # Public contact inquiry manager
|   +-- faqs.php                # FAQ CRUD manager
|   +-- categories.php          # Category administration & image manager
|   +-- statistics.php          # Analytics & completion metrics
|   +-- reports.php             # Problem report queue
|   +-- profile.php             # Admin profile settings
|   +-- admins.php              # Staff accounts & invitation manager
|   \-- logs.php                # Immutable audit log inspection
|
+-- user/                       # Protected User Portal (Donors & Requesters)
|   +-- index.php               # Routes to user dashboard
|   +-- dashboard.php           # Dashboard (metrics, active handovers, history)
|   +-- browse-donations.php    # In-portal donation directory
|   +-- donations.php           # User's posted donations management
|   +-- offer-donation.php      # Create new donation listing with photo upload
|   +-- edit-donation.php       # Edit draft or pending listing
|   +-- requests.php            # User's aid requests tracking
|   +-- request-donation.php    # Submit request with private proof of need
|   +-- request-details.php     # Request status & match details
|   +-- transactions.php        # Active claims & pickup coordination
|   +-- history.php             # Completed transaction records
|   +-- notifications.php       # In-app notifications centre
|   +-- profile.php             # User contact & address profile
|   +-- settings.php            # Privacy & password settings
|   \-- support.php             # User help tickets
|
+-- api/                        # REST / AJAX endpoints for frontend interactions
|   +-- auth/                   # Login, register, logout, password reset
|   +-- donations/              # Create and update donation listings
|   +-- requests/               # Create item requests
|   +-- transactions/           # Claim and handover status transitions
|   +-- user/                   # Profile, region, privacy updates
|   +-- admin/                  # Admin approve, reject, match, and user actions
|   \-- v1/                     # Modular versioned API endpoints (bootstrap, categories,
|                               # donations, notifications, stats, contact, auth, support)
|
+-- includes/                   # Reusable PHP partials and business logic
|   +-- header.php              # Shared HTML head, theme script, CSS loader
|   +-- footer.php              # Shared footer, theme toggle, script loader
|   +-- functions.php           # Security utilities (escaping, CSRF, flash messages)
|   +-- auth.php                # Session authorisation (requireLogin, requireAdmin)
|   +-- nav-public.php          # Public navbar
|   +-- nav-user.php            # User portal navigation
|   \-- nav-admin.php           # Admin wireframe sidebar navigation
|
+-- config/
|   \-- database.php            # Primary PDO database configuration
|
+-- php/                        # Standalone connection and form handlers
|   +-- db.php                  # Shared PDO database connection
|   +-- auth.php                # Authentication helper
|   \-- contact.php             # Contact form submission handler
|
+-- database/                   # MySQL database assets
|   +-- database.sql            # Master setup: all 18 tables + seed data
|   +-- schema.sql              # Base schema (tables only, no seed data)
|   +-- seed_faqs.sql           # FAQ seed data
|   +-- add_archived_status.sql # Migration: archived status column
|   +-- add_category_image_and_emergency.sql  # Migration: category images
|   +-- alter_scope.sql         # Migration: scope adjustments
|   +-- rollback_archived_status.sql          # Rollback script
|   \-- seed_test_accounts.php  # Demo password re-hasher for test accounts
|
+-- assets/                     # Shared media assets
|   +-- images/                 # Community photography & hero illustrations
|   \-- img/categories/         # Category banner images
|
+-- backend/                    # Experimental backend structure (work in progress)
|   +-- .env.example            # Environment variable template (safe to commit)
|   +-- database/               # Backend-specific schema and seeders
|   \-- routes/                 # API route definitions
|
+-- js/                         # Root-level JS entry point
|   \-- script.js               # Master vanilla JS (theme, mobile nav, modals)
|
+-- css/                        # Root-level CSS entry point
|   \-- style.css               # Master stylesheet
|
+-- uploads/                    # User-uploaded files (excluded from Git via .gitignore)
|   \-- .gitkeep                # Preserves folder in repository
|
\-- scratch/                    # Local development scratch scripts (excluded from Git)
```

---

## Setup and Installation Guide (XAMPP & Apache)

### Step 1: Install XAMPP

1. Download **XAMPP for Windows** (PHP 8.2 or later) from [apachefriends.org](https://www.apachefriends.org/).
2. Run the installer and ensure **Apache** and **MySQL** components are selected.
3. Install to the default location: `C:\xampp`.

### Step 2: Clone or Copy the Project

Clone this repository into the `htdocs` directory:

```bash
git clone https://github.com/ForgottenChatLoaf/iCareDonate.git C:\xampp\htdocs\CommunityDono
```

Or copy the project folder manually so that the directory structure is:

```text
C:\xampp\htdocs\CommunityDono\
```

Verify that `index.php` is present directly inside that folder.

### Step 3: Create the `uploads/` folder structure

After cloning, the `uploads/` folder exists but is empty (user files are excluded from Git). Create the required sub-directories:

```text
uploads/
  donations/
  private/
    proofs/
```

Or run XAMPP's local PHP to let the application create them on first upload.

### Step 4: Start Apache and MySQL

1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.
4. Both services should show green status indicators.

### Step 5: Import the Database

1. Open your browser and go to: `http://localhost/phpmyadmin/`
2. Click the **Databases** tab.
3. Enter database name: `icare_donate` and collation: `utf8mb4_unicode_ci`, then click **Create**.
4. Select `icare_donate` in the left sidebar.
5. Click the **Import** tab.
6. Click **Choose File** and select:
   ```text
   C:\xampp\htdocs\CommunityDono\database\database.sql
   ```
7. Click **Import**.

phpMyAdmin will confirm that all 18 tables and seed data have been imported.

### Step 6: Verify the Database Configuration

The database connection is configured in two places. Both use identical default XAMPP credentials:

**`config/database.php`** (used by PHP pages and the main API):

```php
$host    = '127.0.0.1';
$db      = 'icare_donate';
$user    = 'root';
$pass    = '';          // Default XAMPP: no password
$charset = 'utf8mb4';
```

**`php/db.php`** (used by some standalone handlers):

Same connection parameters as above.

> If your local MySQL uses a custom password, update the `$pass` value in **both** `config/database.php` and `php/db.php`.

---

## Running and Testing the Website Locally

Start Apache and MySQL in XAMPP, then open your browser.

### Dynamic PHP pages (require database)

| Page | URL |
|---|---|
| Homepage | `http://localhost/CommunityDono/` |
| Browse Donations | `http://localhost/CommunityDono/pages/browse.php` |
| Categories | `http://localhost/CommunityDono/pages/categories.php` |
| How It Works | `http://localhost/CommunityDono/pages/how-it-works.php` |
| About Us | `http://localhost/CommunityDono/pages/about.php` |
| Guides & FAQs | `http://localhost/CommunityDono/pages/faq.php` |
| Contact Us | `http://localhost/CommunityDono/pages/contact.php` |
| Sign In | `http://localhost/CommunityDono/pages/login.php` |
| Create Account | `http://localhost/CommunityDono/pages/signup.php` |
| Privacy Policy | `http://localhost/CommunityDono/pages/legal/privacy.php` |
| Terms of Service | `http://localhost/CommunityDono/pages/legal/terms.php` |

### Static HTML pages (no database required)

The `frontend/` directory contains standalone HTML versions of each page. These can be opened directly in a browser without Apache or a database connection:

| Page | URL |
|---|---|
| Homepage | `http://localhost/CommunityDono/frontend/index.html` |
| Browse Donations | `http://localhost/CommunityDono/frontend/donations.html` |
| Categories | `http://localhost/CommunityDono/frontend/categories.html` |
| Login | `http://localhost/CommunityDono/frontend/login.html` |
| Admin Dashboard | `http://localhost/CommunityDono/frontend/admin-dashboard.html` |
| User Dashboard | `http://localhost/CommunityDono/frontend/user-dashboard.html` |

> **Note:** The static `frontend/` HTML files are connected to live APIs where possible. Features that write to or read from the database will not work unless XAMPP is running and the database is imported.

### Protected portals

| Portal | URL |
|---|---|
| Admin Portal | `http://localhost/CommunityDono/admin/` |
| User Portal | `http://localhost/CommunityDono/user/` |

---

## Test Accounts (Local Demo)

> These accounts exist only in the locally imported `database.sql` seed data. They are used for development and school presentation demos. Do **not** use these credentials on any public or production system.

All default demonstration accounts use the password: `Password123!`

| Role | Email | Access Level |
|---|---|---|
| **Super Admin** | `admin@icaredonate.org` | Full Admin Console (`/admin/`) |
| **Donor** | `donor@icaredonate.org` | User Portal — post & manage donations (`/user/`) |
| **Requester** | `requester@icaredonate.org` | User Portal — request aid & upload proof (`/user/`) |

**Quick autofill:** On the login pages, click any of the **Quick Test Autofill** buttons (Admin / Donor / Requester) to auto-populate credentials for one-click testing.

---

## Known Limitations and Features Under Development

The following features are planned or partially implemented but are not yet complete:

- **`backend/` directory** — an experimental modular backend structure is present but not yet integrated with the main application. Use `config/database.php` and `php/db.php` for all active connections.
- **Email notifications** — password reset emails and invitation emails require a configured mail server or SMTP relay (not set up in XAMPP by default).
- **`pages/careers.php`** and **`pages/org-reports.php`** — stub files; not fully implemented.
- **`admin/matches.php`** — matching console is present but the automated matching algorithm is not yet complete.
- **Interview scheduling** — `admin/interviews.php` UI is built; calendar integration is not finalised.
- **`cron/`** — cron job scripts for scheduled tasks exist but require manual setup in your local environment.
- **Production deployment** — the project is designed for local XAMPP development only. There is no live deployment at this time.

---

## Project Team

- **Ethan McClarence P. Gacud** — Project Lead & Project Manager
- **Jheus Jhunwary Porteo** — Information Architect
- **Rosemarie Collado** — UI/UX Designer
- **Mikylla Emberga** — Content Manager
- **Charly Diaz** — Front-End Developer
- **Kurt Saranillo** — Back-End Developer & Database Manager
- **Lennie Cholin** — Tester & Quality Assurance
