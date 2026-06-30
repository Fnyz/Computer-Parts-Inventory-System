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
- [Security Notes](#security-notes)
- [Known Issues & Recommendations](#known-issues--recommendations)

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

| Layer       | Technology                          |
|-------------|-------------------------------------|
| Backend     | PHP (procedural + Model classes)    |
| Database    | MySQL via PDO + MySQLi              |
| Frontend    | Bootstrap 5, jQuery 3.6, DataTables |
| Icons       | Font Awesome                        |
| Fonts       | Google Fonts                        |

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

> See [Recommended Folder Structure](#recommended-folder-structure) below for a cleaner layout.

**Current structure (flat, hard to maintain):**

```
/
├── Accounts.php
├── Dashboard.php
├── deleteTrans.php
├── dropModal.php
├── getData.php
├── getTotal.php
├── insertCus.php
├── insertProd.php
├── insertUser.php
├── log.css
├── login.php
├── op.php
├── particles.php
├── Product.php
├── server.php
├── sidebar.php
├── Supplier.php
├── Transaction.php
├── transactionRecord.php
├── Transactions.php
├── updateCus.php
├── updates.php
├── updateUser.php
├── viewTrans.php
├── Database/
│   ├── Database.php
│   └── dbconnect.php
├── Model/
│   ├── Cust.php
│   ├── Prod.php
│   ├── Saless.php
│   ├── transac.php
│   └── User.php
├── scripts/
│   └── jquary-3.6.3.js
└── images/
    └── [random-folder]/ (product images)
```

---

## Installation

### Requirements

- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+
- Web server: Apache or Nginx (or XAMPP/WAMP locally)

### Steps

1. Clone or copy the project into your web server's document root (e.g., `htdocs/`).
2. Create a MySQL database (e.g., `inventory_db`).
3. Import the database schema (SQL file not included — create tables manually using the structure above).
4. Edit `Database/Database.php` and `Database/dbconnect.php` with your DB credentials:

```php
// Database/Database.php
$host = 'localhost';
$db   = 'inventory_db';
$user = 'root';
$pass = 'your_password';
```

5. Open your browser and navigate to `http://localhost/Computer-Parts-Inventory-Management-System/login.php`.

---

## Usage

1. **Login** with a registered account at `login.php`.
2. Use the **sidebar menu** to navigate between Accounts, Suppliers, Products, and Transactions.
3. **Add products** with name, price, quantity, description, and an image.
4. **Add suppliers** with name and contact number.
5. Go to **Transactions** to create a new purchase order — select a supplier, add product line items, enter payment, and save.
6. View past transactions under **Transaction History**.

---

## Security Notes

> **This project was built for learning/academic purposes.** The following vulnerabilities exist and must be addressed before any production deployment.

| Severity | Issue |
|----------|-------|
| 🔴 High | Passwords stored and compared as **plaintext** — use `password_hash()` / `password_verify()` |
| 🔴 High | **No authentication guard** on protected pages — unauthenticated users can access all URLs directly |
| 🔴 High | Passwords **rendered in edit form fields** via AJAX response |
| 🟠 Medium | **No CSRF tokens** on any POST form or AJAX action |
| 🟠 Medium | **XSS risk** — user-controlled data echoed directly into HTML without `htmlspecialchars()` |
| 🟠 Medium | **Unrestricted file upload** — no MIME type or extension validation on product images |
| 🟡 Low | `var_dump($_POST)` debug output left in `updateCus.php` and `updates.php` |
| 🟡 Low | No session ID regeneration after login (session fixation risk) |

---

## Known Issues & Recommendations

### Code Quality

- **Mixed DB drivers** — `Database.php` uses PDO while `dbconnect.php` uses MySQLi. Standardize on PDO.
- **Logic error** — Product quantity is overwritten directly from transaction input instead of being decremented from existing stock.
- **DRY violations** — Navigation/sidebar markup is copy-pasted across every page. Extract to a shared `includes/nav.php`.
- **Duplicate jQuery/Bootstrap** — Multiple versions of the same libraries are loaded on some pages.
- **Dead files** — `sidebar.php` is empty; `particles.php` is an unintegrated placeholder.
- **Naming inconsistency** — `Saless.php`, `jquary-3.6.3.js`, `Cust.php` for supplier — rename for clarity.

### Recommended Folder Structure

```
/
├── public/                     ← Web root (only this folder exposed)
│   ├── index.php               ← Entry point / redirects to login
│   ├── login.php
│   ├── assets/
│   │   ├── css/
│   │   │   └── app.css
│   │   ├── js/
│   │   │   └── jquery-3.6.3.min.js
│   │   └── images/             ← Product images (with validation)
├── src/
│   ├── Controllers/            ← Action handlers (replaces op.php, server.php, etc.)
│   │   ├── AuthController.php
│   │   ├── ProductController.php
│   │   ├── SupplierController.php
│   │   ├── TransactionController.php
│   │   └── UserController.php
│   ├── Models/                 ← Database models
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Supplier.php
│   │   └── Transaction.php
│   ├── Database/
│   │   └── Database.php        ← Single PDO connection (remove dbconnect.php)
│   └── Helpers/
│       ├── Auth.php            ← Session guard helper
│       └── Sanitize.php        ← htmlspecialchars wrapper
├── views/                      ← HTML/PHP templates
│   ├── layouts/
│   │   ├── header.php
│   │   ├── sidebar.php
│   │   └── footer.php
│   ├── dashboard/
│   │   └── index.php
│   ├── products/
│   │   ├── index.php
│   │   └── edit.php
│   ├── suppliers/
│   │   ├── index.php
│   │   └── edit.php
│   ├── transactions/
│   │   ├── index.php
│   │   ├── create.php
│   │   └── view.php
│   └── accounts/
│       ├── index.php
│       └── edit.php
└── README.md
```

---

## License

This project is for educational purposes only.
