<?php
require_once 'includes/public_header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Initialize variables for the form
$email = '';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        die('CSRF token validation failed.');
    }

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $db = new Database();
        $stmt = $db->query("SELECT * FROM users WHERE email = ?", [$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            
            // Regerate session ID to prevent fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];

            // Secure session
            secureSession();

            // Redirect to intended page or dashboard
            $redirect_url = getRedirectUrl();
            header('Location: ' . $redirect_url);
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sentinel Cameroon</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Public+Sans:wght@600;700;800;900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">
</head>

<body class="bg-surface">
    <div class="min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="card">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-primary mb-2">Sentinel Cameroon</h1>
                    <p class="text-gray-600">Community Safety Platform</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" class="space-y-6">
                    <?php csrfInput(); ?>
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" required
                            value="<?php echo htmlspecialchars($email); ?>"
                            placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-input" required
                            placeholder="Enter your password">
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="mr-2">
                            <span class="text-sm">Remember me</span>
                        </label>
                        <a href="forgot_password.php" class="text-sm text-primary hover:underline">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">
                        Sign In
                    </button>
                </form>

                <div class="text-center mt-6">
                    <p class="text-sm text-gray-600">
                        Don't have an account?
                        <a href="register.php" class="text-primary hover:underline font-medium">Sign up</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>