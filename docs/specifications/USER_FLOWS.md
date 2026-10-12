# iCareDonate — Core User Flow Diagrams
> **Version:** 1.0 · **Date:** 2026-10-04  
> Describes the primary journeys for Donors, Requesters, and Admins.  
> These flows drive the development sequence. The vertical slice (Post → Verify → Request → Match → Claim → Complete) must work end-to-end before any secondary features are built.

---

## 1. The Core Product Loop

```mermaid
flowchart LR
    A["👤 Donor\nHas something\nto give"] -->|Posts donation| B["📋 Platform\nVerification"]
    B -->|Approved| C["🗂️ Available\nDonation Listing"]
    C -->|Requester finds it| D["📝 Request\n+ Proof of Need"]
    D -->|Admin reviews| E["🔗 Admin\nMatching"]
    E -->|Transaction created| F["📦 Claim\n& Pickup"]
    F -->|Confirmed| G["✓ Completed\nTransaction"]
    G --> H["📊 Both users\nsee in history"]
```

---

## 2. Donation Journey (Donor)

```mermaid
flowchart TD
    A([Start]) --> B[Create account / Log in]
    B --> C[Go to Post a Donation]
    C --> D["Step 1: Item details\nTitle, Category, Quantity, Condition"]
    D --> E["Step 2: More details\nExpiration, Description, Photos"]
    E --> F["Step 3: Pickup info\nLocation, Schedule, Method"]
    F --> G[Step 4: Review & Submit]
    G --> H[Donation submitted\nStatus: Pending Verification]
    H --> I{Admin review}
    I -->|Approved| J[Status: Approved\nDonation is public]
    I -->|Needs info| K[Donor notified\nStatus: Needs Information]
    K --> L[Donor updates\nand resubmits]
    L --> H
    I -->|Rejected| M[Donor notified\nwith reason]
    J --> N{Requests come in}
    N -->|Match created| O[Donor notified\nof match]
    O --> P[Donor sees transaction\nin My Donations]
    P --> Q{Item claimed}
    Q -->|Completed| R([Transaction Completed\nShows in history])
```

---

## 3. Request Journey (Requester)

```mermaid
flowchart TD
    A([Start]) --> B[Create account / Log in]
    B --> C[Browse Donations]
    C --> D[Find a suitable donation]
    D --> E[View Donation Detail]
    E --> F[Click 'Request this donation']
    F --> G["Fill request form:\nQuantity, Purpose, Urgency"]
    G --> H[Upload proof of need]
    H --> I[Submit request\nStatus: Submitted]
    I --> J{Admin reviews proof}
    J -->|Needs more info| K[Requester notified\nNeeds Information]
    K --> L[Requester uploads\nadditional proof]
    L --> J
    J -->|Approved| M[Status: Approved]
    J -->|Rejected| N[Requester notified\nwith reason]
    M --> O{Admin creates match}
    O --> P[Status: Matched\nRequester notified]
    P --> Q[Claim instructions sent\nPickup details provided]
    Q --> R[Requester picks up / receives item]
    R --> S[Claim recorded]
    S --> T{Admin confirms}
    T -->|Confirmed| U([Status: Completed\nShows in history])
```

---

## 4. Admin Review Flow — Donations

```mermaid
flowchart TD
    A([Admin receives notification]) --> B[Open Donation Review]
    B --> C[Review: title, category, quantity,\ncondition, photos, pickup info]
    C --> D{Decision}
    D -->|Approve| E[Status → Approved\nDonor notified\nAudit logged]
    D -->|Request info| F[Write reason\nStatus → Needs Information\nDonor notified\nAudit logged]
    D -->|Reject| G[Write reason\nStatus → Rejected\nDonor notified\nAudit logged]
    F --> H[Donor resubmits]
    H --> B
```

---

## 5. Admin Review Flow — Requests

