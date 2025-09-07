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
                <code>python enhanced_trading_script.py</code>
                <button class="btn" onclick="runPy('enhanced_trading_script', this)">Run</button></p>

                <h4>Database Query:</h4>
                <p># Direct database access for trade analysis<br>
                <code>python -c \"from enhanced_trading_script import *; engine = create_trading_engine('micro'); print('Trade data available via Python')\"</code>
                <button class="btn" onclick="runPy('enhanced_trading_script_db_query', this)">Run</button></p>
                <div id="py-output" style="margin-top:10px; color:#333;"></div>
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
            </div>
        </div>
        
        <div class="card">
            <h3>Trade Log Structure</h3>
            <?php require_once 'TradeLogDAO.php'; ?>
            <form method="get" style="margin-bottom:20px;display:flex;gap:20px;flex-wrap:wrap;align-items:end;">
                <div><label>Date From<br><input type="date" name="date_from" value="<?=htmlspecialchars($_GET['date_from']??'')?>"></label></div>
                <div><label>Date To<br><input type="date" name="date_to" value="<?=htmlspecialchars($_GET['date_to']??'')?>"></label></div>
                <div><label>Ticker<br><input type="text" name="ticker" value="<?=htmlspecialchars($_GET['ticker']??'')?>" placeholder="e.g. ABEO"></label></div>
                <div><label>Cost Min<br><input type="number" step="any" name="cost_min" value="<?=htmlspecialchars($_GET['cost_min']??'')?>"></label></div>
                <div><label>Cost Max<br><input type="number" step="any" name="cost_max" value="<?=htmlspecialchars($_GET['cost_max']??'')?>"></label></div>
                <div><button class="btn" type="submit">Filter</button></div>
            </form>
            <div style="display: flex; flex-wrap: wrap; gap: 30px;">
                <div style="flex:1; min-width:320px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; background:#fafbfc;">
                    <h4>Micro-Cap Trade Log (DB/CSV)</h4>
                    <?php
                    $filters = [
                        'date_from' => $_GET['date_from'] ?? '',
                        'date_to' => $_GET['date_to'] ?? '',
                        'ticker' => $_GET['ticker'] ?? '',
                        'cost_min' => $_GET['cost_min'] ?? '',
                        'cost_max' => $_GET['cost_max'] ?? '',
                    ];
                    $dao = new TradeLogDAO('../Scripts and CSV Files/chatgpt_trade_log.csv', 'trade_log', 'MicroCapDatabaseConfig');
                    $rows = $dao->readTradeLog($filters);
                    $errors = $dao->getErrors();
                    if ($rows && count($rows)) {
                        echo '<table style="width:100%;font-size:0.95em;">';
                        echo '<tr>';
                        foreach (array_keys($rows[0]) as $h) echo '<th style="background:#eee;">' . htmlspecialchars($h) . '</th>';
                        echo '</tr>';
                        foreach ($rows as $r) {
                            echo '<tr>';
                            foreach ($r as $v) echo '<td>' . htmlspecialchars($v) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    } else {
                        echo '<em>No micro-cap trade log data found.</em>';
                    }
                    if ($errors && count($errors)) {
                        echo '<div style="color:#b00;margin-top:10px;"><strong>Warning:</strong><ul>';
                        foreach ($errors as $err) echo '<li>' . htmlspecialchars($err) . '</li>';
                        echo '</ul></div>';
                    }
                    ?>
                </div>
                <div style="flex:1; min-width:320px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; background:#fafbfc;">
                    <h4>Blue-Chip Trade Log (DB/CSV)</h4>
                    <?php
                    $dao = new TradeLogDAO('../Scripts and CSV Files/blue_chip_cap_trade_log.csv', 'trade_log', 'LegacyDatabaseConfig');
                    $rows = $dao->readTradeLog($filters);
                    $errors = $dao->getErrors();
                    if ($rows && count($rows)) {
                        echo '<table style="width:100%;font-size:0.95em;">';
                        echo '<tr>';
                        foreach (array_keys($rows[0]) as $h) echo '<th style="background:#eee;">' . htmlspecialchars($h) . '</th>';
                        echo '</tr>';
                        foreach ($rows as $r) {
                            echo '<tr>';
                            foreach ($r as $v) echo '<td>' . htmlspecialchars($v) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    } else {
                        echo '<em>No blue-chip trade log data found.</em>';
                    }
                    if ($errors && count($errors)) {
                        echo '<div style="color:#b00;margin-top:10px;"><strong>Warning:</strong><ul>';
                        foreach ($errors as $err) echo '<li>' . htmlspecialchars($err) . '</li>';
                        echo '</ul></div>';
                    }
                    ?>
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
