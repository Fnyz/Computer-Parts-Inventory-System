# Computer Parts Inventory Management System

A web-based inventory and purchasing management system for computer parts built with PHP and MySQL.

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Database Structure](#database-structure)
- [Folder Structure](#folder-structure)
- [Installation](#installation)
- [Usage](#usage)
- [Security](#security)
- [Changelog](#changelog)

---

## Overview

This system allows staff to manage computer parts inventory, track suppliers, create purchase transactions, and view transaction history. It includes basic user account management and a dashboard with summary counts.

---

## Features

- **Authentication** — Login/logout with session-based access
- **Account Management** — Create and edit user accounts
- **Supplier Management** — Add, view, and update suppliers
- **Product Catalog** — Add products with image upload, price, quantity, and description
- **Transaction Entry** — Build purchase orders with line items (product + supplier + quantity)
- **Payment Calculation** — Auto-calculated totals, payment, and change
- **Transaction History** — List of past transactions with detail/receipt view
- **Dashboard** — Summary counts for total products and transactions

---

## Tech Stack

| Layer    | Technology                           |
|----------|--------------------------------------|
| Backend  | PHP 7.4+ (MVC-style structure)       |
| Database | MySQL via PDO (single driver)        |
| Frontend | Bootstrap 5, jQuery 3.6, DataTables  |
| Icons    | Font Awesome                         |
| Fonts    | Google Fonts                         |

---

## Database Structure

### `user_account`
| Column           | Type    |
|------------------|---------|
| user_id          | INT PK  |
| user_fname       | VARCHAR |
| user_lname       | VARCHAR |
| user_phone       | VARCHAR |
| username         | VARCHAR |
| user_pass        | VARCHAR |
| userType         | VARCHAR |
| user_created_at  | DATETIME|
| user_updated_at  | DATETIME|

### `supplier`
| Column          | Type    |
|-----------------|---------|
| cus_id          | INT PK  |
| cus_fname       | VARCHAR |
| cus_lname       | VARCHAR |
| cus_phone       | VARCHAR |
| cus_created_at  | DATETIME|
| cus_updated_at  | DATETIME|

### `product`
| Column          | Type    |
|-----------------|---------|
| prod_id         | INT PK  |
| prod_name       | VARCHAR |
| prod_image      | VARCHAR |
| prod_price      | DECIMAL |
| prod_quan       | INT     |
| prod_desc       | TEXT    |
| prod_created_at | DATETIME|
| prod_updated_at | DATETIME|

### `trans_history`
| Column         | Type    |
|----------------|---------|
| trans_id       | INT PK  |
| prod_id        | INT FK  |
| cus_id         | INT FK  |
| user_id        | INT FK  |
| trans_code     | VARCHAR |
| sub_price      | DECIMAL |
| sub_quantity   | INT     |
| created_date   | DATETIME|

### `transactions`
| Column             | Type    |
|--------------------|---------|
| transac_id         | INT PK  |
| transac_code       | VARCHAR |
| transac_total      | DECIMAL |
| transac_quan       | INT     |
| cus_id             | INT FK  |
| transac_payment    | DECIMAL |
| transac_created_at | DATETIME|
| transac_updated_at | DATETIME|

---

## Folder Structure

```
/
├── login.php                          ← Entry point
├── logout.php                         ← Session destroy + redirect
├── config.php                         ← BASEPATH + BASE_URL constants
├── .gitignore
│
├── app/
│   ├── Controllers/
│   │   ├── TransactionController.php  ← Unified AJAX hub
│   │   ├── ProductController.php
│   │   ├── SupplierController.php
│   │   └── UserController.php
│   ├── Database/
│   │   └── Database.php               ← Single PDO connection
│   ├── Helpers/
│   │   └── csrf.php                   ← CSRF token + validation
│   └── Models/
│       ├── Product.php
│       ├── Sale.php
│       ├── Supplier.php
│       ├── TransactionModel.php
│       └── User.php
│
├── assets/
│   ├── css/app.css
│   └── js/jquery-3.6.3.min.js
│
├── views/
│   ├── layouts/
│   │   ├── header.php
│   │   ├── nav.php
│   │   └── footer.php
│   ├── dashboard.php
│   ├── accounts.php
│   ├── products.php
│   ├── suppliers.php
│   └── transactions/
│       ├── create.php
│       ├── history.php
│       ├── view.php
│       └── receipt.php
│
├── public/
│   └── index.php                      ← Optional redirect entry point
└── README.md
```

---

## Installation

### Requirements

- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+
- Web server: Apache or Nginx (or XAMPP/WAMP locally)

### Steps

1. Clone the repository into your web server's document root (e.g., `htdocs/`).
2. Create the database and tables:

```bash
mysql -u root -p < database/schema.sql
```

3. Copy `.env.example` to `.env` and fill in your database credentials. `.env` is git-ignored; never commit it.

```ini
DB_HOST=localhost
DB_PORT=3306
DB_NAME=inventorymanagementsystem
DB_USER=root
DB_PASS=your_password
```

4. Open `http://localhost/Computer-Parts-Inventory-System/login.php` and click **Set up admin account**. This button only appears while there are no accounts. The first account is always an Admin.
5. Sign in, then add Staff and Employee accounts from **Registered Accounts**.

### Upgrading an existing database

If your database was created by the **original** version of the app (it has a `customer` table instead of `supplier`), run once:

```bash
mysql -u root -p <your_database> < database/upgrade-legacy.sql
php database/hash-plaintext-passwords.php
```

The first command renames `customer` to `supplier`, adds the `userType` and `prod_image` columns, and makes the oldest account the Admin. The second stores any plain-text passwords as hashes; each password stays the same.

If you installed after the 2026-06 refactor but before the Phase 0 hotfixes, run once:

```bash
mysql -u root -p inventorymanagementsystem < database/phase0-upgrade.sql
php database/find-empty-passwords.php
```

The script lists accounts whose password was wiped by the old edit bug. An Admin should set a new password for each of them.

---

## Usage

1. **Login** with a registered account at `login.php`.
2. Use the **sidebar menu** to navigate between Accounts, Suppliers, Products, and Transactions.
3. **Add products** with name, price, quantity, description, and an image.
4. **Add suppliers** with name and contact number.
5. Go to **Transactions** to create a new purchase order — select a supplier, add product line items, enter payment, and save.
6. View past transactions under **Transaction History**.

---

## Security

All previously identified vulnerabilities have been resolved.

| # | Issue | Resolution |
|---|-------|-----------|
| 1 | Plaintext password storage | `password_hash(PASSWORD_DEFAULT)` on insert/update; `password_verify()` on login |
| 2 | No authentication guard on protected pages | Session check + redirect to `login.php` at the top of every protected page and AJAX endpoint |
| 3 | Session fixation on login | `session_regenerate_id(true)` called immediately after successful login |
| 4 | Passwords exposed in AJAX edit forms | Password field `value` attribute is always empty; never sent back to the client |
| 5 | No CSRF protection | `csrf_token()` / `csrf_validate()` applied to all state-changing POST forms and AJAX actions |
| 6 | XSS — unescaped output | All user-controlled values wrapped in `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` |
| 7 | Unrestricted file upload | MIME type validated via `finfo` + extension whitelist (jpg, png, gif, webp); random filename on save |
| 8 | `var_dump` debug output in production | Removed from all files |
| 9 | Product quantity overwrite bug | SQL changed to `prod_quan = prod_quan - :prod_quan` (decrement, not overwrite) |
| 10 | Proper logout | `logout.php` destroys session, clears cookie, then redirects |

---

## Changelog

### Phase 0 hotfixes (2026-10-22)

**Security**
- Public sign-up removed. Only the first account can be created from the login page, and it is always an Admin. After that, only an Admin can create accounts, and the server validates the role (`Admin`, `Staff`, `Employee`).
- Editing a user no longer wipes their password. A blank password field keeps the current one, and empty passwords are rejected at login.
- Users can edit only their own account; an Admin can edit anyone. Who is asking now comes from the session, not from a form field.
- Employees are blocked from recording transactions on the server, not only in the browser. The encoder on a transaction line comes from the session.
- Database credentials moved to `.env`. Connection errors and exceptions are logged instead of shown to users.

**Bugs**
- Every page in `views/` loaded `config.php` from the wrong folder and crashed after login. The include paths are fixed.
- Editing a product no longer deletes its image.
- Transaction History works under MySQL's default `ONLY_FULL_GROUP_BY` mode.
- Success and error messages are now shown at the top of each page.
- One shared database connection per request instead of one per query.

**Setup**
- Added `database/schema.sql`, `database/phase0-upgrade.sql`, `database/find-empty-passwords.php` and `.env.example`.

### Refactor (2026-06-30)

**Structure**
- Replaced flat root-level PHP files with an MVC-style layout: `app/Controllers/`, `app/Models/`, `app/Database/`, `app/Helpers/`, `views/`
- Extracted repeated nav/header/footer markup into shared `views/layouts/` includes
- Consolidated 8 scattered AJAX endpoint files into a single `TransactionController.php`
- Removed dead files: `sidebar.php`, `particles.php`, `dropModal.php`
- Standardised to a single PDO database driver (removed MySQLi `dbconnect.php`)
- Renamed models to clear names: `Cust` → `Supplier`, `Saless` → `Sale`, `transac` → `TransactionModel`, `Prod` → `Product`
- Added `config.php` for `BASEPATH` / `BASE_URL` constants
- Moved assets to `assets/css/` and `assets/js/`
- Added `.gitignore` to exclude uploaded images and OS/editor files

**Security fixes**
- Passwords hashed with `PASSWORD_DEFAULT` (bcrypt)
- Auth guard on every protected page and AJAX endpoint
- CSRF tokens on all forms and AJAX mutations
- `htmlspecialchars` on all rendered output
- File upload MIME + extension validation
- Session regeneration on login; proper session destroy on logout

---

## License

This project is for educational purposes only.
