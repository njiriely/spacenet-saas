<?php
namespace SpaceNet;

require_once 'DatabaseConfig.php';
// includes/Database.php

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . \DatabaseConfig::HOST . ";dbname=" . \DatabaseConfig::DB_NAME . ";charset=" . \DatabaseConfig::CHARSET;
            $this->connection = new \PDO($dsn, \DatabaseConfig::USERNAME, \DatabaseConfig::PASSWORD, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (\PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}