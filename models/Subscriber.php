<?php
/**
 * Subscriber Model
 * 
 * @package Alokpath\Models
 */

class Subscriber {
    private $conn;
    private $table = 'subscribers';

    /**
     * Constructor
     */
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        
        // Create table if not exists (runs silently on first instantiation)
        $this->createTableIfNotExists();
    }

    /**
     * Create subscribers table if it doesn't exist
     */
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS " . $this->table . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            status ENUM('active', 'unsubscribed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        try {
            $this->conn->exec($sql);
        } catch (PDOException $e) {
            error_log("Failed to create subscribers table: " . $e->getMessage());
        }
    }

    /**
     * Add new subscriber
     * 
     * @param string $email
     * @return bool|array True if success, array with error if fail
     */
    public function subscribe($email) {
        try {
            // Check if already exists
            $stmt = $this->conn->prepare("SELECT id, status FROM " . $this->table . " WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if ($existing['status'] === 'unsubscribed') {
                    // Reactivate
                    $updateStmt = $this->conn->prepare("UPDATE " . $this->table . " SET status = 'active' WHERE id = :id");
                    $updateStmt->bindParam(':id', $existing['id']);
                    return $updateStmt->execute();
                }
                return ['error' => 'already_subscribed'];
            }

            // Insert new
            $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (email, status) VALUES (:email, 'active')");
            $stmt->bindParam(':email', $email);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Subscriber Add Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get subscribers with filters
     * 
     * @param string $filter 'all', 'weekly', 'monthly'
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getSubscribers($filter = 'all', $limit = 50, $offset = 0) {
        try {
            $where = "1=1";
            
            if ($filter === 'weekly') {
                $where = "created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            } elseif ($filter === 'monthly') {
                $where = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            }

            $sql = "SELECT * FROM " . $this->table . " WHERE $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get Subscribers Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total count for pagination
     * 
     * @param string $filter
     * @return int
     */
    public function getTotalCount($filter = 'all') {
        try {
            $where = "1=1";
            
            if ($filter === 'weekly') {
                $where = "created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            } elseif ($filter === 'monthly') {
                $where = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            }

            $sql = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE $where";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['total'] ?? 0);
        } catch(PDOException $e) {
            return 0;
        }
    }

    /**
     * Delete subscriber
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
}
