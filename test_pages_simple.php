<?php
// Simple test to see if pages load without fatal errors
set_time_limit(10);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing system_status.php (simplified) ===\n";

try {
    // Test if we can require the key components without hanging
    require_once __DIR__ . '/web_ui/auth_check.php';
    echo "SUCCESS: auth_check.php loaded\n";
    
    // Test if NavigationManager works in style context
    ob_start();
    require_once __DIR__ . '/web_ui/NavigationManager.php';
    $navManager = new NavigationManager();
    $css = $navManager->getNavigationCSS();
    ob_end_clean();
    
    echo "SUCCESS: NavigationManager works (CSS length: " . strlen($css) . ")\n";
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Test completed ===\n";
