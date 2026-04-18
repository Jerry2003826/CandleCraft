#!/bin/bash
# ============================================================================
# CandleCraft - One-Click cPanel Deployment
# ============================================================================
# Automates the full deployment process to cPanel shared hosting.
#
# What it does:
#   1. Builds production-ready packages locally (composer install --no-dev)
#   2. Exports local MySQL database
#   3. Uploads everything to cPanel via SSH/SCP
#   4. Creates databases and users on the remote server
#   5. Imports database schema + data
#   6. Configures each environment (dev, production, review)
#   7. Sets file permissions
#   8. Verifies deployment
#
# Prerequisites:
#   - SSH access to cPanel server (Terminal or SSH)
#   - Local MySQL running with the app database
#   - composer installed locally
#   - zip installed locally
#
# Usage:
#   ./scripts/deploy-oneclick.sh
#
# Or with arguments:
#   ./scripts/deploy-oneclick.sh --host ssh.example.com --user myuser --db-pass secret
# ============================================================================

set -e

# ============================================================================
# CONFIGURATION - Edit these or pass them as arguments
# ============================================================================

# cPanel SSH
REMOTE_HOST=""
REMOTE_USER=""
REMOTE_PORT="22"
SSH_KEY=""             # Optional: path to SSH private key

# cPanel Database (shared across all envs, prefixed per env)
DB_HOST="localhost"
DB_USER=""             # cPanel MySQL username (usually same as cPanel username)
DB_PASS=""             # cPanel MySQL password

# Local database to export
LOCAL_DB_HOST="localhost"
LOCAL_DB_USER="root"
LOCAL_DB_PASS="root"
LOCAL_DB_NAME="academy_management_db"

# App settings
STRIPE_SECRET_KEY=""
STRIPE_PUBLISHABLE_KEY=""
STRIPE_WEBHOOK_SECRET=""

# Derived
OUTPUT_DIR="./deploy-output"

# ============================================================================
# COLORS & HELPERS
# ============================================================================

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

# ============================================================================
# PARSE ARGUMENTS
# ============================================================================

while [[ $# -gt 0 ]]; do
    case $1 in
        --host)         REMOTE_HOST="$2"; shift 2 ;;
        --user)         REMOTE_USER="$2"; shift 2 ;;
        --port)         REMOTE_PORT="$2"; shift 2 ;;
        --ssh-key)      SSH_KEY="$2"; shift 2 ;;
        --db-user)      DB_USER="$2"; shift 2 ;;
        --db-pass)      DB_PASS="$2"; shift 2 ;;
        --local-db)     LOCAL_DB_NAME="$2"; shift 2 ;;
        --local-db-user) LOCAL_DB_USER="$2"; shift 2 ;;
        --local-db-pass) LOCAL_DB_PASS="$2"; shift 2 ;;
        --stripe-sk)    STRIPE_SECRET_KEY="$2"; shift 2 ;;
        --stripe-pk)    STRIPE_PUBLISHABLE_KEY="$2"; shift 2 ;;
        --stripe-wh)    STRIPE_WEBHOOK_SECRET="$2"; shift 2 ;;
        --help|-h)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --host HOST          cPanel SSH hostname"
            echo "  --user USER          cPanel SSH username"
            echo "  --port PORT          SSH port (default: 22)"
            echo "  --ssh-key PATH       SSH private key path"
            echo "  --db-user USER       Remote MySQL username"
            echo "  --db-pass PASS       Remote MySQL password"
            echo "  --local-db NAME      Local database name (default: academy_management_db)"
            echo "  --local-db-user USER Local MySQL username (default: root)"
            echo "  --local-db-pass PASS Local MySQL password (default: root)"
            echo "  --stripe-sk KEY      Stripe secret key"
            echo "  --stripe-pk KEY      Stripe publishable key"
            echo "  --stripe-wh SECRET   Stripe webhook secret"
            exit 0
            ;;
        *) die "Unknown option: $1. Use --help for usage." ;;
    esac
done

# ============================================================================
# INTERACTIVE PROMPTS (if not provided via arguments)
# ============================================================================

