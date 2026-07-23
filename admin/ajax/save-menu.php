<?php
/**
 * AJAX - Save Menu
 */
require_once '../../config/config.php';
requireAuth();

header('Content-Type: application/json');
$rawInput = file_get_contents('php://input');
file_put_contents(__DIR__ . '/../../debug_json.txt', $rawInput);
$data = json_decode($rawInput, true);

if (!isset($data['name']) || trim($data['name']) === '') {
    echo json_encode(['status' => 'error', 'message' => 'মেনুর নাম আবশ্যক']);
    exit;
}

$menu_id = (int)($data['menu_id'] ?? 0);
$name = trim($data['name']);
$locations = $data['locations'] ?? [];
$items = $data['items'] ?? [];

$menuModel = new Menu();

if ($menu_id === 0) {
    // Create new menu
    $menu_id = $menuModel->createMenu($name);
    if (!$menu_id) {
        echo json_encode(['status' => 'error', 'message' => 'মেনু তৈরি করা যায়নি']);
        exit;
    }
}

// Save locations and items
$success = $menuModel->saveMenu($menu_id, $name, $locations, $items);

if ($success) {
    if (function_exists('clear_page_caches')) clear_page_caches();
    echo json_encode(['status' => 'success', 'new_id' => $menu_id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'ডাটাবেস এরর']);
}
