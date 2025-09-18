<?php
/**
 * Correlation Calculation Service
 * Handles correlation calculations, accuracy tracking, and weighted scoring
 * Uses dependency injection following SOLID/DRY/DI principles
 * 
 * @package Service
 */

namespace Service;

use Service\Interfaces\CorrelationServiceInterface;
use Repository\Interfaces\CorrelationRepositoryInterface;
use Repository\CorrelationRepository;
use Exception;
use DateTime;

class CorrelationService implements CorrelationServiceInterface
{
    /** @var CorrelationRepositoryInterface Repository for correlation data */
    protected $correlationRepository;
    
    /**
     * Constructor with dependency injection
     * 
     * @param CorrelationRepositoryInterface|null $correlationRepository Optional repository injection
     */
    public function __construct(?CorrelationRepositoryInterface $correlationRepository = null)
    {
        $this->correlationRepository = $correlationRepository ?? new CorrelationRepository();
    }
    
    /**
     * Calculate correlation between a market factor and stock price movements
     * 
     * @param string $factorType Type of market factor
     * @param int $factorId Factor ID
     * @param string $stockSymbol Stock symbol
     * @param array $options Calculation options (timeframe, period, etc.)
     * @return array Correlation results
     */
    public function calculateFactorStockCorrelation(string $factorType, int $factorId, string $stockSymbol, array $options = []): array
    {
        try {
            // Set default options
            $options = array_merge([
                'period_days' => 30,
                'min_data_points' => 10,
                'timeframe' => '1d',
                'calculation_method' => 'pearson'
            ], $options);
            
            // Get factor data
            $factorData = $this->getFactorData($factorType, $factorId, $options);
            
            // Get stock price data
            $stockData = $this->getStockData($stockSymbol, $options);
            
            // Validate data availability
            if (count($factorData) < $options['min_data_points'] || count($stockData) < $options['min_data_points']) {
                throw new Exception("Insufficient data points for correlation calculation");
            }
            
            // Align data by date
            $alignedData = $this->alignDataByDate($factorData, $stockData);
            
            if (count($alignedData) < $options['min_data_points']) {
                throw new Exception("Insufficient aligned data points for correlation calculation");
            }
            
            // Calculate correlation coefficient
            $correlationCoefficient = $this->calculatePearsonCorrelation($alignedData);
            
            // Calculate statistical significance
            $significance = $this->calculateStatisticalSignificance($correlationCoefficient, count($alignedData));
            
            // Prepare correlation data
            $correlationData = [
                'factor_type' => $factorType,
                'factor_id' => $factorId,
                'stock_symbol' => $stockSymbol,
                'correlation_coefficient' => round($correlationCoefficient, 4),
                'sample_size' => count($alignedData),
                'confidence_level' => round($significance['confidence_level'], 2),
                'calculation_date' => (new DateTime())->format('Y-m-d H:i:s'),
                'is_active' => true
            ];
            
            // Save correlation to repository
            $correlationId = $this->correlationRepository->saveFactorStockCorrelation($correlationData);
            
            // Save historical record
            $this->saveCorrelationHistory($correlationId, $correlationData, $options);
            
            return [
                'success' => true,
                'correlation_id' => $correlationId,
                'correlation_coefficient' => $correlationCoefficient,
                'correlation_strength' => $this->getCorrelationStrength($correlationCoefficient),
                'sample_size' => count($alignedData),
                'confidence_level' => $significance['confidence_level'],
                'p_value' => $significance['p_value'],
                'is_significant' => $significance['is_significant'],
                'calculation_options' => $options,
                'data_quality' => $this->assessDataQuality($alignedData)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'correlation_coefficient' => 0.0,
                'correlation_strength' => 'very_weak'
            ];
        }
    }
    
    /**
     * Calculate correlation for multiple stocks against a factor
     * 
     * @param string $factorType Type of market factor
     * @param int $factorId Factor ID
     * @param array $stockSymbols Array of stock symbols
     * @param array $options Calculation options
     * @return array Correlation results for all stocks
     */
    public function calculateBulkFactorStockCorrelations(string $factorType, int $factorId, array $stockSymbols, array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($stockSymbols as $stockSymbol) {
            $result = $this->calculateFactorStockCorrelation($factorType, $factorId, $stockSymbol, $options);
            
            $results[$stockSymbol] = $result;
            
            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }
        
        return [
            'factor_type' => $factorType,
            'factor_id' => $factorId,
            'total_stocks' => count($stockSymbols),
            'successful_calculations' => $successCount,
            'failed_calculations' => $errorCount,
            'results' => $results,
            'summary' => $this->generateBulkCorrelationSummary($results)
        ];
    }
    
