#!/usr/bin/env bash

set -Eeuo pipefail

# CandleCraft server-side deployment script
#
# Usage:
#   1. Upload the repository to the server, or run this inside the checked-out app directory.
#   2. Edit the variables below, or export them as environment variables before running.
#   3. Run:
#        bash scripts/server-deploy.sh
#
# Example:
#   APP_DIR=/home/deploy/candlecraft \
#   APP_URL=https://example.com \
#   DB_HOST=127.0.0.1 \
#   DB_PORT=3306 \
#   DB_NAME=academy_management_db \
#   DB_USER=academy_user \
#   DB_PASS='replace-me' \
#   SECURITY_SALT='replace-with-a-long-random-string' \
#   STRIPE_ENVIRONMENT=live \
#   STRIPE_SECRET_KEY='sk_live_xxx' \
#   STRIPE_PUBLISHABLE_KEY='pk_live_xxx' \
#   STRIPE_WEBHOOK_SECRET='whsec_xxx' \
#   RECAPTCHA_SITE_KEY='site-key' \
#   RECAPTCHA_SECRET_KEY='secret-key' \
#   bash scripts/server-deploy.sh

APP_DIR="${APP_DIR:-$(pwd)}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

APP_URL="${APP_URL:-https://example.com}"
DEBUG_DEFAULT="${DEBUG_DEFAULT:-false}"
PAYMENTS_DEMO_MODE="${PAYMENTS_DEMO_MODE:-false}"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-academy_management_db}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"

DB_TEST_HOST="${DB_TEST_HOST:-$DB_HOST}"
DB_TEST_PORT="${DB_TEST_PORT:-$DB_PORT}"
DB_TEST_NAME="${DB_TEST_NAME:-test_${DB_NAME}}"
DB_TEST_USER="${DB_TEST_USER:-$DB_USER}"
DB_TEST_PASS="${DB_TEST_PASS:-$DB_PASS}"

SECURITY_SALT="${SECURITY_SALT:-}"

STRIPE_ENVIRONMENT="${STRIPE_ENVIRONMENT:-}"
STRIPE_SECRET_KEY="${STRIPE_SECRET_KEY:-}"
STRIPE_PUBLISHABLE_KEY="${STRIPE_PUBLISHABLE_KEY:-}"
STRIPE_WEBHOOK_SECRET="${STRIPE_WEBHOOK_SECRET:-}"

RECAPTCHA_SITE_KEY="${RECAPTCHA_SITE_KEY:-}"
RECAPTCHA_SECRET_KEY="${RECAPTCHA_SECRET_KEY:-}"

UPLOAD_RESOURCES_ROOT="${UPLOAD_RESOURCES_ROOT:-${APP_DIR}/storage/resources}"
UPLOAD_RESOURCES_URL_PREFIX="${UPLOAD_RESOURCES_URL_PREFIX:-/resources}"

RUN_COMPOSER_INSTALL="${RUN_COMPOSER_INSTALL:-true}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-true}"
RUN_ADMIN_SEED="${RUN_ADMIN_SEED:-false}"
ADMIN_SEED_PASSWORD="${ADMIN_SEED_PASSWORD:-}"

CLEAR_CACHE_DIRECTORIES="${CLEAR_CACHE_DIRECTORIES:-true}"
BACKUP_EXISTING_CONFIG="${BACKUP_EXISTING_CONFIG:-true}"

APP_OWNER="${APP_OWNER:-}"
APP_GROUP="${APP_GROUP:-}"
RELEASE_LABEL="${RELEASE_LABEL:-$(date +%Y%m%d-%H%M%S)}"

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

info() {
    printf '%b\n' "${BLUE}INFO${NC}  $*"
}

success() {
    printf '%b\n' "${GREEN}OK${NC}    $*"
}

warn() {
    printf '%b\n' "${YELLOW}WARN${NC}  $*"
}

die() {
    printf '%b\n' "${RED}ERROR${NC} $*" >&2
    exit 1
}

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

is_true() {
    case "${1,,}" in
        1|true|yes|y|on) return 0 ;;
        *) return 1 ;;
    esac
}

require_value() {
    local name="$1"
    local value="$2"
    [ -n "$value" ] || die "${name} is required."
}

php_literal() {
    "$PHP_BIN" -r 'echo var_export($argv[1], true);' "$1"
}

php_nullable_literal() {
    if [ -n "$1" ]; then
        php_literal "$1"
    else
        printf 'null'
    fi
}

php_bool_literal() {
    if is_true "$1"; then
        printf 'true'
    else
        printf 'false'
    fi
}

generate_salt() {
    if command_exists openssl; then
        openssl rand -hex 32
        return
    fi

    "$PHP_BIN" -r 'echo bin2hex(random_bytes(32));'
}

ensure_directory() {
    mkdir -p "$1"
}

