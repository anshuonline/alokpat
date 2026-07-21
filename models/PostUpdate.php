<?php
/**
 * PostUpdate Model
 * Handles live blog timeline updates
 * 
 * @package Alokpath\Models
 */
class PostUpdate {
    private $conn;
    private $table = 'post_updates';

    public function __construct() {
        global $db;
        $this->conn = $db;
    }

    /**
     * Get all updates for a specific post
     * 
     * @param int $post_id
     * @return array
     */
    public function getByPostId($post_id) {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE post_id = :post_id ORDER BY update_time DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get Post Updates Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get single update by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Create new update
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO " . $this->table . " (post_id, update_time, content) VALUES (:post_id, :update_time, :content)";
            $stmt = $this->conn->prepare($sql);
            
            $post_id = (int)$data['post_id'];
            $update_time = $data['update_time'];
            $content = $data['content']; // HTML allowed
            
            $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
            $stmt->bindParam(':update_time', $update_time);
            $stmt->bindParam(':content', $content);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch(PDOException $e) {
            error_log("Create Post Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update existing update
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE " . $this->table . " SET update_time = :update_time, content = :content WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            
            $update_time = $data['update_time'];
            $content = $data['content'];
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':update_time', $update_time);
            $stmt->bindParam(':content', $content);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update Post Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete update
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>
