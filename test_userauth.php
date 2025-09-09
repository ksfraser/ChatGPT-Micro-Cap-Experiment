<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing UserAuthDAO loading ===\n";

try {
    require_once __DIR__ . '/web_ui/UserAuthDAO.php';
    echo "SUCCESS: UserAuthDAO.php loaded\n";
    
    $userAuth = new UserAuthDAO();
    echo "SUCCESS: UserAuthDAO instance created\n";
    
    echo "Is logged in: " . ($userAuth->isLoggedIn() ? 'Yes' : 'No') . "\n";
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
