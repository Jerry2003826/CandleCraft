#!/usr/bin/env bash
# =============================================================================
# CandleCraft  ·  Pull latest code into an already-deployed cPanel server
# =============================================================================
#
# Use this script every time you want to push a new release to production.
# It is intentionally focused on UPDATING an existing install — it never
# touches your .env, never resets the database, and only runs schema /
# composer changes when the relevant files in the new commit have changed.
#
# Quick start (SSH into the cPanel account, cd into the app directory):
#
#     cd ~/public_html/production           # or wherever the app lives
#     bash scripts/cpanel-update.sh
#
# Optional environment overrides (export these before running, or pass them
# inline on the same line):
#
#     APP_DIR              path to the app root          (default: $(pwd))
#     GIT_BRANCH           branch to pull                 (default: main)
#     PHP_BIN              php binary                     (auto-detected)
#     COMPOSER_BIN         composer binary                (auto-detected)
#     MYSQL_BIN            mysql client                   (auto-detected)
#
#     DB_HOST DB_NAME DB_USER DB_PASS
#                          mysql credentials. Required only if you also want
#                          this script to apply docs/sql/cms-bootstrap.sql.
#                          If left empty the script just skips that step
#                          (re-running it later is always safe).
#
#     SKIP_GIT_PULL=true   skip `git pull` (e.g. if you uploaded a tarball)
#     SKIP_COMPOSER=true   skip `composer install` even if composer.lock changed
#     SKIP_BOOTSTRAP_SQL=true
#                          skip cms-bootstrap.sql even when DB creds are set
#     SKIP_MIGRATIONS=true skip `bin/cake migrations migrate`
#     SKIP_CACHE_CLEAR=true skip `bin/cake cache clear_all`
#     SKIP_HEALTH_CHECK=true skip the final HTTP smoke check
#     APP_URL=https://...  url to hit for the final health check
#
# Exit codes:
#   0   success
#   1   pre-flight failure (wrong directory, missing tools, etc.)
#   2   git pull failed
#   3   composer install failed
#   4   bootstrap SQL apply failed
#   5   cake migrations migrate failed
#   6   cake cache clear_all failed
#
# Re-runnable: every step in this script is idempotent. Running it twice in
# a row produces the same end state as running it once.
# =============================================================================

set -Eeuo pipefail

APP_DIR="${APP_DIR:-$(pwd)}"
GIT_BRANCH="${GIT_BRANCH:-main}"
PHP_BIN="${PHP_BIN:-}"
COMPOSER_BIN="${COMPOSER_BIN:-}"
MYSQL_BIN="${MYSQL_BIN:-}"

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"

SKIP_GIT_PULL="${SKIP_GIT_PULL:-false}"
SKIP_COMPOSER="${SKIP_COMPOSER:-false}"
SKIP_BOOTSTRAP_SQL="${SKIP_BOOTSTRAP_SQL:-false}"
SKIP_MIGRATIONS="${SKIP_MIGRATIONS:-false}"
SKIP_CACHE_CLEAR="${SKIP_CACHE_CLEAR:-false}"
SKIP_HEALTH_CHECK="${SKIP_HEALTH_CHECK:-false}"

APP_URL="${APP_URL:-}"

# ---- pretty printing -------------------------------------------------------

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

step()    { echo -e "\n${BLUE}==>${NC} $*"; }
ok()      { echo -e "    ${GREEN}✓${NC} $*"; }
warn()    { echo -e "    ${YELLOW}!${NC} $*"; }
err()     { echo -e "    ${RED}✗${NC} $*"; }
die()     { err "$1"; exit "${2:-1}"; }

# ---- tool auto-detection ---------------------------------------------------

# cPanel typically exposes PHP 8.x as /usr/local/bin/ea-php82 or as a
# `php` shim. Composer is often dropped into ~/composer or available via
# `composer`. Probe a handful of well-known paths.

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

# ---- pre-flight ------------------------------------------------------------

step "Pre-flight"

cd "$APP_DIR"
if [ ! -f "composer.json" ] || [ ! -f "bin/cake" ]; then
    die "$APP_DIR does not look like the CakePHP app root (missing composer.json or bin/cake). Set APP_DIR or cd into the app first."
fi
ok "App root: $APP_DIR"

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
            warn "composer not found in PATH and no ~/composer.phar present — will skip composer install (set SKIP_COMPOSER=true to silence this warning)."
            SKIP_COMPOSER=true
        fi
    else
        ok "Composer: $COMPOSER_BIN"
    fi
fi

if [ -n "$DB_NAME" ] && [ "$SKIP_BOOTSTRAP_SQL" != "true" ]; then
    if ! detect_bin MYSQL_BIN mysql mariadb; then
        warn "mysql/mariadb client not found in PATH — bootstrap SQL will be skipped. Run it manually via cPanel → phpMyAdmin instead."
        SKIP_BOOTSTRAP_SQL=true
    else
        ok "MySQL client: $MYSQL_BIN"
    fi
fi

# ---- 1. git pull -----------------------------------------------------------

step "1. Git fetch + pull (branch: $GIT_BRANCH)"

if [ "$SKIP_GIT_PULL" = "true" ]; then
    warn "SKIP_GIT_PULL=true — leaving working tree as-is."
