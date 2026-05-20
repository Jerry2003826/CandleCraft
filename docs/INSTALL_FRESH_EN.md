# Fresh Install on an Empty cPanel Server

> Audience: this is a brand-new cPanel account that has **never** hosted
> the project before — the database is empty, there is no `config/.env`,
> and `webroot/uploads/site/` does not exist. Goal: stand up code +
> data + config in a single pass.

---

## One-Line Install (Recommended)

SSH into the cPanel account, upload the code into the app directory
(`git clone` or extract a zip — either works), then run:

```bash
cd ~/public_html/production            # change to the actual app path

APP_URL='https://u26s1185.iedev.org/production' \
DB_NAME=academy_management_db \
DB_USER=academy_user \
DB_PASS='your-strong-db-password' \
SECURITY_SALT="$(openssl rand -hex 32)" \
bash scripts/cpanel-fresh-install.sh
```

The script runs the steps below in order. Every step is **idempotent**:
re-running it produces the same final state.

| # | Step | Notes |
|---|---|---|
| 0 | Pre-flight | Auto-detects php / composer / mysql binaries |
| 1 | `composer install --no-dev --optimize-autoloader` | Skipped automatically if `vendor/` already exists |
| 2 | Generate `config/.env` | Built from your env vars; **never overwrites an existing .env** unless `FORCE_OVERWRITE_ENV=true` |
| 3 | Apply `docs/sql/cms-fresh-install.sql` | 30 tables + 2 views + 4 CMS pages + 20 sections + 3 demo accounts + 32 migration records |
| 4 | `bin/cake migrations migrate` | Safety net in case your checkout is ahead of the SQL snapshot |
| 5 | Create `webroot/uploads/site/` | CMS image upload destination |
| 6 | `bin/cake cache clear_all` | Flush model / routing / CMS bundle caches |
| 7 | HTTP health check | Only when `APP_URL` is set |

---

## Demo Accounts (Reset Passwords IMMEDIATELY After First Login)

| Role | Email | Default Password |
|---|---|---|
| Admin | `admin@candlecraft.com` | `admin123` |
| Teacher | `emma.clay@candlecraft.com` | `alice123` |
| Student / Customer | `alice.wong@candlecraft.com` | `alice123` |

> These are dev fixtures defaults and **must not stay in production**.
> The first thing to do after the homepage loads is log in as admin and
> rotate every demo password from the admin panel. If you do not need
> demo data at all, edit `docs/sql/cms-fresh-install.sql` before running
> it: delete every `INSERT IGNORE INTO …` statement and keep only the
> CREATE TABLE / CREATE OR REPLACE VIEW blocks.

---

## What the Database Contains After Install

- **30 tables**: from the core (`users` / `students` / `teachers` /
  `courses` / `classes` / `bookings` / `payments`) through all five CMS
  tables (`site_pages` / `site_media` / `page_sections` /
  `page_section_revisions` / `page_section_locks`), plus the payment
  ecosystem (`payment_refunds`, `payment_disputes`,
  `stripe_webhook_events`, `payment_webhook_incidents`,
  `payment_profiles`), plus framework tables (`cake_migrations`,
  `cake_seeds`).
- **2 views**: `vw_booking_details`, `vw_payment_summary` (read-only,
  used by admin reports).
- **CMS defaults**: 4 pages (`global` / `home` / `contact` / `courses`)
  + 20 sections (site name, logo placeholder, hero copy, contact
  intro, course descriptions, etc.) — admin can start editing on
  first login.
- **Sample business data**: 1 pottery course, 1 scheduled class, 1
  Alice booking, 1 demo enquiry — so the admin UI is not empty.
- **32 migration rows**: `bin/cake migrations migrate` will be a no-op
  on the next deploy.

---

## Key Environment Variables

