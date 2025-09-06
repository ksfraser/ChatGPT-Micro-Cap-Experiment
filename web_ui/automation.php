<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automation Control - Enhanced Trading System</title>
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
        .warning { border-left: 4px solid #ffc107; }
        .danger { border-left: 4px solid #dc3545; }
        .btn { 
            display: inline-block; padding: 10px 20px; background: #007bff; color: white; 
            text-decoration: none; border-radius: 4px; margin: 5px;
        }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-danger { background: #dc3545; }
        .btn:hover { opacity: 0.8; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; }
        .code-block { 
            background: #f8f9fa; padding: 15px; border-radius: 5px; 
            font-family: monospace; font-size: 0.9em; overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🤖 Automation Control Center</h1>
            <p>Enhanced trading automation with multi-market cap support</p>
        </div>
        
        <div class="card success">
            <h3>✅ System Status</h3>
            <p>Enhanced automation engine is fully operational with the following features:</p>
            <ul>
                <li><strong>Multi-Market Cap Support:</strong> Micro, Small, Mid-cap trading</li>
                <li><strong>Dual Storage:</strong> CSV backup + MySQL database</li>
                <li><strong>LLM Integration:</strong> AI-driven decision making</li>
                <li><strong>Session Management:</strong> Trading session tracking</li>
                <li><strong>Risk Management:</strong> Position sizing and stop losses</li>
            </ul>
        </div>
        
        <div class="grid">
            <div class="card info">
                <h4>🚀 Start Automation</h4>
                <p>Launch the enhanced automation engine:</p>
                <div class="code-block">
                    # Enhanced automation with database<br>
                    python enhanced_automation.py<br><br>
                    # Original automation (CSV only)<br>
                    python simple_automation.py
                </div>
                <p><strong>Note:</strong> Enhanced version includes database logging and multi-market cap support.</p>
            </div>
            
            <div class="card warning">
                <h4>⚙️ Configuration</h4>
                <p>Available automation modes:</p>
                <ul>
                    <li><strong>Micro Cap:</strong> Original experiment format</li>
                    <li><strong>Small Cap:</strong> Enhanced small-cap trading</li>
                    <li><strong>Mid Cap:</strong> Mid-cap market automation</li>
                </ul>
                <p>Configure in <code>db_config_refactored.yml</code></p>
            </div>
            
            <div class="card info">
                <h4>📊 Monitoring</h4>
                <p>Real-time automation monitoring:</p>
                <ul>
                    <li>Portfolio performance tracking</li>
                    <li>Trade execution logging</li>
                    <li>LLM interaction analytics</li>
                    <li>Session-based reporting</li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <h3>🎛️ Control Commands</h3>
            <div class="grid">
                <div class="card info">
                    <h4>Market Cap Selection</h4>
                    <div class="code-block">
                        # Run micro-cap automation<br>
                        python enhanced_automation.py --market_cap micro<br><br>
                        # Run small-cap automation<br>
                        python enhanced_automation.py --market_cap small<br><br>
                        # Run mid-cap automation<br>
                        python enhanced_automation.py --market_cap mid
                    </div>
                </div>
                
                <div class="card warning">
                    <h4>Session Management</h4>
                    <div class="code-block">
                        # Start new trading session<br>
                        python -c "from enhanced_automation import *; engine = EnhancedAutomationEngine('micro'); engine.start_session()"<br><br>
                        # View active sessions<br>
                        python -c "from database_architect import *; arch = DatabaseArchitect(); arch.show_sessions()"
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card danger">
            <h3>⚠️ Risk Controls</h3>
            <p>Built-in safety features:</p>
            <ul>
                <li><strong>Position Limits:</strong> Automatic position sizing based on portfolio value</li>
                <li><strong>Stop Losses:</strong> Configurable stop-loss percentages</li>
                <li><strong>Daily Limits:</strong> Maximum trades per day</li>
                <li><strong>Portfolio Limits:</strong> Maximum portfolio concentration</li>
            </ul>
            
            <div class="code-block">
                # Emergency stop all automation<br>
                python -c "import os; os.system('taskkill /f /im python.exe')"<br><br>
                # Check current positions<br>
                python -c "from enhanced_automation import *; engine = EnhancedAutomationEngine('micro'); engine.show_positions()"
            </div>
        </div>
        
        <div class="card">
            <h3>📈 Performance Tracking</h3>
            <p>The automation system tracks comprehensive metrics:</p>
            <div class="grid">
                <div class="card info">
                    <h4>Trading Metrics</h4>
                    <ul>
                        <li>Win/Loss Ratios</li>
                        <li>Average Trade Duration</li>
                        <li>Profit/Loss per Trade</li>
                        <li>Market Cap Performance</li>
                    </ul>
                </div>
                
                <div class="card info">
                    <h4>LLM Analytics</h4>
                    <ul>
                        <li>Prompt Response Times</li>
                        <li>Token Usage Tracking</li>
                        <li>Decision Accuracy</li>
                        <li>Cost Analysis</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>🔧 Troubleshooting</h3>
            <div class="code-block">
                # Check system health<br>
                python -c "from database_architect import *; arch = DatabaseArchitect(); arch.test_connections()"<br><br>
                # Validate configuration<br>
                python -c "import yaml; print(yaml.safe_load(open('db_config_refactored.yml')))"<br><br>
                # View recent trades<br>
                python -c "import pandas as pd; print(pd.read_csv('chatgpt_trade_log.csv').tail())"
            </div>
        </div>
        
        <div class="card">
            <h3>Quick Actions</h3>
            <a href="index.php" class="btn">Dashboard</a>
            <a href="portfolios.php" class="btn">View Portfolios</a>
            <a href="trades.php" class="btn">Trade History</a>
            <a href="analytics.php" class="btn">Analytics</a>
            <a href="database.php" class="btn">Database Manager</a>
        </div>
    </div>
</body>
</html>
