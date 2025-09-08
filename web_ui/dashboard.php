<?php
/**
 * User Dashboard - Main logged-in area
 */

require_once __DIR__ . '/UserAuthDAO.php';

$auth = new UserAuthDAO();

// Require login
$auth->requireLogin();

$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Portfolio Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info span {
            opacity: 0.9;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }
        
        .dashboard-card h3 {
            margin: 0 0 1rem 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .dashboard-card p {
            color: #666;
            margin: 0 0 1rem 0;
        }
        
        .card-links {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .card-links a {
            color: #667eea;
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .card-links a:hover {
            background: #f8f9fa;
            text-decoration: underline;
        }
        
        .welcome-section {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
            text-align: center;
        }
        
        .admin-badge {
            background: #28a745;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .status-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 4px;
            padding: 1rem;
            margin: 1rem 0;
            color: #1976d2;
        }
        
        /* Navigation compatibility - adjust for nav header */
        body {
            margin: 0;
            padding: 0;
        }
        
        .header {
            margin-top: 0;
        }
    </style>
</head>
<body>

<?php
// Include navigation header
require_once 'nav_header.php';
renderNavigationHeader('Portfolio Dashboard - Enhanced Trading System');
?>

    <header class="header">
        <div class="header-content">
            <h1>💼 Portfolio Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</span>
                <?php if ($user['is_admin']): ?>
                    <span class="admin-badge">ADMIN</span>
                <?php endif; ?>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="welcome-section">
            <h2>🎯 Your Portfolio Management Hub</h2>
            <p>Access all your investment tools and portfolio data from this centralized dashboard.</p>
            
            <div class="status-info">
                <strong>🔧 System Status:</strong> User authentication is active. Database connection required for full portfolio functionality.
            </div>
        </div>
        
        <div class="dashboard-grid">
            <!-- Portfolio Management -->
            <div class="dashboard-card">
                <h3>📊 Portfolio Management</h3>
                <p>View and manage your investment portfolios, track performance, and analyze holdings.</p>
                <div class="card-links">
                    <a href="../">📈 Portfolio Overview</a>
                    <a href="portfolio_manager.php">⚙️ Manage Portfolios</a>
                    <a href="../simple_automation.py">🤖 Automation Scripts</a>
                </div>
            </div>
            
            <!-- Account Management -->
            <div class="dashboard-card">
                <h3>🏦 Account Management</h3>
                <p>Manage your brokerage accounts, account types, and banking information.</p>
                <div class="card-links">
                    <a href="admin_account_types.php">📋 Account Types</a>
                    <a href="admin_brokerages_simple.php">🏢 Brokerages</a>
                    <a href="admin_bank_accounts.php">🏪 Bank Accounts</a>
                </div>
            </div>
            
            <!-- Data Import -->
            <div class="dashboard-card">
                <h3>📥 Data Import</h3>
                <p>Import transaction data and account holdings from your brokerages and banks.</p>
                <div class="card-links">
                    <a href="bank_import.php">💾 Bank CSV Import</a>
                    <a href="trade_log.php">📝 Trade Log</a>
                </div>
            </div>
            
            <!-- Reports & Analysis -->
            <div class="dashboard-card">
                <h3>📊 Reports & Analysis</h3>
                <p>Generate reports, view performance charts, and analyze your investment strategy.</p>
                <div class="card-links">
                    <a href="../Scripts and CSV Files/Generate_Graph.py">📈 Performance Charts</a>
                    <a href="reports.php">📋 Custom Reports</a>
                </div>
            </div>
            
            <!-- User Profile -->
            <div class="dashboard-card">
                <h3>👤 User Profile</h3>
                <p>Manage your account settings, security preferences, and profile information.</p>
                <div class="card-links">
                    <a href="profile.php">⚙️ Edit Profile</a>
                    <a href="change_password.php">🔐 Change Password</a>
                </div>
            </div>
            
            <?php if ($user['is_admin']): ?>
            <!-- Admin Tools -->
            <div class="dashboard-card">
                <h3>🔧 Admin Tools</h3>
                <p>Administrative functions for managing users and system settings.</p>
                <div class="card-links">
                    <a href="admin_users.php">👥 User Management</a>
                    <a href="admin_system.php">⚙️ System Settings</a>
                    <a href="database.php">🗄️ Database Management</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Stats -->
        <div style="margin-top: 2rem;">
            <div class="dashboard-card">
                <h3>📋 Quick Stats</h3>
                <p><strong>User ID:</strong> <?php echo $user['id']; ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Account Type:</strong> <?php echo $user['is_admin'] ? 'Administrator' : 'Standard User'; ?></p>
                <p><strong>Login Time:</strong> <?php echo date('Y-m-d H:i:s', $user['login_time']); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
