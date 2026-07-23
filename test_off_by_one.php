<?php
require_once 'config/config.php';
$stmt = $db->query("SELECT mi.*, ml.location FROM menu_items mi JOIN menu_locations ml ON mi.menu_id = ml.menu_id WHERE ml.location = 'primary' ORDER BY mi.display_order ASC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($items) . "\n";
foreach ($items as $idx => $it) {
    echo $idx . ": " . $it['title'] . " (Order: " . $it['display_order'] . ")\n";
}
?>
