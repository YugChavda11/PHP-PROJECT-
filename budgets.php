<?php
// budgets.php - Category Budget Caps Manager with Dynamic Pop Notifications
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
$currency = $currentUser['currency_symbol'] ?? '$';

$currentPage = 'budgets';
$pageTitle = 'Budget Caps';

// Notifications
$notifications = getUserNotifications($pdo, $userId);

$currentMonth = date('Y-m');
$dateSql = getMonthSql('t.date');

// Update Budget Limits
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['budgets'])) {
    foreach ($_POST['budgets'] as $catId => $limit) {
        $catId = (int)$catId;
        $limit = (float)$limit;
        $stmt = $pdo->prepare("UPDATE categories SET budget_limit = ? WHERE id = ? AND (user_id IS NULL OR user_id = ?)");
        $stmt->execute([$limit, $catId, $userId]);
    }
    logActivity($pdo, $userId, 'UPDATE_BUDGET_CAPS', 'Updated category budget caps');
    setFlashPop('Budget Caps Saved! 📊', 'Category monthly budget limits have been updated.');
}

// Fetch categories with monthly spending
$stmtCat = $pdo->prepare("
    SELECT c.*, COALESCE(SUM(t.amount), 0) as current_spent 
    FROM categories c
    LEFT JOIN transactions t ON t.category_id = c.id AND t.user_id = ? AND t.type = 'expense' AND $dateSql = ?
    WHERE c.type = 'expense' AND (c.user_id IS NULL OR c.user_id = ?)
    GROUP BY c.id, c.name, c.color, c.icon, c.budget_limit
    ORDER BY current_spent DESC
");
$stmtCat->execute([$userId, $currentMonth, $userId]);
$categories = $stmtCat->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-header">
        <div>
            <h1 class="page-title">Category Budget Caps</h1>
            <p style="color: var(--text-secondary); font-size:0.9rem; margin-top:4px;">Set and enforce spending limits per expense category for <?php echo date('F Y'); ?></p>
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
                        <div style="font-weight:800; font-size:0.95rem; display:flex; align-items:center; gap:8px;">
                            <i data-lucide="bell" style="width:16px; color:var(--accent-primary);"></i> Notifications
                        </div>
                        <span class="badge" style="background:rgba(99,102,241,0.2); color:var(--accent-primary);"><?php echo count($notifications); ?> Active</span>
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

    <!-- Budgets Form Card -->
    <div class="card">
        <form action="budgets.php" method="POST">
            <div class="grid-4" style="margin-bottom:0;">
                <?php foreach ($categories as $cat): ?>
                    <?php 
                        $spent = (float)$cat['current_spent'];
                        $limit = (float)$cat['budget_limit'];
                        $percent = $limit > 0 ? min(100, round(($spent / $limit) * 100, 1)) : 0;
                        $isOver = $limit > 0 && $spent > $limit;
                    ?>
                    <div style="background:rgba(15,23,42,0.6); padding:20px; border-radius:var(--radius-sm); border:1px solid var(--border-glass);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span class="category-tag" style="border-left: 3px solid <?php echo $cat['color']; ?>;">
                                <i data-lucide="<?php echo htmlspecialchars($cat['icon'] ?: 'tag'); ?>" style="width:14px; height:14px; margin-right:4px;"></i>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </span>
                            <?php if ($isOver): ?>
                                <span class="badge" style="background:var(--danger-bg); color:var(--danger);">OVER BUDGET ⚠️</span>
                            <?php elseif ($limit > 0): ?>
                                <span class="badge" style="background:var(--success-bg); color:var(--success);"><?php echo $percent; ?>%</span>
                            <?php endif; ?>
                        </div>

                        <div style="font-size:1.1rem; font-weight:800; margin-bottom:8px;">
                            Spent: <?php echo formatCurrency($spent, $currency); ?>
                        </div>

                        <div class="form-group" style="margin-bottom:12px;">
                            <label class="form-label">Monthly Limit (<?php echo $currency; ?>)</label>
                            <input type="number" step="10" name="budgets[<?php echo $cat['id']; ?>]" class="form-control" value="<?php echo $limit; ?>" placeholder="Set cap...">
                        </div>

                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?php echo $percent; ?>%; background: <?php echo $isOver ? 'var(--danger)' : 'var(--accent-primary)'; ?>;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="display:flex; justify-content:flex-end; margin-top:24px;">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i> Save Budget Caps
                </button>
            </div>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
