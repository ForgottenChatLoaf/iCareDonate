# iCareDonate — Production Deployment Guide

## 1. Overview & Architecture

iCareDonate is a PHP/MySQL donation and community support platform. This guide provides comprehensive, step-by-step instructions for deploying the production PHP application to live shared hosting, VPS, or cloud environments, while preserving local XAMPP compatibility.

---

## 2. Centralized Path & Base URL Configuration

The application base path is centralized in `config/app.php`:

```php
// config/app.php
if (!defined('APP_BASE')) {
    $envBase = getenv('APP_BASE');
    if ($envBase !== false) {
        define('APP_BASE', rtrim((string)$envBase, '/'));
    } else {
        // Default: '/CommunityDono' for local XAMPP subfolder.
        // Change to '' for root domain deployment.
        define('APP_BASE', '/CommunityDono');
    }
}
```

### URL Helpers
All internal URLs, redirects, assets, and forms utilize unified helpers:
- `appUrl(string $path)` — Generates an internal URL with `APP_BASE` prepended (clean handling of root slash without duplicates).
- `appRedirect(string $path)` — Performs an HTTP `Location` redirect and safely terminates execution.
- `isSafeInternalRedirect(string $url)` — Validates internal redirect targets to prevent open redirect vulnerabilities.
- `$base` — Defined as `APP_BASE` in view scopes for template links (`<?= $base ?>/pages/login.php`).
- `window.APP_BASE` — Injected into `<head>` in `includes/header.php` for dynamic client-side JS requests.

### Environment Switching
| Setting | Local XAMPP | Production Domain Root |
|---|---|---|
| **URL Example** | `http://localhost/CommunityDono/` | `https://yourdomain.com/` |
| **`APP_BASE` Value** | `'/CommunityDono'` | `''` (empty string) |
| **Configuration Method** | Default in `config/app.php` | Set environment variable `APP_BASE=""` or change line in `config/app.php` |

---

## 3. Production Hosting Requirements

- **Web Server**: Apache 2.4+ with `mod_rewrite` enabled (required for `.htaccess` Bearer token authorization header forwarding).
- **PHP Version**: PHP 8.1 or PHP 8.2+
- **PHP Extensions**:
  - `pdo_mysql` (database communication)
  - `mbstring` (multibyte string handling)
  - `fileinfo` (secure MIME validation for uploads)
  - `json` (REST API serialization)
  - `openssl` (secure token generation)
  - `session` (user authentication)
- **Database**: MySQL 8.0+ or MariaDB 10.4+ with `utf8mb4` character set support.
- **File Upload Limits**: `upload_max_filesize = 10M`, `post_max_size = 12M` in `php.ini`.

---

## 4. File Upload & Deployment Matrix

