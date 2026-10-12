# iCareDonate — Master Architecture Plan & System Specification
**Project:** iCareDonate — Community Donation Management Platform  
**Tagline:** *"Give with care. Receive with dignity."*  
**Architecture:** Pure HTML5 Frontend + Vanilla CSS/JS + Plain PHP 8 (PDO) API + MySQL  
**Approved Strategy:** Choice 1 (Decoupled HTML Frontend + PHP Backend Endpoints)  
**Date Approved:** October 2026  

---

## 1. Architectural Overview & Philosophy

iCareDonate is structured with an absolute separation of concerns:
- **Presentation Layer (Frontend):** Pure HTML5 pages, styled with external CSS (`assets/css/style.css`), powered by Vanilla JavaScript (`assets/js/script.js`, `api-client.js`, `auth-guard.js`). No server-side PHP tags in client HTML files.
- **Service Layer (Backend API):** Modular PHP scripts in `api/` that receive requests, validate data, verify session permissions, and interact with MySQL using PDO prepared statements. Responses are standard JSON.
- **Persistence Layer (MySQL Database):** Relational tables with strict foreign keys, atomic transactions for item claims, and role-based access.

---

## 2. Directory Layout & Roles

```text
CommunityDono/
├── [Root Public HTML Pages]
│   ├── index.html, about.html, how-it-works.html, categories.html
│   ├── donations.html, donation-details.html, contact.html, help.html
│   ├── careers.html, terms.html, privacy.html, login.html, signup.html
│   ├── forgot-password.html, reset-password.html
│
├── user/                                # User Portal Views (.html)
│   ├── dashboard.html, profile.html, donations.html, post-donation.html
│   ├── edit-donation.html, requests.html, request-resource.html
│   ├── request-details.html, claims.html, transactions.html
│   ├── notifications.html, support.html, settings.html
│
├── admin/                               # Administrator Portal Views (.html)
│   ├── dashboard.html, donations.html, donation-details.html
│   ├── requests.html, request-details.html, claims.html, transactions.html
│   ├── interviews.html, users.html, user-details.html, support.html
│   ├── faqs.html, categories.html, reports.html, statistics.html, profile.html
│
├── assets/
│   ├── css/ (style.css, dashboard.css)
│   ├── js/ (script.js, api-client.js, auth-guard.js, user-dashboard.js, admin-dashboard.js)
│   └── images/ (icaredonate-icon.svg, ...)
│
├── api/                                 # PHP Backend Endpoints
│   ├── auth/ (login.php, signup.php, logout.php, check.php, forgot-password.php, reset-password.php)
│   ├── public/ (categories.php, donations.php, donation-details.php, faqs.php, contact.php)
│   ├── user/ (dashboard.php, profile.php, donations.php, donation-item.php, requests.php, ...)
│   └── admin/ (dashboard.php, donations.php, requests.php, claims.php, transactions.php, ...)
│
├── config/
│   └── database.php                     # PDO Connection Singleton
│
├── includes/
│   ├── auth.php                         # Server-side requireLogin() & requireRole()
│   ├── functions.php                    # Input sanitization, CSRF, response helpers
│   └── upload.php                       # Secure file validation & storage
│
├── database/
│   ├── schema.sql                       # Complete MySQL Schema
│   └── seed.sql                         # Categories, admin seed & starter records
│
├── uploads/
│   ├── donations/ (Item photos)
│   ├── proofs/ (Restricted proof-of-need documents)
│   └── avatars/ (User profile avatars)
│
└── docs/                                # Project Documentation & Reference Logs
```

---

## 3. Security Guidelines & Defense Talking Points

1. **Defense Point 1 — Server-Side Security Enforcement:**  
   Client-side JavaScript only manages UI convenience. True security is enforced on the PHP server. Every restricted endpoint runs `requireRole('admin')` or verifies `$_SESSION['user_id']`. If an unauthorized user attempts to call an admin API, the server terminates with HTTP 403 Forbidden.
2. **Defense Point 2 — SQL Injection Defense:**  
   100% of SQL queries with user inputs utilize PDO prepared statements with parameterized inputs.
3. **Defense Point 3 — Password Storage:**  
   Passwords are never stored in plaintext. They are encrypted using PHP's `password_hash()` (Bcrypt with automatic salt generation).
4. **Defense Point 4 — Secure File Handling:**  
   Uploaded item photos and proof documents are validated for MIME type, restricted to safe extensions, renamed with random hashes, and executed in directories protected from script execution. Proof-of-need files are strictly protected from public view.
5. **Defense Point 5 — Transaction Concurrency:**  
   When claiming items, database transactions (`$pdo->beginTransaction()`) ensure quantity cannot fall below zero or suffer from race conditions.
