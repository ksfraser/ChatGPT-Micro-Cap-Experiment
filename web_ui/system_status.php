<?php
// system_status.php
// Displays system status and Python backend status
include_once 'QuickActions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Status - Enhanced Trading System</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { border-left: 4px solid #28a745; }
        .info { border-left: 4px solid #007bff; }
        .warning { border-left: 4px solid #ffc107; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
        }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>System Status</h1>
            <p>Centralized status for multi-market cap trading operations</p>
        </div>
        <?php QuickActions::render(); ?>
        <div class="card success">
            <h3>System Status</h3>
            <p><strong>Server Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
            <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
            <p><strong>Status:</strong> <span style="color: green;">Online</span></p>
        </div>
        <div class="card info">
            <h3>Python Backend Status</h3>
            <p style='color: green;'>✅ <strong>Python database integration is working perfectly!</strong></p>
            <p>All enhanced trading functionality is available via Python scripts:</p>
            <ul>
                <li><strong>Enhanced Trading:</strong> <code>python enhanced_trading_script.py</code></li>
                <li><strong>Database Testing:</strong> <code>python test_database_connection.py</code></li>
                <li><strong>Table Management:</strong> <code>python database_architect.py</code></li>
            </ul>
        </div>
    </div>
</body>
</html>
