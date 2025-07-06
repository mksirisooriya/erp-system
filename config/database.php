<?php
/**
 * MySQL database connections with proper error handling
 */
class Database {
    // Database configuration
    private $host = 'localhost';        // Database host
    private $db_name = 'assignment';    // Database name
    private $username = 'root';         // Database username
    private $password = '';             // Database password
    public $conn;                       // PDO connection object

    public function getConnection() {
        $this->conn = null;
        
        try {
            // Create connection with MySQL
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                 $this->username, $this->password);
            
            // Set error mode to exceptions for better error handling
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch(PDOException $exception) {
            // Display connection error message
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>