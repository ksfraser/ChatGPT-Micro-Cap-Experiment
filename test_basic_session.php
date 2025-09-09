<?php
echo "Testing session and basic functionality without auth\n";

// Test SessionManager directly
require_once 'web_ui/SessionManager.php';
$sessionManager = SessionManager::getInstance();

echo "Session active: " . ($sessionManager->isSessionActive() ? 'Yes' : 'No') . "\n";
echo "Session error: " . ($sessionManager->getInitializationError() ?: 'None') . "\n";
echo "Session save path: " . session_save_path() . "\n";

// Test if we can create session data
$testResult = $sessionManager->set('test_key', 'test_value');
echo "Can set session data: " . ($testResult ? 'Yes' : 'No') . "\n";

$retrievedValue = $sessionManager->get('test_key');
echo "Retrieved session data: " . ($retrievedValue === 'test_value' ? 'Success' : 'Failed') . "\n";

echo "Basic session functionality test completed successfully\n";
?>
