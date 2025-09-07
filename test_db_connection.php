<?php
require_once 'web_ui/DbConfigClasses.php';

try {
    $pdo = LegacyDatabaseConfig::createConnection();
    echo "Database connection successful\n";
    echo "PDO object type: " . get_class($pdo) . "\n";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
