<?php
// profile.php - User Settings, Preferences & Category Customizer
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/notifications.php';
require_once __DIR__ . '/includes/security.php';

$pdo = getDBConnection();
checkAuth($pdo);
$currentUser = getCurrentUser($pdo);
if (!$currentUser) {
    header("Location: auth/login.php");
    exit;
}
$userId = $_SESSION['user_id'];

$currentPage = 'profile';
$pageTitle = 'Settings & Preferences';

// Fetch Notifications
$notifications = getUserNotifications($pdo, $userId);

// Update Profile Info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $currency = $_POST['currency_symbol'] ?? '$';
    $monthly_budget = (float)($_POST['monthly_budget'] ?? 2000);

    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, currency_symbol = ?, monthly_budget = ? WHERE id = ?");
        $stmt->execute([$name, $currency, $monthly_budget, $userId]);
        logActivity($pdo, $userId, 'UPDATE_PROFILE', 'Updated profile preferences');
        setFlashPop('Profile Updated! ⚙️', 'Your preferences have been saved successfully.');
        $currentUser = getCurrentUser($pdo);
    }
}

// Add Custom Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $catName = sanitizeInput($_POST['cat_name'] ?? '');
    $catType = in_array($_POST['cat_type'], ['income', 'expense']) ? $_POST['cat_type'] : 'expense';
    $catIcon = sanitizeInput($_POST['cat_icon'] ?? 'tag');
    $catColor = $_POST['cat_color'] ?? '#6366f1';

    if (!empty($catName)) {
        $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type, icon, color) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $catName, $catType, $catIcon, $catColor]);
        logActivity($pdo, $userId, 'ADD_USER_CATEGORY', "Added custom category: $catName");
        setFlashPop('Category Created! 🏷️', "Custom category '$catName' added.");
    }
}

// Fetch user's categories
$stmtCat = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY id DESC");
$stmtCat->execute([$userId]);
$userCategories = $stmtCat->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-header">
        <div>
            <h1 class="page-title">Settings & Preferences</h1>
            <p style="color: var(--text-secondary); font-size:0.9rem; margin-top:4px;">Customize your profile, currency, budget targets, and custom categories</p>
        </div>
        <div class="header-actions" style="align-items:center;">
            <div class="notification-wrapper">
                <button class="btn btn-secondary notification-btn" id="notifBellBtn" title="Smart Notifications">
                    <i data-lucide="bell"></i>
                    <?php if (count($notifications) > 0): ?>
                        <span class="notif-badge"><?php echo count($notifications); ?></span>
                    <?php endif; ?>
                </button>

                <div class="notification-dropdown" id="notifDropdown">
                    <div class="notif-header">
                        <div style="font-weight:800; font-size:0.92rem; display:flex; align-items:center; gap:6px;">
                            <i data-lucide="bell" style="width:15px; color:var(--accent-primary);"></i> Notifications
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span class="badge" id="notifCountBadge" style="background:rgba(99,102,241,0.2); color:var(--accent-primary);"><?php echo count($notifications); ?> Active</span>
                            <button id="clearNotifsBtn" class="btn btn-secondary btn-sm" style="font-size:0.72rem; padding:2px 8px; font-weight:700;">Clear All</button>
                        </div>
                    </div>

                    <div class="notif-list">
                        <?php foreach ($notifications as $n): ?>
                            <div class="notif-item">
                                <div class="notif-icon" style="background: <?php echo $n['color']; ?>22; color: <?php echo $n['color']; ?>;">
                                    <i data-lucide="<?php echo $n['icon']; ?>" style="width:18px; height:18px;"></i>
                                </div>
                                <div class="notif-content">
                                    <div class="notif-title"><?php echo $n['title']; ?></div>
                                    <div class="notif-msg"><?php echo $n['message']; ?></div>
                                    <div class="notif-time"><?php echo $n['time']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid-2">
        <!-- User Profile Form -->
        <div class="card">
            <h3 style="font-size:1.1rem; font-weight:800; margin-bottom:20px;">Account Settings</h3>
            <form action="profile.php" method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo sanitizeOutput($currentUser['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address (Read Only)</label>
                    <input type="email" class="form-control" value="<?php echo sanitizeOutput($currentUser['email']); ?>" readonly disabled style="opacity:0.6;">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Preferred Currency Symbol</label>
                        <select name="currency_symbol" class="form-control">
                            <option value="$" <?php echo $currentUser['currency_symbol'] === '$' ? 'selected' : ''; ?>>$ (USD)</option>
                            <option value="₹" <?php echo $currentUser['currency_symbol'] === '₹' ? 'selected' : ''; ?>>₹ (INR)</option>
                            <option value="€" <?php echo $currentUser['currency_symbol'] === '€' ? 'selected' : ''; ?>>€ (EUR)</option>
                            <option value="£" <?php echo $currentUser['currency_symbol'] === '£' ? 'selected' : ''; ?>>£ (GBP)</option>
                            <option value="¥" <?php echo $currentUser['currency_symbol'] === '¥' ? 'selected' : ''; ?>>¥ (JPY)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Global Monthly Target Budget</label>
                        <input type="number" step="50" name="monthly_budget" class="form-control" value="<?php echo $currentUser['monthly_budget']; ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:10px;">
                    <i data-lucide="save"></i> Save Profile Settings
                </button>
            </form>
        </div>

        <!-- Custom User Categories Customizer -->
        <div class="card">
            <h3 style="font-size:1.1rem; font-weight:800; margin-bottom:20px;">Create Personal Category</h3>
            <form action="profile.php" method="POST" style="margin-bottom:24px;">
                <input type="hidden" name="action" value="add_category">

                <div class="form-group">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="cat_name" class="form-control" placeholder="e.g. Pet Care, Crypto Trading" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select name="cat_type" class="form-control">
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Color Theme</label>
                        <input type="color" name="cat_color" class="form-control" value="#6366f1" style="height:46px; padding:4px;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Lucide Icon Name</label>
                    <select name="cat_icon" class="form-control">
                        <option value="tag">tag</option>
                        <option value="heart-pulse">heart-pulse</option>
                        <option value="paw-print">paw-print</option>
                        <option value="gift">gift</option>
                        <option value="plane">plane</option>
                        <option value="coffee">coffee</option>
                        <option value="music">music</option>
                        <option value="shield">shield</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">
                    <i data-lucide="plus-circle"></i> Add Category
                </button>
            </form>

            <?php if (!empty($userCategories)): ?>
                <h4 style="font-size:0.9rem; font-weight:700; margin-bottom:12px;">Your Custom Categories</h4>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <?php foreach ($userCategories as $c): ?>
                        <span class="category-tag" style="border-left: 3px solid <?php echo $c['color']; ?>;">
                            <i data-lucide="<?php echo htmlspecialchars($c['icon'] ?: 'tag'); ?>" style="width:14px; height:14px; margin-right:4px;"></i>
                            <?php echo sanitizeOutput($c['name']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
