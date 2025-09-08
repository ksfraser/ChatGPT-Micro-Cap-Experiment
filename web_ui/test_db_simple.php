<?php
try {
    require_once 'DbConfigClasses.php';
    echo "Config loaded\n";
    $pdo = LegacyDatabaseConfig::createConnection();
    echo "Connection: " . ($pdo ? "SUCCESS" : "FAILED") . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