write_app_local() {
    local file_path="$1"

    local app_url_literal db_host_literal db_port_literal db_name_literal db_user_literal db_pass_literal
    local test_host_literal test_port_literal test_name_literal test_user_literal test_pass_literal
    local salt_literal stripe_env_literal stripe_sk_literal stripe_pk_literal stripe_wh_literal
    local recaptcha_site_literal recaptcha_secret_literal uploads_root_literal uploads_prefix_literal
    local debug_literal payments_demo_literal

    app_url_literal="$(php_literal "$APP_URL")"
    db_host_literal="$(php_literal "$DB_HOST")"
    db_port_literal="$(php_literal "$DB_PORT")"
    db_name_literal="$(php_literal "$DB_NAME")"
    db_user_literal="$(php_literal "$DB_USER")"
    db_pass_literal="$(php_literal "$DB_PASS")"
    test_host_literal="$(php_literal "$DB_TEST_HOST")"
    test_port_literal="$(php_literal "$DB_TEST_PORT")"
    test_name_literal="$(php_literal "$DB_TEST_NAME")"
    test_user_literal="$(php_literal "$DB_TEST_USER")"
    test_pass_literal="$(php_literal "$DB_TEST_PASS")"
    salt_literal="$(php_literal "$SECURITY_SALT")"
    stripe_env_literal="$(php_nullable_literal "$STRIPE_ENVIRONMENT")"
    stripe_sk_literal="$(php_nullable_literal "$STRIPE_SECRET_KEY")"
    stripe_pk_literal="$(php_nullable_literal "$STRIPE_PUBLISHABLE_KEY")"
    stripe_wh_literal="$(php_nullable_literal "$STRIPE_WEBHOOK_SECRET")"
    recaptcha_site_literal="$(php_nullable_literal "$RECAPTCHA_SITE_KEY")"
    recaptcha_secret_literal="$(php_nullable_literal "$RECAPTCHA_SECRET_KEY")"
    uploads_root_literal="$(php_literal "$UPLOAD_RESOURCES_ROOT")"
    uploads_prefix_literal="$(php_literal "$UPLOAD_RESOURCES_URL_PREFIX")"
    debug_literal="$(php_bool_literal "$DEBUG_DEFAULT")"
    payments_demo_literal="$(php_bool_literal "$PAYMENTS_DEMO_MODE")"

    cat > "$file_path" <<PHP
<?php

use function Cake\Core\env;

return [
    'debug' => filter_var(env('DEBUG', ${debug_literal}), FILTER_VALIDATE_BOOLEAN),

    'App' => [
        'fullBaseUrl' => env('APP_FULL_BASE_URL', ${app_url_literal}),
    ],

    'Security' => [
        'salt' => env('SECURITY_SALT', ${salt_literal}),
    ],

    'Datasources' => [
        'default' => [
            'host' => env('DATABASE_HOST', ${db_host_literal}),
            'port' => env('DATABASE_PORT', ${db_port_literal}),
            'username' => env('DATABASE_USERNAME', ${db_user_literal}),
            'password' => env('DATABASE_PASSWORD', ${db_pass_literal}),
            'database' => env('DATABASE_NAME', ${db_name_literal}),
            'url' => env('DATABASE_URL', null),
        ],
        'test' => [
            'host' => env('DATABASE_TEST_HOST', ${test_host_literal}),
            'port' => env('DATABASE_TEST_PORT', ${test_port_literal}),
            'username' => env('DATABASE_TEST_USERNAME', ${test_user_literal}),
            'password' => env('DATABASE_TEST_PASSWORD', ${test_pass_literal}),
            'database' => env('DATABASE_TEST_NAME', ${test_name_literal}),
            'url' => env('DATABASE_TEST_URL', null),
        ],
    ],

    'EmailTransport' => [
        'default' => [
            'host' => 'localhost',
            'port' => 25,
            'username' => null,
            'password' => null,
            'client' => null,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],

    'Stripe' => [
        'environment' => env('STRIPE_ENVIRONMENT', ${stripe_env_literal}),
        'secret_key' => env('STRIPE_SECRET_KEY', ${stripe_sk_literal}),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ${stripe_pk_literal}),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ${stripe_wh_literal}),
    ],

    'Payments' => [
        'demo_mode' => filter_var(env('PAYMENTS_DEMO_MODE', ${payments_demo_literal}), FILTER_VALIDATE_BOOLEAN),
    ],

    'Recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', ${recaptcha_site_literal}),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', ${recaptcha_secret_literal}),
    ],

    'Uploads' => [
        'resources_root' => env('UPLOAD_RESOURCES_ROOT', ${uploads_root_literal}),
        'resources_url_prefix' => env('UPLOAD_RESOURCES_URL_PREFIX', ${uploads_prefix_literal}),
    ],
];
PHP
}

