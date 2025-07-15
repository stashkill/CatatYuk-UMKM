<?php
/**
 * Database Configuration for CatatYuk
 * UMKM Cashflow Tracker Application
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'catatYuk');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty

// Application configuration
define('APP_NAME', 'CatatYuk');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/CatatYuk');

// Security configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Database connection class
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn = null;

    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            die();
        }
        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }
}

// Global database instance
function getDB() {
    static $database = null;
    if ($database === null) {
        $database = new Database();
    }
    return $database->getConnection();
}
?>

