<?php
/**
 * Simple UserAuthDAO debug
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing UserAuthDAO...\n";

try {
    require_once __DIR__ . '/UserAuthDAO.php';
    echo "UserAuthDAO file loaded\n";
    
    $userAuth = new UserAuthDAO();
    echo "UserAuthDAO instance created successfully\n";
    
    // Test basic method
    $isLoggedIn = $userAuth->isLoggedIn();
    echo "isLoggedIn() result: " . ($isLoggedIn ? 'true' : 'false') . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
