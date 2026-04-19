#!/bin/bash
# ============================================================================
# CandleCraft - One-Click cPanel Deployment
# ============================================================================

set -euo pipefail

REMOTE_HOST=""
REMOTE_USER=""
REMOTE_PORT="22"
SSH_KEY=""

DB_HOST="localhost"
DB_USER=""
DB_PASS=""

LOCAL_DB_HOST="localhost"
LOCAL_DB_USER="root"
LOCAL_DB_PASS="root"
LOCAL_DB_NAME="academy_management_db"

STRIPE_SECRET_KEY=""
STRIPE_PUBLISHABLE_KEY=""
STRIPE_WEBHOOK_SECRET=""

OUTPUT_DIR="./deploy-output"
PACKAGE_ONLY=false
SKIP_COMPOSER_INSTALL=false
CLONE_LOCAL_DATA=false
CLONE_TARGETS_RAW="dev"
SCHEMA_ONLY=false
DATA_ONLY=false
EMBED_SECRETS=false
KEEP_ARTIFACTS=false

SSH_ARGS=()
SCP_ARGS=()
TEMP_PATHS=()
SECRET_STAGING_DIR=""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

banner() {
    echo ""
    echo -e "${CYAN}╔══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}  ${BOLD}CandleCraft - One-Click cPanel Deployment${NC}              ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC}  dev / production / review environments                  ${CYAN}║${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

step() {
    echo ""
    echo -e "${GREEN}${BOLD}▶ $1${NC}"
    echo -e "${GREEN}  $(printf '─%.0s' {1..58})${NC}"
}

info() {
    echo -e "  ${BLUE}ℹ${NC} $1"
}

success() {
    echo -e "  ${GREEN}✔${NC} $1"
}

warn() {
    echo -e "  ${YELLOW}⚠${NC} $1"
}

error() {
    echo -e "  ${RED}✘${NC} $1"
}

die() {
    error "$1"
    exit 1
}

register_temp_path() {
    TEMP_PATHS+=("$1")
}

cleanup() {
    local preserve_output=false
    if [ "$KEEP_ARTIFACTS" = true ]; then
        preserve_output=true
    fi

    for path in "${TEMP_PATHS[@]:-}"; do
        [ -z "$path" ] && continue
        if [ "$preserve_output" = true ] && [ "$path" = "$OUTPUT_DIR" ]; then
            continue
        fi

        rm -rf "$path" 2>/dev/null || true
    done
}

trap cleanup EXIT

generate_salt() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -hex 32
        return
    fi

    php -r 'echo bin2hex(random_bytes(32));'
}

php_literal() {
    php -r 'echo var_export($argv[1], true);' "$1"
}

mysql_option_escape() {
    php -r 'echo addcslashes($argv[1], "\\\"\n\r");' "$1"
}

create_mysql_defaults_file() {
    local file_path="$1"
    local host="$2"
    local user="$3"
    local pass="$4"

    (
        umask 077
        cat > "$file_path" <<EOF
[client]
host="$(mysql_option_escape "$host")"
user="$(mysql_option_escape "$user")"
password="$(mysql_option_escape "$pass")"
EOF
    )

    chmod 600 "$file_path"
}

validate_generated_app_local() {
    local file_path="$1"
    local env_name="$2"

    php -l "$file_path" >/dev/null || die "Generated ${env_name} app_local.php failed php -l validation"
    grep -q "__SALT__" "$file_path" && die "Generated ${env_name} app_local.php still contains __SALT__"
    grep -q "CHANGE_ME_" "$file_path" && die "Generated ${env_name} app_local.php still contains placeholder credentials"

    if [ "$env_name" = "production" ]; then
        grep -q "'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN)," "$file_path" || \
            die "Generated production app_local.php must default debug to false"
    fi
}

