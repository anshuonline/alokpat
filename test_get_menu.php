<?php
require_once 'config/config.php';
$menuModel = new Menu();
$items = $menuModel->getMenuByLocation('primary');
echo "Count: " . count($items) . "\n";
foreach ($items as $idx => $it) {
    echo $idx . ": " . $it['title'] . "\n";
}
?>
