<?php
/**
 * Category Model
 * Handles category management
 * 
 * @package Alokpath\Models
 */
class Category {
    private $conn;
    private $table = 'categories';

    public function __construct() {
        global $pdo;
        $this->conn = $pdo;
    }

    /**
     * Get all categories
     * 
     * @return array
     */
    public function getAll() {
        try {
            $sql = "SELECT * FROM " . $this->table . " ORDER BY display_order ASC, name ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get All Categories Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active categories
     * 
     * @return array
     */
    public function getActive() {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE is_active = 1 ORDER BY display_order ASC, name ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Active Categories Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get category by ID
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
            error_log("Get Category By ID Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get category by slug
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
            error_log("Get Category By Slug Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create category
     * 
     * @param array $data
     * @return bool|int
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO " . $this->table . " 
                    (name, name_en, slug, description, parent_id, icon, display_order, is_active,
                     seo_title, seo_description, seo_keywords, meta_og_title, meta_og_description, meta_og_image) 
                    VALUES 
                    (:name, :name_en, :slug, :description, :parent_id, :icon, :display_order, :is_active,
                     :seo_title, :seo_description, :seo_keywords, :meta_og_title, :meta_og_description, :meta_og_image)";
            
            $stmt = $this->conn->prepare($sql);
            
            $stmt->bindValue(':name', sanitize($data['name']));
            $stmt->bindValue(':name_en', isset($data['name_en']) ? sanitize($data['name_en']) : null);
            $stmt->bindValue(':slug', sanitize($data['slug']));
            $stmt->bindValue(':description', isset($data['description']) ? sanitize($data['description']) : null);
            $stmt->bindValue(':parent_id', isset($data['parent_id']) ? $data['parent_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':icon', isset($data['icon']) ? sanitize($data['icon']) : null);
            $stmt->bindValue(':display_order', isset($data['display_order']) ? $data['display_order'] : 0, PDO::PARAM_INT);
            $stmt->bindValue(':is_active', isset($data['is_active']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':seo_title', isset($data['seo_title']) ? sanitize($data['seo_title']) : null);
            $stmt->bindValue(':seo_description', isset($data['seo_description']) ? sanitize($data['seo_description']) : null);
            $stmt->bindValue(':seo_keywords', isset($data['seo_keywords']) ? sanitize($data['seo_keywords']) : null);
            $stmt->bindValue(':meta_og_title', isset($data['meta_og_title']) ? sanitize($data['meta_og_title']) : null);
            $stmt->bindValue(':meta_og_description', isset($data['meta_og_description']) ? sanitize($data['meta_og_description']) : null);
            $stmt->bindValue(':meta_og_image', isset($data['meta_og_image']) ? sanitize($data['meta_og_image']) : null);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Create Category Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update category
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $params = [':id' => $id];
            
            $allowed_fields = [
                'name', 'name_en', 'slug', 'description', 'parent_id', 'icon', 
                'display_order', 'is_active', 'seo_title', 'seo_description', 
                'seo_keywords', 'meta_og_title', 'meta_og_description', 'meta_og_image'
            ];
            
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $fields[] = $field . " = :" . $field;
                    
                    if (in_array($field, ['parent_id', 'display_order', 'is_active'])) {
                        $params[':' . $field] = is_int($data[$field]) ? $data[$field] : (int)$data[$field];
                    } else {
                        $params[':' . $field] = is_string($data[$field]) ? sanitize($data[$field]) : $data[$field];
                    }
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
            error_log("Update Category Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete category
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
            error_log("Delete Category Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get category post count
     * 
     * @param int $categoryId
     * @return int
     */
    public function getPostCount($categoryId) {
        try {
            $sql = "SELECT COUNT(*) as total FROM posts WHERE category_id = :category_id AND status = 'published'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':category_id', $categoryId);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
        } catch(PDOException $e) {
            error_log("Get Post Count Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get categories with post counts
     * 
     * @return array
     */
    public function getCategoriesWithCount() {
        try {
            $sql = "SELECT c.*, COUNT(p.id) as post_count 
                    FROM " . $this->table . " c
                    LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
                    WHERE c.is_active = 1
                    GROUP BY c.id
                    ORDER BY c.display_order ASC, c.name ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Categories With Count Error: " . $e->getMessage());
            return [];
        }
    }
}
