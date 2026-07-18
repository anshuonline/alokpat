<?php
/**
 * Upload Image from TinyMCE Editor
 * Handles image uploads from the rich text editor
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();

// Check CSRF token
if (!validateCSRFRequest()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

// Upload the file
$upload_result = uploadFile($_FILES['file'], 'uploads/posts/editor');

if (isset($upload_result['error'])) {
    http_response_code(500);
    echo json_encode(['error' => $upload_result['error']]);
    exit;
}

// Return the file URL
http_response_code(200);
echo json_encode([
    'location' => $upload_result['file_url']
]);
exit;
