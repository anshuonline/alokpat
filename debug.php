<?php
// Turn on all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Mode Started</h1><hr>";

// Try to load config
try {
    require_once __DIR__ . '/config/config.php';
    echo "<p style='color:green;'>config.php loaded successfully.</p>";
} catch (Throwable $e) {
    echo "<p style='color:red;'>Error loading config.php:</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Try to check Permissions class
try {
    if (class_exists('Permissions')) {
        echo "<p style='color:green;'>Permissions class loaded successfully.</p>";
    } else {
        echo "<p style='color:red;'>Permissions class NOT found!</p>";
    }
} catch (Throwable $e) {
    echo "<p style='color:red;'>Error checking Permissions:</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}

echo "<hr><p>End of Debug.</p>";
