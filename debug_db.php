<?php
echo "Starting database debug...\n";

// Check if config file exists
if (!file_exists('db_config.yml')) {
    echo "ERROR: db_config.yml not found\n";
    exit(1);
}

echo "db_config.yml found\n";

// Test if we can load the config classes
try {
    require_once 'web_ui/DbConfigClasses.php';
    echo "DbConfigClasses loaded successfully\n";
} catch (Exception $e) {
    echo "Failed to load DbConfigClasses: " . $e->getMessage() . "\n";
    exit(1);
}

// Test database connection
try {
    echo "Attempting database connection...\n";
    $pdo = LegacyDatabaseConfig::createConnection();
    if ($pdo) {
        echo "Database connection successful!\n";
        echo "PDO object type: " . get_class($pdo) . "\n";
    } else {
        echo "Database connection returned null\n";
    }
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}

echo "Debug complete.\n";
?>
