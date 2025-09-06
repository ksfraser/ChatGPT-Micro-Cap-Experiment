<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Management - Enhanced Trading System</title>
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
        .info { border-left: 4px solid #007bff; }
        .warning { border-left: 4px solid #ffc107; }
        .btn { 
            display: inline-block; padding: 10px 20px; background: #007bff; color: white; 
            text-decoration: none; border-radius: 4px; margin: 5px;
        }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Portfolio Management</h1>
            <p>View and manage trading portfolios across different market categories</p>
        </div>
        
        <div class="card warning">
            <h3>⚠️ PHP Database Limitations</h3>
            <p>This PHP installation doesn't have MySQL database extensions enabled. For full portfolio functionality, use the Python backend:</p>
            <ul>
                <li><strong>Enhanced Trading Script:</strong> <code>python enhanced_trading_script.py</code></li>
                <li><strong>Portfolio Analysis:</strong> Direct database access with full functionality</li>
            </ul>
        </div>
        
        <div class="card info">
            <h3>Available Portfolio Categories</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h4>Micro-Cap Portfolio</h4>
                    <p><strong>Database:</strong> stock_market_micro_cap_trading</p>
                    <p><strong>Purpose:</strong> CSV-mirrored original data</p>
                    <p><strong>Data Directory:</strong> data_micro_cap/</p>
                </div>
                
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h4>Blue-Chip Portfolio</h4>
                    <p><strong>Database:</strong> stock_market_2</p>
                    <p><strong>Purpose:</strong> Enhanced features</p>
                    <p><strong>Data Directory:</strong> data_blue-chip_cap/</p>
                </div>
                
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h4>Small-Cap Portfolio</h4>
                    <p><strong>Database:</strong> stock_market_2</p>
                    <p><strong>Purpose:</strong> Enhanced features</p>
                    <p><strong>Data Directory:</strong> data_small_cap/</p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>Python Command Line Access</h3>
            <p>Use these commands to manage portfolios with full database integration:</p>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace;">
                <p><strong># View micro-cap portfolio</strong><br>
                python enhanced_trading_script.py</p>
                
                <p><strong># Test database connections</strong><br>
                python test_database_connection.py</p>
                
                <p><strong># Run enhanced automation</strong><br>
                python enhanced_automation.py</p>
            </div>
        </div>
        
        <div class="card">
            <h3>Quick Actions</h3>
            <a href="index.php" class="btn">Dashboard</a>
            <a href="trades.php" class="btn">Trade History</a>
            <a href="analytics.php" class="btn">Analytics</a>
            <a href="database.php" class="btn">Database Manager</a>
        </div>
    </div>
</body>
</html>
