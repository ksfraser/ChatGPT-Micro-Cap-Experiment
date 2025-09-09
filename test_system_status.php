<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "Testing system_status.php with full error reporting\n";

// Capture any output or errors
ob_start();

try {
    // Set up basic server environment for testing
    $_SERVER['REQUEST_URI'] = '/system_status.php';
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    echo "About to include system_status.php\n";
    
    // Include the page
    include 'web_ui/system_status.php';
    
    echo "system_status.php included successfully\n";
    
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Fatal error caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} finally {
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "Captured output length: " . strlen($output) . " bytes\n";
    if (strlen($output) > 0) {
        echo "First 500 chars of output:\n";
        echo substr($output, 0, 500) . "\n";
    }
}

echo "Test completed\n";
?>