set_permissions() {
    local target

    for target in "$APP_DIR/tmp" "$APP_DIR/logs" "$APP_DIR/storage"; do
        [ -e "$target" ] || continue
        find "$target" -type d -exec chmod 775 {} +
        find "$target" -type f -exec chmod 664 {} +
    done

    [ -f "$APP_DIR/config/app_local.php" ] && chmod 640 "$APP_DIR/config/app_local.php"

    if [ -n "$APP_OWNER" ] && [ -n "$APP_GROUP" ]; then
        chown -R "${APP_OWNER}:${APP_GROUP}" "$APP_DIR/tmp" "$APP_DIR/logs" "$APP_DIR/storage" "$APP_DIR/config/app_local.php" 2>/dev/null || \
            warn "Could not change ownership to ${APP_OWNER}:${APP_GROUP}. Check server permissions."
    fi
}

run_cake() {
    "$PHP_BIN" "$APP_DIR/bin/cake.php" "$@"
}

main() {
    [ -d "$APP_DIR" ] || die "APP_DIR does not exist: $APP_DIR"
    cd "$APP_DIR"

    [ -f composer.json ] || die "composer.json not found in APP_DIR: $APP_DIR"
    [ -f "$APP_DIR/bin/cake.php" ] || die "CakePHP CLI entrypoint not found: $APP_DIR/bin/cake.php"
    command_exists "$PHP_BIN" || die "PHP binary not found: $PHP_BIN"

    require_value "APP_URL" "$APP_URL"
    require_value "DB_USER" "$DB_USER"
    require_value "DB_PASS" "$DB_PASS"

    if [ "$APP_URL" = "https://example.com" ]; then
        die "APP_URL is still the example placeholder. Set the real production URL first."
    fi

    if [ -z "$SECURITY_SALT" ]; then
        SECURITY_SALT="$(generate_salt)"
        success "Generated SECURITY_SALT automatically."
    fi

    info "Deploying CandleCraft from $APP_DIR"
    info "Using APP_URL=$APP_URL"
    info "Using database ${DB_USER}@${DB_HOST}:${DB_PORT}/${DB_NAME}"

    ensure_directory "$APP_DIR/config"
    ensure_directory "$APP_DIR/logs"
    ensure_directory "$APP_DIR/tmp/cache/models"
    ensure_directory "$APP_DIR/tmp/cache/persistent"
    ensure_directory "$APP_DIR/tmp/cache/translated"
    ensure_directory "$APP_DIR/tmp/sessions"
    ensure_directory "$APP_DIR/tmp/tests"
    ensure_directory "$APP_DIR/storage/resources"
    ensure_directory "$UPLOAD_RESOURCES_ROOT"

    if is_true "$BACKUP_EXISTING_CONFIG" && [ -f "$APP_DIR/config/app_local.php" ]; then
        cp "$APP_DIR/config/app_local.php" "$APP_DIR/config/app_local.php.bak.${RELEASE_LABEL}"
        success "Backed up existing config/app_local.php"
    fi

    write_app_local "$APP_DIR/config/app_local.php"
    "$PHP_BIN" -l "$APP_DIR/config/app_local.php" >/dev/null
    success "Generated config/app_local.php"

    if is_true "$RUN_COMPOSER_INSTALL"; then
        command_exists "$COMPOSER_BIN" || die "Composer binary not found: $COMPOSER_BIN"
        info "Installing production dependencies"
        "$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
        success "Composer install completed"
    else
        warn "Skipping composer install because RUN_COMPOSER_INSTALL=false"
    fi

    if is_true "$CLEAR_CACHE_DIRECTORIES"; then
        rm -rf "$APP_DIR/tmp/cache/models/"* \
               "$APP_DIR/tmp/cache/persistent/"* \
               "$APP_DIR/tmp/cache/translated/"* \
               "$APP_DIR/tmp/sessions/"*
        success "Cleared tmp cache directories"
    fi

    set_permissions
    success "Updated writable directory permissions"

    if is_true "$RUN_MIGRATIONS"; then
        info "Running database migrations"
        run_cake migrations migrate
        success "Database migrations completed"
    else
        warn "Skipping migrations because RUN_MIGRATIONS=false"
    fi

    if is_true "$RUN_ADMIN_SEED"; then
        require_value "ADMIN_SEED_PASSWORD" "$ADMIN_SEED_PASSWORD"
        info "Running AdminSeed"
        ADMIN_SEED_PASSWORD="$ADMIN_SEED_PASSWORD" run_cake seeds run AdminSeed -q
        success "Admin seed completed"
    fi

    success "Deployment finished successfully."
    info "Health check suggestion: curl -I ${APP_URL}"
    info "Stripe webhook endpoint: ${APP_URL%/}/stripe/webhook"
}

main "$@"
