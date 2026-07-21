<?php
/**
 * AJAX Live Update Handler
 * Handles CRUD operations for Live Blog Timeline updates
 * 
 * @package Alokpath\Admin
 */
require_once '../config/config.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Verify CSRF
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'CSRF validation failed']);
    exit;
}

$action = $_POST['action'] ?? '';
$updateModel = new PostUpdate();

try {
    switch ($action) {
        case 'create':
            $post_id = $_POST['post_id'] ?? 0;
            $content = $_POST['content'] ?? '';
            $update_time = $_POST['update_time'] ?? date('Y-m-d H:i:s');
            
            if (empty($post_id) || empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            $data = [
                'post_id' => $post_id,
                'update_time' => date('Y-m-d H:i:s', strtotime($update_time)),
                'content' => $content
            ];
            
            $id = $updateModel->create($data);
            if ($id) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Update added successfully',
                    'id' => $id
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add update']);
            }
            break;
            
        case 'update':
            $id = $_POST['id'] ?? 0;
            $content = $_POST['content'] ?? '';
            $update_time = $_POST['update_time'] ?? date('Y-m-d H:i:s');
            
            if (empty($id) || empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            $data = [
                'update_time' => date('Y-m-d H:i:s', strtotime($update_time)),
                'content' => $content
            ];
            
            if ($updateModel->update($id, $data)) {
                echo json_encode(['success' => true, 'message' => 'Update saved successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save update']);
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'Missing update ID']);
                exit;
            }
            
            if ($updateModel->delete($id)) {
                echo json_encode(['success' => true, 'message' => 'Update deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete update']);
            }
            break;
            
        case 'fetch':
            $post_id = $_POST['post_id'] ?? 0;
            if (empty($post_id)) {
                echo json_encode(['success' => false, 'message' => 'Missing post ID']);
                exit;
            }
            
            $updates = $updateModel->getByPostId($post_id);
            // Format dates for display
            foreach ($updates as &$upd) {
                $upd['display_time'] = date('h:i A, d M', strtotime($upd['update_time']));
            }
            
            echo json_encode(['success' => true, 'updates' => $updates]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
