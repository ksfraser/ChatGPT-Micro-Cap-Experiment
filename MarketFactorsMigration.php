<?php
/**
 * Market Factors Database Migration
 * 
 * Creates the market factors tables in the stock_market_2 database
 * This includes all tables needed for comprehensive market factor analysis
 */

require_once __DIR__ . '/DatabaseConfig.php';

class MarketFactorsMigration
{
    private $pdo;
    
    public function __construct()
    {
        $this->pdo = DatabaseConfig::createLegacyConnection(); // Uses stock_market_2
    }
    
    /**
     * Run the migration to create all market factors tables
     */
    public function migrate()
    {
        echo "Starting Market Factors Migration for stock_market_2 database...\n";
        
        try {
            $this->pdo->beginTransaction();
            
            // Create all market factor tables
            $this->createMarketFactorsTable();
            $this->createIndexPerformanceTable();
            $this->createForexRatesTable();
            $this->createEconomicIndicatorsTable();
            $this->createSectorPerformanceTable();
            $this->createMarketFactorCorrelationsTable();
            $this->createMarketSentimentTable();
            $this->createMarketFactorHistoryTable();
            $this->createMarketFactorAlertsTable();
            $this->createMarketFactorCategoriesTable();
            
            // Insert initial data
            $this->insertInitialData();
            
            $this->pdo->commit();
            echo "✅ Market Factors Migration completed successfully!\n";
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            echo "❌ Migration failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    /**
     * Create the main market factors table
     */
    private function createMarketFactorsTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS market_factors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                symbol VARCHAR(50) NOT NULL,
                name VARCHAR(255) NOT NULL,
                type ENUM('sector', 'index', 'forex', 'economic', 'sentiment', 'commodity') NOT NULL,
                value DECIMAL(15,6) NOT NULL,
                change_amount DECIMAL(15,6) DEFAULT 0.0,
                change_percent DECIMAL(8,4) DEFAULT 0.0,
                signal_strength DECIMAL(4,2) DEFAULT 0.0,
                age_hours INT DEFAULT 0,
                metadata JSON,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_symbol_type (symbol, type),
                INDEX idx_type (type),
                INDEX idx_symbol (symbol),
                INDEX idx_timestamp (timestamp),
                INDEX idx_signal_strength (signal_strength)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
        echo "✓ Created market_factors table\n";
    }
    
    /**
     * Create index performance tracking table
     */
    private function createIndexPerformanceTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS index_performance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                index_symbol VARCHAR(20) NOT NULL,
                index_name VARCHAR(255) NOT NULL,
                current_value DECIMAL(12,4) NOT NULL,
                change_amount DECIMAL(12,4) DEFAULT 0.0,
                change_percent DECIMAL(8,4) DEFAULT 0.0,
                volume BIGINT DEFAULT 0,
                market_cap BIGINT DEFAULT 0,
                pe_ratio DECIMAL(8,2) DEFAULT 0.0,
                dividend_yield DECIMAL(6,4) DEFAULT 0.0,
                volatility_category ENUM('Very Low', 'Low', 'Moderate', 'High', 'Very High') DEFAULT 'Moderate',
                sector VARCHAR(100),
                country VARCHAR(50) DEFAULT 'US',
                currency VARCHAR(10) DEFAULT 'USD',
                metadata JSON,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_symbol (index_symbol),
                INDEX idx_symbol (index_symbol),
                INDEX idx_volatility (volatility_category),
                INDEX idx_change_percent (change_percent),
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
        echo "✓ Created index_performance table\n";
    }
    
