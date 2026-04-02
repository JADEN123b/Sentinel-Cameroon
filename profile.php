<?php
require_once 'includes/header.php';
require_once 'database/config.php';

// Get current user
$user = getCurrentUser();
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($full_name) || empty($email)) {
        $errors[] = 'Name and email are required.';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // If changing password
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password.';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        }
    }
    
    if (empty($errors)) {
        $db = new Database();
        
        // Check if new email already exists (and is different from current)
        if ($email !== $user['email']) {
            $stmt = $db->query("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user['id']]);
            if ($stmt->fetch()) {
                $errors[] = 'Email address already registered by another user.';
            }
        }
        
        if (empty($errors)) {
            // Update user info
            $update_fields = "full_name = ?, email = ?, phone = ?";
            $update_params = [$full_name, $email, $phone];
            
            // Add password update if provided
            if (!empty($new_password)) {
                $update_fields .= ", password_hash = ?";
                $update_params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }
            
            $update_fields .= " WHERE id = ?";
            $update_params[] = $user['id'];
            
            $stmt = $db->query("UPDATE users SET $update_fields", $update_params);
            
            if ($stmt) {
                $success = 'Profile updated successfully!';
                // Refresh user data
                $user = getCurrentUser();
            } else {
                $errors[] = 'Failed to update profile. Please try again.';
            }
        }
    }
}
?>

<div class="max-w-2xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">My Profile</h1>
        <span class="text-sm text-gray-600">
            Member since: <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
        </span>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile Overview -->
        <div class="md:col-span-1">
            <div class="card text-center">
                <div class="w-20 h-20 bg-primary rounded-full mx-auto mb-4 flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-3xl">person</span>
                </div>
                <h2 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars($user['email']); ?></p>
                
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Role:</span>
                        <span class="font-medium"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status:</span>
                        <span class="<?php echo $user['is_verified'] ? 'text-success' : 'text-warning'; ?> font-medium">
                            <?php echo $user['is_verified'] ? 'Verified' : 'Pending Verification'; ?>
                        </span>
                    </div>
                    <?php if ($user['phone']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Phone:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Edit Profile Form -->
        <div class="md:col-span-2">
            <div class="card">
                <div class="card-header">
                    <h3>Edit Profile Information</h3>
                </div>
                
                <form method="POST" class="space-y-6">
                    <?php csrfInput(); ?>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input 
                                type="text" 
                                id="full_name" 
                                name="full_name" 
                                class="form-input" 
                                required
                                value="<?php echo htmlspecialchars($user['full_name']); ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                required
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            class="form-input"
                            value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                            placeholder="+237 XXX XXX XXX"
                        >
                    </div>
                    
                    <div class="border-t border-surface-container-low pt-6">
                        <h4 class="font-medium mb-4">Change Password</h4>
                        <p class="text-sm text-gray-600 mb-4">Leave blank if you don't want to change password</p>
                        
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                class="form-input"
                                placeholder="Enter current password"
                            >
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password</label>
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    class="form-input"
                                    placeholder="Enter new password"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    class="form-input"
                                    placeholder="Confirm new password"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Save Changes
                        </button>
                        <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- User Statistics -->
    <div class="card mt-6">
        <div class="card-header">
            <h3>My Activity</h3>
        </div>
        
        <div class="grid grid-cols-4 gap-4 text-center">
            <?php
            $db = new Database();
            $stats = [
                'incidents_reported' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE user_id = ?", [$user['id']])->fetch()['count'],
                'incidents_resolved' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE user_id = ? AND status = 'resolved'", [$user['id']])->fetch()['count'],
                'reports_this_month' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE user_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())", [$user['id']])->fetch()['count'],
                'account_age' => floor((time() - strtotime($user['created_at'])) / (60 * 60 * 24))
            ];
            ?>
            
            <div class="bg-surface-container-low rounded-lg p-4">
                <div class="text-2xl font-bold text-primary"><?php echo $stats['incidents_reported']; ?></div>
                <div class="text-sm text-gray-600">Incidents Reported</div>
            </div>
            
            <div class="bg-surface-container-low rounded-lg p-4">
                <div class="text-2xl font-bold text-success"><?php echo $stats['incidents_resolved']; ?></div>
                <div class="text-sm text-gray-600">Resolved Cases</div>
            </div>
            
            <div class="bg-surface-container-low rounded-lg p-4">
                <div class="text-2xl font-bold text-warning"><?php echo $stats['reports_this_month']; ?></div>
                <div class="text-sm text-gray-600">This Month</div>
            </div>
            
            <div class="bg-surface-container-low rounded-lg p-4">
                <div class="text-2xl font-bold text-secondary"><?php echo $stats['account_age']; ?></div>
                <div class="text-sm text-gray-600">Days Active</div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
