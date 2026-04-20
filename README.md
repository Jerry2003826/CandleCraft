# CandleCraft Academy - Admin Management System

Pottery and knitting tutoring business management platform built with CakePHP 5.

## Requirements

- PHP >= 8.2
- MySQL 8.0+ / MariaDB
- Composer

## Quick Start (3 Steps)

### Step 1: Install Dependencies

```bash
composer install
```

### Step 2: Setup Database

Create the database, run migrations, and seed the local demo admin:

```bash
mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS academy_management_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cp config/app_local.example.php config/app_local.php   # Only if composer did not create it for you
composer run-script post-install-cmd --no-interaction
bin/cake migrations migrate
ADMIN_SEED_PASSWORD=admin123 bin/cake seeds run AdminSeed -q
```

PowerShell:

```powershell
Copy-Item config/app_local.example.php config/app_local.php -ErrorAction SilentlyContinue
composer run-script post-install-cmd --no-interaction
bin/cake migrations migrate
$env:ADMIN_SEED_PASSWORD = "admin123"
bin/cake seeds run AdminSeed -q
```

> `config/schema/academy_management_db.sql` and `config/schema/seed_admin.sql` are kept as legacy reference files only. New environments should use migrations + seeds. The legacy `payments` reference is still maintained to match the current state machine, including zero-amount bookings being confirmed locally without Stripe.

### Step 3: Configure & Run

```bash
bin/cake server
```

Visit [http://localhost:8765](http://localhost:8765) and login:

| Field    | Value                   |
|:---------|:------------------------|
| Email    | `admin@candlecraft.com` |
| Password | `admin123` (local demo only) |

Change seeded credentials outside local development. Production deployments must provide a unique `SECURITY_SALT`, database password, Stripe secrets, reCAPTCHA secrets, and a non-default admin password.

## Deployment

For full production and shared-hosting deployment instructions, see:

- [`docs/DEPLOYMENT_GUIDE_EN.md`](docs/DEPLOYMENT_GUIDE_EN.md)

This guide covers:

- standard Linux / VPS deployment with `scripts/server-deploy.sh`
- cPanel deployment with `scripts/deploy-oneclick.sh` or `scripts/cpanel-deploy.sh`
- post-deployment verification and troubleshooting

## One-Click Setup (Auto Detect OS)

Use one cross-platform script for both macOS and Windows (also works on Linux).

### Super easy mode (single command)

Just run:

```bash
python3 scripts/easy-run.py
```

Windows:

```powershell
py .\scripts\easy-run.py
```

It will guide the teammate to configure DB profile and then automatically run `check-env` + `setup-run`.
It also supports one-click auto-auth detection for common local MySQL/XAMPP credentials.

### Team setup (recommended for multiple classmates)

Generate a local DB profile interactively:

```bash
python3 scripts/onboard-teammate.py
```

Then run with your profile name:

```bash
python3 scripts/dev-setup.py check-env --profile <your_profile>
python3 scripts/dev-setup.py setup-run --profile <your_profile>
```

Windows:

```powershell
py .\scripts\onboard-teammate.py
py .\scripts\dev-setup.py check-env --profile <your_profile>
py .\scripts\dev-setup.py setup-run --profile <your_profile>
```

### 1) Environment pre-check (recommended)

```bash
python3 scripts/dev-setup.py check-env
```

Windows:

```powershell
py .\scripts\dev-setup.py check-env
```

### 2) Full setup + run

```bash
python3 scripts/dev-setup.py setup-run
```

Windows:

```powershell
py .\scripts\dev-setup.py setup-run
```

Optional custom DB settings:

```bash
python3 scripts/dev-setup.py setup-run --db-user myuser --db-pass mypass --db-port 3307
```

Profile + override example (CLI has highest priority):

```bash
python3 scripts/dev-setup.py setup-run --profile alice --db-pass "newpass"
```

If `mysql` is not in PATH (for example, XAMPP), provide full client path:

```bash
python3 scripts/dev-setup.py setup-run --mysql-cmd "C:\xampp\mysql\bin\mysql.exe" --db-user root --db-pass ""
```

## Admin Features

- **Dashboard** - Statistics cards (Total Enquiries / New / Replied) + recent messages
- **Messages** - View, reply, delete customer enquiries; filter by status
- **Students** - Full CRUD management
- **Teachers** - Full CRUD (auto-creates user account)
- **Classes** - Full CRUD with course & teacher assignment

## Database Configuration

Edit the generated `config/app_local.php`:

```php
'Datasources' => [
    'default' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => 'root',
        'database' => 'academy_management_db',
    ],
],
```

## Project Structure

```text
src/
├── Controller/
│   ├── Admin/           # Admin prefix controllers
│   │   ├── AppController.php      # Auth check + admin layout
│   │   ├── DashboardController.php
│   │   ├── MessagesController.php
│   │   ├── StudentsController.php
│   │   ├── TeachersController.php
│   │   └── ClassesController.php
│   ├── UsersController.php        # Login / Logout
│   └── AppController.php
├── Model/
│   ├── Entity/          # 10 entity classes
│   └── Table/           # 10 table classes with associations
templates/
├── Admin/               # Admin page templates
├── Users/login.php      # Login page
├── layout/
│   ├── admin.php        # Sidebar layout
│   └── login.php        # Login layout
config/
├── schema/
│   ├── academy_management_db.sql  # Legacy schema reference
│   └── seed_admin.sql             # Legacy sample seed reference
├── Migrations/                    # Source of truth for schema changes
├── Seeds/                         # Source of truth for local seed data
└── routes.php                     # Routing config
```
