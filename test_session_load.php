<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Loading SessionManager...\n";

try {
    require_once __DIR__ . '/web_ui/SessionManager.php';
    echo "SessionManager loaded successfully\n";
    
    $sm = SessionManager::getInstance();
    echo "SessionManager instance created successfully\n";
    
    echo "Session active: " . ($sm->isSessionActive() ? 'Yes' : 'No') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
