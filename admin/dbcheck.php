<?php
require_once '../config/config.php';
requireAuth();
if (!hasPermission('manage_settings')) die('Access denied');

$db = (new Database())->getConnection();

// Check the specific post
$stmt = $db->prepare("SELECT p.id, p.title, p.slug, p.category_id, p.status, p.parent_id, p.published_at, c.name as cat_name 
                       FROM posts p LEFT JOIN categories c ON p.category_id = c.id 
                       WHERE p.slug LIKE :slug ORDER BY p.id DESC");
$stmt->execute([':slug' => '%monsoon-session%']);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<pre style="background:#111;color:#0f0;padding:20px;font-size:13px;">';
echo "=== MATCHING POSTS ===\n\n";
foreach ($rows as $r) {
    echo "ID: {$r['id']}\n";
    echo "  Status:      {$r['status']}\n";
    echo "  category_id: " . ($r['category_id'] ?? 'NULL') . " ({$r['cat_name']})\n";
    echo "  parent_id:   " . ($r['parent_id'] ?? 'NULL') . "\n";
    echo "  published_at:" . ($r['published_at'] ?? 'NULL') . "\n";
    echo "  slug: {$r['slug']}\n\n";
}

echo "\n=== ALL CATEGORIES ===\n";
$cats = $db->query("SELECT id, name, slug FROM categories ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cats as $c) {
    echo "ID: {$c['id']} | {$c['name']} ({$c['slug']})\n";
}
echo '</pre>';
