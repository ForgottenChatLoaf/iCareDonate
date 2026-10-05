# iCareDonate — System Architecture
> **Version:** 1.0 · **Date:** 2026-10-04  
> Defines the technical structure, project layout, conventions, and deployment approach.

---

## 1. Technology Stack

| Layer | Technology | Rationale |
|---|---|---|
| Language | PHP 8.2+ | Team familiarity; Laravel ecosystem |
| Framework | Laravel 11 | Auth, routing, ORM, queues, validation out of the box |
| Database | MySQL 8.0 / MariaDB 10.11 | Reliable, well-supported RDBMS |
| ORM | Eloquent (via Laravel) | Cleaner model layer; avoids raw string queries |
| Templating | Blade (via Laravel) | Server-side rendering; works well for multi-page app |
| CSS | Vanilla CSS with design tokens | No framework dependency; full design control |
| JS | Vanilla JS + Alpine.js (optional) | Lightweight interactivity (modals, dropdowns) without a full SPA |
| File storage | Local disk (Phase 1) → S3-compatible (Phase 2) | Start simple, scale when needed |
| Email | SMTP (Phase 1) → SES / Mailgun (Phase 2) | Standard Laravel mail configuration |
| Queue | Database driver (Phase 1) → Redis (Phase 2) | For notification delivery, email sending |
| Search | MySQL FULLTEXT (Phase 1) → Scout/Meilisearch (Phase 2) | Start with native, upgrade if needed |
| Version control | Git + GitHub / GitLab | Feature branch strategy |

> **Note:** If the team is not using Laravel, the same separation of concerns must be maintained manually. Do not build a monolithic `index.php` file.

---

## 2. Project Directory Structure