else
    if [ ! -d ".git" ]; then
        die "$APP_DIR is not a git checkout (no .git directory). Either git clone the repo first, or set SKIP_GIT_PULL=true and upload files manually."
    fi

    if [ -n "$(git status --porcelain)" ]; then
        warn "Working tree has local modifications:"
        git status --short | sed 's/^/      /'
        warn "Stashing them so the pull can fast-forward; the stash is recoverable with 'git stash pop'."
        git stash push -m "cpanel-update auto-stash $(date '+%F %T')" || die "git stash failed" 2
    fi

    GIT_BEFORE="$(git rev-parse HEAD)"
    git fetch origin "$GIT_BRANCH" || die "git fetch failed" 2
    git checkout "$GIT_BRANCH" 2>/dev/null || true
    git pull --ff-only origin "$GIT_BRANCH" || die "git pull --ff-only failed (non-fast-forward divergence)" 2
    GIT_AFTER="$(git rev-parse HEAD)"

    if [ "$GIT_BEFORE" = "$GIT_AFTER" ]; then
        ok "Already up to date at $GIT_AFTER."
    else
        ok "Updated: $GIT_BEFORE → $GIT_AFTER"
        echo "    Commits applied:"
        git log --oneline "$GIT_BEFORE..$GIT_AFTER" | sed 's/^/      /'

        # Detect what kind of changes landed so later steps can self-skip.
        CHANGED_FILES="$(git diff --name-only "$GIT_BEFORE" "$GIT_AFTER")"
        export CHANGED_FILES
    fi
fi

# ---- 2. composer install (only if vendor changes are likely) ---------------

step "2. Composer install"

if [ "$SKIP_COMPOSER" = "true" ]; then
    warn "Skipped (SKIP_COMPOSER=true or composer not available)."
elif [ -n "${CHANGED_FILES-}" ] && ! echo "$CHANGED_FILES" | grep -qE '^(composer\.(json|lock)|src/Application\.php)$'; then
    ok "composer.json/lock unchanged — skipping vendor refresh."
else
    info_msg="${CHANGED_FILES-}"
    if [ -z "$info_msg" ]; then
        ok "No git diff context — running composer install just in case."
    else
        ok "composer.json/lock changed — refreshing vendor."
    fi
    $COMPOSER_BIN install --no-dev --no-interaction --optimize-autoloader \
        || die "composer install failed" 3
fi

# ---- 3. uploads dir --------------------------------------------------------

step "3. Ensure CMS uploads directory exists"

mkdir -p webroot/uploads/site
chmod 755 webroot/uploads/site || true
if [ ! -f webroot/uploads/site/.gitkeep ]; then
    : > webroot/uploads/site/.gitkeep
fi
ok "webroot/uploads/site/ ready."

# ---- 4. CMS bootstrap SQL (one-shot, but idempotent so safe to retry) ------

step "4. CMS bootstrap SQL"

if [ "$SKIP_BOOTSTRAP_SQL" = "true" ]; then
    warn "Skipped."
elif [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]; then
    warn "DB_NAME / DB_USER not set — skipping. To apply the CMS schema either"
    warn "  re-run with DB_NAME=… DB_USER=… DB_PASS=… or import"
    warn "  docs/sql/cms-bootstrap.sql via cPanel → phpMyAdmin."
elif [ ! -f "docs/sql/cms-bootstrap.sql" ]; then
    warn "docs/sql/cms-bootstrap.sql missing in this checkout — skipping."
else
    MYSQL_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME")
    if [ -n "$DB_PASS" ]; then
        # MYSQL_PWD avoids leaking the password into ps -ef output.
        export MYSQL_PWD="$DB_PASS"
    fi
    $MYSQL_BIN "${MYSQL_ARGS[@]}" < docs/sql/cms-bootstrap.sql \
        || die "Failed to apply docs/sql/cms-bootstrap.sql" 4
    unset MYSQL_PWD
    ok "Applied docs/sql/cms-bootstrap.sql (idempotent)."
fi

# ---- 5. cake migrations migrate --------------------------------------------

step "5. CakePHP migrations"

if [ "$SKIP_MIGRATIONS" = "true" ]; then
    warn "Skipped."
elif [ ! -d "config/Migrations" ]; then
    warn "No config/Migrations/ directory — skipping."
else
    $PHP_BIN bin/cake migrations migrate --no-lock \
        || die "bin/cake migrations migrate failed" 5
    ok "Migrations up to date."
fi

# ---- 6. cake cache clear ---------------------------------------------------

step "6. Clear application cache"

if [ "$SKIP_CACHE_CLEAR" = "true" ]; then
    warn "Skipped."
else
    $PHP_BIN bin/cake cache clear_all \
        || die "bin/cake cache clear_all failed" 6
    ok "Cache cleared."
fi

# ---- 7. health check -------------------------------------------------------

step "7. Health check"

if [ "$SKIP_HEALTH_CHECK" = "true" ] || [ -z "$APP_URL" ]; then
    warn "Skipped (set APP_URL=https://your-host/production to enable)."
else
    if ! command -v curl >/dev/null 2>&1; then
        warn "curl not available — skipping HTTP health check."
    else
        STATUS="$(curl -s -o /dev/null -w '%{http_code}' "$APP_URL/" || echo "000")"
        if [ "$STATUS" = "200" ] || [ "$STATUS" = "302" ]; then
            ok "Homepage $APP_URL/ → HTTP $STATUS."
        else
            warn "Homepage $APP_URL/ → HTTP $STATUS (investigate logs/error.log)."
        fi
    fi
fi

# ---- summary ---------------------------------------------------------------

echo
echo -e "${GREEN}Update complete.${NC} App is now at $(git rev-parse --short HEAD)."
echo "If you stashed local changes earlier, recover them with:  git stash pop"
