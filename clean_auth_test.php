<?php
// Test auth flow without premature output
require_once 'web_ui/SessionManager.php';
require_once 'web_ui/UserAuthDAO.php';

$sessionManager = SessionManager::getInstance();
$userAuth = new UserAuthDAO();

$isLoggedIn = $userAuth->isLoggedIn();

echo "Auth flow test results:\n";
echo "Session active: " . ($sessionManager->isSessionActive() ? 'Yes' : 'No') . "\n";
echo "Is logged in: " . ($isLoggedIn ? 'Yes' : 'No') . "\n";

if (!$isLoggedIn) {
    echo "User not logged in - would redirect to login\n";
} else {
    echo "User is logged in\n";
    $currentUser = $userAuth->getCurrentUser();
    echo "Current user: " . print_r($currentUser, true) . "\n";
}
?>