    /**
     * Track technical analysis indicator accuracy
     * 
     * @param string $indicatorName Indicator name
     * @param string $stockSymbol Stock symbol
     * @param array $prediction Prediction data
     * @param array $actualOutcome Actual outcome data
     * @return array Accuracy tracking results
     */
    public function trackIndicatorAccuracy(string $indicatorName, string $stockSymbol, array $prediction, array $actualOutcome): array
    {
        try {
            // Validate required prediction data
            $requiredPredictionFields = ['prediction_type', 'predicted_date', 'confidence_score'];
            foreach ($requiredPredictionFields as $field) {
                if (!isset($prediction[$field])) {
                    throw new Exception("Missing required prediction field: {$field}");
                }
            }
            
            // Validate required outcome data
            $requiredOutcomeFields = ['actual_outcome', 'outcome_date'];
            foreach ($requiredOutcomeFields as $field) {
                if (!isset($actualOutcome[$field])) {
                    throw new Exception("Missing required outcome field: {$field}");
                }
            }
            
            // Calculate accuracy score
            $accuracyScore = $this->calculateAccuracyScore($prediction, $actualOutcome);
            
            // Get historical accuracy for this indicator
            $historicalAccuracy = $this->getHistoricalAccuracy($indicatorName, $stockSymbol, $prediction['timeframe'] ?? '1d');
            
            // Update running totals
            $totalPredictions = $historicalAccuracy['total_predictions'] + 1;
            $correctPredictions = $historicalAccuracy['correct_predictions'] + ($accuracyScore > 75 ? 1 : 0);
            $runningAccuracy = ($correctPredictions / $totalPredictions) * 100;
            
            // Prepare accuracy data
            $accuracyData = [
                'indicator_name' => $indicatorName,
                'stock_symbol' => $stockSymbol,
                'timeframe' => $prediction['timeframe'] ?? '1d',
                'prediction_type' => $prediction['prediction_type'],
                'predicted_date' => $prediction['predicted_date'],
                'actual_outcome' => $actualOutcome['actual_outcome'],
                'outcome_date' => $actualOutcome['outcome_date'],
                'accuracy_score' => round($accuracyScore, 2),
                'confidence_score' => round($prediction['confidence_score'], 2),
                'profit_loss_percentage' => $actualOutcome['profit_loss_percentage'] ?? null,
                'total_predictions' => $totalPredictions,
                'correct_predictions' => $correctPredictions,
                'running_accuracy' => round($runningAccuracy, 2),
                'indicator_parameters' => json_encode($prediction['parameters'] ?? [])
            ];
            
            // Save accuracy record
            $accuracyId = $this->correlationRepository->saveTechnicalAnalysisAccuracy($accuracyData);
            
            // Update indicator performance metrics
            $this->updateIndicatorPerformance($indicatorName, $prediction['timeframe'] ?? '1d');
            
            return [
                'success' => true,
                'accuracy_id' => $accuracyId,
                'accuracy_score' => $accuracyScore,
                'running_accuracy' => $runningAccuracy,
                'total_predictions' => $totalPredictions,
                'correct_predictions' => $correctPredictions,
                'accuracy_improvement' => $runningAccuracy - $historicalAccuracy['running_accuracy'],
                'confidence_score' => $prediction['confidence_score']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'accuracy_score' => 0.0,
                'running_accuracy' => 0.0
            ];
        }
    }
    