write_app_local() {
    local file_path="$1"
    local env_name="$2"
    local debug_default="$3"
    local db_user="$4"
    local db_pass="$5"
    local db_name="$6"
    local salt="$7"
    local embed_secrets="$8"
    local uploads_root="$9"
    local uploads_url_prefix="${10}"

    local host_literal user_literal pass_literal database_literal salt_literal
    local stripe_sk_literal stripe_pk_literal stripe_wh_literal
    local uploads_root_literal uploads_url_prefix_literal

    host_literal="$(php_literal "$DB_HOST")"
    user_literal="$(php_literal "$db_user")"
    pass_literal="$(php_literal "$db_pass")"
    database_literal="$(php_literal "$db_name")"
    salt_literal="$(php_literal "$salt")"
    stripe_sk_literal="$(php_literal "$STRIPE_SECRET_KEY")"
    stripe_pk_literal="$(php_literal "$STRIPE_PUBLISHABLE_KEY")"
    stripe_wh_literal="$(php_literal "$STRIPE_WEBHOOK_SECRET")"
    uploads_root_literal="$(php_literal "$uploads_root")"
    uploads_url_prefix_literal="$(php_literal "$uploads_url_prefix")"

    if [ "$embed_secrets" = true ]; then
        cat > "$file_path" <<PHPEOF
<?php
use function Cake\Core\env;

return [
    'debug' => filter_var(env('DEBUG', ${debug_default}), FILTER_VALIDATE_BOOLEAN),
    'Security' => [
        'salt' => env('SECURITY_SALT', ${salt_literal}),
    ],
    'Datasources' => [
        'default' => [
            'host' => env('DATABASE_HOST', ${host_literal}),
            'username' => env('DATABASE_USERNAME', ${user_literal}),
            'password' => env('DATABASE_PASSWORD', ${pass_literal}),
            'database' => env('DATABASE_NAME', ${database_literal}),
            'url' => env('DATABASE_URL', null),
        ],
        'test' => [
            'host' => env('DATABASE_TEST_HOST', ${host_literal}),
            'username' => env('DATABASE_TEST_USERNAME', ${user_literal}),
            'password' => env('DATABASE_TEST_PASSWORD', ${pass_literal}),
            'database' => env('DATABASE_TEST_NAME', ${database_literal}),
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
        'secret_key' => env('STRIPE_SECRET_KEY', ${stripe_sk_literal}),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ${stripe_pk_literal}),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ${stripe_wh_literal}),
    ],
    'Recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', null),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', null),
    ],
    'Uploads' => [
        'resources_root' => env('UPLOAD_RESOURCES_ROOT', ${uploads_root_literal}),
        'resources_url_prefix' => env('UPLOAD_RESOURCES_URL_PREFIX', ${uploads_url_prefix_literal}),
    ],
];
PHPEOF
    else
        cat > "$file_path" <<PHPEOF
<?php
use function Cake\Core\env;

return [
    'debug' => filter_var(env('DEBUG', ${debug_default}), FILTER_VALIDATE_BOOLEAN),
    'Security' => [
        'salt' => env('SECURITY_SALT', '__SET_A_UNIQUE_SECURITY_SALT__'),
    ],
    'Datasources' => [
        'default' => [
            'host' => env('DATABASE_HOST', ${host_literal}),
            'username' => env('DATABASE_USERNAME', ${user_literal}),
            'password' => env('DATABASE_PASSWORD', null),
            'database' => env('DATABASE_NAME', ${database_literal}),
            'url' => env('DATABASE_URL', null),
        ],
        'test' => [
            'host' => env('DATABASE_TEST_HOST', ${host_literal}),
            'username' => env('DATABASE_TEST_USERNAME', ${user_literal}),
            'password' => env('DATABASE_TEST_PASSWORD', null),
            'database' => env('DATABASE_TEST_NAME', ${database_literal}),
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
        'secret_key' => env('STRIPE_SECRET_KEY', null),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', null),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', null),
    ],
    'Recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', null),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', null),
    ],
    'Uploads' => [
        'resources_root' => env('UPLOAD_RESOURCES_ROOT', ${uploads_root_literal}),
        'resources_url_prefix' => env('UPLOAD_RESOURCES_URL_PREFIX', ${uploads_url_prefix_literal}),
    ],
];
PHPEOF
    fi
}

