<?php
require_once 'config/config.php';

echo "<h1>Generating Latest News for All Categories...</h1>";

try {
    $db = (new Database())->getConnection();
    
    // Clear existing posts
    $db->exec("TRUNCATE TABLE posts");
    echo "<p>Existing posts cleared.</p>";
    
    $categoryModel = new Category();
    $postModel = new Post();
    
    $categories = $categoryModel->getAll();
    $total_inserted = 0;
    
    foreach ($categories as $cat) {
        $cat_name = $cat['name'];
        $cat_id = $cat['id'];
        
        for ($i = 1; $i <= 10; $i++) {
            // Generate dynamic title and content
            $title = "{$cat_name} এর গুরুত্বপূর্ণ খবর - আপডেট {$i}";
            $slug = "{$cat['slug']}-update-" . time() . "-{$i}";
            $excerpt = "আজকের {$cat_name} সম্পর্কিত এই বিশেষ আপডেটে আমরা আলোচনা করব কিছু গুরুত্বপূর্ণ বিষয় নিয়ে। বিস্তারিত জানতে পড়ুন...";
            $content = "<p>এটি একটি বিস্তারিত খবর যা <strong>{$cat_name}</strong> বিভাগ থেকে নেওয়া হয়েছে। বর্তমানে এই বিষয়টি নিয়ে প্রচুর আলোচনা হচ্ছে। আমরা আমাদের পাঠকদের জন্য এই বিষয়ে সবচেয়ে সঠিক এবং দ্রুত আপডেট নিয়ে এসেছি।</p><p>এই খবরের সাথে জড়িত অন্যান্য দিকগুলোও আমরা ধাপে ধাপে প্রকাশ করব। আরও তথ্যের জন্য আমাদের সাথে থাকুন।</p>";
            
            // Randomly make some posts featured or trending
            $is_featured = (rand(1, 10) > 8) ? 1 : 0; // 20% chance
            $is_trending = (rand(1, 10) > 7) ? 1 : 0; // 30% chance
            $is_breaking = (rand(1, 10) > 9) ? 1 : 0; // 10% chance
            
            $postData = [
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $excerpt,
                'content' => $content,
                'category_id' => $cat_id,
                'author_id' => 1,
                'status' => 'published',
                'is_breaking' => $is_breaking,
                'is_featured' => $is_featured,
                'is_trending' => $is_trending,
                'seo_title' => $title,
                'meta_description' => $excerpt,
                'focus_keywords' => "{$cat_name}, খবর, আপডেট",
                'published_at' => date('Y-m-d H:i:s', strtotime("-" . rand(0, 7) . " days"))
            ];
            
            if ($postModel->create($postData)) {
                $total_inserted++;
            }
        }
        echo "<p>Added 10 news items to category: <b>{$cat_name}</b></p>";
    }
    
    echo "<p style='color:green;font-weight:bold;'>Success! Successfully generated {$total_inserted} posts across all categories.</p>";
    echo "<p style='color:red;'>SECURITY WARNING: Please delete this file (generate_news.php) from your Hostinger file manager immediately!</p>";
    echo "<a href='" . SITE_URL . "'>Go to Homepage</a>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
