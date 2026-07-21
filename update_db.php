<?php
require 'config/config.php';

echo "<h2>Database Update Script</h2>";

try {
    global $db;
    
    // 1. Update ENUM for status
    $sql1 = "ALTER TABLE posts MODIFY status ENUM('draft', 'published', 'scheduled', 'archived') DEFAULT 'draft'";
    $db->exec($sql1);
    echo "<p>✅ Status ENUM updated to include 'scheduled'.</p>";

    // 2. Add is_live column if not exists
    try {
        $sql2 = "ALTER TABLE posts ADD COLUMN is_live TINYINT(1) DEFAULT 0 AFTER is_trending";
        $db->exec($sql2);
        echo "<p>✅ 'is_live' column added.</p>";
    } catch (PDOException $e) {
        echo "<p>⚠️ 'is_live' column might already exist: " . $e->getMessage() . "</p>";
    }

    // 3. Add flags_expiry column if not exists
    try {
        $sql3 = "ALTER TABLE posts ADD COLUMN flags_expiry DATETIME NULL AFTER is_live";
        $db->exec($sql3);
        echo "<p>✅ 'flags_expiry' column added.</p>";
    } catch (PDOException $e) {
        echo "<p>⚠️ 'flags_expiry' column might already exist: " . $e->getMessage() . "</p>";
    }

    echo "<h3>🎉 Database update complete! You can now safely delete this file (update_db.php)</h3>";
    
} catch(PDOException $e) {
    echo "<h3>❌ Error updating database:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
