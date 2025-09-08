<?php
/**
 * Create Admin User Script
 * 
 * This script allows you to create an initial admin user for the system.
 * Run this script once to create your first admin account.
 */

require_once __DIR__ . '/web_ui/UserAuthDAO.php';

echo "=== Enhanced Trading System - Admin User Creator ===\n\n";

try {
    $auth = new UserAuthDAO();
    
    // Check if any admin users already exist
    $existingAdmins = $auth->getAllUsers();
    $adminCount = count(array_filter($existingAdmins, function($user) {
        return $user['is_admin'] == 1;
    }));
    
    if ($adminCount > 0) {
        echo "⚠️  Warning: {$adminCount} admin user(s) already exist in the system.\n";
        echo "Existing admin users:\n";
        foreach ($existingAdmins as $user) {
            if ($user['is_admin']) {
                echo "  - {$user['username']} ({$user['email']})\n";
            }
        }
        echo "\nDo you want to create another admin user? (y/N): ";
        $continue = trim(fgets(STDIN));
        if (strtolower($continue) !== 'y' && strtolower($continue) !== 'yes') {
            echo "Admin user creation cancelled.\n";
            exit(0);
        }
        echo "\n";
    }
    
    // Get admin user details
    echo "Enter details for the new admin user:\n\n";
    
    echo "Username: ";
    $username = trim(fgets(STDIN));
    
    if (empty($username)) {
        throw new Exception("Username cannot be empty");
    }
    
    echo "Email: ";
    $email = trim(fgets(STDIN));
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please enter a valid email address");
    }
    
    echo "Password: ";
    $password = trim(fgets(STDIN));
    
    if (empty($password)) {
        throw new Exception("Password cannot be empty");
    }
    
    if (strlen($password) < 6) {
        throw new Exception("Password must be at least 6 characters long");
    }
    
    echo "Confirm password: ";
    $confirmPassword = trim(fgets(STDIN));
    
    if ($password !== $confirmPassword) {
        throw new Exception("Passwords do not match");
    }
    
    echo "\nCreating admin user...\n";
    
    // Create the admin user
    $userId = $auth->registerUser($username, $email, $password, true); // true = admin
    
    if ($userId) {
        echo "\n✅ SUCCESS!\n";
        echo "Admin user created successfully!\n";
        echo "User ID: {$userId}\n";
        echo "Username: {$username}\n";
        echo "Email: {$email}\n";
        echo "Admin privileges: YES\n\n";
        echo "You can now log in to the web interface at: http://localhost:8000/login.php\n";
    } else {
        throw new Exception("Failed to create admin user");
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
