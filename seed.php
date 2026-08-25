<?php
// seed.php - Seed initial admin and demo user into MySQL or SQLite
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();
echo "DB Connected successfully using driver: " . getActiveDriver() . "\n";

// 1. Seed / Ensure Admin Account exists
$stmtAdmin = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmtAdmin->execute(['admin@example.com']);
$adminUser = $stmtAdmin->fetch();

if (!$adminUser) {
    $hash = password_hash('password123', PASSWORD_BCRYPT);
    $stmtInsAdmin = $pdo->prepare("INSERT INTO users (name, email, password_hash, currency_symbol, monthly_budget, is_admin) VALUES (?, ?, ?, ?, ?, 1)");
    $stmtInsAdmin->execute(['System Administrator', 'admin@example.com', $hash, '$', 5000]);
    echo "Created Admin User: admin@example.com (Password: password123)\n";
} else {
    $pdo->prepare("UPDATE users SET is_admin = 1 WHERE email = 'admin@example.com'")->execute();
    echo "Admin user verified.\n";
}

// 2. Seed Demo User
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute(['demo@example.com']);
$user = $stmt->fetch();

if (!$user) {
    $hash = password_hash('password123', PASSWORD_BCRYPT);
    $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, currency_symbol, monthly_budget) VALUES (?, ?, ?, ?, ?)");
    $stmtUser->execute(['Alex Morgan', 'demo@example.com', $hash, '$', 3000]);
    $userId = $pdo->lastInsertId();
    echo "Created demo user ID: $userId\n";

    $sampleTx = [
        ['type' => 'income', 'cat' => 1, 'amount' => 4500, 'date' => date('Y-m-01'), 'desc' => 'Monthly Salary', 'method' => 'Bank Transfer'],
        ['type' => 'income', 'cat' => 2, 'amount' => 850, 'date' => date('Y-m-05'), 'desc' => 'Upwork Web Project', 'method' => 'Bank Transfer'],
        ['type' => 'expense', 'cat' => 4, 'amount' => 85.50, 'date' => date('Y-m-02'), 'desc' => 'Whole Foods Grocery', 'method' => 'Credit Card'],
        ['type' => 'expense', 'cat' => 4, 'amount' => 24.00, 'date' => date('Y-m-06'), 'desc' => 'Starbucks Coffee', 'method' => 'Debit Card'],
        ['type' => 'expense', 'cat' => 5, 'amount' => 950.00, 'date' => date('Y-m-01'), 'desc' => 'Monthly Apartment Rent', 'method' => 'Bank Transfer'],
        ['type' => 'expense', 'cat' => 6, 'amount' => 45.00, 'date' => date('Y-m-04'), 'desc' => 'Uber Trip to Airport', 'method' => 'UPI / Online'],
        ['type' => 'expense', 'cat' => 7, 'amount' => 120.00, 'date' => date('Y-m-03'), 'desc' => 'Electricity & Wifi Bill', 'method' => 'Bank Transfer'],
        ['type' => 'expense', 'cat' => 8, 'amount' => 149.99, 'date' => date('Y-m-07'), 'desc' => 'Zara Summer Jacket', 'method' => 'Credit Card'],
        ['type' => 'expense', 'cat' => 9, 'amount' => 15.99, 'date' => date('Y-m-08'), 'desc' => 'Netflix Subscription', 'method' => 'Credit Card']
    ];

    $insertTx = $pdo->prepare("INSERT INTO transactions (user_id, category_id, type, amount, date, description, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($sampleTx as $t) {
        $insertTx->execute([$userId, $t['cat'], $t['type'], $t['amount'], $t['date'], $t['desc'], $t['method']]);
    }

    $pdo->prepare("INSERT INTO savings_goals (user_id, title, target_amount, current_amount, target_date) VALUES (?, ?, ?, ?, ?)")
        ->execute([$userId, 'Vacation in Tokyo', 4000, 1850, date('Y-12-31')]);

    echo "Sample data seeded successfully into MySQL!\n";
} else {
    echo "Demo user already exists.\n";
}
