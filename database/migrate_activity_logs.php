<?php
/**
 * Run this script to create the missing activity_logs table for Super Admin oversight.
 */
require_once __DIR__ . '/config.php';

$db = new Database();

$r = $db->query("
    CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action_text TEXT NOT NULL,
        action_type ENUM('incident', 'alert', 'system', 'user', 'partner', 'security') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )
");

if ($r !== false) {
    echo "<h2 style='color:green;'>✅ Activity Logs table successfully created!</h2>";
    echo "<p>Super Admin oversight can now track actions across the platform.</p>";
    echo "<p>Go to <a href='/admin.php'>admin.php</a> to see your Oversight Hub.</p>";
} else {
    echo "<h2 style='color:red;'>❌ Database migration failed. Check MySQL logs.</h2>";
}