confirm_overwrite_targets() {
    local needs_confirmation=false
    local target

    for target in "$@"; do
        if [ "$target" = "production" ] || [ "$target" = "review" ]; then
            needs_confirmation=true
            break
        fi
    done

    if [ "$needs_confirmation" != true ]; then
        return
    fi

    if [ ! -t 0 ]; then
        die "Cloning local data into review/production requires an interactive OVERWRITE confirmation."
    fi

    warn "You are about to overwrite remote review/production data."
    read -rp "  Type OVERWRITE to continue: " confirmation
    [ "$confirmation" = "OVERWRITE" ] || die "Remote data clone aborted."
}

parse_clone_targets() {
    local IFS=','
    read -r -a CLONE_TARGET_LIST <<< "$CLONE_TARGETS_RAW"

    [ "${#CLONE_TARGET_LIST[@]}" -gt 0 ] || die "--clone-targets must list at least one target."

    local target
    for target in "${CLONE_TARGET_LIST[@]}"; do
        case "$target" in
            dev|review|production) ;;
            *) die "Unsupported clone target '${target}'. Use dev, review, or production." ;;
        esac
    done
}

ssh_run() {
    ssh "${SSH_ARGS[@]}" "${REMOTE_USER}@${REMOTE_HOST}" "$@"
}

scp_upload() {
    local source_path="$1"
    local destination_path="$2"
    scp "${SCP_ARGS[@]}" "$source_path" "${REMOTE_USER}@${REMOTE_HOST}:${destination_path}"
}

export_local_database_dump() {
    local output_file="$1"
    local defaults_file
    defaults_file="$(mktemp "${TMPDIR:-/tmp}/candlecraft-local-mysql.XXXXXX")"
    register_temp_path "$defaults_file"
    create_mysql_defaults_file "$defaults_file" "$LOCAL_DB_HOST" "$LOCAL_DB_USER" "$LOCAL_DB_PASS"

    local dump_args=(--defaults-extra-file="$defaults_file" --no-tablespaces --single-transaction --quick)
    if [ "$SCHEMA_ONLY" = true ]; then
        dump_args+=(--no-data)
    elif [ "$DATA_ONLY" = true ]; then
        dump_args+=(--no-create-info)
    fi

    mysqldump "${dump_args[@]}" "${LOCAL_DB_NAME}" > "$output_file" || die "Could not export local database."
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --host) REMOTE_HOST="$2"; shift 2 ;;
        --user) REMOTE_USER="$2"; shift 2 ;;
        --port) REMOTE_PORT="$2"; shift 2 ;;
        --ssh-key) SSH_KEY="$2"; shift 2 ;;
        --db-user) DB_USER="$2"; shift 2 ;;
        --db-pass) DB_PASS="$2"; shift 2 ;;
        --output-dir) OUTPUT_DIR="$2"; shift 2 ;;
        --package-only) PACKAGE_ONLY=true; shift ;;
        --skip-composer-install) SKIP_COMPOSER_INSTALL=true; shift ;;
        --local-db) LOCAL_DB_NAME="$2"; shift 2 ;;
        --local-db-user) LOCAL_DB_USER="$2"; shift 2 ;;
        --local-db-pass) LOCAL_DB_PASS="$2"; shift 2 ;;
        --stripe-sk) STRIPE_SECRET_KEY="$2"; shift 2 ;;
        --stripe-pk) STRIPE_PUBLISHABLE_KEY="$2"; shift 2 ;;
        --stripe-wh) STRIPE_WEBHOOK_SECRET="$2"; shift 2 ;;
        --clone-local-data) CLONE_LOCAL_DATA=true; shift ;;
        --clone-targets=*) CLONE_TARGETS_RAW="${1#*=}"; shift ;;
        --schema-only) SCHEMA_ONLY=true; shift ;;
        --data-only) DATA_ONLY=true; shift ;;
        --embed-secrets) EMBED_SECRETS=true; shift ;;
        --keep-artifacts) KEEP_ARTIFACTS=true; shift ;;
        --help|-h)
            cat <<'EOF'
Usage: ./scripts/deploy-oneclick.sh [OPTIONS]

