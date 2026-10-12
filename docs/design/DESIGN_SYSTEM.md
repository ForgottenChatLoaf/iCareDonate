# iCareDonate — Master Brand Palette & Design System
> **Version:** 2.0 (Master Brand Authority) · **Date:** 2026-10-04  
> This document is the single source of truth for all visual decisions across the entire application.  
> Every page, component, form, modal, dashboard, Admin portal, and public interface inherits from this palette.

---

## 1. Brand Identity

### Product positioning
iCareDonate is a **community resource-donation platform** — not a cash-donation website.  
The interface feels **calm, modern, light to the eyes, trustworthy, and dignified**.

### Primary tagline
> **iCareDonate · Give what you can. Help where it matters.**

---

## 2. Master Color Palette

Every color has a light and a dark value; switch themes to see them change.

### Primary Teal
*Teal carries trust and action: buttons, links, the logo.*

| Token | Light Value | Dark Value | Usage |
|---|---|---|---|
| `--brand-primary` | `#0D9488` | `#2DD4BF` | Primary Teal — buttons, links, logo |
| `--brand-primary-dark` | `#0F766E` | `#5EEAD4` | Primary Dark — hover states, dark text |
| `--brand-primary-light` | `#D9F7F1` | `#123430` | Soft Mint — panels, light backgrounds |

### Accent Warm Coral
*Warm coral adds the human touch: highlights, focus rings, key calls to action.*

| Token | Light Value | Dark Value | Usage |
|---|---|---|---|
| `--brand-accent` | `#F2603F` | `#FF7A5C` | Warm Coral — CTAs, focus rings, highlights |
| `--brand-accent-dark` | `#C94B32` | `#FF9A84` | Coral Dark — active/hover emphasis |
| `--brand-accent-light` | `#FFE8E0` | `#3A1E18` | Soft Coral — subtle tints |

### Neutrals
*Backgrounds, surfaces, text, and borders.*

| Token | Light Value | Dark Value | Usage |
|---|---|---|---|
| `--bg` | `#FFFEFC` | `#0E1514` | Background |
| `--surface` | `#FFFFFF` | `#151E1D` | Surface (cards, modals) |
| `--surface-soft` | `#F0FAF8` | `#1A2524` | Surface Soft (inputs, sidebars, hero) |
| `--text` | `#17363A` | `#F1F5F4` | Primary text |
| `--text-soft` | `#46636A` | `#CBD5D3` | Secondary text |
| `--text-muted` | `#5F7B80` | `#93A5A2` | Muted text, captions, placeholders |
| `--border` | `#D9ECE8` | `#263331` | Border |
| `--border-strong` | `#B8D8D2` | `#364744` | Border Strong |

### Semantic Colors
*Status colors for success, warning, error, and information.*

| Token | Light Value | Dark Value | Status / Meaning |
|---|---|---|---|
| `--success` / `--success-bg` | `#1B9A5E` / `#E3F8EC` | `#4ADE9A` / `#12301F` | Approved, available |
| `--warning` / `--warning-bg` | `#A15C00` / `#FFF1C7` | `#FBBF24` / `#33290D` | Pending, review |
| `--danger` / `--danger-bg` | `#D92D20` / `#FFE9E6` | `#F87171` / `#3A1816` | Rejected, danger, errors |
| `--info` / `--info-bg` | `#2F6BFF` / `#E6EEFF` | `#7AA7FF` / `#172545` | Info, in use |

### Contrast / Accessibility Token
| Token | Light Value | Dark Value | Description |
|---|---|---|---|
| `--on-primary` | `#FFFFFF` | `#06201D` | Text on `--brand-primary` elements for optimal WCAG contrast |

---

## 3. Typography

Lilita One gives headings a friendly voice. Outfit keeps everything else clean and readable.

### Headings & Logo
**Lilita One** · Regular 400 only:
- **Heading 1**: Lilita One · 2.8–5rem · 1.02
- **Heading 2**: Lilita One · 2–3rem · 1.1
- **Heading 3**: Lilita One · 1.35rem · 1.2
- **Logo Wordmark**: Lilita One 400

