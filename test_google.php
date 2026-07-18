<?php
$env = 'local';
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config/config.php';
$db = (new Database())->getConnection();
$db->exec("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES ('google_search_console', '<meta name=\"google-site-verification\" content=\"test-verification-code\" />', 'text') ON DUPLICATE KEY UPDATE setting_value='<meta name=\"google-site-verification\" content=\"test-verification-code\" />'");
$db->exec("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES ('google_analytics_code', '<script>console.log(\"GA4 Mock Script\");</script>', 'text') ON DUPLICATE KEY UPDATE setting_value='<script>console.log(\"GA4 Mock Script\");</script>'");