banner

if [ -z "$REMOTE_HOST" ]; then
    echo -e "${BOLD}Please enter your cPanel server details:${NC}"
    read -rp "  SSH Host (e.g. ssh.example.com): " REMOTE_HOST
    [ -z "$REMOTE_HOST" ] && die "SSH host is required"
fi

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

# Build SSH command
SSH_CMD="ssh -p ${REMOTE_PORT} -o ConnectTimeout=10 -o StrictHostKeyChecking=accept-new"
if [ -n "$SSH_KEY" ]; then
    SSH_CMD="$SSH_CMD -i $SSH_KEY"
fi
SSH_CMD="$SSH_CMD ${REMOTE_USER}@${REMOTE_HOST}"

SCP_CMD="scp -P ${REMOTE_PORT} -o ConnectTimeout=10 -o StrictHostKeyChecking=accept-new"
if [ -n "$SSH_KEY" ]; then
    SCP_CMD="$SCP_CMD -i $SSH_KEY"
fi

REMOTE_HOME="/home/${REMOTE_USER}"

# ============================================================================
# STEP 0: Verify connectivity
# ============================================================================

step "Step 0/8: Verifying SSH connection"
info "Connecting to ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PORT}..."

$SSH_CMD "echo 'SSH connection successful'" > /dev/null 2>&1 || {
    die "Cannot connect via SSH. Check your credentials and ensure SSH is enabled in cPanel."
}
success "SSH connection established"

# Check remote PHP version
REMOTE_PHP=$($SSH_CMD "php -v 2>/dev/null | head -1" || echo "PHP not found")
info "Remote PHP: ${REMOTE_PHP}"

# Check if mysql client is available on remote
$SSH_CMD "which mysql > /dev/null 2>&1" && {
    success "Remote MySQL client available"
} || {
    warn "MySQL client not found on remote server, will use cPanel UAPI for database operations"
}

# ============================================================================
# STEP 1: Build packages locally
# ============================================================================

step "Step 1/8: Building deployment packages locally"

rm -rf "${OUTPUT_DIR}"
mkdir -p "${OUTPUT_DIR}"

info "Running composer install --no-dev..."
composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || {
    warn "Composer install failed, using existing vendor/"
}

for ENV in dev production review; do
    ENV_DIR="${OUTPUT_DIR}/${ENV}_app"
    mkdir -p "${ENV_DIR}"

    info "Packaging ${ENV}_app/..."
    cp -r bin config resources src templates vendor webroot "${ENV_DIR}/"
    cp composer.json composer.lock index.php .htaccess "${ENV_DIR}/" 2>/dev/null || true
    cp LICENSE "${ENV_DIR}/" 2>/dev/null || true

    # Create runtime directories
    mkdir -p "${ENV_DIR}/tmp/cache/models" \
             "${ENV_DIR}/tmp/cache/persistent" \
             "${ENV_DIR}/tmp/sessions" \
             "${ENV_DIR}/tmp/tests" \
             "${ENV_DIR}/logs"

    # --- Generate app_local.php ---
    DEBUG_VAL="true"
    [ "$ENV" = "production" ] && DEBUG_VAL="false"

    cat > "${ENV_DIR}/config/app_local.php" << PHPEOF
<?php
use function Cake\Core\env;

