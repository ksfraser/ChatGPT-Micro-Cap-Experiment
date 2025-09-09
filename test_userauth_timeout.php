<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing UserAuthDAO instantiation with timeout ===\n";

// Set a global timeout
set_time_limit(10);

try {
    require_once __DIR__ . '/web_ui/UserAuthDAO.php';
    echo "SUCCESS: UserAuthDAO.php loaded\n";
    
    $start = microtime(true);
    $userAuth = new UserAuthDAO();
    $elapsed = microtime(true) - $start;
    
    echo "SUCCESS: UserAuthDAO created in " . round($elapsed, 2) . " seconds\n";
    echo "Is logged in: " . ($userAuth->isLoggedIn() ? 'Yes' : 'No') . "\n";
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
