<?php
require_once 'includes/auth.php';

$error = '';
$success = '';

// Initialize variables for form
$full_name = '';
$username = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF Token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $full_name = $_POST['full_name'] ?? '';
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $db = new Database();

        // Check if user already exists
        $existing = $db->query("SELECT id FROM users WHERE email = ? OR username = ?", [$email, $username])->fetch();
        if ($existing) {
            $error = 'This email or username is already registered. Please sign in instead.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';

            $db->beginTransaction();
            try {
                $db->query("
                    INSERT INTO users (full_name, username, email, password_hash, phone, role, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ", [$full_name, $username, $email, $hashed_password, $phone, $role]);

                $user_id = $db->lastInsertId();
                $verification_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $db->query("
                    INSERT INTO email_verifications (user_id, email, token, expires_at) 
                    VALUES (?, ?, ?, ?)
                ", [$user_id, $email, $verification_token, $expires_at]);

                $db->commit();

                $success = 'Account created! Please check your email to verify your account.';
                header('Location: login.php?success=' . urlencode($success));
                exit;
            } catch (Exception $e) {
                $db->rollback();
                $error = 'Registration failed: ' . $e->getMessage();
            }
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
    <title>Sentinel Cameroon | Create Account</title>
    <link rel="stylesheet" href="assets/css/resilient-sentinel.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Public+Sans:wght@700;800;900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">

    <style>
        body {
            background: white;
            overflow-x: hidden;
        }

        .reg-split {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .reg-branding {
            flex: 0.8;
            background: linear-gradient(135deg, var(--rs-primary) 0%, #020617 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 5rem;
            position: relative;
            overflow: hidden;
        }

        .reg-form-side {
            flex: 1.2;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 5rem;
            overflow-y: auto;
        }

        .auth-input {
            width: 100%;
            padding: 1.15rem;
            border-radius: 12px;
            border: 1px solid var(--rs-border);
            background: var(--rs-bg);
            font-weight: 700;
            font-size: 0.95rem;
            transition: var(--rs-transition);
            margin-bottom: 1.25rem;
        }

        .auth-input:focus {
            border-color: var(--rs-secondary);
            background: white;
            outline: none;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .reg-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 1024px) {
            .reg-branding {
                display: none;
            }

            .reg-form-side {
                flex: 1;
                padding: 3rem 1.5rem;
                min-height: 100vh;
                justify-content: flex-start;
            }

            .reg-grid {
                grid-template-columns: 1fr;
                gap: 1.25rem;
            }

            .auth-input {
                padding: 1rem;
            }

            h2 { font-size: 1.75rem !important; }
        }

        /* Mobile Header */
        .mobile-auth-header {
            display: none;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
            gap: 12px;
        }

        @media (max-width: 1024px) {
            .mobile-auth-header {
                display: flex;
            }
        }
    </style>
</head>

<body class="animate-rs">

    <div class="reg-split">
        <!-- Branding Side -->
        <div class="reg-branding">
            <div style="position: relative; z-index: 5;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 3rem;">
                    <span class="material-symbols-outlined"
                        style="background: var(--rs-secondary); color: white; padding: 10px; border-radius: 10px; font-size: 1.5rem;">shield</span>
                    <h2 style="font-size: 1.75rem; color: white;">Sentinel</h2>
                </div>

                <h1 style="font-size: 3.5rem; color: white; line-height: 1.1; margin-bottom: 2rem;">Be part of a <span
                        style="color: var(--rs-secondary);">Safer</span> Cameroon.</h1>
                <p style="font-size: 1.15rem; color: #94a3b8; line-height: 1.6; max-width: 440px; margin-bottom: 4rem;">
                    Create your account today and start contributing to community safety in your area.</p>

                <div
                    style="background: rgba(255,255,255,0.03); padding: 2rem; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
                    <div
                        style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--rs-secondary); letter-spacing: 2px; margin-bottom: 1.5rem;">
                        Why Join Us?</div>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 15px;">
                        <li style="display: flex; gap: 12px; align-items: center; font-weight: 700;">
                            <span class="material-symbols-outlined"
                                style="color: var(--rs-secondary); font-size: 1.25rem;">check_circle</span>
                            Report incidents instantly
                        </li>
                        <li style="display: flex; gap: 12px; align-items: center; font-weight: 700;">
                            <span class="material-symbols-outlined"
                                style="color: var(--rs-secondary); font-size: 1.25rem;">check_circle</span>
                            Receive local safety alerts
                        </li>
                        <li style="display: flex; gap: 12px; align-items: center; font-weight: 700;">
                            <span class="material-symbols-outlined"
                                style="color: var(--rs-secondary); font-size: 1.25rem;">check_circle</span>
                            Access real-time emergency map
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Form Side -->
        <div class="reg-form-side">
            <div style="max-width: 650px; width: 100%; margin: 0 auto;">
                
                <!-- Mobile Branding -->
                <div class="mobile-auth-header">
                    <span class="material-symbols-outlined"
                        style="background: var(--rs-secondary); color: white; padding: 10px; border-radius: 10px; font-size: 1.5rem;">shield</span>
                    <h2 style="font-size: 1.25rem; font-weight: 900; color: var(--rs-primary);">Sentinel Cameroon</h2>
                </div>

                <div style="margin-bottom: 2rem;">
                    <h2 style="font-size: 2.25rem; margin-bottom: 8px;">Join our Community</h2>
                    <p style="color: #64748b; font-weight: 600; margin-bottom: 2rem;">Create your account today.</p>
                </div>

                <?php if ($error): ?>
                    <div
                        style="background: #fee2e2; color: #ef4444; padding: 1.25rem; border-radius: 12px; font-weight: 700; font-size: 0.85rem; margin-bottom: 2rem; border: 1px solid #fecaca; display: flex; align-items: center; gap: 10px;">
                        <span class="material-symbols-outlined">error</span>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <?php csrfInput(); ?>
                    <div class="reg-grid">
                        <div style="grid-column: span 2;">
                            <label
                                style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 10px;">Full
                                Name</label>
                            <input type="text" name="full_name" class="auth-input" placeholder="e.g. John Doe" required
                                autofocus value="<?php echo htmlspecialchars($full_name); ?>">
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 10px;">Username</label>
                            <input type="text" name="username" class="auth-input" placeholder="Unique username" required
                                value="<?php echo htmlspecialchars($username); ?>">
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 10px;">Email
                                Address</label>
                            <input type="email" name="email" class="auth-input" placeholder="name@domain.com" required
                                value="<?php echo htmlspecialchars($email); ?>">
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 10px;">Phone
                                Number</label>
                            <input type="tel" name="phone" class="auth-input" placeholder="+237..."
                                value="<?php echo htmlspecialchars($phone); ?>">
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 10px;">Password</label>
                            <input type="password" name="password" class="auth-input" placeholder="Min 8 characters"
                                required>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 10px;">Confirm
                                Password</label>
                            <input type="password" name="confirm_password" class="auth-input"
                                placeholder="Repeat password" required>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 12px; margin: 1rem 0 2.5rem;">
                        <input type="checkbox" required
                            style="width: 20px; height: 20px; accent-color: var(--rs-secondary); margin-top: 3px;">
                        <p style="font-size: 0.9rem; color: #64748b; font-weight: 600; line-height: 1.5;">
                            I agree to the <a href="#" style="color: var(--rs-primary); font-weight: 700;">Terms of
                                Service</a> and <a href="#" style="color: var(--rs-primary); font-weight: 700;">Privacy
                                Policy</a>.
                        </p>
                    </div>

                    <button type="submit" class="btn-rs btn-rs-primary"
                        style="width: 100%; justify-content: center; padding: 1.25rem; font-size: 1.1rem; border-radius: 12px; margin-bottom: 2rem;">
                        Create My Account
                    </button>

                    <div style="text-align: center; font-size: 0.95rem; font-weight: 600; color: #64748b;">
                        Already registered?
                        <a href="login.php"
                            style="color: var(--rs-secondary); font-weight: 800; text-decoration: none; margin-left: 5px;">Sign
                            In Here</a>
                    </div>

                    <div
                        style="margin: 2rem 0; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 1.5rem;">
                        <p style="font-size: 0.85rem; color: #94a3b8; font-weight: 600;">
                            Official Law Enforcement?
                            <a href="authority_register.php"
                                style="color: var(--rs-primary); font-weight: 800;">Register as Authority</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>