<?php
/**
 * Test user registration functionality
 */

// Include the UserAuthDAO
require_once __DIR__ . '/web_ui/UserAuthDAO.php';

echo "Testing user registration...\n";

try {
    $auth = new UserAuthDAO();
    
    // Test database connection first
    echo "Testing database connection...\n";
    
    // Try to get all users to test if DB is working
    $users = $auth->getAllUsers();
    echo "✓ Database connection successful. Found " . count($users) . " existing users.\n";
    
    // Test user registration
    $testUsername = "testuser_" . time();
    $testEmail = "test" . time() . "@example.com";
    $testPassword = "testpass123";
    
    echo "Attempting to register user: $testUsername\n";
    
    $userId = $auth->registerUser($testUsername, $testEmail, $testPassword);
    
    if ($userId && is_numeric($userId)) {
        echo "✓ User registration successful! User ID: $userId\n";
        
        // Test if user was actually created
        $users = $auth->getAllUsers();
        $found = false;
        foreach ($users as $user) {
            if ($user['username'] === $testUsername) {
                $found = true;
                echo "✓ User found in database: ID={$user['id']}, Username={$user['username']}, Email={$user['email']}\n";
                break;
            }
        }
        
        if (!$found) {
            echo "✗ User registration reported success but user not found in database\n";
        }
        
    } else {
        echo "✗ User registration failed. Result: " . var_export($userId, true) . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error during registration test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest complete.\n";
?>
