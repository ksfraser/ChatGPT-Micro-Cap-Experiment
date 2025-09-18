<?php

namespace App\Repositories;

/**
 * Single Responsibility: Handle database access for per-symbol indicator data
 * Follows SRP by handling only indicator data persistence and retrieval
 */
class IndicatorRepository
{
    /**
     * Store indicator value in the per-symbol indicators table
     */
    public function storeIndicatorValue(
        string $symbol,
        string $date,
        string $indicatorName,
        float $value,
        array $additionalData = []
    ): bool {
        $tableName = $symbol . '_indicators';
        
        try {
            // Check if table exists, create if not
            $this->ensureTableExists($symbol);
            
            $data = array_merge([
                'symbol' => $symbol,
                'date' => $date,
                'indicator_name' => $indicatorName,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now()
            ], $additionalData);
            
            // Use INSERT ... ON DUPLICATE KEY UPDATE for upsert behavior
            return \DB::table($tableName)->updateOrInsert(
                [
                    'symbol' => $symbol,
                    'date' => $date,
                    'indicator_name' => $indicatorName
                ],
                $data
            );
            
        } catch (\Exception $e) {
            \Log::error("Failed to store indicator for {$symbol}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve indicator values for a symbol over a date range
     */
    public function getIndicatorValues(
        string $symbol,
        string $indicatorName,
        string $startDate = null,
        string $endDate = null,
        int $limit = null
    ): array {
        $tableName = $symbol . '_indicators';
        
        try {
            if (!$this->tableExists($tableName)) {
                return [];
            }
            
            $query = \DB::table($tableName)
                ->where('symbol', $symbol)
                ->where('indicator_name', $indicatorName)
                ->orderBy('date', 'desc');
                
            if ($startDate) {
                $query->where('date', '>=', $startDate);
            }
            
            if ($endDate) {
                $query->where('date', '<=', $endDate);
            }
            
            if ($limit) {
                $query->limit($limit);
            }
            
            return $query->get()->toArray();
            
        } catch (\Exception $e) {
            \Log::error("Failed to retrieve indicators for {$symbol}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the latest indicator value for a symbol
     */
    public function getLatestIndicatorValue(string $symbol, string $indicatorName): ?object
    {
        $tableName = $symbol . '_indicators';
        
        try {
            if (!$this->tableExists($tableName)) {
                return null;
            }
            
            return \DB::table($tableName)
                ->where('symbol', $symbol)
                ->where('indicator_name', $indicatorName)
                ->orderBy('date', 'desc')
                ->first();
                
        } catch (\Exception $e) {
            \Log::error("Failed to get latest indicator for {$symbol}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all indicators for a symbol on a specific date
     */
    public function getIndicatorsForDate(string $symbol, string $date): array
    {
        $tableName = $symbol . '_indicators';
        
        try {
            if (!$this->tableExists($tableName)) {
                return [];
            }
            
            return \DB::table($tableName)
                ->where('symbol', $symbol)
                ->where('date', $date)
                ->get()
                ->toArray();
                
        } catch (\Exception $e) {
            \Log::error("Failed to get indicators for {$symbol} on {$date}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Store Shannon analysis results
     */
    public function storeShannonAnalysis(
        string $symbol,
        string $date,
        float $shannonProbability,
        float $effectiveProbability,
        array $additionalData = []
    ): bool {
        $tableName = $symbol . '_shannon';
        
        try {
            $this->ensureTableExists($symbol, 'shannon');
            
            $data = array_merge([
                'symbol' => $symbol,
                'analysis_date' => $date,
                'shannon_probability' => $shannonProbability,
                'effective_probability' => $effectiveProbability,
                'created_at' => now()
            ], $additionalData);
            
            return \DB::table($tableName)->updateOrInsert(
                [
                    'symbol' => $symbol,
                    'analysis_date' => $date
                ],
                $data
            );
            
        } catch (\Exception $e) {
            \Log::error("Failed to store Shannon analysis for {$symbol}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Store risk metrics
     */
    public function storeRiskMetrics(
        string $symbol,
        string $date,
        string $metricName,
        float $metricValue,
        array $additionalData = []
    ): bool {
        $tableName = $symbol . '_risk_metrics';
        
        try {
            $this->ensureTableExists($symbol, 'risk_metrics');
            
            $data = array_merge([
                'symbol' => $symbol,
                'calculation_date' => $date,
                'metric_name' => $metricName,
                'metric_value' => $metricValue,
                'created_at' => now()
            ], $additionalData);
            
            return \DB::table($tableName)->updateOrInsert(
                [
                    'symbol' => $symbol,
                    'calculation_date' => $date,
                    'metric_name' => $metricName
                ],
                $data
            );
            
        } catch (\Exception $e) {
            \Log::error("Failed to store risk metrics for {$symbol}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Store correlation data
     */
    public function storeCorrelationData(
        string $symbol,
        string $comparisonSymbol,
        string $date,
        float $correlationCoefficient,
        array $additionalData = []
    ): bool {
        $tableName = $symbol . '_correlations';
        
        try {
            $this->ensureTableExists($symbol, 'correlations');
            
            $data = array_merge([
                'symbol' => $symbol,
                'comparison_symbol' => $comparisonSymbol,
                'calculation_date' => $date,
                'correlation_coefficient' => $correlationCoefficient,
                'created_at' => now()
            ], $additionalData);
            
            return \DB::table($tableName)->updateOrInsert(
                [
                    'symbol' => $symbol,
                    'comparison_symbol' => $comparisonSymbol,
                    'calculation_date' => $date
                ],
                $data
            );
            
        } catch (\Exception $e) {
            \Log::error("Failed to store correlation data for {$symbol}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Store backtest results
     */
    public function storeBacktestResults(
        string $symbol,
        string $strategyName,
        array $results
    ): bool {
        $tableName = $symbol . '_backtests';
        
        try {
            $this->ensureTableExists($symbol, 'backtests');
            
            return \DB::table($tableName)->insert(array_merge($results, [
                'symbol' => $symbol,
                'strategy_name' => $strategyName,
                'created_at' => now()
            ]));
            
        } catch (\Exception $e) {
            \Log::error("Failed to store backtest results for {$symbol}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure the symbol table exists by calling StockTableManager
     */
    private function ensureTableExists(string $symbol, string $tableType = 'indicators'): bool
    {
        try {
            // This would integrate with the StockTableManager
            $tableManager = app(\App\Services\StockTableManager::class);
            return $tableManager->createSymbolTable($symbol, $tableType);
        } catch (\Exception $e) {
            \Log::error("Failed to ensure table exists for {$symbol}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a table exists
     */
    private function tableExists(string $tableName): bool
    {
        try {
            return \Schema::hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get historical data for calculating indicators
     */
    public function getHistoricalPrices(
        string $symbol,
        int $periods,
        string $endDate = null
    ): array {
        $tableName = $symbol . '_prices';
        
        try {
            if (!$this->tableExists($tableName)) {
                return [];
            }
            
            $query = \DB::table($tableName)
                ->where('symbol', $symbol)
                ->orderBy('date', 'desc')
                ->limit($periods);
                
            if ($endDate) {
                $query->where('date', '<=', $endDate);
            }
            
            return $query->get()->reverse()->values()->toArray();
            
        } catch (\Exception $e) {
            \Log::error("Failed to get historical prices for {$symbol}: " . $e->getMessage());
            return [];
        }
    }
}
