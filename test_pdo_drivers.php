<?php
echo "Available PDO drivers:\n";
$drivers = PDO::getAvailableDrivers();
foreach ($drivers as $driver) {
    echo "- $driver\n";
}

echo "\nLooking for database configuration files...\n";
$possibleFiles = [
    __DIR__ . '/web_ui/db_config.yml',
    __DIR__ . '/web_ui/db_config.yaml',
    __DIR__ . '/web_ui/db_config.ini',
    __DIR__ . '/db_config.yml',
    __DIR__ . '/db_config.yaml',
    __DIR__ . '/db_config.ini'
];

foreach ($possibleFiles as $file) {
    if (file_exists($file)) {
        echo "✓ Found: $file\n";
    } else {
        echo "✗ Missing: $file\n";
    }
}