```
icare-donate/
│
├── app/
│   ├── Console/                  # Artisan commands (expiration jobs, etc.)
│   │   └── Commands/
│   │       ├── ExpireDonations.php
│   │       └── ExpireTransactions.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Public/           # Public-facing pages (no auth required)
│   │   │   │   ├── HomeController.php
│   │   │   │   ├── BrowseController.php
│   │   │   │   ├── DonationDetailController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── ContactController.php
│   │   │   │   └── PageController.php     # About, How it works, FAQ, Legal
│   │   │   │
│   │   │   ├── Auth/             # Authentication flows
│   │   │   │   ├── RegisterController.php
│   │   │   │   ├── LoginController.php
│   │   │   │   ├── LogoutController.php
│   │   │   │   ├── EmailVerificationController.php
│   │   │   │   ├── ForgotPasswordController.php
│   │   │   │   └── ResetPasswordController.php
│   │   │   │
│   │   │   ├── User/             # Authenticated user dashboards
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── DonationController.php      # Post, edit, my donations
│   │   │   │   ├── RequestController.php       # Browse, request, my requests
│   │   │   │   ├── NotificationController.php
│   │   │   │   ├── SupportController.php
│   │   │   │   └── AccountController.php
│   │   │   │
│   │   │   └── Admin/            # Admin panel
│   │   │       ├── DashboardController.php
│   │   │       ├── DonationController.php
│   │   │       ├── RequestController.php
│   │   │       ├── MatchController.php
│   │   │       ├── TransactionController.php
│   │   │       ├── UserController.php
│   │   │       ├── CategoryController.php
│   │   │       ├── ModerationController.php
│   │   │       ├── ReportController.php
│   │   │       ├── AuditController.php
│   │   │       └── SettingsController.php
│   │   │
│   │   └── Middleware/
│   │       ├── Authenticate.php          # Standard auth check
│   │       ├── AdminOnly.php             # Role: admin or superadmin
│   │       ├── ActiveUserOnly.php        # Blocks suspended/banned
│   │       └── RedirectIfAuthenticated.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Category.php
│   │   ├── Donation.php
│   │   ├── DonationImage.php
│   │   ├── Request.php               # Eloquent model (not PHP Request)
│   │   ├── RequestProof.php
│   │   ├── Match.php
│   │   ├── Transaction.php
│   │   ├── Notification.php
│   │   ├── SupportTicket.php
│   │   ├── ProblemReport.php
│   │   ├── AuditLog.php
│   │   └── Faq.php
│   │
│   ├── Services/                 # Business logic (not HTTP-specific)
│   │   ├── DonationService.php       # Submit, approve, reject, expire
│   │   ├── RequestService.php        # Submit, review, approve, reject
│   │   ├── MatchService.php          # Create match, reserve quantity
│   │   ├── TransactionService.php    # Create transaction, claim, complete
│   │   ├── NotificationService.php   # Create and dispatch notifications
│   │   ├── AuditService.php          # Record all audit events
│   │   ├── FileUploadService.php     # Validate, rename, store files
│   │   └── StatusMachineService.php  # Enforce status transitions
│   │
│   ├── Policies/                 # Laravel authorization policies
│   │   ├── DonationPolicy.php
│   │   ├── RequestPolicy.php
│   │   └── TransactionPolicy.php
│   │
│   └── Mail/                     # Mailable classes
│       ├── EmailVerification.php
│       ├── PasswordReset.php
│       ├── DonationApproved.php
│       ├── DonationRejected.php
│       ├── RequestApproved.php
│       └── MatchCreated.php
│
├── database/
│   ├── migrations/               # Numbered migration files (see DATABASE.md)
│   └── seeders/
│       ├── CategorySeeder.php
│       ├── AdminUserSeeder.php
│       └── DevelopmentSeeder.php  # Sample data for dev only
│
├── public/
│   ├── index.php                 # Laravel entry point
│   └── assets/
│       ├── css/
│       │   ├── tokens.css        # CSS custom properties (design tokens)
│       │   ├── base.css          # Reset + base styles
│       │   ├── components.css    # Reusable component styles
│       │   ├── public.css        # Public site specific styles
│       │   ├── user.css          # User dashboard styles
│       │   └── admin.css         # Admin panel styles
│       ├── js/
│       │   ├── app.js            # Main JS bundle
│       │   └── admin.js          # Admin-specific JS
│       └── images/
│           ├── logo.svg
│           ├── logo-compact.svg
│           └── illustrations/
│
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── public.blade.php      # Public site layout
│       │   ├── user.blade.php        # User dashboard layout
│       │   └── admin.blade.php       # Admin panel layout
│       │
│       ├── public/                   # Public pages
│       │   ├── home.blade.php
│       │   ├── browse.blade.php
│       │   ├── donation-detail.blade.php
│       │   ├── how-it-works.blade.php
│       │   ├── categories.blade.php
│       │   ├── about.blade.php
│       │   ├── contact.blade.php
│       │   ├── faq.blade.php
│       │   └── legal/
│       │       ├── privacy.blade.php
│       │       └── terms.blade.php
│       │
│       ├── auth/                     # Auth pages
│       │   ├── login.blade.php
│       │   ├── register.blade.php
│       │   ├── verify-email.blade.php
│       │   ├── forgot-password.blade.php
│       │   └── reset-password.blade.php
│       │
│       ├── user/                     # User dashboard pages
│       │   ├── dashboard.blade.php
│       │   ├── donations/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   ├── edit.blade.php
│       │   │   └── show.blade.php
│       │   ├── requests/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   └── show.blade.php
│       │   ├── notifications.blade.php
│       │   ├── support.blade.php
│       │   └── account.blade.php
│       │
│       ├── admin/                    # Admin panel pages
│       │   ├── dashboard.blade.php
│       │   ├── donations/
│       │   ├── requests/
│       │   ├── matches/
│       │   ├── transactions/
│       │   ├── users/
│       │   ├── categories/
│       │   ├── moderation/
│       │   ├── reports/
│       │   ├── audit/
│       │   └── settings/
│       │
│       └── components/               # Blade components
│           ├── status-badge.blade.php
│           ├── donation-card.blade.php
│           ├── empty-state.blade.php
│           ├── pagination.blade.php
│           └── breadcrumb.blade.php
│
├── routes/
│   ├── web.php                   # All web routes
│   └── README.md                 # Route grouping documentation
│
├── storage/
│   ├── public/                   # Web-accessible file storage (symlinked)
│   │   └── donations/
│   └── private/                  # Never web-accessible
│       └── proofs/
│
├── tests/
│   ├── Unit/
│   │   ├── DonationServiceTest.php
│   │   ├── RequestServiceTest.php
│   │   ├── MatchServiceTest.php
│   │   └── StatusMachineTest.php
│   │
│   └── Feature/
│       ├── AuthTest.php
│       ├── DonationFlowTest.php
│       ├── RequestFlowTest.php
│       └── AdminActionsTest.php
│
├── .env.example
├── README.md
├── ARCHITECTURE.md               # This file
├── BUSINESS_RULES.md
├── DATABASE.md
└── DESIGN_SYSTEM.md
```

---

## 3. Routing Strategy

### Route groups

