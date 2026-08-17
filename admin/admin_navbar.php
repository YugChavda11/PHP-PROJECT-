<?php
// admin/admin_navbar.php — Luxury dark navbar for admin panel
// Requires session_start() and admin_auth.php before including
$adminName = e($_SESSION['user_name'] ?? 'Admin');
$adminCurrent = basename($_SERVER['PHP_SELF']);
function adminNavActive(string $f): string {
    global $adminCurrent;
    return $adminCurrent === $f ? ' active' : '';
}
?>
<nav class="navbar navbar-expand-lg" style="
    background-color:#0B0B0B;
    border-bottom: 2px solid #D4AF37;
    font-family:'Lato','Poppins',sans-serif;">
  <div class="container-fluid px-3">
    <a class="navbar-brand fw-bold" href="dashboard.php"
       style="color:#D4AF37;font-family:'Playfair Display',serif;font-size:1.2rem;letter-spacing:.5px;">
      <i class="bi bi-shield-check me-2"></i>Admin Panel
    </a>
    <button class="navbar-toggler border-0" type="button"
            data-bs-toggle="collapse" data-bs-target="#adminNav"
            style="color:#F5F0E6;">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link<?= adminNavActive('dashboard.php') ?>"
             href="dashboard.php"
             style="color:<?= $adminCurrent==='dashboard.php'?'#D4AF37':'#F5F0E6' ?>">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= adminNavActive('manage_users.php') ?>"
             href="manage_users.php"
             style="color:<?= $adminCurrent==='manage_users.php'?'#D4AF37':'#F5F0E6' ?>">
            <i class="bi bi-people me-1"></i>Manage Users
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= adminNavActive('activity_log.php') ?>"
             href="activity_log.php"
             style="color:<?= $adminCurrent==='activity_log.php'?'#D4AF37':'#F5F0E6' ?>">
            <i class="bi bi-journal-text me-1"></i>Activity Log
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../dashboard.php" style="color:#8B7355">
            <i class="bi bi-arrow-left-circle me-1"></i>Main App
          </a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"
             style="color:#F5F0E6">
            <i class="bi bi-person-circle me-1"></i><?= $adminName ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end"
              style="background:#1C1F2A;border:1px solid #D4AF37;">
            <li>
              <a class="dropdown-item" href="../profile.php"
                 style="color:#F5F0E6">
                <i class="bi bi-person me-2"></i>My Profile
              </a>
            </li>
            <li><hr class="dropdown-divider" style="border-color:#D4AF37"></li>
            <li>
              <a class="dropdown-item" href="logout.php"
                 style="color:#ef4444">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
