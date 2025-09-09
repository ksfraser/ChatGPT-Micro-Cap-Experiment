<?php
// No output before session
require_once 'web_ui/SessionManager.php';
$sessionManager = SessionManager::getInstance();

// Now we can output
echo "Clean session test results:\n";
echo "Session active: " . ($sessionManager->isSessionActive() ? 'Yes' : 'No') . "\n";
echo "Session error: " . ($sessionManager->getInitializationError() ?: 'None') . "\n";
echo "Session save path: " . session_save_path() . "\n";
echo "Session save path exists: " . (is_dir(session_save_path()) ? 'Yes' : 'No') . "\n";

// Test session functionality
$testResult = $sessionManager->set('test_key', 'test_value');
echo "Can set session data: " . ($testResult ? 'Yes' : 'No') . "\n";

$retrievedValue = $sessionManager->get('test_key');
echo "Retrieved session data: " . ($retrievedValue === 'test_value' ? 'Success' : 'Failed') . "\n";

echo "Session functionality test completed successfully\n";
?>
