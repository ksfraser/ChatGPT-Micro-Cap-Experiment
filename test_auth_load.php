<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing auth_check.php loading...\n";

try {
    require_once __DIR__ . '/web_ui/auth_check.php';
    echo "auth_check.php loaded successfully\n";
    
    if (function_exists('requireLogin')) {
        echo "requireLogin function is available\n";
    } else {
        echo "requireLogin function is NOT available\n";
    }
    
    if (function_exists('requireAdmin')) {
        echo "requireAdmin function is available\n";
    } else {
        echo "requireAdmin function is NOT available\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
