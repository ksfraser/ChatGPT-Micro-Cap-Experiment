
<?php
require_once __DIR__ . '/src/IStockDataAccess.php';

/**
 * Dynamic Stock Data Access Layer
 * Handles data operations across per-symbol tables
 */

require_once 'StockTableManager.php';
require_once 'DatabaseConfig.php';

class DynamicStockDataAccess implements IStockDataAccess
{
    private $pdo;
    private $tableManager;
    private $logger;
    
    public function __construct()
    {
        $this->pdo = DatabaseConfig::createLegacyConnection();
        $this->tableManager = new StockTableManager();
        $this->logger = new JobLogger('logs/stock_data_access.log');
    }
    
    /**
     * Insert historical price data for a symbol
     */
    public function insertPriceData($symbol, $priceData)
    {
        $symbol = strtoupper(trim($symbol));
        
        // Ensure tables exist for this symbol
        if (!$this->tableManager->tablesExistForSymbol($symbol)) {
            $this->tableManager->registerSymbol($symbol);
        }
        
        $tableName = $this->tableManager->getTableName($symbol, 'historical_prices');
        
        $sql = "INSERT INTO {$tableName} 
                (symbol, date, open, high, low, close, adj_close, volume)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                open = VALUES(open),
                high = VALUES(high),
                low = VALUES(low),
                close = VALUES(close),
                adj_close = VALUES(adj_close),
                volume = VALUES(volume),
                updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->pdo->prepare($sql);
        
        if (is_array($priceData[0] ?? null)) {
            // Bulk insert
            $inserted = 0;
            foreach ($priceData as $data) {
                if ($this->executePriceInsert($stmt, $symbol, $data)) {
                    $inserted++;
                }
            }
            return $inserted;
        } else {
            // Single insert
            return $this->executePriceInsert($stmt, $symbol, $priceData);
        }
    }
    
