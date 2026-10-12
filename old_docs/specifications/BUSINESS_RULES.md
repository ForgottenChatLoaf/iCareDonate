# iCareDonate — Business Rules
> **Version:** 1.0 · **Date:** 2026-10-04  
> This document defines the authoritative business logic for the platform.  
> All backend validation must enforce these rules at the server/API/database layer — not only the UI.

---

## 1. Core Platform Definition

iCareDonate is a **community resource-donation platform** for physical/in-kind donations only.

- **In scope:** Food, school supplies, clothing, medical items, household goods, and other physical resources
- **Out of scope (v1):** Cash/monetary donations, online payments, digital goods
- **Core loop:** Donor posts item → Platform verifies → Requester submits request with proof → Platform verifies need → Admin matches → Transaction created → Item claimed → Completed

---

## 2. User Accounts

### 2.1 Registration
- Public signup is open to donors and requesters only
- Admin accounts **cannot** be created through public signup under any circumstances
- All new accounts require email verification before any action can be taken
- A user may register only one account per email address

### 2.2 Account statuses
| Status | Allowed actions |
|---|---|
| `pending_verification` | None until email verified |
| `active` | Full access per role |
| `suspended` | Read-only; cannot post, request, or interact |
| `banned` | No access; blocked from login |

### 2.3 Roles
| Role | Description |
|---|---|
| `donor` | Can post donations; can also make requests |
| `requester` | Can browse and request donations |
| `user` | Combined role; can both donate and request |
| `admin` | Full platform administration; separate login |
| `superadmin` | All admin capabilities + manage admin accounts |

- A user can act as both donor and requester simultaneously
- Role elevations (to admin) require explicit admin action

### 2.4 Suspension and banning
- Suspended accounts are frozen — no posting, requesting, or interacting — but existing records are preserved
- Banned accounts are blocked from login entirely
- Admins must record a reason for any suspension or ban (stored in audit log)
- Only admins with at least `admin` role may suspend or ban accounts

---

## 3. Donations

### 3.1 Posting rules
- A donation can only be posted by an `active` account
- Suspended or banned accounts cannot post donations
- Required fields: title, category, quantity, condition, pickup method, pickup location
- Optional but encouraged: photos (up to 5), expiration date, description
- A donation starts in `draft` status and must be explicitly submitted for verification

### 3.2 Verification
- A donation cannot become publicly visible until an admin sets it to `approved`
- Admins may set status to `needs_information` and must provide a reason
- The donor may resubmit with additional information; this returns the donation to `pending_verification`
- Admins may `reject` a donation; the donor is notified with a reason

### 3.3 Quantity management
- Available quantity = total quantity − reserved quantity − claimed quantity
- When a request is matched and a transaction created, the matched quantity moves from "available" to "reserved"
- Reserved quantity is released back to available if:
  - The transaction is cancelled before claim
  - The requester cancels their request
  - The claim deadline passes without a claim being recorded
- When a claim is completed, reserved quantity moves to "claimed" (permanent)
- A donation cannot accept new requests once available quantity reaches 0
- A donation is automatically set to `fully_allocated` when available quantity = 0

### 3.4 Donation statuses (state machine)

```
draft
  └─→ pending_verification  (on submission)
        └─→ needs_information  (admin requests more info)
        │     └─→ pending_verification  (donor resubmits)
        └─→ approved  (admin approves)
        │     └─→ fully_allocated  (available qty = 0)
        │     └─→ expired  (past expiration date, no claims)
        └─→ rejected  (admin rejects)
cancelled  (donor cancels at any time before approval or when no active transactions)
```

### 3.5 Expiration
- Donations with an expiration date are automatically flagged as `expired` if the date passes and no claim has been completed
- Admins are alerted about donations expiring within 7 days
- Expired donations cannot receive new requests, but existing in-progress transactions may continue at admin discretion

### 3.6 Editing
- Donors may edit a donation only when it is in `draft` or `needs_information` status
- Editing a `pending_verification` donation is not allowed (must wait for admin review to complete)
- Approved donations cannot have quantity increased by the donor; only admins may adjust approved quantities with a reason

---

## 4. Requests