### Files & Folders to Upload (Production Web Root)
Upload the following files and directories into `public_html` (or your domain's web root):

```
public_html/
├── .htaccess                      # Apache rewrite & access control
├── icaredonate-icon.svg           # Main icon/logo asset
├── index.php                      # Application root entry point
├── config/
│   ├── app.php                    # Base URL configuration (set APP_BASE = '')
│   └── database.php               # Production DB credentials (configured on server)
├── includes/                      # Layout components, auth, utilities
│   ├── auth.php
│   ├── brand-sidebar.php
│   ├── footer.php
│   ├── functions.php
│   ├── header.php
│   ├── nav-admin.php
│   ├── nav-public.php
│   ├── nav-user.php
│   └── topbar-user-btn.php
├── pages/                         # Public and legal pages
│   ├── 403.php, 404.php
│   ├── about.php, browse.php, careers.php, categories.php
│   ├── contact.php, donation-details.php, faq.php
│   ├── forgot-password.php, how-it-works.php, login.php
│   ├── reports.php, reset-password.php, signup.php
│   └── legal/ (privacy.php, terms.php)
├── admin/                         # Admin portal (protected)
├── user/                          # User dashboard (protected)
├── api/                           # Backend API handlers & endpoints
├── php/                           # Supporting backend handlers (contact.php, db.php)
├── assets/                        # Design tokens, stylesheets, JS, icons
│   ├── css/php/                   # Isolated PHP application stylesheets
│   ├── js/                        # Client-side scripts (main.js, date-time-picker.js)
│   ├── img/categories/            # Category thumbnails
│   └── images/                    # Section & hero imagery
├── uploads/                       # User uploads directory (writable: chmod 775)
│   ├── donations/
│   └── private/proofs/
└── scripts/
    └── create_admin.php           # Initial superadmin setup CLI tool
```

### Files & Folders EXCLUDED from Production Upload
Do NOT upload these to the production server:
- `frontend/` — Unfinished HTML client; preserved in Git repository, excluded from initial PHP deployment.
- `database/database.sql` — Contains local development demonstration data and sample passwords.
- `tests/` — Automated test suites.
- `scratch/` — Scratch scripts and inspection utilities.
- `.git/` & `.gitignore` — Source control metadata.
- `docs/` (except offline reference) — Project documentation and wireframe design files.

---

## 5. Production Database Setup

### Step 1: Create Database & User
In cPanel, phpMyAdmin, or MySQL CLI:
```sql
CREATE DATABASE `icare_donate_prod` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'icaredonate_user'@'localhost' IDENTIFIED BY 'StrongRandomPassword!2026';
GRANT ALL PRIVILEGES ON `icare_donate_prod`.* TO 'icaredonate_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 2: Import Schema
In phpMyAdmin (or via MySQL CLI):
1. Select your newly created database in the left sidebar.
2. Click the **Import** tab.
3. Choose `database/schema.sql` and click **Go / Import**.
This creates all 18 clean application tables, foreign keys, and indexes.

### Step 3: Import Safe Production Seed
In phpMyAdmin:
1. Select your database.
2. Click the **Import** tab.
3. Choose `database/seed_production.sql` and click **Go / Import**.

> **Note on SQL Seed Files:**
> - `database/seed_production.sql` contains **both** the 9 essential categories and the 16 platform FAQs.
> - `database/seed_faqs.sql` contains only the FAQs. Since `seed_production.sql` already includes the FAQs, you only need to import `seed_production.sql`.
> - Both files are completely safe for production (zero demo users, zero dummy donations, and zero local upload references).

### Step 4: Configure Database Connection
Copy `config/database.example.php` to `config/database.php` (or edit `config/database.php` directly on the server):
```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

// NOTE: On free hosting like InfinityFree, $host is NOT 'localhost'.
// Use the MySQL Hostname displayed in your hosting control panel (e.g., sql100.infinityfree.com).
$host    = getenv('DB_HOST') ?: 'sql100.yourhost.com';
$db      = getenv('DB_NAME') ?: 'epiz_12345678_icaredonate';
$user    = getenv('DB_USER') ?: 'epiz_12345678';
$pass    = getenv('DB_PASS') !== false ? (string)getenv('DB_PASS') : 'YourHostingDatabasePassword';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Please contact the administrator.');
}
```

---

## 6. Creating the Initial Production Administrator Account

Do **NOT** use demo accounts or hardcoded credentials.

### Method A: Shared Hosting / phpMyAdmin (No SSH, e.g. InfinityFree)
Because free hosts like InfinityFree do not provide SSH terminal access, generate a ready-to-run SQL `INSERT` statement locally:

1. Run this command in your local PowerShell / terminal:
   ```bash
   C:\xampp\php\php.exe scripts/generate_admin_sql.php "admin@yourdomain.com" "YourSecurePassword123!" "Firstname" "Lastname"
   ```
2. The script prints a complete SQL `INSERT` query containing a genuine bcrypt hash.
3. Open your hosting control panel's **phpMyAdmin**, select your database, click the **SQL** tab.
4. Paste the query and click **Go**.
5. Your superadmin account is created immediately.

### Method B: VPS / Dedicated Server (With SSH)
If you have SSH access to your server:
```bash
php scripts/create_admin.php "admin@yourdomain.com" "YourSecurePassword123!" "Firstname" "Lastname"
```

**Security Safeguards**:
- Blocked from browser access (`php_sapi_name() !== 'cli'`).
- Validates email format and duplicate records.
- Enforces minimum 8-character password.
- Employs standard `password_hash(PASSWORD_DEFAULT)`.
- Sets role to `superadmin` and status to `active`.

---

## 7. Uploads Directory Permissions

Ensure web server write permissions on the upload directories:
```bash
chmod 775 uploads
chmod 775 uploads/donations
chmod 775 uploads/private
chmod 775 uploads/private/proofs
```

Ensure `.htaccess` protects private proofs from direct unauthorized downloading:
```apache
# uploads/private/.htaccess
Order Deny,Allow
Deny from all
```

---

## 8. Post-Deployment Verification Checklist

1. **Homepage & Navigation**: Visit `https://yourdomain.com/`. Verify header logo, navigation links, and footer render correctly without broken styles.
2. **Public Pages**: Verify `pages/about.php`, `pages/how-it-works.php`, `pages/categories.php`, `pages/browse.php`, and `pages/faq.php`.
3. **Protected Portals**: Visit `https://yourdomain.com/admin/index.php` in an incognito window. Verify it redirects (HTTP 302) to `https://yourdomain.com/pages/login.php`.
4. **Superadmin Login**: Log in with the account created in Step 6. Verify successful redirect to `https://yourdomain.com/admin/index.php`.
5. **API Endpoints**: Test `https://yourdomain.com/api/v1/categories.php` returns HTTP 200 with JSON category array.
6. **User Registration & Flow**: Register a test user, log in, create a donation offer, and test file upload.

---

## 9. Known Issues & Operational Notes

- **`frontend/` directory**: An unfinished pure HTML prototype exists in `frontend/`. It is maintained in Git for future roadmap development but is omitted from public hosting.
- **SSL / HTTPS**: Always enforce HTTPS in production (via Cloudflare, Let's Encrypt, or host cPanel).
- **Error Display**: Ensure `display_errors = Off` and `log_errors = On` in production `php.ini`. All database errors log to `error_log` without exposing credentials.
