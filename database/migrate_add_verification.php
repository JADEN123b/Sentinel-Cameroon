<?php
/**
 * Run this script ONCE via your browser to add the verification_token column:
 * http://localhost/stitch/stitch/database/migrate_add_verification.php
 * DELETE this file after running it.
 */
require_once __DIR__ . '/../database/config.php';

$db = new Database();
$result = $db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS verification_token VARCHAR(255) DEFAULT NULL");

if ($result !== false) {
    echo "<p style='color:green;font-family:monospace;'>✅ Migration complete: <code>verification_token</code> column added to <code>users</code> table.</p>";
} else {
    echo "<p style='color:red;font-family:monospace;'>❌ Migration failed — check your MySQL error log.</p>";
}
echo "<p style='font-family:monospace;'>You can now delete this file.</p>";
