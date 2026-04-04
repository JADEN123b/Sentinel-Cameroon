<?php
require_once __DIR__ . '/includes/header.php';
$db = new Database();

if (!isLoggedIn()) {
    echo "<div style='padding:3rem; text-align:center;'><h2>You must be logged in first!</h2><p>Please log in with your primary user account, then come back to this page to upgrade it to Admin.</p></div>";
    exit;
}

$user = getCurrentUser();

if ($user['role'] === 'admin') {
    echo "<div style='padding:3rem; text-align:center;'><h2 style='color:green;'>You are already an Admin!</h2><p>Go to <a href='admin.php'>admin.php</a> right now.</p></div>";
} else {
    // Upgrade the user to admin
    $db->query("UPDATE users SET role = 'admin', is_verified = 1 WHERE id = ?", [$user['id']]);
    
    // Update session
    $_SESSION['user_role'] = 'admin';
    $_SESSION['is_verified'] = 1;
    
    echo "<div style='padding:3rem; text-align:center;'>
            <h1 style='color:blue;'>🎉 Success! You are now the Super Admin!</h1>
            <p style='font-size:1.2rem; margin-top:2rem;'>Your account (<strong>" . htmlspecialchars($user['email']) . "</strong>) has been fully upgraded.</p>
            <p style='margin-top:2rem;'><a href='admin.php' style='padding:1rem 2rem; background:blue; color:white; border-radius:10px; text-decoration:none; font-weight:bold;'>Go to Admin Dashboard &rarr;</a></p>
          </div>";
}
