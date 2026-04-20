# CandleCraft Deployment Guide

This guide explains how to deploy CandleCraft in production and shared-hosting environments.

It covers:

- standard Linux or VPS deployment
- cPanel / shared-hosting deployment
- the server-side deployment script in `scripts/server-deploy.sh`
- post-deployment checks
- common deployment issues

## 1. Choose the Right Deployment Path

Use the option that matches your hosting setup.

### Option A. Standard Linux / VPS Server

Use this when:

- you have SSH access to the server
- your web server can point directly to the app `webroot/`
- you want to deploy from the repository already present on the server

Recommended tool:

- `scripts/server-deploy.sh`

### Option B. cPanel / Shared Hosting With Public Subdirectories

Use this when:

- the application code lives outside `public_html`
- public files must be copied into `public_html/dev`, `public_html/production`, or `public_html/review`
- the app is served from a subdirectory such as `/production`

Recommended tools:

- `scripts/deploy-oneclick.sh`
- `scripts/cpanel-deploy.sh`

### Option C. Routine Server Update After Initial Setup

Use this when:

- the app has already been installed on the server
- the document root and public files are already configured
- you only need to pull the latest code, update dependencies, regenerate `app_local.php`, and run migrations

Recommended tool:

- `scripts/server-deploy.sh`

## 2. Requirements

Before deploying, make sure the target environment has:

- PHP 8.2 or newer
- Composer
- MySQL 8.0+ or MariaDB
- a writable `tmp/`, `logs/`, and `storage/` directory
- a configured virtual host, Apache site, or cPanel public directory
- a database and database user with permission to run migrations

If Stripe payments will be used in production, also prepare:

- `STRIPE_SECRET_KEY`
- `STRIPE_PUBLISHABLE_KEY`
- `STRIPE_WEBHOOK_SECRET`

If the public contact form will use reCAPTCHA in production, also prepare:

- `RECAPTCHA_SITE_KEY`
- `RECAPTCHA_SECRET_KEY`

## 3. Important Deployment Concepts

### 3.1 Migrations Are the Source of Truth

Do not rely on manually edited legacy tables or stale hand-maintained SQL dumps for current production schema.

For brand-new empty databases, the deployment scripts bootstrap from `config/schema/academy_management_db.sql` first, then apply the current migrations on top.

For current versions of the app, schema updates should be applied with:

```bash
php bin/cake.php migrations migrate
```

This matters especially for recent changes such as:

- `users.age_verified_by_admin`
- `students.declared_age`
- `payment_webhook_incidents`
- `stripe_webhook_events`

### 3.2 `App.fullBaseUrl` Is Required in Production

In production, this application enforces host validation through `HostHeaderMiddleware`.

That means `APP_FULL_BASE_URL` or `App.fullBaseUrl` must be set correctly, otherwise requests can fail with a host-header security error.

Examples:

- root-domain deployment: `https://example.com`
- subdirectory deployment: `https://example.com`

For subdirectory deployments, the host must still be correct, and the application base path must also be handled by your web-server or cPanel packaging flow.

### 3.3 Root vs Subdirectory Deployments

If your site runs directly from a domain root and the web server points to `webroot/`, use `scripts/server-deploy.sh`.

If your site runs from a subdirectory like `/production`, use the cPanel packaging flow because it prepares the public folder structure and base path handling for shared hosting.

## 4. Standard Linux / VPS Deployment

This is the recommended path for a normal SSH-access server.

### 4.1 Prepare the Server

Upload or clone the repository onto the server.

Example:

```bash
git clone <your-repository-url> candlecraft
cd candlecraft
```

Or if the repository already exists:

```bash
cd /path/to/candlecraft
git pull
```

### 4.2 Point the Web Server to `webroot/`

For Apache or Nginx, the public document root should point to:

```text
<APP_DIR>/webroot
```

Do not point the public web root to the repository root unless you have intentionally built a wrapper front controller for that environment.

### 4.3 Run the Server-Side Deployment Script

From the application directory, run:

