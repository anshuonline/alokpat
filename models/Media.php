<?php
/**
 * Media Model
 * Handles media library management
 * 
 * @package Alokpath\Models
 */
class Media {
    private $conn;
    private $table = 'media';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Add media record
     * 
     * @param array $data
     * @return bool|int
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO " . $this->table . " 
                    (filename, original_filename, file_path, file_url, file_type, file_size, 
                     mime_type, width, height, alt_text, caption, uploaded_by) 
                    VALUES 
                    (:filename, :original_filename, :file_path, :file_url, :file_type, :file_size, 
                     :mime_type, :width, :height, :alt_text, :caption, :uploaded_by)";
            
            $stmt = $this->conn->prepare($sql);
            
            $filename = sanitize($data['filename']);
            $original_filename = sanitize($data['original_filename']);
            $file_path = sanitize($data['file_path']);
            $file_url = sanitize($data['file_url']);
            $file_type = sanitize($data['file_type']);
            $mime_type = sanitize($data['mime_type']);
            $alt_text = isset($data['alt_text']) ? sanitize($data['alt_text']) : null;
            $caption = isset($data['caption']) ? sanitize($data['caption']) : null;
            $width = isset($data['width']) ? $data['width'] : null;
            $height = isset($data['height']) ? $data['height'] : null;
            $uploaded_by = isset($data['uploaded_by']) ? $data['uploaded_by'] : null;
            
            $stmt->bindParam(':filename', $filename);
            $stmt->bindParam(':original_filename', $original_filename);
            $stmt->bindParam(':file_path', $file_path);
            $stmt->bindParam(':file_url', $file_url);
            $stmt->bindParam(':file_type', $file_type);
            $stmt->bindParam(':file_size', $data['file_size'], PDO::PARAM_INT);
            $stmt->bindParam(':mime_type', $mime_type);
            $stmt->bindParam(':width', $width, PDO::PARAM_INT);
            $stmt->bindParam(':height', $height, PDO::PARAM_INT);
            $stmt->bindParam(':alt_text', $alt_text);
            $stmt->bindParam(':caption', $caption);
            $stmt->bindParam(':uploaded_by', $uploaded_by, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Create Media Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get media by ID
     * 
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Get Media By ID Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all media with pagination
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll($limit = 20, $offset = 0) {
        try {
            $sql = "SELECT m.*, u.username as uploaded_by_name 
                    FROM " . $this->table . " m
                    LEFT JOIN users u ON m.uploaded_by = u.id
                    ORDER BY m.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get All Media Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get media by file type
     * 
     * @param string $type
     * @param int $limit
     * @return array
     */
    public function getByType($type, $limit = 20) {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE file_type = :type ORDER BY created_at DESC LIMIT :limit";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Media By Type Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search media
     * 
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function search($query, $limit = 20) {
        try {
            $searchTerm = '%' . $query . '%';
            
            $sql = "SELECT * FROM " . $this->table . " 
                    WHERE filename LIKE :search 
                    OR original_filename LIKE :search 
                    OR alt_text LIKE :search 
                    OR caption LIKE :search
                    ORDER BY created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Search Media Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update media
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $params = [':id' => $id];
            
            $allowed_fields = ['alt_text', 'caption', 'filename'];
            
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $fields[] = $field . " = :" . $field;
                    $params[':' . $field] = sanitize($data[$field]);
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $sql = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update Media Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete media
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        try {
            // Get file path to delete physical file
            $media = $this->getById($id);
            
            if ($media && file_exists($media['file_path'])) {
                unlink($media['file_path']);
            }
            
            $sql = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Delete Media Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total count
     * 
     * @return int
     */
    public function getCount() {
        try {
            $sql = "SELECT COUNT(*) as total FROM " . $this->table;
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
        } catch(PDOException $e) {
            error_log("Get Count Error: " . $e->getMessage());
            return 0;
        }
    }
}
