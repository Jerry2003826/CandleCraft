#!/usr/bin/env bash
# =============================================================================
# CandleCraft  ·  Fresh Install on an Empty cPanel Server
# =============================================================================
#
# Use this script when you are deploying the project to a brand-new cPanel
# account (no .env yet, no database tables yet). It does the bare minimum
# in the right order so the site is reachable in one pass:
#
#     1. Verify pre-requisites (php, composer, mysql clients).
#     2. composer install --no-dev --optimize-autoloader
#     3. Generate config/.env from your environment variables (or copy the
#        example) so Cake can connect to the DB.
#     4. Apply docs/sql/cms-fresh-install.sql to the empty database — this
#        creates all 30 tables + 2 views, seeds the 4 CMS pages with their
#        20 default sections, registers all 32 migrations as applied, and
#        creates 3 demo users so you can log in immediately.
#     5. Run `bin/cake migrations migrate` as a no-op safety net (will pick
#        up anything newer than the snapshot if you happen to be ahead).
#     6. Create webroot/uploads/site/ for CMS image uploads.
#     7. Clear the application cache.
#     8. HTTP smoke-check the homepage.
#
# Quick start (SSH into cPanel, cd into the freshly-uploaded code):
#
#     cd ~/public_html/production
#     APP_URL='https://u26s1185.iedev.org/production' \
#     DB_NAME=academy_management_db \
#     DB_USER=academy_user \
#     DB_PASS='your-strong-db-password' \
#     SECURITY_SALT="$(openssl rand -hex 32)" \
#     bash scripts/cpanel-fresh-install.sh
#
# After this finishes, log in immediately at $APP_URL/login with:
#
#     admin@candlecraft.com  /  admin123
#
# and reset every demo password from the admin panel. The Emma + Alice
# accounts use a development default and are NOT safe for production
# without rotation.
#
# Re-runnable: every step is idempotent. If the database already has
# tables, the SQL skips them; if .env already exists it is left in place
# untouched (set FORCE_OVERWRITE_ENV=true to regenerate).
# =============================================================================

set -Eeuo pipefail

APP_DIR="${APP_DIR:-$(pwd)}"
PHP_BIN="${PHP_BIN:-}"
COMPOSER_BIN="${COMPOSER_BIN:-}"
MYSQL_BIN="${MYSQL_BIN:-}"

APP_URL="${APP_URL:-}"
APP_BASE="${APP_BASE:-}"

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"

SECURITY_SALT="${SECURITY_SALT:-}"

# Optional integrations — leave blank to defer configuration to a later run.
STRIPE_ENVIRONMENT="${STRIPE_ENVIRONMENT:-}"
STRIPE_SECRET_KEY="${STRIPE_SECRET_KEY:-}"
STRIPE_PUBLISHABLE_KEY="${STRIPE_PUBLISHABLE_KEY:-}"
STRIPE_WEBHOOK_SECRET="${STRIPE_WEBHOOK_SECRET:-}"
EMAIL_SMTP_HOST="${EMAIL_SMTP_HOST:-}"
EMAIL_SMTP_PORT="${EMAIL_SMTP_PORT:-465}"
EMAIL_SMTP_USERNAME="${EMAIL_SMTP_USERNAME:-}"
EMAIL_SMTP_PASSWORD="${EMAIL_SMTP_PASSWORD:-}"
EMAIL_FROM_ADDRESS="${EMAIL_FROM_ADDRESS:-}"
EMAIL_FROM_NAME="${EMAIL_FROM_NAME:-CandleCraft Academy}"
RECAPTCHA_SITE_KEY="${RECAPTCHA_SITE_KEY:-}"
RECAPTCHA_SECRET_KEY="${RECAPTCHA_SECRET_KEY:-}"

