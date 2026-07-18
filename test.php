<?php
require 'c:/xampp/htdocs/alokpath/config/config.php';
require 'c:/xampp/htdocs/alokpath/database/Database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("DESCRIBE posts");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
