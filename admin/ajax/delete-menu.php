<?php
/**
 * AJAX - Delete Menu
 */
require_once '../../config/config.php';
requireAuth();

if (!hasPermission('manage_appearance')) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'আপনার এই কাজটি করার অনুমতি নেই']);
    exit;
}

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

$menu_id = (int)($data['id'] ?? 0);
if ($menu_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

$menuModel = new Menu();
if ($menuModel->deleteMenu($menu_id)) {
    if (function_exists('clear_page_caches')) clear_page_caches();
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
