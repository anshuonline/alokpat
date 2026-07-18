<?php
$env = 'local';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['filter'] = 'breaking';
require_once 'config/config.php';
// Mock auth
$_SESSION['user'] = ['id' => 1, 'role' => 'admin'];
// Capture output
ob_start();
require 'admin/posts.php';
$out = ob_get_clean();
if (strpos($out, 'সংবাদ ব্যবস্থাপনা') !== false) {
    echo "SUCCESS: Page rendered!";
} else {
    echo "FAILED: " . substr($out, 0, 500);
}
