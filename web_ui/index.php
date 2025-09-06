<?php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Trading System - Dashboard</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 20px; background: #f5f5f5; 
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card { 
            background: white; padding: 20px; margin: 10px 0; border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .success { border-left: 4px solid #28a745; }
        .info { border-left: 4px solid #007bff; }
        .warning { border-left: 4px solid #ffc107; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .btn { 
            display: inline-block; padding: 10px 20px; background: #007bff; color: white; 
            text-decoration: none; border-radius: 4px; margin: 5px;
        }
        .btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Enhanced Trading System</h1>
            <p>Centralized dashboard for multi-market cap trading operations</p>
        </div>
        
        <div class="grid">
            <div class="card success">
                <h3>System Status</h3>
                <p><strong>Server Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
                <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
                <p><strong>Status:</strong> <span style="color: green;">Online</span></p>
            </div>
            
            <div class="card info">
                <h3>Database Architecture</h3>
                <p><strong>Micro-cap DB:</strong> CSV-mirrored data only</p>
                <p><strong>Master DB:</strong> All enhanced features</p>
                <p><strong>Separation:</strong> Clean data organization</p>
            </div>
        </div>
        
        <div class="card">
            <h3>Quick Actions</h3>
            <a href="portfolios.php" class="btn">View Portfolios</a>
            <a href="trades.php" class="btn">Trade History</a>
            <a href="analytics.php" class="btn">Analytics</a>
            <a href="database.php" class="btn">Database Manager</a>
            <a href="automation.php" class="btn">Automation</a>
        </div>
        
        <div class="card">
            <h3>Database Connection Test</h3>
            <?php
            $databases = [
                ['name' => 'Master Database', 'db' => 'stock_market_2'],
                ['name' => 'Micro-cap Database', 'db' => 'stock_market_micro_cap_trading']
            ];
            
            foreach ($databases as $database) {
                try {
                    $pdo = new PDO("mysql:host=fhsws001.ksfraser.com;dbname={$database['db']}", 'stocks', 'stocks');
                    echo "<p style='color: green;'>✓ {$database['name']} connection successful</p>";
                } catch(PDOException $e) {
                    echo "<p style='color: red;'>✗ {$database['name']} connection failed</p>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>