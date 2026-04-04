<?php
require_once 'database/config.php';

$token = $_GET['token'] ?? '';
$message = '';
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $errors[] = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } else {
        $db = new Database();
        
        // Find the reset token
        $reset = $db->query("
            SELECT pr.*, u.email, u.full_name 
            FROM password_resets pr 
            JOIN users u ON pr.user_id = u.id 
            WHERE pr.token = ? AND pr.is_used = FALSE AND pr.expires_at > NOW()
        ", [$token])->fetch();
        
        if (!$reset) {
            $errors[] = "Invalid or expired reset link. Please request a new password reset.";
        } else {
            try {
                $db->beginTransaction();
                
                // Update user password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $db->query("UPDATE users SET password_hash = ? WHERE id = ?", [$password_hash, $reset['user_id']]);
                
                // Mark reset token as used
                $db->query("UPDATE password_resets SET is_used = TRUE WHERE id = ?", [$reset['id']]);
                
                // Log the email notification
                $db->query("
                    INSERT INTO email_notifications (user_id, email_type, email, subject, status, sent_at) 
                    VALUES (?, 'password_reset', ?, 'Password Reset Completed', 'sent', NOW())
                ", [$reset['user_id'], $reset['email']]);
                
                $db->commit();
                
                $success = true;
                $message = "Your password has been successfully reset!";
                
                // Send welcome email
                try {
                    require_once 'includes/phpmailer_email_service.php';
                    $emailService = new PHPMailerEmailService();
                    $emailService->sendWelcomeEmail($reset['email'], $reset['full_name']);
                } catch (Exception $e) {
                    error_log("Failed to send welcome email: " . $e->getMessage());
                }
            } catch (Exception $e) {
                $db->rollback();
                $errors[] = "An error occurred. Please try again.";
                error_log("Password reset error: " . $e->getMessage());
            }
        }
    }
} elseif (!empty($token)) {
    // Check if token is valid for GET request
    $db = new Database();
    $reset = $db->query("
        SELECT pr.*, u.email, u.full_name 
        FROM password_resets pr 
        JOIN users u ON pr.user_id = u.id 
        WHERE pr.token = ? AND pr.is_used = FALSE AND pr.expires_at > NOW()
    ", [$token])->fetch();
    
    if (!$reset) {
        $errors[] = "Invalid or expired reset link. Please request a new password reset.";
    }
} else {
    $errors[] = "No reset token provided. Please request a password reset from the login page.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Sentinel Cameroon</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .reset-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #9c3400;
            font-size: 28px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #9c3400;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: #9c3400;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn:hover {
            background: #7a2800;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .success-icon {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .success-icon .material-symbols-outlined {
            font-size: 60px;
            color: #28a745;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #9c3400;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="logo">
            <h1>
                <span class="material-symbols-outlined">shield_with_heart</span>
                Sentinel Cameroon
            </h1>
        </div>
        
        <?php if ($success): ?>
            <div class="success-icon">
                <span class="material-symbols-outlined">check_circle</span>
            </div>
            
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
            
            <a href="login.php" class="btn">
                <span class="material-symbols-outlined">login</span>
                Login with New Password
            </a>
            
        <?php else: ?>
            <h2 style="text-align: center; margin-bottom: 20px;">Reset Password</h2>
            
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (empty($errors) || $_SERVER['REQUEST_METHOD'] === 'GET'): ?>
                <form method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="password">
                            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 4px;">lock</span>
                            New Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input"
                            placeholder="Enter your new password (min. 8 characters)"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">
                            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 4px;">lock</span>
                            Confirm New Password
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input"
                            placeholder="Confirm your new password"
                            required
                        >
                    </div>
                    
                    <button type="submit" class="btn">
                        <span class="material-symbols-outlined">refresh</span>
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php">
                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_back</span>
                Back to Login
            </a>
        </div>
    </div>
</body>
</html>
