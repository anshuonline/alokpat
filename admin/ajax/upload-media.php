<?php
/**
 * AJAX Endpoint: Upload Media
 */

require_once '../../config/config.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    // Handle both single 'file' and multiple 'files[]'
    $files = [];
    if (isset($_FILES['files'])) {
        for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
            $files[] = [
                'name' => $_FILES['files']['name'][$i],
                'type' => $_FILES['files']['type'][$i],
                'tmp_name' => $_FILES['files']['tmp_name'][$i],
                'error' => $_FILES['files']['error'][$i],
                'size' => $_FILES['files']['size'][$i]
            ];
        }
    } elseif (isset($_FILES['file'])) {
        $files[] = $_FILES['file'];
    } else {
        echo json_encode(['status' => 'error', 'message' => 'কোনো ফাইল নির্বাচন করা হয়নি।']);
        exit;
    }

    $uploaded_data = [];
    $errors = [];
    $media = new Media();

    foreach ($files as $index => $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading " . $file['name'];
            continue;
        }

        $upload_result = uploadFile($file, 'uploads/media');
        
        if (isset($upload_result['error'])) {
            $errors[] = $upload_result['error'];
            continue;
        }
        
        // Get true original size from client if available, otherwise use uploaded file size
        $true_original_size = isset($_POST['client_original_sizes'][$index]) ? $_POST['client_original_sizes'][$index] : ($upload_result['original_size'] ?? $file['size']);
        
        // Add to media library database
        $media_data = [
            'filename' => $upload_result['filename'],
            'original_filename' => $file['name'],
            'file_path' => $upload_result['filepath'],
            'file_url' => $upload_result['file_url'],
            'file_type' => pathinfo($upload_result['filename'], PATHINFO_EXTENSION),
            'file_size' => $upload_result['file_size'],
            'original_size' => $true_original_size, // Use true original size
            'mime_type' => $upload_result['mime_type'],
            'alt_text' => pathinfo($file['name'], PATHINFO_FILENAME),
            'uploaded_by' => getCurrentUser()['id']
        ];
        
        if ($media->create($media_data)) {
            $uploaded_data[] = $media_data;
        } else {
            $errors[] = "ডাটাবেসে " . $file['name'] . " সংরক্ষণ করতে সমস্যা হয়েছে";
        }
    }
    
    if (!empty($uploaded_data)) {
        echo json_encode([
            'status' => 'success',
            'message' => count($uploaded_data) . 'টি ফাইল সফলভাবে আপলোড হয়েছে',
            'data' => $uploaded_data[0] // Return first one for selection
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => implode(", ", $errors)]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'সার্ভার ত্রুটি: ' . $e->getMessage()]);
}
