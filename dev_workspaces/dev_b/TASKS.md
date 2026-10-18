# Dev B — Task List

> **Your role:** Authentication, user portal & donation/request flows.
> **Rewrite folder:** `dev_workspaces/dev_b/rewrite/`
> Work from **easy → hard**. Tick each checkbox when the file is finished.

---

## 📦 Dependencies to read first (in `reference/`)
- `frontend/css/variables.css` — All colour tokens and spacing scale.
- `frontend/includes/public-header.html` — Public nav (you use it on auth pages).
- `frontend/includes/user-sidebar.html` — User portal sidebar.
- `includes/auth.php` — Session auth helper used by `login.php`, `logout.php`, and PHP gates.

---

## ✅ Tier 1 — Easy (static / mostly markup)

| # | File | Lines | Notes |
|---|------|-------|-------|
| ☐ | `frontend/forgot-password.html` | ~80 | Simple form page |
| ☐ | `frontend/reset-password.html` | ~80 | Simple form page |
| ☐ | `includes/demo_accounts.php` | ~30 | Demo credential store |
| ☐ | `frontend/user/index.html` | 12 | Redirect stub |
| ☐ | `frontend/user/notifications.html` | varies | Notifications list |
| ☐ | `frontend/user/support.html` | 159 | User support page |

---

## ✅ Tier 2 — Medium (forms, auth logic, styled portals)

| # | File | Lines | Notes |
|---|------|-------|-------|
| ☐ | `frontend/login.html` | varies | Public login page (form only) |
| ☐ | `frontend/signup.html` | varies | Registration form |
| ☐ | `frontend/admin-login.html` | varies | Admin login page |
| ☐ | `frontend/includes/user-sidebar.html` | ~80 | User portal sidebar |
| ☐ | `frontend/includes/brand-sidebar.html` | ~50 | Brand sidebar variant |
| ☐ | `frontend/includes/topbar-user-btn.html` | ~30 | Topbar user button |
| ☐ | `actions/login_process.php` | varies | POST handler for login form |
| ☐ | `logout.php` | ~20 | Session destroy + redirect |
| ☐ | `frontend/user/settings.html` | varies | Account settings |
| ☐ | `frontend/user/profile.html` | 166 | User profile |
| ☐ | `frontend/user/impact.html` | varies | Impact stats |
| ☐ | `frontend/user/rewards.html` | varies | Reward badges |
| ☐ | `frontend/user/messages.html` | varies | Messages |
| ☐ | `frontend/css/pages/auth.css` | varies | Auth page styles |
| ☐ | `frontend/css/pages/forms.css` | varies | Form component styles |
| ☐ | `frontend/css/pages/user.css` | varies | User portal styles |
| ☐ | `frontend/css/components.css` | varies | Shared UI components |
| ☐ | `frontend/js/main.js` | varies | Site-wide JS init |
| ☐ | `frontend/js/forms.js` | varies | Form validation + submission |

---

## ✅ Tier 3 — Hard (complex flows, data, JS state)

| # | File | Lines | Notes |
|---|------|-------|-------|
| ☐ | `includes/auth.php` | varies | Core session auth helper |
| ☐ | `login.php` | varies | PHP session login endpoint |
| ☐ | `user/index.php` | varies | Session gate (PHP) |
| ☐ | `frontend/donate.html` | varies | Donation submission form |
| ☐ | `frontend/request.html` | varies | Request submission form |
| ☐ | `frontend/donations.html` | varies | Public donations browse list |
| ☐ | `frontend/donation-details.html` | varies | Single donation detail |
| ☐ | `frontend/user/dashboard.html` | varies | User home dashboard |
| ☐ | `frontend/user/my-donations.html` | varies | User's own donations |
| ☐ | `frontend/user/my-requests.html` | varies | User's own requests |
| ☐ | `frontend/user/history.html` | varies | Activity history |
| ☐ | `frontend/user/browse-donations.html` | 211 | Browse public donations |
| ☐ | `frontend/user/donations.html` | 171 | User donations view |
| ☐ | `frontend/user/edit-donation.html` | 197 | Edit a donation |
| ☐ | `frontend/user/offer-donation.html` | 270 | Offer a donation form |
| ☐ | `frontend/user/request-details.html` | 175 | Request detail view |
| ☐ | `frontend/user/request-donation.html` | 179 | Request a donation form |
| ☐ | `frontend/user/requests.html` | 148 | User requests list |
| ☐ | `frontend/user/transactions.html` | 185 | Transactions list |
| ☐ | `frontend/css/pages/donations.css` | varies | Donation page styles |
| ☐ | `frontend/js/donations.js` | varies | Donation list filtering JS |
| ☐ | `frontend/js/user-portal.js` | varies | User portal JS (logout modal, etc.) |
| ☐ | `frontend/js/data.js` | varies | API/data fetch helpers |
| ☐ | `frontend/js/mock-data.js` | varies | Mock dataset for demo |

---

## 📋 Copy-back instructions
When ready for the final video, copy your rewrite files back:
```
xcopy /E /Y dev_workspaces\dev_b\rewrite\frontend frontend\
xcopy /E /Y dev_workspaces\dev_b\rewrite\includes includes\
xcopy /E /Y dev_workspaces\dev_b\rewrite\actions actions\
copy dev_workspaces\dev_b\rewrite\login.php login.php
copy dev_workspaces\dev_b\rewrite\logout.php logout.php
xcopy /E /Y dev_workspaces\dev_b\rewrite\user user\
```