FORCE_OVERWRITE_ENV="${FORCE_OVERWRITE_ENV:-false}"
SKIP_COMPOSER="${SKIP_COMPOSER:-false}"
SKIP_DB_IMPORT="${SKIP_DB_IMPORT:-false}"
SKIP_MIGRATIONS="${SKIP_MIGRATIONS:-false}"
SKIP_CACHE_CLEAR="${SKIP_CACHE_CLEAR:-false}"
SKIP_HEALTH_CHECK="${SKIP_HEALTH_CHECK:-false}"

# ---- pretty printing -------------------------------------------------------

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

step()  { echo -e "\n${BLUE}==>${NC} $*"; }
ok()    { echo -e "    ${GREEN}✓${NC} $*"; }
warn()  { echo -e "    ${YELLOW}!${NC} $*"; }
err()   { echo -e "    ${RED}✗${NC} $*"; }
die()   { err "$1"; exit "${2:-1}"; }

# ---- tool auto-detection ---------------------------------------------------

detect_bin() {
    local var="$1"; shift
    local current
    current="$(eval "echo \${$var-}")"
    if [ -n "$current" ] && command -v "$current" >/dev/null 2>&1; then
        return 0
    fi
    for candidate in "$@"; do
        if command -v "$candidate" >/dev/null 2>&1; then
            eval "$var=\"$(command -v "$candidate")\""
            return 0
        fi
    done
    return 1
}

# ---- 0. pre-flight ---------------------------------------------------------

step "0. Pre-flight"

cd "$APP_DIR"

if [ ! -f "composer.json" ] || [ ! -f "bin/cake" ]; then
    die "$APP_DIR does not look like the CakePHP app root (missing composer.json or bin/cake). cd into the uploaded code first or set APP_DIR."
fi
ok "App root: $APP_DIR"

if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]; then
    die "DB_NAME and DB_USER are required for a fresh install. See the script header for the full one-liner."
fi
ok "Target DB: $DB_USER@$DB_HOST:$DB_PORT/$DB_NAME"

if ! detect_bin PHP_BIN php php8.4 ea-php84 php8.3 ea-php83 php8.2 ea-php82 php8.1 ea-php81; then
    die "Could not find a php binary. Set PHP_BIN explicitly (e.g. PHP_BIN=/opt/alt/php82/usr/bin/php)."
fi
ok "PHP: $PHP_BIN ($($PHP_BIN -r 'echo PHP_VERSION;'))"

if [ "$SKIP_COMPOSER" != "true" ]; then
    if ! detect_bin COMPOSER_BIN composer composer.phar; then
        if [ -f "$HOME/composer.phar" ]; then
            COMPOSER_BIN="$PHP_BIN $HOME/composer.phar"
            ok "Composer: $COMPOSER_BIN"
        else
            die "Composer not found in PATH and no ~/composer.phar present. Install composer first or set SKIP_COMPOSER=true and upload vendor/ manually."
        fi
    else
        ok "Composer: $COMPOSER_BIN"
    fi
fi

if [ "$SKIP_DB_IMPORT" != "true" ]; then
    if ! detect_bin MYSQL_BIN mysql mariadb; then
        die "mysql/mariadb client not found. Install one or set SKIP_DB_IMPORT=true and apply docs/sql/cms-fresh-install.sql via cPanel → phpMyAdmin instead."
    fi
    ok "MySQL client: $MYSQL_BIN"
fi

# ---- 1. composer install ---------------------------------------------------

step "1. Composer install (production-only dependencies)"

if [ "$SKIP_COMPOSER" = "true" ]; then
    warn "Skipped (SKIP_COMPOSER=true)."
elif [ -d "vendor" ] && [ -f "vendor/autoload.php" ] && [ "${FORCE_COMPOSER_REFRESH:-false}" != "true" ]; then
    ok "vendor/ already present — skipping. Set FORCE_COMPOSER_REFRESH=true to reinstall."
else
    $COMPOSER_BIN install --no-dev --no-interaction --optimize-autoloader \
        || die "composer install failed" 2
    ok "Vendor packages installed."
fi

# ---- 2. .env generation ----------------------------------------------------

