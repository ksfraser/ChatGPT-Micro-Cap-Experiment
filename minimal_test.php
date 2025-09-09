<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting minimal test...\n";

echo "1. Including SessionManager...\n";
require_once 'web_ui/SessionManager.php';
echo "SessionManager included successfully.\n";

echo "2. Creating SessionManager instance...\n";
$sessionManager = SessionManager::getInstance();
echo "SessionManager instance created.\n";

echo "3. Including UserAuthDAO...\n";
require_once 'web_ui/UserAuthDAO.php';
echo "UserAuthDAO included successfully.\n";

echo "4. Creating UserAuthDAO instance...\n";
$userAuth = new UserAuthDAO();
echo "UserAuthDAO instance created.\n";

echo "5. Checking login status...\n";
$isLoggedIn = $userAuth->isLoggedIn();
echo "Login status checked: " . ($isLoggedIn ? 'true' : 'false') . "\n";

echo "Test completed successfully.\n";
?>
