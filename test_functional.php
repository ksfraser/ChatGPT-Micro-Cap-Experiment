<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing system_status.php ===\n";

try {
    // Start output buffering to capture any output
    ob_start();
    
    // Include the system status page
    include __DIR__ . '/web_ui/system_status.php';
    
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "SUCCESS: system_status.php loaded without fatal errors\n";
    echo "Output length: " . strlen($output) . " characters\n";
    
    if (strlen($output) > 0) {
        echo "First 200 characters of output:\n";
        echo substr($output, 0, 200) . "...\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    ob_end_clean();
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Testing database.php ===\n";

try {
    // Start output buffering to capture any output
    ob_start();
    
    // Include the database page
    include __DIR__ . '/web_ui/database.php';
    
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "SUCCESS: database.php loaded without fatal errors\n";
    echo "Output length: " . strlen($output) . " characters\n";
    
    if (strlen($output) > 0) {
        echo "First 200 characters of output:\n";
        echo substr($output, 0, 200) . "...\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    ob_end_clean();
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
