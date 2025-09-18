<?php
/**
 * Market Factors Database Schema Creator
 * Creates all market factors tables in stock_market_2 database
 */

require_once __DIR__ . '/DatabaseConfig.php';

function createMarketFactorsSchema() {
    try {
        echo "Connecting to stock_market_2 database...\n";
        $pdo = DatabaseConfig::createLegacyConnection();
        
        if (!$pdo) {
            throw new Exception("Failed to connect to database");
        }
        
        echo "✅ Connected to database\n";
        
        // Start transaction
        $pdo->beginTransaction();
        
        // 1. Create main market_factors table
        echo "Creating market_factors table...\n";
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
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sql);
        echo "✅ Created market_factors table\n";
        
        // 2. Create index_performance table
        echo "Creating index_performance table...\n";
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
                volatility_category ENUM('Very Low', 'Low', 'Moderate', 'High', 'Very High') DEFAULT 'Moderate',
                country VARCHAR(50) DEFAULT 'US',
                currency VARCHAR(10) DEFAULT 'USD',
                metadata JSON,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_symbol (index_symbol),
                INDEX idx_symbol (index_symbol),
                INDEX idx_change_percent (change_percent)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sql);
        echo "✅ Created index_performance table\n";
        
        // 3. Create forex_rates table
        echo "Creating forex_rates table...\n";
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
                volatility_category ENUM('Very Low', 'Low', 'Moderate', 'High', 'Very High') DEFAULT 'Moderate',
                is_major_pair BOOLEAN DEFAULT FALSE,
                metadata JSON,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_pair (currency_pair),
                INDEX idx_base_currency (base_currency),
                INDEX idx_change_percent (change_percent)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sql);
        echo "✅ Created forex_rates table\n";
        
        // 4. Create economic_indicators table
        echo "Creating economic_indicators table...\n";
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
                beat_forecast BOOLEAN DEFAULT FALSE,
                surprise_factor DECIMAL(8,4) DEFAULT 0.0,
                indicator_type ENUM('Leading', 'Lagging', 'Coincident') DEFAULT 'Coincident',
                metadata JSON,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_indicator_country (indicator_code, country),
                INDEX idx_country (country),
                INDEX idx_importance (importance)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sql);
        echo "✅ Created economic_indicators table\n";
        
        // 5. Create sector_performance table
        echo "Creating sector_performance table...\n";
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
                metadata JSON,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_sector_code (sector_code),
                INDEX idx_sector_code (sector_code),
                INDEX idx_change_percent (change_percent)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sql);
        echo "✅ Created sector_performance table\n";
        
        // 6. Create market_factor_correlations table
        echo "Creating market_factor_correlations table...\n";
        $sql = "
            CREATE TABLE IF NOT EXISTS market_factor_correlations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                factor1_symbol VARCHAR(50) NOT NULL,
                factor2_symbol VARCHAR(50) NOT NULL,
                correlation_coefficient DECIMAL(6,4) NOT NULL,
                sample_size INT NOT NULL,
                calculation_period_days INT NOT NULL,
                p_value DECIMAL(8,6) DEFAULT NULL,
                correlation_strength ENUM('Very Weak', 'Weak', 'Moderate', 'Strong', 'Very Strong') DEFAULT 'Moderate',
                calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_correlation (factor1_symbol, factor2_symbol),
                INDEX idx_correlation_strength (correlation_strength),
                INDEX idx_calculated_at (calculated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sql);
        echo "✅ Created market_factor_correlations table\n";
        
        // 7. Create market_sentiment table
        echo "Creating market_sentiment table...\n";
        $sql = "
            CREATE TABLE IF NOT EXISTS market_sentiment (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sentiment_type ENUM('overall', 'sector', 'economic', 'technical') NOT NULL,
                sentiment_score DECIMAL(4,2) NOT NULL,
                sentiment_label ENUM('Very Bearish', 'Bearish', 'Neutral', 'Bullish', 'Very Bullish') NOT NULL,
                contributing_factors JSON,
                confidence_level DECIMAL(4,2) DEFAULT 75.0,
                calculation_method VARCHAR(100),
                time_period VARCHAR(50) DEFAULT 'daily',
                metadata JSON,
                calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sentiment_type (sentiment_type),
                INDEX idx_sentiment_score (sentiment_score)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sql);
        echo "✅ Created market_sentiment table\n";
        
        // Commit transaction
        $pdo->commit();
        
        echo "\n🎉 Market Factors Database Schema Created Successfully!\n";
        echo "Database: stock_market_2\n";
        echo "Tables created: 7\n";
        
        // Show table summary
        showTableSummary($pdo);
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        echo "❌ Error: " . $e->getMessage() . "\n";
        throw $e;
    }
}

function showTableSummary($pdo) {
    echo "\n📊 Table Summary:\n";
    
    $tables = [
        'market_factors',
        'index_performance', 
        'forex_rates',
        'economic_indicators',
        'sector_performance',
        'market_factor_correlations',
        'market_sentiment'
    ];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  $table: " . $result['count'] . " records\n";
    }
}

// Run the schema creation
if (php_sapi_name() === 'cli') {
    createMarketFactorsSchema();
}
