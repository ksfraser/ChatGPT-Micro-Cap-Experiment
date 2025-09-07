<?php
echo "Testing INI configuration...\n";

// Test direct INI parsing
$config = parse_ini_file('db_config.ini', true);
echo "Direct INI parse result:\n";
print_r($config);

echo "\nTesting database config classes...\n";
try {
    require_once 'web_ui/DbConfigClasses.php';
    
    // Test LegacyDatabaseConfig
    echo "Testing LegacyDatabaseConfig...\n";
    $legacyConfig = LegacyDatabaseConfig::getConfig();
    echo "Legacy config:\n";
    print_r($legacyConfig);
    
    // Test connection
    echo "Testing legacy database connection...\n";
    $pdo = LegacyDatabaseConfig::createConnection();
    if ($pdo) {
        echo "✓ Legacy database connection successful!\n";
        echo "PDO class: " . get_class($pdo) . "\n";
    } else {
        echo "✗ Legacy database connection failed (null)\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
