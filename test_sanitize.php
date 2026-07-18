<?php
require_once 'helpers/functions.php';
$str = "Movies & TV";
$sanitized = sanitize($str);
echo "Sanitized: " . $sanitized . "\n";
$escaped = escape($sanitized);
echo "Escaped: " . $escaped . "\n";
