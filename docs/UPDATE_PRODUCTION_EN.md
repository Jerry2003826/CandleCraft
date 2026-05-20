# Updating an Already-Deployed cPanel Server

> Audience: this project is already running on a cPanel + Apache + MariaDB
> server (e.g. `https://u26s1185.iedev.org/production/`) and you want to
> roll out the latest commit from GitLab `main` to it.

---

## One-Line Update (Recommended)

SSH into the cPanel account, `cd` into the app root, and run:

```bash
cd ~/public_html/production            # change to the actual app path
bash scripts/cpanel-update.sh
```

The script performs the following 7 steps in order. Every step is
**idempotent** — running it twice produces the same end state as running
it once, and it never touches anything destructive on its own:

| # | Step | Notes |
|---|---|---|
| 1 | `git fetch` + `git pull --ff-only origin main` | Auto-stashes any uncommitted local changes first so the pull always fast-forwards; the stash can be recovered later |
| 2 | `composer install --no-dev --optimize-autoloader` | Only runs when `composer.json` / `composer.lock` actually changed |
| 3 | `mkdir -p webroot/uploads/site/` | CMS image upload dir, `chmod 755` |
| 4 | `mysql < docs/sql/cms-bootstrap.sql` | Only runs when DB credentials are provided; the SQL itself is idempotent |
| 5 | `bin/cake migrations migrate` | Applies any new migrations |
| 6 | `bin/cake cache clear_all` | Invalidates routing / CMS bundle / model caches |
| 7 | HTTP health check | Only runs when `APP_URL` is set |

---

## First-Time Run: Pass DB Credentials Once

The old server is missing the five new CMS tables and a couple of columns
on the legacy `messages` table (`parent_message_id`, `recipient_name`,
`recipient_email`, `delivery_status`). Pass the database credentials on
the first run so the script can apply `docs/sql/cms-bootstrap.sql` for
you in one shot:

```bash
DB_NAME=academy_management_db \
DB_USER=academy_user \
DB_PASS='your-db-password' \
APP_URL='https://u26s1185.iedev.org/production' \
bash scripts/cpanel-update.sh
```

After that, every subsequent release is just:

```bash
bash scripts/cpanel-update.sh
```

(The bootstrap SQL is safe to re-apply, but skipping it the second time
saves a few seconds.)

---

## Common Environment Variables

| Variable | Default | Purpose |
|---|---|---|
| `APP_DIR` | `$(pwd)` | App root path |
| `GIT_BRANCH` | `main` | Branch to pull |
| `PHP_BIN` | auto-detected (`php`, `ea-php82`, …) | Force a specific PHP binary |
| `COMPOSER_BIN` | auto-detected | Force a specific composer binary |
| `MYSQL_BIN` | auto-detected | Force a specific mysql client |
| `DB_NAME` / `DB_USER` / `DB_PASS` | empty | Used by the bootstrap SQL step; skipped if unset |
| `APP_URL` | empty | Used by the final HTTP health check |
| `SKIP_GIT_PULL=true` | false | Use when you uploaded a tarball instead of pulling |
| `SKIP_COMPOSER=true` | false | Use when you know there are no dependency changes |
| `SKIP_BOOTSTRAP_SQL=true` | false | Use after the first successful run |
| `SKIP_MIGRATIONS=true` | false | Use when you know there are no new migrations |
| `SKIP_CACHE_CLEAR=true` | false | Don't skip this in normal use |

---

## No SSH Available? cPanel-Only Fallback

If your hosting plan only exposes the cPanel control panel (no shell
access), do the same three things by hand:

1. **Upload a zip**: locally run
   `git archive --format=zip --output=update.zip main` and use cPanel's
   File Manager to upload + extract over the app directory (back the
   directory up first).
2. **Run the SQL**: cPanel → phpMyAdmin → select
   `academy_management_db` → SQL tab → paste the entire contents of
   `docs/sql/cms-bootstrap.sql` → Go.
3. **Clear the cache**: in File Manager, multi-select everything inside
   `tmp/cache/` and delete it. This is functionally equivalent to
   `bin/cake cache clear_all`.

> Heads up: uploading a zip will not run `composer install`. If this
> release touched `composer.json`, you must use the SSH path above, or
> open a Terminal app from inside cPanel.

---

## Post-Deploy Verification Checklist

```bash
# in SSH or the cPanel Terminal
git log -1 --format='%h %s'                                # current HEAD
bin/cake migrations status | tail -10                      # all migrations "up"
mysql -u USER -p DB -e "SELECT COUNT(*) FROM site_pages;"  # = 4
mysql -u USER -p DB -e "SELECT COUNT(*) FROM page_sections;"          # = 20
mysql -u USER -p DB -e "SHOW COLUMNS FROM messages LIKE 'parent_message_id';"  # present
ls -la webroot/uploads/site/                              # directory exists
```

Then click through the key URLs in a real browser:

| Path | Expected |
|---|---|
| `/` | 200, the CANDLECRAFT homepage renders |
| `/login` | 200, "USER LOGIN PAGE" no longer wraps to three lines on mobile |
| `/admin/cms` | After admin login, lists the 4 CMS pages and the Site Content sidebar entry is present |
| `/admin/messages/view/<id>?return_url=%2Fadmin%2Fmessages` | Clicking "← Back" returns to the list (no longer 404) |
| `/consumer/payments/success/<stripe-cs-id>` | Redirects to `/consumer/bookings` (no longer 403 from ModSecurity) |

---

## Rolling Back If Something Goes Wrong

The updater stashes your local edits and never touches `.env` or your
data, so the worst-case rollback is simple:

```bash
# revert to the commit you were on before the pull
git reset --hard ORIG_HEAD     # or any specific commit sha
bin/cake cache clear_all
```

The CMS bootstrap SQL is `CREATE TABLE IF NOT EXISTS` + `INSERT IGNORE`
end-to-end, so it never overwrites existing data — you don't have to
roll the database back. Only do this if you really want to remove the
CMS tables entirely:

```sql
DROP TABLE IF EXISTS page_section_locks;
DROP TABLE IF EXISTS page_section_revisions;
DROP TABLE IF EXISTS page_sections;
DROP TABLE IF EXISTS site_media;
DROP TABLE IF EXISTS site_pages;
DELETE FROM cake_migrations WHERE version IN (20260511020000, 20260511020100);
```
