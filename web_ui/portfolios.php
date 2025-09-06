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
    <?php require_once 'QuickActions.php'; ?>
    <?php QuickActions::render(); ?>
        </div>
        
        <div class="card">
            <h3>Portfolio Data Locations</h3>
            <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                <h4>Micro-Cap Portfolio</h4>
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
        
        <div class="card">
            <h3>Python Command Line Access</h3>
            <p>Use these commands to manage portfolios with full database integration:</p>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace;">
                <div style="margin-bottom:10px;">
                    <p><strong># View micro-cap portfolio</strong><br>
                    <code>python ../enhanced_trading_script.py</code>
                    <button class="btn btn-secondary" onclick="runPy('enhanced_trading_script', this)">Run</button></p>
                </div>
                <div style="margin-bottom:10px;">
                    <p><strong># Test database connections</strong><br>
                    <code>python ../test_database_connection.py</code>
                    <button class="btn btn-secondary" onclick="runPy('test_database_connection', this)">Run</button></p>
                </div>
                <div style="margin-bottom:10px;">
                    <p><strong># Run enhanced automation</strong><br>
                    <code>python ../enhanced_automation.py</code>
                    <button class="btn btn-secondary" onclick="runPy('enhanced_automation', this)">Run</button></p>
                </div>
                <div id="py-output" style="margin-top:10px; color:#333;"></div>
            </div>
        </div>
        <script>
        function runPy(cmdKey, btn) {
            btn.disabled = true;
            btn.innerText = 'Running...';
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'run_python_command.php');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                btn.disabled = false;
                btn.innerText = 'Run';
                var out = document.getElementById('py-output');
                try {
                    var resp = JSON.parse(xhr.responseText);
                    out.innerText = resp.output || resp.error || 'No output.';
                } catch(e) {
                    out.innerText = 'Error: ' + xhr.responseText;
                }
            };
            xhr.send('command_key=' + encodeURIComponent(cmdKey));
        }
        </script>
        
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
