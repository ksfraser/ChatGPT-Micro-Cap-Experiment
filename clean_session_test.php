<?php
// Clean test - no output before SessionManager
require_once 'web_ui/SessionManager.php';
$sessionManager = SessionManager::getInstance();

if ($sessionManager->isSessionActive()) {
    echo "Session initialized successfully.\n";
} else {
    echo "Session initialization failed: " . $sessionManager->getInitializationError() . "\n";
}
?>
