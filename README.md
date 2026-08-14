# Lighting Technical Data — CMS

A PHP + MySQL content system for the public "Lighting Technical Data" reference
page: parameter articles, standards, a tools carousel, and an events/booth
photo gallery (LedEXPO 1/2/3 and beyond) — all editable from a small admin
dashboard, no code changes needed to add content.

## What's inside

```
lighting-cms/
├── config.php              ← DB credentials, edit this first
├── schema.sql               ← run once to create tables + default admin
├── seed_content.sql          ← optional: migrates the original parameter
│                                articles (now with physical mechanism,
│                                formulas, and physiological/psychological
│                                impact per parameter — see below), EN
│                                12464-1 etc., standards, terminology
│                                matrix, and tools (LuxSCale/SChools/XR/
│                                DIALux/Relux)
├── migration_002_science_depth.sql  ← for an EXISTING db: adds the new
│                                        articles columns + standard_terms
│                                        table without touching your data
├── migration_002_seed.sql           ← optional follow-up: fills in the new
│                                        science content for the 5 articles
│                                        it covers, adds the Lumens article,
│                                        and seeds the terminology matrix
├── migration_003_image_urls.sql     ← adds an editable image_url column to
│                                        articles + tools (replaces the old
│                                        fixed icon set with a real "online
│                                        image" field) and backfills on-brand
│                                        placeholder images you can swap
├── migration_004_users_search.sql   ← adds public user accounts + email
│                                        subscriptions, full-text article
│                                        search, and "notify subscribers on
│                                        publish" tracking
├── migration_005_topic_subscriptions.sql ← adds per-topic follows (follow
│                                        "CRI" without subscribing to every
│                                        new article)
├── migration_006_roles_and_topics.sql ← adds account roles (client /
│                                        employee / leader / admin), a
│                                        role_permissions cpanel table,
│                                        per-user pre-approval, and the
│                                        discussion_topics table for
│                                        user-submitted community topics
│                                        with moderation
├── includes/                ← shared PHP (DB helpers, auth, icons)
├── admin/                   ← the dashboard (login required)
│   ├── login.php / logout.php
│   ├── dashboard.php        ← overview + quick links
│   ├── articles.php / article_edit.php / article_delete.php
│   ├── standards.php / standard_edit.php / standard_delete.php
│   ├── standard_terms.php    ← one-screen matrix editor (parameter ×
│   │                             EN 12464-1 / ISO 8995-1 / ANSI-IES / WELL v2)
│   ├── tools.php / tool_edit.php / tool_delete.php
│   ├── events.php / event_edit.php / event_delete.php / event_image_delete.php
│   ├── topics.php / topic_edit.php / topic_delete.php
│   │                             ← full CRUD + moderation for user-
│   │                             submitted community topics: accept,
│   │                             reject, edit, delete, or author one
│   │                             directly, regardless of what leaders
│   │                             can do on the public site
│   ├── users.php / user_edit.php / user_delete.php
│   │                             ← full CRUD for public accounts: set
│   │                             role, mark an account "pre-approved"
│   │                             (its topics always auto-publish),
│   │                             activate/deactivate, delete
│   └── roles.php                ← the permissions cpanel: per-role
│                                   toggles for "can post topics",
│                                   "can moderate topics", and "topics
│                                   auto-publish" — Full Admin / High
│                                   Board always keeps every permission
├── public/                  ← the page visitors see
│   ├── index.php             ← landing page: hero, "latest 6" articles
│   │                            preview (links to articles.php for the
│   │                            rest), standards, terminology matrix,
│   │                            tools + events carousels
│   ├── articles.php          ← dedicated, paginated "All Articles" page
│   │                            with search — the scalable listing view
│   │                            once you have more than a handful of guides
│   ├── article.php?slug=…    ← full article page: a full-bleed cover-image
│   │                            hero if the article has one, or a bold
│   │                            typographic hero if it doesn't (both fully
│   │                            supported — see Images below), the
│   │                            definition, why it matters, a tabbed
│   │                            "Science" deep-dive (Physical Mechanism /
│   │                            Physiological Impact / Psychological
│   │                            Impact, plus a formula box when one
│   │                            applies), the range table, a link out to
│   │                            the full live simulator, and prev/next
│   │                            article navigation
│   ├── search.php            ← site search: an Articles tab (real,
│   │                            full-text, paginated) and a Fixtures tab
│   │                            (front-end only for now — see Search below)
│   ├── register.php / login.php / logout.php / account.php
│   │                          ← public accounts: create one, subscribe to
│   │                            new-article emails, toggle it any time,
│   │                            see your role, post/track community topics
│   ├── unsubscribe.php?token=…
│   │                          ← one-click unsubscribe from the email link,
│   │                            no login needed
│   ├── topics.php / topic.php / topic_new.php
│   │                          ← community: browse approved topics, view
│   │                            one, or submit a new one (role must have
│   │                            "can post topics" on — see admin/roles.php)
│   ├── moderate.php            ← leader-only moderation queue, only
│   │                            reachable if the Leader role has "can
│   │                            moderate topics" switched on
│   └── assets/                ← CSS (SC brand system) + JS (theme toggle,
│                                  carousels, science tabs)
└── uploads/                  ← event booth photos land here (web-writable)
```

