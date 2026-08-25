<?php
// index.php - Smart Expense Tracker Dashboard with Real-Time Notifications
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/notifications.php';

$pdo = getDBConnection();
checkAuth($pdo);
$currentUser = getCurrentUser($pdo);
if (!$currentUser) {
    header("Location: auth/login.php");
    exit;
}
$userId = $_SESSION['user_id'];
$currency = $currentUser['currency_symbol'] ?? '$';

$currentPage = 'dashboard';
$pageTitle = 'Dashboard';

// Fetch Notifications
$notifications = getUserNotifications($pdo, $userId);

// Current Month Metrics
$currentMonth = date('Y-m');
$daysInMonth = (int)date('t');
$currentDay = (int)date('j');
$dateSql = getMonthSql('date');

// Total Income this month
$stmtInc = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'income' AND $dateSql = ?");
$stmtInc->execute([$userId, $currentMonth]);
$monthlyIncome = (float)($stmtInc->fetchColumn() ?: 0);

// Total Expense this month
$stmtExp = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'expense' AND $dateSql = ?");
$stmtExp->execute([$userId, $currentMonth]);
$monthlyExpense = (float)($stmtExp->fetchColumn() ?: 0);

$netBalance = $monthlyIncome - $monthlyExpense;
$savingsRate = $monthlyIncome > 0 ? max(0, round(($netBalance / $monthlyIncome) * 100, 1)) : 0;

// Financial Health Index Calculation (0 - 100)
$userMonthlyBudget = (float)($currentUser['monthly_budget'] ?? 2000);
$budgetUsagePercent = $userMonthlyBudget > 0 ? ($monthlyExpense / $userMonthlyBudget) * 100 : 0;

$healthScore = 70; // baseline
if ($monthlyIncome > 0) {
    if ($savingsRate >= 30) $healthScore += 20;
    elseif ($savingsRate >= 15) $healthScore += 10;
    elseif ($savingsRate < 0) $healthScore -= 25;
}
if ($budgetUsagePercent > 100) $healthScore -= 30;
elseif ($budgetUsagePercent > 80) $healthScore -= 15;

$healthScore = min(100, max(5, $healthScore));

