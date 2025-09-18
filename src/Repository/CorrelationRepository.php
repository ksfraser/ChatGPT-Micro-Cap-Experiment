<?php
/**
 * Correlation Repository Implementation
 * Handles all correlation data access using EnhancedDbManager
 * Follows SOLID/DRY/DI principles with proper dependency injection
 * 
 * @package Repository
 */

namespace Repository;

use Repository\Interfaces\CorrelationRepositoryInterface;
use Ksfraser\Database\EnhancedDbManager;
use Ksfraser\Database\DatabaseConnectionInterface;
use Exception;
use DateTime;

class CorrelationRepository implements CorrelationRepositoryInterface
{
    /** @var DatabaseConnectionInterface Database connection */
    protected $connection;
    
    /**
     * Constructor with dependency injection
     * 
     * @param DatabaseConnectionInterface|null $connection Optional connection injection
     */
    public function __construct(?DatabaseConnectionInterface $connection = null)
    {
        $this->connection = $connection ?? EnhancedDbManager::getConnection();
    }
    
    /**
     * Get factor-stock correlation by ID
     * 
     * @param int $id Correlation ID
     * @return array|null Correlation data
     */
    public function getFactorStockCorrelationById(int $id): ?array
    {
        $sql = "
            SELECT 
                id,
                factor_type,
                factor_id,
                stock_symbol,
                correlation_coefficient,
                correlation_strength,
                sample_size,
                confidence_level,
                calculation_date,
                last_updated,
                is_active
            FROM factor_stock_correlations 
            WHERE id = ?
        ";
        
        return EnhancedDbManager::fetchOne($sql, [$id]);
    }
    
    /**
     * Get correlations for a specific stock
     * 
     * @param string $stockSymbol Stock symbol
     * @param bool $activeOnly Return only active correlations
     * @return array List of correlations
     */
    public function getCorrelationsByStock(string $stockSymbol, bool $activeOnly = true): array
    {
        $sql = "
            SELECT 
                id,
                factor_type,
                factor_id,
                stock_symbol,
                correlation_coefficient,
                correlation_strength,
                sample_size,
                confidence_level,
                calculation_date,
                last_updated,
                is_active
            FROM factor_stock_correlations 
            WHERE stock_symbol = ?
        ";
        
        $params = [$stockSymbol];
        
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY correlation_strength DESC, ABS(correlation_coefficient) DESC";
        
        return EnhancedDbManager::fetchAll($sql, $params);
    }
    
    /**
     * Get correlations for a specific factor
     * 
     * @param string $factorType Factor type
     * @param int $factorId Factor ID
     * @param bool $activeOnly Return only active correlations
     * @return array List of correlations
     */
    public function getCorrelationsByFactor(string $factorType, int $factorId, bool $activeOnly = true): array
    {
        $sql = "
            SELECT 
                id,
                factor_type,
                factor_id,
                stock_symbol,
                correlation_coefficient,
                correlation_strength,
                sample_size,
                confidence_level,
                calculation_date,
                last_updated,
                is_active
            FROM factor_stock_correlations 
            WHERE factor_type = ? AND factor_id = ?
        ";
        
        $params = [$factorType, $factorId];
        
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY ABS(correlation_coefficient) DESC";
        
        return EnhancedDbManager::fetchAll($sql, $params);
    }
    
