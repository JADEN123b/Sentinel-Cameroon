<?php
require_once 'includes/public_header.php';
require_once 'includes/mailer.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Initialize variables for the form
$errors = [];
$username = '';
$email = '';
$full_name = '';
$phone = '';
$organization = '';
$position = '';
$badge_number = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $organization = $_POST['organization'] ?? '';
    $position = $_POST['position'] ?? '';
    $badge_number = $_POST['badge_number'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($organization) || empty($position)) {
        $errors[] = 'All required fields must be filled.';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        $db = new Database();
        
        // Check if email already exists
        $stmt = $db->query("SELECT id FROM users WHERE email = ?", [$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email address already registered.';
        } else {
            // Create new authority user with a verification token
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $verification_token = bin2hex(random_bytes(32));

            $stmt = $db->query("
                INSERT INTO users (username, email, password_hash, full_name, phone, role, is_verified, verification_token) 
                VALUES (?, ?, ?, ?, ?, 'authority', 0, ?)
            ", [$username, $email, $password_hash, $full_name, $phone, $verification_token]);
            
            if ($stmt) {
                // Send welcome email
                sendWelcomeEmail($email, $full_name);

                // Send verification email
                $mail_sent = sendVerificationEmail($email, $full_name, $verification_token);
                if (!$mail_sent) {
                    error_log("[Authority Register] Verification email failed for: {$email}");
                }
                $_SESSION['pending_verification_email'] = $email;
                header('Location: authority_register.php?registered=1');
                exit;
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
$just_registered = isset($_GET['registered']) && $_GET['registered'] == '1';
$pending_email = $_SESSION['pending_verification_email'] ?? '';
unset($_SESSION['pending_verification_email']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authority Registration | Sentinel Cameroon</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Public+Sans:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
</head>
<body class="bg-surface">
    <div class="min-h-screen flex items-center justify-center py-8">
        <div class="w-full max-w-2xl">
            <div class="card">

                <?php if ($just_registered): ?>
                <div class="text-center">
                    <span class="material-symbols-outlined" style="font-size:72px;color:#1a73e8;font-variation-settings:'FILL' 1;">mark_email_unread</span>
                    <h1 class="text-2xl font-bold text-primary mt-4 mb-2">Check Your Inbox!</h1>
                    <p class="text-gray-600 mb-6">
                        We sent a verification link to
                        <?php if ($pending_email): ?>
                            <strong><?php echo htmlspecialchars($pending_email); ?></strong>.
                        <?php else: ?>
                            your email address.
                        <?php endif; ?>
                        Verify your email to activate your authority account — it will then be reviewed by our team.
                    </p>
                    <p class="text-sm text-gray-500 mb-6">Didn't get it? Check your spam folder.</p>
                    <a href="login.php" class="btn btn-primary">Go to Sign In</a>
                </div>
                <?php else: ?>

                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-primary mb-2">Authority Registration</h1>
                    <p class="text-gray-600">Register for emergency response and incident management access</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="authority_register.php" class="space-y-6">
                    <?php csrfInput(); ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input 
                                type="text" 
                                id="full_name" 
                                name="full_name" 
                                class="form-input" 
                                required
                                value="<?php echo htmlspecialchars($full_name); ?>"
                                placeholder="Enter your full name"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                required
                                value="<?php echo htmlspecialchars($email); ?>"
                                placeholder="Enter your official email"
                            >
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label for="username" class="form-label">Username *</label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-input" 
                                required
                                value="<?php echo htmlspecialchars($username); ?>"
                                placeholder="Choose a username"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                class="form-input"
                                value="<?php echo htmlspecialchars($phone); ?>"
                                placeholder="+237 XXX XXX XXX"
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="organization" class="form-label">Organization *</label>
                        <input 
                            type="text" 
                                id="organization" 
                                name="organization" 
                                class="form-input" 
                                required
                                value="<?php echo htmlspecialchars($organization); ?>"
                            placeholder="e.g., Cameroon National Police"
                        >
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label for="position" class="form-label">Position *</label>
                            <input 
                                type="text" 
                                id="position" 
                                name="position" 
                                class="form-input" 
                                required
                                value="<?php echo htmlspecialchars($position); ?>"
                                placeholder="e.g., Emergency Response Officer"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="badge_number" class="form-label">Badge Number</label>
                            <input 
                                type="text" 
                                id="badge_number" 
                                name="badge_number" 
                                class="form-input"
                                value="<?php echo htmlspecialchars($badge_number); ?>"
                                placeholder="Official badge number"
                            >
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label for="password" class="form-label">Password *</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                required
                                placeholder="Create a strong password"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-input" 
                                required
                                placeholder="Confirm your password"
                            >
                        </div>
                    </div>
                    
                    <div class="bg-surface-container-low p-4 rounded-lg">
                        <h4 class="font-bold mb-2">Authority Account Information</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• Authority accounts require verification before full access</li>
                            <li>• You'll be able to verify incidents and update status</li>
                            <li>• Access to admin dashboard and incident management tools</li>
                            <li>• Ability to broadcast alerts to specific geographic areas</li>
                            <li>• Priority support for emergency response coordination</li>
                        </ul>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">security</span>
                            Submit for Review
                        </button>
                        <a href="index.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
                
                <div class="text-center mt-6">
                    <p class="text-sm text-gray-600">
                        Already have an account? 
                        <a href="login.php" class="text-primary hover:underline font-medium">Sign in</a>
                    </p>
                </div>

                <?php endif; // end just_registered ?>
            </div>
        </div>
    </div>
</body>
</html>
