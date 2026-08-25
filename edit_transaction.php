<?php
// edit_transaction.php - Transaction Edit Handler with Audit Logging & Pop Notifications
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/security.php';

$pdo = getDBConnection();
checkAuth($pdo);
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $txId = (int)($_POST['transaction_id'] ?? 0);
    $description = sanitizeInput($_POST['description'] ?? '');
    $type = in_array($_POST['type'], ['income', 'expense']) ? $_POST['type'] : 'expense';
    $amount = (float)($_POST['amount'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d');
    $payment_method = sanitizeInput($_POST['payment_method'] ?? 'Cash');

    // Verify ownership or admin role
    $stmtCheck = $pdo->prepare("SELECT user_id, receipt_image FROM transactions WHERE id = ?");
    $stmtCheck->execute([$txId]);
    $existing = $stmtCheck->fetch();

    if ($existing && ($existing['user_id'] == $userId || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1))) {
        $receiptName = $existing['receipt_image'];

        // Replace Receipt Image if uploaded
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

        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET category_id = ?, type = ?, amount = ?, date = ?, description = ?, payment_method = ?, receipt_image = ? 
            WHERE id = ?
        ");
        $stmt->execute([$category_id, $type, $amount, $date, $description, $payment_method, $receiptName, $txId]);

        logActivity($pdo, $userId, 'EDIT_TRANSACTION', "Updated transaction #$txId ($type $amount)");
        setFlashPop('Transaction Updated! ✏️', "Transaction details updated successfully.");
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'transactions.php'));
exit;
