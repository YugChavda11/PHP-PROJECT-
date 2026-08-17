<?php
// =============================================================
// admin/dashboard.php — Admin overview: stats, signups, top spenders, flagged
// =============================================================
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

$pdo = getDB();

// ---- Aggregate stats -----------------------------------------
$totalUsers  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user' AND is_deleted=0")->fetchColumn();
$totalInc    = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM income")->fetchColumn();
$totalExp    = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn();
$totalTxns   = (int)$pdo->query("SELECT (SELECT COUNT(*) FROM income) + (SELECT COUNT(*) FROM expenses)")->fetchColumn();
$flaggedCount= (int)$pdo->query("SELECT COUNT(*) FROM expenses WHERE is_flagged=1")->fetchColumn();
$net = $totalInc - $totalExp;

// ---- New signups last 6 months --------------------------------
$signupStmt = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS mo,
           DATE_FORMAT(created_at,'%b %Y')  AS label,
           COUNT(*) AS cnt
    FROM users
    WHERE role='user'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at,'%Y-%m'), DATE_FORMAT(created_at,'%b %Y')
    ORDER BY mo ASC
");
$signupData = $signupStmt->fetchAll();
$signupMax  = $signupData ? max(array_column($signupData, 'cnt')) : 1;

// ---- Top 5 spenders ------------------------------------------
$spendersStmt = $pdo->query("
    SELECT u.id, u.name, u.email, COALESCE(SUM(e.amount),0) AS total_exp
    FROM users u
    LEFT JOIN expenses e ON e.user_id = u.id
    WHERE u.role='user' AND u.is_deleted=0
    GROUP BY u.id, u.name, u.email
    ORDER BY total_exp DESC
    LIMIT 5
");
$topSpenders = $spendersStmt->fetchAll();
$spendMax = $topSpenders ? max(array_column($topSpenders,'total_exp')) : 1;

// ---- Recent flagged expenses ---------------------------------
$flaggedStmt = $pdo->query("
    SELECT e.id, e.amount, e.date, e.category, e.description, u.name AS user_name
    FROM expenses e
    JOIN users u ON u.id = e.user_id
    WHERE e.is_flagged = 1
    ORDER BY e.created_at DESC
    LIMIT 10
");
$flagged = $flaggedStmt->fetchAll();

// ---- Common luxury page head ---------------------------------
function adminHead(string $title): void {
    echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($title, ENT_QUOTES) . ' | Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
<style>
  :root {
    --gold:#D4AF37; --gold-hover:#C9A227; --bg:#0B0B0B;
    --card:#1C1F2A; --text:#F5F0E6; --muted:#8B7355; --border:#D4AF37;
  }
  body   { font-family:"Lato",sans-serif; background:var(--bg); color:var(--text); font-size:.9rem; }
  h1,h2,h3,h4,h5,h6 { font-family:"Playfair Display",serif; color:var(--gold); }
  .card  { background:var(--card); border:1px solid var(--border); border-radius:.75rem; }
  .card-header { background:transparent; border-bottom:1px solid var(--border); font-weight:600; color:var(--gold); }
  .table { color:var(--text); }
  .table thead th { color:var(--muted); border-color:#2a2d3a; text-transform:uppercase; font-size:.78rem; letter-spacing:.05em; }
  .table tbody td { border-color:#2a2d3a; }
  .table-hover tbody tr:hover { background:rgba(212,175,55,.05); }
  .stat-card { border-radius:.75rem; padding:1.25rem; }
  .stat-card .stat-value { font-size:1.6rem; font-weight:700; font-family:"Playfair Display",serif; }
  .stat-card .stat-label { font-size:.75rem; text-transform:uppercase; letter-spacing:.07em; opacity:.7; }
  .btn-gold { background:var(--gold); border-color:var(--gold-hover); color:#0B0B0B; font-weight:700; }
  .btn-gold:hover { background:var(--gold-hover); color:#0B0B0B; }
  .progress { background:#2a2d3a; }
  .progress-thin { height:.4rem; border-radius:1rem; }
  .badge-active { background:rgba(16,185,129,.15); color:#10b981; border:1px solid #10b981; }
  .badge-inactive { background:rgba(239,68,68,.15); color:#ef4444; border:1px solid #ef4444; }
  .badge-flagged { background:rgba(239,68,68,.2); color:#ef4444; }
  .form-control, .form-select {
    background:#0B0B0B; border:1px solid #8B7355;
    color:var(--text);
  }
  .form-control:focus, .form-select:focus {
    background:#0B0B0B; border-color:var(--gold);
    box-shadow:0 0 0 .2rem rgba(212,175,55,.2); color:var(--text);
  }
  .page-container { max-width:1400px; margin:0 auto; padding:1.5rem 1rem; }
  .dropdown-menu { background:var(--card); border:1px solid var(--border); }
  .dropdown-item { color:var(--text); }
  .dropdown-item:hover { background:rgba(212,175,55,.1); color:var(--gold); }
</style>
</head>
<body>';
}

adminHead('Admin Dashboard');
require_once 'admin_navbar.php';
?>

<div class="page-container">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="mb-0" style="font-size:1.6rem">
      <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
    </h1>
    <span class="text-muted small"><?= date('d M Y, H:i') ?></span>
  </div>

  <?php
  // Flash
  $f = getFlash();
  if ($f): ?>
  <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show mb-3">
    <?= e($f['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <!-- ===== STATS CARDS ===== -->
  <div class="row g-3 mb-4">
    <?php
    $cards = [
      ['Total Users',      $totalUsers,              '#D4AF37', 'bi-people'],
      ['Active Users',     $activeUsers,             '#10b981', 'bi-person-check'],
      ['Total Transactions',$totalTxns,              '#3b82f6', 'bi-arrow-left-right'],
      ['Flagged Expenses', $flaggedCount,            '#ef4444', 'bi-flag'],
    ];
    foreach ($cards as [$label, $val, $color, $icon]): ?>
    <div class="col-6 col-lg-3">
      <div class="card stat-card h-100" style="border-color:<?= $color ?>30">
        <div style="color:<?= $color ?>"><i class="bi <?= $icon ?>" style="font-size:1.5rem"></i></div>
        <div class="stat-value mt-1" style="color:<?= $color ?>"><?= number_format($val) ?></div>
        <div class="stat-label"><?= $label ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Income / Expense / Net -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
      <div class="card stat-card h-100" style="border-color:#10b98130">
        <div style="color:#10b981"><i class="bi bi-arrow-down-circle" style="font-size:1.5rem"></i></div>
        <div class="stat-value mt-1" style="color:#10b981"><?= formatMoney($totalInc) ?></div>
        <div class="stat-label">Total Income (All Users)</div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card stat-card h-100" style="border-color:#ef444430">
        <div style="color:#ef4444"><i class="bi bi-arrow-up-circle" style="font-size:1.5rem"></i></div>
        <div class="stat-value mt-1" style="color:#ef4444"><?= formatMoney($totalExp) ?></div>
        <div class="stat-label">Total Expenses (All Users)</div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card stat-card h-100" style="border-color:<?= $net >= 0 ? '#D4AF37' : '#ef4444' ?>30">
        <div style="color:<?= $net >= 0 ? '#D4AF37':'#ef4444' ?>">
          <i class="bi bi-wallet" style="font-size:1.5rem"></i>
        </div>
        <div class="stat-value mt-1" style="color:<?= $net >= 0 ? '#D4AF37':'#ef4444' ?>">
          <?= formatMoney(abs($net)) ?>
        </div>
        <div class="stat-label">Net <?= $net >= 0 ? 'Surplus' : 'Deficit' ?> (Platform)</div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <!-- ===== NEW SIGNUPS — CSS bar chart ===== -->
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-person-plus me-1"></i>New Signups — Last 6 Months</div>
        <div class="card-body">
          <?php if ($signupData): ?>
          <?php foreach ($signupData as $row): ?>
          <?php $pct = $signupMax > 0 ? round($row['cnt'] / $signupMax * 100) : 0; ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span class="small fw-semibold"><?= e($row['label']) ?></span>
              <span class="small" style="color:#D4AF37"><?= $row['cnt'] ?> user<?= $row['cnt'] != 1 ? 's' : '' ?></span>
            </div>
            <div class="progress progress-thin">
              <div class="progress-bar" style="width:<?= $pct ?>%;background:#D4AF37"></div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php else: ?>
          <p class="text-muted text-center py-4">No signups in the last 6 months.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ===== TOP SPENDERS ===== -->
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-trophy me-1"></i>Top 5 Spenders</div>
        <div class="card-body">
          <?php if ($topSpenders): ?>
          <?php foreach ($topSpenders as $i => $row): ?>
          <?php $pct = $spendMax > 0 ? round($row['total_exp'] / $spendMax * 100) : 0; ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span class="small fw-semibold">
                <span style="color:#D4AF37">#<?= $i+1 ?></span>
                <a href="view_user_data.php?id=<?= $row['id'] ?>" class="ms-1" style="color:#F5F0E6">
                  <?= e($row['name']) ?>
                </a>
              </span>
              <span class="small text-danger fw-semibold"><?= formatMoney($row['total_exp']) ?></span>
            </div>
            <div class="progress progress-thin">
              <div class="progress-bar bg-danger" style="width:<?= $pct ?>%"></div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php else: ?>
          <p class="text-muted text-center py-4">No expense data.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ===== FLAGGED TRANSACTIONS ===== -->
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-flag me-1" style="color:#ef4444"></i>Recent Flagged Expenses</span>
          <a href="manage_users.php" class="btn btn-sm btn-gold">View All Users</a>
        </div>
        <?php if ($flagged): ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>User</th><th>Date</th><th>Category</th>
                <th>Description</th><th class="text-end">Amount</th><th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($flagged as $r): ?>
              <tr>
                <td><a href="view_user_data.php?id=<?= $r['id'] ?>" style="color:#D4AF37"><?= e($r['user_name']) ?></a></td>
                <td class="text-muted"><?= formatDate($r['date']) ?></td>
                <td><?= e($r['category']) ?></td>
                <td class="text-truncate" style="max-width:160px"><?= e($r['description'] ?? '') ?></td>
                <td class="text-end text-danger fw-semibold"><?= formatMoney($r['amount']) ?></td>
                <td class="text-center">
                  <a href="flag_transaction.php?id=<?= $r['id'] ?>&user_id=<?= $r['id'] ?>&flag=0"
                     class="btn btn-sm btn-outline-secondary py-0 px-2">Unflag</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="card-body text-center text-muted py-4">
          <i class="bi bi-check-circle" style="font-size:1.5rem;color:#10b981"></i>
          <p class="mb-0 mt-2">No flagged transactions.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
