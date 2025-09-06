<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade History - Enhanced Trading System</title>
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
        .success { border-left: 4px solid #28a745; }
        .btn { 
            display: inline-block; padding: 10px 20px; background: #007bff; color: white; 
            text-decoration: none; border-radius: 4px; margin: 5px;
        }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Trade History</h1>
            <p>View trading history and transaction logs across all market categories</p>
    <?php require_once 'QuickActions.php'; ?>
    <?php QuickActions::render(); ?>
        </div>
        
        <div class="card success">
            <h3>✅ Database Architecture</h3>
            <p>Trade history is stored in multiple locations based on the new database architecture:</p>
            <?php require_once 'UiStyles.php'; ?>
            <?php UiStyles::render(); ?>
                <h4>View Recent Trades:</h4>
                <p># Load and display portfolio with recent trades<br>
                python enhanced_trading_script.py</p>
                
                <h4>Database Query:</h4>
                <p># Direct database access for trade analysis<br>
                python -c "from enhanced_trading_script import *; engine = create_trading_engine('micro'); print('Trade data available via Python')"</p>
            </div>
        </div>
        
        <div class="card">
            <h3>Trade Log Structure</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h4>CSV Format (Original)</h4>
                    <ul>
                        <li>Date</li>
                        <li>Ticker</li>
                        <li>Shares Bought/Sold</li>
                        <li>Buy/Sell Price</li>
                        <li>Cost Basis/Proceeds</li>
                        <li>Reason</li>
                    </ul>
                </div>
                
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h4>Enhanced Database Format</h4>
                    <ul>
                        <li>Portfolio Type</li>
                        <li>Symbol & Action</li>
                        <li>Quantity & Price</li>
                        <li>Fees & Amount</li>
                        <li>LLM Session ID</li>
                        <li>Risk Score & Strategy</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>File Locations</h3>
            <ul>
                <li><strong>Micro-cap:</strong> data_micro_cap/micro_cap_trade_log.csv</li>
                <li><strong>Blue-chip:</strong> data_blue-chip_cap/blue-chip_cap_trade_log.csv</li>
                <li><strong>Small-cap:</strong> data_small_cap/small_cap_trade_log.csv</li>
            </ul>
        </div>
        
        <div class="card">
            <h3>Quick Actions</h3>
            <a href="index.php" class="btn">Dashboard</a>
            <a href="portfolios.php" class="btn">View Portfolios</a>
            <a href="analytics.php" class="btn">Analytics</a>
            <a href="database.php" class="btn">Database Manager</a>
        </div>
    </div>
</body>
</html>
