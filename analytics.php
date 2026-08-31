<?php
// analytics.php - Detailed Financial Reports & Visual Analytics with 1M to 12M Range Selector 
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

$pdo = getDBConnection();
checkAuth($pdo);
$currentUser = getCurrentUser($pdo);
if (!$currentUser) {
    header("Location: auth/login.php");
    exit;
}
$userId = $_SESSION['user_id'];
$currency = $currentUser['currency_symbol'] ?? '$';

$currentPage = 'analytics';
$pageTitle = 'Analytics & Insights';

// Category Breakdown Query
$stmtCat = $pdo->prepare("
    SELECT c.name, c.color, c.icon, SUM(t.amount) as total_amount, COUNT(t.id) as tx_count 
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? AND t.type = 'expense' 
    GROUP BY c.id, c.name, c.color, c.icon 
    ORDER BY total_amount DESC
");
$stmtCat->execute([$userId]);
$catStats = $stmtCat->fetchAll();

$totalExpense = array_sum(array_column($catStats, 'total_amount')) ?: 1;

// Payment Method Breakdown
$stmtMethod = $pdo->prepare("
    SELECT payment_method, SUM(amount) as total_amount 
    FROM transactions 
    WHERE user_id = ? AND type = 'expense' 
    GROUP BY payment_method 
    ORDER BY total_amount DESC
");
$stmtMethod->execute([$userId]);
$methodStats = $stmtMethod->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-header">
        <div>
            <h1 class="page-title">Analytics & Insights</h1>
            <p style="color: var(--text-secondary); font-size:0.9rem; margin-top:4px;">Deep-dive inspection of your spending patterns, multi-month trends, and payment habits</p>
        </div>
        <div class="header-actions">
            <a href="print_report.php?auto_print=1" target="_blank" class="btn btn-primary">
                <i data-lucide="printer"></i> PDF / Print Report
            </a>
            <a href="export.php?format=excel" class="btn btn-secondary">
                <i data-lucide="file-spreadsheet"></i> Export Excel
            </a>
        </div>
    </div>

    <!-- Multi-Month Income vs Expense Line Chart Card -->
    <div class="card" style="margin-bottom: 24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:16px;">
            <h3 id="analyticsChartTitle" style="font-size:1.1rem; font-weight:700;">6-Month Income vs Expense Trend</h3>
            <div style="display:flex; gap:4px; background:rgba(15,23,42,0.6); padding:3px; border-radius:var(--radius-sm); border:1px solid var(--border-color);">
                <button class="btn btn-secondary btn-sm analytics-range-btn" data-range="1" style="padding:4px 10px; font-size:0.78rem;">1M</button>
                <button class="btn btn-secondary btn-sm analytics-range-btn" data-range="3" style="padding:4px 10px; font-size:0.78rem;">3M</button>
                <button class="btn btn-primary btn-sm analytics-range-btn active" data-range="6" style="padding:4px 10px; font-size:0.78rem;">6M</button>
                <button class="btn btn-secondary btn-sm analytics-range-btn" data-range="12" style="padding:4px 10px; font-size:0.78rem;">12M</button>
            </div>
        </div>
        <div style="height: 280px; position: relative;">
            <canvas id="analyticsTrendChart"></canvas>
        </div>
    </div>

    <!-- Analytics Cards Grid -->
    <div class="grid-2">
        <!-- Top Spending Categories Table -->
        <div class="card">
            <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:20px;">Top Spending Categories</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Tx Count</th>
                            <th>Total Spent</th>
                            <th>Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($catStats)): ?>
                            <tr>
                                <td colspan="4" style="text-align:center; color:var(--text-muted); padding:32px;">No expense data yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($catStats as $stat): ?>
                                <?php $share = round(($stat['total_amount'] / $totalExpense) * 100, 1); ?>
                                <tr>
                                    <td>
                                        <span class="category-tag" style="border-left: 3px solid <?php echo $stat['color']; ?>;">
                                            <i data-lucide="<?php echo htmlspecialchars($stat['icon'] ?: 'tag'); ?>" style="width:14px; height:14px; margin-right:4px;"></i>
                                            <?php echo htmlspecialchars($stat['name']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $stat['tx_count']; ?></td>
                                    <td style="font-weight:700;"><?php echo formatCurrency($stat['total_amount'], $currency); ?></td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <span style="font-size:0.82rem; font-weight:600; width:40px;"><?php echo $share; ?>%</span>
                                            <div class="progress-bar-bg" style="flex:1; height:6px; margin:0;">
                                                <div class="progress-bar-fill" style="width:<?php echo $share; ?>%; background:<?php echo $stat['color']; ?>;"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Method Breakdown -->
        <div class="card">
            <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:20px;">Spending by Payment Method</h3>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <?php if (empty($methodStats)): ?>
                    <div style="text-align:center; color:var(--text-muted); padding:32px;">No payment data recorded.</div>
                <?php else: ?>
                    <?php foreach ($methodStats as $m): ?>
                        <?php $mShare = round(($m['total_amount'] / $totalExpense) * 100, 1); ?>
                        <div style="background:rgba(15, 23, 42, 0.6); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color);">
                            <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-weight:600;">
                                <span><?php echo htmlspecialchars($m['payment_method']); ?></span>
                                <span><?php echo formatCurrency($m['total_amount'], $currency); ?> (<?php echo $mShare; ?>%)</span>
                            </div>
                            <div class="progress-bar-bg" style="height:6px; margin:0;">
                                <div class="progress-bar-fill" style="width:<?php echo $mShare; ?>%; background:var(--accent-primary);"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let analyticsChartInstance = null;

    function loadAnalyticsChart(range = 6) {
        fetch(`api.php?action=chart_monthly_trends&range=${range}`)
            .then(res => res.json())
            .then(data => {
                const titleElem = document.getElementById('analyticsChartTitle');
                if (titleElem) {
                    titleElem.textContent = `${range}-Month Income vs Expense Trend`;
                }

                const ctx = document.getElementById('analyticsTrendChart').getContext('2d');
                if (analyticsChartInstance) {
                    analyticsChartInstance.destroy();
                }

                analyticsChartInstance = new Chart(ctx, {
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

    loadAnalyticsChart(6);

    document.querySelectorAll('.analytics-range-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.analytics-range-btn').forEach(b => {
                b.classList.remove('active', 'btn-primary');
                b.classList.add('btn-secondary');
            });
            btn.classList.add('active', 'btn-primary');
            btn.classList.remove('btn-secondary');

            const selectedRange = parseInt(btn.dataset.range);
            loadAnalyticsChart(selectedRange);
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
