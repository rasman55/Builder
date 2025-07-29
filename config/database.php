<?php
/**
 * Database Configuration for Goba Hospital Patient Record Management System
 * 
 * This file contains all database connection settings and configuration
 * for the hospital management system.
 */

// Database Configuration Settings
class DatabaseConfig {
    // Database Connection Parameters
    const DB_HOST = 'localhost';
    const DB_NAME = 'goba_hospital_db';
    const DB_USER = 'hospital_user';
    const DB_PASS = 'secure_password_2024';
    const DB_PORT = 3306;
    const DB_CHARSET = 'utf8mb4';
    
    // Connection Options
    const PDO_OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    // Connection Pool Settings
    const MAX_CONNECTIONS = 20;
    const CONNECTION_TIMEOUT = 30;
    const QUERY_TIMEOUT = 60;
    
    // Security Settings
    const USE_SSL = false;
    const SSL_CA_PATH = '';
    const SSL_CERT_PATH = '';
    const SSL_KEY_PATH = '';
}

/**
 * Database Connection Class
 * Handles database connections with connection pooling and error handling
 */
class Database {
    private static $instance = null;
    private $connection = null;
    private $config;
    
    private function __construct() {
        $this->config = new DatabaseConfig();
        $this->connect();
    }
    
    /**
     * Get singleton instance of Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                DatabaseConfig::DB_HOST,
                DatabaseConfig::DB_PORT,
                DatabaseConfig::DB_NAME,
                DatabaseConfig::DB_CHARSET
            );
            
            $this->connection = new PDO(
                $dsn,
                DatabaseConfig::DB_USER,
                DatabaseConfig::DB_PASS,
                DatabaseConfig::PDO_OPTIONS
            );
            
            // Set connection timeout
            $this->connection->setAttribute(PDO::ATTR_TIMEOUT, DatabaseConfig::CONNECTION_TIMEOUT);
            
            // Enable SSL if configured
            if (DatabaseConfig::USE_SSL) {
                $this->connection->setAttribute(PDO::MYSQL_ATTR_SSL_CA, DatabaseConfig::SSL_CA_PATH);
                $this->connection->setAttribute(PDO::MYSQL_ATTR_SSL_CERT, DatabaseConfig::SSL_CERT_PATH);
                $this->connection->setAttribute(PDO::MYSQL_ATTR_SSL_KEY, DatabaseConfig::SSL_KEY_PATH);
            }
            
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
        }
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        // Check if connection is still alive
        if ($this->connection === null) {
            $this->connect();
        }
        
        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            // Reconnect if connection is lost
            $this->connect();
        }
        
        return $this->connection;
    }
    
    /**
     * Execute prepared statement
     */
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->handleQueryError($e, $sql, $params);
            return false;
        }
    }
    
    /**
     * Execute SELECT query and return results
     */
    public function select($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }
    
    /**
     * Execute SELECT query and return single row
     */
    public function selectOne($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return null;
    }
    
    /**
     * Execute INSERT query and return last insert ID
     */
    public function insert($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        if ($stmt) {
            return $this->connection->lastInsertId();
        }
        return false;
    }
    
    /**
     * Execute UPDATE query and return affected rows
     */
    public function update($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        if ($stmt) {
            return $stmt->rowCount();
        }
        return false;
    }
    
    /**
     * Execute DELETE query and return affected rows
     */
    public function delete($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        if ($stmt) {
            return $stmt->rowCount();
        }
        return false;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Check if in transaction
     */
    public function inTransaction() {
        return $this->connection->inTransaction();
    }
    
    /**
     * Handle connection errors
     */
    private function handleConnectionError($e) {
        $error_message = "Database Connection Error: " . $e->getMessage();
        error_log($error_message);
        
        // In production, show generic error message
        if (getenv('ENVIRONMENT') === 'production') {
            die("Database connection failed. Please try again later.");
        } else {
            die($error_message);
        }
    }
    
    /**
     * Handle query errors
     */
    private function handleQueryError($e, $sql, $params) {
        $error_message = "Database Query Error: " . $e->getMessage();
        $error_message .= "\nSQL: " . $sql;
        $error_message .= "\nParams: " . print_r($params, true);
        
        error_log($error_message);
        
        // Log to audit table if possible
        try {
            $audit_sql = "INSERT INTO Audit_log (user_type, user_id, action, error_message) VALUES (?, ?, ?, ?)";
            $this->connection->prepare($audit_sql)->execute([
                'System',
                'DB_ERROR',
                'QUERY_ERROR',
                $error_message
            ]);
        } catch (Exception $audit_e) {
            // Ignore audit log errors to prevent infinite loops
        }
        
        if (getenv('ENVIRONMENT') === 'production') {
            throw new Exception("A database error occurred. Please try again later.");
        } else {
            throw new Exception($error_message);
        }
    }
    
    /**
     * Get database statistics
     */
    public function getStats() {
        $stats = [];
        
        try {
            // Get table sizes
            $sql = "SELECT 
                        table_name,
                        table_rows,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                    FROM information_schema.tables 
                    WHERE table_schema = ?
                    ORDER BY size_mb DESC";
            
            $stats['tables'] = $this->select($sql, [DatabaseConfig::DB_NAME]);
            
            // Get connection info
            $stats['connection_info'] = [
                'host' => DatabaseConfig::DB_HOST,
                'database' => DatabaseConfig::DB_NAME,
                'charset' => DatabaseConfig::DB_CHARSET,
                'ssl_enabled' => DatabaseConfig::USE_SSL
            ];
            
        } catch (Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        return $stats;
    }
    
    /**
     * Test database connection
     */
    public static function testConnection() {
        try {
            $db = self::getInstance();
            $result = $db->selectOne("SELECT 'Connection successful' as status, NOW() as current_time");
            return [
                'success' => true,
                'message' => 'Database connection successful',
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Database Helper Functions
 */
class DatabaseHelper {
    
    /**
     * Sanitize input for database queries
     */
    public static function sanitizeInput($input) {
        if (is_string($input)) {
            return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
        }
        return $input;
    }
    
    /**
     * Generate secure hash for passwords
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Verify password hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate random salt
     */
    public static function generateSalt($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate unique reference number
     */
    public static function generateReferenceNumber($prefix = 'REF') {
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }
    
    /**
     * Format date for database storage
     */
    public static function formatDate($date, $format = 'Y-m-d H:i:s') {
        if ($date instanceof DateTime) {
            return $date->format($format);
        }
        
        if (is_string($date)) {
            $dateTime = DateTime::createFromFormat($format, $date);
            if ($dateTime) {
                return $dateTime->format($format);
            }
        }
        
        return date($format);
    }
    
    /**
     * Validate Ethiopian SSN format
     */
    public static function validateSSN($ssn) {
        // Basic validation for Ethiopian ID formats
        $patterns = [
            '/^\d{10}$/',           // National ID
            '/^[A-Z]{2}\d{7}$/',    // Passport
            '/^\d{4}\/\d{4}\/\d{4}$/' // Birth Certificate
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $ssn)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate patient portal username
     */
    public static function generatePatientUsername($firstName, $lastName, $ssn) {
        $base = strtolower(substr($firstName, 0, 3) . substr($lastName, 0, 3));
        $suffix = substr($ssn, -4);
        return $base . $suffix;
    }
}

// Environment-specific configurations
if (getenv('ENVIRONMENT') === 'development') {
    // Development settings
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} elseif (getenv('ENVIRONMENT') === 'production') {
    // Production settings
    ini_set('display_errors', 0);
    error_reporting(0);
    
    // Override sensitive configurations from environment variables
    if (getenv('DB_HOST')) {
        DatabaseConfig::DB_HOST = getenv('DB_HOST');
    }
    if (getenv('DB_NAME')) {
        DatabaseConfig::DB_NAME = getenv('DB_NAME');
    }
    if (getenv('DB_USER')) {
        DatabaseConfig::DB_USER = getenv('DB_USER');
    }
    if (getenv('DB_PASS')) {
        DatabaseConfig::DB_PASS = getenv('DB_PASS');
    }
}

// Auto-create database tables if they don't exist (development only)
if (getenv('ENVIRONMENT') === 'development' && getenv('AUTO_CREATE_TABLES') === 'true') {
    try {
        $db = Database::getInstance();
        $schema_file = __DIR__ . '/../database/schema.sql';
        
        if (file_exists($schema_file)) {
            $schema_sql = file_get_contents($schema_file);
            $db->getConnection()->exec($schema_sql);
            error_log("Database tables created successfully");
        }
    } catch (Exception $e) {
        error_log("Failed to create database tables: " . $e->getMessage());
    }
}

?>