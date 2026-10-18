# Dev A — Task List
**Role:** Public marketing website  
**Rewrite folder:** `dev_workspaces/dev_a/rewrite/`  
**Time budget:** ~50–60 min  
Work from MUST → IF-TIME. Tick each checkbox when done.

---

## ⚙️ Shared files — walk through, don't retype
These are pre-made. Spend ~2 min explaining each one on camera, then paste them in.

| File | What to explain |
|------|-----------------|
| `shared/frontend/css/variables.css` | Colour tokens, spacing scale |
| `shared/frontend/css/layout.css` | Grid helpers, container widths |
| `shared/frontend/css/base.css` | CSS reset, typography |
| `shared/frontend/js/nav.js` | Mobile nav toggle logic |
| `shared/frontend/includes/public-header.html` | Nav used on every page |
| `shared/frontend/includes/public-footer.html` | Footer used on every page |

After explaining, copy these from `shared/` into `rewrite/` (or paste them in your editor).

---

## ✅ MUST — Do these first

| ☐ | File | Raw LOC | Notes |
|----|------|---------|-------|| ☐ | `frontend/index.html` | 507 | Homepage — hero, stats, CTA sections |
| ☐ | `frontend/about.html` | 309 | About us — mission, team, values |
| ☐ | `frontend/categories.html` | 657 | Ways to Help & Community Needs |
| ☐ | `frontend/how-it-works.html` | 304 | 5-step community workflow page |
| ☐ | `frontend/css/pages/home.css` | 335 | Homepage-specific styles |

**Order recommendation:**
1. Start with `css/pages/home.css` — establishes your colour/layout vocabulary
2. `index.html` — the flagship page; explain header/footer once here
3. `about.html` — similar structure, move faster
4. `categories.html` — card grid; explain filter tabs
5. `how-it-works.html` — simplest structure (numbered steps)

---

## ⏱ IF-TIME — Do these if you finish early

| ☐ | File | Raw LOC | Notes |
|----|------|---------|-------|| ☐ | `frontend/mission.html` | 321 | Mission statement & vision |
| ☐ | `frontend/contact.html` | 310 | Contact form page (shared with Dev B) |
| ☐ | `frontend/css/pages/about.css` | 454 | About page styles |
| ☐ | `frontend/js/categories.js` | 0 | Category filter/tab logic |

---

## 📋 Copy-back (end of video)
```powershell
xcopy /E /Y dev_workspaces\dev_a\rewrite\frontend frontend\
```
> **Do Dev A's copy FIRST** — shared CSS/JS live here.

---

## 💡 Learning notes
- Every page starts with: `<!DOCTYPE html>` → `<head>` (imports variables.css, layout.css, page-specific CSS) → copy-paste header partial → unique `<main>` content → copy-paste footer partial → `<script>` imports.
- Variables like `--color-primary` and `--space-*` are defined in `variables.css`. Use them instead of hardcoding colours.
- `nav.js` handles the mobile hamburger. You never need to rewrite it — just `<script src="js/nav.js"></script>`.