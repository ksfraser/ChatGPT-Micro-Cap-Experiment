<?php

/**
 * Simple setup script for the correlation scoring system
 * Uses existing database modules following SOLID principles
 */

// Include necessary files
require_once __DIR__ . '/web_ui/DbConfigClasses.php';

try {
    echo "Setting up Correlation System Database...\n\n";
    
    // Use the existing LegacyDatabaseConfig that's already working
    echo "Connecting to database using LegacyDatabaseConfig...\n";
    $pdo = LegacyDatabaseConfig::createConnection();
    
    $config = LegacyDatabaseConfig::getConfig();
    echo "Connected to database: {$config['dbname']} on {$config['host']}\n\n";
    
    // Read the correlation setup SQL
    echo "Reading correlation setup SQL...\n";
    $sqlFile = __DIR__ . '/correlation_setup.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL setup file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("Failed to read SQL setup file");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) { return !empty($stmt) && !preg_match('/^\s*--/', $stmt); }
    );
    
    echo "Found " . count($statements) . " SQL statements to execute\n\n";
    
    // Execute each statement
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) {
            continue;
        }
        
        try {
            echo "Executing statement " . ($index + 1) . "...\n";
            $pdo->exec($statement);
            echo "✓ Statement " . ($index + 1) . " executed successfully\n";
        } catch (PDOException $e) {
            echo "✗ Failed to execute statement " . ($index + 1) . ": " . $e->getMessage() . "\n";
            echo "SQL: " . $statement . "\n";
            throw $e;
        }
    }
    
    echo "\nCorrelation system database setup completed successfully!\n";
    
    // Verify tables were created
    echo "\nVerifying tables...\n";
    $tables = [
        'stock_factor_correlations',
        'technical_indicator_accuracy', 
        'correlation_calculation_jobs',
        'correlation_summary_stats'
    ];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table $table exists\n";
        } else {
            echo "✗ Table $table missing\n";
        }
    }
    
    echo "\n=== Correlation System Setup Complete ===\n";
    echo "✓ Database tables created\n";
    echo "✓ System ready for use\n\n";
    
} catch (Exception $e) {
    echo "\n=== Setup Failed ===\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    if ($e->getPrevious()) {
        echo "Previous error: " . $e->getPrevious()->getMessage() . "\n";
    }
    
    exit(1);
}
