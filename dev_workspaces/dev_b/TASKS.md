# Dev B — Task List
**Role:** Authentication system + Community Donations & supporting public pages  
**Rewrite folder:** `dev_workspaces/dev_b/rewrite/`  
**Time budget:** ~50–60 min  
Work from MUST [M] → IF-TIME. Tick each checkbox when done.

---

## ⚙️ Shared files — walk through, don't retype
These are pre-made in `shared/`. Spend ~1 min each on camera.

| File | What to explain |
|------|-----------------|
| `shared/frontend/css/variables.css` | Color tokens used on login and donations pages |
| `shared/frontend/includes/public-header.html` | Nav that appears above auth and donations pages |
| `shared/frontend/includes/public-footer.html` | Footer that appears at bottom of all pages |

---

## ✅ MUST [M] — Do these first (PHP auth + Community Donations)

| ☐ | File | Raw LOC | Weighted | Tier | Notes |
|---|------|---------|----------|------|-------|
| ☐ | `includes/demo_accounts.php` | 46 | 92 | MUST [M] | Demo credential store with bcrypt hashes (weighted 2×) |
| ☐ | `includes/auth.php` | 81 | 162 | MUST [M] | Core session auth helper (`requireLogin`, `isLoggedIn`, `currentRole`) (weighted 2×) |
| ☐ | `logout.php` | 29 | 58 | MUST [M] | Destroys session and redirects to login (weighted 2×) |
| ☐ | `actions/login_process.php` | 72 | 144 | MUST [M] | POST handler: validates credentials, starts session (weighted 2×) |
| ☐ | `login.php` | 356 | 712 | MUST [M] | Main login page — PHP renders the login form with `$base` (weighted 2×) |
| ☐ | `frontend/donations.html` | 428 | 428 | MUST [M] | Community Donations browse page — filter bar, dynamic verified donations count, item cards |
| ☐ | `frontend/signup.html` | 313 | 313 | MUST [M] | Sign-up form with demo-mode notice banner |
| ☐ | `frontend/faqs.html` | 360 | 360 | MUST [M] | FAQ accordion page with community Q&A |
| ☐ | `frontend/css/pages/auth.css` | 504 | 504 | MUST [M] | Login / signup card styles and responsive split layout |

**Order recommendation:**
1. `includes/demo_accounts.php` — smallest; introduces credential structure
2. `includes/auth.php` — core helper functions (`isLoggedIn`, `requireLogin`, `currentRole`)
3. `logout.php` — short; explain `session_destroy()` + redirect
4. `actions/login_process.php` — POST handler; explain `password_verify()` + session start
5. `login.php` — full PHP page; explain `$base` path-building and form rendering
6. `frontend/donations.html` — public Community Donations browse page with aligned filter bar and donation cards
7. `frontend/signup.html` — HTML form + demo-mode notice banner
8. `frontend/faqs.html` — accordion page
9. `frontend/css/pages/auth.css` — login/signup styles

**Subtotal MUST [M] (Weighted):** 2,773 LOC (Raw: 2,189 LOC)

---

## ⏱ IF-TIME — Do these if you finish early

| ☐ | File | Raw LOC | Weighted | Tier | Notes |
|---|------|---------|----------|------|-------|
| ☐ | `frontend/donation-details.html` | 330 | 330 | IF-TIME | Donation item details view & requester modal |
| ☐ | `frontend/css/pages/donations.css` | 224 | 224 | IF-TIME | Community Donations listing, filter bar, and badge styles |
| ☐ | `frontend/js/forms.js` | 474 | 474 | IF-TIME | Form validation + AJAX submission helpers |

**Subtotal IF-TIME:** 1,028 LOC  
**Dev B Total (Weighted):** 3,801 LOC (Raw: 3,217 LOC)

---

## 📋 Copy-back (end of video)
```powershell
xcopy /E /Y dev_workspaces\dev_b\rewrite\frontend frontend\
xcopy /E /Y dev_workspaces\dev_b\rewrite\includes includes\
xcopy /E /Y dev_workspaces\dev_b\rewrite\actions actions\
copy /Y dev_workspaces\dev_b\rewrite\login.php login.php
copy /Y dev_workspaces\dev_b\rewrite\logout.php logout.php
```

---

## 💡 Learning notes
- PHP files use `<?php ... ?>` tags. `login.php` uses short-echo `<?= $var ?>` to print variables into HTML.
- `session_start()` must be the FIRST thing in every PHP file that reads sessions.
- `password_verify($input, $hash)` returns `true/false` — never compare passwords with `==`.
- The `$base` variable in `login.php` is built so that CSS/JS paths work whether the server is at `/` or `/iCareDonate/`. Look at how it's set at the top of `login.php`.
- `requireLogin()` in `auth.php` calls `die()` / `header('Location:')` + `exit` — order matters.
- `frontend/donations.html` displays verified community donations with aligned filter controls and dynamic counts.