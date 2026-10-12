# iCareDonate

> **Give what you can. Help where it matters.**

iCareDonate is a community resource-sharing platform that connects people who have useful physical items to donate with individuals and communities who need them. It is strictly an item-based mutual aid platform, **not** a cash or monetary solicitation website.

**Core Workflow:**  
Donor lists physical item → Admin reviews and verifies listing → Requester submits request with private proof of need → Admin matches & schedules handover → Item claimed & completed.

---

## 🛠 Technology Stack

The project is built entirely using standard, beginner-friendly web technologies with zero build tools or complex frameworks:

- **Frontend:** HTML5 (Semantic Structure)
- **Styling:** CSS3 (Vanilla Design System with CSS Tokens, Responsive Layouts)
- **Client-Side Functionality:** Vanilla JavaScript (ES6, No external frameworks)
- **Backend:** Plain PHP 8.2+ (Procedural & Object-Oriented with PDO Prepared Statements)
- **Database:** MySQL 8.0 / MariaDB (18-table normalized relational schema)
- **Local Environment:** Apache HTTP Server & MySQL via XAMPP
- **Version Control:** Git & GitHub

---

## 🎨 Master Brand Color Palette

The visual identity is derived directly from the official brand palette tokens (`assets/css/tokens.css`):

| Token | Hex Value | Role & Usage |
|---|---|---|
| **Primary (Cobalt)** | `#1F4FA3` | Navbar, primary headings, brand mark, navigation links |
| **Accent / CTA (Coral)** | `#E0553D` | Main action callouts only: "Donate now" button |
| **Amber** | `#F2B84B` | Highlight badges, platform impact statistic numbers |
| **Gold** | `#BC7F08` | Brand wordmark "Care" accent on light surfaces |
| **Background (Cream)** | `#FBF7F2` | Page background surface |
| **Surface Soft (Sky)** | `#E3EEFB` | Information panels, cards, section bands |
| **Body Text (Navy)** | `#14213D` | Primary body typography, dark section bands, footer |
| **Muted Text (Slate)** | `#5A6781` | Helper text, secondary descriptions, placeholders |

---

## 📂 Project Folder Structure

