<?php
// system_status.php - MINIMAL TEST VERSION
// Displays system status without NavigationManager

// Import the namespaced exception classes
use App\Auth\AuthenticationException;
use App\Auth\LoginRequiredException;
use App\Auth\AdminRequiredException;
use App\Auth\SessionException;

try {
    // Require authentication
    require_once __DIR__ . '/web_ui/auth_check.php';
    
} catch (LoginRequiredException $e) {
    // Handle login requirement
    if (!headers_sent()) {
        header('Location: ' . $e->getRedirectUrl());
        exit;
    } else {
        echo '<p>Please <a href="login.php">click here to login</a>.</p>';
        exit;
    }
} catch (Exception $e) {
    // Handle other errors
    error_log('Error in system_status.php: ' . $e->getMessage());
    echo '<p>Authentication error. Please <a href="login.php">click here to login</a>.</p>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Status - Enhanced Trading System</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5; 
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>System Status Dashboard - MINIMAL TEST</h1>
        <p>Testing without NavigationManager</p>
    </div>
    
    <div>
        <h3>Authentication Status</h3>
        <p>✅ Successfully authenticated</p>
        <?php if (isset($currentUser)): ?>
            <p>Current user: <?php echo htmlspecialchars($currentUser['username'] ?? 'Unknown'); ?></p>
        <?php endif; ?>
    </div>
    
    <div>
        <h3>System Components</h3>
        <ul>
            <li>PHP Version: <?php echo PHP_VERSION; ?></li>
            <li>Session Active: <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No'; ?></li>
            <li>Current Time: <?php echo date('Y-m-d H:i:s'); ?></li>
        </ul>
    </div>
    
</div>

</body>
</html>
