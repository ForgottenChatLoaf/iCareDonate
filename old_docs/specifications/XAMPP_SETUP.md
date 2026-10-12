# iCareDonate — XAMPP Setup & PHP Architecture Guide
> **Version:** 1.0 · **Date:** 2026-10-04  
> Covers local development environment setup, project structure, PHP/MySQL data flow,  
> security practices, and the recommended path from wireframe to working application.

---

## The Golden Rule: HTML never talks to MySQL directly

```
Browser (HTML / CSS / JS)
         ↓
    PHP backend
         ↓
       MySQL
         ↓
    PHP backend
         ↓
Browser (rendered response)
```

The browser never receives a database password. PHP is the only layer that touches MySQL.

---

## 1. XAMPP Setup

### Installation

1. Download and install XAMPP for Windows from [apachefriends.org](https://www.apachefriends.org/)
2. Start the XAMPP Control Panel
3. Turn on **Apache** and **MySQL**

### Where the project lives

```
C:\xampp\htdocs\icareDonate\
```

Access it in your browser at:

```
http://localhost/icareDonate/
```

**Put the entire project folder inside `htdocs`** — not just PHP files. CSS, JS, images, and PHP all belong together, organized by responsibility inside the folder.

---

## 2. Project Folder Structure

```
C:\xampp\htdocs\icareDonate\
│
├── index.php                     ← Public homepage
│
├── pages\                        ← Public pages (no login required)
│   ├── about.php
│   ├── how-it-works.php
│   ├── categories.php
│   ├── browse.php
│   ├── donation-details.php
│   ├── contact.php
│   ├── faq.php
│   ├── login.php
│   ├── signup.php
│   ├── forgot-password.php
│   ├── reset-password.php
│   └── legal\
│       ├── privacy.php
│       └── terms.php
│
├── user\                         ← Authenticated user area
│   ├── dashboard.php
│   ├── donations.php
│   ├── post-donation.php
│   ├── requests.php
│   ├── notifications.php
│   ├── support.php
│   └── settings.php
│
├── admin\                        ← Admin area (separate auth, role-protected)
│   ├── index.php
│   ├── donations.php
│   ├── requests.php
│   ├── matches.php
│   ├── transactions.php
│   ├── users.php
│   ├── categories.php
│   ├── moderation.php
│   ├── reports.php
│   ├── audit.php
│   └── settings.php
│
├── api\                          ← PHP API endpoints (called by JS fetch())
│   ├── auth\
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── register.php
│   ├── donations\
│   │   ├── create.php
│   │   ├── update.php
│   │   └── list.php
│   ├── requests\
│   │   ├── create.php
│   │   └── list.php
│   └── admin\
│       ├── approve-donation.php
│       ├── reject-donation.php
│       └── create-match.php
│
├── config\
│   └── database.php              ← Single PDO connection (never duplicated)
│
├── includes\                     ← Reusable PHP components
│   ├── header.php
│   ├── footer.php
│   ├── auth.php                  ← requireLogin(), requireAdmin()
│   └── functions.php             ← Helpers, status transitions
│
├── assets\
│   ├── css\
│   │   ├── tokens.css            ← CSS custom properties (design tokens)
│   │   ├── base.css              ← Reset + base styles
│   │   ├── components.css        ← Buttons, cards, badges, forms
│   │   ├── public.css            ← Public site
│   │   ├── user.css              ← User dashboard
│   │   └── admin.css             ← Admin panel
│   ├── js\
│   │   ├── main.js
│   │   ├── donations.js
│   │   ├── requests.js
│   │   └── admin.js
│   └── images\
│       ├── logo.svg
│       └── logo-compact.svg
│
└── uploads\
    ├── donations\                ← Publicly accessible donation photos
    └── private\                  ← NEVER publicly accessible
        ├── proofs\               ← Requester proof-of-need documents
        └── admin\
```

> [!IMPORTANT]
> The `uploads/private/` folder must **never** be served directly by Apache. Use PHP to gate access to those files. See section 11 for upload security.

---

## 3. Database Setup

Open phpMyAdmin:
```
http://localhost/phpmyadmin/
```

Create a new database:
```
Name:      icare_donate
Charset:   utf8mb4
Collation: utf8mb4_unicode_ci
```

Your key tables (full schema in [`DATABASE.md`](DATABASE.md)):

```
users               user_sessions
categories          donations           donation_images
requests            request_proofs
matches             transactions
notifications       support_tickets     problem_reports
audit_logs          faqs
```

---

## 4. Database Connection File

Create **one** connection file. Never duplicate it.

```php
<?php
// config/database.php

declare(strict_types=1);

$host    = '127.0.0.1';
$db      = 'icare_donate';
$user    = 'root';
$pass    = '';           // Change in production
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Database connection failed.');
}
```

Every PHP file that needs the database uses:

```php
require_once __DIR__ . '/../config/database.php';
```

> [!CAUTION]
> Never put database credentials in JavaScript, HTML, or any client-visible file. Credentials belong only in server-side PHP configuration.

---

## 5. Turning the Wireframe into PHP

You don't need to rewrite existing HTML. Rename `.html` → `.php` and it still works. PHP only activates when you add `<?php ... ?>` tags.

**Before (static HTML):**
```html
<h1>Welcome to iCareDonate</h1>
```

**After (dynamic PHP):**
```php
<h1>Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest') ?></h1>
```

The wireframe's three areas (public / user / admin) map directly to the three folder groups (`pages/`, `user/`, `admin/`).

**Do not** keep everything in one giant file like the wireframe. The wireframe is a specification — break it into components.

---

## 6. Shared Layout via Includes

```php
// includes/header.php
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($pageTitle ?? 'iCareDonate') ?></title>
  <link rel="stylesheet" href="/icareDonate/assets/css/tokens.css">
  <link rel="stylesheet" href="/icareDonate/assets/css/components.css">
  <?php if (!empty($extraCss)): ?>
  <link rel="stylesheet" href="/icareDonate/assets/css/<?= $extraCss ?>">
  <?php endif; ?>
</head>
<body>
```

```php
// pages/browse.php
<?php
$pageTitle = 'Browse Donations — iCareDonate';
$extraCss  = 'public.css';
require_once __DIR__ . '/../includes/header.php';
?>

<main>
  <h1>Available Donations</h1>
  <!-- ... -->
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
```

---

## 7. Authentication — `includes/auth.php`

```php
<?php
// includes/auth.php

declare(strict_types=1);

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /icareDonate/pages/login.php');
        exit;
    }

    if (($_SESSION['status'] ?? '') !== 'active') {
        session_destroy();
        header('Location: /icareDonate/pages/login.php?e=suspended');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();

    if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}
```

Every user page starts with:
```php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
```

Every admin page starts with:
```php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
```

> [!WARNING]
> Hiding a nav link is NOT security. Anyone can type `/admin/users.php` directly into a browser. PHP must reject unauthorized requests at the server level — every time.

---

## 8. Password Hashing

**Never store plain text passwords.**

**Register:**
```php
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "INSERT INTO users (email, password_hash, first_name, last_name) VALUES (:email, :hash, :first, :last)"
);
$stmt->execute([
    ':email' => $email,
    ':hash'  => $hash,
    ':first' => $firstName,
    ':last'  => $lastName,
]);
```

**Login:**
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true);       // Prevent session fixation
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['status']  = $user['status'];
    header('Location: /icareDonate/user/dashboard.php');
    exit;
}
```

---

## 9. Prepared Statements — Always

**Never do this (SQL injection vulnerability):**
```php
// DANGEROUS — never do this
$sql = "SELECT * FROM users WHERE email = '$email'";
```

**Always do this:**
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();
```

