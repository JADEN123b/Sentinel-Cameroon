<?php
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';

$user = getCurrentUser();
$success = '';
$errors = [];

// Handle success message from URL
if (isset($_GET['success'])) { $success = $_GET['success']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Handle profile picture upload
    $profile_picture = $user['profile_picture'] ?? ''; 
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; 
        
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) { $errors[] = 'Image must be JPG, PNG, or GIF.'; }
        if ($file['size'] > $max_size) { $errors[] = 'Image must be less than 2MB.'; }
        
        if (empty($errors)) {
            $upload_dir = 'uploads/profiles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = uniqid('profile_', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $filepath = $upload_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                if ($user['profile_picture'] && file_exists($user['profile_picture'])) unlink($user['profile_picture']);
                $profile_picture = $filepath;
            } else { $errors[] = 'Failed to upload image.'; }
        }
    }
    
    if (empty($full_name) || empty($email)) $errors[] = 'Name and email are required.';
    
    if (!empty($new_password)) {
        if (empty($current_password)) $errors[] = 'Current password is required to change it.';
        elseif (!password_verify($current_password, $user['password_hash'])) $errors[] = 'Incorrect current password.';
        elseif (strlen($new_password) < 8) $errors[] = 'New password must be at least 8 characters.';
        elseif ($new_password !== $confirm_password) $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        $db = new Database();
        if ($email !== $user['email']) {
            $stmt = $db->query("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user['id']]);
            if ($stmt->fetch()) $errors[] = 'Email already in use.';
        }
        
        if (empty($errors)) {
            $q = "UPDATE users SET full_name = ?, email = ?, phone = ?, profile_picture = ?" . (!empty($new_password) ? ", password_hash = ?" : "") . " WHERE id = ?";
            $p = [$full_name, $email, $phone, $profile_picture];
            if (!empty($new_password)) $p[] = password_hash($new_password, PASSWORD_DEFAULT);
            $p[] = $user['id'];
            
            if ($db->query($q, $p)) {
                header('Location: profile.php?success=' . urlencode('Profile updated successfully!'));
                exit;
            } else { $errors[] = 'Failed to save changes.'; }
        }
    }
}

$db = new Database();
$stats = [
    'reported' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE user_id = ?", [$user['id']])->fetch()['count'],
    'resolved' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE user_id = ? AND status = 'resolved'", [$user['id']])->fetch()['count'],
    'account_age' => floor((time() - strtotime($user['created_at'])) / 86400)
];
?>

