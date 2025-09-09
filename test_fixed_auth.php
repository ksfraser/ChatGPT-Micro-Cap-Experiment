<?php
// Test the fixed auth_check.php
echo "Testing fixed auth_check.php\n";

// Set up mock environment
$_SERVER['REQUEST_URI'] = '/test';
$_SERVER['HTTP_HOST'] = 'localhost';

echo "About to include auth_check.php\n";

// Capture any redirect output
ob_start();

include 'web_ui/auth_check.php';

$output = ob_get_contents();
ob_end_clean();

echo "auth_check.php completed\n";
echo "Captured output length: " . strlen($output) . " bytes\n";
if (strlen($output) > 0) {
    echo "Output preview: " . substr($output, 0, 200) . "...\n";
}
?>
