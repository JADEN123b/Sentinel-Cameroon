<?php
/**
 * Run this script to create the missing authority_applications table 
 * and fix the users role ENUM to support 'authority_pending'.
 */
require_once __DIR__ . '/config.php';

$db = new Database();

// 1. Alter users table to support authority_pending
$r1 = $db->query("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'authority', 'authority_pending', 'admin') DEFAULT 'user'");

// 2. Create the missing authority_applications table
$r2 = $db->query("
    CREATE TABLE IF NOT EXISTS authority_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        organization_name VARCHAR(200) NOT NULL,
        organization_type VARCHAR(100) NOT NULL,
        contact_person VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        government_id VARCHAR(100),
        position VARCHAR(100),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");

if ($r1 !== false && $r2 !== false) {
    echo "<h2 style='color:green;'>✅ Database successfully migrated!</h2>";
    echo "<p>The <code>authority_applications</code> table was created and the user roles were updated.</p>";
    echo "<p>You can now go back to <a href='/admin.php'>admin.php</a> and the fatal error will be gone.</p>";
} else {
    echo "<h2 style='color:red;'>❌ Database migration failed. Check MySQL logs.</h2>";
}
