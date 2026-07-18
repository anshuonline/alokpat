<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'reorder';
$_POST['order'] = [2, 1, 3]; // mock category IDs
$env = 'local';
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config/config.php';
// We need to bypass requireAuth() for this test, but admin/categories.php calls it.
// Let's just mock the auth session.
$_SESSION['user'] = ['id' => 1, 'role' => 'admin'];
ob_start();
require 'admin/categories.php';
$out = ob_get_clean();
echo $out;
