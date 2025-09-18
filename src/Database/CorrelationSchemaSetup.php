<?php
/**
 * Correlation Database Schema Setup
 * Creates tables for tracking correlations between market factors and stocks,
 * plus technical analysis accuracy tracking
 * 
 * @package Database
 * @uses Ksfraser\Database\EnhancedDbManager Following SOLID/DRY/DI principles
 */

namespace Database;

use Ksfraser\Database\EnhancedDbManager;
use Exception;

class CorrelationSchemaSetup
{
    /**
     * Set up all correlation-related database tables
     * 
     * @return array Setup results with table creation status
     * @throws Exception If database operations fail
     */
    public static function setupCorrelationTables(): array
    {
        $results = [];
        
        try {
            EnhancedDbManager::beginTransaction();
            
            // 1. Factor-Stock Correlations Table
            $results['factor_stock_correlations'] = self::createFactorStockCorrelationsTable();
            
            // 2. Technical Analysis Accuracy Table
            $results['technical_analysis_accuracy'] = self::createTechnicalAnalysisAccuracyTable();
            
            // 3. Correlation History Table
            $results['correlation_history'] = self::createCorrelationHistoryTable();
            
            // 4. Indicator Performance Table
            $results['indicator_performance'] = self::createIndicatorPerformanceTable();
            
            // 5. Weighted Scores Table
            $results['weighted_scores'] = self::createWeightedScoresTable();
            
            EnhancedDbManager::commit();
            
            $results['overall_status'] = 'success';
            $results['message'] = 'All correlation tables created successfully';
            
        } catch (Exception $e) {
            EnhancedDbManager::rollback();
            $results['overall_status'] = 'error';
            $results['message'] = 'Failed to create correlation tables: ' . $e->getMessage();
            throw $e;
        }
        
        return $results;
    }
    
    /**
     * Create factor-stock correlations table
     * Tracks correlation scores between market factors and individual stocks
     * 
     * @return bool Table creation success
     */
    protected static function createFactorStockCorrelationsTable(): bool
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS factor_stock_correlations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                factor_type ENUM('interest_rate', 'market_sentiment', 'economic_indicator', 'commodity_price', 'forex_rate', 'sector_performance', 'index_performance') NOT NULL,
                factor_id INT NOT NULL,
                stock_symbol VARCHAR(10) NOT NULL,
                correlation_coefficient DECIMAL(5,4) NOT NULL DEFAULT 0.0000 COMMENT 'Correlation value between -1.0000 and 1.0000',
                correlation_strength ENUM('very_weak', 'weak', 'moderate', 'strong', 'very_strong') NOT NULL DEFAULT 'very_weak',
                sample_size INT NOT NULL DEFAULT 0 COMMENT 'Number of data points used for correlation',
                confidence_level DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Statistical confidence level as percentage',
                calculation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                
                -- Indexes for performance
                INDEX idx_factor_type_id (factor_type, factor_id),
                INDEX idx_stock_symbol (stock_symbol),
                INDEX idx_correlation_strength (correlation_strength),
                INDEX idx_calculation_date (calculation_date),
                INDEX idx_active (is_active),
                
                -- Unique constraint to prevent duplicates
                UNIQUE KEY unique_factor_stock (factor_type, factor_id, stock_symbol),
                
                -- Check constraints for data integrity
                CONSTRAINT chk_correlation_range CHECK (correlation_coefficient BETWEEN -1.0000 AND 1.0000),
                CONSTRAINT chk_confidence_range CHECK (confidence_level BETWEEN 0.00 AND 100.00),
                CONSTRAINT chk_sample_size CHECK (sample_size >= 0)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
            COMMENT='Tracks correlation coefficients between market factors and stocks'
        ";
        
