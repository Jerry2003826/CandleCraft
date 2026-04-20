#!/bin/bash
# ============================================================================
# cPanel Deployment Packager for CandleCraft (CakePHP 5)
# ============================================================================

set -euo pipefail

CPANEL_USER=""
DOMAIN=""
APP_NAME="CandleCraft"
OUTPUT_DIR="./cpanel-output"
SRC_DIR="$(pwd)"
DEV_DB_PASS=""
PRODUCTION_DB_PASS=""
REVIEW_DB_PASS=""
SKIP_COMPOSER_INSTALL=false
EMBED_SECRETS=false
KEEP_ARTIFACTS=false

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

info() {
    echo -e "${BLUE}$1${NC}"
}

success() {
    echo -e "${GREEN}$1${NC}"
}

warn() {
    echo -e "${YELLOW}$1${NC}"
}

die() {
    echo "Error: $1" >&2
    exit 1
}

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

prompt_for_password() {
    local env_name="$1"
    local current_value="$2"
    local result="$current_value"

    if [ -z "$result" ]; then
        read -rsp "${env_name} database password: " result
        echo ""
    fi

    [ -z "$result" ] && die "${env_name} database password is required."

    printf '%s' "$result"
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
    local uploads_root_literal uploads_url_prefix_literal
    host_literal="$(php_literal 'localhost')"
    user_literal="$(php_literal "$db_user")"
    pass_literal="$(php_literal "$db_pass")"
    database_literal="$(php_literal "$db_name")"
    salt_literal="$(php_literal "$salt")"
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
            'host' => env('DATABASE_HOST', 'localhost'),
            'username' => env('DATABASE_USERNAME', ${user_literal}),
            'password' => env('DATABASE_PASSWORD', null),
            'database' => env('DATABASE_NAME', ${database_literal}),
            'url' => env('DATABASE_URL', null),
        ],
        'test' => [
            'host' => env('DATABASE_TEST_HOST', 'localhost'),
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

validate_generated_app_local() {
    local file_path="$1"
    local env_name="$2"

    php -l "$file_path" >/dev/null || die "${env_name} generated config failed php -l validation"
    grep -q "__SALT__" "$file_path" && die "${env_name} app_local.php still contains __SALT__"
    grep -q "CHANGE_ME_" "$file_path" && die "${env_name} app_local.php still contains placeholder credentials"

    if [ "$env_name" = "production" ]; then
        grep -q "'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN)," "$file_path" || \
            die "production app_local.php must default debug to false"
    fi
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --output-dir) OUTPUT_DIR="$2"; shift 2 ;;
        --dev-db-pass) DEV_DB_PASS="$2"; shift 2 ;;
        --production-db-pass) PRODUCTION_DB_PASS="$2"; shift 2 ;;
        --review-db-pass) REVIEW_DB_PASS="$2"; shift 2 ;;
        --skip-composer-install) SKIP_COMPOSER_INSTALL=true; shift ;;
        --embed-secrets) EMBED_SECRETS=true; shift ;;
        --keep-artifacts) KEEP_ARTIFACTS=true; shift ;;
        --help|-h)
            cat <<'EOF'
Usage:
  ./scripts/cpanel-deploy.sh [CPANEL_USER] [DOMAIN] [OPTIONS]

Options:
  --output-dir PATH             Output directory (default: ./cpanel-output)
  --dev-db-pass PASSWORD        dev environment database password
  --production-db-pass PASSWORD production environment database password
  --review-db-pass PASSWORD     review environment database password
  --skip-composer-install       Reuse the current vendor/ directory without running composer install
  --embed-secrets               Write real app_local.php files into local artifacts
  --keep-artifacts              Keep generated local artifacts
EOF
            exit 0
            ;;
        --*)
            die "Unknown option: $1"
            ;;
        *)
            if [ -z "$CPANEL_USER" ]; then
                CPANEL_USER="$1"
            elif [ -z "$DOMAIN" ]; then
                DOMAIN="$1"
            else
                die "Unexpected extra argument: $1"
            fi
            shift
            ;;
    esac
