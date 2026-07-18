<?php
$env = 'local';
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config/config.php';
$db = (new Database())->getConnection();
$db->exec("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES ('seo_title_format', '%pagetitle% | %sitename%', 'text') ON DUPLICATE KEY UPDATE setting_value='%pagetitle% | %sitename%'");
