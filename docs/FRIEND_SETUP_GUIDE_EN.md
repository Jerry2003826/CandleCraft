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

macOS/Linux:

```bash
python3 scripts/dev-setup.py check-env
```

Windows (PowerShell):

```powershell
py .\scripts\dev-setup.py check-env
```

If this step fails, fix the reported issue first (missing command, DB connection, etc.).

## 3) One-Command Setup and Run

macOS/Linux:

```bash
python3 scripts/dev-setup.py setup-run
```

Windows (PowerShell):

```powershell
py .\scripts\dev-setup.py setup-run
```

This command will:

1. install Composer dependencies
2. create the database
3. import schema and demo seed data
4. verify the demo admin account
5. start the app server

## 4) Login

- URL: `http://localhost:8765`
- Email: `admin@candlecraft.com`
- Password: `admin123`

## Optional: Custom Database Credentials

If your MySQL user/password/port is not `root/root/3306`, pass flags:

```bash
python3 scripts/dev-setup.py setup-run --db-user <your_user> --db-pass <your_pass> --db-port <your_port>
```

Windows:

```powershell
py .\scripts\dev-setup.py setup-run --db-user <your_user> --db-pass <your_pass> --db-port <your_port>
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