step "2. Generate config/.env"

ENV_FILE="config/.env"

if [ -f "$ENV_FILE" ] && [ "$FORCE_OVERWRITE_ENV" != "true" ]; then
    ok "$ENV_FILE already exists — leaving it untouched (set FORCE_OVERWRITE_ENV=true to regenerate)."
else
    if [ -z "$SECURITY_SALT" ]; then
        if command -v openssl >/dev/null 2>&1; then
            SECURITY_SALT="$(openssl rand -hex 32)"
        else
            SECURITY_SALT="$($PHP_BIN -r 'echo bin2hex(random_bytes(32));')"
        fi
        warn "SECURITY_SALT was empty — generated a fresh one. Persist this in your secrets store."
    fi

    {
        echo "# Generated by cpanel-fresh-install.sh on $(date -u '+%Y-%m-%dT%H:%M:%SZ')"
        echo "export APP_NAME=\"CandleCraft Academy\""
        echo "export APP_ENV=production"
        echo "export DEBUG=false"
        [ -n "$APP_URL" ]  && echo "export APP_DEFAULT_URL=$APP_URL"
        [ -n "$APP_BASE" ] && echo "export APP_BASE=$APP_BASE"
        echo "export SECURITY_SALT=$SECURITY_SALT"
        echo ""
        echo "# Database"
        echo "export DB_HOST=$DB_HOST"
        echo "export DB_PORT=$DB_PORT"
        echo "export DB_NAME=$DB_NAME"
        echo "export DB_USER=$DB_USER"
        echo "export DB_PASS='$DB_PASS'"
        echo ""
        echo "# Stripe"
        [ -n "$STRIPE_ENVIRONMENT" ]    && echo "export STRIPE_ENVIRONMENT=$STRIPE_ENVIRONMENT"
        [ -n "$STRIPE_SECRET_KEY" ]     && echo "export STRIPE_SECRET_KEY=$STRIPE_SECRET_KEY"
        [ -n "$STRIPE_PUBLISHABLE_KEY" ] && echo "export STRIPE_PUBLISHABLE_KEY=$STRIPE_PUBLISHABLE_KEY"
        [ -n "$STRIPE_WEBHOOK_SECRET" ] && echo "export STRIPE_WEBHOOK_SECRET=$STRIPE_WEBHOOK_SECRET"
        echo ""
        echo "# Email"
        [ -n "$EMAIL_SMTP_HOST" ]     && echo "export EMAIL_SMTP_HOST=$EMAIL_SMTP_HOST"
        [ -n "$EMAIL_SMTP_PORT" ]     && echo "export EMAIL_SMTP_PORT=$EMAIL_SMTP_PORT"
        [ -n "$EMAIL_SMTP_USERNAME" ] && echo "export EMAIL_SMTP_USERNAME=$EMAIL_SMTP_USERNAME"
        [ -n "$EMAIL_SMTP_PASSWORD" ] && echo "export EMAIL_SMTP_PASSWORD='$EMAIL_SMTP_PASSWORD'"
        [ -n "$EMAIL_FROM_ADDRESS" ]  && echo "export EMAIL_FROM_ADDRESS=$EMAIL_FROM_ADDRESS"
        echo "export EMAIL_FROM_NAME=\"$EMAIL_FROM_NAME\""
        echo ""
        echo "# reCAPTCHA"
        [ -n "$RECAPTCHA_SITE_KEY" ]   && echo "export RECAPTCHA_SITE_KEY=$RECAPTCHA_SITE_KEY"
        [ -n "$RECAPTCHA_SECRET_KEY" ] && echo "export RECAPTCHA_SECRET_KEY=$RECAPTCHA_SECRET_KEY"
    } > "$ENV_FILE"

    chmod 640 "$ENV_FILE" || true
    ok "Wrote $ENV_FILE (chmod 640)."
fi

# ---- 3. import fresh DB snapshot ------------------------------------------

