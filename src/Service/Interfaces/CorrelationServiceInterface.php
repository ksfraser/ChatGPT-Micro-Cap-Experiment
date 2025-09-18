<?php
/**
 * Correlation Service Interface
 * Defines contract for correlation calculation and management
 * 
 * @package Service\Interfaces
 */

namespace Service\Interfaces;

interface CorrelationServiceInterface
{
    /**
     * Calculate correlation between a market factor and stock price movements
     * 
     * @param string $factorType Type of market factor
     * @param int $factorId Factor ID
     * @param string $stockSymbol Stock symbol
     * @param array $options Calculation options (timeframe, period, etc.)
     * @return array Correlation results
     */
    public function calculateFactorStockCorrelation(string $factorType, int $factorId, string $stockSymbol, array $options = []): array;
    
    /**
     * Calculate correlation for multiple stocks against a factor
     * 
     * @param string $factorType Type of market factor
     * @param int $factorId Factor ID
     * @param array $stockSymbols Array of stock symbols
     * @param array $options Calculation options
     * @return array Correlation results for all stocks
     */
    public function calculateBulkFactorStockCorrelations(string $factorType, int $factorId, array $stockSymbols, array $options = []): array;
    
    /**
     * Track technical analysis indicator accuracy
     * 
     * @param string $indicatorName Indicator name
     * @param string $stockSymbol Stock symbol
     * @param array $prediction Prediction data
     * @param array $actualOutcome Actual outcome data
     * @return array Accuracy tracking results
     */
    public function trackIndicatorAccuracy(string $indicatorName, string $stockSymbol, array $prediction, array $actualOutcome): array;
    
    /**
     * Calculate weighted score for a stock based on correlations
     * 
     * @param string $stockSymbol Stock symbol
     * @param array $marketFactors Market factors data
     * @param array $technicalIndicators Technical indicators data
     * @param array $options Calculation options
     * @return array Weighted score results
     */
    public function calculateWeightedScore(string $stockSymbol, array $marketFactors, array $technicalIndicators, array $options = []): array;
    
    /**
     * Update indicator performance metrics
     * 
     * @param string $indicatorName Indicator name
     * @param string $timeframe Timeframe
     * @param array $options Update options
     * @return array Performance update results
     */
    public function updateIndicatorPerformance(string $indicatorName, string $timeframe, array $options = []): array;
    
    /**
     * Get correlation strength classification
     * 
     * @param float $correlationCoefficient Correlation coefficient
     * @return string Strength classification
     */
    public function getCorrelationStrength(float $correlationCoefficient): string;
    
    /**
     * Get recommendation based on weighted scores
     * 
     * @param array $weightedScores Weighted scores data
     * @return array Recommendation with confidence
     */
    public function getRecommendation(array $weightedScores): array;
}
?>
