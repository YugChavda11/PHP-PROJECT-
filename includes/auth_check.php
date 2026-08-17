<?php
// =============================================================
// includes/auth_check.php  — Redirect unauthenticated users
// =============================================================
// Include AFTER session_start() in every protected page.

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
