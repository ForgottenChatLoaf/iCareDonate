# iCareDonate: Next Week Change Plan

This plan lists every change the team intends to make next week. Items are ordered from **least important (1)** to **most important (11)**, ranked by how much each one affects the midterm rubric. Since the most important work is at the bottom, **start from the bottom of this list and work upward** if time runs short.

**Reminder of the constraint:** there is no database until the final defense. Everything below uses HTML, CSS, JavaScript, and PHP with sample data and sessions.

## Rubric Reference

| Criteria | Points |
|---|---|
| Sitemap and Navigation Implementation | 20 |
| Wireframe and Mockup Implementation | 20 |
| Layout, Color, Typography and White Space | 15 |
| Website Completeness and Functionality: Client/User | 15 |
| Website Completeness and Functionality: Admin | 15 |
| Presentation and Design Explanation | 15 |

## Summary Table

| # | Change | Main rubric link | Effort |
|---|---|---|---|
| 1 | Verified and certified badges | Presentation (extra feature) | Small |
| 2 | Public page animation and effects | Layout and visual quality | Medium |
| 3 | Small polish items | Layout and visual quality | Small |
| 4 | One Contact Us page only | Sitemap, wireframe | Small |
| 5 | Global text editing | Layout, typography, presentation | Medium |
| 6 | Responsive and accessibility check | Layout and visual quality | Medium |
| 7 | Design tweaks for admin and user accounts | Layout, client, admin | Medium |
| 8 | Connect frontend to backend (login and sessions) | Client, admin | Medium |
| 9 | Demo flow and presentation preparation | Presentation | Medium |
| 10 | Admin pages completion | Admin, sitemap | Large |
| 11 | Match sitemap and wireframe to actual admin and user pages | Sitemap, wireframe, client, admin | Large |

---

## 1. Verified and Certified Badges (Optional Addition)

**Priority:** Lowest. This is a new feature that is not in the approved wireframe, so it must be explained as an enhancement during the presentation.

**Why add it:** It supports the rule that the admin checks proof from both sides, and it makes trust visible to visitors.

### Proposed badge types

| Badge | Who gets it | How it is earned |
|---|---|---|
| Verified Donor | Individual donors | Admin approves the donor's proof that the donation is real |
| Verified Requester | Individual requesters | Admin approves proof that the need is genuine |
| Certified Sponsor | Businesses, schools, or organizations that support the platform | Admin reviews the organization's documents and approves it |
| Trusted Donor (optional) | Donors with repeated approved donations | Reaches a set number of completed donations, to be decided by the team |

### Where it appears

- On donation and request cards in Browse Donation
- On the item detail page, beside the poster's name
- On the user's own profile and My Donation or My Request pages
- In the admin approval queue, where the admin can grant or remove the badge
- In the About Us sponsor area for Certified Sponsors

### Design notes

- Small rounded label with a shield and check icon
- Use cobalt and sky tint for Verified, amber for Certified Sponsor. **Do not use green.**
- Tooltip text: "Identity and proof reviewed by the iCareDonate admin team."
- Keep the badge small so it does not crowd the cards.

### Tasks

- [ ] Add a `verified` and `badge_type` field to the sample user data
- [ ] Create one reusable badge component (a PHP function or include)
- [ ] Add a "Mark as verified" button in the admin users list or approval page
- [ ] Add a short FAQ entry: "What does the Verified badge mean?"
- [ ] Add one line in the presentation explaining that this is an added feature and why

---

## 2. Public Page Animation and Effects

**Priority:** Low. The public page looks static, but the rubric rewards fidelity to the design more than motion.

**Stack rule:** Use only plain CSS and vanilla JavaScript so it matches the project's technology stack. No extra frameworks.

**How it connects to PHP:** Animations do not depend on the backend. Put the CSS and JS links inside the shared `header.php` and `footer.php`, and every page that includes them gets the same effects.

### Suggested effects

- **Scroll reveal:** sections fade and slide up slightly as they enter the screen (using `IntersectionObserver`)
- **Hero entrance:** headline, tagline, and buttons appear one after another
- **Card hover:** category and donation cards lift with a soft shadow
- **Button feedback:** smooth color change and a small press effect
- **Impact numbers:** count up when scrolled into view (fixed sample numbers)
- **Navbar:** adds a light shadow after scrolling, and the active link has an animated underline
- **FAQ accordion:** smooth open and close
- **Back to top button:** fades in after scrolling down

