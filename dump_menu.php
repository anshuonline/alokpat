<?php
require_once 'config/config.php';
$stmt = $db->query("SELECT mi.*, ml.location FROM menu_items mi JOIN menu_locations ml ON mi.menu_id = ml.menu_id WHERE ml.location = 'primary' ORDER BY mi.display_order ASC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('debug_db.txt', print_r($items, true));
echo "Done DB dump.";
?>
