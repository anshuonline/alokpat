<?php
$env = 'local';
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config/config.php';
$db = (new Database())->getConnection();
$db->exec("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES ('home_seo_title', 'Alokpath Bangla News', 'text') ON DUPLICATE KEY UPDATE setting_value='Alokpath Bangla News'");
$db->exec("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES ('home_seo_description', 'The best news portal in Bengali', 'text') ON DUPLICATE KEY UPDATE setting_value='The best news portal in Bengali'");
$db->exec("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES ('home_seo_keywords', 'news, bangla', 'text') ON DUPLICATE KEY UPDATE setting_value='news, bangla'");