// Recent Transactions with Custom Category Icons
$stmtRecent = $pdo->prepare("
    SELECT t.*, c.name as category_name, c.color as category_color, c.icon as category_icon 
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ?
    ORDER BY t.date DESC, t.id DESC
    LIMIT 6
");
$stmtRecent->execute([$userId]);
$recentTransactions = $stmtRecent->fetchAll();

// Categories for modal drop-down
$stmtCat = $pdo->prepare("SELECT * FROM categories WHERE user_id IS NULL OR user_id = ? ORDER BY type, name");
$stmtCat->execute([$userId]);
$categories = $stmtCat->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="main-content">
    <!-- Top Header with Notification Bell -->
    <div class="top-header">
        <div>
            <h1 class="page-title">Welcome back, <?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?> 👋</h1>
            <p style="color: var(--text-secondary); font-size:0.9rem; margin-top:4px;">Here is your financial summary for <?php echo date('F Y'); ?></p>
        </div>
        <div class="header-actions" style="align-items:center;">
            <!-- Notification Bell Component -->
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

            <button class="btn btn-primary" data-modal-target="addTransactionModal">
                <i data-lucide="plus-circle"></i> Add Transaction
            </button>
            <a href="export.php?format=excel" class="btn btn-secondary" title="Export to MS Excel">
                <i data-lucide="file-spreadsheet"></i> Excel
            </a>
            <a href="print_report.php?auto_print=1" target="_blank" class="btn btn-secondary" title="Export PDF / Print Report">
                <i data-lucide="printer"></i> PDF / Print
            </a>
        </div>
    </div>

    <!-- Summary Metric Cards -->
    <div class="grid-4">
        <div class="card stat-card">
            <div>
                <div class="stat-title">Total Income</div>
                <div class="stat-value" style="color: var(--success);"><?php echo formatCurrency($monthlyIncome, $currency); ?></div>
            </div>
            <div class="stat-icon income">
                <i data-lucide="trending-up"></i>
            </div>
        </div>

        <div class="card stat-card">
            <div>
                <div class="stat-title">Total Expense</div>
                <div class="stat-value" style="color: var(--danger);"><?php echo formatCurrency($monthlyExpense, $currency); ?></div>
            </div>
            <div class="stat-icon expense">
                <i data-lucide="trending-down"></i>
            </div>
        </div>

        <div class="card stat-card">
            <div>
                <div class="stat-title">Net Savings</div>
                <div class="stat-value"><?php echo formatCurrency($netBalance, $currency); ?></div>
            </div>
            <div class="stat-icon balance">
                <i data-lucide="wallet"></i>
            </div>
        </div>

        <div class="card stat-card">
            <div>
                <div class="stat-title">Health Score</div>
                <div class="stat-value" style="color: <?php echo $healthScore >= 70 ? 'var(--success)' : ($healthScore >= 40 ? 'var(--warning)' : 'var(--danger)'); ?>;"><?php echo $healthScore; ?> <span style="font-size:1rem;">/ 100</span></div>
            </div>
            <div class="stat-icon health">
                <i data-lucide="activity"></i>
            </div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid-2">
        <!-- Monthly Trend Line Chart with 1M - 12M Range Selector -->
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:16px;">
                <h3 id="trendChartTitle" style="font-size:1.1rem; font-weight:700;">6-Month Income vs Expense</h3>
                <div style="display:flex; gap:4px; background:rgba(15,23,42,0.6); padding:3px; border-radius:var(--radius-sm); border:1px solid var(--border-color);">
                    <button class="btn btn-secondary btn-sm range-btn" data-range="1" style="padding:4px 8px; font-size:0.75rem;">1M</button>
                    <button class="btn btn-secondary btn-sm range-btn" data-range="3" style="padding:4px 8px; font-size:0.75rem;">3M</button>
                    <button class="btn btn-primary btn-sm range-btn active" data-range="6" style="padding:4px 8px; font-size:0.75rem;">6M</button>
                    <button class="btn btn-secondary btn-sm range-btn" data-range="12" style="padding:4px 8px; font-size:0.75rem;">12M</button>
                </div>
            </div>
            <div style="height: 260px; position: relative;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Expense Category Breakdown Doughnut Chart -->
        <div class="card">
            <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:16px;">Expense Breakdown</h3>
            <div style="height: 260px; position: relative;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Transactions Section -->
    <div class="card">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
            <h3 style="font-size:1.1rem; font-weight:700;">Recent Transactions</h3>
            <a href="transactions.php" class="btn btn-secondary btn-sm">View All <i data-lucide="chevron-right" style="width:14px;"></i></a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Payment Method</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTransactions)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; color:var(--text-muted); padding:32px;">No transactions recorded yet. Click "Add Transaction" to start!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentTransactions as $tx): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($tx['date'])); ?></td>
                                <td>
                                    <span class="category-tag" style="border-left: 3px solid <?php echo $tx['category_color']; ?>;">
                                        <i data-lucide="<?php echo htmlspecialchars($tx['category_icon'] ?: 'tag'); ?>" style="width:14px; height:14px; margin-right:4px;"></i>
                                        <?php echo htmlspecialchars($tx['category_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($tx['description'] ?: '-'); ?></td>
                                <td><span style="color:var(--text-secondary); font-size:0.85rem;"><?php echo htmlspecialchars($tx['payment_method']); ?></span></td>
                                <td>
                                    <span class="badge <?php echo $tx['type'] === 'income' ? 'badge-income' : 'badge-expense'; ?>">
                                        <?php echo $tx['type'] === 'income' ? '+' : '-'; ?>
                                        <?php echo formatCurrency($tx['amount'], $currency); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="delete_transaction.php?id=<?php echo $tx['id']; ?>" onclick="return confirm('Are you sure you want to delete this transaction?');" style="color:var(--danger); text-decoration:none;">
                                        <i data-lucide="trash-2" style="width:16px;"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add Transaction Modal -->
<div class="modal-overlay" id="addTransactionModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add Transaction</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="add_transaction.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Description / Payee (Smart Auto-Complete)</label>
                <input type="text" name="description" class="form-control smart-desc-input" placeholder="e.g. Starbucks coffee, Uber ride, Salary..." required autocomplete="off">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control" required>
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount (<?php echo $currency; ?>)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">Select Category...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                [<?php echo strtoupper($cat['type']); ?>] <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <option value="Cash">Cash</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="UPI / Online">UPI / Online</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Receipt Image (Optional)</label>
                    <input type="file" name="receipt_image" class="form-control" accept="image/*,.pdf">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Transaction</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let trendChartInstance = null;

    function loadTrendChart(range = 6) {
        fetch(`api.php?action=chart_monthly_trends&range=${range}`)
            .then(res => res.json())
            .then(data => {
                const titleElem = document.getElementById('trendChartTitle');
                if (titleElem) {
                    titleElem.textContent = `${range}-Month Income vs Expense`;
                }

                const ctx = document.getElementById('trendChart').getContext('2d');
                if (trendChartInstance) {
                    trendChartInstance.destroy();
                }

                trendChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Income',
                                data: data.income,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Expense',
                                data: data.expense,
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                fill: true,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { labels: { color: '#94a3b8' } }
                        },
                        scales: {
                            x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                            y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } }
                        }
                    }
                });
            });
    }

    // Initialize 6M chart on load
    loadTrendChart(6);

    // Range selector click handlers
    document.querySelectorAll('.range-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.range-btn').forEach(b => {
                b.classList.remove('active', 'btn-primary');
                b.classList.add('btn-secondary');
            });
            btn.classList.add('active', 'btn-primary');
            btn.classList.remove('btn-secondary');

            const selectedRange = parseInt(btn.dataset.range);
            loadTrendChart(selectedRange);
        });
    });

    // Fetch and render Doughnut Chart
    fetch('api.php?action=chart_category_breakdown')
        .then(res => res.json())
        .then(data => {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            if (data.data.length === 0) {
                ctx.font = '14px Plus Jakarta Sans';
                ctx.fillStyle = '#64748b';
                ctx.fillText('No expenses recorded this month', 40, 130);
                return;
            }
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.data,
                        backgroundColor: data.colors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { color: '#94a3b8' } }
                    }
                }
            });
        });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