step "3. Import docs/sql/cms-fresh-install.sql"

if [ "$SKIP_DB_IMPORT" = "true" ]; then
    warn "Skipped (SKIP_DB_IMPORT=true)."
elif [ ! -f "docs/sql/cms-fresh-install.sql" ]; then
    die "docs/sql/cms-fresh-install.sql is missing from this checkout. Did the upload finish?" 3
else
    if [ -n "$DB_PASS" ]; then
        export MYSQL_PWD="$DB_PASS"
    fi
    $MYSQL_BIN -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" \
        < docs/sql/cms-fresh-install.sql \
        || die "Failed to apply cms-fresh-install.sql — check that the DB exists and the user has CREATE/INSERT/ALTER privileges." 3
    unset MYSQL_PWD

    SUMMARY="$($MYSQL_BIN -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e "
        SELECT CONCAT(
            (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'), ' tables, ',
            (SELECT COUNT(*) FROM users), ' users, ',
            (SELECT COUNT(*) FROM site_pages), ' CMS pages, ',
            (SELECT COUNT(*) FROM page_sections), ' CMS sections, ',
            (SELECT COUNT(*) FROM cake_migrations), ' migrations registered'
        );" 2>/dev/null)"
    ok "Database imported: ${SUMMARY:-(verify manually)}"
fi

# ---- 4. cake migrations (no-op safety net) ---------------------------------

step "4. CakePHP migrations safety pass"

if [ "$SKIP_MIGRATIONS" = "true" ]; then
    warn "Skipped (SKIP_MIGRATIONS=true)."
else
    $PHP_BIN bin/cake migrations migrate --no-lock \
        || die "bin/cake migrations migrate failed (check PHP error log)" 4
    ok "Migrations up to date."
fi

# ---- 5. uploads dir --------------------------------------------------------

step "5. Ensure CMS uploads directory exists"

mkdir -p webroot/uploads/site
chmod 755 webroot/uploads/site || true
[ -f webroot/uploads/site/.gitkeep ] || : > webroot/uploads/site/.gitkeep
ok "webroot/uploads/site/ ready."

# ---- 6. cache clear --------------------------------------------------------

step "6. Clear application cache"

if [ "$SKIP_CACHE_CLEAR" = "true" ]; then
    warn "Skipped."
else
    $PHP_BIN bin/cake cache clear_all \
        || die "bin/cake cache clear_all failed" 5
    ok "Cache cleared."
fi

# ---- 7. health check -------------------------------------------------------

step "7. Health check"

if [ "$SKIP_HEALTH_CHECK" = "true" ] || [ -z "$APP_URL" ]; then
    warn "Skipped (set APP_URL=https://your-host/production to enable)."
elif ! command -v curl >/dev/null 2>&1; then
    warn "curl not available — skipping HTTP health check."
else
    STATUS="$(curl -s -o /dev/null -w '%{http_code}' "$APP_URL/" || echo "000")"
    if [ "$STATUS" = "200" ] || [ "$STATUS" = "302" ]; then
        ok "Homepage $APP_URL/ → HTTP $STATUS."
    else
        warn "Homepage $APP_URL/ → HTTP $STATUS (investigate logs/error.log)."
    fi
fi

# ---- summary ---------------------------------------------------------------

cat <<DONE

${GREEN}Fresh install complete.${NC}

Next actions (DO THESE NOW):

  1. Open ${APP_URL:-the site}/login and sign in as:
        admin@candlecraft.com  /  admin123
  2. Change every demo password from the admin panel:
        - admin@candlecraft.com
        - emma.clay@candlecraft.com   (default: alice123)
        - alice.wong@candlecraft.com  (default: alice123)
  3. Visit /admin/cms and verify the 4 default pages + 20 sections render.
  4. Verify /admin/messages opens cleanly.
  5. If you skipped Stripe/SMTP/reCAPTCHA env vars, edit config/.env now
     and re-run \`bin/cake cache clear_all\`.
DONE