Prepared statements separate SQL code from user data. This is non-negotiable.

---

## 10. CSRF Protection

Any form that changes data (post donation, submit request, approve, reject, cancel, admin actions) must include a CSRF token.

**Generate once per session:**
```php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

**Include in every form:**
```html
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
```

**Verify on every POST:**
```php
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid request.');
}
```

---

## 11. File Upload Security

**Never do this:**
```php
// DANGEROUS
move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/' . $_FILES['file']['name']);
```

**Always do this:**

```php
// includes/upload.php

function storeDonationImage(array $file, int $donationId): string
{
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxBytes     = 10 * 1024 * 1024; // 10 MB

    if (!in_array($file['type'], $allowedMimes, true)) {
        throw new RuntimeException('Invalid file type.');
    }

    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('File too large.');
    }

    // Use finfo for actual MIME validation (not just the browser claim)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);
    if (!in_array($realMime, $allowedMimes, true)) {
        throw new RuntimeException('File content does not match extension.');
    }

    // Random filename — never use the original name
    $ext  = match($realMime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => throw new RuntimeException('Unsupported type.'),
    };
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dir  = __DIR__ . "/../uploads/donations/$donationId/";

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    move_uploaded_file($file['tmp_name'], $dir . $name);

    return "uploads/donations/$donationId/$name";
}

