# PartsDesk · Computer Parts Inventory System

Inventory, purchasing and point of sale for a computer parts shop. Native PHP 8.3 (no framework) and MySQL, with a dark "motherboard" theme and a light "datasheet" theme.

![PartsDesk dashboard in dark mode: stock value, low-stock alert, 14-day sales chart, stock health by category and restock list](docs/screenshots/dashboard-dark.png)

---

## Contents

- [Screenshots](#screenshots)
- [Features](#features)
- [Tech stack](#tech-stack)
- [Run it on your computer](#run-it-on-your-computer)
- [Roles](#roles)
- [Project structure](#project-structure)
- [Tests and code checks](#tests-and-code-checks)
- [Deploying to Render + Aiven](#deploying-to-render--aiven)
- [CI/CD pipeline](#cicd-pipeline)
- [Upgrading from the old version](#upgrading-from-the-old-version)
- [Troubleshooting](#troubleshooting)
- [Changelog](#changelog)

---

## Screenshots

Taken from the app with the built-in demo data (`composer seed`). Click any image to see it full size.

<table>
  <tr>
    <td width="50%" valign="top">
      <a href="docs/screenshots/login.png"><img src="docs/screenshots/login.png" alt="Sign-in screen styled as a BIOS boot log next to the sign-in form"></a>
      <br><b>Sign in</b><br>A BIOS-style boot screen. Only an Admin creates accounts.
    </td>
    <td width="50%" valign="top">
      <a href="docs/screenshots/dashboard-light.png"><img src="docs/screenshots/dashboard-light.png" alt="Dashboard in light mode"></a>
      <br><b>Dashboard · light mode</b><br>Every page has a Light / Dark / System switch, saved per user.
    </td>
  </tr>
  <tr>
    <td width="50%" valign="top">
      <a href="docs/screenshots/products.png"><img src="docs/screenshots/products.png" alt="Products list with SKU, category, prices, quantity and stock status LEDs"></a>
      <br><b>Products</b><br>Search, category and stock filters, LED status badges, CSV export.
    </td>
    <td width="50%" valign="top">
      <a href="docs/screenshots/product-datasheet.png"><img src="docs/screenshots/product-datasheet.png" alt="Product page laid out like a manufacturer's datasheet with specifications, barcode, pricing and stock ledger"></a>
      <br><b>Product datasheet</b><br>Specs per category, Code 128 barcode, margin, and the stock ledger.
    </td>
  </tr>
  <tr>
    <td width="50%" valign="top">
      <a href="docs/screenshots/pos.png"><img src="docs/screenshots/pos.png" alt="Point of sale with a product grid on the left and the cart, total due and change on the right"></a>
      <br><b>Point of sale</b><br>Scan or search, cart, cash / GCash / card. The server recalculates every total.
    </td>
    <td width="50%" valign="top">
      <a href="docs/screenshots/purchase-order.png"><img src="docs/screenshots/purchase-order.png" alt="Purchase order ready to receive, with ordered and arrived quantities per part"></a>
      <br><b>Purchase order</b><br>Draft → Ordered → Received. Receiving a delivery adds the stock.
    </td>
  </tr>
  <tr>
    <td width="50%" valign="top">
      <a href="docs/screenshots/reports.png"><img src="docs/screenshots/reports.png" alt="Reports page with revenue, gross profit, daily chart, top products and stock valuation"></a>
      <br><b>Reports</b><br>Revenue, gross profit, top products, stock valuation. CSV and PDF export.
    </td>
    <td width="50%" valign="top">
      <table>
        <tr>
          <td valign="top"><a href="docs/screenshots/receipt.png"><img src="docs/screenshots/receipt.png" alt="Printed 80 mm sales receipt" width="230"></a><br><b>Receipt</b><br>80 mm print and PDF.</td>
          <td valign="top"><a href="docs/screenshots/phone.png"><img src="docs/screenshots/phone.png" alt="Dashboard on a phone screen" width="170"></a><br><b>Phone</b><br>Works on phones and tablets.</td>
        </tr>
      </table>
    </td>
  </tr>
</table>

---

## Features

| Area | What it does |
|---|---|
| **Products** | SKU, category, brand/model, specs per category (socket, TDP, capacity…), cost and selling price, reorder level, photo, barcode label, CSV export |
| **Stock ledger** | Every change to stock is one recorded row: opening stock, purchases received, sales, voids and adjustments, with who and why. Stock can never go below zero. |
| **Categories** | Each category has its own spec fields, shown on the product form |
| **Suppliers** | Contacts, notes, their products and purchase orders. Archived, never deleted. |
| **Purchase orders** | Draft → Ordered → Received (or Cancelled). Receiving adds stock and can update each part's cost. Partial deliveries are recorded. |
| **Point of sale** | Scan or search parts, cart, discount (Staff/Admin), cash / GCash / card, change. The server recalculates every price and locks stock, so two cashiers can't sell the same last unit. Sequential receipt numbers (`SO-2026-00042`), printable 80 mm receipt and PDF. |
| **Sales** | Filter by date, cashier, payment; void with a reason (stock goes back); CSV export |
| **Dashboard** | Stock value, units, low/out of stock, sales today, 14-day sales vs. purchases chart, stock health by category, restock list, recent activity |
| **Reports** | Revenue, gross profit and margin, sales by day, top products, by cashier and payment, stock valuation by category, low stock. CSV and PDF export. |
| **Users & security** | Admin / Staff / Employee roles, first-run Admin setup, sign-in throttling, CSRF on every form, activity log, Light / Dark / System theme per user |

---

## Tech stack

| Layer | Choice |
|---|---|
| Language | PHP 8.3+, Composer, PSR-4 (`App\` → `src/`) |
| Routing | `nikic/fast-route` behind a single entry point, `public/index.php` |
| Database | MySQL 8 / MariaDB 10.4+, PDO, migrations with `robmorgan/phinx` |
| PDFs / barcodes | `dompdf/dompdf`, `picqer/php-barcode-generator` (Code 128) |
| Logging | `monolog/monolog` (file locally, stderr on Render) |
| Front end | Plain PHP templates, one stylesheet (`public/assets/css/app.css`), a little vanilla JavaScript. No jQuery, no build step. |
| Tests & checks | PHPUnit, PHPStan, PHP-CS-Fixer, Playwright smoke tests |
| Hosting | Docker image (`php:8.3-apache`) on Render, MySQL on Aiven, images on Cloudinary |

---

## Run it on your computer

You need **PHP 8.3 or newer** with `pdo_mysql`, **Composer**, and a **MySQL/MariaDB** server. XAMPP's MySQL is fine. XAMPP's own PHP is often 7.4, so use a newer one, for example Homebrew's `php`.

```bash
composer install
cp .env.example .env          # then edit the database settings
```

For XAMPP on macOS, connect through its socket file:

```ini
DB_SOCKET=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock
DB_NAME=partsdesk             # create it first in phpMyAdmin, or use an existing one
DB_USER=root
DB_PASS=
APP_DEBUG=true                # only on your own computer
```

Create the tables, then start the app:

```bash
composer migrate              # creates or updates the tables (safe to run again)
composer serve                # http://127.0.0.1:8010
```

Open http://127.0.0.1:8010. The first visit shows **Set up**, which creates the first Admin. After that, the Admin adds accounts under **Users**.

**Demo data** (optional, only for an empty database): `composer seed` adds 3 suppliers, 20 computer parts, purchase orders and two weeks of sales. It refuses to run if products already exist.

---

## Roles

| | Admin | Staff | Employee |
|---|:-:|:-:|:-:|
| Sell at the point of sale, view sales | ✔ | ✔ | ✔ |
| View products and stock | ✔ | ✔ | ✔ |
| Give discounts, void sales | ✔ | ✔ | |
| Add/edit products, categories, suppliers; adjust stock | ✔ | ✔ | |
| Purchase orders, reports, cost and stock value | ✔ | ✔ | |
| Users and the activity log | ✔ | | |

The last active Admin can't be demoted or deactivated, and nobody can deactivate their own account.

---

## Project structure

```
public/            ← the only folder the web server exposes
  index.php        ← every request comes in here
  assets/          ← app.css, app.js, pos.js, favicon
  uploads/         ← product photos when Cloudinary isn't set
src/
  Core/            App, Router, Request/Response, View, Session, Csrf, Database, Validator, Paginator, Csv, Logger
  Auth/            Auth (sign-in), Permissions (roles), LoginThrottle
  Middleware/      auth, guest, can:<ability>
  Controllers/     one per area (Products, Pos, Sales, PurchaseOrders, Reports…)
  Services/        StockService, CheckoutService, PurchaseService, SequenceService, BarcodeService, ActivityLogger, Images/*
  Repositories/    all SQL lives here
  helpers.php      e(), money(), url(), csrf_field(), stock_status()…
config/routes.php  every URL, with the role each one needs
views/             PHP templates (layouts, partials, one folder per area)
database/
  migrations/      Phinx migrations (also import data from the old app)
  seeds/           DemoSeeder
tests/             Unit/, Feature/ (PHPUnit), smoke/ (Playwright)
docs/screenshots/  the images in this README
docker/, Dockerfile, render.yaml, .github/workflows/ci-cd.yml, scripts/
```

**House rules**
- Stock changes only through `StockService`, inside a database transaction, and always writes a ledger row.
- Never trust prices, totals or roles from the browser. The server recalculates them.
- Money is handled as integer centavos in PHP (`to_cents()`, `money()`), and stored as `DECIMAL(12,2)`.
- Escape all output with `e()`. Every POST needs `csrf_field()` (JSON requests send `X-CSRF-Token`).

---

## Tests and code checks

The tests **delete and recreate** a database called `partsdesk_test` (it must contain "test" in its name), so give them a MySQL server you don't mind them using. By default they connect to `127.0.0.1:3306` as `root` with no password; override any of these in your shell:

```bash
composer test                                        # PHPUnit: 61 unit + feature tests
DB_SOCKET=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock composer test   # XAMPP
composer stan                                        # PHPStan static analysis
composer lint                                        # code style (composer fix to apply)

# Browser smoke test against any running copy
npm ci && npx playwright install chromium
APP_URL=http://127.0.0.1:8010 SMOKE_USER=… SMOKE_PASS=… npx playwright test
APP_URL=http://127.0.0.1:8010 bash scripts/smoke.sh
```

---

## Deploying to Render + Aiven

Both have free plans with no card needed. Free tiers change, so check their pricing pages.

### 1. Database: Aiven

1. Sign up at [aiven.io](https://aiven.io/free-mysql-database) and create a **free MySQL** service.
2. From the service's **Overview** page, note the **host, port, user, password and database name**, and download the **CA certificate** (`ca.pem`).

The free service may be powered off after a long time unused. Aiven emails you first, and you can turn it back on in the console.

### 2. Images: Cloudinary

Render's free disk is wiped on every deploy and restart, so product photos must live elsewhere. Create a free [Cloudinary](https://cloudinary.com) account and copy its **API environment variable**: `cloudinary://API_KEY:API_SECRET@CLOUD_NAME`.

### 3. App: Render

1. Sign in to [render.com](https://render.com) with GitHub. Choose **New → Blueprint** and pick this repository. Render reads `render.yaml` and creates a free **Docker** web service named `partsdesk` in Singapore.
2. Fill in the environment variables it asks for:

   | Variable | Value |
   |---|---|
   | `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | From Aiven |
   | `DB_SSL_CA` | Paste the whole text of `ca.pem` |
   | `CLOUDINARY_URL` | From Cloudinary |
   | `SHOP_ADDRESS`, `SHOP_PHONE` | Printed on receipts (optional) |

3. **Auto-deploy is off** on purpose: GitHub Actions deploys only after the tests pass.
4. Note the service's **ID** (starts with `srv-`, shown in its URL) and create an **API key** (Account settings → API keys).

### 4. GitHub settings

In the repository: **Settings → Environments → New environment** named `production`, then add:

| Kind | Name | Value |
|---|---|---|
| Secret | `RENDER_API_KEY` | Render API key |
| Secret | `RENDER_SERVICE_ID` | `srv-…` |
| Secret | `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SSL_CA` | Same as Render (migrations run from GitHub) |
| Secret | `SMOKE_USER`, `SMOKE_PASS` | An **Employee** account created only for the smoke test |
| Secret (optional) | `DISCORD_WEBHOOK_URL` | Alerts when a deploy is rolled back |
| Variable | `APP_URL` | `https://<your-service>.onrender.com` |

Then **Settings → Branches → Add rule** for `main`: require the **Lint, analyse, test** and **Build Docker image** checks to pass before merging.

### 5. First deploy

Merge to `main` (or run the workflow by hand from the **Actions** tab). Once it's live, open the site, create the Admin on the **Set up** screen, then create the Employee account you put in `SMOKE_USER`/`SMOKE_PASS`.

**Optional:** add a free [UptimeRobot](https://uptimerobot.com) monitor for `https://<your-service>.onrender.com/health` every 5 minutes. It alerts you if the site goes down and stops the free instance from sleeping.

---

## CI/CD pipeline

`.github/workflows/ci-cd.yml`:

```
Pull request / push ─► Lint (PHP-CS-Fixer) ─► PHPStan ─► composer audit ─► PHPUnit on MySQL 8
                    └► Docker build + container answers /health
Push to main ───────► migrate Aiven (Phinx) ─► Render deploy of this exact commit ─► wait until live
                    ─► smoke test (curl + Playwright, retries while the free instance wakes up)
                    ─► pass ✔  |  fail ✘ roll back to the previous live deploy + alert
```

- Until the Render/Aiven secrets exist, the deploy job just leaves a notice and the pipeline stays green.
- Render's free plan has no pre-deploy step, so migrations run from GitHub before the new code goes live. Write migrations so the **currently live** code still works with them: add columns, don't rename or drop them in the same release.
- Dependabot opens weekly pull requests for Composer, npm, Actions and Docker updates.

---

## Upgrading from the old version

The migrations import the old app's data automatically the first time they run on its database:

- `user_account` → `users`: same ids and passwords. Plain-text passwords are hashed. Old roles (including the "Emoloyee" typo) are mapped; if there were no roles, the oldest account becomes the Admin.
- `supplier` or `customer` → `suppliers`: the old "last name" column was used for the address.
- `product` → `products`: SKU `LEGACY-<id>`, same price and quantity, cost 0, and an "opening stock" ledger row.

The old tables (and old transactions) stay untouched in the database for reference. After upgrading, give the imported products real SKUs, categories and cost prices.

---

## Troubleshooting

| Problem | Fix |
|---|---|
| "The system is temporarily unavailable" | The app can't reach the database. Check the `DB_*` settings, that MySQL is running, and on Aiven that the service is powered on. `/health` shows `"database": "unreachable"`. |
| XAMPP: can't connect via `127.0.0.1` | Use `DB_SOCKET=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock` instead of host/port. |
| "This form expired" | The page was open too long, or cookies are blocked. Refresh and submit again. |
| "Too many wrong passwords" | Wait 15 minutes, or an Admin resets the password under Users. |
| Product photos disappear on Render | `CLOUDINARY_URL` isn't set. Without it, uploads go to Render's temporary disk. |
| First page on Render is slow | The free instance sleeps after 15 minutes idle and takes up to a minute to wake. UptimeRobot keeps it awake. |
| Error details | Locally set `APP_DEBUG=true` and see `storage/logs/app.log`. On Render, check the service's **Logs** tab. |

---

## Changelog

### 2026-10-22 · Rebuild (Phases 1–5)

- **Phase 1 · Foundation:** Composer, front controller and router, middleware, sessions with flash messages, CSRF, validation, Phinx migrations, logging, roles, first-run setup, sign-in throttling, user management, PCB theme with Light / Dark / System.
- **Phase 2 · Automation:** Docker image, Render Blueprint, GitHub Actions CI/CD with migrations, deploy, smoke tests and automatic rollback; Cloudinary image storage; Dependabot.
- **Phase 3 · Inventory:** categories with spec fields, products, suppliers, stock ledger and adjustments, CSV exports, import of old data.
- **Phase 4 · Purchasing & POS:** purchase orders, point of sale with server-side totals and row locking, sequential receipt numbers, receipts (HTML + PDF), voids.
- **Phase 5 · Insight:** dashboard, reports with CSV/PDF export, activity log, barcode labels, demo data seeder.
- The old pages (`login.php`, `views/*.php`, `app/`) were removed; everything they did is in the new app.

### 2026-10-22 · Phase 0 hotfixes

Removed public sign-up, fixed the password wipe on user edit, enforced per-user edit rights and Employee restrictions on the server, moved credentials to `.env`, fixed the broken include paths, product image loss and the `ONLY_FULL_GROUP_BY` crash.

### 2026-06-30 · First refactor

MVC-style folders, single PDO driver, password hashing, CSRF tokens, output escaping, upload validation.

---

This project is for educational purposes.
