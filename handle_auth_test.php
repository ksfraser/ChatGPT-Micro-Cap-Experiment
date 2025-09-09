<?php
// Test if we can handle auth failure gracefully
echo "Starting auth handling test\n";

try {
    // Set up mock SERVER variables  
    $_SERVER['REQUEST_URI'] = '/test';
    
    echo "About to include auth_check.php\n";
    include 'web_ui/auth_check.php';
    
    echo "Auth check passed - user is logged in\n";
    
} catch (Exception $e) {
    echo "Auth exception caught: " . $e->getMessage() . "\n";
    echo "This is expected behavior when not logged in.\n";
    echo "Continuing with test...\n";
}

echo "Test completed successfully\n";
?>
