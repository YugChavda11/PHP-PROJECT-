<?php
// =============================================================
// includes/admin_auth.php  — Admin session guard
// =============================================================
// Include at the top of EVERY file inside /admin/ (after session_start).
// Redirects to admin login if user is not logged in as admin.

if (
    empty($_SESSION['user_id']) ||
    empty($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header('Location: login.php');
    exit;
}
