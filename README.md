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

Create the database and import schema + seed data:

```bash
mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS academy_management_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -proot academy_management_db < config/schema/academy_management_db.sql
mysql -u root -proot academy_management_db < config/schema/seed_admin.sql
```

> If your MySQL credentials are different from `root/root`, edit `config/app_local.php` lines 50-53.

### Step 3: Configure & Run

```bash
cp config/app_local.example.php config/app_local.php   # Only if app_local.php is missing
bin/cake server
```

Visit **http://localhost:8765** and login:

| Field | Value |
|-------|-------|
| Email | `admin@candlecraft.com` |
| Password | `admin123` |

## Admin Features

- **Dashboard** - Statistics cards (Total Enquiries / New / Replied) + recent messages
- **Messages** - View, reply, delete customer enquiries; filter by status
- **Students** - Full CRUD management
- **Teachers** - Full CRUD (auto-creates user account)
- **Classes** - Full CRUD with course & teacher assignment

## Database Configuration

Edit `config/app_local.php`:

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

```
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
│   ├── academy_management_db.sql  # Full database schema (14 tables)
│   └── seed_admin.sql             # Sample data + admin account
└── routes.php           # Routing config
```