    /**
     * Create or update factor-stock correlation
     * 
     * @param array $correlationData Correlation data
     * @return int Correlation ID
     */
    public function saveFactorStockCorrelation(array $correlationData): int
    {
        // Validate required fields
        $required = ['factor_type', 'factor_id', 'stock_symbol', 'correlation_coefficient'];
        foreach ($required as $field) {
            if (!isset($correlationData[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Calculate correlation strength based on coefficient
        $correlationData['correlation_strength'] = $this->calculateCorrelationStrength(
            $correlationData['correlation_coefficient']
        );
        
        // Set defaults
        $correlationData = array_merge([
            'sample_size' => 0,
            'confidence_level' => 0.00,
            'calculation_date' => (new DateTime())->format('Y-m-d H:i:s'),
            'is_active' => true
        ], $correlationData);
        
        // Check if correlation already exists
        $existingId = $this->findExistingCorrelation(
            $correlationData['factor_type'],
            $correlationData['factor_id'],
            $correlationData['stock_symbol']
        );
        
        if ($existingId) {
            return $this->updateFactorStockCorrelation($existingId, $correlationData);
        } else {
            return $this->insertFactorStockCorrelation($correlationData);
        }
    }
    
    /**
     * Insert new factor-stock correlation
     * 
     * @param array $data Correlation data
     * @return int Correlation ID
     */
    protected function insertFactorStockCorrelation(array $data): int
    {
        $sql = "
            INSERT INTO factor_stock_correlations (
                factor_type, factor_id, stock_symbol, correlation_coefficient,
                correlation_strength, sample_size, confidence_level,
                calculation_date, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $params = [
            $data['factor_type'],
            $data['factor_id'],
            $data['stock_symbol'],
            $data['correlation_coefficient'],
            $data['correlation_strength'],
            $data['sample_size'],
            $data['confidence_level'],
            $data['calculation_date'],
            $data['is_active'] ? 1 : 0
        ];
        
        EnhancedDbManager::execute($sql, $params);
        return (int) EnhancedDbManager::lastInsertId();
    }
    
    /**
     * Update existing factor-stock correlation
     * 
     * @param int $id Correlation ID
     * @param array $data Correlation data
     * @return int Correlation ID
     */
    protected function updateFactorStockCorrelation(int $id, array $data): int
    {
        $sql = "
            UPDATE factor_stock_correlations SET
                correlation_coefficient = ?,
                correlation_strength = ?,
                sample_size = ?,
                confidence_level = ?,
                calculation_date = ?,
                is_active = ?,
                last_updated = CURRENT_TIMESTAMP
            WHERE id = ?
        ";
        
        $params = [
            $data['correlation_coefficient'],
            $data['correlation_strength'],
            $data['sample_size'],
            $data['confidence_level'],
            $data['calculation_date'],
            $data['is_active'] ? 1 : 0,
            $id
        ];
        
        EnhancedDbManager::execute($sql, $params);
        return $id;
    }
    
    /**
     * Find existing correlation
     * 
     * @param string $factorType Factor type
     * @param int $factorId Factor ID
     * @param string $stockSymbol Stock symbol
     * @return int|null Existing correlation ID
     */
    protected function findExistingCorrelation(string $factorType, int $factorId, string $stockSymbol): ?int
    {
        $sql = "
            SELECT id FROM factor_stock_correlations 
            WHERE factor_type = ? AND factor_id = ? AND stock_symbol = ?
        ";
        
        $result = EnhancedDbManager::fetchValue($sql, [$factorType, $factorId, $stockSymbol]);
        return $result ? (int) $result : null;
    }
    
    /**
     * Calculate correlation strength from coefficient
     * 
     * @param float $coefficient Correlation coefficient
     * @return string Correlation strength
     */
    protected function calculateCorrelationStrength(float $coefficient): string
    {
        $absCoeff = abs($coefficient);
        
        if ($absCoeff >= 0.8) return 'very_strong';
        if ($absCoeff >= 0.6) return 'strong';
        if ($absCoeff >= 0.4) return 'moderate';
        if ($absCoeff >= 0.2) return 'weak';
        return 'very_weak';
    }
    
    /**
     * Get technical analysis accuracy records
     * 
     * @param string $stockSymbol Stock symbol
     * @param string|null $indicatorName Specific indicator or null for all
     * @param string|null $timeframe Specific timeframe or null for all
     * @return array Accuracy records
     */
    public function getTechnicalAnalysisAccuracy(string $stockSymbol, ?string $indicatorName = null, ?string $timeframe = null): array
    {
        $sql = "
            SELECT 
                id,
                indicator_name,
                indicator_parameters,
                stock_symbol,
                timeframe,
                prediction_type,
                predicted_date,
                actual_outcome,
                outcome_date,
                accuracy_score,
                confidence_score,
                profit_loss_percentage,
                total_predictions,
                correct_predictions,
                running_accuracy,
                created_at,
                updated_at
            FROM technical_analysis_accuracy 
            WHERE stock_symbol = ?
        ";
        
        $params = [$stockSymbol];
        
        if ($indicatorName) {
            $sql .= " AND indicator_name = ?";
            $params[] = $indicatorName;
        }
        
        if ($timeframe) {
            $sql .= " AND timeframe = ?";
            $params[] = $timeframe;
        }
        
        $sql .= " ORDER BY predicted_date DESC";
        
        return EnhancedDbManager::fetchAll($sql, $params);
    }
    
    /**
     * Save technical analysis accuracy record
     * 
     * @param array $accuracyData Accuracy data
     * @return int Record ID
     */
    public function saveTechnicalAnalysisAccuracy(array $accuracyData): int
    {
        // Validate required fields
        $required = ['indicator_name', 'stock_symbol', 'prediction_type', 'predicted_date'];
        foreach ($required as $field) {
            if (!isset($accuracyData[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Set defaults
        $accuracyData = array_merge([
            'timeframe' => '1d',
            'actual_outcome' => 'pending',
            'accuracy_score' => 0.00,
            'confidence_score' => 0.00,
            'total_predictions' => 1,
            'correct_predictions' => 0,
            'running_accuracy' => 0.00
        ], $accuracyData);
        
        // Calculate running accuracy if we have the data
        if ($accuracyData['total_predictions'] > 0) {
            $accuracyData['running_accuracy'] = 
                ($accuracyData['correct_predictions'] / $accuracyData['total_predictions']) * 100;
        }
        
        $sql = "
            INSERT INTO technical_analysis_accuracy (
                indicator_name, indicator_parameters, stock_symbol, timeframe,
                prediction_type, predicted_date, actual_outcome, outcome_date,
                accuracy_score, confidence_score, profit_loss_percentage,
                total_predictions, correct_predictions, running_accuracy
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $params = [
            $accuracyData['indicator_name'],
            $accuracyData['indicator_parameters'] ?? null,
            $accuracyData['stock_symbol'],
            $accuracyData['timeframe'],
            $accuracyData['prediction_type'],
            $accuracyData['predicted_date'],
            $accuracyData['actual_outcome'],
            $accuracyData['outcome_date'] ?? null,
            $accuracyData['accuracy_score'],
            $accuracyData['confidence_score'],
            $accuracyData['profit_loss_percentage'] ?? null,
            $accuracyData['total_predictions'],
            $accuracyData['correct_predictions'],
            $accuracyData['running_accuracy']
        ];
        
        EnhancedDbManager::execute($sql, $params);
        return (int) EnhancedDbManager::lastInsertId();
    }
    
    /**
     * Get indicator performance metrics
     * 
     * @param string|null $indicatorName Specific indicator or null for all
     * @param string|null $timeframe Specific timeframe or null for all
     * @return array Performance metrics
     */
    public function getIndicatorPerformance(?string $indicatorName = null, ?string $timeframe = null): array
    {
        $sql = "
            SELECT 
                id,
                indicator_name,
                timeframe,
                overall_accuracy,
                total_predictions,
                correct_predictions,
                avg_confidence,
                avg_profit_loss,
                buy_accuracy,
                sell_accuracy,
                hold_accuracy,
                consistency_score,
                volatility_performance,
                trend_performance,
                performance_period_start,
                performance_period_end,
                last_calculated,
                next_calculation_due,
                is_active,
                calculation_status
            FROM indicator_performance 
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($indicatorName) {
            $sql .= " AND indicator_name = ?";
            $params[] = $indicatorName;
        }
        
        if ($timeframe) {
            $sql .= " AND timeframe = ?";
            $params[] = $timeframe;
        }
        
        $sql .= " ORDER BY overall_accuracy DESC";
        
        return EnhancedDbManager::fetchAll($sql, $params);
    }
    
    /**
     * Save indicator performance metrics
     * 
     * @param array $performanceData Performance data
     * @return int Record ID
     */
    public function saveIndicatorPerformance(array $performanceData): int
    {
        // Validate required fields
        $required = ['indicator_name', 'performance_period_start', 'performance_period_end'];
        foreach ($required as $field) {
            if (!isset($performanceData[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Set defaults
        $performanceData = array_merge([
            'timeframe' => '1d',
            'overall_accuracy' => 0.00,
            'total_predictions' => 0,
            'correct_predictions' => 0,
            'avg_confidence' => 0.00,
            'buy_accuracy' => 0.00,
            'sell_accuracy' => 0.00,
            'hold_accuracy' => 0.00,
            'consistency_score' => 0.00,
            'volatility_performance' => 0.00,
            'trend_performance' => 0.00,
            'next_calculation_due' => (new DateTime('+1 day'))->format('Y-m-d H:i:s'),
            'is_active' => true,
            'calculation_status' => 'current'
        ], $performanceData);
        
        // Check if performance record already exists for this indicator/timeframe/period
        $existingId = $this->findExistingPerformance(
            $performanceData['indicator_name'],
            $performanceData['timeframe'],
            $performanceData['performance_period_start'],
            $performanceData['performance_period_end']
        );
        
        if ($existingId) {
            return $this->updateIndicatorPerformance($existingId, $performanceData);
        } else {
            return $this->insertIndicatorPerformance($performanceData);
        }
    }
    
    /**
     * Insert new indicator performance record
     * 
     * @param array $data Performance data
     * @return int Record ID
     */
    protected function insertIndicatorPerformance(array $data): int
    {
        $sql = "
            INSERT INTO indicator_performance (
                indicator_name, timeframe, overall_accuracy, total_predictions,
                correct_predictions, avg_confidence, avg_profit_loss,
                buy_accuracy, sell_accuracy, hold_accuracy,
                consistency_score, volatility_performance, trend_performance,
                performance_period_start, performance_period_end,
                next_calculation_due, is_active, calculation_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $params = [
            $data['indicator_name'],
            $data['timeframe'],
            $data['overall_accuracy'],
            $data['total_predictions'],
            $data['correct_predictions'],
            $data['avg_confidence'],
            $data['avg_profit_loss'],
            $data['buy_accuracy'],
            $data['sell_accuracy'],
            $data['hold_accuracy'],
            $data['consistency_score'],
            $data['volatility_performance'],
            $data['trend_performance'],
            $data['performance_period_start'],
            $data['performance_period_end'],
            $data['next_calculation_due'],
            $data['is_active'] ? 1 : 0,
            $data['calculation_status']
        ];
        
        EnhancedDbManager::execute($sql, $params);
        return (int) EnhancedDbManager::lastInsertId();
    }
    
    /**
     * Update existing indicator performance record
     * 
     * @param int $id Record ID
     * @param array $data Performance data
     * @return int Record ID
     */
    protected function updateIndicatorPerformance(int $id, array $data): int
    {
        $sql = "
            UPDATE indicator_performance SET
                overall_accuracy = ?,
                total_predictions = ?,
                correct_predictions = ?,
                avg_confidence = ?,
                avg_profit_loss = ?,
                buy_accuracy = ?,
                sell_accuracy = ?,
                hold_accuracy = ?,
                consistency_score = ?,
                volatility_performance = ?,
                trend_performance = ?,
                last_calculated = CURRENT_TIMESTAMP,
                next_calculation_due = ?,
                is_active = ?,
                calculation_status = ?
            WHERE id = ?
        ";
        
        $params = [
            $data['overall_accuracy'],
            $data['total_predictions'],
            $data['correct_predictions'],
            $data['avg_confidence'],
            $data['avg_profit_loss'],
            $data['buy_accuracy'],
            $data['sell_accuracy'],
            $data['hold_accuracy'],
            $data['consistency_score'],
            $data['volatility_performance'],
            $data['trend_performance'],
            $data['next_calculation_due'],
            $data['is_active'] ? 1 : 0,
            $data['calculation_status'],
            $id
        ];
        
        EnhancedDbManager::execute($sql, $params);
        return $id;
    }
    
    /**
     * Find existing performance record
     * 
     * @param string $indicatorName Indicator name
     * @param string $timeframe Timeframe
     * @param string $periodStart Period start
     * @param string $periodEnd Period end
     * @return int|null Existing record ID
     */
    protected function findExistingPerformance(string $indicatorName, string $timeframe, string $periodStart, string $periodEnd): ?int
    {
        $sql = "
            SELECT id FROM indicator_performance 
            WHERE indicator_name = ? AND timeframe = ? 
            AND performance_period_start = ? AND performance_period_end = ?
        ";
        
        $result = EnhancedDbManager::fetchValue($sql, [$indicatorName, $timeframe, $periodStart, $periodEnd]);
        return $result ? (int) $result : null;
    }
    
    /**
     * Get weighted scores for stock
     * 
     * @param string $stockSymbol Stock symbol
     * @param bool $currentOnly Return only current scores
     * @return array Weighted scores
     */
    public function getWeightedScores(string $stockSymbol, bool $currentOnly = true): array
    {
        $sql = "
            SELECT 
                id,
                stock_symbol,
                calculation_date,
                calculation_timestamp,
                market_factors_raw_score,
                market_factors_weighted_score,
                market_factors_confidence,
                technical_analysis_raw_score,
                technical_analysis_weighted_score,
                technical_analysis_confidence,
                combined_raw_score,
                combined_weighted_score,
                combined_confidence,
                recommendation,
                recommendation_confidence,
                factor_scores,
                indicator_scores,
                calculation_method,
                is_current,
                notes
            FROM weighted_scores 
            WHERE stock_symbol = ?
        ";
        
        $params = [$stockSymbol];
        
        if ($currentOnly) {
            $sql .= " AND is_current = 1";
        }
        
        $sql .= " ORDER BY calculation_date DESC, calculation_timestamp DESC";
        
        return EnhancedDbManager::fetchAll($sql, $params);
    }
    
    /**
     * Save weighted scores
     * 
     * @param array $scoreData Score data
     * @return int Record ID
     */
    public function saveWeightedScores(array $scoreData): int
    {
        // Validate required fields
        $required = ['stock_symbol', 'calculation_date'];
        foreach ($required as $field) {
            if (!isset($scoreData[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Set defaults
        $scoreData = array_merge([
            'market_factors_raw_score' => 0.0000,
            'market_factors_weighted_score' => 0.0000,
            'market_factors_confidence' => 0.00,
            'technical_analysis_raw_score' => 0.0000,
            'technical_analysis_weighted_score' => 0.0000,
            'technical_analysis_confidence' => 0.00,
            'combined_raw_score' => 0.0000,
            'combined_weighted_score' => 0.0000,
            'combined_confidence' => 0.00,
            'recommendation' => 'hold',
            'recommendation_confidence' => 0.00,
            'calculation_method' => 'correlation_weighted',
            'is_current' => true
        ], $scoreData);
        
        // Calculate combined scores
        $scoreData['combined_raw_score'] = 
            ($scoreData['market_factors_raw_score'] + $scoreData['technical_analysis_raw_score']) / 2;
        $scoreData['combined_weighted_score'] = 
            ($scoreData['market_factors_weighted_score'] + $scoreData['technical_analysis_weighted_score']) / 2;
        $scoreData['combined_confidence'] = 
            ($scoreData['market_factors_confidence'] + $scoreData['technical_analysis_confidence']) / 2;
        
        // If this is current, mark others as not current
        if ($scoreData['is_current']) {
            $this->markOtherScoresAsNotCurrent($scoreData['stock_symbol'], $scoreData['calculation_date']);
        }
        
        $sql = "
            INSERT INTO weighted_scores (
                stock_symbol, calculation_date, market_factors_raw_score,
                market_factors_weighted_score, market_factors_confidence,
                technical_analysis_raw_score, technical_analysis_weighted_score,
                technical_analysis_confidence, combined_raw_score,
                combined_weighted_score, combined_confidence, recommendation,
                recommendation_confidence, factor_scores, indicator_scores,
                calculation_method, is_current, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $params = [
            $scoreData['stock_symbol'],
            $scoreData['calculation_date'],
            $scoreData['market_factors_raw_score'],
            $scoreData['market_factors_weighted_score'],
            $scoreData['market_factors_confidence'],
            $scoreData['technical_analysis_raw_score'],
            $scoreData['technical_analysis_weighted_score'],
            $scoreData['technical_analysis_confidence'],
            $scoreData['combined_raw_score'],
            $scoreData['combined_weighted_score'],
            $scoreData['combined_confidence'],
            $scoreData['recommendation'],
            $scoreData['recommendation_confidence'],
            $scoreData['factor_scores'] ?? null,
            $scoreData['indicator_scores'] ?? null,
            $scoreData['calculation_method'],
            $scoreData['is_current'] ? 1 : 0,
            $scoreData['notes'] ?? null
        ];
        
        EnhancedDbManager::execute($sql, $params);
        return (int) EnhancedDbManager::lastInsertId();
    }
    
    /**
     * Mark other scores as not current for the same stock and date
     * 
     * @param string $stockSymbol Stock symbol
     * @param string $calculationDate Calculation date
     */
    protected function markOtherScoresAsNotCurrent(string $stockSymbol, string $calculationDate): void
    {
        $sql = "
            UPDATE weighted_scores 
            SET is_current = 0 
            WHERE stock_symbol = ? AND calculation_date = ? AND is_current = 1
        ";
        
        EnhancedDbManager::execute($sql, [$stockSymbol, $calculationDate]);
    }
    
    /**
     * Get correlation history for a specific correlation
     * 
     * @param int $correlationId Correlation ID
     * @param int $limit Number of records to return
     * @return array Historical correlation data
     */
    public function getCorrelationHistory(int $correlationId, int $limit = 50): array
    {
        $sql = "
            SELECT 
                id,
                correlation_id,
                correlation_coefficient,
                correlation_strength,
                sample_size,
                confidence_level,
                calculation_period_start,
                calculation_period_end,
                created_at
            FROM correlation_history 
            WHERE correlation_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ";
        
        return EnhancedDbManager::fetchAll($sql, [$correlationId, $limit]);
    }
    
    /**
     * Save correlation history record
     * 
     * @param array $historyData History data
     * @return int Record ID
     */
    public function saveCorrelationHistory(array $historyData): int
    {
        $sql = "
            INSERT INTO correlation_history (
                correlation_id, correlation_coefficient, correlation_strength,
                sample_size, confidence_level, calculation_period_start,
                calculation_period_end
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $params = [
            $historyData['correlation_id'],
            $historyData['correlation_coefficient'],
            $historyData['correlation_strength'],
            $historyData['sample_size'],
            $historyData['confidence_level'],
            $historyData['calculation_period_start'],
            $historyData['calculation_period_end']
        ];
        
        EnhancedDbManager::execute($sql, $params);
        return (int) EnhancedDbManager::lastInsertId();
    }
}
?>
