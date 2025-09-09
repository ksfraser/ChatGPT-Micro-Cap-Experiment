<?php
// Test pages in different scenarios
set_time_limit(10);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing system_status.php (not logged in) ===\n";

try {
    ob_start();
    include __DIR__ . '/web_ui/system_status.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "UNEXPECTED: Page loaded without login redirect\n";
    
} catch (Exception $e) {
    echo "EXPECTED: " . get_class($e) . ": " . $e->getMessage() . "\n";
    
    if (method_exists($e, 'getRedirectUrl')) {
        echo "Redirect URL: " . $e->getRedirectUrl() . "\n";
    }
}

echo "\n=== Testing database.php (not logged in) ===\n";

try {
    ob_start();
    include __DIR__ . '/web_ui/database.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "UNEXPECTED: Page loaded without login redirect\n";
    
} catch (Exception $e) {
    echo "EXPECTED: " . get_class($e) . ": " . $e->getMessage() . "\n";
    
    if (method_exists($e, 'getRedirectUrl')) {
        echo "Redirect URL: " . $e->getRedirectUrl() . "\n";
    }
}

echo "\n=== Test completed ===\n";
