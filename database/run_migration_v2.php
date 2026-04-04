<?php
require_once __DIR__ . '/config.php';

$db = new Database();
$db->connect();

$sqlFile = __DIR__ . '/chat_management_migration.sql';

if (file_exists($sqlFile)) {
    $sql = file_get_contents($sqlFile);
    
    try {
        // Execute the entire SQL script
        $db->conn->exec($sql);
        echo "<h1>Migration Successful (v2)</h1>";
        echo "<p>Chat Management and Community Profile columns have been created.</p>";
        echo "<a href='../community_detail.php'>Return to Communities</a>";
    } catch (PDOException $e) {
        echo "<h1>Migration Failed</h1>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<h1>Migration Failed</h1>";
    echo "<p>SQL file not found: $sqlFile</p>";
}
?>