### Body & UI Elements
**Outfit** · Regular 400, Medium 500, Semibold 600, Bold 700:
- **Body text**: Outfit 400 · 1rem · 1.6
- **Caption & helper text**: Outfit 400 · 0.85rem · 1.6
- **Buttons, forms, labels**: Outfit 500/600/700
- Labels: 12px bold
- Table: 13px

---

## 4. Spacing Scale

Use only values from this scale. No ad-hoc values.

| Token | Value | Usage |
|---|---|---|
| `--space-1` | 4px | Micro gaps, icon margins |
| `--space-2` | 8px | Inline gaps, tight stacks |
| `--space-3` | 12px | Form element gaps |
| `--space-4` | 16px | Card inner padding (sm), list item padding |
| `--space-5` | 24px | Card padding (default), section gaps |
| `--space-6` | 32px | Section spacing |
| `--space-7` | 48px | Large section breaks |
| `--space-8` | 64px | Page-level vertical rhythm |

---

## 5. Border Radius

| Token | Value | Usage |
|---|---|---|
| `--radius-sm` | 8px | Pills, small badges |
| `--radius-md` | 12px | Inputs, selects, buttons |
| `--radius-lg` | 16px | Cards |
| `--radius-xl` | 20px | Hero panels, modals |
| `--radius-full` | 9999px | Status pills, avatars |

---

## 6. Shadows

Keep shadows subtle. Avoid heavy floating-card effects.

| Token | Value | Usage |
|---|---|---|
| `--shadow-sm` | `0 1px 3px rgba(0,0,0,0.08)` | Inputs on focus, subtle cards |
| `--shadow-md` | `0 4px 12px rgba(0,0,0,0.10)` | Cards, dropdowns |
| `--shadow-lg` | `0 8px 24px rgba(0,0,0,0.12)` | Modals, overlays |
| `--shadow-none` | `none` | Flat elements |

---

## 7. Layout

### Container widths

| Context | Max width |
|---|---|
| Public pages | 1120px |
| User dashboard main | 100% (sidebar layout) |
| Admin dashboard main | 100% (sidebar layout) |
| Auth forms | 420px |
| Narrow forms | 640px |

### Breakpoints

| Name | Value | Behavior |
|---|---|---|
| `mobile` | < 640px | Single column, stacked nav |
| `tablet` | 640px–1024px | 2-column where needed |
| `desktop` | > 1024px | Full layouts |

### Sidebar widths

| Context | Width |
|---|---|
| User sidebar | 200px |
| Admin sidebar | 220px |

---

## 8. Component Specifications

### Buttons

| Variant | Background | Text | Border | Usage |
|---|---|---|---|---|
| Primary | `#0F766E` | White | None | Main actions |
| Primary hover | `#0B5D56` | White | None | — |
| Accent | `#C94B32` | White | None | Donate CTA |
| Accent hover | `#A53D27` | White | None | — |
| Secondary | White | `#1F2937` | `#E2E8F0` | Tertiary actions |
| Danger | White | `#991B1B` | `#FCA5A5` | Destructive actions |
| Ghost | Transparent | `#0F766E` | None | Low-emphasis links |

Padding: 12px 20px (default) | 10px 16px (sm) | 14px 28px (lg)
Radius: var(--radius-md)
Font: var(--text-btn)

### Form inputs

Height: 44px (default)
Padding: 10px 14px
Border: 1px solid var(--color-border)
Radius: var(--radius-md)
Focus: border-color var(--color-primary), box-shadow 0 0 0 3px rgba(15,118,110,0.15)
Error: border-color #EF4444, background #FFF5F5

### Cards

Background: var(--color-surface)
Border: 1px solid var(--color-border)
Radius: var(--radius-lg)
Padding: var(--space-5)
Shadow: var(--shadow-md)

### Status badges / pills

Always include: **icon + text**. Never color alone.

Radius: var(--radius-full)
Padding: 4px 12px
Font: var(--text-xs) 500

