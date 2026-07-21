<?php
require_once 'config/config.php';

try {
    $db = (new Database())->getConnection();
    
    echo "<h1>Database Update for Trashed Status</h1>";
    
    try {
        $db->exec("ALTER TABLE posts MODIFY COLUMN status ENUM('draft', 'published', 'scheduled', 'archived', 'trashed', 'unlisted') DEFAULT 'draft'");
        echo "<p style='color:green;'>Updated status column to include 'trashed' and 'unlisted'.</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error updating status: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
