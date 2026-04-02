<?php
require_once 'includes/public_header.php';
require_once 'includes/mailer.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db = new Database();
        $stmt = $db->query("SELECT id, full_name, email FROM users WHERE email = ?", [$email]);
        $user = $stmt ? $stmt->fetch() : null;

        // Always show success — don't reveal if email exists (security)
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $db->query(
                "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?",
                [$token, $expires, $user['id']]
            );

            sendPasswordResetEmail($user['email'], $user['full_name'], $token);
        }

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Sentinel Cameroon</title>
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
                        <span class="material-symbols-outlined" style="font-size:72px;color:#1a73e8;font-variation-settings:'FILL' 1;">forward_to_inbox</span>
                        <h1 class="text-2xl font-bold text-primary mt-4 mb-2">Check Your Inbox</h1>
                        <p class="text-gray-600 mb-6">
                            If an account exists for that email address, we've sent a password reset link. The link expires in <strong>1 hour</strong>.
                        </p>
                        <p class="text-sm text-gray-500 mb-6">Didn't get it? Check your spam folder.</p>
                        <a href="login.php" class="btn btn-primary">Back to Sign In</a>
                    </div>
                <?php else: ?>
                    <div class="text-center mb-8">
                        <span class="material-symbols-outlined" style="font-size:48px;color:#1a73e8;">lock_reset</span>
                        <h1 class="text-2xl font-bold text-primary mt-3 mb-1">Forgot your password?</h1>
                        <p class="text-gray-600 text-sm">Enter your registered email and we'll send you a reset link.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-error mb-4"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="forgot_password.php" class="space-y-6">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-input"
                                required
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                placeholder="Enter your registered email"
                            >
                        </div>

                        <button type="submit" class="btn btn-primary w-full">
                            Send Reset Link
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
