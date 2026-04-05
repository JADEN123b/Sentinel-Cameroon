#!/bin/bash
# Sentinel Cameroon - Local Development Startup Script
# This script starts the PHP development server with proper SQLite configuration

set -e

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DB_PATH="$PROJECT_DIR/database/data/sentinel_cameroon.sqlite"

echo "🚀 Sentinel Cameroon - Local Development Server"
echo "=================================================="
echo ""

# Check if database exists
if [ ! -f "$DB_PATH" ]; then
    echo "❌ Database not found at: $DB_PATH"
    echo ""
    echo "📝 Initializing database..."
    php << 'EOF'
<?php
$db_path = getenv('DB_PATH') ?: __DIR__ . '/database/data/sentinel_cameroon.sqlite';
$db_dir = dirname($db_path);

if (!is_dir($db_dir)) {
    mkdir($db_dir, 0755, true);
}

try {
    $pdo = new PDO("sqlite:$db_path", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec('PRAGMA foreign_keys = ON');

    $schema_file = __DIR__ . '/database/schema-sqlite.sql';
    $schema = file_get_contents($schema_file);
    
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Table might exist
            }
        }
    }

    echo "✅ Database initialized successfully!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
EOF
    echo ""
fi

# Check if database has data
RECORD_COUNT=$(php << 'EOF'
<?php
try {
    $db_path = getenv('DB_PATH') ?: __DIR__ . '/database/data/sentinel_cameroon.sqlite';
    $pdo = new PDO("sqlite:$db_path");
    $result = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
    echo $result['count'];
} catch (Exception $e) {
    echo "0";
}
?>
EOF
)

if [ "$RECORD_COUNT" -eq 0 ]; then
    echo "📊 Database is empty. Adding sample data..."
    php << 'SAMPLE_EOF'
<?php
$db_path = getenv('DB_PATH') ?: __DIR__ . '/database/data/sentinel_cameroon.sqlite';
$pdo = new PDO("sqlite:$db_path");
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON');

$users = [
    ['john_doe', 'john@example.com', 'John Doe', 'user'],
    ['jane_smith', 'jane@example.com', 'Jane Smith', 'authority'],
    ['admin_user', 'admin@example.com', 'Admin User', 'admin'],
    ['carlos_safety', 'carlos@example.com', 'Carlos Safety Officer', 'authority'],
    ['marie_community', 'marie@example.com', 'Marie Community Leader', 'user'],
];

foreach ($users as $user) {
    $password_hash = password_hash('password123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT OR IGNORE INTO users (username, email, password_hash, full_name, role, is_verified, email_verified_at) 
               VALUES ('{$user[0]}', '{$user[1]}', '$password_hash', '{$user[2]}', '{$user[3]}', 1, datetime('now'))");
}

// Add sample incidents  
$incidents = [
    [1, 'Traffic Accident', 'Vehicle collision', 'accident', 'medium', 'investigating', 3.8667, 11.5167],
    [2, 'Theft at Market', 'Electronics theft', 'theft', 'low', 'reported', 3.8500, 11.5100],
    [3, 'Medical Emergency', 'Person collapsed', 'medical', 'high', 'resolved', 3.8400, 11.5050],
];

foreach ($incidents as $incident) {
    $pdo->exec("INSERT INTO incidents (user_id, title, description, incident_type, severity, status, latitude, longitude, location_address) 
               VALUES ({$incident[0]}, '{$incident[1]}', '{$incident[2]}', '{$incident[3]}', '{$incident[4]}', '{$incident[5]}', {$incident[6]}, {$incident[7]}, 'Yaoundé')");
}

// Add partners
$partners = [
    ['Yaoundé Police', 'police@yaoundé.cm', '+237222222222', 'security', 1],
    ['Red Cross', 'redcross@cm.org', '+237333333333', 'medical', 1],
    ['Fire Dept', 'fire@yaoundé.cm', '+237444444444', 'security', 1],
];

foreach ($partners as $partner) {
    $pdo->exec("INSERT INTO partners (name, contact_email, contact_phone, partner_type, is_verified) 
               VALUES ('{$partner[0]}', '{$partner[1]}', '{$partner[2]}', '{$partner[3]}', {$partner[4]})");
}

echo "✅ Sample data added!\n";
?>
SAMPLE_EOF
    echo ""
fi

# Display credentials
echo "📋 TEST ACCOUNTS (password: password123)"
echo "=================================================="
echo "  • john@example.com - Regular User"
echo "  • jane@example.com - Authority Officer"
echo "  • admin@example.com - Admin"
echo ""

# Start PHP server
export DB_PATH="$DB_PATH"
echo "🌐 Starting PHP Development Server..."
echo "   URL: http://localhost:8080"
echo "   DB:  $DB_PATH"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

cd "$PROJECT_DIR"
php -S localhost:8080