```php
// Public (no auth)
Route::prefix('/')->group('routes/public.php');

// Auth
Route::prefix('/auth')->group('routes/auth.php');

// User (auth + active)
Route::prefix('/dashboard')->middleware(['auth', 'active'])->group('routes/user.php');

// Admin (auth + admin role, separate prefix)
Route::prefix('/admin')->middleware(['auth', 'admin'])->group('routes/admin.php');
```

### URL conventions

| Area | Pattern | Example |
|---|---|---|
| Public | `/page-name` | `/browse`, `/about` |
| User | `/dashboard/{resource}` | `/dashboard/donations` |
| Admin | `/admin/{resource}` | `/admin/donations` |
| Auth | `/auth/{action}` | `/auth/login` |

---

## 4. Authentication Architecture

### Public users
- Standard Laravel session-based authentication
- Session cookie: `httponly`, `secure` (production), `samesite=lax`
- Session lifetime: 2 hours (configurable), with "remember me" extending to 30 days
- Email verification required before any action

### Admins
- Separate login page at `/admin/login`
- Same `users` table but `role IN ('admin', 'superadmin')`
- Admin-specific middleware enforces role on every admin route
- Admin sessions have a shorter lifetime (8 hours)

### Middleware chain for protected routes

```
User routes:   auth → active_only → controller
Admin routes:  auth → admin_only  → controller
```

---

## 5. Service Layer Pattern

All business logic lives in Service classes, not Controllers.

```php
// Controller — thin, handles HTTP only
class DonationController
{
    public function store(StoreDonationRequest $request, DonationService $service)
    {
        $donation = $service->create($request->validated(), auth()->user());
        return redirect()->route('user.donations.show', $donation);
    }
}

// Service — owns business logic
class DonationService
{
    public function create(array $data, User $donor): Donation
    {
        // validate business rules
        // create record
        // dispatch notifications
        // write audit log
    }
}
```

---

## 6. Status Machine

All status transitions go through `StatusMachineService`. No code anywhere else may directly change an entity status.

```php
class StatusMachineService
{
    public function transitionDonation(Donation $donation, string $newStatus, User $actor, string $reason = null): void
    {
        $this->assertTransitionAllowed($donation->status, $newStatus);
        $oldStatus = $donation->status;
        $donation->update(['status' => $newStatus]);
        $this->auditService->log('DONATION_STATUS_CHANGED', $donation, $oldStatus, $newStatus, $reason, $actor);
        $this->notificationService->dispatchForStatusChange($donation, $newStatus);
    }
}
```

Allowed transitions are defined as a map — attempting an invalid transition throws a `InvalidStatusTransitionException`.

---

## 7. File Upload Architecture

```php
class FileUploadService
{
    public function storeDonationImage(UploadedFile $file, int $donationId): string
    {
        $this->validateType($file, ['image/jpeg', 'image/png', 'image/webp']);
        $this->validateSize($file, 10 * 1024 * 1024); // 10MB
        $name = bin2hex(random_bytes(16)) . '.' . $file->extension();
        return $file->storeAs("donations/{$donationId}", $name, 'public');
    }

    public function storeRequestProof(UploadedFile $file, int $requestId): string
    {
        $this->validateType($file, ['image/jpeg', 'image/png', 'application/pdf']);
        $this->validateSize($file, 10 * 1024 * 1024);
        $name = bin2hex(random_bytes(16)) . '.' . $file->extension();
        return $file->storeAs("proofs/{$requestId}", $name, 'private');
    }
}
```

Private files are served through an authenticated controller — never via direct URL.

---

## 8. Audit Logging

Every significant action calls `AuditService::log()`:

```php
class AuditService
{
    public function log(
        string $action,
        Model $target,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?string $reason = null,
        ?User $actor = null
    ): void {
        AuditLog::create([
            'actor_id'    => $actor?->id ?? auth()->id(),
            'actor_role'  => $actor?->role ?? auth()->user()?->role,
            'action'      => $action,
            'target_type' => $target->getTable(),
            'target_id'   => $target->id,
            'old_value'   => $oldValue,
            'new_value'   => $newValue,
            'reason'      => $reason,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }
}
```

---

## 9. Notification Architecture

```php
class NotificationService
{
    public function notify(User $user, string $type, string $title, string $message, ?string $relatedType = null, ?int $relatedId = null): void
    {
        Notification::create([
            'user_id'      => $user->id,
            'type'         => $type,
            'title'        => $title,
            'message'      => $message,
            'related_type' => $relatedType,
            'related_id'   => $relatedId,
        ]);

        // Dispatch email if user has email notifications enabled (Phase 2)
        // Mail::to($user)->queue(new NotificationEmail($type, $title, $message));
    }
}
```

