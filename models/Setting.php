<?php
/**
 * Setting Model
 * Handles website settings management
 * 
 * @package Alokpath\Models
 */
class Setting {
    private $conn;
    private $table = 'settings';
    private static $cache = null; // In-memory cache: loaded once per request

    public function __construct() {
        global $db;
        $this->conn = $db;
    }

    /**
     * Load all settings into memory (1 query for entire request lifecycle)
     */
    private function loadCache() {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                $stmt = $this->conn->query("SELECT setting_key, setting_value FROM " . $this->table);
                while ($row = $stmt->fetch()) {
                    self::$cache[$row['setting_key']] = $row['setting_value'];
                }
            } catch (PDOException $e) {
                error_log("Load Settings Cache Error: " . $e->getMessage());
            }
        }
    }

    /**
     * Clear the static cache (call after updates)
     */
    public static function clearCache() {
        self::$cache = null;
    }

    /**
     * Get all settings
     * 
     * @return array
     */
    public function getAll() {
        try {
            $sql = "SELECT * FROM " . $this->table . " ORDER BY setting_key ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get All Settings Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get setting by key (served from memory cache)
     * 
     * @param string $key
     * @return string|false
     */
    public function get($key) {
        $this->loadCache();
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        return false;
    }

    /**
     * Get multiple settings by keys
     * 
     * @param array $keys
     * @return array
     */
    public function getMultiple($keys) {
        $this->loadCache();
        $settings = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, self::$cache)) {
                $settings[$key] = self::$cache[$key];
            }
        }
        return $settings;
    }

    /**
     * Update setting
     * 
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function update($key, $value) {
        try {
            $sql = "UPDATE " . $this->table . " SET setting_value = :value WHERE setting_key = :key";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':value', $value);
            $stmt->bindParam(':key', $key);
            $result = $stmt->execute();
            self::clearCache();
            return $result;
        } catch(PDOException $e) {
            error_log("Update Setting Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update multiple settings
     * 
     * @param array $settings
     * @return bool
     */
    public function updateMultiple($settings) {
        try {
            $this->conn->beginTransaction();
            
            foreach ($settings as $key => $value) {
                $sql = "UPDATE " . $this->table . " SET setting_value = :value WHERE setting_key = :key";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindParam(':value', $value);
                $stmt->bindParam(':key', $key);
                $stmt->execute();
            }
            
            $this->conn->commit();
            self::clearCache();
            return true;
        } catch(PDOException $e) {
            $this->conn->rollBack();
            error_log("Update Multiple Settings Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create setting
     * 
     * @param string $key
     * @param string $value
     * @param string $type
     * @param string $description
     * @return bool|int
     */
    public function create($key, $value, $type = 'text', $description = '') {
        try {
            $sql = "INSERT INTO " . $this->table . " 
                    (setting_key, setting_value, setting_type, description) 
                    VALUES (:key, :value, :type, :description)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':key', $key);
            $stmt->bindParam(':value', $value);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':description', $description);
            
            if ($stmt->execute()) {
                self::clearCache();
                return $this->conn->lastInsertId();
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Create Setting Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete setting
     * 
     * @param string $key
     * @return bool
     */
    public function delete($key) {
        try {
            $sql = "DELETE FROM " . $this->table . " WHERE setting_key = :key";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':key', $key);
            $result = $stmt->execute();
            self::clearCache();
            return $result;
        } catch(PDOException $e) {
            error_log("Delete Setting Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get site information
     * 
     * @return array
     */
    public function getSiteInfo() {
        $keys = [
            'site_name', 'site_name_en', 'site_tagline', 'site_logo', 'footer_logo',
            'site_email', 'site_phone', 'site_address', 'site_header_html',
            'google_analytics_id', 'facebook_url', 'twitter_url', 'youtube_url', 'instagram_url',
            'whatsapp_channel_url', 'enable_comments', 'enable_sharing', 'posts_per_page'
        ];
        
        return $this->getMultiple($keys);
    }
}