## Setup

1. **Create the database and tables**
   ```
   mysql -u youruser -p your_db < schema.sql
   ```
   This also creates a default admin account:
   - username: `admin`
   - password: `changeme123`

   **Change this password immediately** — there's no in-app "change password"
   screen yet, so update it directly:
   ```sql
   UPDATE admin_users SET password_hash = '<new hash>' WHERE username = 'admin';
   ```
   Generate `<new hash>` in a PHP shell with `password_hash('yourNewPassword', PASSWORD_DEFAULT)`.

2. **(Optional) Load starter content**
   ```
   mysql -u youruser -p your_db < seed_content.sql
   ```
   This migrates the 10 lighting-parameter articles (including the new
   Lumens article), the EN 12464-1 and comparison-guide articles, five
   standards, the cross-standard terminology matrix, and five tools
   (LuxSCale, SChools, XR, DIALux, Relux) from the original static page.
   Skip it if you'd rather start empty and add everything from the
   dashboard.

   **Upgrading a database that already ran the original `seed_content.sql`?**
   Don't re-run it (you'd get duplicate-slug errors). Instead run the two
   migration files in order:
   ```
   mysql -u youruser -p your_db < migration_002_science_depth.sql
   mysql -u youruser -p your_db < migration_002_seed.sql
   ```
   The first adds the new columns/table; the second fills in the science
   content for the articles it covers (CRI, CCT, Lux, UGR, Flicker), adds
   the Lumens article, and seeds the terminology matrix — without
   touching any edits you've already made in the dashboard.

   Either way, finish with:
   ```
   mysql -u youruser -p your_db < migration_003_image_urls.sql
   mysql -u youruser -p your_db < migration_004_users_search.sql
   mysql -u youruser -p your_db < migration_005_topic_subscriptions.sql
   ```
   `migration_003` adds the `image_url` column to `articles` and `tools`
   (safe to run even on a fresh DB from `schema.sql`, which already has the
   column) and fills in an on-brand placeholder image for each existing row
   so nothing looks broken — see **Images** below for how to replace them.
   `migration_004` adds the public `users` table (accounts + email
   subscriptions), full-text article search, and notify-on-publish
   tracking. `migration_005` adds per-topic follows on top of that — see
   **Accounts & Subscriptions** and **Search** below.

3. **Fill in `config.php`** with your real DB host/name/user/password, the
   site's base URL, and (for email) `MAIL_FROM_ADDRESS`/`MAIL_FROM_NAME` —
   see **Email Notifications** below if `mail()` isn't reliable on your host.

4. **Make `uploads/` web-writable** (e.g. `chmod 775 uploads uploads/events`)
   so the dashboard can save event booth photos.

5. Point your web server's document root at this folder (or a subfolder of
   it), then visit:
   - `/admin/login.php` — the dashboard
   - `/public/index.php` — the live page

## Using the dashboard

