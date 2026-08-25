<?php
// auth/forgot_password.php - Request Password Reset Token
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

$pdo = getDBConnection();
$error = '';
$success = '';
$simulatedLink = '';

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');

        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && ($user['status'] ?? 'active') !== 'inactive') {
                    // Generate secure token
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    // Remove existing tokens for this email
                    $delStmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                    $delStmt->execute([$email]);

                    // Insert new token
                    $insStmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                    $insStmt->execute([$email, $token, $expiresAt]);

                    // Log activity
                    logActivity($pdo, $user['id'], 'PASSWORD_RESET_REQUESTED', "Password reset requested for: $email");

                    // Protocol & Host building
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                    $simulatedLink = "{$scheme}://{$host}{$dir}/reset_password.php?token={$token}&email=" . urlencode($email);

                    $success = 'Password reset token created successfully!';
                } else {
                    // Standard security response to prevent user enumeration
                    $success = 'If an active account exists with that email, reset instructions have been generated.';
                }
            }
        } else {
            $error = 'Please enter your email address.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Smart Expense Tracker</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-card">
    <div class="auth-brand">
        <div class="brand-icon">
            <i data-lucide="key-round"></i>
        </div>
        <h2 style="font-size:1.5rem; font-weight:800; margin-bottom:6px;">Forgot Password?</h2>
        <p style="color: var(--text-secondary); font-size:0.88rem;">Enter your email to receive password reset instructions</p>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); padding:12px; border-radius: var(--radius-sm); font-size:0.88rem; margin-bottom:20px; font-weight:600;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding:12px; border-radius: var(--radius-sm); font-size:0.88rem; margin-bottom:20px; font-weight:600;">
            <i data-lucide="check-circle" style="width:16px; height:16px; display:inline-block; vertical-align:middle; margin-right:6px;"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($simulatedLink)): ?>
        <div style="background: rgba(99, 102, 241, 0.1); border: 1px solid var(--accent-primary); border-radius: var(--radius-sm); padding:14px; margin-bottom:20px; text-align:left;">
            <div style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:700; color:var(--accent-primary); margin-bottom:6px; display:flex; align-items:center; gap:6px;">
                <i data-lucide="mail" style="width:14px; height:14px;"></i> Simulated Email Reset Link
            </div>
            <p style="font-size:0.82rem; color:var(--text-secondary); margin-bottom:10px;">In a live production environment, this link is emailed to the user. Click below to reset your password:</p>
            <a href="<?php echo htmlspecialchars($simulatedLink); ?>" style="word-break: break-all; font-size:0.84rem; font-weight:700; color:var(--accent-primary); text-decoration:underline; display:block;">
                Click here to reset your password
            </a>
        </div>
    <?php endif; ?>

    <form action="forgot_password.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="user@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">
            Send Reset Link <i data-lucide="send" style="width:16px; margin-left:6px;"></i>
        </button>
    </form>

    <div style="text-align: center; margin-top: 24px; font-size: 0.88rem; color: var(--text-secondary);">
        Remember your password? <a href="login.php" style="color: var(--accent-primary); text-decoration: none; font-weight: 700;">Back to Sign In</a>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</body>
</html>
