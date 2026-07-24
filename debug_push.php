<?php
/**
 * Test Push Notification Script
 * Run this: https://yoursite.com/test_push.php?post_id=YOUR_POST_ID
 * Example: https://alokpat.in/test_push.php?post_id=1
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/config/config.php';
echo "<pre>";
echo "<h3>Testing Push Notification Database Query</h3>\n";

try {
    $db = (new Database())->getConnection();
    
    // Check if the 'posts' table has 'image' or 'featured_image'
    $stmt = $db->query("DESCRIBE posts");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasImage = false;
    $hasFeaturedImage = false;
    foreach ($cols as $col) {
        if ($col['Field'] === 'image') $hasImage = true;
        if ($col['Field'] === 'featured_image') $hasFeaturedImage = true;
    }
    
    echo "Column 'image' exists? " . ($hasImage ? "YES" : "NO") . "\n";
    echo "Column 'featured_image' exists? " . ($hasFeaturedImage ? "YES" : "NO") . "\n";
    
    if (!$hasImage && $hasFeaturedImage) {
        echo "\n<strong>AHA! BUG FOUND:</strong>\n";
        echo "Your code in admin/api/send_push.php is looking for 'image' but the column is actually 'featured_image'!\n";
        echo "This is why you are getting a Database error.\n";
    }
    
    // Check fcm_subscribers
    $stmt = $db->query("SELECT COUNT(*) FROM fcm_subscribers");
    $subCount = $stmt->fetchColumn();
    echo "\nTotal Subscribers: " . $subCount . "\n";
    
    if ($subCount == 0) {
        echo "<strong>WARNING:</strong> You have 0 subscribers! Push notifications cannot be sent to 0 people.\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
