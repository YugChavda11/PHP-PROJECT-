<?php
// delete_transaction.php - Delete Transaction Handler with Audit Logging & Pop Notifications
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/security.php';

$pdo = getDBConnection();
checkAuth($pdo);
$userId = $_SESSION['user_id'];

$txId = (int)($_GET['id'] ?? 0);

if ($txId > 0) {
    // Verify ownership or admin role
    $stmtCheck = $pdo->prepare("SELECT user_id, receipt_image FROM transactions WHERE id = ?");
    $stmtCheck->execute([$txId]);
    $existing = $stmtCheck->fetch();

    if ($existing && ($existing['user_id'] == $userId || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1))) {
        // Delete receipt file if exists
        if ($existing['receipt_image']) {
            $filePath = __DIR__ . '/uploads/' . $existing['receipt_image'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
        $stmt->execute([$txId]);

        logActivity($pdo, $userId, 'DELETE_TRANSACTION', "Deleted transaction #$txId");
        setFlashPop('Transaction Deleted! 🗑️', "Transaction was deleted successfully.", 'warning');
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'transactions.php'));
exit;