---

## 10. Scheduled Jobs

```php
// app/Console/Kernel.php
$schedule->command('donations:expire')->daily();
$schedule->command('transactions:expire')->hourly();
$schedule->command('notifications:send-expiry-alerts')->hourly();
```

| Command | Action |
|---|---|
| `donations:expire` | Mark approved donations past expiration date as `expired` |
| `transactions:expire` | Mark transactions past claim deadline as `expired`; release reserved quantity |
| `notifications:send-expiry-alerts` | Send alerts for donations expiring in 7 days, claims expiring in 24 hours |

---

## 11. Environment Configuration

```env
APP_NAME="iCareDonate"
APP_ENV=production
APP_KEY=
APP_URL=https://icaredonate.ph

DB_CONNECTION=mysql
DB_HOST=
DB_DATABASE=icare_donate
DB_USERNAME=
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_FROM_ADDRESS="noreply@icaredonate.ph"
MAIL_FROM_NAME="iCareDonate"

FILESYSTEM_DISK=local
SESSION_DRIVER=database
SESSION_LIFETIME=120
QUEUE_CONNECTION=database

ADMIN_LOGIN_PATH=/admin/login  # change in production for obscurity
```

---

## 12. Git Strategy

### Branch naming

```
main              production-ready code
develop           integration branch
feature/{name}    new feature
fix/{name}        bug fix
hotfix/{name}     urgent production fix
release/{version} release preparation
```

### Commit convention

```
feat: add donation submission wizard
fix: release reserved quantity on transaction expiry
docs: update BUSINESS_RULES for partial matching
chore: add expiry scheduled command
test: add StatusMachineService unit tests
```

### PR rules
- No direct pushes to `main`
- PRs require at least one review
- PRs must include: what changed, how to test, screenshots (if UI)

---

## 13. Security Checklist

| Category | Implementation |
|---|---|
| Passwords | bcrypt (cost 12) via Laravel Hash |
| Sessions | Secure, httponly, samesite cookies |
| CSRF | Laravel CSRF middleware on all POST/PUT/DELETE |
| Authorization | Laravel Policies + middleware (server-enforced) |
| Input validation | Laravel Form Requests (server-side always) |
| SQL injection | Eloquent parameterized queries only |
| File uploads | Type + size validation, random filenames, private storage for proofs |
| Rate limiting | Laravel throttle middleware on auth + form endpoints |
| XSS | Blade auto-escaping; never use `{!! !!}` with user input |
| Audit | All admin actions logged with actor, reason, timestamp |

---

## 14. Performance Guidelines

- Paginate all lists (max 20 items per page for cards, 50 for admin tables)
- Eager-load Eloquent relationships — avoid N+1 queries
- Index all frequently filtered columns (see DATABASE.md)
- Lazy-load donation images
- Limit donation image files to 10MB; compress on upload where possible
- Use database queue (Phase 1) or Redis queue (Phase 2) for email sending
- Cache category list (rarely changes): `Cache::remember('categories', 3600, ...)`
- Minify CSS/JS for production (`npm run build`)

---

## 15. Development Phases

See the roadmap for full detail. At a technical level:

| Phase | Technical milestone |
|---|---|
| 0 | Repo, environment, DB migrations, folder structure |
| 1 | CSS tokens + component library (no pages yet) |
| 2 | Auth: register, login, verify, reset, roles, middleware |
| 3 | Public pages: home, browse, detail, FAQ, contact |
| 4 | Donation CRUD + image upload + admin review |
| 5 | Request + proof upload + admin review |
| 6 | Matching + transaction + quantity management |
| 7 | Notifications + support tickets |
| 8 | Admin dashboard + moderation + audit log UI |
| 9 | Security audit + privacy implementation |
| 10 | Accessibility + responsive refinement |
| 11 | Testing (unit + integration + security + UI) |
| 12 | Deployment + monitoring |

---

## 16. Deployment Architecture (Phase 1)

```
Internet
  └─→ Web server (Nginx)
        └─→ PHP-FPM (Laravel)
              └─→ MySQL database
              └─→ Local file storage
              └─→ SMTP mail service
```

### Production security requirements
- HTTPS only (TLS 1.2+)
- PHP display_errors = Off
- Laravel APP_DEBUG = false
- Database on private network (not publicly accessible)
- Private storage directory not web-accessible
- Regular automated database backups with tested restore procedure
- Server and application error logging to files (not to screen)
