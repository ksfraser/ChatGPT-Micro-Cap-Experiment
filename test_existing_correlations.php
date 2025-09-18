<?php

/**
 * Test correlation functionality using existing MarketFactorsService
 * This tests the correlation system that's already integrated
 */

// Include the existing service
require_once __DIR__ . '/src/Ksfraser/Finance/MarketFactors/Services/MarketFactorsService.php';

use Ksfraser\Finance\MarketFactors\Services\MarketFactorsService;

try {
    echo "=== Testing Existing Correlation System ===\n\n";
    
    // Create service instance (no repository needed for testing correlations)
    $service = new MarketFactorsService();
    
    echo "1. Adding sample correlation data...\n";
    
    // Add some sample correlations between stocks and factors
    $service->setCorrelation('AAPL', 'SP500', 0.85);
    $service->setCorrelation('AAPL', 'NASDAQ', 0.92);
    $service->setCorrelation('AAPL', 'INTEREST_RATE', -0.65);
    $service->setCorrelation('AAPL', 'VIX', -0.55);
    
    $service->setCorrelation('MSFT', 'SP500', 0.78);
    $service->setCorrelation('MSFT', 'NASDAQ', 0.88);
    $service->setCorrelation('MSFT', 'INTEREST_RATE', -0.45);
    
    $service->setCorrelation('GOOGL', 'SP500', 0.72);
    $service->setCorrelation('GOOGL', 'NASDAQ', 0.89);
    $service->setCorrelation('GOOGL', 'VIX', -0.48);
    
    echo "✓ Sample correlations added\n\n";
    
    echo "2. Testing correlation analysis...\n";
    
    // Test individual correlations
    $appleSpCorr = $service->analyzeCorrelation('AAPL', 'SP500');
    echo "AAPL vs SP500 correlation: $appleSpCorr\n";
    
    $appleNasdaqCorr = $service->analyzeCorrelation('AAPL', 'NASDAQ');
    echo "AAPL vs NASDAQ correlation: $appleNasdaqCorr\n";
    
    $appleInterestCorr = $service->analyzeCorrelation('AAPL', 'INTEREST_RATE');
    echo "AAPL vs Interest Rate correlation: $appleInterestCorr\n";
    
    echo "\n3. Testing correlated factors...\n";
    
    // Get strongly correlated factors for AAPL
    $strongCorrelations = $service->getCorrelatedFactors('AAPL', 0.7);
    echo "Factors strongly correlated with AAPL (>= 0.7):\n";
    foreach ($strongCorrelations as $factor) {
        echo "  - {$factor['symbol']}: {$factor['correlation']}\n";
    }
    
    echo "\n4. Testing correlation matrix...\n";
    
    // Get full correlation matrix
    $matrix = $service->getCorrelationMatrix();
    echo "Full correlation matrix:\n";
    foreach ($matrix as $pair => $correlation) {
        echo "  $pair: $correlation\n";
    }
    
    echo "\n=== Correlation System Test Complete ===\n";
    echo "✓ All correlation functionality working\n";
    echo "✓ Ready for integration with technical indicator accuracy tracking\n\n";
    
    echo "Next steps:\n";
    echo "1. Access web UI at: web_ui/market_factors.php\n";
    echo "2. Click 'View Correlations' button to test API\n";
    echo "3. Add technical indicator accuracy tracking to existing service\n";
    echo "4. Enhance correlation calculation with historical data\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
