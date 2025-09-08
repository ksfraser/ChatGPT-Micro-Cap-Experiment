<?php
/**
 * Simple autoloader for Ksfraser Database and Auth namespaces
 * This is a fallback autoloader for testing when composer is not available
 */

// Check if composer autoloader exists first
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Simple PSR-4 autoloader
    spl_autoload_register(function ($className) {
        // Base directory for the namespace prefix
        $baseDir = __DIR__ . '/src/';
        
        // Does the class use the namespace prefix?
        $prefix = 'Ksfraser\\';
        $len = strlen($prefix);
        if (strncmp($prefix, $className, $len) !== 0) {
            // No, move to the next registered autoloader
            return;
        }
        
        // Get the relative class name
        $relativeClass = substr($className, $len);
        
        // Replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    });
    
    // Also register PHPUnit if not available
    if (!class_exists('PHPUnit\Framework\TestCase')) {
        echo "PHPUnit not found. Please install PHPUnit or run tests with a proper test runner.\n";
        echo "You can install PHPUnit using: composer require --dev phpunit/phpunit\n";
        exit(1);
    }
}
?>
