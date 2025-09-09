<?php
// Test system_status.php with a mock logged-in user
echo "Testing system_status.php with mock authentication\n";

// Set up mock environment
$_SERVER['REQUEST_URI'] = '/system_status.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Mock session data for a logged-in admin user
require_once 'web_ui/SessionManager.php';
$sessionManager = SessionManager::getInstance();

// Set mock user session data
$mockUserData = [
    'id' => 1,
    'username' => 'admin',
    'email' => 'admin@test.com',
    'is_admin' => true,
    'role' => 'admin'
];

$sessionManager->set('user_auth', $mockUserData);

echo "Mock user session set - testing with logged-in admin\n";

try {
    ob_start();
    
    include 'web_ui/system_status.php';
    
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "system_status.php completed successfully\n";
    echo "Output length: " . strlen($output) . " bytes\n";
    
    if (strlen($output) > 1000) {
        echo "Output preview (first 500 chars):\n";
        echo substr($output, 0, 500) . "...\n";
    } else {
        echo "Full output:\n";
        echo $output . "\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "Test completed\n";
?>
