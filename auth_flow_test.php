<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing auth flow step by step:\n";

echo "1. Including SessionManager...\n";
require_once 'web_ui/SessionManager.php';
$sessionManager = SessionManager::getInstance();
echo "Session active: " . ($sessionManager->isSessionActive() ? 'Yes' : 'No') . "\n";

echo "2. Including UserAuthDAO...\n";
require_once 'web_ui/UserAuthDAO.php';
$userAuth = new UserAuthDAO();
echo "UserAuthDAO created successfully.\n";

echo "3. Checking login status...\n";
$isLoggedIn = $userAuth->isLoggedIn();
echo "Is logged in: " . ($isLoggedIn ? 'Yes' : 'No') . "\n";

if (!$isLoggedIn) {
    echo "4. User not logged in - this would redirect in auth_check.php\n";
    echo "Current page would be: " . ($_SERVER['REQUEST_URI'] ?? 'CLI') . "\n";
    echo "Login URL would be: login.php\n";
} else {
    echo "4. User is logged in - auth_check would proceed\n";
    $currentUser = $userAuth->getCurrentUser();
    echo "Current user: " . print_r($currentUser, true) . "\n";
}

echo "Auth flow test completed.\n";
?>
