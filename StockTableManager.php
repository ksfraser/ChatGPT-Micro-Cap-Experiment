<?php

/**
 * Dynamic Stock Table Manager
 * Creates and manages per-symbol tables for stock data
 */

require_once 'DatabaseConfig.php';
require_once __DIR__ . '/JobLogger.php';

require_once __DIR__ . '/src/IStockTableManager.php';

class StockTableManager implements IStockTableManager
{
    private $pdo;
    private $logger;
    
    // Table templates for each stock symbol
    private $tableTemplates = [
        'historical_prices' => [
            'suffix' => '_prices',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    open DECIMAL(10,4) NOT NULL,
                    high DECIMAL(10,4) NOT NULL,
                    low DECIMAL(10,4) NOT NULL,
                    close DECIMAL(10,4) NOT NULL,
                    adj_close DECIMAL(10,4),
                    volume BIGINT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    -- Enhanced quote fields for advanced analysis
                    exchange VARCHAR(10) NULL COMMENT 'Exchange where traded',
                    currency VARCHAR(3) DEFAULT 'USD' COMMENT 'Currency code',
                    split_adjusted BOOLEAN DEFAULT FALSE COMMENT 'Whether prices are split-adjusted',
                    dividend_adjusted BOOLEAN DEFAULT FALSE COMMENT 'Whether prices are dividend-adjusted',
                    data_source VARCHAR(50) NULL COMMENT 'Source of the data',
                    data_quality ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
                    bid DECIMAL(10,4) NULL COMMENT 'Bid price if available',
                    ask DECIMAL(10,4) NULL COMMENT 'Ask price if available',
                    vwap DECIMAL(10,4) NULL COMMENT 'Volume weighted average price',
                    true_range DECIMAL(10,4) NULL COMMENT 'True range for volatility analysis',
                    typical_price DECIMAL(10,4) NULL COMMENT '(H+L+C)/3 for technical analysis',
                    UNIQUE KEY unique_date_symbol (symbol, date),
                    INDEX idx_date (date),
                    INDEX idx_symbol (symbol),
                    INDEX idx_exchange (exchange),
                    INDEX idx_volume (volume),
                    INDEX idx_data_quality (data_quality)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'technical_indicators' => [
            'suffix' => '_indicators',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    indicator_name VARCHAR(50) NOT NULL,
                    value DECIMAL(15,6),
                    period INT,
                    timeframe VARCHAR(10) DEFAULT 'daily',
                    calculation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    -- Enhanced indicator fields for advanced analysis
                    indicator_type ENUM('trend', 'momentum', 'volatility', 'volume', 'statistical') DEFAULT 'trend',
                    upper_band DECIMAL(15,6) NULL COMMENT 'For Bollinger Bands, etc.',
                    lower_band DECIMAL(15,6) NULL COMMENT 'For Bollinger Bands, etc.',
                    signal_line DECIMAL(15,6) NULL COMMENT 'For MACD, Stochastic %D, etc.',
                    histogram DECIMAL(15,6) NULL COMMENT 'For MACD histogram',
                    plus_di DECIMAL(15,6) NULL COMMENT 'For ADX +DI',
                    minus_di DECIMAL(15,6) NULL COMMENT 'For ADX -DI',
                    aroon_up DECIMAL(15,6) NULL COMMENT 'For Aroon oscillator',
                    aroon_down DECIMAL(15,6) NULL COMMENT 'For Aroon oscillator',
                    k_percent DECIMAL(15,6) NULL COMMENT 'For Stochastic %K',
                    d_percent DECIMAL(15,6) NULL COMMENT 'For Stochastic %D',
                    trend_direction ENUM('up', 'down', 'sideways') NULL COMMENT 'For Parabolic SAR',
                    accuracy_score DECIMAL(5,2) NULL COMMENT 'Prediction accuracy 0-100',
                    confidence_level DECIMAL(5,2) NULL COMMENT 'Statistical confidence 0-100',
                    sample_size INT NULL COMMENT 'Sample size for statistical indicators',
                    UNIQUE KEY unique_indicator (symbol, date, indicator_name, period, timeframe),
                    INDEX idx_indicator_name (indicator_name),
                    INDEX idx_indicator_type (indicator_type),
                    INDEX idx_date (date),
                    INDEX idx_symbol (symbol),
                    INDEX idx_accuracy (accuracy_score)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'candlestick_patterns' => [
            'suffix' => '_patterns',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    pattern_name VARCHAR(100) NOT NULL,
                    strength INT DEFAULT 50,
                    signal ENUM('BUY', 'SELL', 'NEUTRAL') DEFAULT 'NEUTRAL',
                    timeframe VARCHAR(10) DEFAULT 'daily',
                    detection_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_pattern (symbol, date, pattern_name, timeframe),
                    INDEX idx_pattern_name (pattern_name),
                    INDEX idx_signal (signal),
                    INDEX idx_date (date),
                    INDEX idx_symbol (symbol)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'support_resistance' => [
            'suffix' => '_support_resistance',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    level_type ENUM('SUPPORT', 'RESISTANCE') NOT NULL,
                    price_level DECIMAL(10,4) NOT NULL,
                    strength INT DEFAULT 50,
                    touches INT DEFAULT 1,
                    timeframe VARCHAR(10) DEFAULT 'daily',
                    calculated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_level_type (level_type),
                    INDEX idx_price_level (price_level),
                    INDEX idx_date (date),
                    INDEX idx_symbol (symbol)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'trading_signals' => [
            'suffix' => '_signals',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    signal_type VARCHAR(50) NOT NULL,
                    signal ENUM('BUY', 'SELL', 'HOLD') NOT NULL,
                    strength INT DEFAULT 50,
                    price DECIMAL(10,4),
                    strategy VARCHAR(100),
                    notes TEXT,
                    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_signal_type (signal_type),
                    INDEX idx_signal (signal),
                    INDEX idx_date (date),
                    INDEX idx_symbol (symbol)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'earnings_data' => [
            'suffix' => '_earnings',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    quarter VARCHAR(10) NOT NULL,
                    year INT NOT NULL,
                    earnings_date DATE,
                    estimated_eps DECIMAL(8,4),
                    actual_eps DECIMAL(8,4),
                    revenue BIGINT,
                    estimated_revenue BIGINT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_quarter (symbol, quarter, year),
                    INDEX idx_earnings_date (earnings_date),
                    INDEX idx_year (year),
                    INDEX idx_symbol (symbol)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'dividends' => [
            'suffix' => '_dividends',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    ex_date DATE NOT NULL,
                    payment_date DATE,
                    record_date DATE,
                    declared_date DATE,
                    amount DECIMAL(8,4) NOT NULL,
                    frequency VARCHAR(20),
                    dividend_type VARCHAR(50) DEFAULT 'REGULAR',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    -- Enhanced dividend fields for advanced analysis
                    currency VARCHAR(3) DEFAULT 'USD' COMMENT 'Currency of dividend payment',
                    tax_status ENUM('qualified', 'ordinary', 'capital_gain', 'return_of_capital') DEFAULT 'qualified',
                    yield_on_ex_date DECIMAL(6,4) NULL COMMENT 'Dividend yield on ex-date',
                    payout_ratio DECIMAL(6,4) NULL COMMENT 'Dividend payout ratio',
                    growth_rate DECIMAL(6,4) NULL COMMENT 'YoY dividend growth rate',
                    coverage_ratio DECIMAL(6,4) NULL COMMENT 'Earnings coverage ratio',
                    franking_credit DECIMAL(8,4) NULL COMMENT 'For Australian dividends',
                    special_designation TEXT NULL COMMENT 'Special dividend notes',
                    data_source VARCHAR(50) NULL COMMENT 'Source of dividend data',
                    adjustment_factor DECIMAL(10,6) DEFAULT 1.0 COMMENT 'Price adjustment factor',
                    UNIQUE KEY unique_ex_date (symbol, ex_date),
                    INDEX idx_ex_date (ex_date),
                    INDEX idx_payment_date (payment_date),
                    INDEX idx_symbol (symbol),
                    INDEX idx_dividend_type (dividend_type),
                    INDEX idx_frequency (frequency)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'splits' => [
            'suffix' => '_splits',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    split_date DATE NOT NULL,
                    split_ratio_from INT NOT NULL COMMENT 'Old shares',
                    split_ratio_to INT NOT NULL COMMENT 'New shares',
                    adjustment_factor DECIMAL(10,6) NOT NULL COMMENT 'Price adjustment factor',
                    announcement_date DATE NULL,
                    effective_date DATE NULL,
                    split_type ENUM('split', 'reverse_split', 'spinoff') DEFAULT 'split',
                    notes TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_split_date (symbol, split_date),
                    INDEX idx_split_date (split_date),
                    INDEX idx_symbol (symbol),
                    INDEX idx_split_type (split_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'risk_metrics' => [
            'suffix' => '_risk_metrics',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    calculation_date DATE NOT NULL,
                    metric_name VARCHAR(50) NOT NULL,
                    metric_value DECIMAL(15,8) NULL,
                    confidence_level DECIMAL(5,2) DEFAULT 95.0,
                    time_horizon INT DEFAULT 1 COMMENT 'Days',
                    sample_period INT DEFAULT 252 COMMENT 'Days used in calculation',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    -- Risk metric specific fields
                    var_95 DECIMAL(8,4) NULL COMMENT 'Value at Risk 95%',
                    var_99 DECIMAL(8,4) NULL COMMENT 'Value at Risk 99%',
                    expected_shortfall DECIMAL(8,4) NULL COMMENT 'Conditional VaR',
                    beta DECIMAL(6,4) NULL COMMENT 'Market beta',
                    alpha DECIMAL(6,4) NULL COMMENT 'Jensen alpha',
                    sharpe_ratio DECIMAL(6,4) NULL COMMENT 'Risk-adjusted return',
                    sortino_ratio DECIMAL(6,4) NULL COMMENT 'Downside risk-adjusted return',
                    calmar_ratio DECIMAL(6,4) NULL COMMENT 'Return over max drawdown',
                    max_drawdown DECIMAL(6,4) NULL COMMENT 'Maximum drawdown percentage',
                    volatility DECIMAL(6,4) NULL COMMENT 'Annualized volatility',
                    skewness DECIMAL(6,4) NULL COMMENT 'Return distribution skewness',
                    kurtosis DECIMAL(6,4) NULL COMMENT 'Return distribution kurtosis',
                    UNIQUE KEY unique_metric (symbol, calculation_date, metric_name, time_horizon),
                    INDEX idx_calculation_date (calculation_date),
                    INDEX idx_metric_name (metric_name),
                    INDEX idx_symbol (symbol)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'shannon_analysis' => [
            'suffix' => '_shannon',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    analysis_date DATE NOT NULL,
                    window_size INT DEFAULT 20,
                    shannon_probability DECIMAL(8,6) NOT NULL,
                    effective_probability DECIMAL(8,6) NOT NULL,
                    entropy DECIMAL(8,6) NULL,
                    accuracy_estimate DECIMAL(5,2) NULL COMMENT 'Accuracy percentage',
                    standard_error DECIMAL(8,6) NULL,
                    sample_size INT NULL,
                    up_moves INT NULL,
                    down_moves INT NULL,
                    mean_return DECIMAL(8,6) NULL,
                    volatility DECIMAL(8,6) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    -- Market behavior analysis
                    hurst_exponent DECIMAL(6,4) NULL COMMENT 'Market persistence measure',
                    persistence_interpretation ENUM('persistent', 'anti_persistent', 'random_walk') NULL,
                    mean_reversion_score DECIMAL(6,4) NULL,
                    is_mean_reverting BOOLEAN DEFAULT FALSE,
                    autocorrelation_lag1 DECIMAL(6,4) NULL,
                    half_life DECIMAL(8,2) NULL COMMENT 'Mean reversion half-life in days',
                    UNIQUE KEY unique_analysis (symbol, analysis_date, window_size),
                    INDEX idx_analysis_date (analysis_date),
                    INDEX idx_shannon_probability (shannon_probability),
                    INDEX idx_symbol (symbol),
                    INDEX idx_persistence (persistence_interpretation)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'backtest_results' => [
            'suffix' => '_backtests',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    strategy_name VARCHAR(100) NOT NULL,
                    start_date DATE NOT NULL,
                    end_date DATE NOT NULL,
                    initial_capital DECIMAL(15,2) NOT NULL,
                    final_capital DECIMAL(15,2) NOT NULL,
                    total_return DECIMAL(8,4) NOT NULL,
                    annualized_return DECIMAL(8,4) NULL,
                    max_drawdown DECIMAL(8,4) NULL,
                    sharpe_ratio DECIMAL(6,4) NULL,
                    sortino_ratio DECIMAL(6,4) NULL,
                    profit_factor DECIMAL(6,4) NULL,
                    total_trades INT DEFAULT 0,
                    winning_trades INT DEFAULT 0,
                    losing_trades INT DEFAULT 0,
                    win_rate DECIMAL(5,2) NULL,
                    avg_win DECIMAL(10,4) NULL,
                    avg_loss DECIMAL(10,4) NULL,
                    largest_win DECIMAL(10,4) NULL,
                    largest_loss DECIMAL(10,4) NULL,
                    consecutive_wins INT DEFAULT 0,
                    consecutive_losses INT DEFAULT 0,
                    commission_paid DECIMAL(10,2) DEFAULT 0,
                    slippage_cost DECIMAL(10,2) DEFAULT 0,
                    parameters JSON NULL COMMENT 'Strategy parameters used',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    -- Performance attribution
                    benchmark_return DECIMAL(8,4) NULL COMMENT 'Benchmark performance',
                    alpha DECIMAL(6,4) NULL COMMENT 'Excess return over benchmark',
                    beta DECIMAL(6,4) NULL COMMENT 'Market sensitivity',
                    tracking_error DECIMAL(6,4) NULL COMMENT 'Standard deviation of excess returns',
                    information_ratio DECIMAL(6,4) NULL COMMENT 'Alpha divided by tracking error',
                    calmar_ratio DECIMAL(6,4) NULL COMMENT 'Return over max drawdown',
                    INDEX idx_strategy_name (strategy_name),
                    INDEX idx_start_date (start_date),
                    INDEX idx_end_date (end_date),
                    INDEX idx_symbol (symbol),
                    INDEX idx_total_return (total_return),
                    INDEX idx_sharpe_ratio (sharpe_ratio)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'correlation_data' => [
            'suffix' => '_correlations',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    comparison_symbol VARCHAR(10) NOT NULL,
                    calculation_date DATE NOT NULL,
                    correlation_coefficient DECIMAL(8,6) NOT NULL,
                    sample_period INT DEFAULT 252 COMMENT 'Days used in calculation',
                    correlation_type ENUM('pearson', 'spearman', 'kendall') DEFAULT 'pearson',
                    significance_level DECIMAL(5,2) NULL COMMENT 'Statistical significance',
                    p_value DECIMAL(8,6) NULL COMMENT 'Hypothesis test p-value',
                    confidence_interval_lower DECIMAL(8,6) NULL,
                    confidence_interval_upper DECIMAL(8,6) NULL,
                    rolling_window INT NULL COMMENT 'Rolling correlation window',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    -- Additional correlation metrics
                    covariance DECIMAL(12,8) NULL COMMENT 'Covariance between assets',
                    r_squared DECIMAL(6,4) NULL COMMENT 'Coefficient of determination',
                    beta_coefficient DECIMAL(8,6) NULL COMMENT 'Regression beta',
                    alpha_coefficient DECIMAL(8,6) NULL COMMENT 'Regression alpha',
                    UNIQUE KEY unique_correlation (symbol, comparison_symbol, calculation_date, sample_period, correlation_type),
                    INDEX idx_calculation_date (calculation_date),
                    INDEX idx_comparison_symbol (comparison_symbol),
                    INDEX idx_correlation_coefficient (correlation_coefficient),
                    INDEX idx_symbol (symbol)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
    ];
    
    public function __construct()
    {
        $this->pdo = DatabaseConfig::createLegacyConnection();
        $this->logger = new JobLogger('logs/table_manager.log');
        
        // Ensure we have the stock symbol registry table
        $this->createSymbolRegistryTable();
    }
    
    /**
     * Create the master symbol registry table
     */
    private function createSymbolRegistryTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS stock_symbol_registry (
                id INT AUTO_INCREMENT PRIMARY KEY,
                symbol VARCHAR(10) NOT NULL UNIQUE,
                company_name VARCHAR(255),
                exchange VARCHAR(20),
                sector VARCHAR(100),
                industry VARCHAR(100),
                market_cap BIGINT,
                status ENUM('ACTIVE', 'INACTIVE', 'DELISTED') DEFAULT 'ACTIVE',
                tables_created BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_symbol (symbol),
                INDEX idx_status (status),
                INDEX idx_exchange (exchange)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
        $this->logger->info("Stock symbol registry table ensured");
    }
    
    /**
     * Register a new stock symbol and create its tables
     */
    public function registerSymbol($symbol, $companyData = [])
    {
        $symbol = strtoupper(trim($symbol));
        
        // Validate symbol
        if (!$this->isValidSymbol($symbol)) {
            throw new InvalidArgumentException("Invalid stock symbol: {$symbol}");
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Insert or update symbol in registry
            $sql = "INSERT INTO stock_symbol_registry 
                    (symbol, company_name, exchange, sector, industry, market_cap, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    company_name = COALESCE(VALUES(company_name), company_name),
                    exchange = COALESCE(VALUES(exchange), exchange),
                    sector = COALESCE(VALUES(sector), sector),
                    industry = COALESCE(VALUES(industry), industry),
                    market_cap = COALESCE(VALUES(market_cap), market_cap),
                    status = VALUES(status),
                    updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $symbol,
                $companyData['company_name'] ?? null,
                $companyData['exchange'] ?? null,
                $companyData['sector'] ?? null,
                $companyData['industry'] ?? null,
                $companyData['market_cap'] ?? null,
                $companyData['status'] ?? 'ACTIVE'
            ]);
            
            // Create tables for this symbol
            $this->createTablesForSymbol($symbol);
            
            // Mark tables as created
            $sql = "UPDATE stock_symbol_registry SET tables_created = TRUE WHERE symbol = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$symbol]);
            
            $this->pdo->commit();
            
            $this->logger->info("Successfully registered symbol: {$symbol}");
            
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Failed to register symbol {$symbol}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create all tables for a specific symbol
     */
    public function createTablesForSymbol($symbol)
    {
        $symbol = strtoupper(trim($symbol));
        $sanitizedSymbol = $this->sanitizeSymbolForTableName($symbol);
        
        $createdTables = [];
        
        foreach ($this->tableTemplates as $tableType => $template) {
            $tableName = $sanitizedSymbol . $template['suffix'];
            
            try {
                $sql = str_replace('{table_name}', $tableName, $template['schema']);
                $this->pdo->exec($sql);
                
                $createdTables[] = $tableName;
                $this->logger->info("Created table: {$tableName}");
                
            } catch (Exception $e) {
                $this->logger->error("Failed to create table {$tableName}: " . $e->getMessage());
                throw $e;
            }
        }
        
        return $createdTables;
    }
    
    /**
     * Get table name for a specific symbol and data type
     */
    public function getTableName($symbol, $tableType)
    {
        if (!isset($this->tableTemplates[$tableType])) {
            throw new InvalidArgumentException("Unknown table type: {$tableType}");
        }
        
        $symbol = strtoupper(trim($symbol));
        $sanitizedSymbol = $this->sanitizeSymbolForTableName($symbol);
        
        return $sanitizedSymbol . $this->tableTemplates[$tableType]['suffix'];
    }
    
    /**
     * Check if tables exist for a symbol
     */
    public function tablesExistForSymbol($symbol)
    {
        $symbol = strtoupper(trim($symbol));
        
        $sql = "SELECT tables_created FROM stock_symbol_registry WHERE symbol = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$symbol]);
        
        $result = $stmt->fetchColumn();
        return $result === '1' || $result === 1 || $result === true;
    }
    
    /**
     * Get all registered symbols
     */
    public function getAllSymbols($activeOnly = true)
    {
        $sql = "SELECT symbol, company_name, exchange, status, tables_created 
                FROM stock_symbol_registry";
        
        if ($activeOnly) {
            $sql .= " WHERE status = 'ACTIVE'";
        }
        
        $sql .= " ORDER BY symbol";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Remove tables for a symbol (use with caution!)
     */
    public function removeTablesForSymbol($symbol, $confirm = false)
    {
        if (!$confirm) {
            throw new InvalidArgumentException("Must confirm table removal by passing confirm=true");
        }
        
        $symbol = strtoupper(trim($symbol));
        $sanitizedSymbol = $this->sanitizeSymbolForTableName($symbol);
        
        $droppedTables = [];
        
        foreach ($this->tableTemplates as $tableType => $template) {
            $tableName = $sanitizedSymbol . $template['suffix'];
            
            try {
                $sql = "DROP TABLE IF EXISTS {$tableName}";
                $this->pdo->exec($sql);
                
                $droppedTables[] = $tableName;
                $this->logger->warning("Dropped table: {$tableName}");
                
            } catch (Exception $e) {
                $this->logger->error("Failed to drop table {$tableName}: " . $e->getMessage());
            }
        }
        
        // Update registry
        $sql = "UPDATE stock_symbol_registry SET tables_created = FALSE WHERE symbol = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$symbol]);
        
        return $droppedTables;
    }
    
    /**
     * Deactivate a symbol (keeps tables but marks as inactive)
     */
    public function deactivateSymbol($symbol)
    {
        $symbol = strtoupper(trim($symbol));
        
        $sql = "UPDATE stock_symbol_registry SET status = 'INACTIVE' WHERE symbol = ?";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([$symbol]);
        
        if ($result) {
            $this->logger->info("Deactivated symbol: {$symbol}");
        }
        
        return $result;
    }
    
    /**
     * Get table statistics for a symbol
     */
    public function getSymbolTableStats($symbol)
    {
        $symbol = strtoupper(trim($symbol));
        $sanitizedSymbol = $this->sanitizeSymbolForTableName($symbol);
        
        $stats = [];
        
        foreach ($this->tableTemplates as $tableType => $template) {
            $tableName = $sanitizedSymbol . $template['suffix'];
            
            try {
                // Check if table exists
                $sql = "SHOW TABLES LIKE ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$tableName]);
                
                if ($stmt->rowCount() > 0) {
                    // Get row count
                    $sql = "SELECT COUNT(*) as row_count FROM {$tableName}";
                    $stmt = $this->pdo->query($sql);
                    $rowCount = $stmt->fetchColumn();
                    
                    // Get table size
                    $sql = "SELECT 
                                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                            FROM information_schema.TABLES 
                            WHERE table_schema = DATABASE() 
                            AND table_name = ?";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$tableName]);
                    $sizeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $stats[$tableType] = [
                        'table_name' => $tableName,
                        'exists' => true,
                        'row_count' => $rowCount,
                        'size_mb' => $sizeInfo['size_mb'] ?? 0
                    ];
                } else {
                    $stats[$tableType] = [
                        'table_name' => $tableName,
                        'exists' => false,
                        'row_count' => 0,
                        'size_mb' => 0
                    ];
                }
            } catch (Exception $e) {
                $stats[$tableType] = [
                    'table_name' => $tableName,
                    'exists' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Sanitize symbol for use in table names
     */
    private function sanitizeSymbolForTableName($symbol)
    {
        // Replace special characters with underscores
        $sanitized = preg_replace('/[^A-Za-z0-9]/', '_', $symbol);
        
        // Ensure it starts with a letter
        if (is_numeric(substr($sanitized, 0, 1))) {
            $sanitized = 'stock_' . $sanitized;
        }
        
        // Convert to lowercase for consistency
        return strtolower($sanitized);
    }
    
    /**
     * Validate stock symbol format
     */
    private function isValidSymbol($symbol)
    {
        // Basic validation - adjust as needed
        return preg_match('/^[A-Z0-9.-]{1,10}$/i', $symbol);
    }
    
    /**
     * Bulk register symbols from array
     */
    public function bulkRegisterSymbols($symbols)
    {
        $results = [
            'success' => [],
            'failed' => []
        ];
        
        foreach ($symbols as $symbolData) {
            $symbol = is_array($symbolData) ? $symbolData['symbol'] : $symbolData;
            $companyData = is_array($symbolData) ? $symbolData : [];
            
            try {
                $this->registerSymbol($symbol, $companyData);
                $results['success'][] = $symbol;
            } catch (Exception $e) {
                $results['failed'][] = [
                    'symbol' => $symbol,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Generate table creation script for a symbol (for backup/migration)
     */
    public function generateTableScript($symbol)
    {
        $symbol = strtoupper(trim($symbol));
        $sanitizedSymbol = $this->sanitizeSymbolForTableName($symbol);
        
        $script = "-- Table creation script for symbol: {$symbol}\n";
        $script .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($this->tableTemplates as $tableType => $template) {
            $tableName = $sanitizedSymbol . $template['suffix'];
            $sql = str_replace('{table_name}', $tableName, $template['schema']);
            
            $script .= "-- {$tableType} table\n";
            $script .= $sql . ";\n\n";
        }
        
        return $script;
    }
}
