<?php
// admin/flag_transaction.php — Toggle is_flagged on an expense
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

$pdo    = getDB();
$id     = (int)($_GET['id']      ?? 0);
$userId = (int)($_GET['user_id'] ?? 0);
$flag   = (int)($_GET['flag']    ?? 0); // 1 = flag, 0 = unflag

if ($id && $userId && in_array($flag, [0, 1], true)) {
    $pdo->prepare("UPDATE expenses SET is_flagged = ? WHERE id = ?")
        ->execute([$flag, $id]);

    $action = $flag ? 'Flagged expense #' . $id : 'Unflagged expense #' . $id;
    $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_user_id) VALUES (?,?,?)")
        ->execute([(int)$_SESSION['user_id'], $action, $userId]);
}

header('Location: view_user_data.php?id=' . $userId);
exit;
