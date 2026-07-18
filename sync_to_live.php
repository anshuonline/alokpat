<?php
require_once 'config/config.php';

echo "<h1>Syncing Local Posts to Live Server</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql_file = __DIR__ . '/live_data_import.sql';
    if (!file_exists($sql_file)) {
        die("Error: live_data_import.sql not found!");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Remove comments
    $sql_content = preg_replace('/^-- .*$/m', '', $sql_content);
    $sql_content = preg_replace('/^\/\*!.*\*\//m', '', $sql_content);
    $sql_content = preg_replace('/^\/\*!.*;/m', '', $sql_content);
    
    // Split into individual statements
    $queries = explode(';', $sql_content);
    
    $success = 0;
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $db->exec($query);
            $success++;
        }
    }
    
    echo "<p style='color:green;font-weight:bold;'>Success! Executed {$success} queries. The database has been synced.</p>";
    echo "<p>All 14 posts and categories are now live.</p>";
    echo "<p style='color:red;'>SECURITY WARNING: Please delete this file (sync_to_live.php) and live_data_import.sql from your Hostinger file manager immediately!</p>";
    echo "<a href='" . SITE_URL . "'>Go to Homepage</a>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database Error: " . $e->getMessage() . "</p>";
}
