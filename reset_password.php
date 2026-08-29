<?php
//  auth/reset_password.php - Reset User Password with Token Validation
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

$pdo = getDBConnection();
$error = '';
$tokenValid = false;

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$token = sanitizeInput($_GET['token'] ?? $_POST['token'] ?? '');
$email = sanitizeInput($_GET['email'] ?? $_POST['email'] ?? '');

if (empty($token) || empty($email)) {
    $error = 'Invalid password reset request. Missing token or email parameters.';
} else {
    // Check if token exists and is not expired
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email, $token]);
    $resetRequest = $stmt->fetch();

    if (!$resetRequest) {
        $error = 'Invalid password reset token or request.';
    } elseif (strtotime($resetRequest['expires_at']) < time()) {
        $error = 'This password reset link has expired. Please request a new one.';
    } else {
        $tokenValid = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($password) || empty($confirmPassword)) {
            $error = 'Please enter and confirm your new password.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match. Please try again.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            // Find user
            $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $userStmt->execute([$email]);
            $user = $userStmt->fetch();

            if ($user) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $updateStmt->execute([$newHash, $user['id']]);

                // Delete used tokens
                $delStmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $delStmt->execute([$email]);

                // Log Activity
                logActivity($pdo, $user['id'], 'PASSWORD_RESET_SUCCESS', "Password successfully updated for: $email");

                setFlashPop('Password Reset Success', 'Your password has been updated successfully. Please sign in with your new password.', 'success');
                header("Location: login.php");
                exit;
            } else {
                $error = 'User account not found.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Smart Expense Tracker</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-card">
    <div class="auth-brand">
        <div class="brand-icon">
            <i data-lucide="shield-check"></i>
        </div>
        <h2 style="font-size:1.5rem; font-weight:800; margin-bottom:6px;">Reset Password</h2>
        <p style="color: var(--text-secondary); font-size:0.88rem;">Create a strong, new password for your account</p>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); padding:12px; border-radius: var(--radius-sm); font-size:0.88rem; margin-bottom:20px; font-weight:600;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($tokenValid): ?>
        <form action="reset_password.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="6">
            </div>

            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required minlength="6">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">
                Update Password <i data-lucide="check-circle" style="width:16px; margin-left:6px;"></i>
            </button>
        </form>
    <?php else: ?>
        <div style="text-align: center; margin-top: 20px;">
            <a href="forgot_password.php" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px;">
                Request New Password Link
            </a>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 24px; font-size: 0.88rem; color: var(--text-secondary);">
        Back to <a href="login.php" style="color: var(--accent-primary); text-decoration: none; font-weight: 700;">Sign In</a>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</body>
</html>
