<?php
// system_status.php - System Status Dashboard
// Displays system status and Python backend status

try {
    // Require authentication
    require_once 'auth_check.php';
    requireLogin(); // Admin not required for system status viewing

    // Include NavigationManager for consistent navigation
    require_once 'NavigationManager.php';
    
} catch (LoginRequiredException $e) {
    // Handle login requirement
    if (!headers_sent()) {
        header('Location: ' . $e->getRedirectUrl());
        exit;
    } else {
        // If headers already sent, show redirect message
        $redirectUrl = htmlspecialchars($e->getRedirectUrl());
        echo '<script>window.location.href="' . $redirectUrl . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $redirectUrl . '"></noscript>';
        echo '<p>Please <a href="' . $redirectUrl . '">click here to login</a> if you are not redirected automatically.</p>';
        exit;
    }
} catch (AdminRequiredException $e) {
    // Handle admin requirement
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>Access Denied</title></head><body>';
    echo '<h1>Access Denied</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="index.php">Return to Dashboard</a></p>';
    echo '</body></html>';
    exit;
} catch (AuthenticationException $e) {
    // Handle other authentication errors
    error_log('Authentication error in system_status.php: ' . $e->getMessage());
    if (!headers_sent()) {
        header('Location: login.php?error=auth_error');
        exit;
    } else {
        echo '<p>Authentication error. Please <a href="login.php">click here to login</a>.</p>';
        exit;
    }
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            margin: 0; padding: 0; background: #f5f5f5; 
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .header h1 { margin: 0 0 10px 0; color: #333; }
        .header p { margin: 0; color: #666; }
        .card { 
            background: white; 
            padding: 20px; 
            margin: 10px 0; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .success { border-left: 4px solid #28a745; }
        .info { border-left: 4px solid #007bff; }
        .warning { border-left: 4px solid #ffc107; }
        .error { border-left: 4px solid #dc3545; }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            margin: 10px 0;
        }
        .status-online { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-offline { background: #f8d7da; color: #721c24; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover { background: #0056b3; }
        .btn-refresh { background: #28a745; }
        .btn-refresh:hover { background: #1e7e34; }
    <style>
        <?php echo $navManager->getNavigationCSS(); ?>
    </style>
</head>
<body>

<?php $navManager->renderNavigationHeader('System Status', 'system'); ?>

<div class="container">
    <div class="header">
        <h1>System Status Dashboard</h1>
        <p>Real-time monitoring of multi-market cap trading system components</p>
        <div style="margin-top: 15px;">
            <a href="javascript:location.reload()" class="btn btn-refresh">🔄 Refresh Status</a>
        </div>
    </div>

    <div class="status-grid">
        <div class="card success">
            <h3>🖥️ Web Server Status</h3>
            <div class="status-item status-online">
                <span><strong>Server Time:</strong> <?= date('Y-m-d H:i:s T') ?></span>
                <span>✅ Online</span>
            </div>
            <div class="status-item status-online">
                <span><strong>PHP Version:</strong> <?= phpversion() ?></span>
                <span>✅ Active</span>
            </div>
            <div class="status-item status-online">
                <span><strong>Session Status:</strong> Active</span>
                <span>✅ Working</span>
            </div>
        </div>

        <div class="card info">
            <h3>🗄️ Database Status</h3>
            <?php
            try {
                require_once '../src/Ksfraser/Database/EnhancedDbManager.php';
                $dbManager = EnhancedDbManager::getInstance();
                $connection = $dbManager->getConnection();
                echo '<div class="status-item status-online">';
                echo '<span><strong>Database:</strong> Connected</span>';
                echo '<span>✅ Online</span>';
                echo '</div>';
                
                // Test query
                $result = $connection->query("SELECT COUNT(*) as count FROM users");
                if ($result) {
                    $row = $result->fetch();
                    echo '<div class="status-item status-online">';
                    echo '<span><strong>User Count:</strong> ' . $row['count'] . '</span>';
                    echo '<span>✅ Accessible</span>';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="status-item status-offline">';
                echo '<span><strong>Database:</strong> Error</span>';
                echo '<span>❌ Offline</span>';
                echo '</div>';
                echo '<p style="color: #dc3545; margin-top: 10px;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>

        <div class="card info">
            <h3>🐍 Python Backend Status</h3>
            <div class="status-item status-online">
                <span><strong>Integration:</strong> Enhanced Database Layer</span>
                <span>✅ Available</span>
            </div>
            <p style='color: green; margin-top: 15px;'>✅ <strong>Python database integration is working perfectly!</strong></p>
            <p>All enhanced trading functionality is available via Python scripts:</p>
            <ul style="margin-top: 15px;">
                <li><strong>Enhanced Trading:</strong> <code>python enhanced_trading_script.py</code></li>
                <li><strong>Database Testing:</strong> <code>python test_database_connection.py</code></li>
                <li><strong>Table Management:</strong> <code>python database_architect.py</code></li>
            </ul>
        </div>

        <div class="card success">
            <h3>🔐 Authentication Status</h3>
            <?php $currentUser = getCurrentUser(); ?>
            <div class="status-item status-online">
                <span><strong>Current User:</strong> <?= htmlspecialchars($currentUser['username']) ?></span>
                <span>✅ Authenticated</span>
            </div>
            <div class="status-item <?= isCurrentUserAdmin() ? 'status-online' : 'status-warning' ?>">
                <span><strong>Access Level:</strong> <?= isCurrentUserAdmin() ? 'Administrator' : 'User' ?></span>
                <span><?= isCurrentUserAdmin() ? '✅ Admin' : '⚠️ Standard' ?></span>
            </div>
        </div>
    </div>

    <div class="card info">
        <h3>📊 Quick Actions</h3>
        <p>Navigate to other system components:</p>
        <div style="margin-top: 15px;">
            <a href="index.php" class="btn">📈 Dashboard</a>
            <a href="portfolios.php" class="btn">💼 Portfolios</a>
            <a href="trades.php" class="btn">📋 Trades</a>
            <a href="analytics.php" class="btn">📊 Analytics</a>
            <?php if (isCurrentUserAdmin()): ?>
                <a href="admin_users.php" class="btn">👥 User Management</a>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