// ${ENV} environment - auto-generated by deploy-oneclick.sh
return [
    'debug' => filter_var(env('DEBUG', ${DEBUG_VAL}), FILTER_VALIDATE_BOOLEAN),
    'Security' => [
        'salt' => env('SECURITY_SALT', '__SALT__'),
    ],
    'Datasources' => [
        'default' => [
            'host' => 'localhost',
            'username' => '${DB_USER}',
            'password' => '${DB_PASS}',
            'database' => '${REMOTE_USER}_${ENV}_db',
            'url' => env('DATABASE_URL', null),
        ],
        'test' => [
            'host' => 'localhost',
            'username' => '${DB_USER}',
            'password' => '${DB_PASS}',
            'database' => '${REMOTE_USER}_${ENV}_test_db',
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
        'secret_key' => env('STRIPE_SECRET_KEY', '${STRIPE_SECRET_KEY}'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', '${STRIPE_PUBLISHABLE_KEY}'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', '${STRIPE_WEBHOOK_SECRET}'),
    ],
    'Recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', null),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', null),
    ],
];
PHPEOF

    # Set App.base for subdirectory routing
    if [ "$ENV" = "production" ]; then
        SUBDIR="production"
    elif [ "$ENV" = "review" ]; then
        SUBDIR="review"
    else
        SUBDIR="dev"
    fi

    sed -i.bak "s/'base' => false/'base' => '\/${SUBDIR}'/" "${ENV_DIR}/config/app.php" 2>/dev/null || true
    rm -f "${ENV_DIR}/config/app.php.bak"
done

# Create public_html files for each env
for ENV in dev production review; do
    if [ "$ENV" = "production" ]; then
        PUB_DIR="${OUTPUT_DIR}/public_${ENV}"
        SUBDIR="production"
    elif [ "$ENV" = "review" ]; then
        PUB_DIR="${OUTPUT_DIR}/public_${ENV}"
        SUBDIR="review"
    else
        PUB_DIR="${OUTPUT_DIR}/public_${ENV}"
        SUBDIR="dev"
    fi

    mkdir -p "${PUB_DIR}"
    cp -r webroot/css webroot/js webroot/img "${PUB_DIR}/" 2>/dev/null || true
    mkdir -p "${PUB_DIR}/uploads"
    cp webroot/favicon.ico "${PUB_DIR}/" 2>/dev/null || true

    # index.php pointing to app root
    cat > "${PUB_DIR}/index.php" << PHPEOF
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

    # .htaccess with RewriteBase
    cat > "${PUB_DIR}/.htaccess" << APACHEOF
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /${SUBDIR}
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
APACHEOF
done

# ZIP for faster transfer
info "Creating ZIP archives..."
cd "${OUTPUT_DIR}"
for ENV in dev production review; do
    zip -rq "${ENV}_app.zip" "${ENV}_app/"
done
zip -rq "public_all.zip" public_dev/ public_production/ public_review/
cd - > /dev/null

success "Packages built in ${OUTPUT_DIR}/"

# ============================================================================
# STEP 2: Export local database
# ============================================================================

step "Step 2/8: Exporting local database"

info "Exporting '${LOCAL_DB_NAME}' from local MySQL..."
mysqldump -h "${LOCAL_DB_HOST}" -u "${LOCAL_DB_USER}" -p"${LOCAL_DB_PASS}" \
    --no-tablespaces --single-quick \
    "${LOCAL_DB_NAME}" > "${OUTPUT_DIR}/database.sql" 2>/dev/null || {
    warn "mysqldump failed. Trying without password..."
    mysqldump -h "${LOCAL_DB_HOST}" -u "${LOCAL_DB_USER}" \
        --no-tablespaces --single-quick \
        "${LOCAL_DB_NAME}" > "${OUTPUT_DIR}/database.sql" 2>/dev/null || {
        die "Could not export database. Check your local MySQL credentials."
    }
}

SQL_SIZE=$(du -sh "${OUTPUT_DIR}/database.sql" | cut -f1)
success "Database exported (${SQL_SIZE})"

# ============================================================================
# STEP 3: Upload to server
# ============================================================================

step "Step 3/8: Uploading files to server"

info "Uploading ZIP archives (this may take a few minutes)..."

# Upload app ZIP files
for ENV in dev production review; do
    info "Uploading ${ENV}_app.zip..."
    $SCP_CMD "${OUTPUT_DIR}/${ENV}_app.zip" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_HOME}/" 2>/dev/null
done

# Upload public files
info "Uploading public_all.zip..."
$SCP_CMD "${OUTPUT_DIR}/public_all.zip" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_HOME}/" 2>/dev/null

# Upload database dump
info "Uploading database.sql..."
$SCP_CMD "${OUTPUT_DIR}/database.sql" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_HOME}/" 2>/dev/null

success "All files uploaded"

