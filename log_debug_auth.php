<?php
// Create a log file to debug without output
file_put_contents('debug.log', "Starting auth test\n", FILE_APPEND);

require_once 'web_ui/SessionManager.php';
file_put_contents('debug.log', "SessionManager loaded\n", FILE_APPEND);

$sessionManager = SessionManager::getInstance();
file_put_contents('debug.log', "SessionManager instance created\n", FILE_APPEND);

require_once 'web_ui/UserAuthDAO.php';
file_put_contents('debug.log', "UserAuthDAO class loaded\n", FILE_APPEND);

$userAuth = new UserAuthDAO();
file_put_contents('debug.log', "UserAuthDAO instance created\n", FILE_APPEND);

$isLoggedIn = $userAuth->isLoggedIn();
file_put_contents('debug.log', "isLoggedIn called, result: " . ($isLoggedIn ? 'true' : 'false') . "\n", FILE_APPEND);

echo "Auth test completed successfully\n";
echo "Session active: " . ($sessionManager->isSessionActive() ? 'Yes' : 'No') . "\n";
echo "Is logged in: " . ($isLoggedIn ? 'Yes' : 'No') . "\n";
?>