<div class="animate-rs">
    
    <!-- Header -->
    <div class="rs-card" style="margin-bottom: 2rem; border-left: 6px solid var(--rs-accent); border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-size: 2.25rem; margin-bottom: 5px;">My Account Settings</h1>
                <p style="color: #64748b; font-weight: 600;">Update your personal details and manage your security.</p>
            </div>
            <div style="text-align: right;">
                <div style="display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 0.85rem; color: <?php echo $user['is_verified'] ? 'var(--rs-success)' : 'var(--rs-warning)'; ?>;">
                    <span class="material-symbols-outlined" style="font-size: 1.25rem;"><?php echo $user['is_verified'] ? 'verified' : 'pending_actions'; ?></span>
                    <?php echo $user['is_verified'] ? 'VERIFIED ACCOUNT' : 'UNVERIFIED'; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="rs-card animate-rs" style="background: #dcfce7; border-color: #86efac; color: #166534; padding: 1.25rem; margin-bottom: 2rem; font-weight: 700;">
            <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 10px;">check_circle</span>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="rs-card animate-rs" style="background: #fee2e2; border-color: #fecaca; color: #991b1b; padding: 1.25rem; margin-bottom: 2rem;">
            <?php foreach ($errors as $error): ?>
                <div style="display: flex; align-items: center; gap: 10px; font-weight: 700; margin-bottom: 5px;">
                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">error</span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="rs-grid rs-grid-main">
        <!-- Sidebar: Profile Overview -->
        <div class="rs-card" style="display: flex; flex-direction: column; align-items: center; text-align: center;">
            <div style="position: relative; margin-bottom: 2rem;">
                <?php if (!empty($user['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" style="width: 150px; height: 150px; border-radius: 20px; object-fit: cover; border: 4px solid var(--rs-bg);">
                <?php else: ?>
                    <div style="width: 150px; height: 150px; border-radius: 20px; background: var(--rs-bg); display: flex; align-items: center; justify-content: center; color: #cbd5e1;">
                        <span class="material-symbols-outlined" style="font-size: 5rem;">person</span>
                    </div>
                <?php endif; ?>
                <div style="position: absolute; bottom: -10px; right: -10px; background: var(--rs-secondary); color: white; padding: 6px; border-radius: 8px; border: 3px solid white;">
                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">shield</span>
                </div>
            </div>
            
            <h2 style="font-size: 1.5rem; margin-bottom: 5px;"><?php echo htmlspecialchars($user['full_name']); ?></h2>
            <p style="color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 2rem;"><?php echo ucfirst($user['role']); ?></p>

            <div style="width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                <div style="background: var(--rs-bg); padding: 1rem; border-radius: 12px;">
                    <div style="font-size: 1.5rem; font-weight: 900;"><?php echo $stats['reported']; ?></div>
                    <p style="font-size: 0.6rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Reports</p>
                </div>
                <div style="background: var(--rs-bg); padding: 1rem; border-radius: 12px;">
                    <div style="font-size: 1.5rem; font-weight: 900; color: var(--rs-success);"><?php echo $stats['resolved']; ?></div>
                    <p style="font-size: 0.6rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Resolved</p>
                </div>
            </div>
            <p style="font-size: 0.8rem; color: #94a3b8; font-weight: 600;">Member for <?php echo $stats['account_age']; ?> days</p>
        </div>

        <!-- Main Settings Form -->
        <div class="rs-card">
            <h3 style="margin-bottom: 2rem; display: flex; align-items: center; gap: 12px;">
                <span class="material-symbols-outlined" style="color: var(--rs-accent);">manage_accounts</span>
                Profile Details
            </h3>
            
            <form method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" style="width: 100%; padding: 1rem; border-radius: 10px; border: 1px solid #e2e8f0; font-weight: 700;" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" style="width: 100%; padding: 1rem; border-radius: 10px; border: 1px solid #e2e8f0; font-weight: 700;" required>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" style="width: 100%; padding: 1rem; border-radius: 10px; border: 1px solid #e2e8f0; font-weight: 700;" placeholder="+237...">
                </div>

                <div style="margin-bottom: 2rem;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Update Profile Photo</label>
                    <input type="file" name="profile_picture" accept="image/*" style="width: 100%; padding: 1rem; border-radius: 10px; border: 2px dashed #e2e8f0; background: #f8fafc; font-weight: 600;">
                </div>

                <div style="margin-top: 3rem; padding-top: 2rem; border-top: 2px solid #f1f5f9;">
                    <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 12px; font-size: 1.1rem;">
                        <span class="material-symbols-outlined" style="color: var(--rs-secondary);">lock</span>
                        Security Settings
                    </h3>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Current Password</label>
                        <input type="password" name="current_password" style="width: 100%; padding: 1rem; border-radius: 10px; border: 1px solid #e2e8f0; font-weight: 700;" placeholder="Required to change password">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">New Password</label>
                            <input type="password" name="new_password" style="width: 100%; padding: 1rem; border-radius: 10px; border: 1px solid #e2e8f0; font-weight: 700;" placeholder="Minimum 8 characters">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Confirm New Password</label>
                            <input type="password" name="confirm_password" style="width: 100%; padding: 1rem; border-radius: 10px; border: 1px solid #e2e8f0; font-weight: 700;" placeholder="Repeat new password">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 1.25rem; margin-top: 3rem;">
                    <button type="submit" class="btn-rs btn-rs-primary" style="flex: 2; justify-content: center; padding: 1.25rem;">
                        <span class="material-symbols-outlined">save</span>
                        Save Changes
                    </button>
                    <a href="dashboard.php" class="btn-rs" style="flex: 1; justify-content: center; background: #f1f5f9; text-decoration: none; color: #64748b;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
