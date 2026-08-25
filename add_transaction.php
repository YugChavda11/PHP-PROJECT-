<?php
// add_transaction.php - Transaction Creation Handler with Audit Logging & Pop Notifications
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/security.php';

$pdo = getDBConnection();
checkAuth($pdo);
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = sanitizeInput($_POST['description'] ?? '');
    $type = in_array($_POST['type'], ['income', 'expense']) ? $_POST['type'] : 'expense';
    $amount = (float)($_POST['amount'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d');
    $payment_method = sanitizeInput($_POST['payment_method'] ?? 'Cash');

    // Receipt Image Upload Handler
    $receiptName = null;
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['receipt_image']['tmp_name'];
        $fileName = $_FILES['receipt_image']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'webp'];
        if (in_array($fileExt, $allowedExts)) {
            $uploadsDir = __DIR__ . '/uploads';
            if (!file_exists($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
            }
            $receiptName = uniqid('receipt_') . '.' . $fileExt;
            move_uploaded_file($fileTmp, $uploadsDir . '/' . $receiptName);
        }
    }

    if ($amount > 0 && $category_id > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, category_id, type, amount, date, description, payment_method, receipt_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $category_id, $type, $amount, $date, $description, $payment_method, $receiptName]);

        logActivity($pdo, $userId, 'ADD_TRANSACTION', "Added $type of $amount ($description)");
        setFlashPop('Transaction Saved! 💸', "Successfully recorded $type of $amount.");
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
