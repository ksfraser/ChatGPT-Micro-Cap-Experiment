<?php
file_put_contents('debug.log', "Starting include test\n", FILE_APPEND);

try {
    file_put_contents('debug.log', "About to include auth_check.php\n", FILE_APPEND);
    
    // Set up mock SERVER variables since auth_check uses them
    $_SERVER['REQUEST_URI'] = '/test';
    
    include 'web_ui/auth_check.php';
    
    file_put_contents('debug.log', "auth_check.php included successfully\n", FILE_APPEND);
    
    echo "Include test completed - auth_check.php loaded\n";
    
} catch (Exception $e) {
    file_put_contents('debug.log', "Exception caught: " . $e->getMessage() . "\n", FILE_APPEND);
    echo "Expected exception caught: " . $e->getMessage() . "\n";
    echo "This is expected since we're not logged in.\n";
}
?>
