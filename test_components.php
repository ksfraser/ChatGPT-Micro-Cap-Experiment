<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing NavigationManager loading ===\n";

try {
    require_once __DIR__ . '/web_ui/NavigationManager.php';
    echo "SUCCESS: NavigationManager.php loaded\n";
    
    $navManager = new NavigationManager();
    echo "SUCCESS: NavigationManager instance created\n";
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\n=== Testing SessionManager loading ===\n";

try {
    require_once __DIR__ . '/web_ui/SessionManager.php';
    echo "SUCCESS: SessionManager.php loaded\n";
    
    $sessionManager = SessionManager::getInstance();
    echo "SUCCESS: SessionManager instance created\n";
    
    echo "Session active: " . ($sessionManager->isSessionActive() ? 'Yes' : 'No') . "\n";
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
