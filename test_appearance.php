<?php
$env = 'local';
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config/config.php';
$db = (new Database())->getConnection();

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['theme_color'] = 'ruby';
$_SESSION['user'] = ['id' => 1, 'role' => 'admin'];
$_SESSION['csrf_token'] = 'test_token';
$_POST['csrf_token'] = 'test_token';

// Mock isLoggedIn
function isLoggedIn() { return true; }

try {
    ob_start();
    require 'admin/appearance.php';
    echo ob_get_clean();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
} catch (Error $e) {
    echo "FATAL: " . $e->getMessage();
}
