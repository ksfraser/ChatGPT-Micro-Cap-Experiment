<?php
// Test database.php with minimal includes to isolate the issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing database.php step by step ===\n";

try {
    echo "Step 1: Including UserAuthDAO...\n";
    require_once __DIR__ . '/web_ui/UserAuthDAO.php';
    echo "SUCCESS: UserAuthDAO included\n";
    
    echo "Step 2: Creating UserAuthDAO instance...\n";
    $userAuth = new UserAuthDAO();
    echo "SUCCESS: UserAuthDAO created\n";
    
    echo "Step 3: Testing requireLogin...\n";
    $userAuth->requireLogin();
    echo "SUCCESS: Login check passed (unexpected - user should not be logged in)\n";
    
} catch (Exception $e) {
    echo "EXPECTED EXCEPTION: " . get_class($e) . ": " . $e->getMessage() . "\n";
    
    if (get_class($e) === 'App\\Auth\\LoginRequiredException') {
        echo "This is the expected behavior for non-logged-in users\n";
    }
}

echo "=== Test completed ===\n";
