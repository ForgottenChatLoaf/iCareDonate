# iCareDonate Database Schema & Migrations

## Canonical Schema (Currently Active)

To rebuild the live `icaredonate` database from scratch on a clean MySQL instance, run these files in exact order:

1. **Base Schema:** `reconstructed_schema.sql`
2. **Migration 1 (Matching, Campaigns & Achievements):** `migrations/migration_matching_crud_engagement.sql`
3. **Migration 2 (Admin Role Hierarchy & Password Security):** `migrations/migration_admin_hierarchy_security.sql`
4. **Migration 3 (CRUD, `support_admin`, Password Resets & Support Inbox):** `migrations/migration_crud_support_security.sql`

## Archived / Superseded Files (`archive/`)

- `schema.sql`, `schema_production.sql`, `migration_requester_info.sql`, `migration_dashboard_notifications.sql`, `migration_admin_functionalization.sql`, `migration_complete.sql` — superseded October pre-reconstruction SQL files, preserved for history.
- `seed_demo_data.sql`, `seed_dashboard_test_data.sql`, `seed_runner.php` — obsolete demo seeders, archived so they cannot populate the live database.
