<?php
// =============================================================
// admin/manage_users.php — List, search, filter all users
// =============================================================
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

$pdo = getDB();

$search = clean($_GET['search'] ?? '');
$filter = clean($_GET['filter'] ?? 'all'); // all | active | inactive
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;

$where  = ["role = 'user'"];
$params = [];

if ($search !== '') {
    $where[]  = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter === 'active')   { $where[] = 'is_deleted = 0'; }
if ($filter === 'inactive') { $where[] = 'is_deleted = 1'; }

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$total      = (int)$pdo->prepare("SELECT COUNT(*) FROM users $whereSQL")->execute($params)
              ? (function() use ($pdo, $whereSQL, $params) {
                    $s = $pdo->prepare("SELECT COUNT(*) FROM users $whereSQL");
                    $s->execute($params); return (int)$s->fetchColumn();
                })()
              : 0;

// Redo cleanly
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$totalPages = (int)ceil($total / $perPage);
$offset     = ($page - 1) * $perPage;

$dataStmt = $pdo->prepare(
    "SELECT id, name, email, phone, role, is_deleted, daily_limit, created_at
     FROM users $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset"
);
$dataStmt->execute($params);
$users = $dataStmt->fetchAll();

$queryBase = http_build_query(array_filter(['search' => $search, 'filter' => $filter]));
$paginationBase = 'manage_users.php?' . $queryBase;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Users | Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
<style>
  :root { --gold:#D4AF37;--bg:#0B0B0B;--card:#1C1F2A;--text:#F5F0E6;--muted:#8B7355;--border:#D4AF37; }
  body  { font-family:"Lato",sans-serif; background:var(--bg); color:var(--text); font-size:.9rem; }
  h1,h2,h3,h4,h5,h6 { font-family:"Playfair Display",serif; color:var(--gold); }
  .card { background:var(--card); border:1px solid var(--border); border-radius:.75rem; }
  .card-header { background:transparent; border-bottom:1px solid var(--border); font-weight:600; color:var(--gold); }
  .table { color:var(--text); }
  .table thead th { color:var(--muted); border-color:#2a2d3a; text-transform:uppercase; font-size:.78rem; letter-spacing:.05em; }
  .table tbody td { border-color:#2a2d3a; vertical-align:middle; }
  .table-hover tbody tr:hover { background:rgba(212,175,55,.05); }
  .btn-gold { background:var(--gold); border-color:#C9A227; color:#0B0B0B; font-weight:700; }
  .btn-gold:hover { background:#C9A227; color:#0B0B0B; }
  .form-control,.form-select { background:#0B0B0B;border:1px solid #8B7355;color:var(--text); }
  .form-control:focus,.form-select:focus { background:#0B0B0B;border-color:var(--gold);box-shadow:0 0 0 .2rem rgba(212,175,55,.2);color:var(--text); }
  .form-label { color:var(--text); }
  .page-container { max-width:1400px; margin:0 auto; padding:1.5rem 1rem; }
  .page-link { background:var(--card);border-color:#2a2d3a;color:var(--text); }
  .page-item.active .page-link { background:var(--gold);border-color:var(--gold);color:#0B0B0B; }
  .dropdown-menu { background:var(--card);border:1px solid var(--border); }
  .dropdown-item { color:var(--text); }
  .dropdown-item:hover { background:rgba(212,175,55,.1);color:var(--gold); }
  .alert-success { background:#0a2a1a;border-color:#10b981;color:var(--text); }
</style>
</head>
<body>
<?php require_once 'admin_navbar.php'; ?>
<div class="page-container">

  <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <h1 class="mb-0" style="font-size:1.5rem"><i class="bi bi-people me-2"></i>Manage Users</h1>
    <span class="text-muted small"><?= $total ?> user<?= $total != 1 ? 's' : '' ?> found</span>
  </div>

  <?php $f = getFlash(); if ($f): ?>
  <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show mb-3">
    <?= e($f['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="card mb-4">
    <div class="card-body py-2">
      <form method="GET" action="manage_users.php" class="row g-2 align-items-end">
        <div class="col-12 col-md-5">
          <label class="form-label small fw-semibold mb-1">Search</label>
          <input type="text" name="search" class="form-control form-control-sm"
                 placeholder="Name or email..." value="<?= e($search) ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small fw-semibold mb-1">Status</label>
          <select name="filter" class="form-select form-select-sm">
            <option value="all"      <?= $filter==='all'?'selected':'' ?>>All Users</option>
            <option value="active"   <?= $filter==='active'?'selected':'' ?>>Active Only</option>
            <option value="inactive" <?= $filter==='inactive'?'selected':'' ?>>Deactivated Only</option>
          </select>
        </div>
        <div class="col-6 col-md-4 d-flex gap-2">
          <button type="submit" class="btn btn-gold btn-sm flex-fill">
            <i class="bi bi-funnel me-1"></i>Filter
          </button>
          <a href="manage_users.php" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Users table -->
  <div class="card">
    <div class="card-header"><i class="bi bi-table me-1"></i>User List</div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Daily Limit</th>
            <th>Joined</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($users): ?>
          <?php foreach ($users as $u): ?>
          <tr>
            <td class="text-muted"><?= $u['id'] ?></td>
            <td class="fw-semibold"><?= e($u['name']) ?></td>
            <td style="color:var(--muted)"><?= e($u['email']) ?></td>
            <td>
              <?php if ($u['is_deleted']): ?>
                <span class="badge" style="background:rgba(239,68,68,.15);color:#ef4444;border:1px solid #ef4444">
                  <i class="bi bi-x-circle me-1"></i>Inactive
                </span>
              <?php else: ?>
                <span class="badge" style="background:rgba(16,185,129,.15);color:#10b981;border:1px solid #10b981">
                  <i class="bi bi-check-circle me-1"></i>Active
                </span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($u['daily_limit'] > 0): ?>
                <span style="color:#D4AF37"><?= formatMoney($u['daily_limit']) ?></span>
              <?php else: ?>
                <span style="color:var(--muted)">None</span>
              <?php endif; ?>
            </td>
            <td style="color:var(--muted)"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td class="text-center">
              <div class="d-flex justify-content-center gap-1 flex-wrap">
                <a href="edit_user.php?id=<?= $u['id'] ?>"
                   class="btn btn-sm btn-outline-warning py-0 px-2">
                  <i class="bi bi-pencil"></i>
                </a>
                <a href="view_user_data.php?id=<?= $u['id'] ?>"
                   class="btn btn-sm btn-outline-info py-0 px-2">
                  <i class="bi bi-eye"></i>
                </a>
                <?php if ($u['is_deleted']): ?>
                <a href="toggle_user.php?id=<?= $u['id'] ?>&action=activate"
                   class="btn btn-sm btn-outline-success py-0 px-2">
                  <i class="bi bi-person-check"></i>
                </a>
                <?php else: ?>
                <a href="toggle_user.php?id=<?= $u['id'] ?>&action=deactivate"
                   class="btn btn-sm btn-outline-danger py-0 px-2"
                   onclick="return confirm('Deactivate <?= e(addslashes($u['name'])) ?>? They will not be able to log in.')">
                  <i class="bi bi-person-x"></i>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-5" style="color:var(--muted)">
              <i class="bi bi-search" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
              No users found.
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer" style="background:transparent;border-top:1px solid #2a2d3a">
      <?= paginationLinks($page, $totalPages, $paginationBase) ?>
    </div>
    <?php endif; ?>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