function storeProofDocument(array $file, int $requestId): string
{
    // Same validation, but stored in private/proofs/ — never web-accessible
    // Access only through an authenticated PHP route
    ...
    $dir = __DIR__ . "/../uploads/private/proofs/$requestId/";
    ...
}
```

**Rule:** Donation images → `uploads/donations/` (public). Proof documents → `uploads/private/proofs/` (private, PHP-gated only).

---

## 12. Example: Saving a Donation (Form → PHP → MySQL)

**HTML form (`user/post-donation.php`):**
```html
<form action="/icareDonate/api/donations/create.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <label for="title">Donation title</label>
    <input id="title" name="title" type="text" required>

    <label for="category_id">Category</label>
    <select id="category_id" name="category_id" required>
        <option value="1">Food</option>
        <option value="2">School Supplies</option>
        <option value="3">Clothing</option>
        <option value="4">Medical</option>
    </select>

    <label for="quantity">Quantity</label>
    <input id="quantity" name="quantity" type="number" min="1" required>

    <button type="submit" class="btn btn-primary">Submit donation</button>
</form>
```

**PHP handler (`api/donations/create.php`):**
```php
<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

// 1. CSRF check
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid request.');
}

// 2. Server-side validation (never trust the browser)
$title      = trim($_POST['title'] ?? '');
$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$quantity   = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

$errors = [];
if ($title === '')        $errors[] = 'Title is required.';
if (!$categoryId)         $errors[] = 'Please select a category.';
if (!$quantity || $quantity < 1) $errors[] = 'Quantity must be at least 1.';

if ($errors) {
    http_response_code(422);
    exit(implode(' ', $errors));
}

