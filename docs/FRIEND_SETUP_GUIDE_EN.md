# CandleCraft Academy Setup Guide (For Teammates)

This guide helps you run the project from scratch after pulling the latest code.

## Prerequisites

- PHP 8.2+
- Composer
- MySQL 8.0+ (or MariaDB)
- Python 3

## 1) Pull Latest Code

```bash
git pull
```

## 2) Run Environment Check (Recommended)

If you are in a class/team setup, first create your local DB profile:

```bash
python3 scripts/onboard-teammate.py
```

Windows:

```powershell
py .\scripts\onboard-teammate.py
```

macOS/Linux:

```bash
python3 scripts/dev-setup.py check-env
```

Windows (PowerShell):

```powershell
py .\scripts\dev-setup.py check-env
```

If this step fails, fix the reported issue first (missing command, DB connection, etc.).

Using profile:

```bash
python3 scripts/dev-setup.py check-env --profile <your_profile>
```

## 3) One-Command Setup and Run

macOS/Linux:

```bash
python3 scripts/dev-setup.py setup-run
```

Windows (PowerShell):

```powershell
py .\scripts\dev-setup.py setup-run
```

Using profile:

```powershell
py .\scripts\dev-setup.py setup-run --profile <your_profile>
```

This command will:

1. install Composer dependencies
2. create the database
3. run migrations and seed the local demo admin
4. verify the demo admin account
5. start the app server

## 4) Login

- URL: `http://localhost:8765`
- Email: `admin@candlecraft.com`
- Password: `admin123` (local demo default from `ADMIN_SEED_PASSWORD`)

This project applies the base schema snapshot for empty databases, then uses CakePHP migrations and seeds for the current application state.

## 5) Optional Full Demo Accounts

If you need the full demo dataset instead of only the admin account, run:

```bash
DEMO_SEED_PASSWORD=admin123 php bin/cake.php seeds run DemoDataSeed
```

If you already created partial or broken local data and want to replace it:

```bash
DEMO_SEED_PASSWORD=admin123 DEMO_SEED_RESET_EXISTING=true php bin/cake.php seeds run DemoDataSeed
```

Demo logins:

- `admin@candlecraft.com`
- `emma.clay@candlecraft.com`
- `james.knit@candlecraft.com`
- `alice.wong@candlecraft.com`
- `ava.park@candlecraft.com`
- `olivia.lee@candlecraft.com`

Default password for all demo accounts:

```text
admin123
```

## Optional: Custom Database Credentials

If your MySQL user/password/port is not `root/root/3306`, pass flags:

```bash
python3 scripts/dev-setup.py setup-run --db-user <your_user> --db-pass <your_pass> --db-port <your_port>
```

Windows:

```powershell
py .\scripts\dev-setup.py setup-run --db-user <your_user> --db-pass <your_pass> --db-port <your_port>
```

If `mysql` is not in PATH (common with XAMPP), pass the full path:

```powershell
py .\scripts\dev-setup.py setup-run --mysql-cmd "C:\xampp\mysql\bin\mysql.exe" --db-user root --db-pass ""
```

## Quick Troubleshooting

Check that demo admin exists:

```bash
mysql -u root -proot -D academy_management_db -e "SELECT user_id,email,user_role,account_status FROM users WHERE email='admin@candlecraft.com';"
```

If login still fails, send these outputs to the project owner:

1. `python3 scripts/dev-setup.py check-env`
2. the SQL query output above
3. your `DATABASE_URL` environment variable (if set)
