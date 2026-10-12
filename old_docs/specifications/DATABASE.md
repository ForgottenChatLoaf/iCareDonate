# iCareDonate — Database Schema
> **Version:** 1.0 · **Date:** 2026-10-04  
> MySQL/MariaDB target. All timestamps in UTC. Foreign keys enforced.  
> Run migrations in order. Never store structured data as text blobs.

---

## Entity Relationship Overview

```
users ──────────────────────────────────────────────────────┐
  │                                                          │
  ├──[as donor]── donations ──── donation_images             │
  │                    │                                     │
  │                    └──── donation_categories (via cat.)  │
  │                                                          │
  ├──[as requester]── requests ──── request_proofs           │
  │                       │                                  │
  │                       └──── matches ──── transactions ───┘
  │                                 │
  │                                 └── match_allocations
  │
  ├── notifications
  ├── support_tickets
  ├── problem_reports
  ├── user_sessions
  └── [admin actions tracked in audit_logs]

categories ─── donations
admin_users ─── audit_logs
```

---

## 1. `categories`

```sql
CREATE TABLE categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100)  NOT NULL UNIQUE,
  slug        VARCHAR(100)  NOT NULL UNIQUE,
  description TEXT,
  icon        VARCHAR(50),              -- Lucide icon name
  sort_order  SMALLINT      DEFAULT 0,
  is_active   TINYINT(1)    DEFAULT 1,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Seed categories:** Food, School Supplies, Clothing, Medical, Household Goods, Books, Toys, Electronics, Other

---

## 2. `users`

```sql
CREATE TABLE users (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email                VARCHAR(255)  NOT NULL UNIQUE,
  password_hash        VARCHAR(255)  NOT NULL,
  first_name           VARCHAR(100)  NOT NULL,
  last_name            VARCHAR(100)  NOT NULL,
  phone                VARCHAR(30),
  barangay             VARCHAR(150),
  city                 VARCHAR(100),
  province             VARCHAR(100),
  role                 ENUM('user','admin','superadmin') NOT NULL DEFAULT 'user',
  status               ENUM('pending_verification','active','suspended','banned') NOT NULL DEFAULT 'pending_verification',
  email_verified_at    DATETIME,
  suspension_reason    TEXT,
  suspended_at         DATETIME,
  suspended_by         INT UNSIGNED,   -- admin user id
  banned_at            DATETIME,
  banned_by            INT UNSIGNED,   -- admin user id
  profile_photo        VARCHAR(500),
  last_login_at        DATETIME,
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (suspended_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL
);
```

---

## 3. `user_sessions`

```sql
CREATE TABLE user_sessions (
  id            VARCHAR(128) PRIMARY KEY,   -- secure random token
  user_id       INT UNSIGNED NOT NULL,
  ip_address    VARCHAR(45),
  user_agent    VARCHAR(500),
  payload       TEXT,
  last_activity DATETIME,
  expires_at    DATETIME NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_sessions_user (user_id),
  INDEX idx_user_sessions_expires (expires_at)
);
```

---

## 4. `email_verifications`

```sql
CREATE TABLE email_verifications (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  token      VARCHAR(128) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at    DATETIME,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_email_verifications_token (token)
);
```

---

## 5. `password_resets`

```sql
CREATE TABLE password_resets (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  token      VARCHAR(128) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at    DATETIME,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_password_resets_token (token)
);
```

---

## 6. `donations`

```sql
CREATE TABLE donations (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donor_id         INT UNSIGNED NOT NULL,
  category_id      INT UNSIGNED NOT NULL,
  title            VARCHAR(200) NOT NULL,
  description      TEXT,
  quantity_total   INT UNSIGNED NOT NULL,       -- total offered quantity
  quantity_reserved INT UNSIGNED NOT NULL DEFAULT 0,  -- matched/in-transaction
  quantity_claimed  INT UNSIGNED NOT NULL DEFAULT 0,  -- fully completed
  unit             VARCHAR(50)  DEFAULT 'item',  -- e.g. boxes, kg, pcs
  condition_type   ENUM('new','like_new','good','fair','poor') NOT NULL,
  expiration_date  DATE,
  pickup_method    ENUM('pickup','delivery','both') NOT NULL,
  pickup_address   VARCHAR(500),
  pickup_barangay  VARCHAR(150),
  pickup_city      VARCHAR(100),
  pickup_province  VARCHAR(100),
  pickup_notes     TEXT,
  pickup_schedule  TEXT,                         -- JSON: available days/times
  status           ENUM(
                     'draft',
                     'pending_verification',
                     'needs_information',
                     'approved',
                     'rejected',
                     'fully_allocated',
                     'expired',
                     'cancelled'
                   ) NOT NULL DEFAULT 'draft',
  admin_notes      TEXT,                         -- internal admin note
  rejection_reason TEXT,
  info_request     TEXT,                         -- reason for needs_information
  submitted_at     DATETIME,
  approved_at      DATETIME,
  approved_by      INT UNSIGNED,
  expires_at       DATETIME,                     -- computed from expiration_date
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (donor_id)    REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,

  INDEX idx_donations_donor    (donor_id),
  INDEX idx_donations_category (category_id),
  INDEX idx_donations_status   (status),
  INDEX idx_donations_expires  (expires_at)
);
```

### Computed field: `quantity_available`
```sql
-- Always compute at query time, never store:
quantity_total - quantity_reserved - quantity_claimed AS quantity_available
```

---

## 7. `donation_images`

```sql
CREATE TABLE donation_images (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donation_id INT UNSIGNED NOT NULL,
  file_path   VARCHAR(500) NOT NULL,   -- relative path under storage/public/
  file_name   VARCHAR(255) NOT NULL,   -- original filename (sanitized)
  mime_type   VARCHAR(100),
  file_size   INT UNSIGNED,            -- bytes
  sort_order  TINYINT DEFAULT 0,
  is_primary  TINYINT(1) DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE CASCADE,
  INDEX idx_donation_images_donation (donation_id)
);
```

---

## 8. `requests`

```sql
CREATE TABLE requests (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  requester_id      INT UNSIGNED NOT NULL,
  donation_id       INT UNSIGNED NOT NULL,
  quantity_requested INT UNSIGNED NOT NULL,
  quantity_matched   INT UNSIGNED DEFAULT 0,  -- may be partial
  purpose           TEXT NOT NULL,            -- why they need it
  urgency_level     ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  status            ENUM(
                      'submitted',
                      'proof_review',
                      'needs_information',
                      'approved',
                      'rejected',
                      'matched',
                      'pickup_scheduled',
                      'claimed',
                      'completed',
                      'cancelled',
                      'expired'
                    ) NOT NULL DEFAULT 'submitted',
  admin_notes       TEXT,
  rejection_reason  TEXT,
  info_request      TEXT,
  submitted_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at       DATETIME,
  reviewed_by       INT UNSIGNED,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (donation_id)  REFERENCES donations(id) ON DELETE RESTRICT,
  FOREIGN KEY (reviewed_by)  REFERENCES users(id) ON DELETE SET NULL,

  INDEX idx_requests_requester (requester_id),
  INDEX idx_requests_donation  (donation_id),
  INDEX idx_requests_status    (status),
  UNIQUE KEY uq_active_request (requester_id, donation_id, status)
    -- application should prevent exact duplicate active requests
);
```

---

## 9. `request_proofs`

```sql
CREATE TABLE request_proofs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id  INT UNSIGNED NOT NULL,
  file_path   VARCHAR(500) NOT NULL,   -- relative path under storage/private/
  file_name   VARCHAR(255) NOT NULL,
  mime_type   VARCHAR(100),
  file_size   INT UNSIGNED,
  description TEXT,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
  INDEX idx_request_proofs_request (request_id)
);
```

---

## 10. `matches`

```sql
CREATE TABLE matches (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donation_id   INT UNSIGNED NOT NULL,
  request_id    INT UNSIGNED NOT NULL,
  created_by    INT UNSIGNED NOT NULL,    -- admin who created the match
  quantity      INT UNSIGNED NOT NULL,    -- quantity being matched
  match_notes   TEXT,
  status        ENUM('active','cancelled') NOT NULL DEFAULT 'active',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE RESTRICT,
  FOREIGN KEY (request_id)  REFERENCES requests(id)  ON DELETE RESTRICT,
  FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE RESTRICT,

  INDEX idx_matches_donation (donation_id),
  INDEX idx_matches_request  (request_id)
);
```

---

## 11. `transactions`

```sql
CREATE TABLE transactions (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  match_id         INT UNSIGNED NOT NULL UNIQUE,
  donor_id         INT UNSIGNED NOT NULL,
  requester_id     INT UNSIGNED NOT NULL,
  donation_id      INT UNSIGNED NOT NULL,
  quantity         INT UNSIGNED NOT NULL,
  status           ENUM(
                     'created',
                     'pending_claim',
                     'claimed',
                     'completed',
                     'cancelled',
                     'expired'
                   ) NOT NULL DEFAULT 'created',
  pickup_method    ENUM('pickup','delivery') NOT NULL,
  pickup_date      DATE,
  pickup_time      TIME,
  pickup_address   VARCHAR(500),
  claim_deadline   DATETIME NOT NULL,
  claimed_at       DATETIME,
  completed_at     DATETIME,
  cancelled_at     DATETIME,
  cancel_reason    TEXT,
  cancelled_by     INT UNSIGNED,
  admin_notes      TEXT,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (match_id)      REFERENCES matches(id)    ON DELETE RESTRICT,
  FOREIGN KEY (donor_id)      REFERENCES users(id)      ON DELETE RESTRICT,
  FOREIGN KEY (requester_id)  REFERENCES users(id)      ON DELETE RESTRICT,
  FOREIGN KEY (donation_id)   REFERENCES donations(id)  ON DELETE RESTRICT,
  FOREIGN KEY (cancelled_by)  REFERENCES users(id)      ON DELETE SET NULL,

  INDEX idx_transactions_donor     (donor_id),
  INDEX idx_transactions_requester (requester_id),
  INDEX idx_transactions_status    (status),
  INDEX idx_transactions_deadline  (claim_deadline)
);
```

---

## 12. `notifications`

```sql
CREATE TABLE notifications (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  type         VARCHAR(50) NOT NULL,     -- e.g. DONATION_APPROVED, MATCH_CREATED
  title        VARCHAR(255) NOT NULL,
  message      TEXT NOT NULL,
  related_type VARCHAR(50),              -- e.g. donation, request, transaction
  related_id   INT UNSIGNED,
  is_read      TINYINT(1) DEFAULT 0,
  read_at      DATETIME,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notifications_user   (user_id),
  INDEX idx_notifications_unread (user_id, is_read),
  INDEX idx_notifications_type   (type)
);
```

### Notification types
```
DONATION_SUBMITTED         DONATION_APPROVED          DONATION_REJECTED
DONATION_NEEDS_INFO        DONATION_EXPIRING_SOON     REQUEST_RECEIVED
REQUEST_APPROVED           REQUEST_REJECTED            REQUEST_NEEDS_INFO
MATCH_CREATED              CLAIM_EXPIRING             TRANSACTION_COMPLETED
TRANSACTION_CANCELLED      ACCOUNT_SUSPENDED           ADMIN_MESSAGE
PROBLEM_REPORT_RECEIVED    PROBLEM_REPORT_RESOLVED
```

---

## 13. `support_tickets`

```sql
CREATE TABLE support_tickets (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED,               -- nullable if anonymous
  subject      VARCHAR(255) NOT NULL,
  message      TEXT NOT NULL,
  email        VARCHAR(255),               -- contact email (for non-auth users)
  status       ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  assigned_to  INT UNSIGNED,               -- admin user
  admin_reply  TEXT,
  replied_at   DATETIME,
  closed_at    DATETIME,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_support_status (status)
);
```

---

## 14. `problem_reports`

```sql
CREATE TABLE problem_reports (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reporter_id     INT UNSIGNED NOT NULL,
  target_type     ENUM('donation','user') NOT NULL,
  target_id       INT UNSIGNED NOT NULL,
  reason          VARCHAR(100) NOT NULL,
  description     TEXT,
  status          ENUM('pending','reviewed','resolved','dismissed') NOT NULL DEFAULT 'pending',
  reviewed_by     INT UNSIGNED,
  admin_notes     TEXT,
  resolved_at     DATETIME,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (reporter_id)  REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by)  REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_problem_reports_status (status),
  INDEX idx_problem_reports_target (target_type, target_id)
);
```

---

## 15. `audit_logs`

```sql
CREATE TABLE audit_logs (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_id     INT UNSIGNED,              -- who performed the action (null = system)
  actor_role   VARCHAR(20),
  action       VARCHAR(100) NOT NULL,     -- e.g. DONATION_STATUS_CHANGED
  target_type  VARCHAR(50)  NOT NULL,     -- e.g. donation, user
  target_id    INT UNSIGNED NOT NULL,
  old_value    JSON,
  new_value    JSON,
  reason       TEXT,                      -- required for status changes
  ip_address   VARCHAR(45),
  user_agent   VARCHAR(500),
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_actor     (actor_id),
  INDEX idx_audit_target    (target_type, target_id),
  INDEX idx_audit_action    (action),
  INDEX idx_audit_created   (created_at)
);
```

### Auditable actions
```
USER_REGISTERED            USER_EMAIL_VERIFIED        USER_LOGIN
USER_LOGOUT                USER_SUSPENDED             USER_BANNED
USER_ROLE_CHANGED          USER_DELETED               USER_PROFILE_UPDATED
DONATION_CREATED           DONATION_SUBMITTED         DONATION_STATUS_CHANGED
DONATION_EDITED            DONATION_DELETED           DONATION_IMAGE_REMOVED
REQUEST_CREATED            REQUEST_PROOF_UPLOADED     REQUEST_STATUS_CHANGED
REQUEST_CANCELLED          MATCH_CREATED              MATCH_CANCELLED
TRANSACTION_CREATED        TRANSACTION_STATUS_CHANGED TRANSACTION_DEADLINE_EXTENDED
TRANSACTION_CANCELLED      TRANSACTION_COMPLETED      CATEGORY_CREATED
CATEGORY_UPDATED           CATEGORY_DEACTIVATED       REPORT_REVIEWED
MODERATION_WARNING         MODERATION_SUSPENSION      MODERATION_BAN
```

---

## 16. `faqs`

```sql
CREATE TABLE faqs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question    VARCHAR(500) NOT NULL,
  answer      TEXT NOT NULL,
  category    VARCHAR(100),
  sort_order  SMALLINT DEFAULT 0,
  is_active   TINYINT(1) DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 17. Indexes Summary

