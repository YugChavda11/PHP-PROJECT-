<?php
// auth/login.php - Secure User Login with CSRF, Activity Logging & Deactivation Guards
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

$pdo = getDBConnection();
$error = '';

$flashPop = getFlashPop();
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!empty($email) && !empty($password)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if account is deactivated by admin
                if (isset($user['status']) && $user['status'] === 'inactive') {
                    $error = 'Your account has been deactivated by an administrator.';
                    logActivity($pdo, $user['id'], 'LOGIN_BLOCKED', 'Deactivated user attempt');
                } else {
                    // Prevent session fixation
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];

                    logActivity($pdo, $user['id'], 'USER_LOGIN', 'Successful user authentication');
                    header("Location: ../index.php");
                    exit;
                }
            } else {
                $error = 'Invalid email address or password.';
                logActivity($pdo, null, 'LOGIN_FAILED', "Failed login attempt for: $email");
            }
        } else {
            $error = 'Please fill in all fields.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Expense Tracker</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-card">
    <div class="auth-brand">
        <div class="brand-icon">
            <i data-lucide="wallet"></i>
        </div>
        <h2 style="font-size:1.5rem; font-weight:800; margin-bottom:6px;">Welcome Back</h2>
        <p style="color: var(--text-secondary); font-size:0.88rem;">Sign in to your Smart Expense Tracker</p>
    </div>

    <?php if (!empty($flashPop)): ?>
        <div style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding:12px; border-radius: var(--radius-sm); font-size:0.88rem; margin-bottom:20px; font-weight:600;">
            <strong><?php echo htmlspecialchars($flashPop['title']); ?>:</strong> <?php echo htmlspecialchars($flashPop['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); padding:12px; border-radius: var(--radius-sm); font-size:0.88rem; margin-bottom:20px; font-weight:600;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="user@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <div style="display:flex; justify-style:space-between; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                <label class="form-label" style="margin-bottom:0;">Password</label>
                <a href="forgot_password.php" style="color: var(--accent-primary); font-size: 0.8rem; font-weight: 600; text-decoration: none;">Forgot Password?</a>
            </div>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">
            Sign In <i data-lucide="arrow-right" style="width:16px;"></i>
        </button>
    </form>

    <div style="text-align: center; margin-top: 24px; font-size: 0.88rem; color: var(--text-secondary);">
        Don't have an account? <a href="register.php" style="color: var(--accent-primary); text-decoration: none; font-weight: 700;">Create One</a>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</body>
</html>