        return EnhancedDbManager::execute($sql) !== false;
    }
    
    /**
     * Create technical analysis accuracy table
     * Tracks accuracy of technical indicators over time
     * 
     * @return bool Table creation success
     */
    protected static function createTechnicalAnalysisAccuracyTable(): bool
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS technical_analysis_accuracy (
                id INT AUTO_INCREMENT PRIMARY KEY,
                indicator_name VARCHAR(50) NOT NULL COMMENT 'Name of the technical indicator (RSI, MACD, SMA, etc.)',
                indicator_parameters JSON NULL COMMENT 'Parameters used for the indicator (periods, thresholds, etc.)',
                stock_symbol VARCHAR(10) NOT NULL,
                timeframe ENUM('1m', '5m', '15m', '30m', '1h', '4h', '1d', '1w', '1M') NOT NULL DEFAULT '1d',
                
                -- Prediction tracking
                prediction_type ENUM('buy', 'sell', 'hold', 'trend_up', 'trend_down', 'reversal', 'breakout') NOT NULL,
                predicted_date DATETIME NOT NULL,
                actual_outcome ENUM('correct', 'incorrect', 'partial', 'pending') NOT NULL DEFAULT 'pending',
                outcome_date DATETIME NULL,
                
                -- Accuracy metrics
                accuracy_score DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Accuracy percentage (0-100)',
                confidence_score DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Confidence in prediction (0-100)',
                profit_loss_percentage DECIMAL(8,4) NULL COMMENT 'Actual profit/loss if prediction was followed',
                
                -- Statistical tracking
                total_predictions INT NOT NULL DEFAULT 1,
                correct_predictions INT NOT NULL DEFAULT 0,
                running_accuracy DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Running accuracy percentage',
                
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Indexes for performance
                INDEX idx_indicator_name (indicator_name),
                INDEX idx_stock_symbol (stock_symbol),
                INDEX idx_timeframe (timeframe),
                INDEX idx_prediction_type (prediction_type),
                INDEX idx_predicted_date (predicted_date),
                INDEX idx_actual_outcome (actual_outcome),
                INDEX idx_accuracy_score (accuracy_score),
                INDEX idx_running_accuracy (running_accuracy),
                
                -- Composite indexes for common queries
                INDEX idx_indicator_stock_timeframe (indicator_name, stock_symbol, timeframe),
                INDEX idx_stock_date (stock_symbol, predicted_date),
                
                -- Check constraints
                CONSTRAINT chk_accuracy_range CHECK (accuracy_score BETWEEN 0.00 AND 100.00),
                CONSTRAINT chk_confidence_range_ta CHECK (confidence_score BETWEEN 0.00 AND 100.00),
                CONSTRAINT chk_running_accuracy CHECK (running_accuracy BETWEEN 0.00 AND 100.00),
                CONSTRAINT chk_prediction_counts CHECK (correct_predictions <= total_predictions)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
            COMMENT='Tracks accuracy and performance of technical analysis indicators'
        ";
        
        return EnhancedDbManager::execute($sql) !== false;
    }
    
    /**
     * Create correlation history table
     * Maintains historical correlation data for trend analysis
     * 
     * @return bool Table creation success
     */
    protected static function createCorrelationHistoryTable(): bool
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS correlation_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                correlation_id INT NOT NULL COMMENT 'Reference to factor_stock_correlations.id',
                correlation_coefficient DECIMAL(5,4) NOT NULL,
                correlation_strength ENUM('very_weak', 'weak', 'moderate', 'strong', 'very_strong') NOT NULL,
                sample_size INT NOT NULL DEFAULT 0,
                confidence_level DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                calculation_period_start DATE NOT NULL,
                calculation_period_end DATE NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                
                -- Indexes
                INDEX idx_correlation_id (correlation_id),
                INDEX idx_calculation_period (calculation_period_start, calculation_period_end),
                INDEX idx_created_at (created_at),
                INDEX idx_correlation_strength_hist (correlation_strength),
                
                -- Foreign key constraint
                FOREIGN KEY (correlation_id) REFERENCES factor_stock_correlations(id) ON DELETE CASCADE,
                
                -- Check constraints
                CONSTRAINT chk_correlation_range_hist CHECK (correlation_coefficient BETWEEN -1.0000 AND 1.0000),
                CONSTRAINT chk_confidence_range_hist CHECK (confidence_level BETWEEN 0.00 AND 100.00),
                CONSTRAINT chk_period_order CHECK (calculation_period_end >= calculation_period_start)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
            COMMENT='Historical correlation data for trend analysis'
        ";
        
        return EnhancedDbManager::execute($sql) !== false;
    }
    
    /**
     * Create indicator performance table
     * Aggregated performance metrics for technical indicators
     * 
     * @return bool Table creation success
     */
    protected static function createIndicatorPerformanceTable(): bool
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS indicator_performance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                indicator_name VARCHAR(50) NOT NULL,
                timeframe ENUM('1m', '5m', '15m', '30m', '1h', '4h', '1d', '1w', '1M') NOT NULL DEFAULT '1d',
                
                -- Aggregated performance metrics
                overall_accuracy DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                total_predictions INT NOT NULL DEFAULT 0,
                correct_predictions INT NOT NULL DEFAULT 0,
                avg_confidence DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                avg_profit_loss DECIMAL(8,4) NULL,
                
                -- Performance by prediction type
                buy_accuracy DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                sell_accuracy DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                hold_accuracy DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                
                -- Reliability metrics
                consistency_score DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'How consistent the indicator is across different stocks',
                volatility_performance DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Performance in volatile markets',
                trend_performance DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Performance in trending markets',
                
                -- Time tracking
                performance_period_start DATE NOT NULL,
                performance_period_end DATE NOT NULL,
                last_calculated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                next_calculation_due DATETIME NOT NULL,
                
                -- Status
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                calculation_status ENUM('current', 'calculating', 'outdated', 'error') NOT NULL DEFAULT 'current',
                
                -- Indexes
                INDEX idx_indicator_name_perf (indicator_name),
                INDEX idx_timeframe_perf (timeframe),
                INDEX idx_overall_accuracy (overall_accuracy),
                INDEX idx_performance_period (performance_period_start, performance_period_end),
                INDEX idx_last_calculated (last_calculated),
                INDEX idx_calculation_status (calculation_status),
                
                -- Composite indexes
                INDEX idx_indicator_timeframe (indicator_name, timeframe),
                INDEX idx_active_current (is_active, calculation_status),
                
                -- Unique constraint
                UNIQUE KEY unique_indicator_timeframe_period (indicator_name, timeframe, performance_period_start, performance_period_end),
                
                -- Check constraints
                CONSTRAINT chk_accuracy_perf CHECK (overall_accuracy BETWEEN 0.00 AND 100.00),
                CONSTRAINT chk_prediction_counts_perf CHECK (correct_predictions <= total_predictions),
                CONSTRAINT chk_buy_accuracy CHECK (buy_accuracy BETWEEN 0.00 AND 100.00),
                CONSTRAINT chk_sell_accuracy CHECK (sell_accuracy BETWEEN 0.00 AND 100.00),
                CONSTRAINT chk_hold_accuracy CHECK (hold_accuracy BETWEEN 0.00 AND 100.00),
                CONSTRAINT chk_consistency_score CHECK (consistency_score BETWEEN 0.00 AND 100.00),
                CONSTRAINT chk_performance_period_order CHECK (performance_period_end >= performance_period_start)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
            COMMENT='Aggregated performance metrics for technical indicators'
        ";
        
        return EnhancedDbManager::execute($sql) !== false;
    }
    
    /**
     * Create weighted scores table
     * Stores final weighted scores combining correlations and accuracy
     * 
     * @return bool Table creation success
     */
    protected static function createWeightedScoresTable(): bool
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS weighted_scores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                stock_symbol VARCHAR(10) NOT NULL,
                calculation_date DATE NOT NULL,
                calculation_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                
                -- Market factors weighted score
                market_factors_raw_score DECIMAL(8,4) NOT NULL DEFAULT 0.0000 COMMENT 'Raw score before correlation weighting',
                market_factors_weighted_score DECIMAL(8,4) NOT NULL DEFAULT 0.0000 COMMENT 'Score after applying correlation weights',
                market_factors_confidence DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                
                -- Technical analysis weighted score
                technical_analysis_raw_score DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
                technical_analysis_weighted_score DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
                technical_analysis_confidence DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                
                -- Combined final score
                combined_raw_score DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
                combined_weighted_score DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
                combined_confidence DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                
                -- Recommendation based on weighted scores
                recommendation ENUM('strong_buy', 'buy', 'hold', 'sell', 'strong_sell') NOT NULL DEFAULT 'hold',
                recommendation_confidence DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                
                -- Component breakdown (JSON for flexibility)
                factor_scores JSON NULL COMMENT 'Individual factor scores and weights',
                indicator_scores JSON NULL COMMENT 'Individual indicator scores and weights',
                
                -- Status and metadata
                calculation_method VARCHAR(50) NOT NULL DEFAULT 'correlation_weighted',
                is_current BOOLEAN NOT NULL DEFAULT TRUE,
                notes TEXT NULL,
                
                -- Indexes
                INDEX idx_stock_symbol_ws (stock_symbol),
                INDEX idx_calculation_date (calculation_date),
                INDEX idx_calculation_timestamp (calculation_timestamp),
                INDEX idx_recommendation (recommendation),
                INDEX idx_combined_weighted_score (combined_weighted_score),
                INDEX idx_is_current (is_current),
                
                -- Composite indexes
                INDEX idx_stock_date (stock_symbol, calculation_date),
                INDEX idx_stock_current (stock_symbol, is_current),
                INDEX idx_current_scores (is_current, combined_weighted_score),
                
                -- Unique constraint for current scores
                UNIQUE KEY unique_current_stock_score (stock_symbol, calculation_date, is_current),
                
                -- Check constraints
                CONSTRAINT chk_market_factors_confidence CHECK (market_factors_confidence BETWEEN 0.00 AND 100.00),
                CONSTRAINT chk_technical_analysis_confidence CHECK (technical_analysis_confidence BETWEEN 0.00 AND 100.00),
                CONSTRAINT chk_combined_confidence CHECK (combined_confidence BETWEEN 0.00 AND 100.00),
                CONSTRAINT chk_recommendation_confidence CHECK (recommendation_confidence BETWEEN 0.00 AND 100.00)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
            COMMENT='Final weighted scores combining market factors and technical analysis'
        ";
        
        return EnhancedDbManager::execute($sql) !== false;
    }
    
    /**
     * Create indexes for cross-table performance optimization
     * 
     * @return bool Index creation success
     */
    public static function createOptimizationIndexes(): bool
    {
        $indexes = [
            // Cross-table performance indexes
            "CREATE INDEX IF NOT EXISTS idx_factor_stock_lookup ON factor_stock_correlations (stock_symbol, factor_type, is_active)",
            "CREATE INDEX IF NOT EXISTS idx_accuracy_lookup ON technical_analysis_accuracy (stock_symbol, indicator_name, timeframe, actual_outcome)",
            "CREATE INDEX IF NOT EXISTS idx_performance_lookup ON indicator_performance (indicator_name, timeframe, is_active, calculation_status)",
            "CREATE INDEX IF NOT EXISTS idx_weighted_score_lookup ON weighted_scores (stock_symbol, is_current, calculation_date DESC)",
            
            // Analytics and reporting indexes
            "CREATE INDEX IF NOT EXISTS idx_correlation_analytics ON factor_stock_correlations (correlation_strength, confidence_level)",
            "CREATE INDEX IF NOT EXISTS idx_accuracy_analytics ON technical_analysis_accuracy (running_accuracy, total_predictions)",
            "CREATE INDEX IF NOT EXISTS idx_performance_analytics ON indicator_performance (overall_accuracy, total_predictions, timeframe)",
        ];
        
        foreach ($indexes as $sql) {
            if (EnhancedDbManager::execute($sql) === false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Drop all correlation tables (for cleanup or reset)
     * 
     * @return bool Drop success
     */
    public static function dropCorrelationTables(): bool
    {
        $tables = [
            'correlation_history',
            'weighted_scores',
            'indicator_performance',
            'technical_analysis_accuracy',
            'factor_stock_correlations'
        ];
        
        try {
            EnhancedDbManager::beginTransaction();
            
            foreach ($tables as $table) {
                EnhancedDbManager::execute("DROP TABLE IF EXISTS {$table}");
            }
            
            EnhancedDbManager::commit();
            return true;
            
        } catch (Exception $e) {
            EnhancedDbManager::rollback();
            throw $e;
        }
    }
    
    /**
     * Verify table structure and constraints
     * 
     * @return array Verification results
     */
    public static function verifyTableStructure(): array
    {
        $tables = [
            'factor_stock_correlations',
            'technical_analysis_accuracy', 
            'correlation_history',
            'indicator_performance',
            'weighted_scores'
        ];
        
        $results = [];
        
        foreach ($tables as $table) {
            $sql = "SHOW CREATE TABLE {$table}";
            try {
                $result = EnhancedDbManager::fetchOne($sql);
                $results[$table] = [
                    'exists' => true,
                    'structure' => $result['Create Table'] ?? 'Unknown'
                ];
            } catch (Exception $e) {
                $results[$table] = [
                    'exists' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}
?>
