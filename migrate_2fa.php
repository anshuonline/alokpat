<?php
/**
 * One-time Migration Script for 2FA
 * RUN ONCE AND THEN DELETE THIS FILE!
 */

require_once 'config/config.php';

try {
    $db = (new Database())->getConnection();
    
    // Check if columns already exist
    $stmt = $db->query("SHOW COLUMNS FROM `users` LIKE 'two_factor_secret'");
    if ($stmt->rowCount() > 0) {
        echo "<h3 style='color: blue;'>Columns already exist! No changes were made.</h3>";
        echo "<p>You can safely delete this file.</p>";
    } else {
        $sql = "ALTER TABLE `users` 
                ADD COLUMN `two_factor_secret` VARCHAR(255) NULL DEFAULT NULL AFTER `status`,
                ADD COLUMN `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `two_factor_secret`";
                
        $db->exec($sql);
        echo "<h3 style='color: green;'>Success! Added 2FA columns to the users table.</h3>";
        echo "<p style='color: red; font-weight: bold;'>Security Warning: You MUST delete this file (migrate_2fa.php) immediately!</p>";
    }
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Database Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