### 4.1 Submission rules
- A requester can only submit a request for an `approved` donation with available quantity > 0
- The requested quantity cannot exceed the available quantity at time of submission
- Suspended or banned accounts cannot submit requests
- A requester cannot submit duplicate active requests for the same donation

### 4.2 Proof of need
- Requesters must upload proof of need (document, photo, or other evidence) as part of the request
- Admins review the proof before approving the request
- Admins may request additional proof; status moves to `needs_information`
- Proof documents are private — never publicly accessible

### 4.3 Request statuses (state machine)

```
submitted
  └─→ proof_review  (admin opens review)
        └─→ needs_information  (admin requests more info)
        │     └─→ proof_review  (requester resubmits)
        └─→ approved  (admin approves request)
        │     └─→ matched  (admin creates a transaction)
        │           └─→ pickup_scheduled
        │                 └─→ claimed
        │                       └─→ completed
        └─→ rejected  (admin rejects request)
cancelled  (requester cancels before match, or admin cancels)
expired  (no action within defined window)
```

### 4.4 Quantity reservation
- When a request is approved and a match is made, the requested quantity is reserved from the donation's available pool
- Reservation is released if the transaction is cancelled or the claim expires

### 4.5 Matching criteria
Admins consider the following when creating matches:
1. Category / item type match
2. Available quantity vs. requested quantity
3. Location proximity (pickup feasibility)
4. Request urgency (admin-assigned)
5. Request date (FIFO where urgency is equal)
6. Requester verification status

---

## 5. Matching & Transactions

### 5.1 Who creates matches
- Matches are **admin-created** (manual review). The system may suggest, but does not auto-match.

### 5.2 Partial matching
- A single request may be partially matched (e.g., request for 20 units matched to 10 available)
- Partial matches must be explicitly noted in the transaction record
- Requesters are notified of partial match amounts

### 5.3 Transaction statuses

```
created  (admin creates transaction)
  └─→ pending_claim  (claim instructions sent to requester)
        └─→ claimed  (requester records claim / pickup)
              └─→ completed  (admin confirms completion)
cancelled  (cancelled before claim by admin or requester)
expired  (claim deadline passed)
```

### 5.4 Claim deadlines
- Every transaction has a claim deadline set at creation time
- Default: 7 days from transaction creation (configurable per admin)
- Admins receive alerts for claims expiring within 24 hours
- Expired transactions release reserved quantity back to the donation pool

### 5.5 Completion
- Both donor and requester see the transaction in their history upon completion
- Completion is confirmed by admin or via mutual acknowledgment (TBD based on implementation)

---

## 6. Categories

- Categories are created and managed by admins only
- Categories cannot be deleted if they contain existing donations; they may only be deactivated
- Donations must belong to exactly one category
- Category names must be unique

---

## 7. Notifications

### 7.1 Notification triggers

| Event | Recipients |
|---|---|
| Donation submitted | Admin |
| Donation approved | Donor |
| Donation rejected | Donor |
| Donation needs info | Donor |
| Request received | Admin |
| Request approved | Requester |
| Request rejected | Requester |
| Request needs info | Requester |
| Match created | Donor, Requester |
| Claim expiring (24h) | Requester, Admin |
| Claim completed | Donor, Requester |
| Transaction cancelled | Donor, Requester |
| Account suspended | User |
| Admin message | User |

### 7.2 Notification rules
- Notifications are user-specific and private
- Unread count must be visible in the interface at all times
- Admins may send manual messages to users through the notification system

---

## 8. Moderation

### 8.1 Problem reports
- Any active user may report a donation listing as inappropriate, fraudulent, or misleading
- Reports are routed to admin review
- Admins must review and resolve reports within a reasonable time
- Reported donations remain visible until admin takes action

### 8.2 Moderation actions available to admins
- Warning (notified to user, recorded)
- Content removal (donation or request removed)
- Suspension (account suspended with reason)
- Ban (permanent account block with reason)

### 8.3 Moderation records
- All moderation actions must be recorded with:
  - Admin who took the action
  - Target user/content
  - Action type
  - Reason
  - Timestamp

---

## 9. Privacy

### 9.1 Information visibility rules

