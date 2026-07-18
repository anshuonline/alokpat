<?php
/**
 * Post Model
 * Handles article/post management
 * 
 * @package Alokpath\Models
 */
class Post {
    private $conn;
    private $table = 'posts';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Create new post
     * 
     * @param array $data
     * @return bool|int
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO " . $this->table . " 
                    (title, slug, content, excerpt, featured_image, featured_image_alt, author_id, 
                     category_id, status, is_featured, is_breaking, is_trending, publish_at, 
                     seo_title, seo_description, seo_keywords, canonical_url, meta_og_title, 
                     meta_og_description, meta_og_image, meta_twitter_card, robots_meta, schema_markup) 
                    VALUES 
                    (:title, :slug, :content, :excerpt, :featured_image, :featured_image_alt, :author_id, 
                     :category_id, :status, :is_featured, :is_breaking, :is_trending, :publish_at, 
                     :seo_title, :seo_description, :seo_keywords, :canonical_url, :meta_og_title, 
                     :meta_og_description, :meta_og_image, :meta_twitter_card, :robots_meta, :schema_markup)";
            
            $stmt = $this->conn->prepare($sql);
            
            // Assign variables for binding
            $title = sanitize($data['title']);
            $slug = sanitize($data['slug']);
            $content = $data['content'];
            $excerpt = isset($data['excerpt']) ? sanitize($data['excerpt']) : null;
            $featured_image = isset($data['featured_image']) ? sanitize($data['featured_image']) : null;
            $featured_image_alt = isset($data['featured_image_alt']) ? sanitize($data['featured_image_alt']) : null;
            $author_id = $data['author_id'];
            $category_id = isset($data['category_id']) ? $data['category_id'] : null;
            $status = sanitize($data['status']);
            $is_featured = isset($data['is_featured']) ? 1 : 0;
            $is_breaking = isset($data['is_breaking']) ? 1 : 0;
            $is_trending = isset($data['is_trending']) ? 1 : 0;
            $publish_at = isset($data['publish_at']) ? $data['publish_at'] : null;
            $seo_title = isset($data['seo_title']) ? sanitize($data['seo_title']) : null;
            $seo_description = isset($data['seo_description']) ? sanitize($data['seo_description']) : null;
            $seo_keywords = isset($data['seo_keywords']) ? sanitize($data['seo_keywords']) : null;
            $canonical_url = isset($data['canonical_url']) ? sanitize($data['canonical_url']) : null;
            $meta_og_title = isset($data['meta_og_title']) ? sanitize($data['meta_og_title']) : null;
            $meta_og_description = isset($data['meta_og_description']) ? sanitize($data['meta_og_description']) : null;
            $meta_og_image = isset($data['meta_og_image']) ? sanitize($data['meta_og_image']) : null;
            $meta_twitter_card = isset($data['meta_twitter_card']) ? $data['meta_twitter_card'] : 'summary_large_image';
            $robots_meta = isset($data['robots_meta']) ? sanitize($data['robots_meta']) : 'index,follow';
            $schema_markup = isset($data['schema_markup']) ? $data['schema_markup'] : null;
            
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':excerpt', $excerpt);
            $stmt->bindParam(':featured_image', $featured_image);
            $stmt->bindParam(':featured_image_alt', $featured_image_alt);
            $stmt->bindParam(':author_id', $author_id, PDO::PARAM_INT);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':is_featured', $is_featured, PDO::PARAM_INT);
            $stmt->bindParam(':is_breaking', $is_breaking, PDO::PARAM_INT);
            $stmt->bindParam(':is_trending', $is_trending, PDO::PARAM_INT);
            $stmt->bindParam(':publish_at', $publish_at);
            $stmt->bindParam(':seo_title', $seo_title);
            $stmt->bindParam(':seo_description', $seo_description);
            $stmt->bindParam(':seo_keywords', $seo_keywords);
            $stmt->bindParam(':canonical_url', $canonical_url);
            $stmt->bindParam(':meta_og_title', $meta_og_title);
            $stmt->bindParam(':meta_og_description', $meta_og_description);
            $stmt->bindParam(':meta_og_image', $meta_og_image);
            $stmt->bindParam(':meta_twitter_card', $meta_twitter_card);
            $stmt->bindParam(':robots_meta', $robots_meta);
            $stmt->bindParam(':schema_markup', $schema_markup);
            
            if ($stmt->execute()) {
                $postId = $this->conn->lastInsertId();
                
                // Add tags if provided
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $this->attachTags($postId, $data['tags']);
                }
                
                return $postId;
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Create Post Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update post
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
                'title', 'slug', 'content', 'excerpt', 'featured_image', 'featured_image_alt',
                'category_id', 'status', 'is_featured', 'is_breaking', 'is_trending', 'publish_at',
                'seo_title', 'seo_description', 'seo_keywords', 'canonical_url',
                'meta_og_title', 'meta_og_description', 'meta_og_image', 'meta_twitter_card',
                'robots_meta', 'schema_markup'
            ];
            
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $fields[] = $field . " = :" . $field;
                    
                    if (in_array($field, ['is_featured', 'is_breaking', 'is_trending'])) {
                        $params[':' . $field] = $data[$field] ? 1 : 0;
                    } elseif ($field === 'content') {
                        // Don't sanitize content (keep HTML tags)
                        $params[':' . $field] = $data[$field];
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
            
            if ($stmt->execute()) {
                // Update tags if provided
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $this->attachTags($id, $data['tags']);
                }
                return true;
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Update Post Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get post by ID
     * 
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        try {
            $sql = "SELECT p.*, u.full_name as author_name, u.username as author_username, u.avatar as author_avatar, 
                           c.name as category_name, c.slug as category_slug
                    FROM " . $this->table . " p
                    LEFT JOIN users u ON p.author_id = u.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.id = :id
                    LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $post = $stmt->fetch();
            
            if ($post) {
                // Get tags
                $post['tags'] = $this->getTags($id);
            }
            
            return $post;
        } catch(PDOException $e) {
            error_log("Get Post By ID Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get post by slug
     * 
     * @param string $slug
     * @return array|false
     */
    public function getBySlug($slug) {
        try {
            $sql = "SELECT p.*, u.full_name as author_name, u.username as author_username, u.avatar as author_avatar, 
                           c.name as category_name, c.slug as category_slug
                    FROM " . $this->table . " p
                    LEFT JOIN users u ON p.author_id = u.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.slug = :slug
                    AND p.status = 'published'
                    LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':slug', $slug);
            $stmt->execute();
            
            $post = $stmt->fetch();
            
            if ($post) {
                // Increment view count
                $this->incrementViews($post['id']);
                // Get tags
                $post['tags'] = $this->getTags($post['id']);
            }
            
            return $post;
        } catch(PDOException $e) {
            error_log("Get Post By Slug Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get published posts with pagination
     * 
     * @param int $limit
     * @param int $offset
     * @param int $categoryId
     * @return array
     */
    public function getPublished($limit = 10, $offset = 0, $categoryId = null) {
        try {
            $where = "WHERE p.status = 'published'";
            if ($categoryId) {
                $where .= " AND p.category_id = :category_id";
            }
            
            $sql = "SELECT p.*, u.full_name as author_name, c.name as category_name, c.slug as category_slug
                    FROM " . $this->table . " p
                    LEFT JOIN users u ON p.author_id = u.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    $where
                    ORDER BY p.published_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            if ($categoryId) {
                $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Published Posts Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get published posts by author
     * 
     * @param int $authorId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByAuthor($authorId, $limit = 10, $offset = 0) {
        try {
            $sql = "SELECT p.*, u.full_name as author_name, c.name as category_name, c.slug as category_slug
                    FROM " . $this->table . " p
                    LEFT JOIN users u ON p.author_id = u.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.status = 'published' AND p.author_id = :author_id
                    ORDER BY p.published_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':author_id', $authorId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Author Posts Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total count of posts by author
     */
    public function getCountByAuthor($authorId) {
        try {
            $sql = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE status = 'published' AND author_id = :author_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':author_id', $authorId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
        } catch(PDOException $e) {
            error_log("Get Author Posts Count Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get breaking news
     * 
     * @param int $limit
     * @return array
     */
    public function getBreakingNews($limit = 5) {
        try {
            $sql = "SELECT p.*, c.slug as category_slug
                    FROM " . $this->table . " p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.is_breaking = 1 
                    AND p.status = 'published'
                    ORDER BY p.published_at DESC
                    LIMIT :limit";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Breaking News Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get trending posts
     * 
     * @param int $limit
     * @return array
     */
    public function getTrending($limit = 10) {
        try {
            $sql = "SELECT p.*, c.slug as category_slug
                    FROM " . $this->table . " p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.is_trending = 1 
                    AND p.status = 'published'
                    ORDER BY p.view_count DESC
                    LIMIT :limit";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Trending Posts Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get featured posts
     * 
     * @param int $limit
     * @return array
     */
    public function getFeatured($limit = 5) {
        try {
            $sql = "SELECT p.*, c.slug as category_slug
                    FROM " . $this->table . " p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.is_featured = 1 
                    AND p.status = 'published'
                    ORDER BY p.published_at DESC
                    LIMIT :limit";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Featured Posts Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search posts
     * 
     * @param string $query
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search($query, $limit = 10, $offset = 0) {
        try {
            $searchTerm = '%' . $query . '%';
            
            $sql = "SELECT p.*, u.full_name as author_name, c.name as category_name
                    FROM " . $this->table . " p
                    LEFT JOIN users u ON p.author_id = u.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.status = 'published'
                    AND (p.title LIKE :search OR p.content LIKE :search OR p.excerpt LIKE :search)
                    ORDER BY p.published_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Search Posts Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get related posts
     * 
     * @param int $postId
     * @param int $categoryId
     * @param int $limit
     * @return array
     */
    public function getRelated($postId, $categoryId, $limit = 5) {
        try {
            $sql = "SELECT p.*, c.slug as category_slug
                    FROM " . $this->table . " p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.status = 'published'
                    AND p.category_id = :category_id
                    AND p.id != :post_id
                    ORDER BY p.published_at DESC
                    LIMIT :limit";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Related Posts Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete post
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
            error_log("Delete Post Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total count
     * 
     * @param string $status
     * @return int
     */
    public function getCount($status = null) {
        try {
            $sql = "SELECT COUNT(*) as total FROM " . $this->table;
            if ($status) {
                $sql .= " WHERE status = :status";
            }
            
            $stmt = $this->conn->prepare($sql);
            if ($status) {
                $stmt->bindParam(':status', $status);
            }
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
        } catch(PDOException $e) {
            error_log("Get Count Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Attach tags to post
     * 
     * @param int $postId
     * @param array $tagIds
     * @return bool
     */
    private function attachTags($postId, $tagIds) {
        try {
            // Remove existing tags
            $sql = "DELETE FROM post_tags WHERE post_id = :post_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':post_id', $postId);
            $stmt->execute();
            
            // Add new tags
            if (!empty($tagIds)) {
                $sql = "INSERT INTO post_tags (post_id, tag_id) VALUES (:post_id, :tag_id)";
                $stmt = $this->conn->prepare($sql);
                
                foreach ($tagIds as $tagId) {
                    $stmt->bindParam(':post_id', $postId);
                    $stmt->bindParam(':tag_id', $tagId);
                    $stmt->execute();
                }
            }
            
            return true;
        } catch(PDOException $e) {
            error_log("Attach Tags Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get tags for a post
     * 
     * @param int $postId
     * @return array
     */
    private function getTags($postId) {
        try {
            $sql = "SELECT t.* FROM tags t
                    INNER JOIN post_tags pt ON t.id = pt.tag_id
                    WHERE pt.post_id = :post_id
                    ORDER BY t.name ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':post_id', $postId);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Tags Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Increment view count
     * 
     * @param int $id
     * @return bool
     */
    private function incrementViews($id) {
        try {
            $sql = "UPDATE " . $this->table . " SET view_count = view_count + 1 WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Increment Views Error: " . $e->getMessage());
            return false;
        }
    }
}
