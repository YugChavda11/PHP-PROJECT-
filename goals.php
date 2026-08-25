<?php
// goals.php - Savings Goals Tracker with Dynamic Pop Notifications
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

$currentPage = 'goals';
$pageTitle = 'Savings Goals';

// Notifications
$notifications = getUserNotifications($pdo, $userId);

$message = '';
$error = '';

// Add Goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_goal') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $target_amount = (float)($_POST['target_amount'] ?? 0);
    $current_amount = (float)($_POST['current_amount'] ?? 0);
    $target_date = $_POST['target_date'] ?? null;

    if (!empty($title) && $target_amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO savings_goals (user_id, title, target_amount, current_amount, target_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $target_amount, $current_amount, $target_date]);

        logActivity($pdo, $userId, 'ADD_SAVINGS_GOAL', "Created goal: $title ($target_amount)");
        setFlashPop('Savings Goal Created! 🎯', "Goal '$title' added successfully.");
    }
}

// Deposit to Goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deposit') {
    $goal_id = (int)($_POST['goal_id'] ?? 0);
    $deposit = (float)($_POST['deposit_amount'] ?? 0);

    if ($goal_id > 0 && $deposit > 0) {
        $stmt = $pdo->prepare("UPDATE savings_goals SET current_amount = current_amount + ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$deposit, $goal_id, $userId]);

        logActivity($pdo, $userId, 'DEPOSIT_SAVINGS_GOAL', "Deposited $deposit into goal #$goal_id");
        setFlashPop('Savings Deposit Added! 🎉', "Successfully deposited " . formatCurrency($deposit, $currency) . " into your goal.");
    }
}

// Delete Goal
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM savings_goals WHERE id = ? AND user_id = ?");
    $stmt->execute([$delId, $userId]);

    setFlashPop('Goal Removed! 🗑️', "Savings goal was removed.", 'warning');
}

// Fetch Goals
$stmtGoals = $pdo->prepare("SELECT * FROM savings_goals WHERE user_id = ? ORDER BY created_at DESC");
$stmtGoals->execute([$userId]);
$goals = $stmtGoals->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-header">
        <div>
            <h1 class="page-title">Savings Goals</h1>
            <p style="color: var(--text-secondary); font-size:0.9rem; margin-top:4px;">Set, track, and deposit towards your long-term financial milestones</p>
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

            <button class="btn btn-primary" data-modal-target="addGoalModal">
                <i data-lucide="target"></i> Add Savings Goal
            </button>
        </div>
    </div>

    <!-- Goals Grid -->
    <div class="grid-4">
        <?php if (empty($goals)): ?>
            <div class="card" style="grid-column: 1 / -1; text-align:center; padding:48px; color:var(--text-muted);">
                <i data-lucide="target" style="width:48px; height:48px; margin-bottom:16px; opacity:0.5;"></i>
                <h3>No savings goals created yet</h3>
                <p style="font-size:0.9rem; margin-top:8px;">Set up a savings goal for a vacation, emergency fund, or new purchase!</p>
            </div>
        <?php else: ?>
            <?php foreach ($goals as $g): ?>
                <?php 
                    $percent = min(100, round(($g['current_amount'] / $g['target_amount']) * 100, 1));
                    $isComplete = $g['current_amount'] >= $g['target_amount'];
                ?>
                <div class="card" style="display:flex; flex-direction:column; justify-space-between;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                            <h3 style="font-size:1.1rem; font-weight:800;"><?php echo sanitizeOutput($g['title']); ?></h3>
                            <?php if ($isComplete): ?>
                                <span class="badge" style="background:var(--success-bg); color:var(--success);">COMPLETED 🎉</span>
                            <?php else: ?>
                                <span class="badge" style="background:rgba(99,102,241,0.15); color:var(--accent-primary);"><?php echo $percent; ?>%</span>
                            <?php endif; ?>
                        </div>

                        <div style="font-size:1.5rem; font-weight:800; color: <?php echo $isComplete ? 'var(--success)' : 'var(--text-primary)'; ?>; margin-bottom:4px;">
                            <?php echo formatCurrency($g['current_amount'], $currency); ?>
                        </div>
                        <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:16px;">
                            Target: <?php echo formatCurrency($g['target_amount'], $currency); ?>
                            <?php if ($g['target_date']): ?> • Due <?php echo date('M d, Y', strtotime($g['target_date'])); ?><?php endif; ?>
                        </div>

                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?php echo $percent; ?>%; background: <?php echo $isComplete ? 'var(--success)' : 'var(--accent-primary)'; ?>;"></div>
                        </div>
                    </div>

                    <div style="display:flex; gap:8px; margin-top:24px;">
                        <button class="btn btn-secondary btn-sm deposit-btn" data-goal-id="<?php echo $g['id']; ?>" data-goal-title="<?php echo htmlspecialchars($g['title']); ?>" style="flex:1;">
                            <i data-lucide="plus" style="width:14px;"></i> Deposit
                        </button>
                        <a href="goals.php?delete_id=<?php echo $g['id']; ?>" onclick="return confirm('Delete this goal?');" class="btn btn-danger btn-sm">
                            <i data-lucide="trash-2" style="width:14px;"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Add Goal Modal -->
<div class="modal-overlay" id="addGoalModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Create Savings Goal</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="goals.php" method="POST">
            <input type="hidden" name="action" value="add_goal">

            <div class="form-group">
                <label class="form-label">Goal Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Vacation, New Laptop, Emergency Fund" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Target Amount (<?php echo $currency; ?>)</label>
                    <input type="number" step="0.01" name="target_amount" class="form-control" placeholder="1000.00" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Initial Amount (Optional)</label>
                    <input type="number" step="0.01" name="current_amount" class="form-control" placeholder="0.00" value="0">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Target Completion Date (Optional)</label>
                <input type="date" name="target_date" class="form-control">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Goal</button>
            </div>
        </form>
    </div>
</div>

<!-- Deposit Modal -->
<div class="modal-overlay" id="depositModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="depositGoalTitle">Deposit Savings</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="goals.php" method="POST">
            <input type="hidden" name="action" value="deposit">
            <input type="hidden" name="goal_id" id="deposit_goal_id">

            <div class="form-group">
                <label class="form-label">Deposit Amount (<?php echo $currency; ?>)</label>
                <input type="number" step="0.01" name="deposit_amount" class="form-control" placeholder="100.00" required>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Add Deposit</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const depositBtns = document.querySelectorAll('.deposit-btn');
    const depositModal = document.getElementById('depositModal');

    depositBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('deposit_goal_id').value = btn.dataset.goalId;
            document.getElementById('depositGoalTitle').textContent = `Deposit into: ${btn.dataset.goalTitle}`;
            if (depositModal) depositModal.classList.add('active');
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
