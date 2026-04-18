#!/bin/bash
# ============================================================================
# cPanel Deployment Packager for CandleCraft (CakePHP 5)
# ============================================================================
# This script prepares deployment packages for dev, production, and review
# environments on cPanel shared hosting.
#
# Usage:
#   ./scripts/cpanel-deploy.sh [CPANEL_USER] [DOMAIN]
#
# Example:
#   ./scripts/cpanel-deploy.sh myuser example.com
#
# What it creates in ./cpanel-output/:
#   ├── dev_app/          - Full CakePHP app for dev environment
#   ├── production_app/   - Full CakePHP app for production environment
#   ├── review_app/       - Full CakePHP app for review environment
#   ├── public_html_dev/  - Webroot files for dev subdirectory
#   ├── public_html_prod/ - Webroot files for production subdirectory
#   ├── public_html_rev/  - Webroot files for review subdirectory
#   └── database/         - SQL export for import via phpMyAdmin
# ============================================================================

set -e

# ---- Configuration ----
CPANEL_USER="${1:-cpaneluser}"
DOMAIN="${2:-example.com}"
APP_NAME="CandleCraft"
OUTPUT_DIR="./cpanel-output"
SRC_DIR="$(pwd)"

# ---- Colors ----
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== cPanel Deployment Packager for ${APP_NAME} ===${NC}"
echo ""
echo "cPanel User: ${CPANEL_USER}"
echo "Domain:      ${DOMAIN}"
echo "Output:      ${OUTPUT_DIR}/"
echo ""

# ---- Step 1: Clean output directory ----
echo -e "${GREEN}[1/6] Cleaning output directory...${NC}"
rm -rf "${OUTPUT_DIR}"
mkdir -p "${OUTPUT_DIR}/database"

# ---- Step 2: Install production dependencies ----
echo -e "${GREEN}[2/6] Installing production dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || {
    echo "Warning: composer install failed. Using existing vendor/ directory."
}

# ---- Step 3: Create app packages for each environment ----
for ENV in dev production review; do
    echo -e "${GREEN}[3/6] Creating ${ENV}_app package...${NC}"

    ENV_DIR="${OUTPUT_DIR}/${ENV}_app"
    mkdir -p "${ENV_DIR}"

    # Copy full CakePHP application
    cp -r bin "${ENV_DIR}/"
    cp -r config "${ENV_DIR}/"
    cp -r resources "${ENV_DIR}/"
    cp -r src "${ENV_DIR}/"
    cp -r templates "${ENV_DIR}/"
    cp -r vendor "${ENV_DIR}/"
    cp -r webroot "${ENV_DIR}/"

    # Create required runtime directories
    mkdir -p "${ENV_DIR}/tmp/cache/models"
    mkdir -p "${ENV_DIR}/tmp/cache/persistent"
    mkdir -p "${ENV_DIR}/tmp/sessions"
    mkdir -p "${ENV_DIR}/tmp/tests"
    mkdir -p "${ENV_DIR}/logs"

    # Copy root files
    cp composer.json "${ENV_DIR}/"
    cp composer.lock "${ENV_DIR}/"
    cp index.php "${ENV_DIR}/"
    cp .htaccess "${ENV_DIR}/"
    cp LICENSE "${ENV_DIR}/" 2>/dev/null || true

    # ---- Modify app_local.php for this environment ----
    cat > "${ENV_DIR}/config/app_local.php" << PHPEOF
<?php

use function Cake\Core\env;

/*
 * ${ENV} environment configuration for cPanel deployment.
 * Database: ${CPANEL_USER}_${ENV}_db
 */
