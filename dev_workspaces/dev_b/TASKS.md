# Dev B — Task List
**Role:** Authentication system + supporting public pages  
**Rewrite folder:** `dev_workspaces/dev_b/rewrite/`  
**Time budget:** ~50–60 min  
Work from MUST → IF-TIME. Tick each checkbox when done.

---

## ⚙️ Shared files — walk through, don't retype
These are pre-made. Spend ~1 min each on camera.

| File | What to explain |
|------|-----------------|
| `shared/frontend/css/variables.css` | Colour tokens used on login page |
| `shared/frontend/includes/public-header.html` | Nav that appears above login form |

---

## ✅ MUST — Do these first (PHP auth — the hard part)

| ☐ | File | Raw LOC | Weighted | Notes |
|----|------|---------|----------|-------|| ☐ | `includes/auth.php` | 91 | 182 | Core session auth helper (requireLogin, isLoggedIn, currentRole) |
| ☐ | `includes/demo_accounts.php` | 48 | 96 | Demo credential store with bcrypt hashes |
| ☐ | `actions/login_process.php` | 85 | 170 | POST handler: validates credentials, starts session |
| ☐ | `login.php` | 396 | 792 | Main login page — PHP renders the login form |
| ☐ | `logout.php` | 34 | 68 | Destroys session and redirects to login |
| ☐ | `frontend/signup.html` | 352 | 352 | Sign-up form with demo-mode notice |
| ☐ | `frontend/faqs.html` | 392 | 392 | FAQ accordion page |
| ☐ | `frontend/css/pages/auth.css` | 547 | 547 | Login/signup page styles |

**Order recommendation:**
1. `includes/demo_accounts.php` — smallest; introduces the credential structure
2. `includes/auth.php` — core helper functions (`isLoggedIn`, `requireLogin`, `currentRole`)
3. `logout.php` — short; explain `session_destroy()` + redirect
4. `actions/login_process.php` — POST handler; explain `password_verify()` + session start
5. `login.php` — the full PHP page; explain `$base` path-building and the form
6. `frontend/signup.html` — HTML form + demo-mode notice banner
7. `frontend/faqs.html` — accordion page
8. `frontend/css/pages/auth.css` — login/signup styles

---

## ⏱ IF-TIME — Do these if you finish early

| ☐ | File | Raw LOC | Weighted | Notes |
|----|------|---------|----------|-------|| ☐ | `frontend/contact.html` | 310 | 310 | Contact form (if Dev A doesn't finish it first) |
| ☐ | `frontend/js/forms.js` | 534 | 534 | Form validation + AJAX submission helpers |

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