### Guardrails

- Keep durations between 200 and 600 ms, and keep effects subtle
- Respect `prefers-reduced-motion` so motion turns off for users who need it
- Animate only `opacity` and `transform` for smooth performance
- Do not animate every element, or the page will feel busy

### Tasks

- [ ] Create `assets/css/animations.css` and `assets/js/main.js`
- [ ] Link both in the shared header and footer
- [ ] Apply a reveal class to the main sections of each public page
- [ ] Test on a slow laptop to confirm it stays smooth

---

## 3. Small Polish Items

Quick fixes that make the site feel consistent. Each takes only a few minutes.

- [ ] **Tagline consistency:** the footer says "Give what you can. Help where it matters." while the home slogan is "Give with care. Receive with dignity." Choose one main slogan, or decide clearly where each one is used.
- [ ] **Footer email contrast:** the email link is dark blue on dark navy and is hard to read. Use a lighter color.
- [ ] **Active navigation link:** the nav highlights "Contact us" while the breadcrumb says Help Center. Make the active state match the page.
- [ ] **Naming consistency:** the sitemap says "Browse Donation" while the footer says "Browse Donations". Pick one wording and use it everywhere.
- [ ] **Careers wording:** the footer says "Careers & Volunteers" but the sitemap says "careers". Update whichever one is wrong so they match.
- [ ] **Favicon:** use the heart and handshake logo
- [ ] **Page titles:** every page gets a clear title, such as "About Us | iCareDonate"
- [ ] **Custom 404 and access denied pages** in the same brand style
- [ ] **Consistent breadcrumbs** on inner pages

---

## 4. One Contact Us Page Only

**Problem:** There are two different contact pages. The Contact Us page has an inquiry form, and the Contact Support page has a support ticket form. The two do not match and should not both exist as public pages.

### Comparison

| | Contact Us (inquiry form) | Contact Support (ticket desk) |
|---|---|---|
| Layout | Info cards plus a form | Single centered form |
| Fields | Name, email, type, message | Name, email, issue category, donation post ID, subject, explanation, attachment |
| Best for | Any visitor, including guests | Logged in users with a specific problem |
| Matches approved sitemap | Yes (phone, address, email, inquiry form) | Partly (listed under Support in the footer) |

### Recommendation

**Keep the Contact Us inquiry form as the only public contact page.** It matches the approved sitemap and works for visitors who are not logged in. Then move the ticket idea into the **Message and Support** page inside the user account, where fields like Donation Post ID and attachments make sense because the user is logged in.

If the team prefers the ticket desk instead, then use it as the single page, but add the phone, address, and email cards so the sitemap content is still covered.

### Final specification for the winning page

- Heading: "Contact Us" with the short subtitle
- Left: three info cards (phone, address or location, email)
- Right: inquiry form with Name, Email, Type, and Message
- Type options: General, Donation question, Request question, Item verification, Pickup coordination, Safety concern, Technical issue
- Add a clear success message after sending (sample behavior for now)
- The footer link "Contact Support" points to this same page, or to Message and Support when the user is logged in
- Remove the duplicate page from the navigation and footer

### Tasks

- [ ] Team decides which page wins
- [ ] Remove or redirect the other page
- [ ] Update the footer and breadcrumbs
- [ ] Confirm the **official** phone number, address, and email (the current phone number looks like a placeholder)
- [ ] Add form validation and a thank you message

---

## 5. Global Text Editing

**Goal:** Every piece of text reads clearly, professionally, and consistently, with **no dashes of any kind**.

### Rules

1. No em dashes or en dashes anywhere.
2. No spaced hyphens used as dashes (like "word - word").
3. Replace dashes with a comma, period, colon, or the word "to" for ranges.
4. Avoid hyphens where a plain rewording works. Decide as a team whether real compound words are allowed.
5. Use a warm, respectful, direct tone. Short sentences. No slang.
6. Keep terms consistent: **donor**, **requester**, **admin**, **donation post**, **request**.
7. Use "Log in" as the action (verb) and "Login" only as a page name, and use "Sign up" the same way.
8. Use one heading style (Title Case or Sentence case) across the whole site.
9. Use exclamation marks rarely.

