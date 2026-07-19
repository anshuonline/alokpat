<?php
require_once '../config/config.php';

try {
    $db = (new Database())->getConnection();
    $sql = "ALTER TABLE posts MODIFY status ENUM('draft', 'published', 'scheduled', 'archived', 'trashed') DEFAULT 'draft'";
    $db->exec($sql);
    echo "Migration successful!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
