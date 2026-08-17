<?php
// =============================================================
// config/db.php  — PDO Database Connection
// =============================================================
// SETUP:
//   1. Set DB_USER and DB_PASS to match your MySQL account.
//   2. Import database.sql once via phpMyAdmin.
//   3. If MySQL runs on a non-default port, update DB_PORT.
// =============================================================

define('DB_HOST',    'localhost');   // or '127.0.0.1'
define('DB_PORT',    '3306');        // default MySQL port
define('DB_NAME',    'smart_expense_tracker');
define('DB_USER',    'root');        // ← your MySQL username
define('DB_PASS',    '');            // ← your MySQL password (blank = XAMPP default)
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB connection error: ' . $e->getMessage());
            $errMsg  = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $dsnSafe = htmlspecialchars(
                sprintf('mysql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME),
                ENT_QUOTES, 'UTF-8'
            );
            die('<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Database Error — Smart Expense Tracker</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:680px">
  <div class="card border-danger shadow">
    <div class="card-header bg-danger text-white fw-bold fs-5">
      ⚠️ Database Connection Failed
    </div>
    <div class="card-body">

      <p class="fw-semibold mb-1">MySQL Error:</p>
      <div class="alert alert-danger font-monospace small py-2">' . $errMsg . '</div>

      <p class="fw-semibold mb-1">DSN attempted:</p>
      <div class="alert alert-secondary font-monospace small py-2">' . $dsnSafe . '</div>

      <hr>
      <p class="fw-bold mb-2">How to fix — work through this list:</p>
      <ol class="small lh-lg">
        <li>
          <strong>Is MySQL running?</strong><br>
          Open your XAMPP / WAMP / Laragon control panel and click
          <em>Start</em> next to <em>MySQL</em>.
        </li>
        <li>
          <strong>Wrong password?</strong><br>
          Edit <code>config/db.php</code> and set <code>DB_PASS</code> to your MySQL root password.
          XAMPP default is an empty string <code>\'\'</code>.
        </li>
        <li>
          <strong>Database not imported?</strong><br>
          Open <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a>,
          click <strong>Import</strong> in the top menu, choose
          <code>database.sql</code> from the project root, then click <strong>Go</strong>.
        </li>
        <li>
          <strong>Wrong port?</strong><br>
          If MySQL uses a port other than <code>3306</code>,
          update <code>DB_PORT</code> in <code>config/db.php</code>.
        </li>
        <li>
          <strong>PDO MySQL driver disabled?</strong><br>
          Open <code>php.ini</code>, find <code>;extension=pdo_mysql</code>
          and remove the leading semicolon. Save, then restart Apache.
        </li>
      </ol>

      <hr>
      <p class="small text-muted mb-0">
        💡 Open
        <a href="/SmartExpenseTracker/db_test.php">db_test.php</a>
        in your browser for a one-click connection &amp; environment check.
      </p>
    </div>
  </div>
</div>
</body>
</html>');
        }
    }
    return $pdo;
}
