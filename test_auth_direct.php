<?php
// Test just the auth_check.php loading specifically
set_time_limit(5);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing auth_check.php directly ===\n";

try {
    require_once __DIR__ . '/web_ui/auth_check.php';
    echo "SUCCESS: auth_check.php loaded successfully\n";
    
    if (isset($currentUser)) {
        echo "Current user: " . print_r($currentUser, true) . "\n";
    } else {
        echo "No current user set (expected for non-logged-in state)\n";
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Type: " . get_class($e) . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}

echo "=== Test completed ===\n";
