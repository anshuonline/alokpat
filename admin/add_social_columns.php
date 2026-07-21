<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

// Only admins can run this
if (!is_logged_in() || !is_admin()) {
    die("Unauthorized");
}

global $db;
try {
    $sql = "ALTER TABLE users 
            ADD COLUMN facebook_url VARCHAR(255) NULL, 
            ADD COLUMN twitter_url VARCHAR(255) NULL, 
            ADD COLUMN youtube_url VARCHAR(255) NULL";
    $db->exec($sql);
    echo "<h1>Success!</h1><p>Social columns added successfully to the users table.</p>";
    echo "<a href='users.php'>Go back to Users</a>";
} catch (PDOException $e) {
    echo "<h1>Error</h1><p>" . escape($e->getMessage()) . "</p>";
    echo "<p>If it says 'Duplicate column name', then the columns are already added and you can safely ignore this error.</p>";
    echo "<a href='users.php'>Go back to Users</a>";
}
?>
