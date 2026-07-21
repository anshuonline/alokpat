<?php
require_once 'config/config.php';

try {
    $db = (new Database())->getConnection();
    
    echo "<h1>Database Update for Live Blog Feature</h1>";
    
    // 1. Add post_type to posts table
    try {
        $db->exec("ALTER TABLE posts ADD COLUMN post_type ENUM('standard', 'live_blog') DEFAULT 'standard' AFTER id");
        echo "<p style='color:green;'>Added post_type column to posts table.</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color:orange;'>post_type column already exists.</p>";
        } else {
            echo "<p style='color:red;'>Error adding post_type: " . $e->getMessage() . "</p>";
        }
    }
    
    // 2. Create post_updates table
    try {
        $sql = "CREATE TABLE IF NOT EXISTS post_updates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            update_time DATETIME NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $db->exec($sql);
        echo "<p style='color:green;'>Created post_updates table successfully.</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error creating post_updates table: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Update Complete!</h3>";
    echo "<p><a href='" . ADMIN_URL . "'>Go to Admin Dashboard</a></p>";
    
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
