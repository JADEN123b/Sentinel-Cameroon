<?php
require_once 'database/config.php';

$message = '';
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $errors[] = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        $db = new Database();
        
        // Check if user exists
        $user = $db->query("SELECT id, full_name, email FROM users WHERE email = ?", [$email])->fetch();
        
        if (!$user) {
            // Don't reveal if email exists or not for security
            $success = true;
            $message = "If an account with that email exists, a password reset link has been sent.";
        } else {
            try {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Delete any existing reset tokens for this user
                $db->query("DELETE FROM password_resets WHERE user_id = ? OR email = ?", [$user['id'], $email]);
                
                // Insert new reset token
                $db->query("
                    INSERT INTO password_resets (user_id, email, token, expires_at) 
                    VALUES (?, ?, ?, ?)
                ", [$user['id'], $email, $token, $expires_at]);
                
                // Send password reset email
                try {
                    require_once 'includes/phpmailer_email_service.php';
                    $emailService = new PHPMailerEmailService();
                    
                    if ($emailService->sendPasswordResetEmail($email, $user['full_name'], $token)) {
                        $success = true;
                        $message = "A password reset link has been sent to your email address.";
                    } else {
                        $errors[] = "Failed to send password reset email. Please try again.";
                    }
                } catch (Exception $e) {
                    $errors[] = "An error occurred. Please try again.";
                    error_log("Password reset request error: " . $e->getMessage());
                }
            } catch (Exception $e) {
                $errors[] = "An error occurred. Please try again.";
                error_log("Password reset request error: " . $e->getMessage());
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
    <title>Forgot Password - Sentinel Cameroon</title>
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
        
        .forgot-container {
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
        
        .description {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
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
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
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
        
        .security-note {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="logo">
            <h1>
                <span class="material-symbols-outlined">shield_with_heart</span>
                Sentinel Cameroon
            </h1>
        </div>
        
        <?php if ($success): ?>
            <div class="success-icon">
                <span class="material-symbols-outlined">mail</span>
            </div>
            
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
            
            
            <div class="security-note">
                <strong>Security Notice:</strong><br>
                The password reset link will expire in 1 hour for your security. If you don't receive the email, please check your spam folder.
            </div>
            
        <?php else: ?>
            <h2 style="text-align: center; margin-bottom: 10px;">Forgot Password?</h2>
            
            <p class="description">
                Enter your email address and we'll send you a link to reset your password.
            </p>
            
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="email">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 4px;">mail</span>
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input"
                        placeholder="Enter your email address"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                    >
                </div>
                
                <button type="submit" class="btn">
                    <span class="material-symbols-outlined">send</span>
                    Send Reset Link
                </button>
            </form>
            
            <div class="security-note">
                <strong>Important:</strong><br>
                Make sure to enter the email address associated with your Sentinel Cameroon account.
            </div>
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
        </a>

        <?php elseif ($current_step === 1): ?>
        <!-- ══ STEP 1: Email ══ -->
        <h2>Forgot your password?</h2>
        <p class="subtitle">Enter the email address linked to your account. We'll send you a one-time 6-digit code.</p>
        <form method="POST">
            <input type="hidden" name="action" value="request_otp">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input"
                    placeholder="you@example.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
            </div>
            <button type="submit" class="btn" id="sendBtn">
                <span class="material-symbols-outlined">send</span> Send Verification Code
            </button>
        </form>

        <?php elseif ($current_step === 2): ?>
        <!-- ══ STEP 2: OTP Entry ══ -->
        <h2>Enter your code</h2>
        <p class="subtitle">
            A 6-digit one-time code was sent to <strong><?php echo htmlspecialchars($otp_email); ?></strong>.
            It expires in 10 minutes.
        </p>
        <form method="POST" id="otpForm">
            <input type="hidden" name="action" value="verify_otp">
            <div class="otp-inputs">
                <?php for ($i = 0; $i < 6; $i++): ?>
                <input type="text" name="otp_digits[]" class="otp-digit"
                    maxlength="1" inputmode="numeric" pattern="[0-9]"
                    autocomplete="one-time-code" required>
                <?php endfor; ?>
            </div>
            <button type="submit" class="btn">
                <span class="material-symbols-outlined">verified</span> Verify Code
            </button>
        </form>
        <form method="POST">
            <input type="hidden" name="action" value="restart">
            <button type="submit" class="btn btn-ghost">
                ← Use a different email
            </button>
        </form>
        <p class="resend-hint">Didn't receive it? Check your spam folder, or <a href="javascript:history.back();history.back();" onclick="document.querySelector('[name=action]').value='restart';document.querySelector('form').submit();">try again</a>.</p>

        <?php elseif ($current_step === 3): ?>
        <!-- ══ STEP 3: New password ══ -->
        <h2>Create new password</h2>
        <p class="subtitle">Identity verified. Choose a strong password for your account.</p>
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" class="form-input"
                    placeholder="Min. 8 characters" required autofocus>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input"
                    placeholder="Repeat your new password" required>
            </div>
            <button type="submit" class="btn">
                <span class="material-symbols-outlined">lock_reset</span> Reset Password
            </button>
        </form>
        <?php endif; ?>

        <div class="back-link"><a href="login.php">← Back to Login</a></div>
    </div>
</div>

<script>
// OTP digit auto-advance + paste support
const digits = document.querySelectorAll('.otp-digit');
digits.forEach((input, idx) => {
    input.addEventListener('input', () => {
        input.value = input.value.replace(/[^0-9]/g, '').slice(0, 1);
        if (input.value && idx < digits.length - 1) digits[idx + 1].focus();
    });
    input.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !input.value && idx > 0) digits[idx - 1].focus();
    });
    input.addEventListener('paste', e => {
        e.preventDefault();
        const pasted = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
        pasted.split('').forEach((ch, i) => { if (digits[i]) digits[i].value = ch; });
        const last = Math.min(pasted.length, digits.length - 1);
        digits[last].focus();
    });
});

// Disable send button on submit to prevent double-click
const sendBtn = document.getElementById('sendBtn');
if (sendBtn) {
    sendBtn.closest('form').addEventListener('submit', () => {
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<span class="material-symbols-outlined" style="animation:spin 1s linear infinite">sync</span> Sending...';
    });
}
</script>
<style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>
</body>
</html>