- **Articles** — one per lighting topic (a parameter, a standard explainer,
  or a comparison guide). Each has a title, excerpt, intro, a "why it
  matters" callout, an optional "The Science" deep-dive (physical
  mechanism, an optional formula + explanation, physiological impact,
  psychological impact — each shown as its own tab on the article page,
  and hidden entirely if you leave all three blank), a repeatable
  Stage/Environment/Range/Notes table, and a link to the full live
  simulator (kept separate — no simulator logic is embedded on this page,
  it just links out, e.g. to the SChools tool).
- **Standards** — a code (e.g. `EN 12464-1`), name, region, description, and
  a link to the official standard document.
- **Terminology Matrix** — a single-screen table mapping each parameter
  (CCT, CRI, Flicker, Glare, Lux, Lumen) to how EN 12464-1, ISO 8995-1 /
  CIE S 008, ANSI / IES, and WELL v2 each name it. Shown on the public page
  right after the Standards section.
- **Tools** — the bottom carousel. Mark a tool "Industry tool" (DIALux,
  Relux, ...) or leave it unchecked for your own tools (LuxSCale, SChools,
  XR). No API details are ever shown here — just a name, one-line
  description, and a link.
- **Events** — LedEXPO 1/2/3 come pre-seeded (2023/2024/2025). Open any
  event and upload booth photos (multiple at once); add as many future
  events as you like from the same screen.

## Images (articles & tools)

Article cards, the article hero, and the tools carousel used to show a
fixed set of inline SVG icons. They now show a real **Image URL** field
instead — paste a link to any hosted image (your own CDN, an uploads
folder, Google Drive share link exported as a direct image URL, etc.) and
it appears immediately, no deploy needed.

Leave it blank and the design adapts on purpose rather than looking
broken: article cards and the article hero switch to a bold typographic
layout (title + tag on a black/red gradient, no image container), and
the tools carousel falls back to the old icon. Both are intentional
"cover or no cover" layouts, not a placeholder state — publish an article
with or without an image and either one looks finished.

- Edit it from **Admin → Articles/Tools → Edit** — there's a live preview
  next to the field.
- After `migration_003_image_urls.sql`, every article and tool already has
  an on-brand `placehold.co` placeholder (SC red `#EB1B26`, labelled with
  the topic) so the grid looks complete on day one. Swap any of them out
  whenever you have real photography, product renders, or software
  screenshots — just paste the new URL and save, or clear the field to
  switch that row to the no-cover typographic layout instead.
- Recommended sizes: **800×450** (or any 16:9) for article cards/hero,
  **640×420** for tools — both are shown with `object-fit: cover`, so any
  image works but those ratios crop the cleanest.

Everything has a `sort_order` field and a `Published` checkbox, so you can
stage content before it goes live and control the order it appears in.

## Accounts & Subscriptions

Visitors can create an account (`/public/register.php`) and opt in to an
email whenever a new lighting guide is published. It's intentionally
lightweight — no email verification step, no password reset flow yet —
just enough to run subscriptions honestly:

- `/public/login.php` / `logout.php` / `account.php` — log in, and toggle
  the "email me on new guides" preference any time from the account page.
- **Topic follows**: a visitor can also follow one topic (an article's
  tag — CRI, Comfort, Standard, etc.) without subscribing to everything.
  The bell icon next to any topic chip on `/public/articles.php` or
  `/public/search.php`, or the follow banner on the article page itself,
  toggles it — no page reload, just a redirect back to where you were.
  `account.php` lists and lets you unfollow topics too. **Publishing an
  article and clicking Notify Subscribers emails both** the site-wide
  subscribers **and** anyone following that article's specific tag, with
  each person emailed once even if they qualify both ways.
- Every notification email includes a one-click unsubscribe link
  (`/public/unsubscribe.php?token=…`) that works without logging in, and
  flips `is_subscribed` off — standard email-compliance practice. (It
  only affects the site-wide toggle; topic follows are managed
  separately from `account.php`.)
- **Admin → Articles → Edit** shows a **Notify Subscribers** button for
  any published article. It emails everyone currently subscribed and
  records `notified_at`, so you can see at a glance whether an article's
  already been announced (and re-send deliberately if you need to).
- Accounts are a separate `users` table/session from the `admin_users`
  dashboard login — a site visitor account can never reach `/admin`.

## Roles & Community Topics