| Display label | Icon | Color |
|---|---|---|
| Pending verification | ○ | amber |
| Needs information | ! | amber |
| Approved | ✓ | green |
| Rejected | × | red |
| Matched | ⬡ | teal |
| Ready for claim | 📦 | mint/teal |
| Claimed | ✓ | green |
| Completed | ✓ | green |
| Cancelled | — | gray |
| Expired | ⏱ | gray |

### Tables

Font-size: 14px
Cell padding: 10px 12px
Border: bottom 1px solid var(--color-border)
Header: color var(--color-text-muted), font-weight 500, bg var(--color-bg)
Hover row: bg #F8FAFB

### Alerts / Notes

Padding: 12px 16px
Radius: var(--radius-md)
Border: left 4px solid (semantic color)

| Type | Left border | Background |
|---|---|---|
| info | teal | mint |
| success | green | light green |
| warning | amber | light amber |
| error | red | light red |

### Modals

Overlay: rgba(0,0,0,0.5)
Panel: white, var(--radius-xl), var(--shadow-lg)
Max-width: 560px (sm) / 720px (md) / 960px (lg)

### Progress indicators (wizard steps)

Step states:
- completed: teal circle with ✓, teal connector
- current: teal circle with number, teal border
- upcoming: gray circle with number, gray connector

### Empty states

Structure:
1. Illustration / icon
2. Heading
3. Supporting message
4. Optional CTA button

### Loading states
Use skeleton screens (gray animated rectangles) for full-page loads.
Inline spinners acceptable for button loading states.

---

## 9. Status Language

One standardized vocabulary used everywhere.

### Donation statuses

| Status key | Display label | Icon | Color |
|---|---|---|---|
| `draft` | Draft | ✏️ | neutral |
| `pending_verification` | Pending Verification | ○ | amber |
| `needs_information` | Needs Information | ! | amber |
| `approved` | Approved | ✓ | green |
| `rejected` | Rejected | × | red |
| `fully_allocated` | Fully Allocated | — | teal |
| `expired` | Expired | ⏱ | gray |
| `cancelled` | Cancelled | — | gray |

### Request statuses

| Status key | Display label | Icon | Color |
|---|---|---|---|
| `submitted` | Submitted | ○ | amber |
| `proof_review` | Proof Under Review | ! | amber |
| `approved` | Approved | ✓ | green |
| `rejected` | Rejected | × | red |
| `matched` | Matched | ⬡ | teal |
| `pickup_scheduled` | Pickup Scheduled | 📅 | teal |
| `claimed` | Claimed | 📦 | green |
| `completed` | Completed | ✓ | green |
| `cancelled` | Cancelled | — | gray |
| `expired` | Expired | ⏱ | gray |

### User/account statuses

| Status key | Display label |
|---|---|
| `active` | Active |
| `pending_verification` | Pending Verification |
| `suspended` | Suspended |
| `banned` | Banned |

---

## 10. Icons