```mermaid
flowchart TD
    A([Admin receives notification]) --> B[Open Request Review]
    B --> C[Review: purpose, urgency,\nproof documents, requester info]
    C --> D{Decision}
    D -->|Approve| E[Status → Approved\nRequester notified\nAudit logged]
    D -->|Request more proof| F[Write reason\nStatus → Needs Information\nRequester notified\nAudit logged]
    D -->|Reject| G[Write reason\nStatus → Rejected\nRequester notified\nAudit logged]
    F --> H[Requester resubmits proof]
    H --> B
    E --> I[Admin reviews matching candidates]
    I --> J[Create match/transaction]
    J --> K[Both donor and requester notified]
```

---

## 6. Matching & Transaction Flow (Admin)

```mermaid
flowchart TD
    A([Approved request awaiting match]) --> B[Admin opens Matching view]
    B --> C["Review matching criteria:\n• Category match\n• Available quantity\n• Location\n• Urgency\n• Request date"]
    C --> D{Full or partial match?}
    D -->|Full match| E[Create transaction\nFull quantity reserved]
    D -->|Partial match| F[Note partial amount\nCreate transaction\nPartial quantity reserved]
    D -->|No suitable match| G[Hold request\nMark for later review]
    E --> H[Transaction created\nClaim deadline set]
    F --> H
    H --> I[Donor notified\nRequester notified with claim instructions]
    I --> J{Claim recorded within deadline?}
    J -->|Yes| K[Status → Claimed\nAdmin confirms completion]
    K --> L([Status → Completed\nQuantity finalized\nAudit logged])
    J -->|No — deadline passes| M[Status → Expired\nReserved quantity released\nAdmins alerted]
    J -->|Cancelled| N[Status → Cancelled\nReserved quantity released\nAudit logged]
```

---

## 7. Donation Status Machine

```mermaid
stateDiagram-v2
    [*] --> draft : Donor creates
    draft --> pending_verification : Donor submits
    pending_verification --> needs_information : Admin requests info
    needs_information --> pending_verification : Donor resubmits
    pending_verification --> approved : Admin approves
    pending_verification --> rejected : Admin rejects
    approved --> fully_allocated : available qty = 0
    approved --> expired : expiration date passed
    draft --> cancelled : Donor cancels
    approved --> cancelled : Donor/admin cancels\n(no active transactions)
```

---

## 8. Request Status Machine

```mermaid
stateDiagram-v2
    [*] --> submitted : Requester submits
    submitted --> proof_review : Admin opens review
    proof_review --> needs_information : Admin requests more proof
    needs_information --> proof_review : Requester resubmits
    proof_review --> approved : Admin approves
    proof_review --> rejected : Admin rejects
    approved --> matched : Admin creates match
    matched --> pickup_scheduled : Pickup details confirmed
    pickup_scheduled --> claimed : Claim recorded
    claimed --> completed : Admin confirms
    submitted --> cancelled : Requester/admin cancels
    approved --> cancelled : Before match
    matched --> cancelled : Admin/requester cancels
    approved --> expired : No match within window
```

---

## 9. Transaction Status Machine

```mermaid
stateDiagram-v2
    [*] --> created : Admin creates transaction
    created --> pending_claim : Claim instructions sent
    pending_claim --> claimed : Claim recorded
    claimed --> completed : Admin confirms
    pending_claim --> expired : Deadline passed
    pending_claim --> cancelled : Admin or requester cancels
    created --> cancelled : Cancelled before instructions sent
```

---

## 10. First Vertical Slice — The MVP Flow

This is the first milestone. Every other feature builds on this working foundation.

```mermaid
sequenceDiagram
    participant D as 👤 Donor (User A)
    participant S as 🖥️ Platform
    participant A as 🔑 Admin
    participant R as 👤 Requester (User B)

    D->>S: Register + verify email
    D->>S: Post donation (20 canned goods)
    S->>A: Notify: donation pending review
    A->>S: Review + approve donation
    S->>D: Notify: donation approved
    S->>S: Donation appears in Browse

    R->>S: Register + verify email
    R->>S: Browse donations
    R->>S: Request 10 canned goods + upload proof
    S->>A: Notify: request pending review
    A->>S: Review proof + approve request
    S->>R: Notify: request approved

    A->>S: Create match (10 units)\n+ set claim deadline
    S->>D: Notify: match created
    S->>R: Notify: matched! claim instructions sent

    R->>S: Record claim (pickup)
    A->>S: Confirm transaction completed
    S->>D: Notify: transaction completed
    S->>R: Notify: transaction completed
    S->>S: Both users see it in history
    S->>S: Donation: 10 units remaining
```

