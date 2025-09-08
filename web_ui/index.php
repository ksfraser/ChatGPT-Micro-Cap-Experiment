<?php
/**
 * Main Dashboard - Enhanced Trading System
 */

// Include authentication check (will redirect if not logged in)
require_once 'auth_check.php';

// Include navigation header
require_once 'nav_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Enhanced Trading System</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 0; background: #f5f5f5; 
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
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
        .stat-card {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body>

<?php renderNavigationHeader('Enhanced Trading System Dashboard'); ?>

<div class="container">
    <div class="header">
        <h1>Welcome to Your Trading Dashboard</h1>
        <p>Manage your portfolios, track trades, and analyze performance across all market segments</p>
    </div>

    <?php if (isCurrentUserAdmin()): ?>
    <div class="card warning">
        <h3>🔧 Administrator Access</h3>
        <p>You have administrator privileges. You can manage users, system settings, and access all system functions.</p>
    </div>
    <?php endif; ?>
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
        
        <div class="card info">
            <h3>Database Architecture</h3>
            <p><strong>Micro-cap DB:</strong> CSV-mirrored data only</p>
            <p><strong>Master DB:</strong> All enhanced features</p>
            <p><strong>Separation:</strong> Clean data organization</p>
        </div>
        
    <?php require_once 'QuickActions.php'; ?>
    <?php QuickActions::render(); ?>
        
        <!-- QuickActions and Database Architecture only -->
        </div>
    </div>
</body>
</html>