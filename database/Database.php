<?php
/**
 * Database Connection Class
 * 
 * Handles MySQL database connections using PDO
 * Provides prepared statements and error handling
 * 
 * @package Alokpath\Database
 */
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    public $conn;

    /**
     * Get database connection
     * 
     * @return PDO
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $exception) {
            error_log("Database Connection Error: " . $exception->getMessage());
            die("Database connection failed. Please try again later.");
        }

        return $this->conn;
    }

    /**
     * Execute a query with prepared statements
     * 
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get last inserted ID
     * 
     * @return string
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    /**
     * Begin transaction
     * 
     * @return bool
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit transaction
     * 
     * @return bool
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     * 
     * @return bool
     */
    public function rollBack() {
        return $this->conn->rollBack();
    }
}