    /**
     * Calculate weighted score for a stock based on correlations
     * 
     * @param string $stockSymbol Stock symbol
     * @param array $marketFactors Market factors data
     * @param array $technicalIndicators Technical indicators data
     * @param array $options Calculation options
     * @return array Weighted score results
     */
    public function calculateWeightedScore(string $stockSymbol, array $marketFactors, array $technicalIndicators, array $options = []): array
    {
        try {
            // Set default options
            $options = array_merge([
                'correlation_weight' => 0.7,
                'accuracy_weight' => 0.3,
                'confidence_threshold' => 50.0,
                'calculation_date' => date('Y-m-d')
            ], $options);
            
            // Calculate market factors weighted score
            $marketFactorsResult = $this->calculateMarketFactorsWeightedScore($stockSymbol, $marketFactors, $options);
            
            // Calculate technical analysis weighted score
            $technicalAnalysisResult = $this->calculateTechnicalAnalysisWeightedScore($stockSymbol, $technicalIndicators, $options);
            
            // Combine scores
            $combinedRawScore = ($marketFactorsResult['raw_score'] + $technicalAnalysisResult['raw_score']) / 2;
            $combinedWeightedScore = ($marketFactorsResult['weighted_score'] + $technicalAnalysisResult['weighted_score']) / 2;
            $combinedConfidence = ($marketFactorsResult['confidence'] + $technicalAnalysisResult['confidence']) / 2;
            
            // Generate recommendation
            $recommendation = $this->getRecommendation([
                'combined_weighted_score' => $combinedWeightedScore,
                'combined_confidence' => $combinedConfidence,
                'market_factors_score' => $marketFactorsResult['weighted_score'],
                'technical_analysis_score' => $technicalAnalysisResult['weighted_score']
            ]);
            
            // Prepare weighted scores data
            $scoreData = [
                'stock_symbol' => $stockSymbol,
                'calculation_date' => $options['calculation_date'],
                'market_factors_raw_score' => round($marketFactorsResult['raw_score'], 4),
                'market_factors_weighted_score' => round($marketFactorsResult['weighted_score'], 4),
                'market_factors_confidence' => round($marketFactorsResult['confidence'], 2),
                'technical_analysis_raw_score' => round($technicalAnalysisResult['raw_score'], 4),
                'technical_analysis_weighted_score' => round($technicalAnalysisResult['weighted_score'], 4),
                'technical_analysis_confidence' => round($technicalAnalysisResult['confidence'], 2),
                'combined_raw_score' => round($combinedRawScore, 4),
                'combined_weighted_score' => round($combinedWeightedScore, 4),
                'combined_confidence' => round($combinedConfidence, 2),
                'recommendation' => $recommendation['recommendation'],
                'recommendation_confidence' => round($recommendation['confidence'], 2),
                'factor_scores' => json_encode($marketFactorsResult['factor_breakdown']),
                'indicator_scores' => json_encode($technicalAnalysisResult['indicator_breakdown']),
                'calculation_method' => 'correlation_weighted',
                'is_current' => true
            ];
            
            // Save weighted scores
            $scoreId = $this->correlationRepository->saveWeightedScores($scoreData);
            
            return [
                'success' => true,
                'score_id' => $scoreId,
                'stock_symbol' => $stockSymbol,
                'calculation_date' => $options['calculation_date'],
                'market_factors' => $marketFactorsResult,
                'technical_analysis' => $technicalAnalysisResult,
                'combined_score' => [
                    'raw_score' => $combinedRawScore,
                    'weighted_score' => $combinedWeightedScore,
                    'confidence' => $combinedConfidence
                ],
                'recommendation' => $recommendation,
                'calculation_options' => $options
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'combined_score' => [
                    'raw_score' => 0.0,
                    'weighted_score' => 0.0,
                    'confidence' => 0.0
                ]
            ];
        }
    }
    
    /**
     * Update indicator performance metrics
     * 
     * @param string $indicatorName Indicator name
     * @param string $timeframe Timeframe
     * @param array $options Update options
     * @return array Performance update results
     */
    public function updateIndicatorPerformance(string $indicatorName, string $timeframe, array $options = []): array
    {
        try {
            // Set default options
            $options = array_merge([
                'performance_period_days' => 30,
                'calculation_date' => date('Y-m-d')
            ], $options);
            
            // Calculate performance period
            $periodEnd = new DateTime($options['calculation_date']);
            $periodStart = clone $periodEnd;
            $periodStart->modify("-{$options['performance_period_days']} days");
            
            // Get accuracy records for the period
            $accuracyRecords = $this->getAccuracyRecordsForPeriod(
                $indicatorName, 
                $timeframe, 
                $periodStart->format('Y-m-d'), 
                $periodEnd->format('Y-m-d')
            );
            
            if (empty($accuracyRecords)) {
                throw new Exception("No accuracy records found for performance calculation");
            }
            
            // Calculate performance metrics
            $performanceMetrics = $this->calculatePerformanceMetrics($accuracyRecords);
            
            // Prepare performance data
            $performanceData = [
                'indicator_name' => $indicatorName,
                'timeframe' => $timeframe,
                'overall_accuracy' => round($performanceMetrics['overall_accuracy'], 2),
                'total_predictions' => $performanceMetrics['total_predictions'],
                'correct_predictions' => $performanceMetrics['correct_predictions'],
                'avg_confidence' => round($performanceMetrics['avg_confidence'], 2),
                'avg_profit_loss' => $performanceMetrics['avg_profit_loss'],
                'buy_accuracy' => round($performanceMetrics['buy_accuracy'], 2),
                'sell_accuracy' => round($performanceMetrics['sell_accuracy'], 2),
                'hold_accuracy' => round($performanceMetrics['hold_accuracy'], 2),
                'consistency_score' => round($performanceMetrics['consistency_score'], 2),
                'volatility_performance' => round($performanceMetrics['volatility_performance'], 2),
                'trend_performance' => round($performanceMetrics['trend_performance'], 2),
                'performance_period_start' => $periodStart->format('Y-m-d'),
                'performance_period_end' => $periodEnd->format('Y-m-d'),
                'next_calculation_due' => (new DateTime('+1 day'))->format('Y-m-d H:i:s'),
                'is_active' => true,
                'calculation_status' => 'current'
            ];
            
            // Save performance data
            $performanceId = $this->correlationRepository->saveIndicatorPerformance($performanceData);
            
            return [
                'success' => true,
                'performance_id' => $performanceId,
                'indicator_name' => $indicatorName,
                'timeframe' => $timeframe,
                'performance_metrics' => $performanceMetrics,
                'performance_period' => [
                    'start' => $periodStart->format('Y-m-d'),
                    'end' => $periodEnd->format('Y-m-d')
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'performance_metrics' => []
            ];
        }
    }
    
    /**
     * Get correlation strength classification
     * 
     * @param float $correlationCoefficient Correlation coefficient
     * @return string Strength classification
     */
    public function getCorrelationStrength(float $correlationCoefficient): string
    {
        $absCoeff = abs($correlationCoefficient);
        
        if ($absCoeff >= 0.8) return 'very_strong';
        if ($absCoeff >= 0.6) return 'strong';
        if ($absCoeff >= 0.4) return 'moderate';
        if ($absCoeff >= 0.2) return 'weak';
        return 'very_weak';
    }
    
    /**
     * Get recommendation based on weighted scores
     * 
     * @param array $weightedScores Weighted scores data
     * @return array Recommendation with confidence
     */
    public function getRecommendation(array $weightedScores): array
    {
        $score = $weightedScores['combined_weighted_score'];
        $confidence = $weightedScores['combined_confidence'];
        
        // Determine recommendation based on score
        if ($score >= 0.7) {
            $recommendation = $score >= 0.85 ? 'strong_buy' : 'buy';
        } elseif ($score <= -0.7) {
            $recommendation = $score <= -0.85 ? 'strong_sell' : 'sell';
        } else {
            $recommendation = 'hold';
        }
        
        // Adjust confidence based on agreement between factors and technical analysis
        $factorTechnicalAgreement = 1 - abs($weightedScores['market_factors_score'] - $weightedScores['technical_analysis_score']);
        $adjustedConfidence = $confidence * $factorTechnicalAgreement;
        
        return [
            'recommendation' => $recommendation,
            'confidence' => round($adjustedConfidence, 2),
            'score' => round($score, 4),
            'factor_technical_agreement' => round($factorTechnicalAgreement, 4),
            'reasoning' => $this->generateRecommendationReasoning($recommendation, $score, $confidence, $weightedScores)
        ];
    }
    
    // Protected helper methods
    
    /**
     * Get factor data for correlation calculation
     * 
     * @param string $factorType Factor type
     * @param int $factorId Factor ID
     * @param array $options Calculation options
     * @return array Factor data
     */
    protected function getFactorData(string $factorType, int $factorId, array $options): array
    {
        // This would typically fetch from the market factors database
        // For now, return simulated data
        $data = [];
        $startDate = new DateTime("-{$options['period_days']} days");
        
        for ($i = 0; $i < $options['period_days']; $i++) {
            $date = clone $startDate;
            $date->modify("+{$i} days");
            
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'value' => sin($i * 0.1) + rand(-100, 100) / 1000
            ];
        }
        
        return $data;
    }
    
