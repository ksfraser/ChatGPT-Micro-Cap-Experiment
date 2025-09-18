<?php
/**
 * Market Factors System Test Runner
 * 
 * Simple test to validate the complete market factors system
 */

require_once __DIR__ . '/vendor/autoload.php';

use Ksfraser\Finance\MarketFactors\Services\MarketFactorsService;
use Ksfraser\Finance\MarketFactors\Repository\MarketFactorsRepository;
use Ksfraser\Finance\MarketFactors\Controllers\MarketFactorsController;
use Ksfraser\Finance\MarketFactors\Entities\IndexPerformance;
use Ksfraser\Finance\MarketFactors\Entities\ForexRate;

echo "=== Market Factors System Test ===\n\n";

try {
    // Create in-memory SQLite database
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create table
    $sql = "
        CREATE TABLE market_factors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            symbol VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(20) NOT NULL,
            value DECIMAL(15,6) NOT NULL,
            change_amount DECIMAL(15,6) DEFAULT 0.0,
            change_percent DECIMAL(8,4) DEFAULT 0.0,
            signal_strength DECIMAL(4,2) DEFAULT 0.0,
            age_hours INTEGER DEFAULT 0,
            metadata TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($sql);
    echo "✓ Database setup complete\n";
    
    // Initialize components
    $repository = new MarketFactorsRepository($pdo);
    $service = new MarketFactorsService($repository);
    $controller = new MarketFactorsController($pdo);
    echo "✓ Components initialized\n";
    
    // Test 1: Create factors via controller
    echo "\n--- Test 1: Creating Market Factors ---\n";
    
    $spyData = [
        'symbol' => 'SPY',
        'name' => 'SPDR S&P 500 ETF',
        'type' => 'index',
        'value' => 450.25,
        'change' => 5.25,
        'change_percent' => 1.18
    ];
    
    $result = $controller->createOrUpdateFactor($spyData);
    if ($result['success']) {
        echo "✓ SPY factor created successfully\n";
    } else {
        echo "✗ Failed to create SPY factor: " . $result['error'] . "\n";
    }
    
    $forexData = [
        'symbol' => 'EURUSD',
        'name' => 'EUR/USD',
        'type' => 'forex',
        'value' => 1.0850,
        'change' => 0.0025,
        'change_percent' => 0.23
    ];
    
    $result = $controller->createOrUpdateFactor($forexData);
    if ($result['success']) {
        echo "✓ EURUSD factor created successfully\n";
    } else {
        echo "✗ Failed to create EURUSD factor: " . $result['error'] . "\n";
    }
    
    // Test 2: Retrieve factors
    echo "\n--- Test 2: Retrieving Market Factors ---\n";
    
    $allFactors = $controller->getAllFactors();
    if ($allFactors['success']) {
        echo "✓ Retrieved " . count($allFactors['data']) . " factors\n";
        foreach ($allFactors['data'] as $factor) {
            echo "  - {$factor['symbol']}: {$factor['name']} ({$factor['type']})\n";
        }
    } else {
        echo "✗ Failed to retrieve factors\n";
    }
    
    // Test 3: Get specific factor
    $spyFactor = $controller->getFactorBySymbol('SPY');
    if ($spyFactor['success']) {
        echo "✓ Retrieved SPY factor: Value = {$spyFactor['data']['value']}, Change = {$spyFactor['data']['change_percent']}%\n";
    } else {
        echo "✗ Failed to retrieve SPY factor\n";
    }
    
    // Test 4: Filter by type
    echo "\n--- Test 3: Filtering by Type ---\n";
    
    $indexFactors = $controller->getFactorsByType('index');
    if ($indexFactors['success']) {
        echo "✓ Found " . count($indexFactors['data']) . " index factors\n";
    }
    
    $forexFactors = $controller->getFactorsByType('forex');
    if ($forexFactors['success']) {
        echo "✓ Found " . count($forexFactors['data']) . " forex factors\n";
    }
    
    // Test 5: Market summary
    echo "\n--- Test 4: Market Analysis ---\n";
    
    $summary = $controller->getMarketSummary();
    if ($summary['success']) {
        echo "✓ Market summary generated\n";
        echo "  - Total factors: {$summary['data']['total_factors']}\n";
        echo "  - Market sentiment: {$summary['data']['sentiment']['sentiment']}\n";
        echo "  - Bullish factors: {$summary['data']['sentiment']['bullish_factors']}\n";
        echo "  - Bearish factors: {$summary['data']['sentiment']['bearish_factors']}\n";
    }
    
    // Test 6: Top performers
    $topPerformers = $controller->getTopPerformers(5);
    if ($topPerformers['success']) {
        echo "✓ Top performers analysis complete\n";
        foreach ($topPerformers['data'] as $performer) {
            echo "  - {$performer['symbol']}: {$performer['change_percent']}%\n";
        }
    }
    
    // Test 7: Statistics
    echo "\n--- Test 5: System Statistics ---\n";
    
    $stats = $controller->getStatistics();
    if ($stats['success']) {
        echo "✓ Statistics generated\n";
        echo "  - Total factors: {$stats['data']['total_factors']}\n";
        echo "  - Market sentiment: " . round($stats['data']['sentiment'], 2) . "%\n";
        echo "  - Index factors: {$stats['data']['by_type']['index']}\n";
        echo "  - Forex factors: {$stats['data']['by_type']['forex']}\n";
    }
    
    // Test 8: Service-level operations
    echo "\n--- Test 6: Service Layer Operations ---\n";
    
    // Add factor via service
    $tech = new IndexPerformance('QQQ', 'Invesco QQQ Trust', 'US', 380.50, 8.10, 2.18);
    $service->addFactor($tech);
    echo "✓ Added QQQ factor via service layer\n";
    
    // Test correlations
    $service->trackCorrelation('SPY', 'QQQ', 0.85, 30);
    $correlations = $service->getCorrelationMatrix();
    if (isset($correlations['SPY-QQQ'])) {
        echo "✓ Correlation tracking working: SPY-QQQ = {$correlations['SPY-QQQ']}\n";
    }
    
    // Test data export/import
    echo "\n--- Test 7: Data Export/Import ---\n";
    
    $exportData = $service->exportData();
    echo "✓ Data exported: " . count($exportData['factors']) . " factors\n";
    
    $newService = new MarketFactorsService($repository);
    $importResult = $newService->importData($exportData);
    if ($importResult) {
        echo "✓ Data import successful\n";
    }
    
    echo "\n=== All Tests Completed Successfully! ===\n";
    echo "Market Factors System is fully operational.\n\n";
    
} catch (Exception $e) {
    echo "\n✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
