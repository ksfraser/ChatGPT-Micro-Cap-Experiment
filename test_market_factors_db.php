<?php
/**
 * Test Market Factors Database Setup
 */

require_once __DIR__ . '/DatabaseConfig.php';

try {
    echo "Testing database connection to stock_market_2...\n";
    
    // Get legacy database connection (stock_market_2)
    $pdo = DatabaseConfig::createLegacyConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to create database connection");
    }
    
    echo "✅ Database connection established\n";
    
    // Test database name
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current database: " . $result['current_db'] . "\n";
    
    // Create a simple market factors table
    $sql = "
        CREATE TABLE IF NOT EXISTS market_factors_test (
            id INT AUTO_INCREMENT PRIMARY KEY,
            symbol VARCHAR(50) NOT NULL,
            name VARCHAR(255) NOT NULL,
            type ENUM('sector', 'index', 'forex', 'economic') NOT NULL,
            value DECIMAL(15,6) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_symbol (symbol)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql);
    echo "✅ Created test market_factors_test table\n";
    
    // Insert test data
    $stmt = $pdo->prepare("INSERT IGNORE INTO market_factors_test (symbol, name, type, value) VALUES (?, ?, ?, ?)");
    $stmt->execute(['SPY', 'SPDR S&P 500 ETF', 'index', 450.25]);
    $stmt->execute(['EURUSD', 'Euro to US Dollar', 'forex', 1.0850]);
    $stmt->execute(['XLK', 'Technology Sector', 'sector', 165.45]);
    
    echo "✅ Inserted test data\n";
    
    // Verify data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM market_factors_test");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Test records count: " . $result['count'] . "\n";
    
    // Show existing tables
    $stmt = $pdo->query("SHOW TABLES LIKE 'market_factors%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No existing market_factors tables found\n";
    } else {
        echo "Existing market_factors tables:\n";
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    }
    
    echo "\n✅ Database test completed successfully!\n";
    echo "Ready to create full market factors schema.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