---

## 11. Account & Auth Flows

```mermaid
flowchart LR
    subgraph Public
        A[Visit site] --> B{Has account?}
        B -->|No| C[Register]
        C --> D[Verify email]
        D --> E[Active account]
        B -->|Yes| F[Log in]
        F --> E
    end

    subgraph Recovery
        G[Forgot password] --> H[Enter email]
        H --> I[Receive reset link]
        I --> J[Set new password]
        J --> F
    end

    subgraph Admin
        K[/admin/login] --> L[Admin credentials]
        L --> M{Role check}
        M -->|admin or superadmin| N[Admin dashboard]
        M -->|user or invalid| O[Access denied]
    end
```

---

## 12. Public User Navigation Map

```
Home
├── How It Works
├── Categories
│   └── Browse by category
├── Browse Donations
│   └── Donation Detail
│       └── [Login to request]
├── About
├── Contact
├── FAQ / Help
└── Legal
    ├── Privacy Policy
    └── Terms of Service

Auth
├── Login
├── Register
├── Forgot Password
└── Verify Email
```

---

## 13. User Dashboard Navigation Map

```
Dashboard (overview)
├── My Donations
│   ├── Post a Donation (wizard)
│   └── Donation Detail / Edit / Track
├── Browse Donations
│   └── Donation Detail
│       └── Request Resource
├── My Requests
│   └── Request Detail / Track / Upload Proof
├── Notifications
├── Support
└── Account Settings
    ├── Profile
    ├── Change Password
    └── Delete Account
```

---

## 14. Admin Panel Navigation Map

```
Admin Dashboard (action queue)
├── Donations
│   ├── Pending review
│   ├── All donations
│   └── Donation detail / Review / Approve / Reject
├── Requests
│   ├── Pending review
│   ├── All requests
│   └── Request detail / Review / Approve / Reject
├── Matching
│   ├── Approved requests awaiting match
│   └── Create match / transaction
├── Transactions
│   ├── Active
│   ├── Expiring soon
│   └── History
├── Users
│   ├── All users
│   ├── User detail
│   └── Manage status / role
├── Categories
│   ├── All categories
│   └── Create / Edit / Deactivate
├── Moderation
│   ├── Problem reports
│   └── Warnings / Suspensions / Bans
├── Reports
│   ├── Donation report
│   ├── Request report
│   └── Transaction report
├── Audit Log
└── Settings
```

---

## 15. Development Order (summary)

```mermaid
gantt
    title iCareDonate Development Phases
    dateFormat YYYY-MM-DD
    section Foundation
    Phase 0 - Project Setup         :p0, 2026-10-05, 7d
    Phase 1 - Design System CSS     :p1, after p0, 10d
    section Core Backend
    Phase 2 - Authentication        :p2, after p1, 10d
    Phase 3 - Public Website        :p3, after p2, 14d
    Phase 4 - Donation System       :p4, after p3, 14d
    Phase 5 - Request System        :p5, after p4, 14d
    Phase 6 - Matching + Transactions :p6, after p5, 14d
    section Features
    Phase 7 - Notifications         :p7, after p6, 7d
    Phase 8 - Admin Dashboard       :p8, after p7, 21d
    section Quality
    Phase 9 - Security + Privacy    :p9, after p8, 10d
    Phase 10 - Accessibility + Responsive :p10, after p9, 7d
    Phase 11 - Testing              :p11, after p10, 14d
    Phase 12 - Deployment           :p12, after p11, 7d
```