Every account has one of four roles: **Client**, **Employee (SC)**,
**Leader**, and **Full Admin / High Board** (the `admin_users` dashboard
login — always the top tier, always has every permission).

- **`/admin/roles.php`** is the cpanel: three checkboxes per role — can
  post a topic, can moderate (accept/reject) topics, and whether that
  role's topics skip review and auto-publish. No code changes needed.
- **`/admin/users.php` → Edit** lets you mark one specific account
  **Pre-approved**, so its topics always auto-publish regardless of what
  its role is set to — handy for a trusted client or a specific employee
  without changing the whole role.
- A user with posting on submits from **`/public/topic_new.php`**
  (linked from `account.php`). If their role/account isn't set to
  auto-publish, it lands as `pending` until someone accepts it.
- If **Leader → "can moderate"** is on, leaders see a **Moderate** link
  in the site nav and review the queue at **`/public/moderate.php`** —
  they only ever see and act on topics, nothing else in the account
  system. Turn that toggle off and moderation is admin-only, at
  **`/admin/topics.php`**, which also gives full CRUD (create/edit/
  delete any topic, from any status) for the High Board.
- `account.php` shows each user their own topic list and its current
  status (pending / approved / rejected, with the rejection reason if
  given).

Run `migration_006_roles_and_topics.sql` once against an existing
database to add this (fresh installs already get it via `schema.sql`).

## Search

`/public/search.php` and the new `/public/articles.php` (the paginated
"All Articles" page) share the same search: real full-text search over
article title/excerpt/intro/tag (MySQL `FULLTEXT` + `MATCH…AGAINST`, with
an automatic `LIKE` fallback for short queries or older MySQL versions
that can't use it), paginated 12 per page.

The search page also has a **Fixtures** tab — this is a **front-end stub
only**, built and wired up (tab, search box, result panel) but with
nothing behind it yet, exactly as requested. To go live later, point
`searchFixtures()` in `includes/functions.php` at the Short Circuit
fixtures API instead of returning the stub array; it already documents
the expected return shape, and `search.php` needs no changes.

## Email Notifications

`includes/mailer.php` wraps everything in one `sendMail()` function, used
for welcome emails, new-article notifications, and topic accept/reject
decisions. It sends via **PHPMailer/SMTP** whenever `SMTP_HOST` is filled
in in `config.php`, and automatically falls back to PHP's built-in
`mail()` if `SMTP_HOST` is left blank — every caller of `sendMail()`
stays the same either way.

To turn SMTP on:

1. Install PHPMailer once, from the project root:
   ```
   composer require phpmailer/phpmailer
   ```
   This creates `vendor/` — `config.php` auto-loads it if it exists, no
   further wiring needed.
2. Fill in `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USER`, and
   `SMTP_PASS` in `config.php`. For Gmail: `smtp.gmail.com`, port `587`,
   `tls`, and an **App Password** (not your normal Gmail password —
   generate one at myaccount.google.com/apppasswords, requires 2FA on).
3. Set `SMTP_DEBUG` to `true` temporarily if a send fails, to print the
   raw SMTP conversation in the response/error log — then set it back to
   `false`.

If `sendMail()` fails it logs to PHP's error log (`error_log()`) and
returns `false` — callers already treat a failed send as non-fatal
(e.g. `notifySubscribers()` just returns a lower count), so a bad SMTP
config won't break registration, moderation, or article publishing.

## Design

The site's background is intentionally never a flat color block — `body`
and every large dark surface (header, footer, article/card typographic
heroes, the formula box) carry a faint grid + diagonal hairline texture
in the brand red, at low enough opacity to stay out of the way of text.
It's all CSS (two reusable rules, `.tex-dark`/`.tex-light`, plus the
per-element background-image declarations) — no image assets, so it
costs nothing extra to load and adapts automatically between light/dark
mode.

## Notes

- Passwords are hashed with PHP's `password_hash()` (bcrypt) and checked
  with `password_verify()`.
- All forms are protected with a per-session CSRF token.
- Uploaded images are renamed to random filenames on save; a `.htaccess`
  inside `uploads/` blocks PHP execution there as a safety net.
- The public pages only ever query `is_published = 1` rows, so hiding
  something in the dashboard removes it from the live page immediately.