    private function executePriceInsert($stmt, $symbol, $data)
    {
        try {
            return $stmt->execute([
                $symbol,
                $data['date'],
                $data['open'],
                $data['high'],
                $data['low'],
                $data['close'],
                $data['adj_close'] ?? $data['close'],
                $data['volume'] ?? 0
            ]);
        } catch (Exception $e) {
            $this->logger->error("Failed to insert price data for {$symbol}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get historical price data for a symbol
     */
    public function getPriceData($symbol, $startDate = null, $endDate = null, $limit = null)
    {
        $symbol = strtoupper(trim($symbol));
        
        if (!$this->tableManager->tablesExistForSymbol($symbol)) {
            return [];
        }
        
        $tableName = $this->tableManager->getTableName($symbol, 'historical_prices');
        
        $sql = "SELECT * FROM {$tableName} WHERE symbol = ?";
        $params = [$symbol];
        
        if ($startDate) {
            $sql .= " AND date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY date DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Insert technical indicator data
     */
    public function insertTechnicalIndicator($symbol, $indicatorData)
    {
        $symbol = strtoupper(trim($symbol));
        
        if (!$this->tableManager->tablesExistForSymbol($symbol)) {
            $this->tableManager->registerSymbol($symbol);
        }
        
        $tableName = $this->tableManager->getTableName($symbol, 'technical_indicators');
        
        $sql = "INSERT INTO {$tableName} 
                (symbol, date, indicator_name, value, period, timeframe)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                value = VALUES(value),
                calculation_date = CURRENT_TIMESTAMP";
        
        $stmt = $this->pdo->prepare($sql);
        
        if (is_array($indicatorData[0] ?? null)) {
            // Bulk insert
            $inserted = 0;
            foreach ($indicatorData as $data) {
                if ($this->executeIndicatorInsert($stmt, $symbol, $data)) {
                    $inserted++;
                }
            }
            return $inserted;
        } else {
            return $this->executeIndicatorInsert($stmt, $symbol, $indicatorData);
        }
    }
    
    private function executeIndicatorInsert($stmt, $symbol, $data)
    {
        try {
            return $stmt->execute([
                $symbol,
                $data['date'],
                $data['indicator_name'],
                $data['value'],
                $data['period'] ?? null,
                $data['timeframe'] ?? 'daily'
            ]);
        } catch (Exception $e) {
            $this->logger->error("Failed to insert indicator data for {$symbol}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get technical indicators for a symbol
     */
    public function getTechnicalIndicators($symbol, $indicatorName = null, $startDate = null, $endDate = null)
    {
        $symbol = strtoupper(trim($symbol));
        
        if (!$this->tableManager->tablesExistForSymbol($symbol)) {
            return [];
        }
        
        $tableName = $this->tableManager->getTableName($symbol, 'technical_indicators');
        
        $sql = "SELECT * FROM {$tableName} WHERE symbol = ?";
        $params = [$symbol];
        
        if ($indicatorName) {
            $sql .= " AND indicator_name = ?";
            $params[] = $indicatorName;
        }
        
        if ($startDate) {
            $sql .= " AND date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY date DESC, indicator_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Insert candlestick pattern data
     */
    public function insertCandlestickPattern($symbol, $patternData)
    {
        $symbol = strtoupper(trim($symbol));
        
        if (!$this->tableManager->tablesExistForSymbol($symbol)) {
            $this->tableManager->registerSymbol($symbol);
        }
        
        $tableName = $this->tableManager->getTableName($symbol, 'candlestick_patterns');
        
        $sql = "INSERT INTO {$tableName} 
                (symbol, date, pattern_name, strength, signal, timeframe)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                strength = VALUES(strength),
                signal = VALUES(signal),
                detection_date = CURRENT_TIMESTAMP";
        
        $stmt = $this->pdo->prepare($sql);
        
        if (is_array($patternData[0] ?? null)) {
            $inserted = 0;
            foreach ($patternData as $data) {
                if ($this->executePatternInsert($stmt, $symbol, $data)) {
                    $inserted++;
                }
            }
            return $inserted;
        } else {
            return $this->executePatternInsert($stmt, $symbol, $patternData);
        }
    }
    
    private function executePatternInsert($stmt, $symbol, $data)
    {
        try {
            return $stmt->execute([
                $symbol,
                $data['date'],
                $data['pattern_name'],
                $data['strength'] ?? 50,
                $data['signal'] ?? 'NEUTRAL',
                $data['timeframe'] ?? 'daily'
            ]);
        } catch (Exception $e) {
            $this->logger->error("Failed to insert pattern data for {$symbol}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get candlestick patterns for a symbol
     */
    public function getCandlestickPatterns($symbol, $patternName = null, $startDate = null, $endDate = null)
    {
        $symbol = strtoupper(trim($symbol));
        
        if (!$this->tableManager->tablesExistForSymbol($symbol)) {
            return [];
        }
        
        $tableName = $this->tableManager->getTableName($symbol, 'candlestick_patterns');
        
        $sql = "SELECT * FROM {$tableName} WHERE symbol = ?";
        $params = [$symbol];
        
        if ($patternName) {
            $sql .= " AND pattern_name = ?";
            $params[] = $patternName;
        }
        
        if ($startDate) {
            $sql .= " AND date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get the latest price for a symbol
     */
    public function getLatestPrice($symbol)
    {
        $symbol = strtoupper(trim($symbol));
        
        if (!$this->tableManager->tablesExistForSymbol($symbol)) {
            return null;
        }
        
        $tableName = $this->tableManager->getTableName($symbol, 'historical_prices');
        
        $sql = "SELECT * FROM {$tableName} WHERE symbol = ? ORDER BY date DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$symbol]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get price data for technical analysis calculations
     */
    public function getPriceDataForAnalysis($symbol, $days = 200)
    {
        $symbol = strtoupper(trim($symbol));
        
        if (!$this->tableManager->tablesExistForSymbol($symbol)) {
            return [];
        }
        
        $tableName = $this->tableManager->getTableName($symbol, 'historical_prices');
        
        $sql = "SELECT date, open, high, low, close, volume 
                FROM {$tableName} 
                WHERE symbol = ? 
                ORDER BY date DESC 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$symbol, $days]);
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return in chronological order for calculations
        return array_reverse($data);
    }
    
    /**
     * Cross-symbol analysis: Get data for multiple symbols
     */
    public function getMultiSymbolData($symbols, $dataType = 'historical_prices', $startDate = null, $endDate = null)
    {
        $results = [];
        
        foreach ($symbols as $symbol) {
            $symbol = strtoupper(trim($symbol));
            
            switch ($dataType) {
                case 'historical_prices':
                    $results[$symbol] = $this->getPriceData($symbol, $startDate, $endDate);
                    break;
                    
                case 'technical_indicators':
                    $results[$symbol] = $this->getTechnicalIndicators($symbol, null, $startDate, $endDate);
                    break;
                    
                case 'candlestick_patterns':
                    $results[$symbol] = $this->getCandlestickPatterns($symbol, null, $startDate, $endDate);
                    break;
                    
                case 'latest_prices':
                    $results[$symbol] = $this->getLatestPrice($symbol);
                    break;
                    
                default:
                    $this->logger->warning("Unknown data type requested: {$dataType}");
            }
        }
        
        return $results;
    }
    
    /**
     * Get data export for a symbol (for backup/migration)
     */
    public function exportSymbolData($symbol, $tableTypes = null)
    {
        $symbol = strtoupper(trim($symbol));
        
        if (!$this->tableManager->tablesExistForSymbol($symbol)) {
            return [];
        }
        
        $tableTypes = $tableTypes ?? [
            'historical_prices',
            'technical_indicators', 
            'candlestick_patterns',
            'support_resistance',
            'trading_signals',
            'earnings_data',
            'dividends'
        ];
        
        $exportData = [];
        
        foreach ($tableTypes as $tableType) {
            try {
                $tableName = $this->tableManager->getTableName($symbol, $tableType);
                
                $sql = "SELECT * FROM {$tableName} WHERE symbol = ? ORDER BY date";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$symbol]);
                
                $exportData[$tableType] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                $this->logger->error("Failed to export {$tableType} for {$symbol}: " . $e->getMessage());
                $exportData[$tableType] = [];
            }
        }
        
        return $exportData;
    }
    
    /**
     * Import data for a symbol (from backup/migration)
     */
    public function importSymbolData($symbol, $importData)
    {
        $symbol = strtoupper(trim($symbol));
        
        // Ensure symbol is registered and tables exist
        if (!$this->tableManager->tablesExistForSymbol($symbol)) {
            $this->tableManager->registerSymbol($symbol);
        }
        
        $results = [];
        
        foreach ($importData as $tableType => $data) {
            try {
                switch ($tableType) {
                    case 'historical_prices':
                        $results[$tableType] = $this->insertPriceData($symbol, $data);
                        break;
                        
                    case 'technical_indicators':
                        $results[$tableType] = $this->insertTechnicalIndicator($symbol, $data);
                        break;
                        
                    case 'candlestick_patterns':
                        $results[$tableType] = $this->insertCandlestickPattern($symbol, $data);
                        break;
                        
                    default:
                        // For other table types, use generic insert
                        $results[$tableType] = $this->genericImport($symbol, $tableType, $data);
                }
            } catch (Exception $e) {
                $this->logger->error("Failed to import {$tableType} for {$symbol}: " . $e->getMessage());
                $results[$tableType] = 0;
            }
        }
        
        return $results;
    }
    
    /**
     * Generic import method for other table types
     */
    private function genericImport($symbol, $tableType, $data)
    {
        if (empty($data)) {
            return 0;
        }
        
        $tableName = $this->tableManager->getTableName($symbol, $tableType);
        
        // Get the first row to determine columns
        $firstRow = reset($data);
        $columns = array_keys($firstRow);
        
        $placeholders = str_repeat('?,', count($columns) - 1) . '?';
        $sql = "INSERT INTO {$tableName} (" . implode(',', $columns) . ") VALUES ({$placeholders})";
        
        $stmt = $this->pdo->prepare($sql);
        
        $inserted = 0;
        foreach ($data as $row) {
            try {
                if ($stmt->execute(array_values($row))) {
                    $inserted++;
                }
            } catch (Exception $e) {
                $this->logger->warning("Failed to insert row in {$tableName}: " . $e->getMessage());
            }
        }
        
        return $inserted;
    }
    
    /**
     * Clean up old data for a symbol
     */
    public function cleanupOldData($symbol, $daysToKeep = 365)
    {
        $symbol = strtoupper(trim($symbol));
        
        if (!$this->tableManager->tablesExistForSymbol($symbol)) {
            return [];
        }
        
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));
        $cleanupResults = [];
        
        $tableTypes = ['historical_prices', 'technical_indicators', 'candlestick_patterns'];
        
        foreach ($tableTypes as $tableType) {
            try {
                $tableName = $this->tableManager->getTableName($symbol, $tableType);
                
                $sql = "DELETE FROM {$tableName} WHERE symbol = ? AND date < ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$symbol, $cutoffDate]);
                
                $cleanupResults[$tableType] = $stmt->rowCount();
                
            } catch (Exception $e) {
                $this->logger->error("Failed to cleanup {$tableType} for {$symbol}: " . $e->getMessage());
                $cleanupResults[$tableType] = 0;
            }
        }
        
        return $cleanupResults;
    }
}
