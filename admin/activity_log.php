<?php
// =============================================================
// admin/activity_log.php — Admin audit trail from admin_logs
// =============================================================
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

$pdo  = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$countStmt = $pdo->query("SELECT COUNT(*) FROM admin_logs");
$total     = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);
$offset     = ($page - 1) * $perPage;

$logStmt = $pdo->prepare("
    SELECT al.*, a.name AS admin_name, t.name AS target_name
    FROM admin_logs al
    JOIN  users a ON a.id = al.admin_id
    LEFT JOIN users t ON t.id = al.target_user_id
    ORDER BY al.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$logStmt->execute();
$logs = $logStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Activity Log | Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
<style>
  :root{--gold:#D4AF37;--bg:#0B0B0B;--card:#1C1F2A;--text:#F5F0E6;--muted:#8B7355;--border:#D4AF37}
  body{font-family:"Lato",sans-serif;background:var(--bg);color:var(--text);font-size:.9rem}
  h1,h2,h3,h4{font-family:"Playfair Display",serif;color:var(--gold)}
  .card{background:var(--card);border:1px solid var(--border);border-radius:.75rem}
  .card-header{background:transparent;border-bottom:1px solid var(--border);font-weight:600;color:var(--gold)}
  .table{color:var(--text)}
  .table thead th{color:var(--muted);border-color:#2a2d3a;text-transform:uppercase;font-size:.78rem;letter-spacing:.05em}
  .table tbody td{border-color:#2a2d3a;vertical-align:middle}
  .page-container{max-width:1200px;margin:0 auto;padding:1.5rem 1rem}
  .page-link{background:var(--card);border-color:#2a2d3a;color:var(--text)}
  .page-item.active .page-link{background:var(--gold);border-color:var(--gold);color:#0B0B0B}
  .dropdown-menu{background:var(--card);border:1px solid var(--border)}
  .dropdown-item{color:var(--text)}.dropdown-item:hover{background:rgba(212,175,55,.1);color:var(--gold)}
</style>
</head>
<body>
<?php require_once 'admin_navbar.php'; ?>
<div class="page-container">
  <h1 class="mb-4" style="font-size:1.5rem">
    <i class="bi bi-journal-text me-2"></i>Activity Log
    <small class="fs-6 fw-normal ms-2" style="color:var(--muted)"><?= $total ?> total entries</small>
  </h1>

  <div class="card">
    <div class="card-header"><i class="bi bi-list-check me-1"></i>Admin Actions</div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Admin</th>
            <th>Action</th>
            <th>Target User</th>
            <th>Date / Time</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($logs): ?>
          <?php foreach ($logs as $log): ?>
          <tr>
            <td style="color:var(--muted)"><?= $log['id'] ?></td>
            <td class="fw-semibold" style="color:#D4AF37"><?= e($log['admin_name']) ?></td>
            <td><?= e($log['action']) ?></td>
            <td>
              <?php if ($log['target_user_id'] && $log['target_name']): ?>
              <a href="view_user_data.php?id=<?= (int)$log['target_user_id'] ?>"
                 style="color:#F5F0E6">
                <?= e($log['target_name']) ?>
              </a>
              <?php else: ?>
              <span style="color:var(--muted)">—</span>
              <?php endif; ?>
            </td>
            <td style="color:var(--muted)">
              <?= date('d M Y H:i', strtotime($log['created_at'])) ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php else: ?>
          <tr>
            <td colspan="5" class="text-center py-5" style="color:var(--muted)">
              No activity logged yet.
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div style="background:transparent;border-top:1px solid #2a2d3a;padding:.75rem">
      <?= paginationLinks($page, $totalPages, 'activity_log.php?') ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
