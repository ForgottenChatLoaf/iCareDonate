# Dev A — Task List

> **Your role:** Public website & admin portal.
> **Rewrite folder:** `dev_workspaces/dev_a/rewrite/`
> Work from **easy → hard**. Tick each checkbox when the file is finished.

---

## 📦 Dependencies to read first (in `reference/`)
- `frontend/css/variables.css` — All colour tokens and spacing scale you will use everywhere.
- `frontend/includes/public-header.html` — The shared nav you paste into every public page.
- `frontend/includes/public-footer.html` — Shared footer.
- `frontend/includes/admin-sidebar.html` — Admin sidebar used in every admin page.

---

## ✅ Tier 1 — Easy (static / mostly markup)

| # | File | Lines | Notes |
|---|------|-------|-------|
| ☐ | `frontend/403.html` | ~30 | Simple error page |
| ☐ | `frontend/404.html` | ~30 | Simple error page |
| ☐ | `frontend/privacy.html` | ~60 | Static legal text |
| ☐ | `frontend/terms.html` | ~60 | Static legal text |
| ☐ | `frontend/mission.html` | ~80 | Static mission statement |
| ☐ | `frontend/careers.html` | ~100 | Static careers page |
| ☐ | `frontend/contact-support.html` | ~80 | Redirect/support page |
| ☐ | `frontend/help-center.html` | ~120 | Static FAQ-style help |
| ☐ | `frontend/faqs.html` | ~130 | Static FAQ accordions |
| ☐ | `frontend/about.html` | ~150 | About us page |

---

## ✅ Tier 2 — Medium (styled sections + nav)

| # | File | Lines | Notes |
|---|------|-------|-------|
| ☐ | `frontend/css/variables.css` | 100 | **Shared** — colour/spacing tokens |
| ☐ | `frontend/css/base.css` | 406 | **Shared** — resets, typography |
| ☐ | `frontend/css/layout.css` | 539 | **Shared** — grid/flex helpers |
| ☐ | `frontend/js/nav.js` | 195 | **Shared** — mobile nav toggle |
| ☐ | `frontend/js/theme.js` | 33 | **Shared** — dark-mode toggle |
| ☐ | `frontend/includes/public-header.html` | 64 | **Shared** — top nav |
| ☐ | `frontend/includes/public-footer.html` | 82 | **Shared** — footer |
| ☐ | `frontend/contact.html` | ~180 | Contact form |
| ☐ | `frontend/how-it-works.html` | ~200 | 5-step process page |
| ☐ | `frontend/categories.html` | ~350 | Ways to Help & Community Needs |
| ☐ | `frontend/css/pages/home.css` | varies | Homepage styles |
| ☐ | `frontend/css/pages/about.css` | varies | About page styles |
| ☐ | `frontend/css/pages/careers.css` | varies | Careers styles |
| ☐ | `frontend/css/pages/contact.css` | varies | Contact styles |
| ☐ | `frontend/css/pages/help-center.css` | varies | Help center styles |
| ☐ | `frontend/css/pages/legal.css` | varies | Privacy/Terms styles |
| ☐ | `frontend/css/pages/contact-support.css` | varies | Support page styles |
| ☐ | `frontend/js/categories.js` | varies | Category filter JS |

---

## ✅ Tier 3 — Hard (dashboard UI, data tables, JS logic)

| # | File | Lines | Notes |
|---|------|-------|-------|
| ☐ | `frontend/index.html` | ~400 | Homepage — hero + sections |
| ☐ | `frontend/includes/admin-sidebar.html` | ~80 | Admin nav |
| ☐ | `frontend/admin/dashboard.html` | 12 | Redirect stub |
| ☐ | `frontend/admin/index.html` | varies | Main admin dashboard |
| ☐ | `frontend/admin/users.html` | varies | User list |
| ☐ | `frontend/admin/user-detail.html` | 474 | Single user detail |
| ☐ | `frontend/admin/donations.html` | varies | Donations table |
| ☐ | `frontend/admin/donation-review.html` | 411 | Review a donation |
| ☐ | `frontend/admin/requests.html` | varies | Requests table |
| ☐ | `frontend/admin/request-review.html` | 248 | Review a request |
| ☐ | `frontend/admin/reports.html` | varies | Reports page |
| ☐ | `frontend/admin/analytics.html` | varies | Charts & analytics |
| ☐ | `frontend/admin/audit-logs.html` | varies | Audit log table |
| ☐ | `frontend/admin/statistics.html` | 255 | Statistics page |
| ☐ | `frontend/admin/inventory.html` | varies | Inventory page |
| ☐ | `frontend/admin/distribution.html` | varies | Distribution mgmt |
| ☐ | `frontend/admin/communications.html` | varies | Comms page |
| ☐ | `frontend/admin/volunteers.html` | varies | Volunteer mgmt |
| ☐ | `frontend/admin/finance.html` | varies | Finance page |
| ☐ | `frontend/admin/system-health.html` | varies | System health |
| ☐ | `frontend/admin/notifications.html` | varies | Notifications |
| ☐ | `frontend/admin/settings.html` | varies | Admin settings |
| ☐ | `frontend/admin/admins.html` | 189 | Admin list |
| ☐ | `frontend/admin/categories.html` | 178 | Category management |
| ☐ | `frontend/admin/claims.html` | 180 | Claims management |
| ☐ | `frontend/admin/faqs.html` | 182 | FAQ management |
| ☐ | `frontend/admin/inquiries.html` | 172 | Inquiries list |
| ☐ | `frontend/admin/interviews.html` | 241 | Interviews page |
| ☐ | `frontend/admin/logs.html` | 291 | Detailed logs |
| ☐ | `frontend/admin/matches.html` | 178 | Matches page |
| ☐ | `frontend/admin/moderation.html` | 252 | Moderation queue |
| ☐ | `frontend/admin/profile.html` | 158 | Admin profile |
| ☐ | `frontend/admin/support.html` | 172 | Support tickets |
| ☐ | `frontend/admin/transactions.html` | 249 | Transaction list |
| ☐ | `frontend/admin/login.html` | 412 | Admin-specific login |
| ☐ | `admin/index.php` | varies | Session gate (PHP) |
| ☐ | `frontend/css/pages/admin.css` | varies | Admin portal styles |
| ☐ | `frontend/js/admin-portal.js` | varies | Admin JS (sidebar, modals, logout) |
| ☐ | `frontend/js/admin-data.js` | varies | Admin mock data |

---

## 📋 Copy-back instructions
When ready for the final video, copy your rewrite files back:
```
xcopy /E /Y dev_workspaces\dev_a\rewrite\frontend frontend\
xcopy /E /Y dev_workspaces\dev_a\rewrite\admin admin\
```