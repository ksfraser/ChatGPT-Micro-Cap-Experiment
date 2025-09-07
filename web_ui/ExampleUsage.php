<?php

/**
 * Example usage of the improved architecture with dependency injection
 * This demonstrates how to set up and use the new SOLID-principle based code
 */

require_once __DIR__ . '/DIContainer.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/DatabaseConnection.php';
require_once __DIR__ . '/ImportService.php';

// Example configuration
$config = [
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'trading_db',
        'username' => 'your_username',
        'password' => 'your_password'
    ],
    'upload' => [
        'directory' => __DIR__ . '/uploads'
    ],
    'logging' => [
        'level' => 'INFO',
        'file' => __DIR__ . '/logs/app.log'
    ]
];

// Initialize the DI container and register services
$container = new DIContainer();

// Register configuration
$container->singleton('config', function() use ($config) {
    return new Configuration($config);
});

// Register logger
$container->singleton('logger', function() use ($container) {
    $config = $container->resolve('config');
    $logFile = $config->get('logging.file', __DIR__ . '/app.log');
    $logLevel = $config->get('logging.level', 'INFO');
    return new FileLogger($logFile, $logLevel);
});

// Register database connection
$container->singleton('database', function() use ($container) {
    $config = $container->resolve('config');
    $logger = $container->resolve('logger');
    return new DatabaseConnection($config, $logger);
});

// Register import service
$container->singleton('import_service', function() use ($container) {
    $db = $container->resolve('database');
    $logger = $container->resolve('logger');
    $config = $container->resolve('config');
    return ImportServiceFactory::create($db, $logger, $config);
});

// Example: Import transactions from a CSV file
function importTransactionsExample()
{
    global $container;
    
    try {
        $importService = $container->resolve('import_service');
        $logger = $container->resolve('logger');
        
        // Example CSV file path
        $csvFile = __DIR__ . '/example_transactions.csv';
        
        if (!file_exists($csvFile)) {
            echo "Creating example CSV file...\n";
            createExampleCsvFile($csvFile);
        }
        
        echo "Starting import from: $csvFile\n";
        
        $result = $importService->importTransactionsFromFile($csvFile);
        
        if ($result->isSuccess()) {
            echo "Import successful!\n";
            echo "Processed: " . $result->getProcessedCount() . " transactions\n";
            
            if ($result->hasWarnings()) {
                echo "Warnings:\n";
                foreach ($result->getWarnings() as $warning) {
                    echo "  - $warning\n";
                }
            }
        } else {
            echo "Import failed!\n";
            echo "Errors:\n";
            foreach ($result->getErrors() as $error) {
                echo "  - $error\n";
            }
        }
        
        // Get import statistics
        $stats = $importService->getImportStats();
        echo "\nImport Statistics:\n";
        echo "Total transactions in database: " . $stats['total_transactions'] . "\n";
        echo "Portfolio positions: " . $stats['portfolio_positions'] . "\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Create an example CSV file for testing
function createExampleCsvFile($filePath)
{
    $csvData = [
        ['symbol', 'shares', 'price', 'txn_date', 'txn_type'],
        ['AAPL', '100', '150.50', '2024-01-15', 'BUY'],
        ['GOOGL', '50', '2800.75', '2024-01-16', 'BUY'],
        ['MSFT', '75', '420.25', '2024-01-17', 'BUY'],
        ['AAPL', '25', '155.00', '2024-01-18', 'SELL'],
        ['TSLA', '200', '250.80', '2024-01-19', 'BUY']
    ];
    
    $fp = fopen($filePath, 'w');
    foreach ($csvData as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);
}

// Example: Handle file upload and import
function handleFileUploadExample()
{
    global $container;
    
    // This would typically be called from a web form handler
    if (isset($_FILES['csv_file'])) {
        try {
            $importService = $container->resolve('import_service');
            $result = $importService->importTransactionsFromUpload('csv_file');
            
            // Return JSON response for AJAX
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result->isSuccess(),
                'processed' => $result->getProcessedCount(),
                'errors' => $result->getErrors(),
                'warnings' => $result->getWarnings()
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'errors' => ['Upload failed: ' . $e->getMessage()]
            ]);
        }
    }
}

// Example: Use individual DAOs
function daoUsageExample()
{
    global $container;
    
    try {
        $db = $container->resolve('database');
        $logger = $container->resolve('logger');
        
        // Create validators
        $transactionValidator = new TransactionDataValidator($logger);
        
        // Create DAO
        $transactionDAO = new TransactionDAO($db, $logger, $transactionValidator);
        
        // Insert a transaction
        $transactionData = [
            'symbol' => 'NVDA',
            'shares' => 100,
            'price' => 500.00,
            'txn_date' => '2024-01-20',
            'txn_type' => 'BUY'
        ];
        
        $transactionId = $transactionDAO->insertTransaction($transactionData);
        echo "Inserted transaction with ID: $transactionId\n";
        
        // Get portfolio summary
        $portfolio = $transactionDAO->getPortfolioSummary();
        echo "Current portfolio:\n";
        foreach ($portfolio as $position) {
            echo "  {$position['symbol']}: {$position['total_shares']} shares @ avg cost {$position['avg_buy_price']}\n";
        }
        
    } catch (Exception $e) {
        echo "DAO example error: " . $e->getMessage() . "\n";
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    echo "=== Improved Trading System Architecture Demo ===\n\n";
    
    echo "1. Import transactions example:\n";
    importTransactionsExample();
    
    echo "\n2. DAO usage example:\n";
    daoUsageExample();
    
    echo "\nDemo completed!\n";
    echo "Note: To use file upload functionality, run this through a web server with form data.\n";
}

// For web usage, you can call handleFileUploadExample() when processing uploads
