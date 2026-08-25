# 🚀 Smart Expense Tracker - XAMPP / WAMP / Laragon Setup Guide

Smart Expense Tracker is fully compatible with **XAMPP**, **WAMP**, and **Laragon** local web server environments on Windows, as well as standalone PHP CLI servers.

---

## ⚡ Method 1: Instant 1-Click Multi-Stack Launcher (Recommended)

No manual Apache configuration or folder moving required!

1. Double-click **`run.bat`** in the project directory.
2. `run.bat` automatically detects whether **XAMPP**, **WAMP**, **Laragon**, or **System PHP** is installed.
3. It starts the MySQL database server automatically if it isn't running already.
4. The app launches at **`http://127.0.0.1:8000`**.

To reset or re-seed default categories, admin (`admin@example.com` / `password123`) & demo data (`demo@example.com` / `password123`), run **`setup_database.bat`**.

---

## 📦 Method 2: Running directly inside XAMPP

### Folder Location:
Copy the project folder to your XAMPP `htdocs` directory:
`C:\xampp\htdocs\smart-expense`

### Setup Steps:
1. Open **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Open **phpMyAdmin** at `http://localhost/phpmyadmin`.
3. Create a database named `smart_expense` (utf8mb4_unicode_ci).
4. *(Optional)* Import `db/schema_mysql.sql` into the database. (Note: `config/database.php` will also automatically migrate tables on first visit if empty).
5. Access your web app in the browser:
   👉 **`http://localhost/smart-expense/`**

---

## 🐘 Method 3: Running directly inside WampServer (WAMP)

### Folder Location:
Copy the project folder to your WAMP `www` directory:
`C:\wamp64\www\smart-expense` (or `C:\wamp\www\smart-expense`)

### Setup Steps:
1. Start **WampServer** (ensure the tray icon turns green).
2. Click the WAMP tray icon -> **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Create a database named `smart_expense`.
4. Ensure Apache modules `mod_rewrite` and `mod_headers` are enabled (enabled by default).
5. Access your web app in the browser:
   👉 **`http://localhost/smart-expense/`**

---

## 🐉 Method 4: Running directly inside Laragon

Laragon provides automatic Virtual Hosts for clean URLs like `http://smart-expense.test`.

### Folder Location:
Copy the project folder to your Laragon `www` directory:
`C:\laragon\www\smart-expense`

### Setup Steps:
1. Open **Laragon** and click **Start All** (Apache/Nginx & MySQL).
2. Laragon automatically creates a virtual host for you!
3. Open Laragon -> **Database** (HeidiSQL or phpMyAdmin) and verify/create `smart_expense`.
4. Access your web app in the browser:
   👉 **`http://smart-expense.test/`** or **`http://localhost/smart-expense/`**

---

## ⚙️ Configuration Options (`config/database.php`)

Default connection settings:
```php
define('DB_DRIVER', 'mysql'); // 'mysql' or 'sqlite'
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'smart_expense');
define('DB_USER', 'root');
define('DB_PASS', '');
```

*Note: Smart Expense includes an **automatic SQLite fallback**. If MySQL is turned off or not configured, the app will seamlessly run using SQLite at `db/expense_tracker.sqlite` without throwing errors!*
