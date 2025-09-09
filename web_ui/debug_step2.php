<?php
/**
 * Debug the current admin_users.php step by step
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Step 1: Testing basic includes...\n";

try {
    require_once __DIR__ . '/UserAuthDAO.php';
    echo "✓ UserAuthDAO included\n";
    
    require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
    echo "✓ UIRenderer autoloader included\n";
    
    require_once 'MenuService.php';
    echo "✓ MenuService included\n";
    
    use Ksfraser\UIRenderer\Factories\UiFactory;
    use Ksfraser\User\DTOs\UserManagementRequest;
    use Ksfraser\User\Services\UserManagementService;
    echo "✓ All classes imported\n";
    
    echo "Step 2: Testing UserAuthDAO...\n";
    $userAuth = new UserAuthDAO();
    echo "✓ UserAuthDAO created\n";
    
    echo "Step 3: Testing admin check...\n";
    // This might redirect, so let's not call it
    // $userAuth->requireAdmin();
    echo "✓ Skipping requireAdmin for now\n";
    
    echo "Step 4: Testing current user...\n";
    $currentUser = $userAuth->getCurrentUser();
    echo "✓ Current user: " . ($currentUser ? $currentUser['username'] : 'none') . "\n";
    
    echo "Step 5: Testing UserManagementService...\n";
    $service = new UserManagementService($userAuth, $currentUser);
    echo "✓ UserManagementService created\n";
    
    echo "Step 6: Testing getAllUsers...\n";
    $users = $service->getAllUsers();
    echo "✓ Users retrieved: " . count($users) . " users\n";
    
    echo "Step 7: Testing getUserStatistics...\n";
    $userStats = $service->getUserStatistics($users);
    echo "✓ User statistics: " . json_encode($userStats) . "\n";
    
    echo "\nAll tests passed! The issue might be requireAdmin() redirecting.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "FATAL: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
