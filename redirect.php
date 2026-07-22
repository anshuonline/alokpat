<?php
/**
 * Short Link Redirector
 * Handles routing of short codes (e.g. /uXXXX) to the actual article
 * Tracks click counts.
 */
require_once 'config/config.php';
require_once 'helpers/functions.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['code']) || empty($_GET['code'])) {
    redirect(SITE_URL);
}

$code = sanitize($_GET['code']);

try {
    // Query the short link
    $stmt = $db->prepare("SELECT post_id FROM short_links WHERE short_code = ? LIMIT 1");
    $stmt->execute([$code]);
    $link = $stmt->fetch();

    if (!$link) {
        // Short link not found, redirect to home
        redirect(SITE_URL);
    }

    $post_id = $link['post_id'];

    // Increment click count
    $updateStmt = $db->prepare("UPDATE short_links SET clicks = clicks + 1 WHERE short_code = ?");
    $updateStmt->execute([$code]);

    // Get the post details to construct the actual URL
    // We need post slug and category slug
    $postStmt = $db->prepare("
        SELECT p.slug AS post_slug, c.slug AS cat_slug 
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.status = 'published' LIMIT 1
    ");
    $postStmt->execute([$post_id]);
    $post = $postStmt->fetch();

    if (!$post) {
        // Post might be deleted or unpublished
        redirect(SITE_URL);
    }
} catch (PDOException $e) {
    die("Database Error in Redirect: " . $e->getMessage());
}

$article_url = SITE_URL . '/' . $post['cat_slug'] . '/' . $post['post_slug'] . '.html';

// 301 Permanent Redirect to the full article
header("HTTP/1.1 301 Moved Permanently");
header("Location: " . $article_url);
exit();