Options:
  --host HOST                 cPanel SSH hostname
  --user USER                 cPanel SSH username
  --port PORT                 SSH port (default: 22)
  --ssh-key PATH              SSH private key path
  --db-user USER              Remote MySQL username
  --db-pass PASS              Remote MySQL password
  --output-dir PATH           Output directory (default: ./deploy-output)
  --package-only              Build local deployment packages only
  --skip-composer-install     Reuse current vendor/ without composer install
  --local-db NAME             Local database name (default: academy_management_db)
  --local-db-user USER        Local MySQL username (default: root)
  --local-db-pass PASS        Local MySQL password (default: root)
  --stripe-sk KEY             Stripe secret key
  --stripe-pk KEY             Stripe publishable key
  --stripe-wh SECRET          Stripe webhook secret
  --clone-local-data          Clone local database schema/data to remote targets
  --clone-targets=LIST        Comma-separated clone targets (default: dev)
  --schema-only               Clone schema only (requires --clone-local-data)
  --data-only                 Clone data only (requires --clone-local-data)
  --embed-secrets             Embed secrets into local artifacts
  --keep-artifacts            Keep generated artifacts after the script exits
EOF
            exit 0
            ;;
        *) die "Unknown option: $1. Use --help for usage." ;;
    esac
done

[ "$SCHEMA_ONLY" = true ] && [ "$DATA_ONLY" = true ] && die "--schema-only and --data-only cannot be used together."
[ "$CLONE_LOCAL_DATA" != true ] && [ "$SCHEMA_ONLY" = true ] && die "--schema-only requires --clone-local-data."
[ "$CLONE_LOCAL_DATA" != true ] && [ "$DATA_ONLY" = true ] && die "--data-only requires --clone-local-data."
[ "$PACKAGE_ONLY" = true ] && [ "$EMBED_SECRETS" = true ] && [ "$KEEP_ARTIFACTS" != true ] && \
    die "--embed-secrets with --package-only requires --keep-artifacts."

parse_clone_targets
if [ "$CLONE_LOCAL_DATA" = true ]; then
    confirm_overwrite_targets "${CLONE_TARGET_LIST[@]}"
fi

banner

if [ "${PACKAGE_ONLY}" != true ] && [ -z "$REMOTE_HOST" ]; then
    read -rp "  SSH Host (e.g. ssh.example.com): " REMOTE_HOST
    [ -z "$REMOTE_HOST" ] && die "SSH host is required"
fi

[ -z "$REMOTE_HOST" ] && [ "${PACKAGE_ONLY}" = true ] && REMOTE_HOST="package-only.local"

if [ -z "$REMOTE_USER" ]; then
    read -rp "  SSH Username (cPanel username): " REMOTE_USER
    [ -z "$REMOTE_USER" ] && die "SSH username is required"
fi

if [ -z "$DB_USER" ]; then
    read -rp "  Remote MySQL username [${REMOTE_USER}]: " DB_USER
    DB_USER="${DB_USER:-$REMOTE_USER}"
fi

if [ -z "$DB_PASS" ]; then
    read -rsp "  Remote MySQL password: " DB_PASS
    echo ""
    [ -z "$DB_PASS" ] && die "MySQL password is required"
fi

SSH_ARGS=(-p "${REMOTE_PORT}" -o ConnectTimeout=10 -o StrictHostKeyChecking=accept-new)
SCP_ARGS=(-P "${REMOTE_PORT}" -o ConnectTimeout=10 -o StrictHostKeyChecking=accept-new)
if [ -n "$SSH_KEY" ]; then
    SSH_ARGS+=(-i "$SSH_KEY")
    SCP_ARGS+=(-i "$SSH_KEY")
fi

REMOTE_HOME="/home/${REMOTE_USER}"

step "Step 1/7: Building deployment packages locally"
rm -rf "${OUTPUT_DIR}"
mkdir -p "${OUTPUT_DIR}"

if [ "$EMBED_SECRETS" = true ]; then
    warn "WARNING: generated deployment artifacts contain plaintext secrets."
fi

if [ "${SKIP_COMPOSER_INSTALL}" = true ]; then
    info "Skipping composer install and reusing the current vendor/ directory"
else
    info "Running composer install --no-dev..."
    composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || warn "Composer install failed, using existing vendor/"
