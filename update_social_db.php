<?php
require_once 'config/config.php';
global $db;
try {
    $sql = "ALTER TABLE users 
            ADD COLUMN facebook_url VARCHAR(255) NULL, 
            ADD COLUMN twitter_url VARCHAR(255) NULL, 
            ADD COLUMN youtube_url VARCHAR(255) NULL";
    $db->exec($sql);
    echo "Columns added successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
