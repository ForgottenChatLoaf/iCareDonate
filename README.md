# iCareDonate

A community-driven donation and assistance coordination platform designed to connect generous donors with community members in need. Give what you can. Receive what you need.

---

## Table of Contents

- [About the Platform](#about-the-platform)
- [Technology Stack](#technology-stack)
- [Directory Structure](#directory-structure)
- [Getting Started Locally](#getting-started-locally)
  - [Option A: Static Frontend Server (Quickest)](#option-a-static-frontend-server-quickest)
  - [Option B: Local Apache / PHP Server (XAMPP)](#option-b-local-apache--php-server-xampp)
- [Prototype Authentication & Demo Accounts](#prototype-authentication--demo-accounts)
- [Feature Status](#feature-status)
- [Planned Backend & Database Roadmap](#planned-backend--database-roadmap)
- [Deployment](#deployment)

---

## About the Platform

**iCareDonate** bridges the gap between donors with surplus essential goods and individuals or grassroots groups requesting aid. The platform supports diverse categories:

- 🍲 Food & Groceries
- 📚 School & Learning Supplies
- 🩺 Health & First Aid
- 👕 Clothing & Footwear
- 🚨 Emergency & Disaster Relief

---

## Technology Stack

The repository reflects an evolving architecture with a complete, production-ready frontend prototype and modular PHP gates:

- **Frontend Core**: Semantic HTML5 and Vanilla CSS3.
- **Design System**: Custom design tokens utilizing CSS custom properties (`css/variables.css`), responsive layouts (`css/layout.css`), accessible UI components (`css/components.css`), and zero heavy framework dependencies.
- **Client-Side Logic**: Vanilla JavaScript (ES6+) for interactive features, client validation, category filtering, search, and session handling.
- **Backend / PHP Layer**: Native PHP (currently providing session management, role routing, and gate helpers in `actions/`, `admin/`, `user/`, and `includes/`), prepared for full MySQL connection.
- **Static Hosting Configuration**: Configured with `netlify.toml` and `frontend/_redirects` to publish the `frontend/` directory cleanly.

---

## Directory Structure

```text
iCareDonate/
├── actions/                  # PHP server-side form action processors
│   └── login_process.php     # Session login handler
├── admin/                    # PHP gate for admin portal routes
│   └── index.php             # Protected admin access controller
├── user/                     # PHP gate for user portal routes
│   └── index.php             # Protected user access controller
├── includes/                 # Shared PHP authentication and demo configuration
│   ├── auth.php              # Session checks (requireLogin, requireAdmin)
│   └── demo_accounts.php     # Pre-configured demonstration credentials
├── frontend/                 # Core client application (published directory)
│   ├── index.html            # Public landing page
│   ├── about.html            # Mission, story, and values
│   ├── categories.html       # Categories and ways to help
│   ├── how-it-works.html     # User workflow explanation
│   ├── donations.html        # Public donation browsing
│   ├── donation-details.html # Detailed donation item view
│   ├── donate.html           # Public donation initiation
│   ├── request.html          # Public assistance request
│   ├── contact.html          # Contact form and support
│   ├── faqs.html             # Frequently asked questions
│   ├── login.html            # Standard member sign-in page
│   ├── signup.html           # Member registration page
│   ├── admin-login.html      # Administrative sign-in portal
│   ├── user-dashboard.html   # Primary user dashboard
│   ├── admin-dashboard.html  # Primary administrator dashboard
│   ├── logout.html           # Client-side session clearing handler
│   ├── _redirects            # Routing redirects for static hosts
│   ├── user/                 # Sub-pages for user portal (requests, profile, etc.)
│   ├── admin/                # Sub-pages for admin portal (review, moderation, logs)
│   ├── css/                  # Modular stylesheet architecture
│   ├── js/                   # Frontend scripts (forms.js, nav.js, portals, data)
│   └── assets/               # Brand SVGs, icons, and illustrations
├── login.php                 # PHP-rendered login page with server session state
├── logout.php                # PHP-rendered logout handler
├── netlify.toml              # Netlify build configuration
└── .gitignore                # Git exclusions configuration
```

---

## Getting Started Locally

### Option A: Static Frontend Server (Quickest)

You can run the frontend prototype without a database or PHP runtime:

1. **Direct Browser Access**: Open `frontend/index.html` directly in any modern browser (Chrome, Edge, Firefox, Safari).
2. **VS Code Live Server**: Right-click `frontend/index.html` and select **Open with Live Server**.
3. **Node.js**:
   ```bash
   npx serve frontend
   ```
4. **Python**:
   ```bash
   python -m http.server 8000 --directory frontend
   ```
   Then navigate to `http://localhost:8000`.

### Option B: Local Apache / PHP Server (XAMPP)

1. Clone or copy the repository folder into your XAMPP web root:
   ```text
   C:\xampp\htdocs\iCareDonate
   ```
2. Start the **Apache** service in the XAMPP Control Panel.
3. Open your browser and navigate to:
   ```text
   http://localhost/iCareDonate/login.php
   ```
   or view the static frontend directly at:
   ```text
   http://localhost/iCareDonate/frontend/
   ```

---

## Prototype Authentication & Demo Accounts

For demonstration and defense presentation purposes, the frontend includes a prototype client authentication engine in `frontend/js/forms.js` using browser `localStorage` (`icd_token`, `icd_user`):

### Quick Test Accounts

| Role | Email | Password | Destination |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@icaredonate.test` | `Admin@1234` | Admin Dashboard |
| **Regular User / Requester** | `user@icaredonate.test` | `User@1234` | User Dashboard |
| **Donor** | `donor@icaredonate.test` | `Donor@1234` | User Dashboard |

- **Universal Evaluation Mode**: Testers and evaluators can also enter any valid email address and password on `login.html` to instantly initialize a test session. If the entered email contains `admin`, administrative privileges will be assigned.
- **Logout**: Clicking **Log out** in any sidebar, topbar menu, or public navigation clears `localStorage`, `sessionStorage`, and redirects back to `login.html`.

---

## Feature Status

| Feature Area | Status | Implementation Details |
| :--- | :--- | :--- |
| **Public Information Pages** | Complete | Semantic, accessible pages with sticky navigation and mobile drawer |
| **Responsive Design** | Complete | Custom CSS variables; fluid mobile, tablet, and desktop layouts |
| **Client Form Validation** | Complete | Real-time input checking, error states, and image upload preview |
| **Donation Catalog & Filtering** | Complete | Client-side search, category filter, and status display |
| **Client Authentication & Sessions** | Complete | Mock storage via `localStorage` with full login/logout flows |
| **Role-Based Views** | Complete | Distinct dashboards and navigations for Donors, Beneficiaries, and Admins |
| **Persistent Database Storage** | Planned | Schemas and MySQL migration architecture documented in `db_backend_roadmap.md` |
| **Server-Side REST APIs** | Planned | Endpoints for `/v1/auth`, `/v1/donations`, `/v1/requests` |

---

## Planned Backend & Database Roadmap

As detailed in `db_backend_roadmap.md` and `iCareDonate_PreFinal_Implementation_Plan.md`, future milestones will introduce:

1. **MySQL Database**: Centralized tables for `users`, `donations`, `requests`, `categories`, and `audit_logs`.
2. **REST API**: JSON endpoints replacing client-side mock datasets.
3. **Password Security**: Transition from client mocks to `password_hash()` and `password_verify()` via PHP backend.
4. **Cloud Media Storage**: Secure storage for verification documents and uploaded item photos.

---

## Deployment

The application is pre-configured for static host deployment (e.g. Netlify, Vercel, or GitHub Pages):

- The `netlify.toml` file designates `frontend` as the published root directory.
- `frontend/_redirects` ensures seamless fallback routing between PHP and HTML endpoints.
