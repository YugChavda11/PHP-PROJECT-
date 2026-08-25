<?php
// export.php - Multi-Format Exporter (Excel, CSV, PDF, Print)
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

checkAuth();
$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

$format = strtolower($_GET['format'] ?? 'csv');

// Handle PDF & Print Report
if (in_array($format, ['pdf', 'print'])) {
    header("Location: print_report.php?auto_print=1");
    exit;
}

// Fetch user transactions
$stmt = $pdo->prepare("
    SELECT t.id, t.date, t.type, c.name as category, t.amount, t.payment_method, t.description 
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? 
    ORDER BY t.date DESC
");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll();

if ($format === 'excel') {
    $filename = "financial_statement_" . date('Y-m-d') . ".csv";
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    // Write UTF-8 BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");

    // Header row
    fputcsv($output, ['ID', 'Date', 'Type', 'Category', 'Amount', 'Payment Method', 'Description']);

    foreach ($transactions as $tx) {
        fputcsv($output, [
            $tx['id'],
            $tx['date'],
            strtoupper($tx['type']),
            $tx['category'],
            number_format((float)$tx['amount'], 2, '.', ''),
            $tx['payment_method'],
            $tx['description']
        ]);
    }
    fclose($output);
    exit;
}

// Default CSV export
$filename = "transactions_export_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fputs($output, "\xEF\xBB\xBF");
fputcsv($output, ['ID', 'Date', 'Type', 'Category', 'Amount', 'Payment Method', 'Description']);

foreach ($transactions as $tx) {
    fputcsv($output, [
        $tx['id'],
        $tx['date'],
        strtoupper($tx['type']),
        $tx['category'],
        $tx['amount'],
        $tx['payment_method'],
        $tx['description']
    ]);
}

fclose($output);
exit;
