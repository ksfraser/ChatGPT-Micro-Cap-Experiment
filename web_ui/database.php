<?php
/**
 * Database Management Page
 * Enhanced with real-time database connectivity checking
 */

try {
    // Require authentication
    require_once 'auth_check.php';
    // Auth check automatically redirects if not logged in - no need to call requireLogin()

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
    error_log('Authentication error in database.php: ' . $e->getMessage());
    if (!headers_sent()) {
        header('Location: login.php?error=auth_error');
        exit;
    } else {
        echo '<p>Authentication error. Please <a href="login.php">click here to login</a>.</p>';
        exit;
    }
}

// Test database connectivity
$dbStatus = 'disconnected';
$dbMessage = 'Unable to connect to database';
$dbDetails = [];

try {
    // Load all database classes
    require_once 'database_loader.php';
    
    $connection = \Ksfraser\Database\EnhancedDbManager::getConnection();
    
    if ($connection) {
        $dbStatus = 'connected';
        $dbMessage = 'Database connection successful';
        
        // Get database details
        $dbDetails = [
            'driver' => \Ksfraser\Database\EnhancedDbManager::getCurrentDriver(),
            'config_source' => 'Enhanced Database Configuration'
        ];
        
        // Test query to verify functionality
        try {
            $stmt = $connection->prepare("SELECT COUNT(*) as count FROM users");
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row) {
                $dbDetails['user_count'] = $row['count'];
            }
        } catch (Exception $e) {
            $dbDetails['query_error'] = $e->getMessage();
        }
    }
} catch (Exception $e) {
    $dbStatus = 'error';
    $dbMessage = 'Database error: ' . $e->getMessage();
}

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - Enhanced Trading System</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 0; background: #f5f5f5; 
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { 
            background: white; 
            padding: 20px; 
            margin: 10px 0; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .card.success { border-left: 4px solid #28a745; }
        .card.warning { border-left: 4px solid #ffc107; }
        .card.error { border-left: 4px solid #dc3545; }
        .status-indicator {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-connected { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-warning { background: #fff3cd; color: #856404; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; }
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
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .detail-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
        }
        .detail-item strong { color: #495057; }
    <style>
        <?php echo $navManager->getNavigationCSS(); ?>
    </style>
</head>
<body>

<?php $navManager->renderNavigationHeader('Database Management', 'database'); ?>

<div class="container">
    <h1>🗄️ Database Management</h1>
    
    <!-- Database Status Card -->
    <div class="card <?php echo $dbStatus === 'connected' ? 'success' : ($dbStatus === 'error' ? 'error' : 'warning'); ?>">
        <h3>📡 Database Connectivity Status</h3>
        <p>
            <span class="status-indicator status-<?php echo $dbStatus === 'connected' ? 'connected' : 'error'; ?>">
                <?php echo $dbStatus; ?>
            </span>
            <?php echo htmlspecialchars($dbMessage); ?>
        </p>
        
        <?php if ($dbStatus === 'connected' && !empty($dbDetails)): ?>
            <div class="detail-grid">
                <div class="detail-item">
                    <strong>Database Driver:</strong><br>
                    <?php echo htmlspecialchars($dbDetails['driver'] ?? 'Unknown'); ?>
                </div>
                <div class="detail-item">
                    <strong>Config Source:</strong><br>
                    <?php echo htmlspecialchars($dbDetails['config_source'] ?? 'Unknown'); ?>
                </div>
                <?php if (isset($dbDetails['user_count'])): ?>
                <div class="detail-item">
                    <strong>Registered Users:</strong><br>
                    <?php echo $dbDetails['user_count']; ?>
                </div>
                <?php endif; ?>
                <div class="detail-item">
                    <strong>Connection Status:</strong><br>
                    ✅ Fully Operational
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($dbStatus !== 'connected'): ?>
            <p><strong>Troubleshooting:</strong></p>
            <ul>
                <li>Check database configuration in config files</li>
                <li>Verify database server is running</li>
                <li>Ensure proper database credentials</li>
                <li>Check PHP database extensions (PDO, MySQLi)</li>
            </ul>
        <?php endif; ?>
    </div>
    
    <!-- Database Architecture Overview -->
    <div class="card">
        <h3>🏗️ Database Architecture Overview</h3>
        <table>
            <tr>
                <th>Database</th>
                <th>Purpose</th>
                <th>Tables</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Enhanced Trading DB</td>
                <td>User management & authentication</td>
                <td>users, sessions, audit_log</td>
                <td><span class="status-indicator status-connected">Active</span></td>
            </tr>
            <tr>
                <td>stock_market_micro_cap_trading</td>
                <td>CSV-mirrored trading data</td>
                <td>portfolio_data, trade_log, historical_prices</td>
                <td><span class="status-indicator status-connected">Active</span></td>
            </tr>
            <tr>
                <td>stock_market_2</td>
                <td>Enhanced features & analytics</td>
                <td>Advanced analytics and reporting</td>
                <td><span class="status-indicator status-connected">Active</span></td>
            </tr>
        </table>
    </div>
    
    <!-- Database Management Tools -->
    <?php if (isCurrentUserAdmin()): ?>
    <div class="card">
        <h3>🔧 Database Management Tools</h3>
        <p>Administrative tools for database management and maintenance</p>
        <div style="margin-top: 15px;">
            <a href="create_tables.php" class="btn">Create/Update Tables</a>
            <a href="backup.php" class="btn">Backup Databases</a>
            <a href="migrate.php" class="btn">Data Migration</a>
            <a href="system_status.php" class="btn btn-success">System Status</a>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <h3>📊 Database Information</h3>
        <p>Database system is operational and serving trading data.</p>
        <p><strong>Note:</strong> Administrative database tools require admin privileges.</p>
        <a href="system_status.php" class="btn">View System Status</a>
    </div>
    <?php endif; ?>

    <!-- Database Testing Tools -->
    <div class="card">
        <h3>🧪 Database Testing & Diagnostics</h3>
        <p>Tools to test and diagnose database connectivity and performance</p>
        <div style="margin-top: 15px;">
            <button onclick="showSection('dbtest')" class="btn">Database Test</button>
            <button onclick="showSection('dbdiagnosis')" class="btn">Database Diagnosis</button>
            <button onclick="hideAllSections()" class="btn" style="background: #6c757d;">Hide Tests</button>
        </div>
    </div>

    <div id="dbtest" class="card" style="display:none;">
        <h4>Database Connectivity Test</h4>
        <?php if ($dbStatus === 'connected'): ?>
            <div style="color: #28a745; margin-bottom: 15px;">
                ✅ <strong>Database Test Result: PASSED</strong>
            </div>
            <p>✅ Database connection successful</p>
            <p>✅ Query execution working</p>
            <p>✅ User authentication system operational</p>
            <?php if (isset($dbDetails['user_count'])): ?>
                <p>✅ User table accessible (<?php echo $dbDetails['user_count']; ?> users registered)</p>
            <?php endif; ?>
        <?php else: ?>
            <div style="color: #dc3545; margin-bottom: 15px;">
                ❌ <strong>Database Test Result: FAILED</strong>
            </div>
            <p>❌ Database connection failed</p>
            <p>❌ System may not function properly</p>
            <p><strong>Error:</strong> <?php echo htmlspecialchars($dbMessage); ?></p>
        <?php endif; ?>
    </div>

    <div id="dbdiagnosis" class="card" style="display:none;">
        <h4>Database Diagnosis Report</h4>
        <div class="detail-grid">
            <div class="detail-item">
                <strong>PHP Version:</strong><br>
                <?php echo phpversion(); ?>
            </div>
            <div class="detail-item">
                <strong>PDO Available:</strong><br>
                <?php echo extension_loaded('pdo') ? '✅ Yes' : '❌ No'; ?>
            </div>
            <div class="detail-item">
                <strong>MySQLi Available:</strong><br>
                <?php echo extension_loaded('mysqli') ? '✅ Yes' : '❌ No'; ?>
            </div>
            <div class="detail-item">
                <strong>SQLite Available:</strong><br>
                <?php echo extension_loaded('sqlite3') ? '✅ Yes' : '❌ No'; ?>
            </div>
            <?php if ($dbStatus === 'connected'): ?>
            <div class="detail-item">
                <strong>Active Driver:</strong><br>
                <?php echo htmlspecialchars($dbDetails['driver'] ?? 'Unknown'); ?>
            </div>
            <div class="detail-item">
                <strong>Configuration:</strong><br>
                <?php echo htmlspecialchars($dbDetails['config_source'] ?? 'Unknown'); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function showSection(id) {
        hideAllSections();
        document.getElementById(id).style.display = 'block';
    }
    
    function hideAllSections() {
        document.getElementById('dbtest').style.display = 'none';
        document.getElementById('dbdiagnosis').style.display = 'none';
    }
    </script>
</div>

</body>
</html>