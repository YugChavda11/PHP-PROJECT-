<?php
// test_mysql.php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
    echo "MySQL Connected Successfully!\n";
} catch (Exception $e) {
    echo "MySQL Connection Error: " . $e->getMessage() . "\n";
}