done

[ "$EMBED_SECRETS" = true ] && [ "$KEEP_ARTIFACTS" != true ] && \
    die "--embed-secrets requires --keep-artifacts so plaintext secrets are never left behind by accident."

CPANEL_USER="${CPANEL_USER:-cpaneluser}"
DOMAIN="${DOMAIN:-example.com}"
DEV_DB_PASS="$(prompt_for_password "dev" "${DEV_DB_PASS}")"
PRODUCTION_DB_PASS="$(prompt_for_password "production" "${PRODUCTION_DB_PASS}")"
REVIEW_DB_PASS="$(prompt_for_password "review" "${REVIEW_DB_PASS}")"

echo -e "${BLUE}=== cPanel Deployment Packager for ${APP_NAME} ===${NC}"
echo "cPanel User: ${CPANEL_USER}"
echo "Domain:      ${DOMAIN}"
echo "Output:      ${OUTPUT_DIR}/"
echo ""

info "[1/6] Cleaning output directory..."
rm -rf "${OUTPUT_DIR}"
mkdir -p "${OUTPUT_DIR}/database"

info "[2/6] Installing production dependencies..."
if [ "${SKIP_COMPOSER_INSTALL}" = true ]; then
    echo "Skipping composer install and reusing the current vendor/ directory."
else
    composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || {
        warn "composer install failed. Using existing vendor/ directory."
    }
fi