```bash
APP_DIR=/path/to/candlecraft \
APP_URL=https://example.com \
DB_HOST=127.0.0.1 \
DB_PORT=3306 \
DB_NAME=academy_management_db \
DB_USER=academy_user \
DB_PASS='replace-me' \
SECURITY_SALT='replace-with-a-long-random-string' \
STRIPE_ENVIRONMENT=live \
STRIPE_SECRET_KEY='sk_live_xxx' \
STRIPE_PUBLISHABLE_KEY='pk_live_xxx' \
STRIPE_WEBHOOK_SECRET='whsec_xxx' \
RECAPTCHA_SITE_KEY='site-key' \
RECAPTCHA_SECRET_KEY='secret-key' \
bash scripts/server-deploy.sh
```

What this script does:

- generates `config/app_local.php`
- backs up the previous `config/app_local.php`
- installs production Composer dependencies
- creates required writable directories if missing
- clears temporary cache directories
- fixes file permissions for `tmp`, `logs`, and `storage`
- bootstraps the base schema snapshot if the target database is empty
- runs database migrations

### 4.4 Optional First-Time Admin Seed

For a new environment where you want a seeded admin account:

```bash
RUN_ADMIN_SEED=true \
ADMIN_SEED_PASSWORD='strong-admin-password' \
bash scripts/server-deploy.sh
```

Do not use demo credentials in a shared or public environment.

### 4.5 Optional Full Demo Data Seed

If this environment is for demo, review, marking, or teammate verification, and you want the app to include sample logins, sample students, teachers, courses, classes, bookings, and messages, run:

```bash
RUN_DEMO_DATA_SEED=true \
DEMO_SEED_PASSWORD='admin123' \
bash scripts/server-deploy.sh
```

If the database already contains partial demo rows from a previous broken setup and you want to replace them, run:

```bash
RUN_DEMO_DATA_SEED=true \
DEMO_SEED_PASSWORD='admin123' \
DEMO_SEED_RESET_EXISTING=true \
bash scripts/server-deploy.sh
```

Important notes:

- `DemoDataSeed` is for demo or test environments only
- it includes the admin account, so you do not need `RUN_ADMIN_SEED=true` at the same time
- when `DEMO_SEED_RESET_EXISTING=true`, existing application demo data is cleared and replaced

Seeded demo accounts:

- `admin@candlecraft.com` - admin
- `emma.clay@candlecraft.com` - teacher
- `james.knit@candlecraft.com` - teacher
- `alice.wong@candlecraft.com` - verified consumer/student
- `ava.park@candlecraft.com` - unverified consumer/customer
- `olivia.lee@candlecraft.com` - verified parent

Default demo password for all of the above:

```text
admin123
```

### 4.6 Optional Routine Update Command

After the initial install, a typical update flow is:

```bash
cd /path/to/candlecraft
git pull
APP_DIR="$(pwd)" \
APP_URL=https://example.com \
DB_HOST=127.0.0.1 \
DB_NAME=academy_management_db \
DB_USER=academy_user \
DB_PASS='replace-me' \
SECURITY_SALT='existing-or-new-salt' \
bash scripts/server-deploy.sh
```

## 5. cPanel / Shared Hosting Deployment

Use this when you cannot point the web root directly to `webroot/`, or when your site is hosted from subdirectories like:

- `/dev`
- `/production`
- `/review`

### 5.1 Available cPanel Deployment Scripts

#### `scripts/deploy-oneclick.sh`

Use this when you want an automated cPanel deployment with SSH upload.

Help summary:

```bash
./scripts/deploy-oneclick.sh --help
```

Main options include:

- `--host`
- `--user`
- `--port`
- `--ssh-key`
- `--db-user`
- `--db-pass`
- `--output-dir`
- `--package-only`
- `--clone-local-data`
- `--embed-secrets`

#### `scripts/cpanel-deploy.sh`

Use this when you want local packaging output for cPanel environments.

Help summary:

```bash
./scripts/cpanel-deploy.sh --help
```

Main options include:

- cPanel username
- domain
- per-environment database passwords
- local packaging output path

### 5.2 Recommended Initial cPanel Flow

If this is the first deployment to cPanel:

1. Build the deployment artifacts locally with `scripts/deploy-oneclick.sh` or `scripts/cpanel-deploy.sh`.
2. Upload the generated application directories outside `public_html`.
3. Upload the generated public files into the matching public subdirectory.
4. Ensure the environment-specific `app_local.php` is installed.
5. Run migrations on the target environment.