Use **one icon family only**. Recommended: [Lucide Icons](https://lucide.dev/) — clean, consistent, MIT licensed.

Do **not** mix emoji + random SVGs + Font Awesome.

| Action | Icon name |
|---|---|
| Donate | `gift` or `package` |
| Request | `hand` |
| Verified | `shield-check` |
| Pending | `clock` |
| Match | `link` |
| Claim | `package-check` |
| Notification | `bell` |
| Admin | `layout-dashboard` |
| Settings | `settings` |
| Logout | `log-out` |
| Search | `search` |
| Filter | `sliders-horizontal` |
| User | `user` |
| Category | `tag` |
| Location | `map-pin` |
| Calendar | `calendar` |

---

## 11. Logo Specifications

### Symbol concept
Simple rounded shape containing: **heart + subtle hands/community element**.
Not a medical cross.

### Wordmark
Lilita One · Deep Teal `#0F766E`

### Versions

| Version | Use |
|---|---|
| Full (icon + wordmark) | Navbar, footer, login page |
| Compact (icon only) | Favicon, mobile collapsed nav, avatar |
| Light (on dark bg) | Dark overlays, dark sections |
| Single-color | Print, embossed |

### Logo must work at
- 24px height (favicon/compact nav)
- 32px height (standard navbar)
- 64px height (login/auth pages)
- 120px+ height (hero, social card)

---

## 12. Three Interface Contexts

Do **not** make all three look identical.

### Public interface
- Tone: Friendly, visual, welcoming
- Uses Lilita One for major headings
- Full coral accent usage
- Generous whitespace
- Illustration / imagery

### User dashboard
- Tone: Simple, task-oriented
- Outfit only
- Teal for primary actions, coral for alerts/CTA
- Clean card layouts
- Clear status indicators

### Admin dashboard
- Tone: Dense, efficient, information-first
- Outfit only, slightly smaller scale
- More tabular layouts
- Same brand palette — much more restrained
- Action queue as primary interface, not analytics

---

## 13. Accessibility Requirements (WCAG 2.2 AA)

- Minimum contrast: **4.5:1** for normal text, **3:1** for large text
- Deep Teal `#0F766E` on white: ~5.1:1 contrast ✓
- All interactive elements keyboard-navigable
- **Visible focus rings** on all interactive elements (2px teal outline)
- Form fields must have associated `<label>` elements
- Status badges must use icon + text (never color alone)
- Images require descriptive `alt` text
- Error messages programmatically associated with fields (`aria-describedby`)
- Modals must trap focus and support Escape to close
- Prefer `prefers-reduced-motion` for animations
- Minimum touch target: 44×44px on mobile
- Semantic headings hierarchy on every page (one `<h1>`)

---

## 14. Motion & Animation

Subtle only. No heavy animation libraries.

| Element | Animation |
|---|---|
| Buttons | transition: background 0.15s, transform 0.1s |
| Cards on hover | translateY(-2px), subtle shadow increase |
| Page transitions | Fade-in 150ms |
| Sidebar nav items | transition: background 0.1s |
| Modals | Fade + subtle scale-up, 200ms |
| Skeleton loaders | Shimmer animation |

`prefers-reduced-motion: reduce` → disable all transforms and transitions.

---

## 15. Brand Voice in UI Labels

| ❌ Avoid | ✅ Use instead |
|---|---|
| Submit Resource Donation Request | Post a donation |
| Resource allocation transaction | Donation matched |
| Verification status pending | Waiting for verification |
| Execute claim operation | Claim this donation |
| Terminate session | Log out |
| User deactivation | Account suspended |
| Resource quantity | Available items |

---

## 16. CSS Custom Properties — Full Token Reference

```css
:root {
  /* Brand colors */
  --color-primary:      #0F766E;
  --color-primary-dark: #0B5D56;
  --color-accent:       #C94B32;
  --color-accent-hover: #A53D27;

  /* Text */
  --color-text:         #1F2937;
  --color-text-muted:   #475569;

  /* Backgrounds */
  --color-bg:           #FFFDFB;
  --color-surface:      #FFFFFF;
  --color-tint-teal:    #E8F6F3;
  --color-tint-coral:   #FFF0EC;

  /* Borders */
  --color-border:       #E2E8F0;
  --color-border-focus: #0F766E;

  /* Status */
  --status-pending-fg:  #92400E;
  --status-pending-bg:  #FEF3C7;
  --status-ok-fg:       #166534;
  --status-ok-bg:       #DCFCE7;
  --status-error-fg:    #991B1B;
  --status-error-bg:    #FEE2E2;
  --status-info-fg:     #0F766E;
  --status-info-bg:     #E8F6F3;
  --status-neutral-fg:  #475569;
  --status-neutral-bg:  #F1F5F9;

  /* Typography */
  --font-brand: 'Lilita One', sans-serif;
  --font-body:  'Outfit', system-ui, sans-serif;

  /* Spacing */
  --space-1:  4px;
  --space-2:  8px;
  --space-3:  12px;
  --space-4:  16px;
  --space-5:  24px;
  --space-6:  32px;
  --space-7:  48px;
  --space-8:  64px;

  /* Radius */
  --radius-sm:   8px;
  --radius-md:   12px;
  --radius-lg:   16px;
  --radius-xl:   20px;
  --radius-full: 9999px;

  /* Shadows */
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
  --shadow-md: 0 4px 12px rgba(0,0,0,0.10);
  --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
}
```