### Examples

| Before | After |
|---|---|
| Give what you can, help where it matters | Give what you can. Help where it matters. |
| Open Monday - Friday | Open Monday to Friday |
| Need help? Contact us - we respond fast | Need help? Contact us and we will respond as soon as possible. |

### Where to search

Search the whole project for these patterns: `—`, `–`, `&mdash;`, `&ndash;`, ` - `, and `--`. Check these places:

- [ ] All PHP and HTML pages and the shared header and footer
- [ ] Page titles and meta descriptions
- [ ] Button labels, placeholders, error messages, and alt text
- [ ] FAQ, Terms of Service, and Privacy Policy content
- [ ] Sample data text
- [ ] Phone number format (the current number contains a hyphen, so decide how to write it)
- [ ] The presentation slides and documents, for consistency

### Content still to confirm

- [ ] Official "Last Updated" date
- [ ] Third party services used
- [ ] Official contact email, phone, and address

---

## 6. Responsive and Accessibility Check

- [ ] Test every page at 1440, 1024, 768, and 375 pixel widths
- [ ] Check that tables and dashboards do not overflow on small screens
- [ ] Make the sidebar collapse into a menu on mobile
- [ ] Check text contrast against the background (especially footer links)
- [ ] Add visible focus states for keyboard navigation
- [ ] Give every form field a proper label
- [ ] Add alt text to all images
- [ ] Make buttons and links large enough to tap

---

## 7. Design Tweaks for Admin and User Accounts

**Goal:** The logged in side should look like part of the same brand as the public page, with a consistent layout.

### Consistency checklist

- [ ] One shared dashboard layout: sidebar, top bar, and content area
- [ ] Active sidebar item clearly highlighted
- [ ] Page title and breadcrumb at the top of each page
- [ ] User menu with name and logout
- [ ] Same card style, corner radius, and shadows across all pages
- [ ] Same table style for lists (donations, requests, users)
- [ ] Status labels using the approved colors: approved `#2E6FDB`, pending `#E8A317`, rejected `#C0392B`
- [ ] Coral `#E0553D` only for the main action on each page
- [ ] Poppins for headings and Inter for body text
- [ ] Even spacing and enough white space
- [ ] Empty states (for example, "No donations yet" with a button to post one)
- [ ] Form validation messages and success messages
- [ ] Confirmation popup before approve, reject, and delete actions
- [ ] No green anywhere

---

## 8. Connect Frontend to Backend (Login and Sessions)

**Goal:** A working shared login with role based access, using hardcoded test accounts and no database.

### Steps

1. Rename pages that need PHP from `.html` to `.php`.
2. Create `includes/header.php` and `includes/footer.php`, then include them on every page.
3. Create `includes/accounts.php` with three test accounts: donor, requester, and admin.
4. Make the login form post to `login_process.php`, which checks the account and starts a session.
5. Redirect by role: admin goes to the admin dashboard, others go to the user dashboard.
6. Use the homepage buttons `Donate now` and `Request help` to pass an `intent` value to the login page.
7. Add `includes/auth.php` with `requireLogin()` and `requireRole()`, and call it at the top of every protected page.
8. Add `logout.php` to destroy the session.
9. Show the logged in user's name in the header or dashboard.

### Tasks

- [ ] All user pages require login
- [ ] All admin pages require the admin role
- [ ] Test by opening an admin link while logged out or logged in as a donor
- [ ] Add a `reset.php` that clears the session before each demo
- [ ] Check relative paths for pages inside the admin folder

---

## 9. Demo Flow and Presentation Preparation

The Presentation criterion is worth 15 points and depends on explaining how the final website follows the sitemap, wireframe, and mockup.

### Demo flow to practice

1. Visitor browses the public pages and uses the Contact Us form
2. Visitor clicks `Donate now` and logs in as a donor
3. Donor posts a donation, which appears as **pending**
4. Admin logs in and sees it in the approval queue
5. Admin approves it, and the dashboard counts update
6. The donation now appears in Browse Donation with an approved label
7. Requester logs in and posts a request
8. Admin approves and matches the request with the donation

