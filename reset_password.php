<?php
require_once 'includes/public_header.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$token = $_GET['token'] ?? '';
$success = false;
$error = '';
$valid_token = false;
$user = null;

// Validate token on load
if (empty($token)) {
    $error = 'Invalid or missing reset link.';
} else {
    $db = new Database();
    $stmt = $db->query(
        "SELECT id, full_name, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW()",
        [$token]
    );
    $user = $stmt ? $stmt->fetch() : null;

    if (!$user) {
        $error = 'This password reset link is invalid or has expired. Please request a new one.';
    } else {
        $valid_token = true;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = new Database();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $db->query(
            "UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?",
            [$hash, $user['id']]
        );
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Sentinel Cameroon</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Public+Sans:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
</head>
<body class="bg-surface">
    <div class="min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="card">

                <?php if ($success): ?>
                    <div class="text-center">
                        <span class="material-symbols-outlined" style="font-size:72px;color:#16a34a;font-variation-settings:'FILL' 1;">check_circle</span>
                        <h1 class="text-2xl font-bold text-primary mt-4 mb-2">Password Updated!</h1>
                        <p class="text-gray-600 mb-6">Your password has been changed successfully. You can now sign in with your new password.</p>
                        <a href="login.php" class="btn btn-primary">Sign In Now</a>
                    </div>

                <?php elseif (!$valid_token): ?>
                    <div class="text-center">
                        <span class="material-symbols-outlined" style="font-size:72px;color:#dc2626;font-variation-settings:'FILL' 1;">cancel</span>
                        <h1 class="text-2xl font-bold text-primary mt-4 mb-2">Link Expired</h1>
                        <div class="alert alert-error mb-6"><?php echo htmlspecialchars($error); ?></div>
                        <a href="forgot_password.php" class="btn btn-primary">Request New Link</a>
                    </div>

                <?php else: ?>
                    <div class="text-center mb-8">
                        <span class="material-symbols-outlined" style="font-size:48px;color:#1a73e8;">lock_reset</span>
                        <h1 class="text-2xl font-bold text-primary mt-3 mb-1">Set New Password</h1>
                        <p class="text-gray-600 text-sm">Hi <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>, choose a new password below.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-error mb-4"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="reset_password.php?token=<?php echo urlencode($token); ?>" class="space-y-6">
                        <div class="form-group">
                            <label for="password" class="form-label">New Password</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input"
                                required
                                minlength="8"
                                placeholder="At least 8 characters"
                            >
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-input"
                                required
                                placeholder="Repeat your new password"
                            >
                        </div>

                        <button type="submit" class="btn btn-primary w-full">
                            Update Password
                        </button>
                    </form>

                    <div class="text-center mt-6">
                        <a href="login.php" class="text-sm text-gray-500 hover:underline">← Back to Sign In</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>