### 5.3 Example One-Click cPanel Deployment

Example:

```bash
./scripts/deploy-oneclick.sh \
  --host ssh.example.com \
  --user cpaneluser \
  --port 22 \
  --ssh-key ~/.ssh/id_rsa \
  --db-user cpaneluser \
  --db-pass 'db-password' \
  --stripe-sk 'sk_live_xxx' \
  --stripe-pk 'pk_live_xxx' \
  --stripe-wh 'whsec_xxx'
```

This script can:

- package app directories for `dev`, `production`, and `review`
- upload them by SSH
- install environment-specific `app_local.php`
- prepare public subdirectories
- run migrations remotely
- optionally clone local database schema or data

### 5.4 cPanel Terminal Update After Initial Install

If the app is already installed on cPanel and only needs an update:

1. open cPanel Terminal
2. change into the existing app directory, for example:

```bash
cd ~/production_app
```

3. pull the latest code:

```bash
git pull
```

4. run the server-side deployment script:

```bash
APP_DIR="$(pwd)" \
APP_URL=https://your-domain.com \
DB_HOST=localhost \
DB_NAME=your_database \
DB_USER=your_database_user \
DB_PASS='your_database_password' \
SECURITY_SALT='your-production-salt' \
bash scripts/server-deploy.sh
```

If the cPanel site is served from a subdirectory and the public structure was already prepared during the original install, this routine update flow is usually enough.

### cPanel Terminal Notes

- In many shared-hosting cPanel terminals, `uapi` commands should be run as the current account without `--user=...`. Adding `--user` can fail with a `setuids failed` error.
- Some cPanel environments require fully-prefixed MySQL names, for example `u26s1185_ccprod` instead of `ccprod`.
- If the server does not provide the `mysql` CLI client, `scripts/server-deploy.sh` will fall back to a PHP-based schema import automatically.

## 6. Environment Variables Explained

The server-side deployment script accepts these important variables:

- `APP_DIR`: path to the application directory
- `APP_URL`: public application URL
- `DB_HOST`: database host
- `DB_PORT`: database port
- `DB_NAME`: database name
- `DB_USER`: database username
- `DB_PASS`: database password
- `SECURITY_SALT`: production security salt
- `STRIPE_ENVIRONMENT`: usually `test` or `live`
- `STRIPE_SECRET_KEY`
- `STRIPE_PUBLISHABLE_KEY`
- `STRIPE_WEBHOOK_SECRET`
- `RECAPTCHA_SITE_KEY`
- `RECAPTCHA_SECRET_KEY`
- `UPLOAD_RESOURCES_ROOT`: storage path for uploaded learning resources
- `UPLOAD_RESOURCES_URL_PREFIX`: public URL prefix for resource routing
- `RUN_COMPOSER_INSTALL`: set `false` to skip Composer install
- `RUN_MIGRATIONS`: set `false` to skip migrations
- `BOOTSTRAP_BASE_SCHEMA_ON_EMPTY_DB`: set `false` only if your database is already initialized
- `RUN_ADMIN_SEED`: set `true` to create a seeded admin
- `ADMIN_SEED_PASSWORD`: required if `RUN_ADMIN_SEED=true`
- `RUN_DEMO_DATA_SEED`: set `true` to load the full demo dataset
- `DEMO_SEED_PASSWORD`: password used for all demo accounts, defaults to `admin123`
- `DEMO_SEED_RESET_EXISTING`: set `true` to replace partial or old demo data
- `APP_OWNER`: optional filesystem owner for writable directories
- `APP_GROUP`: optional filesystem group for writable directories

## 7. Post-Deployment Checklist

After deployment, verify the following:

### 7.1 Application Availability

Open the site in a browser and confirm:

- the homepage loads
- `/login` loads
- admin login succeeds
- the portal redirects users by role correctly

### 7.2 Database Schema

Run:

```bash
php bin/cake.php migrations status
```

Everything expected for the current release should show as `up`.

### 7.3 Writable Directories

Check that the app can write to:

- `tmp/`
- `logs/`
- `storage/resources/`

If not, fix ownership and permissions and re-run:

```bash
bash scripts/server-deploy.sh
```

