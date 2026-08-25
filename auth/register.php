<?php
// auth/register.php
session_start();
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
$error = '';

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $currency = $_POST['currency_symbol'] ?? '$';

    if (!empty($name) && !empty($email) && !empty($password)) {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insert = $pdo->prepare("INSERT INTO users (name, email, password_hash, currency_symbol) VALUES (?, ?, ?, ?)");
            $insert->execute([$name, $email, $hash, $currency]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;

            header("Location: ../index.php");
            exit;
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Smart Expense Tracker</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-card">
    <div class="auth-brand">
        <div class="brand-icon">
            <i data-lucide="user-plus"></i>
        </div>
        <h2 style="font-size:1.5rem; font-weight:800; margin-bottom:6px;">Create Account</h2>
        <p style="color: var(--text-secondary); font-size:0.88rem;">Start tracking your spending intelligently</p>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); padding:12px; border-radius: var(--radius-sm); font-size:0.88rem; margin-bottom:20px; font-weight:600;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" placeholder="John Doe" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="john@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-group" style="max-width: 110px;">
                <label class="form-label">Currency</label>
                <select name="currency_symbol" class="form-control">
                    <option value="$">$ (USD)</option>
                    <option value="₹">₹ (INR)</option>
                    <option value="€">€ (EUR)</option>
                    <option value="£">£ (GBP)</option>
                    <option value="¥">¥ (JPY)</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">
            Create Free Account <i data-lucide="arrow-right" style="width:16px;"></i>
        </button>
    </form>

    <div style="text-align: center; margin-top: 24px; font-size: 0.88rem; color: var(--text-secondary);">
        Already have an account? <a href="login.php" style="color: var(--accent-primary); text-decoration: none; font-weight: 700;">Sign In</a>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</body>
</html>