| Information | Public visitors | Logged-in users | Matched parties | Admins |
|---|---|---|---|---|
| Donor name | Hidden | Hidden | First name only | Full |
| Donor contact | Hidden | Hidden | Only after match confirmed | Full |
| Requester name | Hidden | Hidden | Hidden until match | Full |
| Requester contact | Hidden | Hidden | Only after match confirmed | Full |
| Proof documents | Never | Never | Never | Full |
| Full addresses | Hidden | Hidden | Only when needed for pickup | Full |

### 9.2 Proof documents
- Proof-of-need documents must be stored in a private, non-public directory
- Proof documents must never be accessible via predictable public URLs
- Access requires admin authentication or a time-limited signed URL

### 9.3 Personal data minimization
- Collect only what is necessary
- Do not display full contact details to parties who do not need them
- Apply the Philippines Data Privacy Act of 2012 standards as the baseline compliance framework

### 9.4 Account deletion
- Users may request account deletion
- Deletion anonymizes personal data while preserving transaction records for accountability
- Active transactions must be resolved or cancelled before deletion is processed

---

## 10. Security

### 10.1 Authentication
- Passwords must be hashed using bcrypt (cost factor ≥ 12) or Argon2
- Sessions must use secure, httponly, same-site cookies
- Admin login is on a separate path from public login
- Failed login attempts must be rate-limited (max 5 per 10 minutes per IP)
- Email verification is required before account activation

### 10.2 Authorization
- Every server-side route and API endpoint must check:
  1. Is the user authenticated?
  2. Does the user have the required role?
  3. Does the user have ownership of the resource being accessed?
- Hiding a button in the UI is **not security**. All checks must be server-enforced.

### 10.3 File uploads
- Accepted types: JPEG, PNG, PDF, WEBP (per context)
- Maximum file size: 10MB per file
- Filenames must be sanitized or replaced with secure random names
- Files must be scanned for invalid content
- Donation images: stored in public directory
- Proof documents: stored in private directory, never public

### 10.4 Input validation
- All input must be validated server-side regardless of client-side validation
- SQL injection protection via parameterized queries (never string concatenation)
- CSRF protection on all state-changing operations
- XSS protection via output escaping

---

## 11. Audit Logging

All significant admin and system actions must be logged.

### 11.1 Required audit fields
| Field | Description |
|---|---|
| `id` | Unique log entry ID |
| `actor_id` | User who performed the action |
| `actor_role` | Role at time of action |
| `action` | What was done (see action types) |
| `target_type` | Entity type (donation, request, user, etc.) |
| `target_id` | Entity ID |
| `old_value` | Previous state (where applicable) |
| `new_value` | New state |
| `reason` | Admin-provided reason (required for status changes) |
| `ip_address` | IP address of actor |
| `created_at` | Timestamp |

### 11.2 Auditable actions
- Donation: create, status change, edit, delete
- Request: create, status change, proof update
- Match/Transaction: create, cancel, complete
- User: register, verify, suspend, ban, role change, delete
- Admin: login, logout, any admin action
- Category: create, edit, deactivate

---

## 12. Reports

### 12.1 Required platform reports
| Report | Content |
|---|---|
| Donation report | Listings by status, category, date |
| Request report | Requests by status, category, date |
| Transaction report | Transactions by status, date, match type |
| User report | Account counts by status and role |
| Moderation report | Actions taken by type and date |

### 12.2 Report access
- Reports are accessible to admin role and above only
- Reports must not expose individual personal data in bulk exports

---

## 13. Edge Cases & Special Rules

| Scenario | Rule |
|---|---|
| Donor cancels donation with active requests | Admin must manually resolve open requests before cancellation |
| Requester requests more than available | System must block at validation; not just display-level |
| Donation expires mid-transaction | Transaction may continue at admin discretion; new requests blocked |
| Same user is donor and requester | Allowed; must use different listings (cannot claim own donation) |
| Admin approves own submission | Not applicable (admins cannot submit donations) |
| Multiple requests for same quantity | Handled via reservation — first approved+matched wins |
| Claim deadline extended | Admin may extend; extension is logged in audit |
| Photo removed after approval | Requires admin action; logged |
