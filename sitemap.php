<?php
require_once 'config/config.php';

// Define the type of sitemap
$type = isset($_GET['type']) ? $_GET['type'] : 'index';

// Set correct header for XML
header("Content-Type: text/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

global $db;

if ($type === 'index') {
    // Current date for lastmod of the index
    $currentDate = date('Y-m-d\TH:i:sP');
    
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    $sitemaps = ['posts', 'categories', 'authors'];
    foreach ($sitemaps as $sitemap) {
        echo '  <sitemap>' . "\n";
        echo '      <loc>' . SITE_URL . '/sitemap-' . $sitemap . '.xml</loc>' . "\n";
        echo '      <lastmod>' . $currentDate . '</lastmod>' . "\n";
        echo '  </sitemap>' . "\n";
    }
    
    echo '</sitemapindex>';
} 
else if ($type === 'posts') {
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    try {
        $sql = "SELECT p.slug, p.updated_at, c.slug as category_slug 
                FROM posts p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'published' AND (p.robots_meta IS NULL OR p.robots_meta NOT LIKE '%noindex%') 
                ORDER BY p.created_at DESC LIMIT 5000";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($posts as $post) {
            $lastmod = date('Y-m-d\TH:i:sP', strtotime($post['updated_at']));
            $url = url_for_post($post);
            
            echo '  <url>' . "\n";
            echo '      <loc>' . htmlspecialchars($url) . '</loc>' . "\n";
            echo '      <lastmod>' . $lastmod . '</lastmod>' . "\n";
            echo '      <changefreq>daily</changefreq>' . "\n";
            echo '      <priority>0.8</priority>' . "\n";
            echo '  </url>' . "\n";
        }
    } catch(PDOException $e) {
        error_log("Sitemap Posts Error: " . $e->getMessage());
    }
    
    echo '</urlset>';
}
else if ($type === 'categories') {
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    try {
        $sql = "SELECT slug, updated_at FROM categories WHERE is_active = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($categories as $cat) {
            // Some categories might not have updated_at, fallback to current time or a default
            $lastmod = isset($cat['updated_at']) ? date('Y-m-d\TH:i:sP', strtotime($cat['updated_at'])) : date('Y-m-d\TH:i:sP');
            $url = SITE_URL . '/category.php?slug=' . urlencode($cat['slug']);
            
            echo '  <url>' . "\n";
            echo '      <loc>' . htmlspecialchars($url) . '</loc>' . "\n";
            echo '      <lastmod>' . $lastmod . '</lastmod>' . "\n";
            echo '      <changefreq>weekly</changefreq>' . "\n";
            echo '      <priority>0.6</priority>' . "\n";
            echo '  </url>' . "\n";
        }
    } catch(PDOException $e) {
        error_log("Sitemap Categories Error: " . $e->getMessage());
    }
    
    echo '</urlset>';
}
else if ($type === 'authors') {
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    try {
        $sql = "SELECT username, created_at FROM users WHERE status = 'active'";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($authors as $author) {
            $lastmod = isset($author['created_at']) ? date('Y-m-d\TH:i:sP', strtotime($author['created_at'])) : date('Y-m-d\TH:i:sP');
            $url = SITE_URL . '/author.php?username=' . urlencode($author['username']);
            
            echo '  <url>' . "\n";
            echo '      <loc>' . htmlspecialchars($url) . '</loc>' . "\n";
            echo '      <lastmod>' . $lastmod . '</lastmod>' . "\n";
            echo '      <changefreq>monthly</changefreq>' . "\n";
            echo '      <priority>0.5</priority>' . "\n";
            echo '  </url>' . "\n";
        }
    } catch(PDOException $e) {
        error_log("Sitemap Authors Error: " . $e->getMessage());
    }
    
    echo '</urlset>';
}
?>