for ENV in dev production review; do
    info "[3/6] Creating ${ENV}_app package..."

    ENV_DIR="${OUTPUT_DIR}/${ENV}_app"
    mkdir -p "${ENV_DIR}"
    rm -rf "${ENV_DIR:?}"/*

    cp -r bin "${ENV_DIR}/"
    cp -r config "${ENV_DIR}/"
    cp -r resources "${ENV_DIR}/"
    cp -r src "${ENV_DIR}/"
    cp -r templates "${ENV_DIR}/"
    cp -r vendor "${ENV_DIR}/"
    cp -r webroot "${ENV_DIR}/"

    rm -f "${ENV_DIR}/config/app_local.php"
    mkdir -p "${ENV_DIR}/tmp/cache/models" "${ENV_DIR}/tmp/cache/persistent" "${ENV_DIR}/tmp/sessions" "${ENV_DIR}/tmp/tests" "${ENV_DIR}/logs"

    cp composer.json "${ENV_DIR}/"
    cp composer.lock "${ENV_DIR}/"
    cp index.php "${ENV_DIR}/"
    cp .htaccess "${ENV_DIR}/"
    cp LICENSE "${ENV_DIR}/" 2>/dev/null || true

    ENV_SALT="$(generate_salt)"
    if [ "$ENV" = "production" ]; then
        DB_PASSWORD="${PRODUCTION_DB_PASS}"
        DEBUG_VAL="false"
        SUBDIR="production"
    elif [ "$ENV" = "review" ]; then
        DB_PASSWORD="${REVIEW_DB_PASS}"
        DEBUG_VAL="true"
        SUBDIR="review"
    else
        DB_PASSWORD="${DEV_DB_PASS}"
        DEBUG_VAL="true"
        SUBDIR="dev"
    fi

    UPLOADS_ROOT="/home/${CPANEL_USER}/${ENV}_app/storage/resources"
    UPLOADS_URL_PREFIX="/resources"

    write_app_local "${ENV_DIR}/config/app_local.template.php" "$ENV" "$DEBUG_VAL" "${CPANEL_USER}_${ENV}" "$DB_PASSWORD" "${CPANEL_USER}_${ENV}_db" "$ENV_SALT" false "${UPLOADS_ROOT}" "${UPLOADS_URL_PREFIX}"

    if [ "$EMBED_SECRETS" = true ]; then
        write_app_local "${ENV_DIR}/config/app_local.php" "$ENV" "$DEBUG_VAL" "${CPANEL_USER}_${ENV}" "$DB_PASSWORD" "${CPANEL_USER}_${ENV}_db" "$ENV_SALT" true "${UPLOADS_ROOT}" "${UPLOADS_URL_PREFIX}"
        chmod 600 "${ENV_DIR}/config/app_local.php"
        validate_generated_app_local "${ENV_DIR}/config/app_local.php" "${ENV}"
    fi

    sed -i.bak "s/'base' => false/'base' => '\/${SUBDIR}'/" "${ENV_DIR}/config/app.php" 2>/dev/null || true
    rm -f "${ENV_DIR}/config/app.php.bak"
done

info "[4/6] Creating public_html subdirectory files..."
for ENV in dev production review; do
    if [ "$ENV" = "production" ]; then
        PUB_DIR="${OUTPUT_DIR}/public_html_production"
        SUBDIR="production"
    elif [ "$ENV" = "review" ]; then
        PUB_DIR="${OUTPUT_DIR}/public_html_review"
        SUBDIR="review"
    else
        PUB_DIR="${OUTPUT_DIR}/public_html_dev"
        SUBDIR="dev"
    fi

    mkdir -p "${PUB_DIR}"
    cp -r webroot/css "${PUB_DIR}/" 2>/dev/null || mkdir -p "${PUB_DIR}/css"
    cp -r webroot/js "${PUB_DIR}/" 2>/dev/null || mkdir -p "${PUB_DIR}/js"
    cp -r webroot/img "${PUB_DIR}/" 2>/dev/null || mkdir -p "${PUB_DIR}/img"
    cp -r webroot/uploads "${PUB_DIR}/" 2>/dev/null || mkdir -p "${PUB_DIR}/uploads"
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

    cat > "${PUB_DIR}/index.php" <<'PHPEOF'
<?php
if (PHP_SAPI === 'cli-server') {
    $_SERVER['PHP_SELF'] = '/' . basename(__FILE__);
    $url = parse_url(urldecode($_SERVER['REQUEST_URI']));
    $file = __DIR__ . $url['path'];
    if (!str_contains($url['path'], '..') && str_contains($url['path'], '.') && is_file($file)) {
        return false;
    }
}

define('APP_ROOT', '/home/CPANEL_USER/ENV_APP');
require APP_ROOT . '/vendor/autoload.php';

use App\Application;
use Cake\Http\Server;

$server = new Server(new Application(APP_ROOT . '/config'));
$server->emit($server->run());
PHPEOF

    sed -i.bak "s|CPANEL_USER|${CPANEL_USER}|g" "${PUB_DIR}/index.php"
    sed -i.bak "s|ENV_APP|${ENV}_app|g" "${PUB_DIR}/index.php"
    rm -f "${PUB_DIR}/index.php.bak"

    cat > "${PUB_DIR}/.htaccess" <<APACHEOF
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /${SUBDIR}
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
APACHEOF
done

info "[5/6] Preparing database export instructions..."
cp "${SRC_DIR}/config/schema/academy_management_db.sql" "${OUTPUT_DIR}/database/academy_management_db.sql"
cp "${SRC_DIR}/config/schema/seed_admin.sql" "${OUTPUT_DIR}/database/seed_admin.sql"

cat > "${OUTPUT_DIR}/database/README.md" <<'MDEOF'
# Database Setup for cPanel

Prefer CakePHP migrations + seeds for new environments. The SQL files in this folder are kept only as a fallback/reference path.

## Option A: phpMyAdmin Import (No SSH)

1. Go to cPanel → MySQL Databases
2. Create three databases:
   - `username_dev_db`
   - `username_production_db`
   - `username_review_db`
3. Create database users and assign them to the respective databases
4. Go to phpMyAdmin, select each database, and import `academy_management_db.sql`
5. Import `seed_admin.sql` only for a local/demo admin account if you explicitly need the legacy sample seed

## Option B: SSH/Terminal

```bash
# Create databases (replace username)
mysql -u username -p -e "CREATE DATABASE username_dev_db;"
mysql -u username -p -e "CREATE DATABASE username_production_db;"
mysql -u username -p -e "CREATE DATABASE username_review_db;"

# Import legacy reference SQL (optional fallback)
mysql -u username -p username_dev_db < academy_management_db.sql
mysql -u username -p username_production_db < academy_management_db.sql
mysql -u username -p username_review_db < academy_management_db.sql

# Optional legacy sample admin seed
mysql -u username -p username_dev_db < seed_admin.sql

# Preferred path: run migrations + seeds
cd /home/username/dev_app && php bin/cake.php migrations migrate
cd /home/username/production_app && php bin/cake.php migrations migrate
cd /home/username/review_app && php bin/cake.php migrations migrate
```
MDEOF

info "[6/6] Creating deployment guide..."
cat > "${OUTPUT_DIR}/README.md" <<MDEOF
# cPanel Deployment Guide - CandleCraft

## Quick Start

### 1. Upload Application Code
Upload each \`*_app.zip\` to \`/home/${CPANEL_USER}/\` and extract:
- \`dev_app/\` → \`/home/${CPANEL_USER}/dev_app/\`
- \`production_app/\` → \`/home/${CPANEL_USER}/production_app/\`
- \`review_app/\` → \`/home/${CPANEL_USER}/review_app/\`

### 2. Upload Webroot Files
Upload each \`public_html_*\` folder contents to the corresponding subdirectory:
- \`public_html_dev/*\` → \`/home/${CPANEL_USER}/public_html/dev/\`
- \`public_html_production/*\` → \`/home/${CPANEL_USER}/public_html/production/\`
- \`public_html_review/*\` → \`/home/${CPANEL_USER}/public_html/review/\`

### 3. Set File Permissions
\`\`\`
chmod 755 /home/${CPANEL_USER}/dev_app/tmp/
chmod 755 /home/${CPANEL_USER}/dev_app/logs/
chmod 755 /home/${CPANEL_USER}/public_html/dev/uploads/
(same for production and review)
\`\`\`

### 4. Configure Databases
Use \`config/app_local.template.php\` in each \`*_app/\` directory as the starting point for environment-specific secrets. If you generated artifacts with \`--embed-secrets\`, verify the real \`config/app_local.php\` files and rotate credentials if needed.

### 5. Verify
Visit:
- \`https://${DOMAIN}/dev\`
- \`https://${DOMAIN}/production\`
- \`https://${DOMAIN}/review\`

## Important Notes
- Replace \`${CPANEL_USER}\` with your actual cPanel username
- Set PHP version to 8.2+ in MultiPHP Manager
- Ensure \`mod_rewrite\` is enabled
- Update \`APP_FULL_BASE_URL\` if you encounter Host header errors
- For database setup, use the current deployment scripts to bootstrap the base schema and then run migrations. Manual SQL fallbacks still live in \`database/academy_management_db.sql\` and \`database/seed_admin.sql\`.
MDEOF

echo ""
echo -e "${BLUE}=== Creating ZIP archives ===${NC}"
cd "${OUTPUT_DIR}"
for ENV in dev production review; do
    zip -rq "${ENV}_app.zip" "${ENV}_app/"
    echo "  Created: ${ENV}_app.zip"
done
zip -rq "public_html_all.zip" public_html_dev/ public_html_production/ public_html_review/
echo "  Created: public_html_all.zip"
cd "${SRC_DIR}"

[ "$EMBED_SECRETS" = true ] && warn "WARNING: generated deployment artifacts contain plaintext secrets."

echo ""
success "=== Done! ==="
echo "Deployment packages are ready in: ${OUTPUT_DIR}/"
echo "Next steps:"
echo "  1. Upload *_app.zip files to /home/${CPANEL_USER}/ and extract"
echo "  2. Upload public_html contents to public_html/{dev,production,review}/"
echo "  3. Create MySQL databases in cPanel"
echo "  4. Use config/app_local.template.php to generate per-environment secrets"
echo "  5. Prefer migrations + seeds, or import academy_management_db.sql / seed_admin.sql as a fallback"
