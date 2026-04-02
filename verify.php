<?php
require_once 'includes/public_header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$message_type = 'error';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = 'Invalid verification link. No token provided.';
} else {
    $db = new Database();
    $stmt = $db->query("SELECT id, full_name, email, is_verified FROM users WHERE verification_token = ?", [$token]);
    $user = $stmt ? $stmt->fetch() : null;

    if (!$user) {
        $message = 'This verification link is invalid or has already been used.';
    } elseif ($user['is_verified']) {
        $message = 'Your email address is already verified. You can <a href="login.php" class="text-primary font-medium hover:underline">sign in</a>.';
        $message_type = 'info';
    } else {
        // Mark as verified and clear token
        $update = $db->query(
            "UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?",
            [$user['id']]
        );

        if ($update) {
            $message = 'Your email has been verified successfully! You can now <a href="login.php" class="text-primary font-medium hover:underline">sign in</a>.';
            $message_type = 'success';
        } else {
            $message = 'Verification failed due to a server error. Please try again or contact support.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | Sentinel Cameroon</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Public+Sans:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
</head>
<body class="bg-surface">
    <div class="min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="card text-center">
                <?php if ($message_type === 'success'): ?>
                    <div class="mb-6">
                        <span class="material-symbols-outlined text-green-500" style="font-size:64px;font-variation-settings:'FILL' 1;">check_circle</span>
                    </div>
                    <h1 class="text-2xl font-bold text-primary mb-4">Email Verified!</h1>
                    <div class="alert alert-success mb-6">
                        <?php echo $message; ?>
                    </div>
                    <a href="login.php" class="btn btn-primary">Sign In Now</a>
                <?php elseif ($message_type === 'info'): ?>
                    <div class="mb-6">
                        <span class="material-symbols-outlined text-blue-500" style="font-size:64px;font-variation-settings:'FILL' 1;">info</span>
                    </div>
                    <h1 class="text-2xl font-bold text-primary mb-4">Already Verified</h1>
                    <div class="alert" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;" class="mb-6">
                        <?php echo $message; ?>
                    </div>
                    <a href="login.php" class="btn btn-primary mt-4">Sign In</a>
                <?php else: ?>
                    <div class="mb-6">
                        <span class="material-symbols-outlined text-red-500" style="font-size:64px;font-variation-settings:'FILL' 1;">cancel</span>
                    </div>
                    <h1 class="text-2xl font-bold text-primary mb-4">Verification Failed</h1>
                    <div class="alert alert-error mb-6">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <a href="register.php" class="btn btn-primary">Try Registering Again</a>
                <?php endif; ?>

                <div class="mt-6">
                    <a href="index.php" class="text-sm text-gray-500 hover:underline">← Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
