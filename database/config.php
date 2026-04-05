<?php
/**
 * Sentinel Cameroon - Database Configuration
 * Database connection and configuration settings
 */

class Database {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $port;
    private $charset = 'utf8mb4';
    
    public $conn;
    
    public function __construct() {
        $this->host = getenv('DB_HOST') ?: '127.0.0.1';
        $this->dbname = getenv('DB_NAME') ?: 'sentinel_cameroon';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
        $this->port = getenv('DB_PORT') ?: '3307';
        $this->connect();
    }
    
    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return true;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            $this->conn = null;
            return false;
        }
    }
    
    public function query($sql, $params = []) {
        if (!$this->conn) {
            error_log("Query attempted while connection is null: " . $sql);
            return false;
        }
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (Throwable $e) { // Catch Throwable to handle both Error and Exception
            error_log("Query failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function lastInsertId() {
        return $this->conn ? $this->conn->lastInsertId() : false;
    }
    
    public function beginTransaction() {
        return $this->conn ? $this->conn->beginTransaction() : false;
    }
    
    public function commit() {
        return $this->conn ? $this->conn->commit() : false;
    }
    
    public function rollback() {
        return $this->conn ? $this->conn->rollback() : false;
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    public function __destruct() {
        $this->conn = null;
    }
}

/**
 * Log any system action for Super Admin oversight
 */
function logSystemActivity($actionText, $type = 'system') {
    $db = new Database();
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $db->query("INSERT INTO activity_logs (user_id, action_text, action_type) VALUES (?, ?, ?)", [$userId, $actionText, $type]);
}
?>
