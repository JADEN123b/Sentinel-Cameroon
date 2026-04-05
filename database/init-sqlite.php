<?php
/**
 * SQLite Database Initialization Script
 * Initializes Sentinel Cameroon database schema in SQLite
 */

$db_path = $argv[1] ?? getenv('DB_PATH') ?: '/var/www/html/database/data/sentinel_cameroon.sqlite';

if (!is_writable(dirname($db_path))) {
    error_log("ERROR: Database directory is not writable: " . dirname($db_path));
    exit(1);
}

try {
    // Connect to SQLite database
    $pdo = new PDO("sqlite:$db_path", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Read and execute SQLite schema
    $schema_file = __DIR__ . '/schema-sqlite.sql';
    
    if (!file_exists($schema_file)) {
        error_log("ERROR: SQLite schema file not found: $schema_file");
        exit(1);
    }

    $schema = file_get_contents($schema_file);
    
    // Split by statements and execute
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Log but continue - table might already exist
                error_log("Statement execution note: " . $e->getMessage());
            }
        }
    }

    echo "SQLite database initialized successfully at: $db_path\n";
    exit(0);

} catch (PDOException $e) {
    error_log("FATAL: Database initialization failed: " . $e->getMessage());
    exit(1);
} catch (Throwable $e) {
    error_log("FATAL: Unexpected error during initialization: " . $e->getMessage());
    exit(1);
}
?>
