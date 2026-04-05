<?php
require_once 'includes/auth.php';

$error = '';
$success = '';

// Initialize variables for form
$full_name = '';
$email = '';
$username = '';
$org_name = '';
$org_type = '';
$position = '';
$phone = '';
$gov_id = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF Token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $db = new Database();
        
        // Ensure table exists
        $db->query("
            CREATE TABLE IF NOT EXISTS authority_applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                organization_name VARCHAR(255) NOT NULL,
                organization_type ENUM('police', 'medical', 'fire', 'community', 'security', 'other') NOT NULL,
                contact_person VARCHAR(255) NOT NULL,
                position VARCHAR(255) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                address TEXT NOT NULL,
                government_id VARCHAR(100) NOT NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Extract POST data
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $org_name = $_POST['org_name'] ?? '';
        $org_type = $_POST['org_type'] ?? '';
        $position = $_POST['position'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $gov_id = $_POST['gov_id'] ?? '';

        // Basic Validation
        if (empty($full_name) || empty($email) || empty($password) || empty($org_name) || empty($gov_id)) {
            $error = 'Please fill in all required fields to complete your application.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Check if user exists
            $existing = $db->fetch("SELECT id FROM users WHERE email = ? OR username = ?", [$email, $username]);
            if ($existing) {
                $error = 'This email or username is already registered.';
            } else {
                $db->beginTransaction();
                try {
                    // 1. Create User
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $db->query("
                        INSERT INTO users (full_name, email, username, password_hash, phone, role, created_at) 
                        VALUES (?, ?, ?, ?, ?, 'authority_pending', NOW())
                    ", [$full_name, $email, $username, $hashed_password, $phone]);
                    
                    $user_id = $db->lastInsertId();
                    
                    // 2. Create Authority Application
                    $db->query("
                        INSERT INTO authority_applications (user_id, organization_name, organization_type, contact_person, position, phone, address, government_id, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                    ", [$user_id, $org_name, $org_type, $full_name, $position, $phone, $address, $gov_id]);
                    
                    $db->commit();
                    $success = 'Application submitted successfully. Our administrators will review your credentials shortly.';
                    header('Location: login.php?success=' . urlencode($success));
                    exit;
                } catch (Exception $e) {
                    $db->rollback();
                    $error = 'Application failed: ' . $e->getMessage();
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
    <title>Sentinel | Official Registration</title>
    <link rel="stylesheet" href="assets/css/resilient-sentinel.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Public+Sans:wght@700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <style>
        body { background: white; overflow-x: hidden; }
        .auth-split { display: flex; min-height: 100vh; width: 100%; }
        
        .auth-branding {
            flex: 0.7;
            background: linear-gradient(135deg, var(--rs-primary) 0%, #020617 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            position: relative;
            overflow: hidden;
        }
        
        .auth-form-side {
            flex: 1.3;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            overflow-y: auto;
        }

        .auth-input {
            width: 100%;
            padding: 1.1rem;
            border-radius: 10px;
            border: 1px solid var(--rs-border);
            background: var(--rs-bg);
            font-weight: 700;
            font-size: 0.95rem;
            transition: var(--rs-transition);
            margin-bottom: 1rem;
        }
        
        .auth-input:focus {
            border-color: var(--rs-secondary);
            background: white;
            outline: none;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .auth-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }

        .sub-heading {
            font-size: 0.8rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--rs-secondary);
            margin: 2rem 0 1rem;
            display: flex;
            align-items: center; gap: 10px;
        }
        
        .sub-heading:first-child { margin-top: 0; }

        @media (max-width: 1024px) {
            .auth-branding {
                display: none;
            }
            .auth-form-side {
                flex: 1;
                padding: 3rem 1.5rem;
                min-height: 100vh;
                justify-content: flex-start;
            }
            .auth-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
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

    <div class="auth-split">
        <!-- Branding Side -->
        <div class="auth-branding">
            <div style="position: relative; z-index: 5;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 2rem;">
                    <span class="material-symbols-outlined" style="background: var(--rs-secondary); color: white; padding: 10px; border-radius: 10px; font-size: 1.5rem;">shield_person</span>
                    <h2 style="font-size: 1.5rem; color: white;">Sentinel Authority</h2>
                </div>
                
                <h1 style="font-size: 3rem; color: white; line-height: 1.1; margin-bottom: 2rem;">Official Agency <br>Registration.</h1>
                <p style="font-size: 1.1rem; color: #94a3b8; line-height: 1.6; max-width: 400px; margin-bottom: 4rem;">Secure access for law enforcement, emergency services, and community responders.</p>
                
                <div style="display: flex; flex-direction: column; gap: 2rem;">
                    <div style="display: flex; gap: 15px;">
                        <span class="material-symbols-outlined" style="color: var(--rs-secondary);">verified</span>
                        <div>
                            <div style="font-weight: 800; font-size: 0.95rem;">Verified Status</div>
                            <div style="color: #64748b; font-size: 0.85rem;">Every official account is manually verified for security.</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 15px;">
                        <span class="material-symbols-outlined" style="color: var(--rs-secondary);">admin_panel_settings</span>
                        <div>
                            <div style="font-weight: 800; font-size: 0.95rem;">Command Center</div>
                            <div style="color: #64748b; font-size: 0.85rem;">Access high-level report management and broadcasting tools.</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: auto; font-size: 0.8rem; color: #475569; font-weight: 600;">
                © 2024 Sentinel Authority Onboarding.
            </div>
        </div>

        <!-- Form Side -->
        <div class="auth-form-side">
            <div style="max-width: 650px; width: 100%; margin: 0 auto;">
                
                <!-- Mobile Branding -->
                <div class="mobile-auth-header">
                    <span class="material-symbols-outlined"
                        style="background: var(--rs-secondary); color: white; padding: 10px; border-radius: 10px; font-size: 1.5rem;">shield_person</span>
                    <h2 style="font-size: 1.25rem; font-weight: 900; color: var(--rs-primary);">Sentinel Authority</h2>
                </div>

                <h2 style="font-size: 2.25rem; margin-bottom: 8px;">Create Official Account</h2>
                <p style="color: #64748b; font-weight: 600; margin-bottom: 2rem;">Please provide your agency information for review.</p>
                
                <?php if ($error): ?>
                    <div style="background: #fee2e2; color: #ef4444; padding: 1rem; border-radius: 10px; font-weight: 700; font-size: 0.85rem; margin-bottom: 2rem; border: 1px solid #fecaca; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined">error</span>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <?php csrfInput(); ?>
                    <div class="sub-heading">Representative Details</div>
                    <div class="auth-grid">
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Full Name</label>
                            <input type="text" name="full_name" class="auth-input" placeholder="e.g. Commandant Jean Pierre" required autofocus value="<?php echo htmlspecialchars($full_name); ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Official Email</label>
                            <input type="email" name="email" class="auth-input" placeholder="official@agency.cm" required value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Desired Username</label>
                            <input type="text" name="username" class="auth-input" placeholder="e.g. DLA_POLICE_01" required value="<?php echo htmlspecialchars($username); ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Password</label>
                            <input type="password" name="password" class="auth-input" placeholder="••••••••" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Confirm Password</label>
                            <input type="password" name="confirm_password" class="auth-input" placeholder="••••••••" required>
                        </div>
                    </div>
                    
                    <div class="sub-heading">Agency Information</div>
                    <div class="auth-grid">
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Agency / Organization Name</label>
                            <input type="text" name="org_name" class="auth-input" placeholder="Full Official Organization Name" required value="<?php echo htmlspecialchars($org_name); ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Agency Type</label>
                            <select name="org_type" class="auth-input" required>
                                <option value="police" <?php echo ($org_type === 'police' ? 'selected' : ''); ?>>Police / Law Enforcement</option>
                                <option value="medical" <?php echo ($org_type === 'medical' ? 'selected' : ''); ?>>Medical / Hospital</option>
                                <option value="fire" <?php echo ($org_type === 'fire' ? 'selected' : ''); ?>>Fire Department</option>
                                <option value="security" <?php echo ($org_type === 'security' ? 'selected' : ''); ?>>Private Security</option>
                                <option value="community" <?php echo ($org_type === 'community' ? 'selected' : ''); ?>>Community Watch</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Rank / Position</label>
                            <input type="text" name="position" class="auth-input" placeholder="Your role in the agency" required value="<?php echo htmlspecialchars($position); ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Official Phone</label>
                            <input type="tel" name="phone" class="auth-input" placeholder="Contact number" required value="<?php echo htmlspecialchars($phone); ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Official Government ID</label>
                            <input type="text" name="gov_id" class="auth-input" placeholder="Registration # / ID Number" required value="<?php echo htmlspecialchars($gov_id); ?>">
                        </div>
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Official Address</label>
                            <input type="text" name="address" class="auth-input" placeholder="Headquarters location" required value="<?php echo htmlspecialchars($address); ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-rs btn-rs-primary" style="width: 100%; justify-content: center; padding: 1.25rem; font-size: 1.1rem; border-radius: 12px; margin-top: 2rem;">
                        Submit Application
                    </button>
                    
                    <div style="text-align: center; margin-top: 2rem; font-size: 0.9rem; color: #64748b; font-weight: 600;">
                        Already registered? <a href="login.php" style="color: var(--rs-primary); font-weight: 800;">Sign In Here</a>
                        <div style="margin-top: 1.5rem;">
                            <a href="index.php" style="display: inline-flex; align-items: center; gap: 5px; color: #94a3b8; text-decoration: none;">
                                <span class="material-symbols-outlined">arrow_back</span>
                                Return to Homepage
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