### 7.4 Stripe Webhook Endpoint

If Stripe is enabled, confirm that the webhook endpoint is reachable at:

```text
/stripe/webhook
```

Then update the Stripe dashboard to use the correct production URL.

### 7.5 Accessibility and Portal Smoke Test

Check at least these pages:

- home page
- contact page
- login page
- admin dashboard
- consumer bookings
- parent dashboard
- teacher dashboard

This release includes accessibility fixes and webhook schema repairs, so these are especially good smoke-test pages.

## 8. Common Problems and Fixes

### Problem: `App.fullBaseUrl` / host-header error in production

Cause:

- `APP_URL` or `App.fullBaseUrl` is missing or wrong

Fix:

- set `APP_URL` correctly
- regenerate `config/app_local.php`
- re-run `bash scripts/server-deploy.sh`

### Problem: `Unknown column 'age_verified_by_admin'`

Cause:

- old database schema with new seed or new code

Fix:

```bash
php bin/cake.php migrations migrate
```

### Problem: `Could not describe columns on payment_webhook_incidents`

Cause:

- database schema drift or missing recent webhook migrations

Fix:

```bash
php bin/cake.php migrations migrate
```

This repository includes a repair migration for affected environments.

### Problem: migrations fail on a brand-new empty database

Cause:

- the environment skipped the base schema bootstrap step
- or an older deployment flow tried to run incremental migrations against a fully empty database

Fix:

- rerun the latest `scripts/server-deploy.sh`
- make sure `BOOTSTRAP_BASE_SCHEMA_ON_EMPTY_DB` is not disabled
- for manual setups, import `config/schema/academy_management_db.sql` into the target database first, then rerun migrations

### Problem: the site works, but the demo users are missing

Cause:

- `scripts/server-deploy.sh` ran migrations only
- or only `AdminSeed` was run, which creates the admin account but not the full demo dataset

Fix:

```bash
DEMO_SEED_PASSWORD='admin123' \
DEMO_SEED_RESET_EXISTING=true \
php bin/cake.php seeds run DemoDataSeed
```

Or rerun the deployment script with:

```bash
RUN_DEMO_DATA_SEED=true \
DEMO_SEED_PASSWORD='admin123' \
DEMO_SEED_RESET_EXISTING=true \
bash scripts/server-deploy.sh
```

### Problem: Stripe payments stay in demo mode

Cause:

- Stripe keys are missing
- `Payments.demo_mode` is enabled in debug mode

Fix:

- set the Stripe environment variables
- make sure production uses `debug = false`

### Problem: Uploaded resources fail to save

Cause:

- `storage/resources` is not writable

Fix:

- create the directory if missing
- fix ownership and permissions
- re-run the deployment script

### Problem: cPanel site loads, but app routing is broken under `/production`

Cause:

- wrong public directory structure
- wrong subdirectory deployment method

Fix:

- use the cPanel packaging scripts for initial setup
- make sure public files are copied into the intended subdirectory
- do not treat a subdirectory shared-hosting install like a direct `webroot/` VPS install

## 9. Security Recommendations

For production:

- use a unique `SECURITY_SALT`
- never keep the demo admin password
- store Stripe and reCAPTCHA secrets securely
- keep `debug` disabled
- limit who can read `config/app_local.php`
- back up the database before major upgrades

## 10. Recommended Deployment Summary

If you are deploying to a normal Linux server:

1. clone or update the repository on the server
2. point the public document root to `webroot/`
3. run `scripts/server-deploy.sh`
4. run smoke tests

If you need a fully populated demo environment instead of an empty production-style install:

1. deploy the code normally
2. run `scripts/server-deploy.sh` with `RUN_DEMO_DATA_SEED=true`
3. use `DEMO_SEED_RESET_EXISTING=true` only when replacing broken or partial demo data
4. sign in with one of the seeded demo accounts

If you are deploying to cPanel for the first time:

1. use `scripts/deploy-oneclick.sh` or `scripts/cpanel-deploy.sh`
2. upload both app and public artifacts to the correct places
3. run migrations
4. verify the production URL and login flow

If you are performing a routine update on an existing server:

1. pull the latest code
2. run `scripts/server-deploy.sh`
3. confirm migrations and smoke tests
