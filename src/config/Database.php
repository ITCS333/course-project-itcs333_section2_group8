<?php

class Database {
    private $host = "localhost";
    private $db_name = "school_management";
    private $username = "admin";
    private $password = "password";
    private $pdo;

    public function getConnection() {
        if ($this->pdo == null) {
            try {
                $this->pdo = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                echo "Connection error: " . $e->getMessage();
            }
        }
        return $this->pdo;
    }
}

?>