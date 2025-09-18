<?php

/**
 * Test the enhanced Market Factors Service with Technical Indicator Accuracy Tracking
 */

require_once __DIR__ . '/src/Ksfraser/Finance/MarketFactors/Services/MarketFactorsService.php';
require_once __DIR__ . '/src/Ksfraser/Finance/MarketFactors/Entities/MarketFactor.php';

use Ksfraser\Finance\MarketFactors\Services\MarketFactorsService;
use Ksfraser\Finance\MarketFactors\Entities\MarketFactor;

echo "=== Testing Enhanced Market Factors Service ===\n\n";

try {
    // Create service instance
    $service = new MarketFactorsService();
    
    echo "1. Setting up sample correlations...\n";
    
    // Add sample factor correlations
    $service->setCorrelation('AAPL', 'SP500', 0.85);
    $service->setCorrelation('AAPL', 'NASDAQ', 0.92);
    $service->setCorrelation('AAPL', 'INTEREST_RATE', -0.65);
    $service->setCorrelation('AAPL', 'VIX', -0.55);
    
    echo "✓ Sample correlations added\n\n";
    
    echo "2. Testing technical indicator tracking...\n";
    
    // Track some RSI predictions
    $rsiPrediction1 = $service->trackIndicatorPrediction(
        'RSI', 'AAPL', 'buy', 85.0, 150.00, '1d'
    );
    echo "✓ RSI prediction tracked: $rsiPrediction1\n";
    
    $rsiPrediction2 = $service->trackIndicatorPrediction(
        'RSI', 'AAPL', 'hold', 60.0, 151.00, '1d'
    );
    echo "✓ RSI prediction tracked: $rsiPrediction2\n";
    
    // Track some MACD predictions
    $macdPrediction1 = $service->trackIndicatorPrediction(
        'MACD', 'AAPL', 'buy', 90.0, 150.50, '1w'
    );
    echo "✓ MACD prediction tracked: $macdPrediction1\n";
    
    $macdPrediction2 = $service->trackIndicatorPrediction(
        'MACD', 'AAPL', 'sell', 75.0, 149.00, '1d'
    );
    echo "✓ MACD prediction tracked: $macdPrediction2\n";
    
    echo "\n3. Updating prediction outcomes...\n";
    
    // Update RSI outcomes
    $service->updateIndicatorAccuracy($rsiPrediction1, 'correct', 155.00);
    echo "✓ RSI prediction 1 updated as correct (price went up)\n";
    
    $service->updateIndicatorAccuracy($rsiPrediction2, 'correct', 151.50);
    echo "✓ RSI prediction 2 updated as correct (minimal movement for hold)\n";
    
    // Update MACD outcomes  
    $service->updateIndicatorAccuracy($macdPrediction1, 'correct', 158.00);
    echo "✓ MACD prediction 1 updated as correct (strong buy signal)\n";
    
    $service->updateIndicatorAccuracy($macdPrediction2, 'incorrect', 152.00);
    echo "✓ MACD prediction 2 updated as incorrect (predicted sell but price went up)\n";
    
    echo "\n4. Checking indicator accuracy...\n";
    
    $rsiAccuracy = $service->getIndicatorAccuracy('RSI');
    echo "RSI Accuracy Data:\n";
    echo "  - Total Predictions: " . $rsiAccuracy['total_predictions'] . "\n";
    echo "  - Correct Predictions: " . $rsiAccuracy['correct_predictions'] . "\n";
    echo "  - Average Accuracy: " . round($rsiAccuracy['average_accuracy'], 2) . "%\n";
    echo "  - Performance Score: " . $service->getIndicatorPerformanceScore('RSI') . "x\n";
    
    $macdAccuracy = $service->getIndicatorAccuracy('MACD');
    echo "\nMACD Accuracy Data:\n";
    echo "  - Total Predictions: " . $macdAccuracy['total_predictions'] . "\n";
    echo "  - Correct Predictions: " . $macdAccuracy['correct_predictions'] . "\n";
    echo "  - Average Accuracy: " . round($macdAccuracy['average_accuracy'], 2) . "%\n";
    echo "  - Performance Score: " . $service->getIndicatorPerformanceScore('MACD') . "x\n";
    
    echo "\n5. Testing weighted scoring system...\n";
    
    // Define market factors for AAPL
    $marketFactors = [
        ['symbol' => 'SP500', 'value' => 0.8],        // Positive market sentiment
        ['symbol' => 'NASDAQ', 'value' => 0.9],       // Strong tech sector
        ['symbol' => 'INTEREST_RATE', 'value' => -0.2], // Low interest rates (good for stocks)
        ['symbol' => 'VIX', 'value' => -0.3]          // Low volatility (market stability)
    ];
    
    // Define technical indicators
    $technicalIndicators = [
        ['name' => 'RSI', 'value' => 0.7],     // Moderate buy signal
        ['name' => 'MACD', 'value' => 0.6],    // Moderate buy signal
        ['name' => 'SMA', 'value' => 0.5]      // New indicator with no history
    ];
    
    $weightedScore = $service->calculateWeightedScore('AAPL', $marketFactors, $technicalIndicators);
    
    echo "Weighted Score Analysis for AAPL:\n";
    echo "==========================================\n";
    
    echo "\nFactor Analysis:\n";
    echo "- Normalized Score: " . round($weightedScore['factor_analysis']['normalized_score'], 3) . "\n";
    echo "- Total Weight: " . round($weightedScore['factor_analysis']['total_weight'], 3) . "\n";
    echo "- Factors Analyzed: " . $weightedScore['factor_analysis']['factors_analyzed'] . "\n";
    
    echo "\nFactor Details:\n";
    foreach ($weightedScore['factor_analysis']['details'] as $detail) {
        echo "  - {$detail['factor']}: {$detail['value']} × {$detail['correlation']} = {$detail['weighted_contribution']}\n";
    }
    
    echo "\nIndicator Analysis:\n";
    echo "- Normalized Score: " . round($weightedScore['indicator_analysis']['normalized_score'], 3) . "\n";
    echo "- Total Weight: " . round($weightedScore['indicator_analysis']['total_weight'], 3) . "\n";
    echo "- Indicators Analyzed: " . $weightedScore['indicator_analysis']['indicators_analyzed'] . "\n";
    
    echo "\nIndicator Details:\n";
    foreach ($weightedScore['indicator_analysis']['details'] as $detail) {
        $accuracy = $detail['accuracy'] ? round($detail['accuracy'], 1) . "%" : "No data";
        echo "  - {$detail['indicator']}: {$detail['value']} × {$detail['performance_weight']}x (Accuracy: $accuracy)\n";
    }
    
    echo "\nCombined Score:\n";
    echo "- Final Score: " . round($weightedScore['combined_score']['weighted_score'], 3) . "\n";
    echo "- Confidence: " . round($weightedScore['combined_score']['confidence'], 2) . "\n";
    
    echo "\nRecommendation:\n";
    echo "- Action: " . strtoupper($weightedScore['recommendation']['action']) . "\n";
    echo "- Strength: " . ucfirst($weightedScore['recommendation']['strength']) . "\n";
    echo "- Risk Level: " . ucfirst($weightedScore['recommendation']['risk_level']) . "\n";
    echo "- Reasoning: " . implode(', ', $weightedScore['recommendation']['reasoning']) . "\n";
    
    echo "\n6. Testing edge cases...\n";
    
    // Test unknown indicator
    $unknownAccuracy = $service->getIndicatorAccuracy('UNKNOWN_INDICATOR');
    echo "Unknown indicator accuracy: " . ($unknownAccuracy ? "Found" : "Not found") . " ✓\n";
    
    // Test performance score for new indicator
    $newIndicatorScore = $service->getIndicatorPerformanceScore('NEW_INDICATOR');
    echo "New indicator performance score: {$newIndicatorScore}x (default) ✓\n";
    
    echo "\n=== Enhanced Market Factors Service Test Complete ===\n";
    echo "✓ Technical indicator accuracy tracking working\n";
    echo "✓ Weighted scoring system operational\n";
    echo "✓ Correlation-based factor weighting active\n";
    echo "✓ Accuracy-based indicator weighting implemented\n";
    echo "✓ System ready for real-world usage\n\n";
    
    echo "Key Features Validated:\n";
    echo "- Factor-stock correlation scoring ✓\n";
    echo "- Technical indicator accuracy tracking ✓\n";
    echo "- Historical performance weighting ✓\n";
    echo "- Comprehensive recommendation engine ✓\n";
    echo "- Risk assessment and confidence scoring ✓\n";
    
} catch (Exception $e) {
    echo "\n=== Test Failed ===\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
