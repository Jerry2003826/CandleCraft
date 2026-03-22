#!/usr/bin/env bash
set -euo pipefail

# One-click setup + run script for macOS/Linux.
# Usage:
#   ./scripts/setup-and-run.sh
#   DB_USER=myuser DB_PASS=mypass DB_PORT=3307 ./scripts/setup-and-run.sh

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-root}"
DB_NAME="${DB_NAME:-academy_management_db}"
APP_HOST="${APP_HOST:-0.0.0.0}"
APP_PORT="${APP_PORT:-8765}"

require_cmd() {
    if ! command -v "$1" >/dev/null 2>&1; then
        echo "Error: required command not found: $1"
        exit 1
    fi
}

echo "== Checking required tools =="
require_cmd php
require_cmd composer
require_cmd mysql

if [ ! -f "composer.json" ]; then
    echo "Error: please run this script from project root (composer.json not found)."
    exit 1
fi

if command -v brew >/dev/null 2>&1; then
    echo "== Starting MySQL service via Homebrew (best effort) =="
    brew services start mysql >/dev/null 2>&1 || true
fi

echo "== Waiting for MySQL to be reachable =="
for i in {1..20}; do
    if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1;" >/dev/null 2>&1; then
        break
    fi
    if [ "$i" -eq 20 ]; then
        echo "Error: cannot connect to MySQL at ${DB_HOST}:${DB_PORT} with provided credentials."
        exit 1
    fi
    sleep 1
done

echo "== Installing dependencies =="
composer install

echo "== Creating database and importing schema/data =="
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" \
    -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < config/schema/academy_management_db.sql
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < config/schema/seed_admin.sql

echo "== Ensuring local app config exists =="
if [ ! -f "config/app_local.php" ]; then
    cp config/app_local.example.php config/app_local.php
fi

echo "== Verifying demo admin account exists =="
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" -D"$DB_NAME" \
    -e "SELECT user_id,email,user_role,account_status FROM users WHERE email='admin@candlecraft.com';"

echo "== Starting CakePHP server =="
echo "URL: http://localhost:${APP_PORT}"
echo "Login: admin@candlecraft.com / admin123"

# Use DATABASE_URL to guarantee runtime DB settings without manual file edits.
export DATABASE_URL="mysql://${DB_USER}:${DB_PASS}@${DB_HOST}:${DB_PORT}/${DB_NAME}?encoding=utf8mb4&timezone=UTC"
bin/cake server -H "$APP_HOST" -p "$APP_PORT"
