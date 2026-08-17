<?php
// admin/login.php — Admin-only login page (luxury dark theme)
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';

// Already logged in as admin → redirect
if (!empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'admin') {
    header('Location: dashboard.php'); exit;
}

$errors = [];
$email  = '';

if (empty($_SESSION['admin_login_attempts']))   $_SESSION['admin_login_attempts']   = 0;
if (empty($_SESSION['admin_locked_until']))     $_SESSION['admin_locked_until']     = 0;

$isLocked      = time() < $_SESSION['admin_locked_until'];
$lockRemaining = max(0, $_SESSION['admin_locked_until'] - time());

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLocked) {
    verifyCsrf();

    $email    = strtolower(clean($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $errors[] = 'Email and password are required.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            "SELECT id, name, email, password_hash, role FROM users
             WHERE email = ? AND role = 'admin' AND is_deleted = 0"
        );
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_login_attempts'] = 0;
            $_SESSION['admin_locked_until']   = 0;
            session_regenerate_id(true);
            $_SESSION['user_id']    = $admin['id'];
            $_SESSION['user_name']  = $admin['name'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['user_role']  = 'admin';
            header('Location: dashboard.php'); exit;
        } else {
            $_SESSION['admin_login_attempts']++;
            if ($_SESSION['admin_login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
                $_SESSION['admin_locked_until'] = time() + LOCKOUT_SECONDS;
                $isLocked      = true;
                $lockRemaining = LOCKOUT_SECONDS;
                $errors[] = 'Too many failed attempts. Locked for ' . (LOCKOUT_SECONDS / 60) . ' minutes.';
            } else {
                $rem = MAX_LOGIN_ATTEMPTS - $_SESSION['admin_login_attempts'];
                $errors[] = 'Invalid admin credentials. ' . $rem . ' attempt(s) remaining.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login | <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Lato', sans-serif;
      background: #0B0B0B;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .admin-card {
      width: 100%;
      max-width: 420px;
      background: #1C1F2A;
      border: 1px solid #D4AF37;
      border-radius: 1rem;
      box-shadow: 0 20px 60px rgba(0,0,0,.6);
    }
    h1 { font-family: 'Playfair Display', serif; color: #D4AF37; }
    .form-control {
      background: #0B0B0B;
      border: 1px solid #8B7355;
      color: #F5F0E6;
    }
    .form-control:focus {
      background: #0B0B0B;
      border-color: #D4AF37;
      box-shadow: 0 0 0 .2rem rgba(212,175,55,.25);
      color: #F5F0E6;
    }
    .form-label { color: #F5F0E6; font-weight: 600; }
    .btn-gold {
      background: #D4AF37;
      border-color: #C9A227;
      color: #0B0B0B;
      font-weight: 700;
    }
    .btn-gold:hover { background: #C9A227; border-color: #D4AF37; color: #0B0B0B; }
    .text-muted-gold { color: #8B7355 !important; }
    .alert-danger { background: #2d1010; border-color: #ef4444; color: #F5F0E6; }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="admin-card mx-auto">
    <div class="p-4 p-md-5">
      <div class="text-center mb-4">
        <i class="bi bi-shield-lock" style="font-size:2.5rem;color:#D4AF37"></i>
        <h1 class="h4 mt-2 mb-0">Admin Portal</h1>
        <p class="text-muted-gold small"><?= SITE_NAME ?></p>
      </div>

      <?php if ($isLocked && empty($errors)): ?>
        <div class="alert alert-danger">
          <i class="bi bi-lock me-1"></i>
          Account locked. Please wait <?= ceil($lockRemaining / 60) ?> minute(s).
        </div>
      <?php elseif ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0 ps-3"><?php foreach ($errors as $err) echo '<li>' . e($err) . '</li>'; ?></ul>
        </div>
      <?php endif; ?>

      <?php if (!$isLocked): ?>
      <form method="POST" action="login.php" novalidate>
        <?= csrfField() ?>
        <div class="mb-3">
          <label for="adm_email" class="form-label">Admin Email</label>
          <input id="adm_email" type="email" name="email" class="form-control"
                 value="<?= e($email) ?>" required placeholder="admin@example.com" autocomplete="email">
        </div>
        <div class="mb-4">
          <label for="adm_pass" class="form-label">Password</label>
          <input id="adm_pass" type="password" name="password" class="form-control"
                 required placeholder="Your password" autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-gold w-100 py-2">
          <i class="bi bi-shield-check me-1"></i>Sign In as Admin
        </button>
      </form>
      <?php endif; ?>

      <p class="text-center mt-4 mb-0">
        <a href="../login.php" class="text-muted-gold small">
          <i class="bi bi-arrow-left me-1"></i>Back to regular login
        </a>
      </p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
