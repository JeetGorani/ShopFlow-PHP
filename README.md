# ShopFlow Core — PHP + MySQL edition

Open-source clothing inventory and order dashboard built for XAMPP. No Maven, Java runtime, paid API, Composer, or external service is required.

## Features
- Dashboard totals: product-size variants, units in stock, low-stock variants, orders, and sales total.
- Inventory by clothing size, product search, add product, and restock.
- Orders with customer details, multiple line items, automatic stock deduction, and printable invoices.
- MySQL storage using PDO prepared statements and database transactions.
- Responsive dashboard adapted from the original ShopFlow Core UI.

## Run with XAMPP (Windows)
1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this entire `ShopFlow-PHP` folder into `C:\xampp\htdocs\`.
3. Open `http://localhost/phpmyadmin`.
4. Choose **Import**, select `database/schema.sql`, and click **Import** / **Go**. This creates the `shopflow` database and adds sample products.
5. If your MySQL root password is not blank, edit `DB_PASS` in `config.php`.
6. Open `http://localhost/ShopFlow-PHP/` in your browser.

## Important notes
- Product name + size must be unique. Use **Restock** to add stock to an existing size.
- Orders save their item names, sizes, quantities, and prices as an invoice snapshot. Stock deduction and order creation run in one database transaction.
- Use the invoice window's **Print / Save as PDF** button and choose “Save as PDF” in the browser print dialog.
- This is a local MVP, not ready for public internet deployment. Before hosting it publicly, add authentication/roles, CSRF protection, rate limiting, backups, and deployment hardening.

## Stack
PHP 8+ (PDO MySQL), MySQL/MariaDB, HTML, CSS, JavaScript. MIT License.