```text
CommunityDono/
│
├── index.html                  # Main public static homepage
├── index.php                   # Dynamic PHP homepage with database integration
├── .htaccess                   # Apache URL routing, DirectoryIndex, and file security
├── README.md                   # Complete documentation and setup manual
├── WIREFRAME_CHECKLIST.md      # Wireframe implementation audit & feature checklist
├── icaredonate-icon.svg        # Official brand SVG icon
│
├── pages/                      # Public informational & authentication pages
│   ├── about.html              # Static About Us page (Mission, Vision, Team)
│   ├── about.php               # Dynamic About Us page
│   ├── browse.php              # Dynamic donation search, filter, and pagination
│   ├── categories.html         # Static directory of donation categories
│   ├── categories.php          # Dynamic categories directory with item counts
│   ├── how-it-works.html       # Static How It Works 3-step guide
│   ├── how-it-works.php        # Dynamic How It Works page
│   ├── contact.html            # Static contact inquiry form
│   ├── contact.php             # Dynamic contact page with support ticket submission
│   ├── faq.html                # Static 16-item Guides & FAQs accordion page
│   ├── faq.php                 # Dynamic FAQs page fetched from MySQL database
│   ├── careers.html            # Static volunteer opportunities page
│   ├── careers.php             # Dynamic careers page
│   ├── donation-details.php    # Detailed donation viewing & requesting modal
│   ├── login.html              # Static split-screen login page with dev autofill
│   ├── login.php               # Dynamic login page with session routing
│   ├── signup.html             # Static user registration page
│   ├── signup.php              # Dynamic registration page
│   ├── forgot-password.php     # Password reset request flow
│   ├── reset-password.php      # Password reset token handler
│   ├── accept-invitation.php   # Admin staff invitation processor
│   ├── 403.php                 # Forbidden error template
│   ├── 404.php                 # Not found error template
│   └── legal/
│       ├── privacy.html        # Static 12-section Privacy Policy
│       ├── privacy.php         # Dynamic Privacy Policy
│       ├── terms.html          # Static 15-section Terms of Service
│       └── terms.php           # Dynamic Terms of Service
│
├── admin/                      # Protected Administrator Management Portal
│   ├── index.php               # Admin Dashboard (4 stat cards, quick actions, activities)
│   ├── donations.php           # Donation review queue (Approve, Reject, Archive, Restore)
│   ├── donation-review.php     # Detailed single donation inspection modal
│   ├── requests.php            # Request review & proof-of-need inspection
│   ├── request-review.php      # Proof verification & match decision modal
│   ├── transactions.php        # Transaction monitoring & handover tracking
│   ├── claims.php              # Claims confirmation
│   ├── matches.php             # Donor-to-requester matching console
│   ├── interviews.php          # Requester interview scheduler & logs
│   ├── users.php               # User moderation & account status control
│   ├── moderation.php          # Suspension & ban administration
│   ├── user-detail.php         # User inspection card
│   ├── support.php             # Support tickets & user inquiries console
│   ├── inquiries.php           # Public contact inquiry manager
│   ├── faqs.php                # FAQ CRUD manager
│   ├── categories.php          # Category administration & image manager
│   ├── statistics.php          # Analytics & transaction completion metrics
│   ├── reports.php             # Problem report queue
│   ├── profile.php             # Admin profile settings
│   ├── admins.php              # Staff accounts & invitation manager
│   └── logs.php                # Immutable audit log inspection
│
├── user/                       # Protected User Portal (Donors & Requesters)
│   ├── index.php               # User entry point (routes to dashboard)
│   ├── dashboard.php           # User Dashboard (metrics, active handovers, history)
│   ├── browse-donations.php    # In-portal donation directory
│   ├── donations.php           # User's posted donations management
│   ├── offer-donation.php      # Create new donation listing with photo upload
│   ├── edit-donation.php       # Edit draft or requested information
│   ├── requests.php            # User's aid requests tracking
│   ├── request-donation.php    # Submit request with private proof of need
│   ├── request-details.php     # Request status & match details
│   ├── transactions.php        # Active claims & pickup coordination
│   ├── history.php             # Completed transaction records
│   ├── notifications.php       # In-app notifications center
│   ├── profile.php             # User contact & address profile
│   ├── settings.php            # Privacy & password settings
│   └── support.php             # User help tickets
│
├── css/                        # Root CSS entry point
│   └── style.css               # Unified master stylesheet importing all modules
│
├── js/                         # Root JavaScript entry point
│   └── script.js               # Master vanilla JS (theme, mobile nav, modals, forms)
│
├── assets/                     # Shared styling and media assets
│   ├── css/
│   │   ├── tokens.css          # Design system color and font tokens
│   │   ├── base.css            # CSS reset, typography, and container layout
│   │   ├── components.css      # Buttons, cards, badges, alerts, modals
│   │   ├── public.css          # Public page layouts, hero, FAQ accordions
│   │   ├── auth.css            # Split-screen login & signup styling
│   │   ├── legal.css           # Privacy policy & terms table formatting
│   │   ├── user.css            # User portal dashboard layout & sidebar
│   │   └── admin.css           # Admin wireframe layout & clean data tables
│   ├── js/
│   │   ├── main.js             # Notification poller, SVG fallback icons
│   │   ├── auth.js             # Password toggle, Caps Lock warning, test prefill
│   │   └── date-time-picker.js # Interview & pickup schedule picker
│   ├── images/                 # Community photography & hero illustrations
│   └── img/categories/         # Category banner images
│
├── php/                        # Backend connection and form endpoints
│   ├── db.php                  # Shared PDO database connection
│   ├── auth.php                # Authentication helper
│   └── contact.php             # Contact form submission handler
│
├── config/
│   └── database.php            # Primary database configuration
│
├── includes/                   # Reusable PHP layouts and business logic
│   ├── header.php              # Shared HTML head, theme script, CSS loader
│   ├── footer.php              # Shared footer, theme toggle, script loader
│   ├── functions.php           # Security utilities (e, CSRF, flash messages)
│   ├── auth.php                # Session authorization (requireLogin, requireAdmin)
│   ├── nav-public.php          # Public navbar
│   ├── nav-user.php            # User portal navigation
│   └── nav-admin.php           # Admin wireframe sidebar navigation
│
├── database/                   # MySQL Database Assets
│   ├── database.sql            # Master database setup (All 18 tables + Seed Data)
│   ├── schema.sql              # Base schema file
│   └── seed_test_accounts.php  # Demonstration password re-hasher
│
└── api/                        # REST and AJAX endpoints for frontend interactions
    ├── auth/                   # Login, register, logout, password reset
    ├── donations/              # Create and update donation listings
    ├── requests/               # Create item requests
    ├── transactions/           # Claim and handover status transitions
    ├── user/                   # Profile, region, privacy updates
    ├── admin/                  # Admin approve, reject, match, and user actions
    └── v1/                     # Modular API endpoints
```