// 3. Insert with prepared statement
$stmt = $pdo->prepare("
    INSERT INTO donations (donor_id, category_id, title, quantity_total, status, created_at)
    VALUES (:donor_id, :category_id, :title, :quantity, 'pending_verification', NOW())
");
$stmt->execute([
    ':donor_id'    => $_SESSION['user_id'],
    ':category_id' => $categoryId,
    ':title'       => $title,
    ':quantity'    => $quantity,
]);

// 4. Redirect with success message
header('Location: /icareDonate/user/donations.php?created=1');
exit;
```

---

## 13. JavaScript Fetch Flow (for async interactions)

For actions that shouldn't reload the page (notifications, status updates, search filtering):

```javascript
// assets/js/donations.js

async function submitRequest(formData) {
    const response = await fetch('/icareDonate/api/requests/create.php', {
        method: 'POST',
        body: formData,     // FormData automatically includes CSRF token from form
    });

    if (!response.ok) {
        const message = await response.text();
        showError(message);
        return;
    }

    showSuccess('Your request has been submitted.');
    refreshRequestList();
}
```

The flow:
```
User fills form
      ↓
JavaScript calls fetch()
      ↓
PHP API validates + processes
      ↓
MySQL saves data
      ↓
PHP returns JSON
      ↓
JavaScript updates the UI
```

Use JS fetch for: notifications, search, filtering, request submission, admin approval actions, status updates.

Use standard HTML form POST for: login, signup, post donation (simpler, more reliable for file uploads).

---

## 14. Status Transitions — Centralized in PHP

All status changes go through a single function. Never let random pages directly update statuses.

```php
// includes/functions.php

function transitionDonationStatus(
    PDO    $pdo,
    int    $donationId,
    string $newStatus,
    int    $adminId,
    string $reason = ''
): void {
    // Allowed transitions map
    $allowed = [
        'draft'               => ['pending_verification'],
        'pending_verification'=> ['needs_information', 'approved', 'rejected'],
        'needs_information'   => ['pending_verification'],
        'approved'            => ['fully_allocated', 'expired', 'cancelled'],
    ];

    // Get current status
    $stmt = $pdo->prepare("SELECT status FROM donations WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => $donationId]);
    $current = $stmt->fetchColumn();

    if (!in_array($newStatus, $allowed[$current] ?? [], true)) {
        throw new RuntimeException("Invalid transition: $current → $newStatus");
    }

    // Update status
    $stmt = $pdo->prepare("UPDATE donations SET status = :status, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':status' => $newStatus, ':id' => $donationId]);

    // Write audit log
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (actor_id, actor_role, action, target_type, target_id, old_value, new_value, reason, created_at)
        VALUES (:actor, 'admin', 'DONATION_STATUS_CHANGED', 'donations', :target, :old, :new, :reason, NOW())
    ");
    $stmt->execute([
        ':actor'  => $adminId,
        ':target' => $donationId,
        ':old'    => json_encode(['status' => $current]),
        ':new'    => json_encode(['status' => $newStatus]),
        ':reason' => $reason,
    ]);
}
```

---

## 15. Session Security

```php
// At login — always regenerate after authentication
session_regenerate_id(true);

// Store only what's needed in session
$_SESSION['user_id'] = $user['id'];
$_SESSION['role']    = $user['role'];
$_SESSION['status']  = $user['status'];

// Logout — destroy everything
session_start();
session_unset();
session_destroy();
header('Location: /icareDonate/pages/login.php');
exit;
```

**For production** (when served over HTTPS), add before `session_start()`:
```php
session_set_cookie_params([
    'httponly' => true,
    'secure'   => true,      // Only send cookie over HTTPS
    'samesite' => 'Lax',
]);
```

On local XAMPP (HTTP), omit `'secure' => true` or the cookie won't work.

---

## 16. Separating Logic from Presentation

Avoid mixing SQL queries inside HTML output:

```php
// BAD — SQL mixed with markup
<html>
<?php
$result = $pdo->query("SELECT * FROM donations");
while ($row = $result->fetch()) {
    echo "<div>$row['title']</div>";
}
?>
</html>
```

**Better — fetch data first, then render:**
```php
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// 1. Fetch data
$stmt = $pdo->prepare("SELECT * FROM donations WHERE status = 'approved' ORDER BY created_at DESC LIMIT 20");
$stmt->execute();
$donations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<body>

<?php foreach ($donations as $donation): ?>
  <article class="donation-card">
    <h3><?= htmlspecialchars($donation['title']) ?></h3>
    <p><?= htmlspecialchars($donation['quantity_total']) ?> available</p>
  </article>
<?php endforeach; ?>

</body>
</html>
```

This keeps the file readable as the project grows.

---

## 17. CSS — Brand Tokens Stay in One File

Your brand palette lives in `assets/css/tokens.css` only:

```css
/* assets/css/tokens.css */
:root {
    --brand-primary:      #0F766E;
    --brand-primary-dark: #0B5D56;
    --brand-primary-light:#E8F6F3;

    --brand-accent:       #C94B32;
    --brand-accent-dark:  #A63C28;
    --brand-accent-light: #FFF0EC;

    --bg:           #FFFDFB;
    --surface:      #FFFFFF;
    --text:         #1F2937;
    --text-soft:    #475569;
    --text-muted:   #64748B;
    --border:       #E2E8F0;
    --border-strong:#CBD5E1;
}
```

Every page loads `tokens.css` first. No color hex values appear anywhere else.

---

## 18. Output Escaping — Always

Every piece of user-supplied data displayed in HTML must be escaped:

```php
// DANGEROUS — raw user data in HTML (XSS vulnerability)
echo $donation['title'];

// CORRECT
echo htmlspecialchars($donation['title'], ENT_QUOTES, 'UTF-8');

// Shorthand (define a helper)
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Usage
echo e($donation['title']);
```

---

## 19. First Technical Milestones (in order)

> [!IMPORTANT]
> Do not start with the homepage. Start with the foundation. The homepage is meaningless without a working backend.

**Milestone 1 — Environment**
- [ ] XAMPP installed and running (Apache + MySQL)
- [ ] `C:\xampp\htdocs\icareDonate\` folder created
- [ ] `http://localhost/icareDonate/` loads `index.php`

**Milestone 2 — Database**
- [ ] `icare_donate` database created in phpMyAdmin (utf8mb4)
- [ ] All tables created (see `DATABASE.md`)
- [ ] `config/database.php` written and tested

**Milestone 3 — Authentication**
- [ ] Signup form → PHP validates → hashed password saved
- [ ] Email verification (or skip for dev, implement for production)
- [ ] Login form → `password_verify()` → session set
- [ ] `requireLogin()` and `requireAdmin()` working in `includes/auth.php`
- [ ] Logout clears session

**Milestone 4 — First Vertical Slice**
- [ ] Donor registers and logs in
- [ ] Donor posts a donation → saved as `pending_verification`
- [ ] Admin logs in at `/admin/`
- [ ] Admin sees pending donation → approves it
- [ ] Donation status changes to `approved`, audit log written
- [ ] Requester registers and logs in
- [ ] Requester browses and finds the donation
- [ ] Requester submits request with proof upload
- [ ] Admin reviews and approves request
- [ ] Admin creates match → transaction created
- [ ] Requester sees claim instructions
- [ ] Claim recorded → admin confirms → status: `completed`
- [ ] Both users see transaction in history

**Everything else builds on this working slice.**

---

## 20. Local vs Production

| Setting | Local (XAMPP) | Production |
|---|---|---|
| DB host | `127.0.0.1` | Private network IP |
| DB password | `""` (empty) | Strong password |
| `APP_DEBUG` | `true` | `false` |
| `display_errors` | `On` | `Off` |
| Session `secure` | `false` | `true` |
| HTTPS | Not required | Required |
| `uploads/private/` | Not web-accessible | Not web-accessible |
| Error logging | Screen (dev) | Log file only |

> [!CAUTION]
> Never upload your local XAMPP config to production. Specifically: empty passwords, `display_errors = On`, and `APP_DEBUG = true` are development-only settings. Production deployments must use environment-specific configuration.

---

## 21. Converting the Wireframe (Recommended Steps)

```
iCareDonate wireframe.html   (single-file prototype)
              ↓
1. Identify the three areas: Public / User / Admin
              ↓
2. Extract brand CSS → assets/css/tokens.css + components.css
              ↓
3. Extract reusable header/footer → includes/header.php, footer.php
              ↓
4. Create PHP pages for each section
              ↓
5. Connect pages to backend API endpoints
              ↓
6. Replace static/placeholder data with real database queries
```

The wireframe's CSS is monochrome placeholder styling — **do not copy it into production CSS**. Use the design tokens from [`DESIGN_SYSTEM.md`](DESIGN_SYSTEM.md) and the palette from `iCareDonate-brand-foundation.html` instead.

---

## Related Documents

| Document | Purpose |
|---|---|
| [`DESIGN_SYSTEM.md`](DESIGN_SYSTEM.md) | Color tokens, typography, component specs, accessibility |
| [`BUSINESS_RULES.md`](BUSINESS_RULES.md) | Platform logic, status machines, privacy, security rules |
| [`DATABASE.md`](DATABASE.md) | Complete MySQL schema, migrations, file storage layout |
| [`SYSTEM_ARCHITECTURE.md`](SYSTEM_ARCHITECTURE.md) | Folder structure, service layer, PHP patterns, Git strategy |
| [`USER_FLOWS.md`](USER_FLOWS.md) | User journey diagrams, status machines, navigation maps |
| [`iCareDonate-brand-foundation.html`](iCareDonate-brand-foundation.html) | Live brand palette reference (light/dark mode) |
| [`docs/iCareDonate wireframe.html`](docs/iCareDonate%20wireframe.html) | Original wireframe / functional specification |