fi

if [ "${PACKAGE_ONLY}" != true ]; then
    SECRET_STAGING_DIR="$(mktemp -d "${TMPDIR:-/tmp}/candlecraft-secret-configs.XXXXXX")"
    register_temp_path "${SECRET_STAGING_DIR}"
fi

for ENV in dev production review; do
    ENV_DIR="${OUTPUT_DIR}/${ENV}_app"
    mkdir -p "${ENV_DIR}"

    info "Packaging ${ENV}_app/..."
    cp -r bin config resources src templates vendor webroot "${ENV_DIR}/"
    cp composer.json composer.lock index.php .htaccess "${ENV_DIR}/" 2>/dev/null || true
    cp LICENSE "${ENV_DIR}/" 2>/dev/null || true
    rm -f "${ENV_DIR}/config/app_local.php"

    mkdir -p "${ENV_DIR}/tmp/cache/models" \
             "${ENV_DIR}/tmp/cache/persistent" \
             "${ENV_DIR}/tmp/sessions" \
             "${ENV_DIR}/tmp/tests" \
             "${ENV_DIR}/logs"

    if [ "$ENV" = "production" ]; then
        DEBUG_VAL="false"
        SUBDIR="production"
    elif [ "$ENV" = "review" ]; then
        DEBUG_VAL="true"
        SUBDIR="review"
    else
        DEBUG_VAL="true"
        SUBDIR="dev"
    fi

    ENV_SALT="$(generate_salt)"
    DB_NAME="${REMOTE_USER}_${ENV}_db"
    UPLOADS_ROOT="${REMOTE_HOME}/${ENV}_app/storage/resources"
    UPLOADS_URL_PREFIX="/resources"

    write_app_local "${ENV_DIR}/config/app_local.template.php" "$ENV" "$DEBUG_VAL" "${DB_USER}" "${DB_PASS}" "${DB_NAME}" "${ENV_SALT}" false "${UPLOADS_ROOT}" "${UPLOADS_URL_PREFIX}"

    if [ "$EMBED_SECRETS" = true ]; then
        write_app_local "${ENV_DIR}/config/app_local.php" "$ENV" "$DEBUG_VAL" "${DB_USER}" "${DB_PASS}" "${DB_NAME}" "${ENV_SALT}" true "${UPLOADS_ROOT}" "${UPLOADS_URL_PREFIX}"
        chmod 600 "${ENV_DIR}/config/app_local.php"
        validate_generated_app_local "${ENV_DIR}/config/app_local.php" "${ENV}"
    fi

    if [ "${PACKAGE_ONLY}" != true ]; then
        mkdir -p "${SECRET_STAGING_DIR}/${ENV}_app/config"
        write_app_local "${SECRET_STAGING_DIR}/${ENV}_app/config/app_local.php" "$ENV" "$DEBUG_VAL" "${DB_USER}" "${DB_PASS}" "${DB_NAME}" "${ENV_SALT}" true "${UPLOADS_ROOT}" "${UPLOADS_URL_PREFIX}"
        chmod 600 "${SECRET_STAGING_DIR}/${ENV}_app/config/app_local.php"
        validate_generated_app_local "${SECRET_STAGING_DIR}/${ENV}_app/config/app_local.php" "${ENV}"
    fi

    sed -i.bak "s/'base' => false/'base' => '\/${SUBDIR}'/" "${ENV_DIR}/config/app.php" 2>/dev/null || true
    rm -f "${ENV_DIR}/config/app.php.bak"
done

for ENV in dev production review; do
    PUB_DIR="${OUTPUT_DIR}/public_${ENV}"
    SUBDIR="$ENV"
    mkdir -p "${PUB_DIR}"
    cp -r webroot/css webroot/js webroot/img "${PUB_DIR}/" 2>/dev/null || true
    mkdir -p "${PUB_DIR}/uploads"
    mkdir -p "${PUB_DIR}/uploads/resources"
    cat > "${PUB_DIR}/uploads/resources/.htaccess" <<'APACHEPROTECT'
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Deny from all
</IfModule>
Options -Indexes
APACHEPROTECT
    cp webroot/favicon.ico "${PUB_DIR}/" 2>/dev/null || true

    cat > "${PUB_DIR}/index.php" <<PHPEOF
