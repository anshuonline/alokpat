<?php
$env = 'local';
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config/config.php';
$db = (new Database())->getConnection();
$db->exec("UPDATE categories SET seo_title = 'Latest Politics News', seo_description = 'Get the latest politics updates.', seo_keywords = 'politics, news, updates' WHERE slug = 'politics'");
