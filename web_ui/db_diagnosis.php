<?php
/**
 * Database Connection Workaround
 * Handles the missing PDO MySQL driver issue
 */

echo "<h2>Database Connection Diagnosis & Workaround</h2>\n";

// Check available database functions
echo "<h3>Available Database Extensions:</h3>";
echo "<ul>";

$extensions = ['pdo_mysql', 'mysql', 'mysqli', 'pdo', 'mysqlnd'];
foreach ($extensions as $ext) {
    $available = extension_loaded($ext);
    $status = $available ? '✅ Available' : '❌ Missing';
    echo "<li><strong>{$ext}:</strong> {$status}</li>";
}
echo "</ul>";

// Try MySQLi connection as fallback
echo "<h3>Testing MySQLi Connection (Fallback):</h3>";

if (extension_loaded('mysqli')) {
    $config = [
        'host' => 'fhsws001.ksfraser.com',
        'username' => 'stocks', 
        'password' => 'stocks',
        'port' => 3306
    ];
    
    $databases = [
        'stock_market_2' => 'Master Database',
        'stock_market_micro_cap_trading' => 'Micro-cap Database'
    ];
    
    foreach ($databases as $dbname => $description) {
        echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px;'>";
        echo "<strong>{$description} ({$dbname})</strong><br>";
        
        $connection = @mysqli_connect(
            $config['host'], 
            $config['username'], 
            $config['password'], 
            $dbname,
            $config['port']
        );
        
        if ($connection) {
            $version = mysqli_get_server_info($connection);
            $result = mysqli_query($connection, "SHOW TABLES");
            $tableCount = mysqli_num_rows($result);
            
            echo "<span style='color: green;'>✅ MySQLi connection successful!</span><br>";
            echo "Server Version: {$version}<br>";
            echo "Tables: {$tableCount}<br>";
            
            mysqli_close($connection);
        } else {
            echo "<span style='color: red;'>❌ MySQLi connection failed!</span><br>";
            echo "Error: " . mysqli_connect_error() . "<br>";
        }
        
        echo "</div>";
    }
} else {
    echo "<p style='color: red;'>MySQLi extension not available either.</p>";
}

// Solution instructions
echo "<h3>Solution Options:</h3>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<h4>Option 1: Enable PDO MySQL Extension</h4>";
echo "<p>The issue is that PHP is missing the <code>pdo_mysql</code> extension.</p>";
echo "<p><strong>For Windows:</strong></p>";
echo "<ol>";
echo "<li>Find your PHP installation directory</li>";
echo "<li>Edit <code>php.ini</code> file</li>";
echo "<li>Uncomment or add: <code>extension=pdo_mysql</code></li>";
echo "<li>Restart PHP server</li>";
echo "</ol>";

echo "<h4>Option 2: Use Alternative Database Functions</h4>";
echo "<p>We can modify the web interface to use MySQLi instead of PDO.</p>";

echo "<h4>Option 3: Use Python Backend</h4>";
echo "<p>Since Python database connections are working perfectly, we can:</p>";
echo "<ul>";
echo "<li>Create Python API endpoints</li>";
echo "<li>Have PHP make HTTP requests to Python</li>";
echo "<li>Use JSON for data exchange</li>";
echo "</ul>";
echo "</div>";

echo "<h3>Current Workaround Status:</h3>";
if (extension_loaded('mysqli')) {
    echo "<p style='color: green;'>✅ MySQLi is available - we can create a working interface!</p>";
} else {
    echo "<p style='color: orange;'>⚠ Limited database options - recommend Python API approach</p>";
}

echo "<p><em>Diagnosis completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
