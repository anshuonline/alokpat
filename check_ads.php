<?php
require_once 'config/config.php';
$db = (new Database())->getConnection();
$stmt = $db->query("SELECT * FROM settings WHERE setting_key LIKE 'ad_%'");
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($settings as $s) {
    if (!empty($s['setting_value'])) {
        echo $s['setting_key'] . ":\n" . $s['setting_value'] . "\n\n";
    }
}
