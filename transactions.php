<?php
// Transactions.php - Full Transactions Management with Notifications Bell
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

$currentPage = 'transactions';
$pageTitle = 'Transactions';

// Fetch Notifications
$notifications = getUserNotifications($pdo, $userId);

// Filters
$search = trim($_GET['search'] ?? '');
$catFilter = (int)($_GET['category_id'] ?? 0);
$typeFilter = $_GET['type'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$sql = "
    SELECT t.*, c.name as category_name, c.color as category_color, c.icon as category_icon 
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ?
";
$params = [$userId];

if (!empty($search)) {
    $sql .= " AND (t.description LIKE ? OR c.name LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($catFilter > 0) {
    $sql .= " AND t.category_id = ?";
    $params[] = $catFilter;
}
if (!empty($typeFilter) && in_array($typeFilter, ['income', 'expense'])) {
    $sql .= " AND t.type = ?";
    $params[] = $typeFilter;
}
if (!empty($startDate)) {
    $sql .= " AND t.date >= ?";
    $params[] = $startDate;
}
if (!empty($endDate)) {
    $sql .= " AND t.date <= ?";
    $params[] = $endDate;
}

$sql .= " ORDER BY t.date DESC, t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Fetch categories for filter dropdown & modal
$stmtCat = $pdo->prepare("SELECT * FROM categories WHERE user_id IS NULL OR user_id = ? ORDER BY type, name");
$stmtCat->execute([$userId]);
$categories = $stmtCat->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-header">
        <div>
            <h1 class="page-title">Transaction History</h1>
            <p style="color: var(--text-secondary); font-size:0.9rem; margin-top:4px;">Manage, edit, filter, and track all your income and expenses</p>
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
            <a href="export.php?format=excel" class="btn btn-secondary" title="Export Excel File">
                <i data-lucide="file-spreadsheet"></i> Export Excel
            </a>
            <a href="print_report.php?auto_print=1" target="_blank" class="btn btn-secondary" title="Export PDF / Print">
                <i data-lucide="printer"></i> Export PDF / Print
            </a>
        </div>
    </div>

    <!-- Filter Form Bar -->
    <div class="card" style="margin-bottom: 24px; padding: 18px 24px;">
        <form action="transactions.php" method="GET" style="display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:2; min-width: 180px;">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search description or payee..." value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div style="flex:1; min-width: 140px;">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-control">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $catFilter === (int)$cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex:1; min-width: 120px;">
                <label class="form-label">Type</label>
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <option value="expense" <?php echo $typeFilter === 'expense' ? 'selected' : ''; ?>>Expenses Only</option>
                    <option value="income" <?php echo $typeFilter === 'income' ? 'selected' : ''; ?>>Income Only</option>
                </select>
            </div>

            <div style="flex:1; min-width: 130px;">
                <label class="form-label">From Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
            </div>

            <div style="flex:1; min-width: 130px;">
                <label class="form-label">To Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
            </div>

            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-secondary">Filter</button>
                <?php if (!empty($search) || $catFilter > 0 || !empty($typeFilter) || !empty($startDate) || !empty($endDate)): ?>
                    <a href="transactions.php" class="btn btn-secondary" style="color:var(--text-muted);">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Method</th>
                        <th>Receipt</th>
                        <th>Amount</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; color:var(--text-muted); padding:32px;">No matching transactions found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($tx['date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $tx['type'] === 'income' ? 'badge-income' : 'badge-expense'; ?>">
                                        <?php echo strtoupper($tx['type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="category-tag" style="border-left: 3px solid <?php echo $tx['category_color']; ?>;">
                                        <i data-lucide="<?php echo htmlspecialchars($tx['category_icon'] ?: 'tag'); ?>" style="width:14px; height:14px; margin-right:4px;"></i>
                                        <?php echo htmlspecialchars($tx['category_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($tx['description'] ?: '-'); ?></td>
                                <td><span style="color:var(--text-secondary); font-size:0.85rem;"><?php echo htmlspecialchars($tx['payment_method']); ?></span></td>
                                <td>
                                    <?php if ($tx['receipt_image']): ?>
                                        <a href="uploads/<?php echo htmlspecialchars($tx['receipt_image']); ?>" target="_blank" class="btn btn-secondary btn-sm" style="padding:2px 8px; font-size:0.75rem;">
                                            <i data-lucide="paperclip" style="width:12px;"></i> View
                                        </a>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.8rem;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight:700; color: <?php echo $tx['type'] === 'income' ? 'var(--success)' : 'var(--danger)'; ?>;">
                                    <?php echo $tx['type'] === 'income' ? '+' : '-'; ?>
                                    <?php echo formatCurrency($tx['amount'], $currency); ?>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; gap:6px;">
                                        <button class="btn btn-secondary btn-sm edit-tx-btn"
                                                data-id="<?php echo $tx['id']; ?>"
                                                data-desc="<?php echo htmlspecialchars($tx['description']); ?>"
                                                data-type="<?php echo $tx['type']; ?>"
                                                data-amount="<?php echo $tx['amount']; ?>"
                                                data-catid="<?php echo $tx['category_id']; ?>"
                                                data-date="<?php echo $tx['date']; ?>"
                                                data-method="<?php echo htmlspecialchars($tx['payment_method']); ?>">
                                            <i data-lucide="edit-3" style="width:14px;"></i> Edit
                                        </button>
                                        <a href="delete_transaction.php?id=<?php echo $tx['id']; ?>" onclick="return confirm('Are you sure you want to delete this transaction?');" class="btn btn-danger btn-sm">
                                            <i data-lucide="trash-2" style="width:14px;"></i> Delete
                                        </a>
                                    </div>
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

<!-- Edit Transaction Modal -->
<div class="modal-overlay" id="editTransactionModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Transaction</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="edit_transaction.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="transaction_id" id="edit_tx_id">
            <div class="form-group">
                <label class="form-label">Description / Payee</label>
                <input type="text" name="description" id="edit_tx_desc" class="form-control" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" id="edit_tx_type" class="form-control" required>
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount (<?php echo $currency; ?>)</label>
                    <input type="number" step="0.01" name="amount" id="edit_tx_amount" class="form-control" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" id="edit_tx_catid" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                [<?php echo strtoupper($cat['type']); ?>] <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" id="edit_tx_date" class="form-control" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" id="edit_tx_method" class="form-control">
                        <option value="Cash">Cash</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="UPI / Online">UPI / Online</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Replace Receipt (Optional)</label>
                    <input type="file" name="receipt_image" class="form-control" accept="image/*,.pdf">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Update Transaction</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const editBtns = document.querySelectorAll('.edit-tx-btn');
    const editModal = document.getElementById('editTransactionModal');

    editBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_tx_id').value = btn.dataset.id;
            document.getElementById('edit_tx_desc').value = btn.dataset.desc;
            document.getElementById('edit_tx_type').value = btn.dataset.type;
            document.getElementById('edit_tx_amount').value = btn.dataset.amount;
            document.getElementById('edit_tx_catid').value = btn.dataset.catid;
            document.getElementById('edit_tx_date').value = btn.dataset.date;
            document.getElementById('edit_tx_method').value = btn.dataset.method;

            if (editModal) {
                editModal.classList.add('active');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
