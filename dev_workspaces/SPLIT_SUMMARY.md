# Dev Workspace Split Summary (v3 — Balanced Scope)

> Fair task distribution reflecting all recent frontend enhancements (Why iCareDonate standalone page, Community Donations rename & alignment, public navigation, and auth backend). Only files retyped on camera; pre-made shared tokens and assets are walked through without retyping.

## LOC Totals

| Developer | Lines of Code | Notes |
|-----------|---------------|-------|
| **Dev A MUST** | **2512** | Flagship homepage, Why iCareDonate, Categories, About, How It Works, Home CSS |
| Dev A IF-TIME | 978 | Mission, Contact, About CSS |
| **Dev A Total** | **3490** | Public marketing & community mission website |
| | | |
| **Dev B MUST (weighted)** | **2773** | PHP auth core (weighted 2×), Community Donations, Signup, FAQs, Auth CSS (Raw: 2189) |
| Dev B IF-TIME (weighted) | 1028 | Donation Details, Donations CSS, Form helpers (Raw: 1028) |
| **Dev B Total (weighted)** | **3801** | Authentication system & Community Donations (Raw: 3217) |
| | | |
| **MUST Delta** | **261 (9.4%)** | **Target < 10% ACHIEVED** |
| **Total Delta** | **311 (8.2%)** | **Target < 10% ACHIEVED** |
| **Raw LOC Delta** | **273 (7.8%)** | **Target < 10% ACHIEVED** |

---

## Shared Pre-Made Files (Walk Through, Don't Retype)

Shared files are kept in `dev_workspaces/shared/` and copied to both workspaces.

| File | LOC | Type | Notes |
|------|-----|------|-------|
| `frontend/css/variables.css` | 88 | CSS | Color tokens, typography, spacing scale |
| `frontend/css/base.css` | 348 | CSS | CSS reset, base elements, button styles |
| `frontend/css/layout.css` | 468 | CSS | Flexbox/grid utilities, containers, headers |
| `frontend/js/nav.js` | 97 | JS | Mobile hamburger menu toggle & ARIA logic |
| `frontend/includes/public-header.html` | 60 | HTML | Top navigation partial used across all pages |
| `frontend/includes/public-footer.html` | 78 | HTML | Footer partial with Community Donations & Why iCareDonate links |
| `frontend/assets/images/*` | — | Assets | Approved donations images, Ways to Help photos, logo SVG |
| **Shared Code Total** | **1141** | | Pre-built reference base for both developers |

---

## Dev A — Files (Public Marketing & Community Mission)

| File | LOC | Tier | Notes |
|------|-----|------|-------|
| `frontend/index.html` | 477 | MUST [M] | Homepage — hero with Why iCareDonate & Donate CTAs, clickable stats bar, 16:9 photo cards, fallback empty state |
| `frontend/why-icare-donate.html` | 564 | MUST [M] | Standalone Why iCareDonate — purpose, 4 core pillars, philosophy, community impact |
| `frontend/categories.html` | 620 | MUST [M] | Ways to Help & Community Needs — assistance types, filter tabs, donation link |
| `frontend/about.html` | 273 | MUST [M] | About Us — mission statement, team cards, community values |
| `frontend/how-it-works.html` | 281 | MUST [M] | How It Works — 5-step community donation and request workflow |
| `frontend/css/pages/home.css` | 297 | MUST [M] | Homepage-specific layout, hero gradients, and responsive styles |
| `frontend/mission.html` | 290 | IF-TIME | Mission statement & vision page |
| `frontend/contact.html` | 284 | IF-TIME | Contact form page (shared public contact) |
| `frontend/css/pages/about.css` | 404 | IF-TIME | About page timeline and team grid styles |

---

## Dev B — Files (Authentication & Community Donations)

| File | Raw LOC | Weighted | Tier | Notes |
|------|---------|----------|------|-------|
| `includes/auth.php` | 81 | 162 | MUST [M] | Core session auth helper (`requireLogin`, `isLoggedIn`, `currentRole`) — weighted 2× |
| `includes/demo_accounts.php` | 46 | 92 | MUST [M] | Demo credential store with bcrypt hashes — weighted 2× |
| `actions/login_process.php` | 72 | 144 | MUST [M] | POST handler: validates credentials, starts session — weighted 2× |
| `login.php` | 356 | 712 | MUST [M] | Main login page — PHP renders the login form with `$base` path — weighted 2× |
| `logout.php` | 29 | 58 | MUST [M] | Destroys session and redirects to login — weighted 2× |
| `frontend/donations.html` | 428 | 428 | MUST [M] | Community Donations browse page — search, filter bar, verified items count, donation cards |
| `frontend/signup.html` | 313 | 313 | MUST [M] | Sign-up form with demo-mode notice |
| `frontend/faqs.html` | 360 | 360 | MUST [M] | FAQ accordion page with community answers |
| `frontend/css/pages/auth.css` | 504 | 504 | MUST [M] | Login / signup card styles and split layout |
| `frontend/donation-details.html` | 330 | 330 | IF-TIME | Donation item details view & requester modal |
| `frontend/css/pages/donations.css` | 224 | 224 | IF-TIME | Community Donations listing, filter bar, and badge styles |
| `frontend/js/forms.js` | 474 | 474 | IF-TIME | Form validation + AJAX submission helpers |

---

## Pre-Shoot Checklist

| Item | Status |
|------|--------|
| Password hashes in `includes/demo_accounts.php` | ✅ Real bcrypt hashes (verified) |
| User portal gate `user/index.php` | ✅ Option B PHP session gate in place |
| Admin portal gate `admin/index.php` | ✅ Option B PHP session gate in place |
| Broken `../auth/login.html` in `frontend/user/dashboard.html` | ✅ Fixed → `../../logout.php` |
| Logout modal (sidebar) on user pages | ✅ JS overlay via `user-portal.js` |
| Logout modal (sidebar) on admin pages | ✅ JS overlay via `admin-portal.js` |
| Homepage hero buttons & stats bar link | ✅ "Why iCareDonate?" hero CTA & clickable stats bar to donations.html |
| Ways to Help cards | ✅ 16:9 photo headers with gradient overlay & tags |
| Community Donations page | ✅ Renamed from browse, aligned filter bar, "Request this item" cards |
| Standalone Why iCareDonate page | ✅ Verified responsive layout, 4 pillars, and Community Donations CTA |

---

## Copy-Back (After Video)

Run **Dev A first** (includes marketing pages and shared CSS/JS):
```powershell
xcopy /E /Y dev_workspaces\dev_a\rewrite\frontend frontend\
```

Then **Dev B** (includes auth backend, Community Donations, and forms):
```powershell
xcopy /E /Y dev_workspaces\dev_b\rewrite\frontend frontend\
xcopy /E /Y dev_workspaces\dev_b\rewrite\includes includes\
xcopy /E /Y dev_workspaces\dev_b\rewrite\actions actions\
copy /Y dev_workspaces\dev_b\rewrite\login.php login.php
copy /Y dev_workspaces\dev_b\rewrite\logout.php logout.php
```
