<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Database Config ===\n";

try {
    require_once __DIR__ . '/web_ui/DbConfigClasses.php';
    echo "SUCCESS: DbConfigClasses.php loaded\n";
    
    // Try to create a database connection
    $pdo = LegacyDatabaseConfig::createConnection();
    echo "SUCCESS: Database connection created\n";
    
    if ($pdo) {
        echo "PDO instance: " . get_class($pdo) . "\n";
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
