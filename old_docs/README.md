# iCareDonate — Documentation Directory

Welcome to the documentation repository for the **iCareDonate** (`CommunityDono`) platform.

---

## 📂 Folder Structure

```
docs/
├── specifications/          # Core technical and business requirements
│   ├── ROADMAP.md           # Master Phase-by-Phase Roadmap (Decoupled 22 Phases)
│   ├── PHP_MIGRATION_MAP.md # Complete inventory & migration mapping of all 92 PHP files
│   ├── BUSINESS_RULES.md    # Platform validation rules & state machines
│   ├── DATABASE.md          # 16-table MySQL schema & migrations
│   ├── SYSTEM_ARCHITECTURE.md # Architecture, directory layout, security
│   ├── USER_FLOWS.md        # User & Admin flow diagrams & step maps
│   └── XAMPP_SETUP.md       # XAMPP & PDO configuration guide
│
├── design/                  # UI design foundations
│   ├── DESIGN_SYSTEM.md     # Color tokens, typography, component specs
│   └── iCareDonate-brand-foundation.html # Live visual palette & preview
│
├── wireframes/              # UI wireframes
│   ├── admin_wireframe.pdf  # Admin interface wireframe document
│   └── iCareDonate wireframe.html # Interactive prototype wireframe
│
└── sitemaps/                # Sitemap visual architectures
    ├── public_sitemap.png   # Public interface navigation map
    └── user_sitemap.png     # User portal navigation map
```

---

## 🧭 Master Roadmap Summary & Architecture

The project follows the decoupled **Frontend (HTML/CSS/JS) + Backend (PHP/Laravel API) + Database (MySQL)** architecture defined in [`docs/specifications/ROADMAP.md`](specifications/ROADMAP.md).

| Phase Group | Phases | Description |
|---|---|---|
| **Foundation & Design** | Phases 0 – 3 | Technology Architecture, Folder Structure (`frontend/`, `backend/`), Brand System, Wireframe Map |
| **Data & Backend Core** | Phases 4 – 7 | Database Schema, Laravel Backend Init, Auth/Sanctum & RBAC, Admin Invitation Flow |
| **Frontend & API Client** | Phases 8 – 10 | HTML/CSS/JS conversion, Centralized `api.js`, Public Website pages |
| **Core Workflows** | Phases 11 – 14 | User Portal (Donations & Requests), Admin System, Custom Components, Standardized UI States |
| **Hardening & Delivery** | Phases 15 – 22 | Security & Uploads, Responsive Design, API Integration, Testing, GitHub, Local Env, Cloudflare, Final QA |
