<?php
/**
 * Run this script ONCE via your browser to add password reset columns:
 * http://localhost/stitch/stitch/database/migrate_add_reset_token.php
 * DELETE this file after running it.
 */
require_once __DIR__ . '/../database/config.php';

$db = new Database();

$r1 = $db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) DEFAULT NULL");
$r2 = $db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token_expires DATETIME DEFAULT NULL");

if ($r1 !== false && $r2 !== false) {
    echo "<p style='color:green;font-family:monospace;'>✅ Migration complete: <code>reset_token</code> and <code>reset_token_expires</code> columns added to <code>users</code> table.</p>";
} else {
    echo "<p style='color:red;font-family:monospace;'>❌ Migration failed — check your MySQL error log.</p>";
}
echo "<p style='font-family:monospace;'>You can now delete this file.</p>";