# ============================================================================
# STEP 4: Extract and organize files on server
# ============================================================================

step "Step 4/8: Extracting files on server"

$SSH_CMD bash << 'REMOTE_SCRIPT'
set -e
cd $HOME

echo "  Extracting application packages..."
for ENV in dev production review; do
    # Remove old deployment if exists
    rm -rf "${ENV}_app"
    unzip -qo "${ENV}_app.zip"
    rm -f "${ENV}_app.zip"
    echo "  ✔ ${ENV}_app/ extracted"
done

echo "  Extracting public webroot files..."
unzip -qo "public_all.zip" -d public_extracted/

# Move each public folder to public_html subdirectory
mkdir -p public_html/dev public_html/production public_html/review

for ENV in dev production review; do
    # Copy webroot files (overwrite)
    cp -rf "public_extracted/public_${ENV}/"* "public_html/${ENV}/" 2>/dev/null || true
    echo "  ✔ public_html/${ENV}/ updated"
done

rm -rf public_extracted public_all.zip

echo "  ✔ All files extracted and organized"
REMOTE_SCRIPT

success "Files extracted on server"

# ============================================================================
# STEP 5: Create databases on remote server
# ============================================================================

step "Step 5/8: Creating databases on remote server"

$SSH_CMD bash << REMOTE_SCRIPT
set -e
cd \$HOME

DB_USER="${DB_USER}"
DB_PASS="${DB_PASS}"
CPANEL_USER="${REMOTE_USER}"

for ENV in dev production review; do
    DB_NAME="\${CPANEL_USER}_\${ENV}_db"

    # Try cPanel UAPI first (works on shared hosting)
    echo "  Creating database \${DB_NAME}..."
    uapi --user=\${CPANEL_USER} Mysql create_database name=\${DB_NAME} 2>/dev/null && \\
        echo "  ✔ Database \${DB_NAME} created via UAPI" || \\
        echo "  ⚠ UAPI failed, trying mysql directly..."

    # Fallback: try direct mysql
    mysql -u "\${DB_USER}" -p"\${DB_PASS}" -e "CREATE DATABASE IF NOT EXISTS \\\`\${DB_NAME}\\\`;" 2>/dev/null && \\
        echo "  ✔ Database \${DB_NAME} created via mysql" || \\
        echo "  ⚠ Could not create database \${DB_NAME} - may already exist"
done

REMOTE_SCRIPT

success "Databases created"

# ============================================================================
# STEP 6: Import database
# ============================================================================

step "Step 6/8: Importing database data"

for ENV in dev production review; do
    DB_NAME="${REMOTE_USER}_${ENV}_db"
    info "Importing into ${DB_NAME}..."

    $SSH_CMD "mysql -u '${DB_USER}' -p'${DB_PASS}' '${DB_NAME}' < ${REMOTE_HOME}/database.sql" 2>/dev/null && {
        success "${DB_NAME} imported"
    } || {
        # Try with cPanel user as MySQL user
        $SSH_CMD "mysql -u '${REMOTE_USER}' -p'${DB_PASS}' '${DB_NAME}' < ${REMOTE_HOME}/database.sql" 2>/dev/null && {
            success "${DB_NAME} imported"
        } || {
            warn "Could not import ${DB_NAME} automatically. Import manually via phpMyAdmin."
        }
    }
done

# Cleanup SQL file from remote
$SSH_CMD "rm -f ${REMOTE_HOME}/database.sql" 2>/dev/null

# ============================================================================
# STEP 7: Set permissions
# ============================================================================

step "Step 7/8: Setting file permissions"

$SSH_CMD bash << REMOTE_SCRIPT
set -e
cd \$HOME

for ENV in dev production review; do
    # App directories
    chmod -R 755 "\${ENV}_app/tmp" 2>/dev/null || true
    chmod -R 755 "\${ENV}_app/logs" 2>/dev/null || true

    # Public upload directories
    mkdir -p "public_html/\${ENV}/uploads"
    chmod -R 755 "public_html/\${ENV}/uploads" 2>/dev/null || true

    echo "  ✔ Permissions set for \${ENV}"
