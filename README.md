# iCareDonate

> **Give what you can. Help where it matters.**

iCareDonate is a community resource-donation platform connecting donors of tangible, non-cash essentials (school supplies, canned food grains, clothing, assistive medical devices, and household emergency items) with verified community beneficiaries.

---

## 🛠 Technology Stack

- **Backend:** PHP 8.2+ / Laravel 12 (Routing, Controllers, Eloquent ORM, Request Validation, Session Auth, Middleware, Blade Templates)
- **Frontend:** HTML5, Vanilla CSS3 (Custom Design Token System), Vanilla JavaScript (UI Enhancements & Previews), Clean SVG Icons
- **Database:** MySQL (through XAMPP)
- **Asset Pipeline:** Vite / Self-contained CSS & JS in `public/`
- **Environment:** XAMPP (Apache + MySQL), Composer, PHP CLI

---

## 🚀 Quick Setup & Local Launch

### 1. Database Configuration
Ensure MySQL is started in XAMPP (Port 3306).  
The `.env` is configured for local MySQL:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=icaredonate
DB_USERNAME=root
DB_PASSWORD=
```

### 2. Reset and Seed Database (Fresh Demo Data)
Run with XAMPP's PHP:
```bash
C:\xampp\php\php.exe artisan migrate:fresh --seed
```

### 3. Start Local Development Server
```bash
C:\xampp\php\php.exe artisan serve
```
Then visit: **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

---

## 🔑 Pre-Seeded Test Accounts

All accounts use the password: `password123`

| Role | Name | Email | Password | Access & Features |
|---|---|---|---|---|
| **Administrator** | System Administrator | `admin@icaredonate.org` | `password123` | Full Admin Portal (`/admin/dashboard`), donation reviews, request reviews, matching engine, claim supervision, user moderation, audit logs, reports |
| **Donor Member** | Maria Elena Santos | `maria@example.com` | `password123` | Member Dashboard (`/dashboard`), post donations, edit items, track stock quantities, mark handoff completed |
| **Requester Member** | Juan Dela Cruz | `juan@example.com` | `password123` | Member Dashboard, browse donations, submit resource request with proof documents, view claim slip & confirm pickup |
| **Donor Member 2** | Elena Ramos | `elena@example.com` | `password123` | Medical wheelchairs & clothing donor |
| **Requester Member 2**| Carlos Mendoza | `carlos@example.com` | `password123` | Calamity relief applicant |

---

## 🧪 Step-by-Step Feature Testing Guide

### Flow 1: Visitor & Public Experience
1. Visit `http://127.0.0.1:8000`.
2. Explore the live platform metrics, category cards, and approved donation previews.
3. Visit **Browse Donations** (`/donations`) and test filtering by category (e.g. *Food*, *School Supplies*) and condition.
4. Click on any item to view its details, live inventory breakdown, and logistics instructions.

### Flow 2: Donor Experience (Posting a Donation)
1. Sign in as `maria@example.com` / `password123` at `/login`.
2. Click **+ Post a Donation** or navigate to `/donations-post/create`.
3. Fill out the title, category, quantity, unit, condition, pickup address, and optional photo.
4. Submit the donation. Notice it redirects to the **Preview screen** with status `Pending Verification`.
5. Check **My Donations** (`/my-donations`) to view live counts (`Total`, `Available`, `Reserved`, `Claimed`).

### Flow 3: Administrator Verification
1. Sign in as `admin@icaredonate.org` / `password123`.
2. Go to **Review Donations** (`/admin/donations`).
3. Open any donation marked `Pending Verification`.
4. Run through the inspection checklist and click **Approve & Make Available**.
5. The donation status instantly shifts to `Approved` and appears on the public browse directory.

### Flow 4: Beneficiary Application (Requesting an Item)
1. Sign in as `juan@example.com` / `password123`.
2. Go to `/donations`, pick an available donation, and click **Request This Resource**.
3. Specify the quantity needed (cannot exceed available stock), urgency, reason, and upload a proof document.
4. Submit the request and view the **Request Tracker** (`/requests/{id}`) showing live progress.

### Flow 5: Matching, Claims & Handoff Completion
1. In the Administrator portal, visit **Resource Matching** (`/admin/matches`).
2. Pair an approved request with an available donation, choose pickup date, and click **Execute Match & Generate Claim Code**.
3. The system:
   - Reserves the stock in MySQL (`quantity_reserved`).
   - Issues a unique Claim Code (e.g. `CLAIM-XXXXXX`).
   - Dispatches notifications to both requester and donor.
4. Sign in as the requester (`juan@example.com`), open **My Requests** or **Dashboard**, and click **View Claim Slip**.
5. Click **Confirm Pickup Intention**.
6. The donor (`maria@example.com`) or Admin can click **Mark Handoff Completed & Received**.
7. The item status transitions to `Completed`, stock shifts from `reserved` to `claimed`, and audit trails are logged.

---

## 🛡️ Technical Defense Cheat Sheet

### "Why Laravel?"
> *"We utilized Laravel for routing, authentication sessions, controller business logic, Eloquent ORM relationships, backend validation, role middleware, and server-rendered Blade templates, while keeping the UI in clean HTML5, CSS3, and JavaScript as specified in our rubric."*

### "What prevents a user from claiming more items than available?"
> *"Our `RequestController` and `MatchController` enforce strict database-level checks. When an allocation is created, `quantity_reserved` is updated inside MySQL. The available balance (`quantity_total - quantity_reserved - quantity_claimed`) is computed dynamically, preventing over-allocation."*

### "How are administrators separated from normal users?"
> *"Administrator routes are guarded by the `AdminMiddleware` alias in `bootstrap/app.php` and `routes/web.php`. Public user registration forms strictly assign `role = 'user'`. Attempts by normal users to access `/admin/*` result in an immediate `403 Forbidden` response."*

### "Where is system accountability tracked?"
> *"All administrative approvals, rejections, moderation warnings, and suspensions write immutable entries to the `audit_logs` table via `AuditLog::log()`, visible under `/admin/audit-logs`."*
