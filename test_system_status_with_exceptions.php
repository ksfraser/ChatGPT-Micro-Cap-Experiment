<?php
// Test system_status.php with custom exception handling
echo "Testing system_status.php with custom exception handling\n";

// Set up mock environment
$_SERVER['REQUEST_URI'] = '/system_status.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

try {
    echo "About to include system_status.php\n";
    
    // Capture output to see what happens
    ob_start();
    include 'web_ui/system_status.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "system_status.php completed\n";
    echo "Output length: " . strlen($output) . " bytes\n";
    
    if (strlen($output) > 500) {
        echo "Output preview: " . substr($output, 0, 500) . "...\n";
    } else {
        echo "Full output:\n" . $output . "\n";
    }
    
} catch (Exception $e) {
    echo "Exception caught at top level: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "Test completed\n";
?>
