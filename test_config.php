<?php
echo "Testing database connectivity...\n";

try {
    // Test if we can include the config classes
    require_once 'web_ui/DbConfigClasses.php';
    echo "✓ DbConfigClasses loaded\n";
    
    // Test config loading
    $config = LegacyDatabaseConfig::load();
    if ($config) {
        echo "✓ Configuration loaded\n";
        print_r($config);
    } else {
        echo "✗ Configuration failed to load\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
