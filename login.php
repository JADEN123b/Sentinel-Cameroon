<?php
require_once 'includes/auth.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF Token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $db = new Database();
        $stmt = $db->query("SELECT * FROM users WHERE email = ?", [$email]);
        $user = $stmt ? $stmt->fetch() : null;

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // Log the login event
            logSystemActivity("User logged into the platform", 'security');

            // Handle redirection after login
            $redirect_url = $_SESSION['redirect_url'] ?? 'dashboard.php';
            unset($_SESSION['redirect_url']);

            header('Location: ' . $redirect_url);
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentinel Cameroon | Sign In</title>
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

        .login-split {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .login-branding {
            flex: 1.2;
            background: linear-gradient(135deg, var(--rs-primary) 0%, #020617 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 5rem;
            position: relative;
            overflow: hidden;
        }

        .login-form-side {
            flex: 0.8;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 5rem;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.05);
            z-index: 10;
        }

        .branding-bg-pattern {
            position: absolute;
            inset: 0;
            opacity: 0.1;
            background-image:
                radial-gradient(circle at 2px 2px, rgba(255, 255, 255, 0.2) 1px, transparent 0);
            background-size: 32px 32px;
        }

        .branding-visual {
            width: 400px;
            height: 400px;
            background: var(--rs-secondary);
            filter: blur(80px);
            border-radius: 50%;
            position: absolute;
            bottom: -100px;
            right: -100px;
            opacity: 0.2;
        }

        .auth-input {
            width: 100%;
            padding: 1.25rem;
            border-radius: 12px;
            border: 1px solid var(--rs-border);
            background: var(--rs-bg);
            font-weight: 700;
            font-size: 1rem;
            transition: var(--rs-transition);
            margin-bottom: 1.5rem;
        }

        .auth-input:focus {
            border-color: var(--rs-secondary);
            background: white;
            outline: none;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        @media (max-width: 1024px) {
            .login-branding {
                display: none;
            }

            .login-form-side {
                flex: 1;
                padding: 3rem 1.5rem;
                min-height: 100vh;
                justify-content: flex-start;
            }

            .auth-input {
                padding: 1rem;
                font-size: 0.95rem;
            }

            h1,
            h2 {
                text-align: center;
            }

            h2 {
                font-size: 1.75rem !important;
            }
        }

        /* Mobile Header */
        .mobile-auth-header {
            display: none;
            flex-direction: column;
            align-items: center;
            margin-bottom: 3rem;
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

    <div class="login-split">
        <!-- Visual Branding Side -->
        <div class="login-branding">
            <div class="branding-bg-pattern"></div>
            <div class="branding-visual"></div>

            <div style="position: relative; z-index: 5;">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 3rem;">
                    <span class="material-symbols-outlined"
                        style="background: var(--rs-secondary); color: white; padding: 12px; border-radius: 12px; font-size: 2rem;">shield</span>
                    <h2 style="font-size: 2.5rem; color: white;">Sentinel Cameroon</h2>
                </div>

                <h1 style="font-size: 4rem; color: white; line-height: 1.1; margin-bottom: 2rem;">Unified Safety for the
                    Community.</h1>
                <p style="font-size: 1.25rem; color: #94a3b8; line-height: 1.6; max-width: 500px; margin-bottom: 4rem;">
                    Access Cameroon's most reliable incident reporting and community protection network.</p>

                <div style="display: flex; flex-direction: column; gap: 2rem;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span class="material-symbols-outlined" style="color: var(--rs-secondary);">done_all</span>
                        <div style="font-weight: 700; font-size: 1rem;">Real-time Threat Monitoring</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span class="material-symbols-outlined" style="color: var(--rs-secondary);">done_all</span>
                        <div style="font-weight: 700; font-size: 1rem;">Verified Response Ecosystem</div>
                    </div>
                </div>
            </div>

            <div style="margin-top: auto; font-size: 0.85rem; color: #475569; font-weight: 600;">
                © 2024 Sentinel Platform Ecosystem. All rights reserved.
            </div>
        </div>

        <!-- Form Side -->
        <div class="login-form-side">
            <div style="max-width: 440px; width: 100%; margin: 0 auto;">

                <!-- Mobile Branding -->
                <div class="mobile-auth-header">
                    <span class="material-symbols-outlined"
                        style="background: var(--rs-secondary); color: white; padding: 10px; border-radius: 10px; font-size: 1.5rem;">shield</span>
                    <h2 style="font-size: 1.25rem; font-weight: 900; color: var(--rs-primary);">Sentinel Cameroon</h2>
                </div>

                <div style="margin-bottom: 2rem;">
                    <h2 style="font-size: 2.5rem; margin-bottom: 10px;">Sign In</h2>
                    <p style="color: #64748b; font-weight: 600; margin-bottom: 3rem;">Welcome back! Please enter your
                        details.</p>
                </div>

                <?php if ($error): ?>
                    <div
                        style="background: #fee2e2; color: #ef4444; padding: 1.25rem; border-radius: 12px; font-weight: 700; font-size: 0.85rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px; border: 1px solid #fecaca;">
                        <span class="material-symbols-outlined">error</span>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <?php csrfInput(); ?>
                    <div>
                        <label
                            style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 10px;">Email
                            Address</label>
                        <input type="email" name="email" class="auth-input" placeholder="Enter your email" required
                            autofocus>
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 10px;">Password</label>
                        <input type="password" name="password" class="auth-input" placeholder="••••••••" required>
                    </div>

                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
                        <label
                            style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600; color: #475569; cursor: pointer;">
                            <input type="checkbox"
                                style="width: 18px; height: 18px; accent-color: var(--rs-secondary);">
                            Remember me
                        </label>
                        <a href="forgot-password.php"
                            style="color: var(--rs-primary); font-size: 0.9rem; font-weight: 700; text-decoration: none;">Forgot
                            password?</a>
                    </div>

                    <button type="submit" class="btn-rs btn-rs-primary"
                        style="width: 100%; justify-content: center; padding: 1.25rem; font-size: 1.1rem; border-radius: 12px; margin-bottom: 2rem;">
                        Access Dashboard
                    </button>

                    <div
                        style="text-align: center; margin-top: 2rem; font-size: 0.9rem; color: #64748b; font-weight: 600;">
                        Don't have an account? <a href="register.php"
                            style="color: var(--rs-primary); font-weight: 800;">Register Here</a>
                        <div style="margin-top: 1.5rem;">
                            <a href="index.php"
                                style="display: inline-flex; align-items: center; gap: 5px; color: #94a3b8; text-decoration: none;">
                                <span class="material-symbols-outlined">arrow_back</span>
                                Return to Homepage
                            </a>
                        </div>
                    </div>

                    <div style="margin: 3rem 0; border-top: 1px solid #f1f5f9; position: relative;">
                        <span
                            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 0 20px; color: #cbd5e1; font-weight: 800; font-size: 0.7rem; text-transform: uppercase;">Help
                            & Support</span>
                    </div>

                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <a href="#"
                            style="color: #94a3b8; font-size: 0.85rem; text-decoration: none; font-weight: 600;">System
                            Status</a>
                        <span style="color: #e2e8f0;">•</span>
                        <a href="#"
                            style="color: #94a3b8; font-size: 0.85rem; text-decoration: none; font-weight: 600;">Privacy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>