    /**
     * Create forex rates tracking table
     */
    private function createForexRatesTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS forex_rates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                currency_pair VARCHAR(10) NOT NULL,
                base_currency VARCHAR(5) NOT NULL,
                quote_currency VARCHAR(5) NOT NULL,
                current_rate DECIMAL(12,8) NOT NULL,
                bid_rate DECIMAL(12,8) DEFAULT 0.0,
                ask_rate DECIMAL(12,8) DEFAULT 0.0,
                spread DECIMAL(12,8) DEFAULT 0.0,
                change_amount DECIMAL(12,8) DEFAULT 0.0,
                change_percent DECIMAL(8,4) DEFAULT 0.0,
                daily_high DECIMAL(12,8) DEFAULT 0.0,
                daily_low DECIMAL(12,8) DEFAULT 0.0,
                volatility_category ENUM('Very Low', 'Low', 'Moderate', 'High', 'Very High') DEFAULT 'Moderate',
                is_major_pair BOOLEAN DEFAULT FALSE,
                metadata JSON,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_pair (currency_pair),
                INDEX idx_base_currency (base_currency),
                INDEX idx_quote_currency (quote_currency),
                INDEX idx_change_percent (change_percent),
                INDEX idx_major_pairs (is_major_pair),
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
        echo "✓ Created forex_rates table\n";
    }
    
    /**
     * Create economic indicators tracking table
     */
    private function createEconomicIndicatorsTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS economic_indicators (
                id INT AUTO_INCREMENT PRIMARY KEY,
                indicator_code VARCHAR(50) NOT NULL,
                indicator_name VARCHAR(255) NOT NULL,
                country VARCHAR(50) NOT NULL,
                current_value DECIMAL(15,6) NOT NULL,
                previous_value DECIMAL(15,6) DEFAULT 0.0,
                forecast_value DECIMAL(15,6) DEFAULT 0.0,
                change_amount DECIMAL(15,6) DEFAULT 0.0,
                change_percent DECIMAL(8,4) DEFAULT 0.0,
                frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'annual') DEFAULT 'monthly',
                unit VARCHAR(20) DEFAULT '',
                importance ENUM('low', 'medium', 'high') DEFAULT 'medium',
                source VARCHAR(100) DEFAULT '',
                release_date TIMESTAMP NULL,
                next_release_date TIMESTAMP NULL,
                beat_forecast BOOLEAN DEFAULT FALSE,
                surprise_factor DECIMAL(8,4) DEFAULT 0.0,
                market_impact_weight DECIMAL(4,2) DEFAULT 0.5,
                indicator_type ENUM('Leading', 'Lagging', 'Coincident') DEFAULT 'Coincident',
                metadata JSON,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_indicator_country (indicator_code, country),
                INDEX idx_country (country),
                INDEX idx_importance (importance),
                INDEX idx_indicator_type (indicator_type),
                INDEX idx_release_date (release_date),
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
        echo "✓ Created economic_indicators table\n";
    }
    
    /**
     * Create sector performance tracking table
     */
    private function createSectorPerformanceTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS sector_performance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sector_code VARCHAR(20) NOT NULL,
                sector_name VARCHAR(255) NOT NULL,
                current_value DECIMAL(12,4) NOT NULL,
                change_amount DECIMAL(12,4) DEFAULT 0.0,
                change_percent DECIMAL(8,4) DEFAULT 0.0,
                market_cap_weight DECIMAL(6,4) DEFAULT 0.0,
                classification VARCHAR(50) DEFAULT 'GICS',
                top_stocks JSON,
                relative_performance DECIMAL(8,4) DEFAULT 0.0,
                volatility_category ENUM('Very Low', 'Low', 'Moderate', 'High', 'Very High') DEFAULT 'Moderate',
                analyst_rating ENUM('Strong Buy', 'Buy', 'Hold', 'Sell', 'Strong Sell') DEFAULT 'Hold',
                metadata JSON,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_sector_code (sector_code),
                INDEX idx_sector_code (sector_code),
                INDEX idx_change_percent (change_percent),
                INDEX idx_market_cap_weight (market_cap_weight),
                INDEX idx_classification (classification),
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
        echo "✓ Created sector_performance table\n";
    }
    
    /**
     * Create market factor correlations table
     */
    private function createMarketFactorCorrelationsTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS market_factor_correlations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                factor1_id INT NOT NULL,
                factor2_id INT NOT NULL,
                correlation_coefficient DECIMAL(6,4) NOT NULL,
                sample_size INT NOT NULL,
                calculation_period_days INT NOT NULL,
                p_value DECIMAL(8,6) DEFAULT NULL,
                confidence_level DECIMAL(4,2) DEFAULT 95.0,
                correlation_strength ENUM('Very Weak', 'Weak', 'Moderate', 'Strong', 'Very Strong') DEFAULT 'Moderate',
                calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (factor1_id) REFERENCES market_factors(id) ON DELETE CASCADE,
                FOREIGN KEY (factor2_id) REFERENCES market_factors(id) ON DELETE CASCADE,
                UNIQUE KEY unique_correlation (factor1_id, factor2_id),
                INDEX idx_correlation_strength (correlation_strength),
                INDEX idx_calculation_period (calculation_period_days),
                INDEX idx_calculated_at (calculated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
        echo "✓ Created market_factor_correlations table\n";
    }
    
    /**
     * Create market sentiment tracking table
     */
    private function createMarketSentimentTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS market_sentiment (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sentiment_type ENUM('overall', 'sector', 'economic', 'technical') NOT NULL,
                sentiment_score DECIMAL(4,2) NOT NULL, -- -100 to +100
                sentiment_label ENUM('Very Bearish', 'Bearish', 'Neutral', 'Bullish', 'Very Bullish') NOT NULL,
                contributing_factors JSON,
                confidence_level DECIMAL(4,2) DEFAULT 75.0,
                data_sources JSON,
                calculation_method VARCHAR(100),
                time_period VARCHAR(50) DEFAULT 'daily',
                metadata JSON,
                calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sentiment_type (sentiment_type),
                INDEX idx_sentiment_score (sentiment_score),
                INDEX idx_calculated_at (calculated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
        echo "✓ Created market_sentiment table\n";
    }
    
    /**
     * Create market factor history table for time series analysis
     */
    private function createMarketFactorHistoryTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS market_factor_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                factor_id INT NOT NULL,
                value DECIMAL(15,6) NOT NULL,
                change_amount DECIMAL(15,6) DEFAULT 0.0,
                change_percent DECIMAL(8,4) DEFAULT 0.0,
                volume BIGINT DEFAULT 0,
                metadata JSON,
                recorded_date DATE NOT NULL,
                recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (factor_id) REFERENCES market_factors(id) ON DELETE CASCADE,
                UNIQUE KEY unique_factor_date (factor_id, recorded_date),
                INDEX idx_factor_date (factor_id, recorded_date),
                INDEX idx_recorded_date (recorded_date),
                INDEX idx_change_percent (change_percent)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
        echo "✓ Created market_factor_history table\n";
    }
    
    /**
     * Create market factor alerts table
     */
    private function createMarketFactorAlertsTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS market_factor_alerts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                factor_id INT NOT NULL,
                alert_type ENUM('threshold', 'correlation', 'volatility', 'trend') NOT NULL,
                alert_condition VARCHAR(255) NOT NULL,
                threshold_value DECIMAL(15,6) DEFAULT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                is_triggered BOOLEAN DEFAULT FALSE,
                triggered_at TIMESTAMP NULL,
                trigger_value DECIMAL(15,6) DEFAULT NULL,
                notification_sent BOOLEAN DEFAULT FALSE,
                alert_message TEXT,
                metadata JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (factor_id) REFERENCES market_factors(id) ON DELETE CASCADE,
                INDEX idx_factor_active (factor_id, is_active),
                INDEX idx_alert_type (alert_type),
                INDEX idx_triggered (is_triggered),
                INDEX idx_triggered_at (triggered_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
        echo "✓ Created market_factor_alerts table\n";
    }
    
    /**
     * Create market factor categories table for organization
     */
    private function createMarketFactorCategoriesTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS market_factor_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category_name VARCHAR(100) NOT NULL UNIQUE,
                category_description TEXT,
                parent_category_id INT DEFAULT NULL,
                weight DECIMAL(4,2) DEFAULT 1.0,
                is_active BOOLEAN DEFAULT TRUE,
                metadata JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (parent_category_id) REFERENCES market_factor_categories(id) ON DELETE SET NULL,
                INDEX idx_parent_category (parent_category_id),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
        echo "✓ Created market_factor_categories table\n";
    }
    
    /**
     * Insert initial data for market factors
     */
    private function insertInitialData()
    {
        echo "Inserting initial market factor data...\n";
        
        // Insert major market indices
        $this->insertMajorIndices();
        
        // Insert major forex pairs
        $this->insertMajorForexPairs();
        
        // Insert key economic indicators
        $this->insertKeyEconomicIndicators();
        
        // Insert GICS sectors
        $this->insertGICSSectors();
        
        // Insert market factor categories
        $this->insertMarketFactorCategories();
    }
    
    private function insertMajorIndices()
    {
        $indices = [
            ['SPY', 'SPDR S&P 500 ETF', 450.00, 'US', 'USD'],
            ['QQQ', 'Invesco QQQ Trust', 380.00, 'US', 'USD'],
            ['IWM', 'iShares Russell 2000 ETF', 195.00, 'US', 'USD'],
            ['VTI', 'Vanguard Total Stock Market ETF', 220.00, 'US', 'USD'],
            ['EFA', 'iShares MSCI EAFE ETF', 75.00, 'International', 'USD'],
            ['EEM', 'iShares MSCI Emerging Markets ETF', 42.00, 'Emerging', 'USD'],
            ['XIU.TO', 'iShares Core S&P Total Canadian Stock Market Index ETF', 32.00, 'CA', 'CAD'],
            ['TDB902', 'TD Canadian Index Fund', 55.00, 'CA', 'CAD']
        ];
        
        foreach ($indices as $index) {
            $sql = "INSERT IGNORE INTO index_performance 
                    (index_symbol, index_name, current_value, country, currency, volatility_category, metadata) 
                    VALUES (?, ?, ?, ?, ?, 'Moderate', JSON_OBJECT('type', 'ETF'))";
            $this->pdo->prepare($sql)->execute($index);
        }
        
        echo "✓ Inserted major market indices\n";
    }
    
    private function insertMajorForexPairs()
    {
        $forexPairs = [
            ['EURUSD', 'EUR', 'USD', 1.0850, true],
            ['GBPUSD', 'GBP', 'USD', 1.2650, true],
            ['USDJPY', 'USD', 'JPY', 149.50, true],
            ['USDCAD', 'USD', 'CAD', 1.3650, true],
            ['AUDUSD', 'AUD', 'USD', 0.6450, true],
            ['NZDUSD', 'NZD', 'USD', 0.5950, true],
            ['USDCHF', 'USD', 'CHF', 0.9150, true],
            ['EURJPY', 'EUR', 'JPY', 162.25, false],
            ['GBPJPY', 'GBP', 'JPY', 189.15, false],
            ['CADJPY', 'CAD', 'JPY', 109.45, false]
        ];
        
        foreach ($forexPairs as $pair) {
            $sql = "INSERT IGNORE INTO forex_rates 
                    (currency_pair, base_currency, quote_currency, current_rate, is_major_pair, volatility_category, metadata) 
                    VALUES (?, ?, ?, ?, ?, 'Moderate', JSON_OBJECT('session', 'global'))";
            $this->pdo->prepare($sql)->execute($pair);
        }
        
        echo "✓ Inserted major forex pairs\n";
    }
    
    private function insertKeyEconomicIndicators()
    {
        $indicators = [
            ['US_GDP', 'GDP Growth Rate', 'US', 2.8, 'quarterly', '%', 'high', 'Leading'],
            ['US_CPI', 'Consumer Price Index', 'US', 3.2, 'monthly', '%', 'high', 'Lagging'],
            ['US_UNEMPLOYMENT', 'Unemployment Rate', 'US', 3.8, 'monthly', '%', 'high', 'Lagging'],
            ['US_NFP', 'Non-Farm Payrolls', 'US', 185000, 'monthly', 'jobs', 'high', 'Leading'],
            ['US_FED_RATE', 'Federal Funds Rate', 'US', 5.25, 'irregular', '%', 'high', 'Leading'],
            ['CA_GDP', 'GDP Growth Rate', 'CA', 1.8, 'quarterly', '%', 'high', 'Leading'],
            ['CA_CPI', 'Consumer Price Index', 'CA', 2.9, 'monthly', '%', 'high', 'Lagging'],
            ['CA_UNEMPLOYMENT', 'Unemployment Rate', 'CA', 5.2, 'monthly', '%', 'high', 'Lagging'],
            ['CA_BOC_RATE', 'Bank of Canada Rate', 'CA', 4.75, 'irregular', '%', 'high', 'Leading'],
            ['VIX', 'Volatility Index', 'US', 18.5, 'daily', 'index', 'high', 'Leading']
        ];
        
        foreach ($indicators as $indicator) {
            $sql = "INSERT IGNORE INTO economic_indicators 
                    (indicator_code, indicator_name, country, current_value, frequency, unit, importance, indicator_type, metadata) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, JSON_OBJECT('source', 'government'))";
            $this->pdo->prepare($sql)->execute($indicator);
        }
        
        echo "✓ Inserted key economic indicators\n";
    }
    
    private function insertGICSSectors()
    {
        $sectors = [
            ['XLE', 'Energy', 85.50, 4.2, 'GICS'],
            ['XLB', 'Materials', 78.25, 2.8, 'GICS'],
            ['XLI', 'Industrials', 102.75, 8.1, 'GICS'],
            ['XLY', 'Consumer Discretionary', 155.80, 10.5, 'GICS'],
            ['XLP', 'Consumer Staples', 75.40, 6.2, 'GICS'],
            ['XLV', 'Health Care', 125.60, 12.8, 'GICS'],
            ['XLF', 'Financials', 38.90, 11.5, 'GICS'],
            ['XLK', 'Information Technology', 165.45, 22.1, 'GICS'],
            ['XLC', 'Communication Services', 68.75, 8.9, 'GICS'],
            ['XLU', 'Utilities', 72.20, 2.4, 'GICS'],
            ['XLRE', 'Real Estate', 42.85, 2.6, 'GICS']
        ];
        
        foreach ($sectors as $sector) {
            $sql = "INSERT IGNORE INTO sector_performance 
                    (sector_code, sector_name, current_value, market_cap_weight, classification, volatility_category, metadata) 
                    VALUES (?, ?, ?, ?, ?, 'Moderate', JSON_OBJECT('exchange', 'NYSE'))";
            $this->pdo->prepare($sql)->execute($sector);
        }
        
        echo "✓ Inserted GICS sectors\n";
    }
    
    private function insertMarketFactorCategories()
    {
        $categories = [
            ['Economic Indicators', 'Macroeconomic data and indicators', null, 1.0],
            ['Market Indices', 'Stock market indices and benchmarks', null, 1.0],
            ['Currency Exchange', 'Foreign exchange rates and trends', null, 0.8],
            ['Sector Performance', 'Industry and sector performance metrics', null, 0.9],
            ['Market Sentiment', 'Sentiment and volatility indicators', null, 0.7],
            ['Commodities', 'Commodity prices and trends', null, 0.6]
        ];
        
        foreach ($categories as $category) {
            $sql = "INSERT IGNORE INTO market_factor_categories 
                    (category_name, category_description, parent_category_id, weight) 
                    VALUES (?, ?, ?, ?)";
            $this->pdo->prepare($sql)->execute($category);
        }
        
        echo "✓ Inserted market factor categories\n";
    }
    
    /**
     * Drop all market factor tables (for testing/cleanup)
     */
    public function rollback()
    {
        echo "Rolling back Market Factors Migration...\n";
        
        $tables = [
            'market_factor_alerts',
            'market_factor_history',
            'market_factor_correlations',
            'market_sentiment',
            'market_factor_categories',
            'sector_performance',
            'economic_indicators',
            'forex_rates',
            'index_performance',
            'market_factors'
        ];
        
        foreach ($tables as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS $table");
            echo "✓ Dropped $table table\n";
        }
        
        echo "✅ Rollback completed\n";
    }
}

// Command line execution
if (php_sapi_name() === 'cli') {
    $migration = new MarketFactorsMigration();
    
    $command = $argv[1] ?? 'migrate';
    
    switch ($command) {
        case 'migrate':
        case 'up':
            $migration->migrate();
            break;
            
        case 'rollback':
        case 'down':
            $migration->rollback();
            break;
            
        default:
            echo "Usage: php MarketFactorsMigration.php [migrate|rollback]\n";
            exit(1);
    }
}
