<?php
/**
 * AJAX Endpoint: Get Media Library
 */

require_once '../../config/config.php';
requireAuth();

header('Content-Type: application/json');

try {
    $mediaModel = new Media();
    
    // Pagination params
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 24; // Load 24 images per page
    $offset = ($page - 1) * $limit;
    
    $mediaList = $mediaModel->getAll($limit, $offset);
    $total = $mediaModel->getCount();
    
    echo json_encode([
        'status' => 'success',
        'data' => $mediaList,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch media library'
    ]);
}
