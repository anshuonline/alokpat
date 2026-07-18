<?php
/**
 * AJAX Live Search Endpoint
 */
require_once 'config/config.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

if (empty($query) || mb_strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$postModel = new Post();
// Get top 5 results for live search
$results = $postModel->search($query, 5, 0);

$formatted_results = [];
if ($results && is_array($results)) {
    foreach ($results as $post) {
        $formatted_results[] = [
            'title' => escape($post['title']),
            'url' => url_for_post($post),
            'image' => get_image_url($post['featured_image'], 'thumbnail'),
            'date' => format_date($post['published_at'] ?? $post['created_at'])
        ];
    }
}

echo json_encode($formatted_results);
exit;
