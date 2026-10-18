# Frontend Shared Includes Architecture

This directory contains presentational HTML snippets and reusable components reconstructed for the frontend-first prototype of **iCareDonate**.

## Presentation Includes (Category A — Frontend Rebuilt)

| Frontend Component | Source PHP Include | Purpose & Treatment |
|---|---|---|
| `brand-sidebar.html` | `includes/brand-sidebar.php` | Brand logo, amber "Care" wordmark, and portal role badge (Admin / User). |
| `public-header.html` | `includes/nav-public.php` | Main public site navigation, accessible links, mobile responsive menu. |
| `public-footer.html` | `includes/footer.php` | Shared public footer with mission, help links, legal notices, and copyright. |
| `admin-sidebar.html` | `includes/nav-admin.php` | Administrator sidebar navigation with all 14 menu links and logout trigger. |
| `user-sidebar.html` | `includes/nav-user.php` | User portal sidebar with dashboard, donation, request, and activity links. |
| `topbar-user-btn.html` | `includes/topbar-user-btn.php` | User avatar dropdown menu component with profile, settings, and sign-out links. |

## Backend Infrastructure Includes (Category B — Preserved Untouched in Root `includes/`)

The following files from the root `includes/` folder contain backend authentication, database, security, and session management logic. **Per project specifications, these remain intact in the legacy PHP root and are NOT converted to client-side code**:

| Source Backend File | Purpose | Reason Preserved Untouched |
|---|---|---|
| `includes/auth.php` | `requireLogin()`, `requireAdmin()`, `isAdmin()`, `isSuperAdmin()` session checks. | Server-side authorization barrier. Preserved untouched for subsequent backend integration phase. |
| `includes/functions.php` | Database helpers, CSRF tokens, flash messages, audit logging, input sanitization. | Core PHP business logic and MySQL operations. Preserved untouched without modification. |

## Design Rules Implemented
- **Zero Emojis**: Every component utilizes clean inline SVG icons.
- **Brand Colors**: Amber (`#F2B84B`) for the wordmark "Care" and accents; Royal Blue (`#1F4FA3`) for primary brand surfaces; neutral borders and shadows.
