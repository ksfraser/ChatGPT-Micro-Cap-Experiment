<?php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Database Management</h1>
        
        <div class="card">
            <h3>Database Architecture Overview</h3>
            <table>
                <tr>
                    <th>Database</th>
                    <th>Purpose</th>
                    <th>Tables</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>stock_market_micro_cap_trading</td>
                    <td>CSV-mirrored data only</td>
                    <td>portfolio_data, trade_log, historical_prices</td>
                    <td style="color: green;">Active</td>
                </tr>
                <tr>
                    <td>stock_market_2</td>
                    <td>Master database - all enhanced features</td>
                    <td>All new tables and analytics</td>
                    <td style="color: green;">Active</td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h3>Table Management</h3>
            <p>Centralized location for creating and managing all database tables</p>
            <a href="create_tables.php" class="btn">Create/Update Tables</a>
            <a href="backup.php" class="btn">Backup Databases</a>
            <a href="migrate.php" class="btn">Data Migration</a>
        </div>
        
        <a href="index.php" class="btn">Back to Dashboard</a>
    </div>
</body>
</html>