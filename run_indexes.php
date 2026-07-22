<?php
require_once 'config/config.php';

echo "<h1>Database Optimization (Adding Indexes)</h1>";

try {
    // Indexes to create
    $indexes = [
        'idx_slug' => ['type' => 'UNIQUE', 'columns' => 'slug'],
        'idx_status_published' => ['type' => 'INDEX', 'columns' => 'status, published_at'],
        'idx_category_status' => ['type' => 'INDEX', 'columns' => 'category_id, status, published_at'],
        'idx_breaking' => ['type' => 'INDEX', 'columns' => 'is_breaking, status, published_at'],
        'idx_trending' => ['type' => 'INDEX', 'columns' => 'is_trending, status, published_at'],
        'idx_author_status' => ['type' => 'INDEX', 'columns' => 'author_id, status, published_at']
    ];

    foreach ($indexes as $indexName => $details) {
        // Check if index exists
        $check = $db->prepare("
            SELECT COUNT(1) 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'posts' 
            AND index_name = ?
        ");
        $check->execute([$indexName]);
        $exists = $check->fetchColumn();

        if ($exists) {
            echo "<p style='color: blue;'>Index <b>{$indexName}</b> already exists. Skipping.</p>";
        } else {
            // Create index
            $db->exec("ALTER TABLE posts ADD {$details['type']} {$indexName} ({$details['columns']})");
            echo "<p style='color: green;'>Index <b>{$indexName}</b> added successfully!</p>";
        }
    }
    
    echo "<h2>Optimization Complete!</h2>";
    echo "<p>Please delete this file (run_indexes.php) from your server for security.</p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
