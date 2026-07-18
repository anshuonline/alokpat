<?php
/**
 * AJAX Endpoint: Delete Media
 */

require_once '../../config/config.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'মিডিয়া আইডি প্রদান করা হয়নি']);
    exit;
}

try {
    $media = new Media();
    $mediaItem = $media->getById($data['id']);
    
    if (!$mediaItem) {
        echo json_encode(['status' => 'error', 'message' => 'মিডিয়া পাওয়া যায়নি']);
        exit;
    }
    
    // Attempt to delete physical file
    $filepath = BASE_PATH . '/' . str_replace(SITE_URL . '/', '', $mediaItem['file_url']);
    if (file_exists($filepath)) {
        @unlink($filepath);
    }
    
    // Delete from DB
    if ($media->delete($data['id'])) {
        echo json_encode(['status' => 'success', 'message' => 'মিডিয়া সফলভাবে মুছে ফেলা হয়েছে']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ডাটাবেস থেকে মিডিয়া মুছতে সমস্যা হয়েছে']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'সার্ভার ত্রুটি: ' . $e->getMessage()]);
}
