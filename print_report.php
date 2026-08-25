<?php
// print_report.php - Clean Printable PDF Financial Report
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

$currentMonth = date('Y-m');
$dateSql = getMonthSql('date');

// Metrics
$stmtInc = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'income' AND $dateSql = ?");
$stmtInc->execute([$userId, $currentMonth]);
$monthlyIncome = (float)($stmtInc->fetchColumn() ?: 0);

$stmtExp = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'expense' AND $dateSql = ?");
$stmtExp->execute([$userId, $currentMonth]);
$monthlyExpense = (float)($stmtExp->fetchColumn() ?: 0);
$netBalance = $monthlyIncome - $monthlyExpense;

// Transactions
$stmt = $pdo->prepare("
    SELECT t.*, c.name as category_name, c.color as category_color 
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? 
    ORDER BY t.date DESC
");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll();

// Category Breakdown
$stmtCat = $pdo->prepare("
    SELECT c.name, SUM(t.amount) as total 
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? AND t.type = 'expense' 
    GROUP BY c.id, c.name 
    ORDER BY total DESC
");
$stmtCat->execute([$userId]);
$catBreakdown = $stmtCat->fetchAll();

$isPdfDownload = isset($_GET['download_pdf']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Report - <?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1e293b;
            background: #fff;
            padding: 40px;
            margin: 0;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .report-title {
            font-size: 24px;
            font-weight: bold;
            color: #6366f1;
        }
        .report-meta {
            font-size: 14px;
            color: #64748b;
        }
        .metrics-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .metric-box {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 16px;
            border-radius: 8px;
        }
        .metric-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .metric-val {
            font-size: 20px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            font-size: 13px;
        }
        th {
            background: #f1f5f9;
            font-weight: bold;
            color: #475569;
        }
        .badge-income { color: #10b981; font-weight: bold; }
        .badge-expense { color: #ef4444; font-weight: bold; }
        .print-btn-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
        }
        .btn {
            background: #6366f1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        @media print {
            .print-btn-bar { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="print-btn-bar">
    <button onclick="window.print()" class="btn">🖨️ Print / Save as PDF</button>
    <button onclick="window.close()" class="btn" style="background:#64748b;">Close</button>
</div>

<div class="report-header">
    <div>
        <div class="report-title">Smart Expense Financial Report</div>
        <div class="report-meta">Generated for: <strong><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></strong> (<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>)</div>
    </div>
    <div style="text-align: right;" class="report-meta">
        <div>Date: <?php echo date('F d, Y'); ?></div>
        <div>Period: <?php echo date('F Y'); ?></div>
    </div>
</div>

<div class="metrics-grid">
    <div class="metric-box">
        <div class="metric-label">Monthly Income</div>
        <div class="metric-val" style="color:#10b981;"><?php echo formatCurrency($monthlyIncome, $currency); ?></div>
    </div>
    <div class="metric-box">
        <div class="metric-label">Monthly Expense</div>
        <div class="metric-val" style="color:#ef4444;"><?php echo formatCurrency($monthlyExpense, $currency); ?></div>
    </div>
    <div class="metric-box">
        <div class="metric-label">Net Savings</div>
        <div class="metric-val" style="color:#6366f1;"><?php echo formatCurrency($netBalance, $currency); ?></div>
    </div>
</div>

<h3>Transaction History Ledger</h3>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Category</th>
            <th>Description</th>
            <th>Payment Method</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($transactions as $tx): ?>
            <tr>
                <td><?php echo date('Y-m-d', strtotime($tx['date'])); ?></td>
                <td><span class="<?php echo $tx['type'] === 'income' ? 'badge-income' : 'badge-expense'; ?>"><?php echo strtoupper($tx['type']); ?></span></td>
                <td><?php echo htmlspecialchars($tx['category_name']); ?></td>
                <td><?php echo htmlspecialchars($tx['description'] ?: '-'); ?></td>
                <td><?php echo htmlspecialchars($tx['payment_method']); ?></td>
                <td class="<?php echo $tx['type'] === 'income' ? 'badge-income' : 'badge-expense'; ?>">
                    <?php echo $tx['type'] === 'income' ? '+' : '-'; ?><?php echo formatCurrency($tx['amount'], $currency); ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
<?php if (isset($_GET['auto_print'])): ?>
    window.onload = function() { window.print(); };
<?php endif; ?>
</script>
</body>
</html>
