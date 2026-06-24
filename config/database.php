<?php
// config/database.php
class Database {
    private $host = "localhost";
    private $db_name = "marketnearme";
    private $username = "root";
    private $password = "";
    private $conn;
    private $error_mode = PDO::ERRMODE_EXCEPTION;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $options = [
                PDO::ATTR_ERRMODE => $this->error_mode,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_PERSISTENT => false
            ];
            
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                $options
            );
        } catch(PDOException $exception) {
            error_log("Database Connection Error: " . $exception->getMessage());
            die("Database connection failed. Please try again later.");
        }
        
        return $this->conn;
    }
}
?>