done
REMOTE_SCRIPT

success "Permissions configured"

# ============================================================================
# STEP 8: Verify deployment
# ============================================================================

step "Step 8/8: Verifying deployment"

# Check critical files exist
$SSH_CMD bash << REMOTE_SCRIPT
echo "  Checking deployment structure..."
ERRORS=0

for ENV in dev production review; do
    # Check app code
    if [ -f "\${ENV}_app/vendor/autoload.php" ]; then
        echo "  ✔ \${ENV}_app/vendor/autoload.php exists"
    else
        echo "  ✘ \${ENV}_app/vendor/autoload.php MISSING"
        ERRORS=1
    fi

    if [ -f "\${ENV}_app/config/app_local.php" ]; then
        echo "  ✔ \${ENV}_app/config/app_local.php exists"
    else
        echo "  ✘ \${ENV}_app/config/app_local.php MISSING"
        ERRORS=1
    fi

    # Check public files
    if [ -f "public_html/\${ENV}/index.php" ]; then
        echo "  ✔ public_html/\${ENV}/index.php exists"
    else
        echo "  ✘ public_html/\${ENV}/index.php MISSING"
        ERRORS=1
    fi

    if [ -f "public_html/\${ENV}/.htaccess" ]; then
        echo "  ✔ public_html/\${ENV}/.htaccess exists"
    else
        echo "  ✘ public_html/\${ENV}/.htaccess MISSING"
        ERRORS=1
    fi
done

if [ \$ERRORS -eq 0 ]; then
    echo "  ✔ All critical files verified"
else
    echo "  ⚠ Some files are missing - check above"
fi
REMOTE_SCRIPT

# ============================================================================
# DONE
# ============================================================================

# Get the domain from SSH host (remove ssh. prefix if present)
DEPLOY_DOMAIN="${REMOTE_HOST}"
DEPLOY_DOMAIN="${DEPLOY_DOMAIN#ssh.}"
DEPLOY_DOMAIN="${DEPLOY_DOMAIN#server.}"

echo ""
echo -e "${CYAN}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║${NC}  ${GREEN}${BOLD}✔ DEPLOYMENT COMPLETE${NC}                                    ${CYAN}║${NC}"
echo -e "${CYAN}╠══════════════════════════════════════════════════════════╣${NC}"
echo -e "${CYAN}║${NC}                                                          ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}  ${BOLD}Access your environments:${NC}                               ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}    Dev:         https://${DEPLOY_DOMAIN}/dev${NC}"
echo -e "${CYAN}║${NC}    Production:  https://${DEPLOY_DOMAIN}/production${NC}"
echo -e "${CYAN}║${NC}    Review:      https://${DEPLOY_DOMAIN}/review${NC}"
echo -e "${CYAN}║${NC}                                                          ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}  ${BOLD}Server paths:${NC}                                            ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}    App code:    ${REMOTE_HOME}/{env}_app/${NC}"
echo -e "${CYAN}║${NC}    Web files:   ${REMOTE_HOME}/public_html/{env}/${NC}"
echo -e "${CYAN}║${NC}                                                          ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}  ${YELLOW}Post-deployment checklist:${NC}                               ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}    1. Set PHP 8.2+ in cPanel → MultiPHP Manager${NC}"
echo -e "${CYAN}║${NC}    2. Verify mod_rewrite is enabled${NC}"
echo -e "${CYAN}║${NC}    3. Set APP_FULL_BASE_URL if you get 500 errors${NC}"
echo -e "${CYAN}║${NC}    4. Import database via phpMyAdmin if auto-import failed${NC}"
echo -e "${CYAN}║${NC}                                                          ${CYAN}║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""

# Cleanup local output
read -rp "Delete local deployment packages? [Y/n] " CLEANUP
CLEANUP="${CLEANUP:-Y}"
if [[ "$CLEANUP" =~ ^[Yy] ]]; then
    rm -rf "${OUTPUT_DIR}"
    success "Local packages cleaned up"
fi

echo ""
echo -e "${BOLD}Done!${NC}"
