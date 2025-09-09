<?php
// Test the updated pages that now use UserAuthDAO directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing updated system_status.php ===\n";

try {
    ob_start();
    include __DIR__ . '/web_ui/system_status.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "Output length: " . strlen($output) . " characters\n";
    
    if (strlen($output) > 0) {
        echo "SUCCESS: Got output from system_status.php\n";
        echo "First 200 characters:\n";
        echo substr($output, 0, 200) . "\n";
    } else {
        echo "ISSUE: Empty output\n";
    }
    
} catch (Exception $e) {
    echo "EXPECTED EXCEPTION: " . get_class($e) . ": " . $e->getMessage() . "\n";
}

echo "\n=== Testing updated database.php ===\n";

try {
    ob_start();
    include __DIR__ . '/web_ui/database.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "Output length: " . strlen($output) . " characters\n";
    
    if (strlen($output) > 0) {
        echo "SUCCESS: Got output from database.php\n";
        echo "First 200 characters:\n";
        echo substr($output, 0, 200) . "\n";
    } else {
        echo "ISSUE: Empty output\n";
    }
    
} catch (Exception $e) {
    echo "EXPECTED EXCEPTION: " . get_class($e) . ": " . $e->getMessage() . "\n";
}

echo "\n=== Test completed ===\n";
