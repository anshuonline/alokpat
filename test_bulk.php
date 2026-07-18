<?php
$env = 'local';
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config/config.php';
$db = (new Database())->getConnection();

// Create a dummy post
$db->exec("INSERT INTO posts (title, slug, content, author_id, is_breaking, status) VALUES ('Test Post', 'test-post', 'Content', 1, 1, 'published')");
$id = $db->lastInsertId();

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['bulk_action'] = 'delete';
$_POST['post_ids'] = [$id];
$_SESSION['user'] = ['id' => 1, 'role' => 'admin'];
$_SESSION['csrf_token'] = 'test_token';
$_POST['csrf_token'] = 'test_token';

// Mock isLoggedIn
function isLoggedIn() { return true; }

ob_start();
require 'admin/posts.php';
$out = ob_get_clean();

// Check if post exists
$stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
if ($stmt->fetch()) {
    echo "FAILED: Post still exists!";
} else {
    echo "SUCCESS: Post was deleted!";
}
