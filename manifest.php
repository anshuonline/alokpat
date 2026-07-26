<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$setting = new Setting();
$site_name = $setting->get('site_name') ?: 'আলোকপাত';
$site_logo = $setting->get('site_logo') ?: SITE_URL . '/assets/images/logo.png';
$theme_color = $setting->get('theme_color_primary') ?: '#2563eb';

// The browser expects application/manifest+json
header('Content-Type: application/manifest+json; charset=utf-8');

$manifest = [
    "name" => $site_name,
    "short_name" => $site_name,
    "start_url" => SITE_URL . "/",
    "display" => "standalone",
    "background_color" => "#ffffff",
    "theme_color" => $theme_color,
    "icons" => [
        [
            "src" => $site_logo,
            "sizes" => "192x192",
            "type" => "image/png"
        ],
        [
            "src" => $site_logo,
            "sizes" => "512x512",
            "type" => "image/png"
        ]
    ]
];

echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
