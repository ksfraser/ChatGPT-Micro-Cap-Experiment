<?php
/**
 * Repository Interface for Correlation Data Access
 * Defines contract for correlation data operations following SOLID principles
 * 
 * @package Repository\Interfaces
 */

namespace Repository\Interfaces;

interface CorrelationRepositoryInterface
{
    /**
     * Get factor-stock correlation by ID
     * 
     * @param int $id Correlation ID
     * @return array|null Correlation data
     */
    public function getFactorStockCorrelationById(int $id): ?array;
    
    /**
     * Get correlations for a specific stock
     * 
     * @param string $stockSymbol Stock symbol
     * @param bool $activeOnly Return only active correlations
     * @return array List of correlations
     */
    public function getCorrelationsByStock(string $stockSymbol, bool $activeOnly = true): array;
    
    /**
     * Get correlations for a specific factor
     * 
     * @param string $factorType Factor type
     * @param int $factorId Factor ID
     * @param bool $activeOnly Return only active correlations
     * @return array List of correlations
     */
    public function getCorrelationsByFactor(string $factorType, int $factorId, bool $activeOnly = true): array;
    
    /**
     * Create or update factor-stock correlation
     * 
     * @param array $correlationData Correlation data
     * @return int Correlation ID
     */
    public function saveFactorStockCorrelation(array $correlationData): int;
    
    /**
     * Get technical analysis accuracy records
     * 
     * @param string $stockSymbol Stock symbol
     * @param string|null $indicatorName Specific indicator or null for all
     * @param string|null $timeframe Specific timeframe or null for all
     * @return array Accuracy records
     */
    public function getTechnicalAnalysisAccuracy(string $stockSymbol, ?string $indicatorName = null, ?string $timeframe = null): array;
    
    /**
     * Save technical analysis accuracy record
     * 
     * @param array $accuracyData Accuracy data
     * @return int Record ID
     */
    public function saveTechnicalAnalysisAccuracy(array $accuracyData): int;
    
    /**
     * Get indicator performance metrics
     * 
     * @param string|null $indicatorName Specific indicator or null for all
     * @param string|null $timeframe Specific timeframe or null for all
     * @return array Performance metrics
     */
    public function getIndicatorPerformance(?string $indicatorName = null, ?string $timeframe = null): array;
    
    /**
     * Save indicator performance metrics
     * 
     * @param array $performanceData Performance data
     * @return int Record ID
     */
    public function saveIndicatorPerformance(array $performanceData): int;
    
    /**
     * Get weighted scores for stock
     * 
     * @param string $stockSymbol Stock symbol
     * @param bool $currentOnly Return only current scores
     * @return array Weighted scores
     */
    public function getWeightedScores(string $stockSymbol, bool $currentOnly = true): array;
    
    /**
     * Save weighted scores
     * 
     * @param array $scoreData Score data
     * @return int Record ID
     */
    public function saveWeightedScores(array $scoreData): int;
    
    /**
     * Get correlation history for a specific correlation
     * 
     * @param int $correlationId Correlation ID
     * @param int $limit Number of records to return
     * @return array Historical correlation data
     */
    public function getCorrelationHistory(int $correlationId, int $limit = 50): array;
    
    /**
     * Save correlation history record
     * 
     * @param array $historyData History data
     * @return int Record ID
     */
    public function saveCorrelationHistory(array $historyData): int;
}
?>
