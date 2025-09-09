<?php
// CLI-friendly test of authentication
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing authentication flow ===\n";

// Import the namespaced exception classes  
use App\Auth\AuthenticationException;
use App\Auth\LoginRequiredException;
use App\Auth\AdminRequiredException;
use App\Auth\SessionException;

try {
    echo "Including auth_check.php...\n";
    require_once __DIR__ . '/web_ui/auth_check.php';
    echo "SUCCESS: Authentication passed!\n";
    
    if (isset($currentUser)) {
        echo "Current user: " . print_r($currentUser, true) . "\n";
    }
    
    if (isset($isAdmin)) {
        echo "Is admin: " . ($isAdmin ? 'Yes' : 'No') . "\n";
    }
    
} catch (LoginRequiredException $e) {
    echo "EXPECTED: Login required - " . $e->getMessage() . "\n";
    echo "Redirect URL: " . $e->getRedirectUrl() . "\n";
    
} catch (Exception $e) {
    echo "EXCEPTION: " . get_class($e) . ": " . $e->getMessage() . "\n";
}

echo "=== Test completed ===\n";
