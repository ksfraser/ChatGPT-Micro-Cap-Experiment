<?php
// Test system_status.php with full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "Starting system_status.php test...\n";

try {
    ob_start();
    include __DIR__ . '/web_ui/system_status.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "Output length: " . strlen($output) . " characters\n";
    
    if (strlen($output) == 0) {
        echo "BLANK OUTPUT - likely fatal error occurred\n";
    } else {
        echo "First 500 characters:\n";
        echo substr($output, 0, 500) . "\n";
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
