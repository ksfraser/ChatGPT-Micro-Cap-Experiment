<?php
/**
 * Correlation System Setup Script
 * Initializes correlation database tables and tests the system
 * Uses existing database modules following SOLID/DRY/DI principles
 * 
 * @package Scripts
 */

require_once __DIR__ . '/src/Ksfraser/Database/EnhancedDbManager.php';
require_once __DIR__ . '/src/Ksfraser/Database/PdoConnection.php';
require_once __DIR__ . '/src/Ksfraser/Database/MysqliConnection.php';
require_once __DIR__ . '/src/Database/CorrelationSchemaSetup.php';
require_once __DIR__ . '/src/Repository/Interfaces/CorrelationRepositoryInterface.php';
require_once __DIR__ . '/src/Repository/CorrelationRepository.php';
require_once __DIR__ . '/src/Service/Interfaces/CorrelationServiceInterface.php';
require_once __DIR__ . '/src/Service/CorrelationService.php';

use Database\CorrelationSchemaSetup;
use Repository\CorrelationRepository;
use Service\CorrelationService;
use Ksfraser\Database\EnhancedDbManager;

echo "========================================\n";
echo "Correlation System Setup and Test\n";
echo "========================================\n\n";

try {
    // Step 1: Test database connection
    echo "1. Testing database connection...\n";
    $connection = EnhancedDbManager::getConnection();
    $driver = EnhancedDbManager::getCurrentDriver();
    echo "   ✓ Connected using driver: {$driver}\n\n";
    
    // Step 2: Setup correlation tables
    echo "2. Setting up correlation database tables...\n";
    $setupResults = CorrelationSchemaSetup::setupCorrelationTables();
    
    if ($setupResults['overall_status'] === 'success') {
        echo "   ✓ All correlation tables created successfully\n";
        foreach ($setupResults as $table => $result) {
            if ($table !== 'overall_status' && $table !== 'message') {
                echo "     - {$table}: " . ($result ? "✓" : "✗") . "\n";
            }
        }
    } else {
        throw new Exception("Failed to setup tables: " . $setupResults['message']);
    }
    echo "\n";
    
    // Step 3: Create optimization indexes
    echo "3. Creating optimization indexes...\n";
    $indexResult = CorrelationSchemaSetup::createOptimizationIndexes();
    echo "   " . ($indexResult ? "✓" : "✗") . " Optimization indexes created\n\n";
    
    // Step 4: Verify table structure
    echo "4. Verifying table structure...\n";
    $verification = CorrelationSchemaSetup::verifyTableStructure();
    foreach ($verification as $table => $result) {
        echo "   {$table}: " . ($result['exists'] ? "✓" : "✗") . "\n";
    }
    echo "\n";
    
    // Step 5: Test repository layer
    echo "5. Testing correlation repository...\n";
    $repository = new CorrelationRepository();
    
    // Test factor-stock correlation
    $testCorrelationData = [
        'factor_type' => 'interest_rate',
        'factor_id' => 1,
        'stock_symbol' => 'AAPL',
        'correlation_coefficient' => 0.7234,
        'sample_size' => 30,
        'confidence_level' => 85.5
    ];
    
    $correlationId = $repository->saveFactorStockCorrelation($testCorrelationData);
    echo "   ✓ Saved test correlation (ID: {$correlationId})\n";
    
    $retrievedCorrelation = $repository->getFactorStockCorrelationById($correlationId);
    echo "   ✓ Retrieved correlation: " . json_encode($retrievedCorrelation['correlation_coefficient']) . "\n";
    
    // Test technical analysis accuracy
    $testAccuracyData = [
        'indicator_name' => 'RSI',
        'stock_symbol' => 'AAPL',
        'prediction_type' => 'buy',
        'predicted_date' => date('Y-m-d H:i:s'),
        'actual_outcome' => 'correct',
        'accuracy_score' => 85.0,
        'confidence_score' => 90.0
    ];
    
    $accuracyId = $repository->saveTechnicalAnalysisAccuracy($testAccuracyData);
    echo "   ✓ Saved test accuracy record (ID: {$accuracyId})\n";
    
    // Test indicator performance
    $testPerformanceData = [
        'indicator_name' => 'RSI',
        'overall_accuracy' => 78.5,
        'total_predictions' => 100,
        'correct_predictions' => 78,
        'performance_period_start' => date('Y-m-d', strtotime('-30 days')),
        'performance_period_end' => date('Y-m-d')
    ];
    
    $performanceId = $repository->saveIndicatorPerformance($testPerformanceData);
    echo "   ✓ Saved test performance record (ID: {$performanceId})\n";
    
    // Test weighted scores
    $testScoreData = [
        'stock_symbol' => 'AAPL',
        'calculation_date' => date('Y-m-d'),
        'market_factors_weighted_score' => 0.6234,
        'technical_analysis_weighted_score' => 0.7456,
        'recommendation' => 'buy'
    ];
    
    $scoreId = $repository->saveWeightedScores($testScoreData);
    echo "   ✓ Saved test weighted scores (ID: {$scoreId})\n\n";
    
    // Step 6: Test correlation service
    echo "6. Testing correlation service...\n";
    $service = new CorrelationService($repository);
    
    // Test correlation calculation
    $correlationResult = $service->calculateFactorStockCorrelation('interest_rate', 1, 'MSFT');
    echo "   ✓ Calculated correlation: " . $correlationResult['correlation_coefficient'] . " (" . $correlationResult['correlation_strength'] . ")\n";
    
    // Test bulk correlation calculation
    $bulkResult = $service->calculateBulkFactorStockCorrelations('interest_rate', 1, ['MSFT', 'GOOGL', 'TSLA']);
    echo "   ✓ Bulk correlation calculated for " . $bulkResult['total_stocks'] . " stocks (Success: " . $bulkResult['successful_calculations'] . ")\n";
    
    // Test accuracy tracking
    $prediction = [
        'prediction_type' => 'buy',
        'predicted_date' => date('Y-m-d H:i:s'),
        'confidence_score' => 85.0,
        'timeframe' => '1d'
    ];
    
    $outcome = [
        'actual_outcome' => 'correct',
        'outcome_date' => date('Y-m-d H:i:s'),
        'profit_loss_percentage' => 5.2
    ];
    
    $accuracyResult = $service->trackIndicatorAccuracy('MACD', 'MSFT', $prediction, $outcome);
    echo "   ✓ Tracked indicator accuracy: " . $accuracyResult['accuracy_score'] . "% (Running: " . $accuracyResult['running_accuracy'] . "%)\n";
    
    // Test weighted scoring
    $marketFactors = [
        ['type' => 'interest_rate', 'id' => 1, 'value' => 0.5],
        ['type' => 'market_sentiment', 'id' => 1, 'value' => 0.3]
    ];
    
    $technicalIndicators = [
        ['name' => 'RSI', 'timeframe' => '1d', 'value' => 0.7],
        ['name' => 'MACD', 'timeframe' => '1d', 'value' => 0.4]
    ];
    
    $weightedResult = $service->calculateWeightedScore('AAPL', $marketFactors, $technicalIndicators);
    echo "   ✓ Calculated weighted score: " . $weightedResult['combined_score']['weighted_score'] . " (Recommendation: " . $weightedResult['recommendation']['recommendation'] . ")\n\n";
    
    // Step 7: Test correlation strength classification
    echo "7. Testing correlation strength classification...\n";
    $testCorrelations = [0.1, 0.3, 0.5, 0.7, 0.9];
    foreach ($testCorrelations as $coeff) {
        $strength = $service->getCorrelationStrength($coeff);
        echo "   Correlation {$coeff}: {$strength}\n";
    }
    echo "\n";
    
    // Step 8: Display repository statistics
    echo "8. Repository statistics...\n";
    $stockCorrelations = $repository->getCorrelationsByStock('AAPL');
    echo "   ✓ AAPL correlations: " . count($stockCorrelations) . "\n";
    
    $factorCorrelations = $repository->getCorrelationsByFactor('interest_rate', 1);
    echo "   ✓ Interest rate correlations: " . count($factorCorrelations) . "\n";
    
    $accuracyRecords = $repository->getTechnicalAnalysisAccuracy('AAPL', 'RSI');
    echo "   ✓ RSI accuracy records for AAPL: " . count($accuracyRecords) . "\n";
    
    $indicatorPerformance = $repository->getIndicatorPerformance('RSI');
    echo "   ✓ RSI performance records: " . count($indicatorPerformance) . "\n";
    
    $weightedScores = $repository->getWeightedScores('AAPL');
    echo "   ✓ AAPL weighted scores: " . count($weightedScores) . "\n\n";
    
    echo "========================================\n";
    echo "✓ Correlation System Setup Complete!\n";
    echo "========================================\n\n";
    
    echo "Summary:\n";
    echo "- Database tables: Created and verified\n";
    echo "- Repository layer: Tested and functional\n";
    echo "- Correlation service: Tested and operational\n";
    echo "- Sample data: Created for testing\n\n";
    
    echo "Next steps:\n";
    echo "1. Integrate with existing market factors system\n";
    echo "2. Add REST API endpoints\n";
    echo "3. Build web interface\n";
    echo "4. Implement real-time correlation updates\n";
    echo "5. Add comprehensive testing framework\n\n";
    
} catch (Exception $e) {
    echo "✗ Error during setup: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
