<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Mode Started</h1><hr>";

try {
    require_once 'users.php';
} catch (Throwable $e) {
    echo "<h2>Fatal Error Caught:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<h3>Trace:</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