<?php
if (PHP_SAPI === 'cli-server') {
    \$_SERVER['PHP_SELF'] = '/' . basename(__FILE__);
    \$url = parse_url(urldecode(\$_SERVER['REQUEST_URI']));
    \$file = __DIR__ . \$url['path'];
    if (!str_contains(\$url['path'], '..') && str_contains(\$url['path'], '.') && is_file(\$file)) {
        return false;
    }
}
define('APP_ROOT', '${REMOTE_HOME}/${ENV}_app');
require APP_ROOT . '/vendor/autoload.php';
use App\Application;
use Cake\Http\Server;
\$server = new Server(new Application(APP_ROOT . '/config'));
\$server->emit(\$server->run());
PHPEOF

    cat > "${PUB_DIR}/.htaccess" <<APACHEOF
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /${SUBDIR}
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
APACHEOF
done

info "Creating ZIP archives..."
(
    cd "${OUTPUT_DIR}"
    for ENV in dev production review; do
        zip -rq "${ENV}_app.zip" "${ENV}_app/"
    done
    zip -rq "public_all.zip" public_dev/ public_production/ public_review/
)
success "Packages built in ${OUTPUT_DIR}/"

if [ "$CLONE_LOCAL_DATA" = true ] && [ "$PACKAGE_ONLY" = true ]; then
    step "Step 2/7: Exporting local database for package-only workflow"
    export_local_database_dump "${OUTPUT_DIR}/database.sql"
    success "Local database export generated at ${OUTPUT_DIR}/database.sql"
fi

if [ "${PACKAGE_ONLY}" = true ]; then
    success "Package-only mode complete."
    exit 0
fi

step "Step 2/7: Verifying SSH connection"
ssh_run "echo 'SSH connection successful'" >/dev/null 2>&1 || die "Cannot connect via SSH. Check your credentials and ensure SSH is enabled in cPanel."
success "SSH connection established"

step "Step 3/7: Uploading files to server"
for ENV in dev production review; do
    info "Uploading ${ENV}_app.zip..."
    scp_upload "${OUTPUT_DIR}/${ENV}_app.zip" "${REMOTE_HOME}/"
done
info "Uploading public_all.zip..."
scp_upload "${OUTPUT_DIR}/public_all.zip" "${REMOTE_HOME}/"
success "Application archives uploaded"

step "Step 4/7: Extracting files and installing runtime config"
ssh "${SSH_ARGS[@]}" "${REMOTE_USER}@${REMOTE_HOST}" 'bash -s' -- "${REMOTE_HOME}" <<'REMOTE_SCRIPT'
set -euo pipefail
remote_home="$1"
cd "$remote_home"

for env in dev production review; do
    rm -rf "${env}_app"
    unzip -qo "${env}_app.zip"
    rm -f "${env}_app.zip"
done

unzip -qo "public_all.zip" -d public_extracted/
mkdir -p public_html/dev public_html/production public_html/review
for env in dev production review; do
    cp -rf "public_extracted/public_${env}/"* "public_html/${env}/" 2>/dev/null || true
done
rm -rf public_extracted public_all.zip
REMOTE_SCRIPT

for ENV in dev production review; do
    info "Uploading secure runtime config for ${ENV}..."
    scp_upload "${SECRET_STAGING_DIR}/${ENV}_app/config/app_local.php" "${REMOTE_HOME}/${ENV}_app/config/app_local.php"
done

ssh "${SSH_ARGS[@]}" "${REMOTE_USER}@${REMOTE_HOST}" 'bash -s' -- "${REMOTE_HOME}" <<'REMOTE_SCRIPT'
set -euo pipefail
remote_home="$1"
for env in dev production review; do
    chmod 600 "${remote_home}/${env}_app/config/app_local.php"
done
REMOTE_SCRIPT
success "Remote packages extracted and runtime config installed"