---

## 🚀 Setup and Installation Guide (XAMPP & Apache)

### Step 1: Install XAMPP
1. Download **XAMPP for Windows** (with PHP 8.2 or later) from [apachefriends.org](https://www.apachefriends.org/).
2. Run the installer and ensure **Apache** and **MySQL** components are selected.
3. Install XAMPP to the default location: `C:\xampp`.

### Step 2: Place the Project in Apache's Document Root
1. Copy or clone this repository folder into the `htdocs` directory:
   ```text
   C:\xampp\htdocs\CommunityDono\
   ```
2. Verify that `index.html` and `index.php` are located directly in:
   ```text
   C:\xampp\htdocs\CommunityDono\index.html
   C:\xampp\htdocs\CommunityDono\index.php
   ```

### Step 3: Start Apache and MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.
4. Both services should show green status indicators.

### Step 4: Import the Database
1. Open your web browser and navigate to:
   ```text
   http://localhost/phpmyadmin/
   ```
2. Click on the **Databases** tab.
3. Enter database name: `icare_donate` and collation: `utf8mb4_unicode_ci`, then click **Create**.
4. Select the newly created `icare_donate` database in the left sidebar.
5. Click on the **Import** tab at the top.
6. Click **Choose File** and select:
   ```text
   C:\xampp\htdocs\CommunityDono\database\database.sql
   ```
7. Click **Import** at the bottom of the page.
8. phpMyAdmin will confirm that all 18 tables and seed data have been successfully imported.

### Step 5: Verify Database Settings
The database connection is centrally managed in `config/database.php` and `php/db.php`:
```php
$host    = '127.0.0.1';
$db      = 'icare_donate';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';
```
By default, XAMPP uses user `root` with no password (`''`). If your local MySQL configuration uses a custom password, update the `$pass` variable in `config/database.php` and `php/db.php`.

---

## 🌐 Running and Testing the Website Locally

1. Open your browser and navigate to:
   - **Static Public Homepage:** `http://localhost/CommunityDono/index.html` (or simply `http://localhost/CommunityDono/`)
   - **Dynamic PHP Homepage:** `http://localhost/CommunityDono/index.php`
2. Test the public pages:
   - Browse Donations: `http://localhost/CommunityDono/pages/browse.php`
   - Categories: `http://localhost/CommunityDono/pages/categories.html` / `.php`
   - How It Works: `http://localhost/CommunityDono/pages/how-it-works.html` / `.php`
   - About Us: `http://localhost/CommunityDono/pages/about.html` / `.php`
   - Guides & FAQs: `http://localhost/CommunityDono/pages/faq.html` / `.php`
   - Contact Us: `http://localhost/CommunityDono/pages/contact.html` / `.php`
   - Sign In: `http://localhost/CommunityDono/pages/login.html` / `.php`
   - Create Account: `http://localhost/CommunityDono/pages/signup.html` / `.php`
   - Privacy Policy: `http://localhost/CommunityDono/pages/legal/privacy.html` / `.php`
   - Terms of Service: `http://localhost/CommunityDono/pages/legal/terms.html` / `.php`

---

## 🔑 Test Accounts (School Presentation Demo)

All default demonstration accounts use the password: `Password123!`

| Role | Email | Password | Access Level |
|---|---|---|---|
| **Super Admin** | `admin@icaredonate.org` | `Password123!` | Full Admin Console (`/admin/index.php`) |
| **Donor** | `donor@icaredonate.org` | `Password123!` | User Portal, Post & Manage Donations (`/user/`) |
| **Requester** | `requester@icaredonate.org` | `Password123!` | User Portal, Request Aid & Upload Proof (`/user/`) |

> **Tip for School Defense:** On the login page (`/pages/login.html` or `/pages/login.php`), click any of the **Quick Test Autofill** buttons (Admin, Donor, Requester) to instantly populate credentials and test role-based portal routing with one click!

---

## 👥 Project Team

- **Ethan McClarence P. Gacud** — Project Lead & Project Manager
- **Jheus Jhunwary Porteo** — Information Architect
- **Rosemarie Collado** — UI/UX Designer
- **Mikylla Emberga** — Content Manager
- **Charly Diaz** — Front-End Developer
- **Kurt Saranillo** — Back-End Developer & Database Manager
- **Lennie Cholin** — Tester & Quality Assurance
