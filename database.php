<?php
// config/database.php - MySQL Primary Database Configuration with SQLite Fallback & Audit Logs

define('DB_DRIVER', 'mysql'); // 'mysql' or 'sqlite'
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'smart_expense');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SQLITE_PATH', __DIR__ . '/../db/expense_tracker.sqlite');

function getDBConnection() {
    static $activeDriver = null;

    if (DB_DRIVER === 'mysql') {
        try {
            // First connect to MySQL server without db name to ensure DB exists
            $pdoServer = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS);
            $pdoServer->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            // Connect to smart_expense database
            $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Auto-migrate tables
            initMySQLSchema($pdo);
            $activeDriver = 'mysql';
            return $pdo;
        } catch (PDOException $e) {
            error_log("MySQL Connection Failed: " . $e->getMessage() . ". Falling back to SQLite.");
        }
    }

    // SQLite Fallback
    $dbDir = dirname(SQLITE_PATH);
    if (!file_exists($dbDir)) {
        mkdir($dbDir, 0777, true);
    }

    $isNew = !file_exists(SQLITE_PATH);
    $pdo = new PDO("sqlite:" . SQLITE_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($isNew || filesize(SQLITE_PATH) === 0) {
        initSQLiteSchema($pdo);
    } else {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN is_admin INTEGER DEFAULT 0;");
            $pdo->exec("ALTER TABLE users ADD COLUMN status TEXT DEFAULT 'active';");
        } catch (PDOException $ex) {}
    }
    $activeDriver = 'sqlite';
    return $pdo;
}

function getActiveDriver() {
    return DB_DRIVER;
}

// SQL Helper for Month Formatting across MySQL & SQLite
function getMonthSql($column = 'date') {
    return DB_DRIVER === 'mysql' ? "DATE_FORMAT($column, '%Y-%m')" : "strftime('%Y-%m', $column)";
}

function initMySQLSchema($pdo) {
    // Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        currency_symbol VARCHAR(10) DEFAULT '$',
        monthly_budget DECIMAL(10,2) DEFAULT 2000.00,
        is_admin TINYINT(1) DEFAULT 0,
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // Auto-migration check for status column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active';");
    } catch (PDOException $e) {}

    // Categories Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        name VARCHAR(255) NOT NULL,
        type ENUM('income', 'expense') NOT NULL,
        icon VARCHAR(100) DEFAULT 'tag',
        color VARCHAR(50) DEFAULT '#6366f1',
        budget_limit DECIMAL(10,2) DEFAULT 0.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // Transactions Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        category_id INT NOT NULL,
        type ENUM('income', 'expense') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        date DATE NOT NULL,
        description TEXT,
        payment_method VARCHAR(100) DEFAULT 'Cash',
        receipt_image VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // Savings Goals Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS savings_goals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        target_amount DECIMAL(10,2) NOT NULL,
        current_amount DECIMAL(10,2) DEFAULT 0.00,
        target_date DATE NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // Activity Audit Logs Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45) DEFAULT '127.0.0.1',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // Password Resets Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_email (email)
    ) ENGINE=InnoDB;");

    // Seed Categories if empty
    $count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ((int)$count === 0) {
        seedDefaultCategories($pdo);
    }
}

function initSQLiteSchema($pdo) {
    $pdo->exec("PRAGMA foreign_keys = ON;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        currency_symbol TEXT DEFAULT '$',
        monthly_budget REAL DEFAULT 2000.00,
        is_admin INTEGER DEFAULT 0,
        status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL,
        name TEXT NOT NULL,
        type TEXT CHECK(type IN ('income', 'expense')) NOT NULL,
        icon TEXT DEFAULT 'tag',
        color TEXT DEFAULT '#6366f1',
        budget_limit REAL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        category_id INTEGER NOT NULL,
        type TEXT CHECK(type IN ('income', 'expense')) NOT NULL,
        amount REAL NOT NULL,
        date DATE NOT NULL,
        description TEXT,
        payment_method TEXT DEFAULT 'Cash',
        receipt_image TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS savings_goals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        target_amount REAL NOT NULL,
        current_amount REAL DEFAULT 0,
        target_date DATE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL,
        action TEXT NOT NULL,
        details TEXT,
        ip_address TEXT DEFAULT '127.0.0.1',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        token TEXT NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    seedDefaultCategories($pdo);
}

function seedDefaultCategories($pdo) {
    $defaults = [
        ['name' => 'Salary', 'type' => 'income', 'icon' => 'briefcase', 'color' => '#10b981'],
        ['name' => 'Freelance & Side Hustle', 'type' => 'income', 'icon' => 'laptop', 'color' => '#06b6d4'],
        ['name' => 'Investments & Dividends', 'type' => 'income', 'icon' => 'trending-up', 'color' => '#8b5cf6'],
        ['name' => 'Food & Dining', 'type' => 'expense', 'icon' => 'utensils', 'color' => '#ef4444', 'budget' => 400],
        ['name' => 'Housing & Rent', 'type' => 'expense', 'icon' => 'home', 'color' => '#f59e0b', 'budget' => 800],
        ['name' => 'Transportation & Gas', 'type' => 'expense', 'icon' => 'car', 'color' => '#3b82f6', 'budget' => 200],
        ['name' => 'Utilities & Bills', 'type' => 'expense', 'icon' => 'zap', 'color' => '#ec4899', 'budget' => 150],
        ['name' => 'Shopping & Clothes', 'type' => 'expense', 'icon' => 'shopping-bag', 'color' => '#a855f7', 'budget' => 250],
        ['name' => 'Entertainment & Subscriptions', 'type' => 'expense', 'icon' => 'film', 'color' => '#14b8a6', 'budget' => 100],
        ['name' => 'Healthcare & Fitness', 'type' => 'expense', 'icon' => 'heart-pulse', 'color' => '#f43f5e', 'budget' => 100],
        ['name' => 'Education & Learning', 'type' => 'expense', 'icon' => 'book-open', 'color' => '#6366f1', 'budget' => 100]
    ];

    $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type, icon, color, budget_limit) VALUES (NULL, :name, :type, :icon, :color, :budget)");

    foreach ($defaults as $cat) {
        $stmt->execute([
            ':name' => $cat['name'],
            ':type' => $cat['type'],
            ':icon' => $cat['icon'],
            ':color' => $cat['color'],
            ':budget' => $cat['budget'] ?? 0
        ]);
    }
}
