<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'config/config.php';
global $db;
$stmt = $db->query('DESCRIBE posts');
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($result);
