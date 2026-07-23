<?php
/**
 * AJAX Endpoint: Rename/Update Media Alt Text
 */

require_once '../../config/config.php';
requireAuth();

if (!hasPermission('manage_media')) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'আপনার এই কাজটি করার অনুমতি নেই']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id']) || !isset($data['alt_text'])) {
    echo json_encode(['status' => 'error', 'message' => 'প্রয়োজনীয় ডেটা প্রদান করা হয়নি']);
    exit;
}

try {
    $media = new Media();
    
    // Check if exists
    $mediaItem = $media->getById($data['id']);
    if (!$mediaItem) {
        echo json_encode(['status' => 'error', 'message' => 'মিডিয়া পাওয়া যায়নি']);
        exit;
    }
    
    // Update
    if ($media->update($data['id'], ['alt_text' => sanitize($data['alt_text'])])) {
        echo json_encode(['status' => 'success', 'message' => 'মিডিয়ার নাম সফলভাবে আপডেট হয়েছে']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ডাটাবেস আপডেট করতে সমস্যা হয়েছে']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'সার্ভার ত্রুটি: ' . $e->getMessage()]);
}
