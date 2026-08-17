<?php
// =============================================================
// admin/edit_user.php — Edit user name, email, daily limit
// =============================================================
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

$pdo    = getDB();
$uid    = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$errors = [];

if (!$uid) { header('Location: manage_users.php'); exit; }

// Load user (only regular users, not other admins)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
$stmt->execute([$uid]);
$user = $stmt->fetch();
if (!$user) { header('Location: manage_users.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name       = clean($_POST['name']        ?? '');
    $email      = strtolower(clean($_POST['email'] ?? ''));
    $dailyLimit = (float)($_POST['daily_limit'] ?? 0);

    if (strlen($name) < 2)                         $errors[] = 'Name must be at least 2 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($dailyLimit < 0)                            $errors[] = 'Daily limit cannot be negative.';

    // Check email uniqueness (excluding current user)
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $uid]);
        if ($check->fetch()) $errors[] = 'Email is already used by another account.';
    }

    if (empty($errors)) {
        $pdo->prepare("UPDATE users SET name=?, email=?, daily_limit=? WHERE id=?")
            ->execute([$name, $email, $dailyLimit, $uid]);

        // Log admin action
        $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_user_id) VALUES (?,?,?)")
            ->execute([(int)$_SESSION['user_id'], 'Edited user profile', $uid]);

        setFlash('success', "User '{$name}' updated successfully.");
        header('Location: manage_users.php'); exit;
    }
}

// Pre-fill from POST if validation failed, else from DB
$name       = $_SERVER['REQUEST_METHOD'] === 'POST' ? clean($_POST['name'] ?? '') : $user['name'];
$email      = $_SERVER['REQUEST_METHOD'] === 'POST' ? clean($_POST['email'] ?? '') : $user['email'];
$dailyLimit = $_SERVER['REQUEST_METHOD'] === 'POST' ? (float)($_POST['daily_limit'] ?? 0) : (float)$user['daily_limit'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit User | Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
<style>
  :root{--gold:#D4AF37;--bg:#0B0B0B;--card:#1C1F2A;--text:#F5F0E6;--muted:#8B7355;--border:#D4AF37}
  body{font-family:"Lato",sans-serif;background:var(--bg);color:var(--text);font-size:.9rem}
  h1,h2,h3,h4,h5,h6{font-family:"Playfair Display",serif;color:var(--gold)}
  .card{background:var(--card);border:1px solid var(--border);border-radius:.75rem}
  .card-header{background:transparent;border-bottom:1px solid var(--border);font-weight:600;color:var(--gold)}
  .form-control,.form-select{background:#0B0B0B;border:1px solid #8B7355;color:var(--text)}
  .form-control:focus,.form-select:focus{background:#0B0B0B;border-color:var(--gold);box-shadow:0 0 0 .2rem rgba(212,175,55,.2);color:var(--text)}
  .form-label{color:var(--text);font-weight:600}
  .btn-gold{background:var(--gold);border-color:#C9A227;color:#0B0B0B;font-weight:700}
  .btn-gold:hover{background:#C9A227;color:#0B0B0B}
  .page-container{max-width:600px;margin:0 auto;padding:1.5rem 1rem}
  .alert-danger{background:#2d1010;border-color:#ef4444;color:var(--text)}
  .dropdown-menu{background:var(--card);border:1px solid var(--border)}
  .dropdown-item{color:var(--text)}.dropdown-item:hover{background:rgba(212,175,55,.1);color:var(--gold)}
</style>
</head>
<body>
<?php require_once 'admin_navbar.php'; ?>
<div class="page-container">
  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="manage_users.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="mb-0" style="font-size:1.4rem">
      <i class="bi bi-pencil-square me-1"></i>Edit User
    </h1>
  </div>

  <?php if ($errors): ?>
  <div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3"><?php foreach ($errors as $err) echo '<li>' . e($err) . '</li>'; ?></ul>
  </div>
  <?php endif; ?>

  <!-- User info badge -->
  <div class="card mb-4" style="border-color:#2a2d3a">
    <div class="card-body py-2 px-3">
      <small style="color:var(--muted)">Editing user ID #<?= $user['id'] ?> &bull; Joined <?= date('d M Y', strtotime($user['created_at'])) ?></small>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><i class="bi bi-person me-1"></i>User Details</div>
    <div class="card-body">
      <form method="POST" action="edit_user.php" class="row g-3">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= $user['id'] ?>">

        <div class="col-12">
          <label for="eu_name" class="form-label">Full Name <span class="text-danger">*</span></label>
          <input id="eu_name" type="text" name="name" class="form-control"
                 value="<?= e($name) ?>" required minlength="2" maxlength="100">
        </div>

        <div class="col-12">
          <label for="eu_email" class="form-label">Email <span class="text-danger">*</span></label>
          <input id="eu_email" type="email" name="email" class="form-control"
                 value="<?= e($email) ?>" required maxlength="255">
        </div>

        <div class="col-12">
          <label for="eu_limit" class="form-label">
            Daily Expense Limit (<?= CURRENCY_SYMBOL ?>)
            <small style="color:var(--muted);font-weight:normal">(0 = no limit)</small>
          </label>
          <input id="eu_limit" type="number" name="daily_limit" class="form-control"
                 step="0.01" min="0" value="<?= number_format($dailyLimit, 2, '.', '') ?>">
          <div class="form-text" style="color:var(--muted)">
            When set, the user sees a warning when their daily spend reaches or exceeds this amount.
          </div>
        </div>

        <div class="col-12 d-flex gap-2 pt-2">
          <button type="submit" class="btn btn-gold">
            <i class="bi bi-check-circle me-1"></i>Save Changes
          </button>
          <a href="manage_users.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