    /**
     * Get stock data for correlation calculation
     * 
     * @param string $stockSymbol Stock symbol
     * @param array $options Calculation options
     * @return array Stock data
     */
    protected function getStockData(string $stockSymbol, array $options): array
    {
        // This would typically fetch from a stock data API or database
        // For now, return simulated data
        $data = [];
        $startDate = new DateTime("-{$options['period_days']} days");
        $basePrice = 100;
        
        for ($i = 0; $i < $options['period_days']; $i++) {
            $date = clone $startDate;
            $date->modify("+{$i} days");
            
            $change = (rand(-500, 500) / 10000) * $basePrice;
            $basePrice += $change;
            
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'close_price' => $basePrice,
                'price_change' => $change,
                'price_change_percent' => ($change / ($basePrice - $change)) * 100
            ];
        }
        
        return $data;
    }
    
    /**
     * Align factor and stock data by date
     * 
     * @param array $factorData Factor data
     * @param array $stockData Stock data
     * @return array Aligned data
     */
    protected function alignDataByDate(array $factorData, array $stockData): array
    {
        $alignedData = [];
        $factorByDate = [];
        $stockByDate = [];
        
        // Index data by date
        foreach ($factorData as $item) {
            $factorByDate[$item['date']] = $item;
        }
        
        foreach ($stockData as $item) {
            $stockByDate[$item['date']] = $item;
        }
        
        // Find common dates and align data
        foreach ($factorByDate as $date => $factorItem) {
            if (isset($stockByDate[$date])) {
                $alignedData[] = [
                    'date' => $date,
                    'factor_value' => $factorItem['value'],
                    'stock_price_change' => $stockByDate[$date]['price_change_percent'] ?? 0
                ];
            }
        }
        
        return $alignedData;
    }
    
    /**
     * Calculate Pearson correlation coefficient
     * 
     * @param array $alignedData Aligned data points
     * @return float Correlation coefficient
     */
    protected function calculatePearsonCorrelation(array $alignedData): float
    {
        $n = count($alignedData);
        if ($n < 2) return 0.0;
        
        $x = array_column($alignedData, 'factor_value');
        $y = array_column($alignedData, 'stock_price_change');
        
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }
        
        $numerator = ($n * $sumXY) - ($sumX * $sumY);
        $denominator = sqrt((($n * $sumX2) - ($sumX * $sumX)) * (($n * $sumY2) - ($sumY * $sumY)));
        
        return $denominator != 0 ? $numerator / $denominator : 0.0;
    }
    
    /**
     * Calculate statistical significance of correlation
     * 
     * @param float $correlation Correlation coefficient
     * @param int $sampleSize Sample size
     * @return array Significance data
     */
    protected function calculateStatisticalSignificance(float $correlation, int $sampleSize): array
    {
        if ($sampleSize < 3) {
            return [
                'confidence_level' => 0.0,
                'p_value' => 1.0,
                'is_significant' => false
            ];
        }
        
        // Calculate t-statistic
        $degreesOfFreedom = $sampleSize - 2;
        $tStatistic = $correlation * sqrt($degreesOfFreedom / (1 - $correlation * $correlation));
        
        // Approximate p-value (simplified calculation)
        $pValue = 2 * (1 - $this->approximateTDistribution(abs($tStatistic), $degreesOfFreedom));
        $confidenceLevel = (1 - $pValue) * 100;
        
        return [
            'confidence_level' => min(99.99, max(0.0, $confidenceLevel)),
            'p_value' => max(0.0001, min(1.0, $pValue)),
            'is_significant' => $pValue < 0.05
        ];
    }
    
    /**
     * Approximate t-distribution CDF (simplified)
     * 
     * @param float $t T-statistic
     * @param int $df Degrees of freedom
     * @return float Approximate CDF value
     */
    protected function approximateTDistribution(float $t, int $df): float
    {
        // Very simplified approximation - in practice, use proper statistical library
        if ($df > 30) {
            // Approximate as normal distribution for large df
            return 0.5 * (1 + $this->approximateErf($t / sqrt(2)));
        }
        
        // Rough approximation for small df
        $x = $t / sqrt($df);
        return 0.5 + 0.5 * $x / (1 + abs($x));
    }
    
    /**
     * Approximate error function (erf)
     * 
     * @param float $x Input value
     * @return float Approximate erf value
     */
    protected function approximateErf(float $x): float
    {
        // Abramowitz and Stegun approximation
        $a1 =  0.254829592;
        $a2 = -0.284496736;
        $a3 =  1.421413741;
        $a4 = -1.453152027;
        $a5 =  1.061405429;
        $p  =  0.3275911;
        
        $sign = $x >= 0 ? 1 : -1;
        $x = abs($x);
        
        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - (((($a5 * $t + $a4) * $t + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);
        
        return $sign * $y;
    }
    
    /**
     * Assess data quality for correlation calculation
     * 
     * @param array $alignedData Aligned data points
     * @return array Data quality metrics
     */
    protected function assessDataQuality(array $alignedData): array
    {
        $factorValues = array_column($alignedData, 'factor_value');
        $stockValues = array_column($alignedData, 'stock_price_change');
        
        return [
            'sample_size' => count($alignedData),
            'factor_variance' => $this->calculateVariance($factorValues),
            'stock_variance' => $this->calculateVariance($stockValues),
            'data_completeness' => 100.0, // Since we only include complete data
            'quality_score' => min(100, max(0, count($alignedData) * 3)) // Simple quality score
        ];
    }
    
    /**
     * Calculate variance of data series
     * 
     * @param array $values Data values
     * @return float Variance
     */
    protected function calculateVariance(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0.0;
        
        $mean = array_sum($values) / $n;
        $sumSquares = 0;
        
        foreach ($values as $value) {
            $sumSquares += pow($value - $mean, 2);
        }
        
        return $sumSquares / ($n - 1);
    }
    
    /**
     * Save correlation history record
     * 
     * @param int $correlationId Correlation ID
     * @param array $correlationData Correlation data
     * @param array $options Calculation options
     */
    protected function saveCorrelationHistory(int $correlationId, array $correlationData, array $options): void
    {
        $periodEnd = new DateTime($correlationData['calculation_date']);
        $periodStart = clone $periodEnd;
        $periodStart->modify("-{$options['period_days']} days");
        
        $historyData = [
            'correlation_id' => $correlationId,
            'correlation_coefficient' => $correlationData['correlation_coefficient'],
            'correlation_strength' => $this->getCorrelationStrength($correlationData['correlation_coefficient']),
            'sample_size' => $correlationData['sample_size'],
            'confidence_level' => $correlationData['confidence_level'],
            'calculation_period_start' => $periodStart->format('Y-m-d'),
            'calculation_period_end' => $periodEnd->format('Y-m-d')
        ];
        
        $this->correlationRepository->saveCorrelationHistory($historyData);
    }
    
    /**
     * Generate bulk correlation summary
     * 
     * @param array $results Correlation results
     * @return array Summary statistics
     */
    protected function generateBulkCorrelationSummary(array $results): array
    {
        $successfulResults = array_filter($results, function($result) {
            return $result['success'];
        });
        
        if (empty($successfulResults)) {
            return [
                'avg_correlation' => 0.0,
                'strongest_correlation' => 0.0,
                'weakest_correlation' => 0.0,
                'significant_correlations' => 0,
                'correlation_distribution' => []
            ];
        }
        
        $correlations = array_column($successfulResults, 'correlation_coefficient');
        $significantCount = 0;
        $distribution = ['very_weak' => 0, 'weak' => 0, 'moderate' => 0, 'strong' => 0, 'very_strong' => 0];
        
        foreach ($successfulResults as $result) {
            if ($result['is_significant']) {
                $significantCount++;
            }
            $distribution[$result['correlation_strength']]++;
        }
        
        return [
            'avg_correlation' => round(array_sum($correlations) / count($correlations), 4),
            'strongest_correlation' => round(max($correlations), 4),
            'weakest_correlation' => round(min($correlations), 4),
            'significant_correlations' => $significantCount,
            'correlation_distribution' => $distribution
        ];
    }
    
    /**
     * Calculate accuracy score based on prediction and outcome
     * 
     * @param array $prediction Prediction data
     * @param array $actualOutcome Actual outcome data
     * @return float Accuracy score (0-100)
     */
    protected function calculateAccuracyScore(array $prediction, array $actualOutcome): float
    {
        // Simplified accuracy calculation - can be enhanced based on specific requirements
        switch ($prediction['prediction_type']) {
            case 'buy':
            case 'sell':
                return $actualOutcome['actual_outcome'] === 'correct' ? 100.0 : 0.0;
                
            case 'trend_up':
            case 'trend_down':
                if ($actualOutcome['actual_outcome'] === 'correct') {
                    return 100.0;
                } elseif ($actualOutcome['actual_outcome'] === 'partial') {
                    return 60.0;
                } else {
                    return 0.0;
                }
                
            default:
                return $actualOutcome['actual_outcome'] === 'correct' ? 100.0 : 0.0;
        }
    }
    
    /**
     * Get historical accuracy for an indicator
     * 
     * @param string $indicatorName Indicator name
     * @param string $stockSymbol Stock symbol
     * @param string $timeframe Timeframe
     * @return array Historical accuracy data
     */
    protected function getHistoricalAccuracy(string $indicatorName, string $stockSymbol, string $timeframe): array
    {
        $accuracyRecords = $this->correlationRepository->getTechnicalAnalysisAccuracy($stockSymbol, $indicatorName, $timeframe);
        
        if (empty($accuracyRecords)) {
            return [
                'total_predictions' => 0,
                'correct_predictions' => 0,
                'running_accuracy' => 0.0
            ];
        }
        
        // Get the most recent record for running totals
        $latestRecord = $accuracyRecords[0];
        
        return [
            'total_predictions' => $latestRecord['total_predictions'] ?? 0,
            'correct_predictions' => $latestRecord['correct_predictions'] ?? 0,
            'running_accuracy' => $latestRecord['running_accuracy'] ?? 0.0
        ];
    }
    
    /**
     * Calculate market factors weighted score
     * 
     * @param string $stockSymbol Stock symbol
     * @param array $marketFactors Market factors data
     * @param array $options Calculation options
     * @return array Market factors score result
     */
    protected function calculateMarketFactorsWeightedScore(string $stockSymbol, array $marketFactors, array $options): array
    {
        $correlations = $this->correlationRepository->getCorrelationsByStock($stockSymbol, true);
        
        $rawScore = 0.0;
        $weightedScore = 0.0;
        $totalWeight = 0.0;
        $confidence = 0.0;
        $factorBreakdown = [];
        
        foreach ($marketFactors as $factor) {
            // Find correlation for this factor
            $correlation = null;
            foreach ($correlations as $corr) {
                if ($corr['factor_type'] === $factor['type'] && $corr['factor_id'] == $factor['id']) {
                    $correlation = $corr;
                    break;
                }
            }
            
            $factorScore = $factor['value'] ?? 0.0;
            $rawScore += $factorScore;
            
            if ($correlation) {
                $correlationWeight = abs($correlation['correlation_coefficient']);
                $confidenceWeight = $correlation['confidence_level'] / 100;
                $weight = $correlationWeight * $confidenceWeight;
                
                $weightedScore += $factorScore * $weight;
                $totalWeight += $weight;
                $confidence += $correlation['confidence_level'];
                
                $factorBreakdown[] = [
                    'factor_type' => $factor['type'],
                    'factor_id' => $factor['id'],
                    'raw_score' => $factorScore,
                    'correlation_coefficient' => $correlation['correlation_coefficient'],
                    'weight' => $weight,
                    'weighted_score' => $factorScore * $weight
                ];
            } else {
                $factorBreakdown[] = [
                    'factor_type' => $factor['type'],
                    'factor_id' => $factor['id'],
                    'raw_score' => $factorScore,
                    'correlation_coefficient' => 0.0,
                    'weight' => 0.0,
                    'weighted_score' => 0.0
                ];
            }
        }
        
        $factorCount = count($marketFactors);
        if ($factorCount > 0) {
            $rawScore /= $factorCount;
            $confidence /= $factorCount;
        }
        
        if ($totalWeight > 0) {
            $weightedScore /= $totalWeight;
        }
        
        return [
            'raw_score' => $rawScore,
            'weighted_score' => $weightedScore,
            'confidence' => $confidence,
            'factor_breakdown' => $factorBreakdown
        ];
    }
    
    /**
     * Calculate technical analysis weighted score
     * 
     * @param string $stockSymbol Stock symbol
     * @param array $technicalIndicators Technical indicators data
     * @param array $options Calculation options
     * @return array Technical analysis score result
     */
    protected function calculateTechnicalAnalysisWeightedScore(string $stockSymbol, array $technicalIndicators, array $options): array
    {
        $performance = $this->correlationRepository->getIndicatorPerformance();
        
        $rawScore = 0.0;
        $weightedScore = 0.0;
        $totalWeight = 0.0;
        $confidence = 0.0;
        $indicatorBreakdown = [];
        
        foreach ($technicalIndicators as $indicator) {
            // Find performance for this indicator
            $indicatorPerformance = null;
            foreach ($performance as $perf) {
                if ($perf['indicator_name'] === $indicator['name'] && $perf['timeframe'] === $indicator['timeframe']) {
                    $indicatorPerformance = $perf;
                    break;
                }
            }
            
            $indicatorScore = $indicator['value'] ?? 0.0;
            $rawScore += $indicatorScore;
            
            if ($indicatorPerformance) {
                $accuracyWeight = $indicatorPerformance['overall_accuracy'] / 100;
                $consistencyWeight = $indicatorPerformance['consistency_score'] / 100;
                $weight = ($accuracyWeight + $consistencyWeight) / 2;
                
                $weightedScore += $indicatorScore * $weight;
                $totalWeight += $weight;
                $confidence += $indicatorPerformance['overall_accuracy'];
                
                $indicatorBreakdown[] = [
                    'indicator_name' => $indicator['name'],
                    'timeframe' => $indicator['timeframe'],
                    'raw_score' => $indicatorScore,
                    'accuracy' => $indicatorPerformance['overall_accuracy'],
                    'consistency' => $indicatorPerformance['consistency_score'],
                    'weight' => $weight,
                    'weighted_score' => $indicatorScore * $weight
                ];
            } else {
                $indicatorBreakdown[] = [
                    'indicator_name' => $indicator['name'],
                    'timeframe' => $indicator['timeframe'],
                    'raw_score' => $indicatorScore,
                    'accuracy' => 0.0,
                    'consistency' => 0.0,
                    'weight' => 0.0,
                    'weighted_score' => 0.0
                ];
            }
        }
        
        $indicatorCount = count($technicalIndicators);
        if ($indicatorCount > 0) {
            $rawScore /= $indicatorCount;
            $confidence /= $indicatorCount;
        }
        
        if ($totalWeight > 0) {
            $weightedScore /= $totalWeight;
        }
        
        return [
            'raw_score' => $rawScore,
            'weighted_score' => $weightedScore,
            'confidence' => $confidence,
            'indicator_breakdown' => $indicatorBreakdown
        ];
    }
    
    /**
     * Get accuracy records for a specific period
     * 
     * @param string $indicatorName Indicator name
     * @param string $timeframe Timeframe
     * @param string $periodStart Period start date
     * @param string $periodEnd Period end date
     * @return array Accuracy records
     */
    protected function getAccuracyRecordsForPeriod(string $indicatorName, string $timeframe, string $periodStart, string $periodEnd): array
    {
        // This would typically use a more sophisticated query to filter by date range
        // For now, return all records for the indicator/timeframe
        return $this->correlationRepository->getTechnicalAnalysisAccuracy('', $indicatorName, $timeframe);
    }
    
    /**
     * Calculate performance metrics from accuracy records
     * 
     * @param array $accuracyRecords Accuracy records
     * @return array Performance metrics
     */
    protected function calculatePerformanceMetrics(array $accuracyRecords): array
    {
        $totalPredictions = count($accuracyRecords);
        $correctPredictions = 0;
        $totalAccuracy = 0.0;
        $totalConfidence = 0.0;
        $profitLossSum = 0.0;
        $profitLossCount = 0;
        
        $buyAccuracy = ['total' => 0, 'correct' => 0];
        $sellAccuracy = ['total' => 0, 'correct' => 0];
        $holdAccuracy = ['total' => 0, 'correct' => 0];
        
        foreach ($accuracyRecords as $record) {
            if ($record['actual_outcome'] === 'correct') {
                $correctPredictions++;
            }
            
            $totalAccuracy += $record['accuracy_score'];
            $totalConfidence += $record['confidence_score'];
            
            if ($record['profit_loss_percentage'] !== null) {
                $profitLossSum += $record['profit_loss_percentage'];
                $profitLossCount++;
            }
            
            // Track by prediction type
            switch ($record['prediction_type']) {
                case 'buy':
                    $buyAccuracy['total']++;
                    if ($record['actual_outcome'] === 'correct') {
                        $buyAccuracy['correct']++;
                    }
                    break;
                case 'sell':
                    $sellAccuracy['total']++;
                    if ($record['actual_outcome'] === 'correct') {
                        $sellAccuracy['correct']++;
                    }
                    break;
                case 'hold':
                    $holdAccuracy['total']++;
                    if ($record['actual_outcome'] === 'correct') {
                        $holdAccuracy['correct']++;
                    }
                    break;
            }
        }
        
        return [
            'overall_accuracy' => $totalPredictions > 0 ? ($correctPredictions / $totalPredictions) * 100 : 0.0,
            'total_predictions' => $totalPredictions,
            'correct_predictions' => $correctPredictions,
            'avg_confidence' => $totalPredictions > 0 ? $totalConfidence / $totalPredictions : 0.0,
            'avg_profit_loss' => $profitLossCount > 0 ? $profitLossSum / $profitLossCount : null,
            'buy_accuracy' => $buyAccuracy['total'] > 0 ? ($buyAccuracy['correct'] / $buyAccuracy['total']) * 100 : 0.0,
            'sell_accuracy' => $sellAccuracy['total'] > 0 ? ($sellAccuracy['correct'] / $sellAccuracy['total']) * 100 : 0.0,
            'hold_accuracy' => $holdAccuracy['total'] > 0 ? ($holdAccuracy['correct'] / $holdAccuracy['total']) * 100 : 0.0,
            'consistency_score' => $this->calculateConsistencyScore($accuracyRecords),
            'volatility_performance' => $this->calculateVolatilityPerformance($accuracyRecords),
            'trend_performance' => $this->calculateTrendPerformance($accuracyRecords)
        ];
    }
    
    /**
     * Calculate consistency score for an indicator
     * 
     * @param array $accuracyRecords Accuracy records
     * @return float Consistency score
     */
    protected function calculateConsistencyScore(array $accuracyRecords): float
    {
        if (count($accuracyRecords) < 2) return 0.0;
        
        $accuracyScores = array_column($accuracyRecords, 'accuracy_score');
        $variance = $this->calculateVariance($accuracyScores);
        
        // Convert variance to consistency score (lower variance = higher consistency)
        return max(0, 100 - $variance);
    }
    
    /**
     * Calculate volatility performance for an indicator
     * 
     * @param array $accuracyRecords Accuracy records
     * @return float Volatility performance score
     */
    protected function calculateVolatilityPerformance(array $accuracyRecords): float
    {
        // Simplified calculation - would typically analyze performance during high volatility periods
        $correctPredictions = array_filter($accuracyRecords, function($record) {
            return $record['actual_outcome'] === 'correct';
        });
        
        return count($accuracyRecords) > 0 ? (count($correctPredictions) / count($accuracyRecords)) * 100 : 0.0;
    }
    
    /**
     * Calculate trend performance for an indicator
     * 
     * @param array $accuracyRecords Accuracy records
     * @return float Trend performance score
     */
    protected function calculateTrendPerformance(array $accuracyRecords): float
    {
        // Simplified calculation - would typically analyze performance during trending periods
        $trendPredictions = array_filter($accuracyRecords, function($record) {
            return in_array($record['prediction_type'], ['trend_up', 'trend_down']);
        });
        
        $correctTrendPredictions = array_filter($trendPredictions, function($record) {
            return $record['actual_outcome'] === 'correct';
        });
        
        return count($trendPredictions) > 0 ? (count($correctTrendPredictions) / count($trendPredictions)) * 100 : 0.0;
    }
    
    /**
     * Generate recommendation reasoning
     * 
     * @param string $recommendation Recommendation
     * @param float $score Combined score
     * @param float $confidence Confidence level
     * @param array $weightedScores All weighted scores
     * @return string Reasoning text
     */
    protected function generateRecommendationReasoning(string $recommendation, float $score, float $confidence, array $weightedScores): string
    {
        $reasoning = "Recommendation: {$recommendation} (Score: " . round($score, 2) . ", Confidence: " . round($confidence, 1) . "%). ";
        
        $factorScore = $weightedScores['market_factors_score'];
        $technicalScore = $weightedScores['technical_analysis_score'];
        
        if (abs($factorScore - $technicalScore) < 0.2) {
            $reasoning .= "Market factors and technical analysis are in strong agreement. ";
        } elseif ($factorScore > $technicalScore) {
            $reasoning .= "Market factors are more bullish than technical indicators. ";
        } else {
            $reasoning .= "Technical indicators are more bullish than market factors. ";
        }
        
        if ($confidence > 80) {
            $reasoning .= "High confidence recommendation based on strong correlation data.";
        } elseif ($confidence > 60) {
            $reasoning .= "Moderate confidence recommendation.";
        } else {
            $reasoning .= "Low confidence recommendation - exercise caution.";
        }
        
        return $reasoning;
    }
}
?>
