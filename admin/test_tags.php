<?php
require_once '../config/config.php';
$post = new Post();

// Let's create a tag and attach it to a test post (e.g., ID 1)
$db = (new Database())->getConnection();

// Create a dummy tag if not exists
$stmt = $db->query("SELECT id FROM tags LIMIT 1");
$tag_id = $stmt->fetchColumn();

if (!$tag_id) {
    $db->query("INSERT INTO tags (name, slug) VALUES ('test', 'test')");
    $tag_id = $db->lastInsertId();
}

echo "Tag ID: $tag_id\n";

$post_id = 1; // Assuming post ID 1 exists
$data = [
    'title' => 'Test',
    'tags' => [$tag_id]
];

$post->update($post_id, $data);

// Check tags
$post_data = $post->getById($post_id);
print_r($post_data['tags']);