Key composite indexes for common queries:

```sql
-- Browse approved donations with category filter
INDEX idx_donations_browse (status, category_id, created_at);

-- Admin action queue: pending items
INDEX idx_donations_pending (status, submitted_at);
INDEX idx_requests_pending  (status, submitted_at);

-- Transaction monitoring
INDEX idx_transactions_expiring (status, claim_deadline);

-- Notification unread count
INDEX idx_notifications_unread_count (user_id, is_read, created_at);
```

---

## 18. File Storage Architecture

```
storage/
├── public/                    (web-accessible)
│   └── donations/
│       └── {donation_id}/
│           └── {random_name}.jpg
│
└── private/                   (not web-accessible; requires auth)
    ├── proofs/
    │   └── {request_id}/
    │       └── {random_name}.pdf
    └── admin/
        └── {type}/
            └── {random_name}.pdf
```

Rules:
- Donation images: `storage/public/` — served directly
- Request proof documents: `storage/private/` — served only via authenticated, time-limited route
- Filenames: always replace user filename with `bin2hex(random_bytes(16)) . ext`
- Never expose original filenames in public URLs

---

## 19. Migration Order

Run in this sequence:

```
001_create_categories
002_create_users
003_create_user_sessions
004_create_email_verifications
005_create_password_resets
006_create_donations
007_create_donation_images
008_create_requests
009_create_request_proofs
010_create_matches
011_create_transactions
012_create_notifications
013_create_support_tickets
014_create_problem_reports
015_create_audit_logs
016_create_faqs
017_seed_categories
018_seed_admin_user
```

---

## 20. Database Conventions

| Convention | Rule |
|---|---|
| Primary keys | `INT UNSIGNED AUTO_INCREMENT` |
| Timestamps | UTC; `DATETIME` type; created/updated on every table |
| Soft deletes | Not used — use status fields instead |
| Enums | Use MySQL ENUM for fixed-value fields |
| JSON fields | Used only for `audit_logs.old_value / new_value` and `donations.pickup_schedule` |
| Text vs VARCHAR | `TEXT` for user content; `VARCHAR(n)` for constrained strings |
| Indexes | All foreign keys indexed; common filter columns indexed |
| Foreign keys | Enforced at the database level |
| Character set | `utf8mb4` (full Unicode + emoji support) |
| Collation | `utf8mb4_unicode_ci` |
