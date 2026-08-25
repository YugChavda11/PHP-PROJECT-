<?php
// api.php - Backend API for Chart.js & Smart Dashboard Analytics (MySQL & SQLite compatible)
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$mSql = getMonthSql('t.date');

if ($action === 'chart_category_breakdown') {
    $currentMonth = date('Y-m');
    $stmt = $pdo->prepare("
        SELECT c.name, c.color, SUM(t.amount) as total 
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.type = 'expense' AND $mSql = ?
        GROUP BY c.id, c.name, c.color
        ORDER BY total DESC
    ");
    $stmt->execute([$userId, $currentMonth]);
    $results = $stmt->fetchAll();

    $labels = [];
    $data = [];
    $colors = [];

    foreach ($results as $row) {
        $labels[] = $row['name'];
        $data[] = (float)$row['total'];
        $colors[] = $row['color'] ?? '#6366f1';
    }

    echo json_encode([
        'labels' => $labels,
        'data' => $data,
        'colors' => $colors
    ]);
    exit;
}

if ($action === 'chart_monthly_trends') {
    $range = (int)($_GET['range'] ?? 6);
    if (!in_array($range, [1, 3, 6, 12])) {
        $range = 6;
    }

    $months = [];
    for ($i = ($range - 1); $i >= 0; $i--) {
        $months[] = date('Y-m', strtotime("-$i months"));
    }

    $incomeData = [];
    $expenseData = [];
    $labels = [];
    $rawMSql = getMonthSql('date');

    foreach ($months as $m) {
        $labels[] = date('M Y', strtotime($m . '-01'));

        // Income sum
        $stmtInc = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'income' AND $rawMSql = ?");
        $stmtInc->execute([$userId, $m]);
        $incomeData[] = (float)($stmtInc->fetchColumn() ?: 0);

        // Expense sum
        $stmtExp = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'expense' AND $rawMSql = ?");
        $stmtExp->execute([$userId, $m]);
        $expenseData[] = (float)($stmtExp->fetchColumn() ?: 0);
    }

    echo json_encode([
        'labels' => $labels,
        'income' => $incomeData,
        'expense' => $expenseData,
        'range' => $range
    ]);
    exit;
}

echo json_encode(['status' => 'ok']);
