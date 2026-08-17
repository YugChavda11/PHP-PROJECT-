<?php
// =============================================================
// admin/view_user_data.php — View a user's income + expenses
// =============================================================
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

$pdo    = getDB();
$userId = (int)($_GET['id'] ?? 0);
if (!$userId) { header('Location: manage_users.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) { header('Location: manage_users.php'); exit; }

// Summary stats
$totalInc = (float)$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM income WHERE user_id=?")->execute([$userId])
           ? (function() use ($pdo, $userId) {
                 $s = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM income WHERE user_id=?");
                 $s->execute([$userId]); return (float)$s->fetchColumn();
             })()
           : 0;

// Redo cleanly
$incTotStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM income WHERE user_id=?");
$incTotStmt->execute([$userId]);
$totalInc = (float)$incTotStmt->fetchColumn();

$expTotStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=?");
$expTotStmt->execute([$userId]);
$totalExp = (float)$expTotStmt->fetchColumn();

$net = $totalInc - $totalExp;

// Income records
$incStmt = $pdo->prepare(
    "SELECT * FROM income WHERE user_id=? ORDER BY date DESC, id DESC LIMIT 100"
);
$incStmt->execute([$userId]);
$incRows = $incStmt->fetchAll();

// Expense records
$expStmt = $pdo->prepare(
    "SELECT * FROM expenses WHERE user_id=? ORDER BY date DESC, id DESC LIMIT 100"
);
$expStmt->execute([$userId]);
$expRows = $expStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($user['name']) ?> — Data | Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
<style>
  :root{--gold:#D4AF37;--bg:#0B0B0B;--card:#1C1F2A;--text:#F5F0E6;--muted:#8B7355;--border:#D4AF37}
  body{font-family:"Lato",sans-serif;background:var(--bg);color:var(--text);font-size:.9rem}
  h1,h2,h3,h4,h5,h6{font-family:"Playfair Display",serif;color:var(--gold)}
  .card{background:var(--card);border:1px solid var(--border);border-radius:.75rem}
  .card-header{background:transparent;border-bottom:1px solid var(--border);font-weight:600;color:var(--gold)}
  .table{color:var(--text)}
  .table thead th{color:var(--muted);border-color:#2a2d3a;text-transform:uppercase;font-size:.78rem;letter-spacing:.05em}
  .table tbody td{border-color:#2a2d3a;vertical-align:middle}
  .table-hover tbody tr:hover{background:rgba(212,175,55,.05)}
  .tr-flagged{background:rgba(239,68,68,.1)!important}
  .btn-gold{background:var(--gold);border-color:#C9A227;color:#0B0B0B;font-weight:700}
  .btn-gold:hover{background:#C9A227;color:#0B0B0B}
  .page-container{max-width:1400px;margin:0 auto;padding:1.5rem 1rem}
  .stat-mini{background:#12151e;border:1px solid #2a2d3a;border-radius:.5rem;padding:.75rem 1rem}
  .dropdown-menu{background:var(--card);border:1px solid var(--border)}
  .dropdown-item{color:var(--text)}.dropdown-item:hover{background:rgba(212,175,55,.1);color:var(--gold)}
</style>
</head>
<body>
<?php require_once 'admin_navbar.php'; ?>
<div class="page-container">

  <!-- Header -->
  <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <a href="manage_users.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h1 class="mb-0" style="font-size:1.4rem">
        <i class="bi bi-person-lines-fill me-1"></i><?= e($user['name']) ?>
      </h1>
      <small style="color:var(--muted)"><?= e($user['email']) ?> &bull; ID #<?= $user['id'] ?> &bull; Joined <?= date('d M Y', strtotime($user['created_at'])) ?></small>
    </div>
    <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-warning ms-auto">
      <i class="bi bi-pencil me-1"></i>Edit User
    </a>
  </div>

  <!-- Summary stats -->
  <div class="row g-3 mb-4">
    <div class="col-4">
      <div class="stat-mini">
        <div style="color:#D4AF37;font-size:.72rem;text-transform:uppercase;letter-spacing:.07em">Total Income</div>
        <div style="color:#10b981;font-size:1.2rem;font-weight:700"><?= formatMoney($totalInc) ?></div>
      </div>
    </div>
    <div class="col-4">
      <div class="stat-mini">
        <div style="color:#D4AF37;font-size:.72rem;text-transform:uppercase;letter-spacing:.07em">Total Expenses</div>
        <div style="color:#ef4444;font-size:1.2rem;font-weight:700"><?= formatMoney($totalExp) ?></div>
      </div>
    </div>
    <div class="col-4">
      <div class="stat-mini">
        <div style="color:#D4AF37;font-size:.72rem;text-transform:uppercase;letter-spacing:.07em">Net</div>
        <div style="color:<?= $net >= 0 ? '#D4AF37' : '#ef4444' ?>;font-size:1.2rem;font-weight:700">
          <?= formatMoney(abs($net)) ?>
          <span style="font-size:.7rem"><?= $net >= 0 ? '▲' : '▼' ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Income table -->
  <div class="card mb-4">
    <div class="card-header">
      <i class="bi bi-arrow-down-circle text-success me-1"></i>
      Income — <?= count($incRows) ?> record<?= count($incRows) != 1 ? 's' : '' ?> (last 100)
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>Date</th><th>Category</th><th>Description</th><th class="text-end">Amount</th></tr>
        </thead>
        <tbody>
          <?php if ($incRows): ?>
          <?php foreach ($incRows as $r): ?>
          <tr>
            <td style="color:var(--muted)"><?= formatDate($r['date']) ?></td>
            <td><span class="badge" style="background:rgba(16,185,129,.15);color:#10b981"><?= e($r['category']) ?></span></td>
            <td class="text-truncate" style="max-width:200px;color:var(--muted)"><?= e($r['description'] ?? '') ?></td>
            <td class="text-end fw-semibold" style="color:#10b981"><?= formatMoney($r['amount']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php else: ?>
          <tr><td colspan="4" class="text-center py-4" style="color:var(--muted)">No income records.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Expenses table -->
  <div class="card">
    <div class="card-header">
      <i class="bi bi-arrow-up-circle text-danger me-1"></i>
      Expenses — <?= count($expRows) ?> record<?= count($expRows) != 1 ? 's' : '' ?> (last 100)
      &nbsp;<span class="badge" style="background:rgba(239,68,68,.2);color:#ef4444">
        <?= count(array_filter($expRows, fn($r) => $r['is_flagged'])) ?> flagged
      </span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Date</th><th>Category</th><th>Payment</th>
            <th>Description</th><th class="text-end">Amount</th><th class="text-center">Flag</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($expRows): ?>
          <?php foreach ($expRows as $r): ?>
          <tr class="<?= $r['is_flagged'] ? 'tr-flagged' : '' ?>">
            <td style="color:var(--muted)"><?= formatDate($r['date']) ?></td>
            <td><span class="badge" style="background:rgba(239,68,68,.15);color:#ef4444"><?= e($r['category']) ?></span></td>
            <td style="color:var(--muted)"><?= e($r['payment_method'] ?? '—') ?></td>
            <td class="text-truncate" style="max-width:180px;color:var(--muted)"><?= e($r['description'] ?? '') ?></td>
            <td class="text-end fw-semibold" style="color:#ef4444"><?= formatMoney($r['amount']) ?></td>
            <td class="text-center">
              <?php if ($r['is_flagged']): ?>
              <a href="flag_transaction.php?id=<?= $r['id'] ?>&user_id=<?= $userId ?>&flag=0"
                 class="btn btn-sm py-0 px-2"
                 style="background:rgba(239,68,68,.15);color:#ef4444;border:1px solid #ef4444">
                <i class="bi bi-flag-fill me-1"></i>Flagged
              </a>
              <?php else: ?>
              <a href="flag_transaction.php?id=<?= $r['id'] ?>&user_id=<?= $userId ?>&flag=1"
                 class="btn btn-sm btn-outline-secondary py-0 px-2">
                <i class="bi bi-flag me-1"></i>Flag
              </a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php else: ?>
          <tr><td colspan="6" class="text-center py-4" style="color:var(--muted)">No expense records.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