return [
    'debug' => filter_var(env('DEBUG', $([ "$ENV" = "production" ] && echo "false" || echo "true")), FILTER_VALIDATE_BOOLEAN),

    'Security' => [
        'salt' => env('SECURITY_SALT', '__SALT__'),
    ],

    'Datasources' => [
        'default' => [
            'host' => 'localhost',
            'username' => '${CPANEL_USER}_${ENV}',
            'password' => 'CHANGE_ME_${ENV}_DB_PASSWORD',
            'database' => '${CPANEL_USER}_${ENV}_db',
            'url' => env('DATABASE_URL', null),
        ],
        'test' => [
            'host' => 'localhost',
            'username' => '${CPANEL_USER}_${ENV}',
            'password' => 'CHANGE_ME_${ENV}_DB_PASSWORD',
            'database' => '${CPANEL_USER}_${ENV}_test_db',
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
];
PHPEOF

    # ---- Modify App.base for subdirectory routing ----
    if [ "$ENV" = "production" ]; then
        SUBDIR="production"
    elif [ "$ENV" = "review" ]; then
        SUBDIR="review"
    else
        SUBDIR="dev"
    fi

    # Update App.base in app.php
    sed -i.bak "s/'base' => false/'base' => '\/${SUBDIR}'/" "${ENV_DIR}/config/app.php" 2>/dev/null || true
    rm -f "${ENV_DIR}/config/app.php.bak"

done

# ---- Step 4: Create public_html subdirectory files ----
echo -e "${GREEN}[4/6] Creating public_html subdirectory files...${NC}"

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

    # Copy webroot assets
    cp -r webroot/css "${PUB_DIR}/" 2>/dev/null || mkdir -p "${PUB_DIR}/css"
    cp -r webroot/js "${PUB_DIR}/" 2>/dev/null || mkdir -p "${PUB_DIR}/js"
    cp -r webroot/img "${PUB_DIR}/" 2>/dev/null || mkdir -p "${PUB_DIR}/img"
    cp -r webroot/uploads "${PUB_DIR}/" 2>/dev/null || mkdir -p "${PUB_DIR}/uploads"
    cp webroot/favicon.ico "${PUB_DIR}/" 2>/dev/null || true

    # Create modified index.php pointing to app root
    cat > "${PUB_DIR}/index.php" << 'PHPEOF'
<?php
/**
 * cPanel Front Controller - CandleCraft
 *
 * This file is placed in public_html/{env}/ and routes requests
 * to the CakePHP application located outside the web root.
 */

// For built-in server
if (PHP_SAPI === 'cli-server') {
    $_SERVER['PHP_SELF'] = '/' . basename(__FILE__);

    $url = parse_url(urldecode($_SERVER['REQUEST_URI']));
    $file = __DIR__ . $url['path'];
    if (!str_contains($url['path'], '..') && str_contains($url['path'], '.') && is_file($file)) {
        return false;
    }
}

// >>> IMPORTANT: Change this path to match your cPanel setup <<<
// Format: /home/{cPanel_username}/{env}_app
define('APP_ROOT', '/home/CPANEL_USER/ENV_APP');

require APP_ROOT . '/vendor/autoload.php';

use App\Application;
use Cake\Http\Server;

$server = new Server(new Application(APP_ROOT . '/config'));
$server->emit($server->run());
PHPEOF

    # Replace placeholders with actual values
    sed -i.bak "s|CPANEL_USER|${CPANEL_USER}|g" "${PUB_DIR}/index.php"
    sed -i.bak "s|ENV_APP|${ENV}_app|g" "${PUB_DIR}/index.php"
    rm -f "${PUB_DIR}/index.php.bak"

    # Create .htaccess with RewriteBase for subdirectory
    cat > "${PUB_DIR}/.htaccess" << APACHEOF
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /${SUBDIR}
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
APACHEOF

done

# ---- Step 5: Export database ----
echo -e "${GREEN}[5/6] Preparing database export instructions...${NC}"

# Create a helper SQL file that creates the database and user
cat > "${OUTPUT_DIR}/database/README.md" << 'MDEOF'
# Database Setup for cPanel

## Option A: phpMyAdmin Import (No SSH)

1. Go to cPanel → MySQL Databases
2. Create three databases:
   - `username_dev_db`
   - `username_production_db`
   - `username_review_db`
3. Create database users and assign them to the respective databases
4. Go to phpMyAdmin, select each database, and Import the SQL file

## Option B: SSH/Terminal

```bash
# Create databases (replace username)
mysql -u username -p -e "CREATE DATABASE username_dev_db;"
mysql -u username -p -e "CREATE DATABASE username_production_db;"
mysql -u username -p -e "CREATE DATABASE username_review_db;"

# Import schema
mysql -u username -p username_dev_db < schema.sql
mysql -u username -p username_production_db < schema.sql
mysql -u username -p username_review_db < schema.sql

# Or run migrations
cd /home/username/dev_app && php bin/cake.php migrations migrate
cd /home/username/production_app && php bin/cake.php migrations migrate
cd /home/username/review_app && php bin/cake.php migrations migrate
```
MDEOF

# ---- Step 6: Create deployment README ----
echo -e "${GREEN}[6/6] Creating deployment guide...${NC}"

cat > "${OUTPUT_DIR}/README.md" << MDEOF
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
Edit \`config/app_local.php\` in each \`*_app/\` directory and set:
- Database name, username, and password

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
MDEOF

# ---- Create ZIP archives for easy upload ----
echo ""
echo -e "${BLUE}=== Creating ZIP archives ===${NC}"

cd "${OUTPUT_DIR}"

for ENV in dev production review; do
    zip -rq "${ENV}_app.zip" "${ENV}_app/"
    echo "  Created: ${ENV}_app.zip"
done

zip -rq "public_html_all.zip" public_html_dev/ public_html_production/ public_html_review/
echo "  Created: public_html_all.zip"

cd ..

echo ""
echo -e "${GREEN}=== Done! ===${NC}"
echo ""
echo "Deployment packages are ready in: ${OUTPUT_DIR}/"
echo ""
echo "Next steps:"
echo "  1. Upload *_app.zip files to /home/${CPANEL_USER}/ and extract"
echo "  2. Upload public_html contents to public_html/{dev,production,review}/"
echo "  3. Create MySQL databases in cPanel"
echo "  4. Edit config/app_local.php in each environment with DB credentials"
echo "  5. Run migrations or import SQL via phpMyAdmin"
echo ""
