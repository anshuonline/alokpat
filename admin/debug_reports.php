<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Mode Started</h1>";

try {
    require_once 'reports.php';
} catch (Throwable $e) {
    echo "<h2>Fatal Error Caught:</h2>";
    echo "<p><strong>" . $e->getMessage() . "</strong></p>";
    echo "<pre>Trace:\n" . $e->getTraceAsString() . "</pre>";
}