### Preparation checklist

- [ ] Write a short script and decide who presents each part
- [ ] Prepare a slide or table of **sitemap versus final site** differences, with a reason for each change
- [ ] Prepare a slide on design decisions: palette, typography, logo, and layout
- [ ] Prepare a "References and future features" slide (BayanihanPH, Give.Asia, Caritas Manila, DSWD, Kiva, charity: water)
- [ ] State clearly that sample data stands in for the database planned for the final defense
- [ ] Keep test account details in notes, not on screen
- [ ] Do a full rehearsal at least once, with timing

---

## 10. Admin Pages Completion

**Priority:** Very high. The admin side is worth 15 points and was not yet fully defined in the sitemap.

**First step:** Finish and approve the admin sitemap as a team, because the admin pages cannot be matched to a sitemap that does not exist yet.

### Proposed admin sidebar (for team approval)

| Sidebar item | What it shows |
|---|---|
| Dashboard | Counts of pending, approved, and rejected donations and requests, plus recent activity |
| Donation Approvals | Queue of pending donations with the donor's proof, plus approve and reject (with reason) |
| Request Approvals | Queue of pending requests with the requester's proof, plus approve and reject (with reason) |
| Matching | Link an approved donation to an approved request |
| Users | List of donors and requesters, with the verified badge control |
| Reports | Simple totals of donations, requests, matches, and deliveries |
| Messages and Support | Inquiries sent through Contact Us and from users |
| Account Settings | Admin profile and password |
| Logout | Ends the session |

### Tasks

- [ ] Finalize the admin sitemap
- [ ] Build each admin page with the shared dashboard layout
- [ ] Use sample data so every list has realistic entries
- [ ] Make approve and reject actually change the status (session based)
- [ ] Make sure approved items appear on the user side

---

## 11. Match Sitemap and Wireframe to the Actual Admin and User Pages

**Priority:** Highest. Sitemap (20 points) and Wireframe and Mockup (20 points) are the two biggest criteria, and they also affect the client and admin scores.

### User sidebar from the approved sitemap

1. Dashboard (overview, quick actions, recent activity, My Donation)
2. Post Donation
3. Browse Donation
4. My Request
5. Notifications
6. Message and Support
7. Account Settings
8. Help and Support
9. Logout

### Method

1. Make a **page inventory table** with these columns: sitemap page, wireframe screen, file name, built (yes or no), differences, reason for any difference.
2. Compare each real page with its wireframe screen for structure, content placement, and components.
3. Match the **sidebar order and labels exactly** to the sitemap.
4. Confirm every sidebar link opens a real page, with no dead links and no placeholder pages.
5. Confirm the contents of each page match the wireframe (sections, buttons, forms, cards).
6. Record every change you decide to keep and be ready to explain it.

### Tasks

- [ ] Build the page inventory table for the public pages, user pages, and admin pages
- [ ] Rebuild or adjust the user sidebar to match the sitemap
- [ ] Confirm the dashboard shows overview, quick actions, recent activity, and My Donation
- [ ] Confirm Post Donation, Browse Donation, My Request, Notifications, Message and Support, Account Settings, and Help and Support all exist and match the wireframe
- [ ] Decide and document whether donors and requesters share one dashboard with role based content or have separate ones
- [ ] Match the item detail page to the wireframe screenshot
- [ ] Make sure the in site sitemap page lists all final pages and connections
- [ ] Update the wireframe and sitemap if a deliberate change is kept, so the documents and the site agree

---

## Optional Ideas If Time Remains

These come from the reference websites. They are low risk because they are mostly text or small labels, but they come **after** everything above.

- "Items we accept" and "Items we cannot accept" guide on How It Works or FAQs (inspired by Caritas Manila and DSWD)
- Impact numbers on Home (inspired by charity: water)
- A short "why I need this" field on requests (inspired by Kiva)
- A simple status timeline on requests: Submitted, Under review, Approved, Matched, Delivered (inspired by Give.Asia)

## Not Recommended Before the Final Defense

These need real backend logic or layout changes, so mention them only as planned improvements: search and filters, report buttons, thank you notes, item condition field, and the full delivery timeline.
