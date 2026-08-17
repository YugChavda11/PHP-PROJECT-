<?php
// =============================================================
// includes/header.php  — Bootstrap 5 navbar + <head>
// =============================================================
// Expects $pageTitle to be set before including.

if (!isset($pageTitle)) $pageTitle = SITE_NAME;
$loggedIn = !empty($_SESSION['user_id']);
$userName = $loggedIn ? e($_SESSION['user_name'] ?? 'User') : '';
$currentFile = basename($_SERVER['PHP_SELF']);
function navActive(string $file): string {
    global $currentFile;
    return $currentFile === $file ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Smart Expense Tracker — manage your income and expenses intelligently.">
  <title><?= e($pageTitle) ?> | <?= SITE_NAME ?></title>
  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Google Font: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #4f46e5;
      --primary-dark: #3730a3;
      --success: #10b981;
      --danger: #ef4444;
      --warning: #f59e0b;
      --info: #3b82f6;
      --body-bg: #f1f5f9;
      --card-bg: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --border: #e2e8f0;
    }
    body { font-family: 'Inter', sans-serif; background: var(--body-bg); color: var(--text); font-size: 0.9rem; }
    .navbar-brand { font-weight: 700; letter-spacing: -0.5px; font-size: 1.15rem; }
    .navbar { box-shadow: 0 1px 4px rgba(0,0,0,.1); }
    .card { border: 1px solid var(--border); border-radius: .75rem; }
    .card-header { background: transparent; border-bottom: 1px solid var(--border); font-weight: 600; }
    .summary-card { border-radius: .75rem; color: #fff; }
    .summary-card .amount { font-size: 1.5rem; font-weight: 700; }
    .summary-card .label  { font-size: .8rem; opacity: .85; }
    .bg-income  { background: linear-gradient(135deg, #10b981, #059669); }
    .bg-expense { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .bg-net-pos { background: linear-gradient(135deg, #4f46e5, #3730a3); }
    .bg-net-neg { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .bg-ytd     { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
    .table th   { font-weight: 600; white-space: nowrap; font-size: .82rem; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
    .badge-category { font-size: .75rem; padding: .3em .65em; border-radius: .4rem; }
    .page-title { font-size: 1.3rem; font-weight: 700; color: var(--text); }
    .btn-primary { background-color: var(--primary); border-color: var(--primary); }
    .btn-primary:hover { background-color: var(--primary-dark); border-color: var(--primary-dark); }
    .filter-form .form-control, .filter-form .form-select { font-size: .85rem; }
    .section-divider { border-top: 2px solid var(--border); margin: 1.5rem 0; }
    .wa-btn { background-color: #25D366; border-color: #25D366; color: #fff; font-weight: 600; }
    .wa-btn:hover { background-color: #1da851; border-color: #1da851; color: #fff; }
    .progress-thin { height: .4rem; border-radius: 1rem; }
    @media (max-width: 576px) {
      .summary-card .amount { font-size: 1.1rem; }
      .table-responsive { font-size: .8rem; }
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color:var(--primary)">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">
      <i class="bi bi-wallet2 me-1"></i><?= SITE_NAME ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <?php if ($loggedIn): ?>
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link<?= navActive('dashboard.php') ?>" href="dashboard.php">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle<?= in_array($currentFile, ['income_list.php','income_add.php']) ? ' active' : '' ?>"
             href="#" data-bs-toggle="dropdown">
            <i class="bi bi-arrow-down-circle me-1"></i>Income
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="income_list.php"><i class="bi bi-list-ul me-2"></i>All Income</a></li>
            <li><a class="dropdown-item" href="income_add.php"><i class="bi bi-plus-circle me-2"></i>Add Income</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle<?= in_array($currentFile, ['expense_list.php','expense_add.php']) ? ' active' : '' ?>"
             href="#" data-bs-toggle="dropdown">
            <i class="bi bi-arrow-up-circle me-1"></i>Expenses
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="expense_list.php"><i class="bi bi-list-ul me-2"></i>All Expenses</a></li>
            <li><a class="dropdown-item" href="expense_add.php"><i class="bi bi-plus-circle me-2"></i>Add Expense</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle<?= in_array($currentFile, ['analysis_income.php','analysis_expense.php']) ? ' active' : '' ?>"
             href="#" data-bs-toggle="dropdown">
            <i class="bi bi-bar-chart me-1"></i>Analysis
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="analysis_income.php"><i class="bi bi-graph-up me-2"></i>Income Analysis</a></li>
            <li><a class="dropdown-item" href="analysis_expense.php"><i class="bi bi-graph-down me-2"></i>Expense Analysis</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle<?= in_array($currentFile, ['reports.php','report_export.php','whatsapp_share.php']) ? ' active' : '' ?>"
             href="#" data-bs-toggle="dropdown">
            <i class="bi bi-file-earmark-bar-graph me-1"></i>Reports
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="reports.php"><i class="bi bi-table me-2"></i>Reports</a></li>
            <li><a class="dropdown-item" href="whatsapp_share.php"><i class="bi bi-whatsapp me-2"></i>WhatsApp Share</a></li>
          </ul>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-1"></i><?= $userName ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </li>
      </ul>
      <?php else: ?>
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div class="container-fluid py-3 px-3 px-md-4">
<!-- Bootstrap 5 JS Bundle (for navbar collapse & dropdowns — CSS-only fallback would break mobile nav) -->
<!-- NOTE: This is only Bootstrap's own bundle, loaded for navbar toggling. No custom JS used anywhere. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