| Variable | Default | Required? | Purpose |
|---|---|---|---|
| `APP_DIR` | `$(pwd)` | no | App root |
| `APP_URL` | empty | recommended | Health check + written into `.env` (affects absolute URL generation) |
| `APP_BASE` | empty | no | Set to `/production` for subdirectory deployments |
| `DB_NAME` | empty | **yes** | Database name |
| `DB_USER` | empty | **yes** | Database user |
| `DB_PASS` | empty | recommended | Database password |
| `DB_HOST` / `DB_PORT` | `localhost` / `3306` | no | DB host / port |
| `SECURITY_SALT` | auto-generated | recommended | Session/CSRF salt (≥ 32 chars) |
| `STRIPE_*` / `EMAIL_SMTP_*` / `RECAPTCHA_*` | empty | no | Payment / email / captcha integrations; can be left blank and edited into `.env` later |
| `FORCE_OVERWRITE_ENV=true` | false | no | Regenerate `.env` even if one already exists |
| `SKIP_COMPOSER=true` | false | no | When `vendor/` was uploaded by hand |
| `SKIP_DB_IMPORT=true` | false | no | When you prefer to import the SQL via phpMyAdmin |
| `SKIP_MIGRATIONS=true` | false | no | The SQL already includes every migration row |

---

## cPanel-Only Fallback (No SSH)

When the host only exposes the cPanel control panel + phpMyAdmin, do
the same four things by hand:

1. **Upload code**: locally
   `git archive --format=zip --output=release.zip main` and use cPanel
   File Manager to upload + extract over the app directory.
2. **Create the database**: cPanel → MySQL Databases → create
   `academy_management_db` + a user + grant ALL PRIVILEGES.
3. **Import the SQL**: phpMyAdmin → select the database → SQL tab →
   paste the entire contents of `docs/sql/cms-fresh-install.sql` → Go.
   Verify with:
   ```sql
   SELECT (SELECT COUNT(*) FROM users)        AS users,
          (SELECT COUNT(*) FROM site_pages)   AS pages,
          (SELECT COUNT(*) FROM page_sections) AS sections,
          (SELECT COUNT(*) FROM cake_migrations) AS migrations;
   ```
   Expected: `users >= 3`, `pages = 4`, `sections >= 20`,
   `migrations >= 32`.
4. **Hand-write .env**: in File Manager, create `config/.env` by
   copying `config/.env.example` and filling in DB_* / SECURITY_SALT
   etc.

> When you cannot run `composer install`, you must run
> `composer install --no-dev` locally and upload `vendor/` along with
> the code zip.

---

## Post-Install Verification

```bash
# in SSH or the cPanel Terminal
git log -1 --format='%h %s'
bin/cake migrations status | tail -10
mysql -u USER -p DB -e "SELECT COUNT(*) AS pages FROM site_pages;
                        SELECT COUNT(*) AS sections FROM page_sections;"
ls -la webroot/uploads/site/
```

Then click through these in a real browser:

| URL | Expected |
|---|---|
| `/` | 200, the CANDLECRAFT homepage renders |
| `/login` | 200, headline does not wrap to 3 lines on mobile |
| Sign in as `admin@candlecraft.com` / `admin123` | redirected to admin dashboard |
| `/admin/cms` | 4 pages listed (global / home / contact / courses) |
| `/admin/messages` | 1 demo enquiry visible |
| `/admin/cms/pages/global` → edit Site Name → save | brand mark on `/` updates immediately |

If all six pass, an empty cPanel server has been turned into a fully
working site end-to-end.

---

## Recover / Reset

```bash
# Reset the database from scratch
mysql -u USER -p -e "DROP DATABASE IF EXISTS academy_management_db;
                     CREATE DATABASE academy_management_db
                       DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
mysql -u USER -p academy_management_db < docs/sql/cms-fresh-install.sql

# Force-rewrite .env
FORCE_OVERWRITE_ENV=true bash scripts/cpanel-fresh-install.sh

# Wipe the CMS uploads dir
rm -rf webroot/uploads/site/* && touch webroot/uploads/site/.gitkeep
```

---

## Routine Updates (Once the Site Is Live)

Use the other script:

```bash
bash scripts/cpanel-update.sh
```

See `docs/UPDATE_PRODUCTION_EN.md` for the full update workflow.