step "Step 5/7: Creating remote databases and running migrations"
ssh "${SSH_ARGS[@]}" "${REMOTE_USER}@${REMOTE_HOST}" 'bash -s' -- "${DB_USER}" "${DB_PASS}" "${REMOTE_USER}" "${REMOTE_HOME}" <<'REMOTE_SCRIPT'
set -euo pipefail
db_user="$1"
db_pass="$2"
cpanel_user="$3"
remote_home="$4"

escape_mysql_option() {
    php -r 'echo addcslashes($argv[1], "\\\"\n\r");' "$1"
}

create_defaults_file() {
    local file_path
    file_path="$(mktemp "${TMPDIR:-/tmp}/candlecraft-remote-mysql.XXXXXX")"
    (
        umask 077
        cat > "$file_path" <<EOF
[client]
host="localhost"
user="$(escape_mysql_option "$db_user")"
password="$(escape_mysql_option "$db_pass")"
EOF
    )
    chmod 600 "$file_path"
    printf '%s' "$file_path"
}

defaults_file="$(create_defaults_file)"
trap 'rm -f "$defaults_file"' EXIT

for env in dev production review; do
    db_name="${cpanel_user}_${env}_db"
    uapi --user="${cpanel_user}" Mysql create_database name="${db_name}" >/dev/null 2>&1 || true
done

for env in dev production review; do
    (cd "${remote_home}/${env}_app" && php bin/cake.php migrations migrate)
done
REMOTE_SCRIPT
success "Remote databases prepared and migrations applied"

if [ "$CLONE_LOCAL_DATA" = true ]; then
    step "Step 6/7: Cloning local database into selected remote targets"
    DUMP_FILE="$(mktemp "${TMPDIR:-/tmp}/candlecraft-clone-data.XXXXXX.sql")"
    register_temp_path "${DUMP_FILE}"
    export_local_database_dump "${DUMP_FILE}"
    scp_upload "${DUMP_FILE}" "${REMOTE_HOME}/clone-data.sql"

    ssh "${SSH_ARGS[@]}" "${REMOTE_USER}@${REMOTE_HOST}" 'bash -s' -- "${DB_USER}" "${DB_PASS}" "${REMOTE_USER}" "${REMOTE_HOME}/clone-data.sql" "${CLONE_TARGET_LIST[@]}" <<'REMOTE_SCRIPT'
set -euo pipefail
db_user="$1"
db_pass="$2"
cpanel_user="$3"
dump_file="$4"
shift 4
targets=("$@")

escape_mysql_option() {
    php -r 'echo addcslashes($argv[1], "\\\"\n\r");' "$1"
}

create_defaults_file() {
    local file_path
    file_path="$(mktemp "${TMPDIR:-/tmp}/candlecraft-remote-mysql.XXXXXX")"
    (
        umask 077
        cat > "$file_path" <<EOF
[client]
host="localhost"
user="$(escape_mysql_option "$db_user")"
password="$(escape_mysql_option "$db_pass")"
EOF
    )
    chmod 600 "$file_path"
    printf '%s' "$file_path"
}

defaults_file="$(create_defaults_file)"
trap 'rm -f "$defaults_file"' EXIT
mkdir -p "$HOME/backups"

timestamp="$(date +%Y%m%d-%H%M%S)"
for env in "${targets[@]}"; do
    db_name="${cpanel_user}_${env}_db"
    mysqldump --defaults-extra-file="$defaults_file" --no-tablespaces --single-transaction --quick "$db_name" > "$HOME/backups/${db_name}-before-clone-${timestamp}.sql"
    mysql --defaults-extra-file="$defaults_file" "$db_name" < "$dump_file"
done

rm -f "$dump_file"
REMOTE_SCRIPT
    success "Selected remote databases updated from local dump"
else
    step "Step 6/7: Skipping local data clone"
    info "Default deployment path is migrations-first with no business data copied."
fi

step "Step 7/7: Finalizing deployment"
if [ "$EMBED_SECRETS" = true ] && [ "$KEEP_ARTIFACTS" != true ]; then
    rm -rf "${OUTPUT_DIR}"
    success "Removed local secret-bearing artifacts."
else
    info "Artifacts available at ${OUTPUT_DIR}/"
fi

success "Deployment complete."
