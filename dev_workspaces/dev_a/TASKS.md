# Dev A — Task List
**Role:** Public marketing & community mission website  
**Rewrite folder:** `dev_workspaces/dev_a/rewrite/`  
**Time budget:** ~50–60 min  
Work from MUST [M] → IF-TIME. Tick each checkbox when done.

---

## ⚙️ Shared files — walk through, don't retype
These are pre-made in `shared/`. Spend ~1–2 min explaining each one on camera, then paste them in.

| File | What to explain |
|------|-----------------|
| `shared/frontend/css/variables.css` | Color tokens (navy #14213D, cobalt #1F4FA3, coral), typography (Inter/Poppins), spacing scale |
| `shared/frontend/css/layout.css` | Grid helpers, container widths, flex alignments |
| `shared/frontend/css/base.css` | CSS reset, base elements, button variants |
| `shared/frontend/js/nav.js` | Mobile navigation hamburger toggle & accessibility ARIA attributes |
| `shared/frontend/includes/public-header.html` | Public navigation bar used across all pages |
| `shared/frontend/includes/public-footer.html` | Footer used on every page (with Community Donations & Why iCareDonate links) |

After explaining, copy these from `shared/` into `rewrite/` (or paste them in your editor).

---

## ✅ MUST [M] — Do these first

| ☐ | File | Raw LOC | Tier | Notes |
|---|------|---------|------|-------|
| ☐ | `frontend/css/pages/home.css` | 297 | MUST [M] | Homepage-specific hero gradients, stats bar, and layout styles |
| ☐ | `frontend/index.html` | 477 | MUST [M] | Flagship homepage — hero CTA buttons, clickable stats bar, 16:9 photo cards, fallback empty state |
| ☐ | `frontend/why-icare-donate.html` | 564 | MUST [M] | Standalone Why iCareDonate — purpose, 4 core pillars, philosophy, community impact |
| ☐ | `frontend/about.html` | 273 | MUST [M] | About Us — mission statement, team cards, community values |
| ☐ | `frontend/categories.html` | 620 | MUST [M] | Ways to Help & Community Needs — assistance types, filter tabs, donation link |
| ☐ | `frontend/how-it-works.html` | 281 | MUST [M] | How It Works — 5-step community donation and request workflow |

**Order recommendation:**
1. Start with `css/pages/home.css` — establishes your color/layout vocabulary
2. `index.html` — the flagship page; explain header/footer partials once here
3. `why-icare-donate.html` — newly added standalone page; highlights community purpose & 4 pillars
4. `about.html` — similar structure, move faster
5. `categories.html` — card grid; explain category focus tabs and links
6. `how-it-works.html` — clean sequential structure (numbered steps)

**Subtotal MUST [M]:** 2,512 LOC

---

## ⏱ IF-TIME — Do these if you finish early

| ☐ | File | Raw LOC | Tier | Notes |
|---|------|---------|------|-------|
| ☐ | `frontend/mission.html` | 290 | IF-TIME | Mission statement & vision page |
| ☐ | `frontend/contact.html` | 284 | IF-TIME | Contact form page (shared public contact) |
| ☐ | `frontend/css/pages/about.css` | 404 | IF-TIME | About page timeline and team grid styles |

**Subtotal IF-TIME:** 978 LOC  
**Dev A Total:** 3,490 LOC

---

## 📋 Copy-back (end of video)
```powershell
xcopy /E /Y dev_workspaces\dev_a\rewrite\frontend frontend\
```
> **Do Dev A's copy FIRST** — shared CSS/JS and public pages live here.

---

## 💡 Learning notes
- Every page starts with: `<!DOCTYPE html>` → `<head>` (imports variables.css, layout.css, page-specific CSS) → copy-paste header partial → unique `<main>` content → copy-paste footer partial → `<script>` imports.
- Variables like `--color-primary` and `--space-*` are defined in `variables.css`. Use them instead of hardcoding colors.
- `nav.js` handles the mobile hamburger. You never need to rewrite it — just `<script src="js/nav.js"></script>`.
- Community Donations link in the navbar search icon and footer points to `donations.html`.