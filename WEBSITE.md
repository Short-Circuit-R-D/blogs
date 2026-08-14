# Short Circuit Lighting Blog — Website Description

**Live site:** [https://blogs.shortcircuit.company](https://blogs.shortcircuit.company)  
**Parent company:** [Short Circuit Company](https://shortcircuit.company)  
**CMS dashboard:** [https://blogs.shortcircuit.company/admin](https://blogs.shortcircuit.company/admin)

This document describes what the website is, who it is for, every public and private area, and how the main features work. It is a product description of the running lighting blog, not a setup guide. For install steps and migrations, see `README.md`.

---

## 1. What this website is

The site is Short Circuit Company’s **Lighting Technical Data & Standards blog**: a public reference and community hub for lighting quality.

It explains the parameters that define how light looks and feels (colour, brightness, glare, flicker, circadian effect), the standards behind those numbers (EN 12464-1, IES, WELL, and others), and the design tools used in the field. Content is written for engineers, consultants, educators, and lighting professionals — not as a marketing landing page for fixtures.

Visitors can:

- Read technical articles and recommended ranges
- Browse lighting standards and a cross-standard terminology matrix
- Discover Short Circuit and industry design tools
- See event / booth photography (for example LedEXPO)
- Search the library
- Subscribe to new-guide emails
- Join a moderated community
- Comment on articles
- Send a Contact Us message

Editors manage all of that from a separate **Lighting CMS** dashboard. Public visitor accounts can never open `/admin`.

---

## 2. Who uses it

| Audience | What they do |
|---|---|
| **Anonymous visitors** | Read published articles, standards, tools, events; search; subscribe; contact the company |
| **Subscribers** | Receive email when a new lighting guide is published; one-click unsubscribe |
| **Registered public users (clients)** | Log in, follow article topics, comment, submit community topics for review |
| **Short Circuit employees** | Staff login (company credentials when the staff API is connected); extra site features by role |
| **Leaders** | If the role allows it: moderate community topics from the public site |
| **CMS admins (`admin_users`)** | Full dashboard: content, users, roles, contact inbox, inviting other admins |

There are **two separate login systems**:

1. **Public accounts** — table `users` — blog login at `/login`
2. **CMS admins** — table `admin_users` — dashboard login at `/admin/login`

A public “Full Admin / High Board” *role* on a visitor account is not the same as a CMS dashboard admin.

---

## 3. Brand and look

- **Brand:** Short Circuit Company
- **Accent:** Short Circuit red `#eb1b26`
- **Tone:** technical field guide, not a shop
- **Header tagline:** Lighting Standards · Technical Reference
- **Light and dark mode** (theme toggle in the header)
- **Logo** from shortcircuit.company; Open Graph image `og-image.png` (1200×630) for social shares
- **Language:** English (`lang="en"`), hreflang `en` and `x-default`

Page titles follow:

`{Page} | Lighting Blog | Short Circuit Company`

The homepage title is:

`Lighting Technical Data & Standards Blog | Short Circuit Company`

---

## 4. Public site map

All of these are pretty URLs on the live domain (no `.php` in the address bar).

| URL | Page | Indexed? |
|---|---|---|
| `/` | Homepage | Yes |
| `/articles` | All lighting articles (search, topic filter, infinite load) | Yes |
| `/article/{slug}` | Single article + discussion | Yes (if published) |
| `/topics` | Community topic list | Yes |
| `/topic/{slug}` | One community topic | Yes only if approved |
| `/search` | Site search | Empty search yes; queries `noindex, follow` |
| `/subscribe` and `/signup` | Subscribe or create an account | Yes |
| `/register` | Alias of signup | Redirects |
| `/contact` | Contact Us form | Yes |
| `/login` | Public log in | No |
| `/sc-login` / `/staff-login` | Short Circuit staff login | No |
| `/account` | Signed-in account | No |
| `/topic_new` | Post a community topic | No |
| `/moderate` | Leader moderation queue | No |
| `/unsubscribe?token=` | One-click email unsubscribe | No |
| `/sitemap.xml` | Combined XML sitemap | Listed in robots |
| `/robots.txt` | Crawl rules | — |

Private paths blocked from crawlers include `/admin/`, `/includes/`, `/storage/`, `/login`, `/account`, and the security log printer.

---

## 5. Homepage

The homepage is a **field-guide overview**, not a blog index of dates.

1. **Hero** — “Lighting Technical Data”, short explanation of colour, brightness, comfort, and circadian health, plus a Subscribe CTA
2. **Lighting Parameters & Guides** — first six published articles as cards; link to the full articles library
3. **Lighting Standards** — published standards (code, region, name, description, official document link)
4. **Terminology Across Global Standards** — table mapping each parameter to EN 12464-1, ISO 8995-1 / CIE S 008, ANSI / IES, and WELL v2
5. **Lighting Design Tools** — carousel of Short Circuit tools and industry tools (DIALux, Relux, and similar)
6. **Events** — named events (LedEXPO editions and others) with booth photo galleries

Only rows marked **published** in the CMS appear here.

---

## 6. Articles

Articles are the core of the site. Each one is a lighting parameter, a standard explainer, or a comparison guide.

Typical article includes:

- Cover image (optional) or a typographic hero if there is no image
- Tag (for example CRI, Comfort, Standard)
- Title, excerpt, introduction
- “Why it matters”
- Optional **Science** tabs: physical mechanism, physiological impact, psychological impact
- Optional formula box
- **Recommended ranges** table: Stage / Environment / Range / Notes
- Link out to a live simulator (for example SChools) — the simulator is not embedded
- Previous / next article
- **Discussion** — logged-in comments
- Topic follow (bell) so the reader is emailed when a new article with the same tag is published

**Library page (`/articles`):**

- 3:4 cards, image taking most of the card height
- Search and topic chips
- Infinite scroll / auto-load of further pages
- Follow a topic without subscribing to every new article

Pretty permalink:

`https://blogs.shortcircuit.company/article/{slug}`

Search engines receive Article JSON-LD (headline, dates, image, publisher) and breadcrumb structured data.

---

## 7. Search

`/search` searches published articles (title, excerpt, intro, tag) with MySQL full-text search and a `LIKE` fallback for short queries.

There is also a **Fixtures** tab on the search page. It is a front-end stub only: the UI is wired, but it does not yet call a fixtures API.

Result pages with a query string are `noindex, follow` so Google does not index every search URL.

---

## 8. Community topics

Separate from articles. A **community topic** is a question or discussion posted by a signed-in user.

Flow:

1. User with “can post topics” writes a topic (`/topic_new`)
2. If their role (or personal pre-approval) auto-publishes, it goes live immediately
3. Otherwise it stays **pending** until a moderator or CMS admin accepts or rejects it
4. Approved topics appear at `/topics` and `/topic/{slug}`
5. The author is emailed on accept or reject (with the reason if rejected)

Leaders who have “can moderate topics” see **Moderate** in the public nav. CMS admins also have full topic CRUD in the dashboard.

Unapproved topics are `noindex`.

---

## 9. Accounts, subscribe, and staff login

### Subscribe vs account

`/subscribe` offers two modes:

- **Subscribe only** — name, email, optional company / profession / phone; added to the mailing list without a password
- **Create an account** — same profile plus password; can comment, follow topics, and post (if the role allows)

`/register` and `/signup` go to the create-account mode.

### Public account (`/account`)

- Profile and subscription toggle
- List of followed topics
- List of the user’s own community topics and their status
- Staff-facing extras when the role is employee, leader, or public-admin

### Staff login (`/sc-login`)

Intended for Short Circuit employees using company credentials. Public signup never creates the employee role. The company API URL is configured in `config.php` (`SC_LOGIN_API_URL`). Until that API is set, the page explains that staff login is not connected yet.

### Emails to readers

- Welcome / subscribed
- New lighting guide (site-wide subscribers **and** people following that article’s tag; each person is emailed once)
- Topic accepted / needs changes
- Unsubscribe link in marketing mail (`/unsubscribe?token=…`)

---

## 10. Contact Us

Public form at `/contact`:

| Field | Required |
|---|---|
| Name | Yes |
| Email | Yes |
| Company | No |
| Message | Yes |

What happens on send:

1. The message is stored in `contact_messages`
2. An organized email goes to every **confirmed, active CMS admin** (`admin_users`) who has an email address
3. Reply-To is the visitor, so the admin can reply from the inbox
4. CMS **Contact Messages** lists every submission (new / read, emailed or not)

The form has a honeypot field and a short cooldown between sends. Login, account, and similar private pages are not used for this.

---

## 11. Lighting CMS (dashboard)

URL: `/admin` (login at `/admin/login`).

The dashboard is `noindex, nofollow`. Listing tables use DataTables with search, paging, and export (Copy, CSV, Excel, PDF, Print).

### Menu

| Section | Purpose |
|---|---|
| Overview | Counts: articles, standards, tools, events, pending topics, users, new contact messages |
| Articles | Create / edit / publish lighting guides; notify subscribers |
| Standards | Codes and official links |
| Terminology Matrix | Parameter names across EN / ISO / IES / WELL |
| Tools | Carousel items (SC tools vs industry tools) |
| Events | Named events and booth photo uploads |
| Community Topics | Full moderation and editing |
| Contact Messages | Inbox from `/contact` |
| Users | Public accounts: role, pre-approval, active flag |
| CMS Admins | Invite and manage dashboard logins (`admin_users`) |
| Roles & Permissions | Per-role toggles for posting, moderating, auto-publish |

Content that is unpublished disappears from the public site immediately. Sort order controls display order.

### Inviting a CMS admin

1. An existing dashboard admin opens **CMS Admins → Invite Admin**
2. They enter name, email, username, and optional phone / company / title
3. The new person receives a **one-time email link** (valid 72 hours)
4. They open `/admin/confirm?token=…`, set their own password, and confirm
5. Until they confirm, they **cannot log in to the dashboard**
6. **Resend invite** is available while status is “Awaiting confirm”

Existing CMS logins that were created before invites existed are treated as already confirmed.

Contact Us mail is sent only to admins who are **active and email-confirmed**.

---

## 12. Email identity

Every outgoing message uses one Short Circuit identity:

- From name: **Short Circuit Company — Lighting Standards**
- Layout: black header, red brand, “Lighting Standards”, footer with blogs.shortcircuit.company and shortcircuit.company
- Transactional mail (invites, contact, topic decisions): Auto-Submitted / Organization / List-Id / Message-ID on the blog host
- Marketing mail (new guides, subscribe): List-Unsubscribe and List-Unsubscribe-Post as well

Mail is sent with PHPMailer over SMTP when `SMTP_HOST` is set; otherwise PHP `mail()` is used.

---

## 13. SEO and marketing

Built in:

- Canonical URLs and branded titles / descriptions
- Open Graph and Twitter cards (PNG 1200×630)
- JSON-LD: Organization, WebSite (with SearchAction), WebPage / CollectionPage, Article, DiscussionForumPosting, BreadcrumbList
- `robots.txt` and XML sitemaps:
  - `/sitemap.xml` — all public URLs
  - `/sitemap-pages.xml` — home, articles, topics, search, subscribe, contact
  - `/sitemap-articles.xml` — published articles
  - `/sitemap-topics.xml` — approved community topics
- Optional tags from `.env` (printed only when filled): Google / Bing / Facebook verification, GTM or GA4, Facebook Pixel, social profile URLs for `sameAs`

Submit this sitemap in Google Search Console and Bing Webmaster Tools:

`https://blogs.shortcircuit.company/sitemap.xml`

---

## 14. Security (high level)

- Passwords hashed with `password_hash()` / `password_verify()`
- CSRF tokens on public and admin forms
- Admin pages require a CMS session; public “staff” features use role permissions
- `.env` and `config.php` cannot be downloaded
- `/storage` (audit log files) is forbidden from the web
- Uploads folder does not execute PHP
- Directory listing is off
- CMS admin actions are written to a daily audit log and a database table
- The audit **printer** is a separate password-protected page (`sc_security_log_printer.php`), not linked in the CMS nav

---

## 15. How the application is built

| Layer | Choice |
|---|---|
| Language | PHP |
| Database | MySQL / MariaDB (`utf8mb4`) |
| Front end | Server-rendered PHP, CSS, a small amount of JS |
| Mail | PHPMailer (SMTP) |
| Admin tables | DataTables + export buttons |
| Web server | Apache (or compatible) with `mod_rewrite` |

Typical folders:

```
config.php          Site and database settings
.env                Secrets and marketing IDs (not in the browser)
public/             Visitor site
admin/              Lighting CMS
includes/           Shared helpers (auth, SEO, mail, sitemap)
uploads/            Event photos
storage/logs/       Admin audit files
db/                 schema.sql and migrations
```

The document root is the project folder. Apache rewrites `/` and pretty paths into `public/`, while `/admin` and `/uploads` stay as those folders.

Local XAMPP uses a local database; production uses the live database. Absolute links in emails and Open Graph always use `https://blogs.shortcircuit.company`.

---

## 16. Content the CMS can publish (without code changes)

Editors can add, reorder, hide, or edit:

- Lighting articles (including science tabs, range tables, cover image URL, simulator link)
- Standards
- Terminology matrix rows
- Design tools
- Events and booth galleries
- Community topics (and their moderation state)
- Public user roles and pre-approval
- CMS admin invitations

They cannot change the page chrome (header, footer, brand colour) from the dashboard; that lives in templates and CSS.

---

## 17. Visitor journeys (short)

**Read a guide**  
Home → article card or `/articles` → `/article/{slug}` → optional simulator link.

**Get email alerts**  
Subscribe → confirm they are on the list → later, “New guide” email with unsubscribe.

**Join the community**  
Create account → post a topic → wait for review (unless auto-publish) → topic goes live.

**Ask the company**  
`/contact` → stored in CMS → email to confirmed CMS admins → reply from the inbox.

**Add a colleague to the CMS**  
Dashboard → CMS Admins → Invite → they confirm the one-time email link → they log in at `/admin/login`.

---

## 18. What this site is not

- It is not the main Short Circuit Company marketing site (`shortcircuit.company`)
- It is not a product catalogue or checkout
- The Fixtures search tab is not live data yet
- Staff company login depends on `SC_LOGIN_API_URL` being configured
- Public accounts cannot access the Lighting CMS

---

*Short Circuit Company — Lighting Standards Reference · blogs.shortcircuit.company*
