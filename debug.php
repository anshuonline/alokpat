<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Page</h2>";

// Direct PDO connection - no require needed
try {
    $dsn = "mysql:host=localhost;dbname=u388169091_alokpat;charset=utf8mb4";
    $pdo = new PDO($dsn, 'u388169091_alokpat', '@Alokpat.in1234');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "1. Database Connected OK<br>";
    
    // Check if table exists
    $result = $pdo->query("SHOW TABLES LIKE 'admin_messages'");
    if ($result->rowCount() > 0) {
        echo "2. Table 'admin_messages' EXISTS<br>";
    } else {
        echo "2. Table 'admin_messages' DOES NOT EXIST - yahi 500 ka reason hai<br>";
    }
    
    // Test admin layout include
    echo "3. Now testing config include...<br>";
    require_once __DIR__ . '/config/config.php';
    echo "4. Config loaded OK<br>";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}
