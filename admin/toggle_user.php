<?php
// admin/toggle_user.php — Soft-delete or reactivate a user
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

$pdo    = getDB();
$id     = (int)($_GET['id'] ?? 0);
$action = clean($_GET['action'] ?? '');

if ($id && in_array($action, ['activate', 'deactivate'], true)) {
    // Prevent admin from deactivating themselves
    if ($id === (int)$_SESSION['user_id']) {
        setFlash('danger', 'You cannot deactivate your own account.');
        header('Location: manage_users.php'); exit;
    }

    $isDeleted = $action === 'deactivate' ? 1 : 0;

    $pdo->prepare("UPDATE users SET is_deleted = ? WHERE id = ? AND role = 'user'")
        ->execute([$isDeleted, $id]);

    // Log the action
    $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_user_id) VALUES (?,?,?)")
        ->execute([(int)$_SESSION['user_id'], ucfirst($action) . 'd user account', $id]);

    $msg = $action === 'deactivate'
        ? 'User deactivated successfully. They can no longer log in.'
        : 'User reactivated successfully. They can now log in again.';

    setFlash($action === 'deactivate' ? 'warning' : 'success', $msg);
}

header('Location: manage_users.php');
exit;
