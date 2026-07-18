<?php
/**
 * Tag Model
 * Handles tag management
 * 
 * @package Alokpath\Models
 */
class Tag {
    private $conn;
    private $table = 'tags';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Get all tags
     * 
     * @return array
     */
    public function getAll() {
        try {
            $sql = "SELECT * FROM " . $this->table . " ORDER BY name ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get All Tags Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tag by ID
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
            error_log("Get Tag By ID Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get tag by slug
     * 
     * @param string $slug
     * @return array|false
     */
    public function getBySlug($slug) {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE slug = :slug LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':slug', $slug);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Get Tag By Slug Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create tag
     * 
     * @param array $data
     * @return bool|int
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO " . $this->table . " (name, slug, description, seo_title, seo_description, seo_keywords) 
                    VALUES (:name, :slug, :description, :seo_title, :seo_description, :seo_keywords)";
            
            $stmt = $this->conn->prepare($sql);
            
            $stmt->bindParam(':name', sanitize($data['name']));
            $stmt->bindParam(':slug', sanitize($data['slug']));
            $stmt->bindParam(':description', isset($data['description']) ? sanitize($data['description']) : null);
            $stmt->bindParam(':seo_title', isset($data['seo_title']) ? sanitize($data['seo_title']) : null);
            $stmt->bindParam(':seo_description', isset($data['seo_description']) ? sanitize($data['seo_description']) : null);
            $stmt->bindParam(':seo_keywords', isset($data['seo_keywords']) ? sanitize($data['seo_keywords']) : null);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Create Tag Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update tag
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $params = [':id' => $id];
            
            $allowed_fields = ['name', 'slug', 'description', 'seo_title', 'seo_description', 'seo_keywords'];
            
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $fields[] = $field . " = :" . $field;
                    $params[':' . $field] = is_string($data[$field]) ? sanitize($data[$field]) : $data[$field];
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
            error_log("Update Tag Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete tag
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Delete Tag Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get tag post count
     * 
     * @param int $tagId
     * @return int
     */
    public function getPostCount($tagId) {
        try {
            $sql = "SELECT COUNT(*) as total FROM post_tags WHERE tag_id = :tag_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':tag_id', $tagId);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
        } catch(PDOException $e) {
            error_log("Get Post Count Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get popular tags
     * 
     * @param int $limit
     * @return array
     */
    public function getPopular($limit = 20) {
        try {
            $sql = "SELECT t.*, COUNT(pt.post_id) as post_count 
                    FROM " . $this->table . " t
                    INNER JOIN post_tags pt ON t.id = pt.tag_id
                    GROUP BY t.id
                    ORDER BY post_count DESC
                    LIMIT :limit";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Popular Tags Error: " . $e->getMessage());
            return [];
        }
    }
}
