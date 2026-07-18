<?php
/**
 * AJAX - Delete Menu
 */
require_once '../../config/config.php';
requireAuth();

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

$menu_id = (int)($data['id'] ?? 0);
if ($menu_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

$menuModel = new Menu();
if ($menuModel->deleteMenu($menu_id)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
