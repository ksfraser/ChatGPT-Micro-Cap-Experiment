<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Management</title>
    <?php require_once 'UiStyles.php'; ?>
    <?php UiStyles::render(); ?>
</head>
<body>
    <div class="container">
        <?php require_once 'QuickActions.php'; ?>
        <?php QuickActions::render(); ?>
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
        
        <?php require_once 'QuickActions.php'; ?>
        <?php QuickActions::render(); ?>

        <div class="card">
            <h3>Database Tools</h3>
            <ul>
                <li><a href="#dbtest" onclick="showSection('dbtest');return false;">DB Test</a></li>
                <li><a href="#dbdiagnosis" onclick="showSection('dbdiagnosis');return false;">DB Diagnosis</a></li>
            </ul>
        </div>

        <div id="dbtest" class="card" style="display:none;">
            <?php include 'db_test.php'; ?>
        </div>
        <div id="dbdiagnosis" class="card" style="display:none;">
            <?php include 'db_diagnosis.php'; ?>
        </div>

        <script>
        function showSection(id) {
            document.getElementById('dbtest').style.display = 'none';
            document.getElementById('dbdiagnosis').style.display = 'none';
            document.getElementById(id).style.display = 'block';
        }
        </script>
    </div>
</body>
</html>