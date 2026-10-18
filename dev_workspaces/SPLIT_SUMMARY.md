# Dev Workspace Split Summary (v2 — Narrow Scope)

> Only files retyped on camera. Everything else (portals, error pages, etc.) is not copied.

## LOC Totals

| Developer | Lines of Code | Notes |
|-----------|---------------|-------|
| Dev A exclusive | 3197 | Public marketing pages + page CSS |
| Dev A shared ownership | 17638 | CSS tokens, nav.js, header/footer partials |
| **Dev A Total** | **20835** | |
| **Dev B Total (weighted)** | **3443** | PHP files counted 2× for difficulty |
| Delta | 17392 (143.3%) | Target < 10% |

---

## Dev A — Files

| File | LOC | Tier | Notes |
|------|-----|------|-------|
| `frontend/css/variables.css` | 100 | SHARED |  |
| `frontend/css/layout.css` | 539 | SHARED |  |
| `frontend/css/base.css` | 406 | SHARED |  |
| `frontend/js/nav.js` | 195 | SHARED |  |
| `frontend/includes/public-header.html` | 64 | SHARED |  |
| `frontend/includes/public-footer.html` | 82 | SHARED |  |
| `frontend/assets/images/logo.svg` | 9 | SHARED |  |
| `frontend/assets/images/placeholder.svg` | 8 | SHARED |  |
| `frontend/assets/images/what-we-do.jpg` | 1272 | SHARED |  |
| `frontend/assets/images/who-we-serve.jpg` | 471 | SHARED |  |
| `frontend/assets/images/03_what_we_do.jpg` | 1272 | SHARED |  |
| `frontend/assets/images/04_who-we-serve.jpg` | 471 | SHARED |  |
| `frontend/assets/images/05_emergency-disaster-relief.jpg` | 1891 | SHARED |  |
| `frontend/assets/images/06_health-care-medical-aid.png` | 7245 | SHARED |  |
| `frontend/assets/images/07_education-youth-support.jpg` | 789 | SHARED |  |
| `frontend/assets/images/login-brand.jpg` | 726 | SHARED |  |
| `frontend/assets/images/categories/clothing-shoes.avif` | 331 | SHARED |  |
| `frontend/assets/images/categories/electronics-gadgets.avif` | 60 | SHARED |  |
| `frontend/assets/images/categories/emergency-disaster-relief.jpg` | 383 | SHARED |  |
| `frontend/assets/images/categories/food-groceries.jpg` | 407 | SHARED |  |
| `frontend/assets/images/categories/medical-first-aid.jpg` | 208 | SHARED |  |
| `frontend/assets/images/categories/other-essentials.jpg` | 139 | SHARED |  |
| `frontend/assets/images/categories/school-supplies.jpg` | 294 | SHARED |  |
| `frontend/assets/images/categories/toys-games.avif` | 276 | SHARED |  |
| `frontend/index.html` | 507 | MUST | Homepage — hero, stats, CTA sections |
| `frontend/about.html` | 309 | MUST | About us — mission, team, values |
| `frontend/categories.html` | 657 | MUST | Ways to Help & Community Needs |
| `frontend/how-it-works.html` | 304 | MUST | 5-step community workflow page |
| `frontend/mission.html` | 321 | IF-TIME | Mission statement & vision |
| `frontend/contact.html` | 310 | IF-TIME | Contact form page (shared with Dev B) |
| `frontend/css/pages/home.css` | 335 | MUST | Homepage-specific styles |
| `frontend/css/pages/about.css` | 454 | IF-TIME | About page styles |
| `frontend/js/categories.js` | 0 | IF-TIME | Category filter/tab logic |


---

## Dev B — Files

| File | Raw LOC | Weighted | Tier | Notes |
|------|---------|----------|------|-------|
| `includes/auth.php` | 91 | 182 | MUST | Core session auth helper (requireLogin, isLoggedIn, currentRole) |
| `includes/demo_accounts.php` | 48 | 96 | MUST | Demo credential store with bcrypt hashes |
| `actions/login_process.php` | 85 | 170 | MUST | POST handler: validates credentials, starts session |
| `login.php` | 396 | 792 | MUST | Main login page — PHP renders the login form |
| `logout.php` | 34 | 68 | MUST | Destroys session and redirects to login |
| `frontend/signup.html` | 352 | 352 | MUST | Sign-up form with demo-mode notice |
| `frontend/faqs.html` | 392 | 392 | MUST | FAQ accordion page |
| `frontend/contact.html` | 310 | 310 | IF-TIME | Contact form (if Dev A doesn't finish it first) |
| `frontend/css/pages/auth.css` | 547 | 547 | MUST | Login/signup page styles |
| `frontend/js/forms.js` | 534 | 534 | IF-TIME | Form validation + AJAX submission helpers |


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

---

## Copy-Back (After Video)

Run **Dev A first** (includes shared CSS/JS):
```powershell
xcopy /E /Y dev_workspaces\dev_a\rewrite\frontend frontend\
```

Then **Dev B:**
```powershell
xcopy /E /Y dev_workspaces\dev_b\rewrite\frontend frontend\
xcopy /E /Y dev_workspaces\dev_b\rewrite\includes includes\
xcopy /E /Y dev_workspaces\dev_b\rewrite\actions actions\
copy /Y dev_workspaces\dev_b\rewrite\login.php login.php
copy /Y dev_workspaces\dev_b\rewrite\logout.php logout.php
```
