<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';

try {
    $db = (new Database())->getConnection();
    echo "Database Connected successfully.<br>";
    
    // Check if table exists
    $result = $db->query("SHOW TABLES LIKE 'admin_messages'");
    if ($result && $result->rowCount() > 0) {
        echo "Table 'admin_messages' EXISTS.<br>";
        
        // Try the query that's in admin layout
        $unread_stmt = $db->prepare("SELECT COUNT(*) FROM admin_messages WHERE receiver_id = 1 AND is_read = 0");
        $unread_stmt->execute();
        $count = $unread_stmt->fetchColumn();
        echo "Unread query executed successfully. Count: $count <br>";
    } else {
        echo "Table 'admin_messages' DOES NOT EXIST! This is causing the 500 error.<br>";
        echo "Please run the SQL query to create it.<br>";
    }
} catch (Exception $e) {
    echo "Exception Caught:<br>";
    echo $e->getMessage() . "<br>";
}
