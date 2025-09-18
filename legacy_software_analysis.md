# Legacy Financial Software Analysis & Requirements Document

## Executive Summary

This document analyzes three major PERL-based financial software packages from the 2000 era to identify algorithms, data structures, features, and capabilities that are missing from our current PHP-based trading system. The analysis covers:

1. **BeanCounter** - Portfolio performance monitoring and risk analysis
2. **GeniusTrader** - Technical analysis and backtesting framework  
3. **TsInvest** - Shannon probability-based portfolio optimization
4. **qtstalker** - Trading analysis with plugin architecture

## Software Package Analysis

### 1. BeanCounter Portfolio Management System

**Core Capabilities:**
- Multi-exchange portfolio tracking (NYSE, NASDAQ, TSE, LSE, ASX)
- Real-time quote fetching from multiple data sources
- Database-driven portfolio storage and historical tracking
- Risk analysis and performance metrics
- Currency conversion for international portfolios
- Dividend and split tracking
- Tax reporting capabilities

**Key Algorithms & Data Structures:**
- Portfolio performance calculation algorithms
- Risk metrics computation (volatility, beta, correlation)
- Historical data normalization for splits/dividends
- Multi-currency conversion engines
- Quote validation and data cleaning algorithms

**Missing Features in Our System:**
- Multi-exchange support
- Comprehensive dividend tracking
- Advanced risk analysis beyond basic correlation
- Currency conversion capabilities
- Historical split/dividend adjustments
- Tax reporting functionality
- Real-time quote validation and error handling

### 2. GeniusTrader Technical Analysis Framework

**Core Capabilities:**
- Extensive technical indicator library (50+ indicators)
- Backtesting engine with multiple strategies
- Signal generation and alert systems
- Portfolio management with position sizing
- Strategy optimization and walk-forward analysis
- Money management rules
- Charting and visualization

**Key Technical Indicators (Missing from Our System):**
- ADX (Average Directional Index)
- Aroon oscillator
- Bollinger Bands
- MACD variations (Signal, Histogram)
- Parabolic SAR
- Stochastic oscillator variations
- Williams %R
- Commodity Channel Index (CCI)
- Rate of Change (ROC)
- Relative Strength Index (RSI)
- Volume indicators (OBV, Volume Rate of Change)
- Fibonacci retracements

**Advanced Features:**
- Strategy backtesting with slippage and commission modeling
- Walk-forward optimization
- Monte Carlo analysis for strategy validation
- Multi-timeframe analysis
- Signal filtering and confirmation systems
- Position sizing algorithms (fixed fractional, Kelly criterion)
- Stop-loss and take-profit management

**System Architecture:**
- Modular indicator framework
- Plugin-based strategy system
- Configurable data feeds
- Signal alert mechanisms
- Performance reporting engine

### 3. TsInvest Shannon Probability Portfolio Optimizer

**Core Capabilities:**
- Shannon probability-based equity selection
- Multi-equity portfolio optimization
- Statistical estimation techniques for accuracy validation
- Market simulation and scenario testing
- Risk-adjusted return optimization
- Volatility analysis and persistence modeling

**Advanced Mathematical Algorithms:**
- Shannon probability calculation for equity selection
- Statistical estimation accuracy validation
- Hurst exponent calculation for persistence analysis
- Fractal analysis of market behavior
- Optimal portfolio allocation using information theory
- Risk-return optimization with volatility constraints
- Mean reversion and momentum detection algorithms

**Sophisticated Features:**
- Market simulation with configurable scenarios
- Long-term backtesting (100,000+ day simulations)
- Multiple investment strategies:
  - Graph watching (technical analysis)
  - High volatility/noise trading
  - Mean reversion strategies
  - Momentum/persistence strategies
  - Random selection (control strategy)
- Statistical validation of strategy effectiveness
- Run-length analysis for market timing
- Effective Shannon probability computation

**Data Structures:**
- Time series database with hash-based lookups
- Statistical accumulator structures
- Multi-dimensional arrays for equity comparisons
- Persistence and volatility tracking matrices
- Portfolio allocation optimization tables

### 4. qtstalker Trading Analysis Platform

**Core Capabilities:**
- Plugin-based architecture for extensibility
- Multiple data feed support
- Custom indicator development framework
- Chart plotting and technical analysis
- Alert and notification systems
- Data import/export utilities

**Architectural Features:**
- Plugin system for custom indicators
- Database abstraction layer
- Configurable user interface
- Data validation and cleaning
- Export capabilities for analysis

## Required Enhancements to Current System

### 1. Enhanced Technical Indicators Module

**New Indicators to Implement:**
```php
class AdvancedIndicators extends TechnicalIndicators {
    // Trend Indicators
    public function calculateADX($data, $period = 14)
    public function calculateAroon($data, $period = 25)
    public function calculateParabolicSAR($data, $acceleration = 0.02)
    
    // Momentum Indicators
    public function calculateStochastic($data, $kPeriod = 14, $dPeriod = 3)
    public function calculateWilliamsR($data, $period = 14)
    public function calculateCCI($data, $period = 20)
    public function calculateROC($data, $period = 10)
    
    // Volume Indicators
    public function calculateOBV($data)
    public function calculateVolumeROC($data, $period = 10)
    
    // Volatility Indicators
    public function calculateBollingerBands($data, $period = 20, $stdDev = 2)
    public function calculateATR($data, $period = 14)
    
    // Statistical Indicators
    public function calculateShannonProbability($data, $period = 20)
    public function calculateHurstExponent($data, $minPeriod = 10, $maxPeriod = 100)
}
```

### 2. Portfolio Risk Management System

**New Classes Required:**
```php
class PortfolioRiskManager {
    public function calculateVaR($portfolio, $confidence = 0.95, $timeHorizon = 1)
    public function calculateBeta($assetReturns, $marketReturns)
    public function calculateSharpeRatio($returns, $riskFreeRate)
    public function calculateMaxDrawdown($portfolioValues)
    public function calculateCorrelationMatrix($assets)
    public function optimizePortfolio($assets, $constraints, $objective)
}

class CurrencyManager {
    public function convertCurrency($amount, $fromCurrency, $toCurrency)
    public function getExchangeRate($fromCurrency, $toCurrency)
    public function updateExchangeRates()
    public function calculateCurrencyExposure($portfolio)
}

class DividendTracker {
    public function recordDividend($symbol, $amount, $exDate, $payDate)
    public function adjustForSplits($historicalData, $splitRatio, $splitDate)
    public function calculateYield($symbol, $period = 12)
    public function getDividendHistory($symbol, $startDate, $endDate)
}
```

### 3. Advanced Backtesting Engine

**New Backtesting Framework:**
```php
class BacktestEngine {
    public function runBacktest($strategy, $data, $parameters)
    public function calculateSlippage($orderSize, $avgVolume)
    public function applyCommissions($trades, $commissionStructure)
    public function performWalkForwardAnalysis($strategy, $data, $windows)
    public function runMonteCarloSimulation($strategy, $iterations = 1000)
    public function optimizeParameters($strategy, $parameterRanges)
}

class StrategyFramework {
    public function defineEntry($conditions)
    public function defineExit($conditions)
    public function setPositionSizing($method, $parameters)
    public function addStopLoss($type, $value)
    public function addTakeProfit($target)
    public function validateSignals($signals, $filters)
}
```

### 4. Shannon Probability Analysis Module

**Mathematical Analysis Tools:**
```php
class ShannonAnalysis {
    public function calculateShannonProbability($priceData, $window = 20)
    public function estimateAccuracy($probability, $dataSetSize)
    public function calculateEffectiveProbability($measured, $confidence, $sampleSize)
    public function analyzeMarketPersistence($data, $method = 'hurst')
    public function detectMeanReversion($priceData, $threshold = 0.5)
    public function optimizePortfolioAllocation($equities, $method = 'shannon')
}

class MarketSimulator {
    public function generateMarketScenario($equityCount, $days, $volatility)
    public function simulateBearMarket($parameters)
    public function simulateBullMarket($parameters)
    public function simulateCrashScenario($prelude, $crash)
    public function validateSimulation($realData, $simulatedData)
}
```

### 5. Enhanced Data Management

**Data Layer Improvements:**
```php
class MultiExchangeDataManager {
    public function fetchQuotes($symbols, $exchanges)
    public function validateQuoteData($quote, $rules)
    public function normalizeData($data, $exchange)
    public function handleMissingData($timeSeries, $method = 'interpolation')
    public function synchronizeExchangeTimes($data, $targetTimezone)
}

class HistoricalDataManager {
    public function adjustForCorporateActions($data, $actions)
    public function calculateAdjustmentFactors($splits, $dividends)
    public function validateDataIntegrity($timeSeries)
    public function fillDataGaps($data, $method = 'forward_fill')
    public function exportToFormat($data, $format)
}
```

### 6. Alert and Notification System

**Real-time Monitoring:**
```php
class AlertSystem {
    public function createTechnicalAlert($symbol, $indicator, $condition, $value)
    public function createPriceAlert($symbol, $price, $direction)
    public function createVolumeAlert($symbol, $volumeThreshold)
    public function createNewsAlert($keywords, $sources)
    public function sendNotification($alert, $channels)
}

class NotificationChannels {
    public function sendEmail($recipient, $subject, $message)
    public function sendSMS($phoneNumber, $message)
    public function sendWebhook($url, $payload)
    public function pushToApp($userId, $notification)
}
```

### 7. Performance Analytics Dashboard

**Advanced Reporting:**
```php
class PerformanceAnalytics {
    public function generatePerformanceReport($portfolio, $benchmark, $period)
    public function calculateRiskMetrics($returns)
    public function analyzeDrawdowns($portfolioValues)
    public function compareStrategies($strategies, $metrics)
    public function generateTaxReport($transactions, $taxYear)
}

class VisualizationEngine {
    public function createEquityCurve($portfolioValues, $benchmark)
    public function plotDrawdownChart($drawdowns)
    public function createCorrelationHeatmap($correlationMatrix)
    public function generateTechnicalChart($data, $indicators, $signals)
}
```

## Implementation Priority

### Phase 1: Core Infrastructure (4-6 weeks)
1. Enhanced technical indicators library
2. Basic backtesting engine
3. Improved data validation and handling
4. Multi-exchange quote fetching

### Phase 2: Risk Management (3-4 weeks)
1. Portfolio risk metrics
2. Currency conversion system
3. Dividend and split tracking
4. Performance analytics

### Phase 3: Advanced Analytics (6-8 weeks)
1. Shannon probability analysis
2. Market simulation framework
3. Walk-forward optimization
4. Monte Carlo analysis

### Phase 4: User Interface & Alerts (3-4 weeks)
1. Advanced dashboard with new metrics
2. Alert and notification system
3. Strategy comparison tools
4. Export and reporting features

## Database Schema Extensions - Per-Symbol Architecture

Our system uses a per-symbol table architecture where each stock symbol gets its own set of tables to manage file size limits and improve backup performance. This approach creates individual tables for each symbol with consistent suffixes.

### Per-Symbol Table Structure:
Each symbol (e.g., 'AAPL') gets the following tables:
- `AAPL_prices` - Historical price data
- `AAPL_indicators` - Technical indicator values
- `AAPL_dividends` - Dividend history
- `AAPL_splits` - Stock split history
- `AAPL_earnings` - Earnings data
- `AAPL_patterns` - Chart patterns
- `AAPL_support_resistance` - Support/resistance levels
- `AAPL_signals` - Trading signals
- `AAPL_risk_metrics` - Risk analysis data
- `AAPL_shannon` - Shannon probability analysis
- `AAPL_backtests` - Backtesting results
- `AAPL_correlations` - Correlation data with other symbols

### Enhanced Table Templates (StockTableManager.php):

**Enhanced Historical Prices (_{symbol}_prices):**
```sql
CREATE TABLE IF NOT EXISTS {symbol}_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    open DECIMAL(10,4) NOT NULL,
    high DECIMAL(10,4) NOT NULL,
    low DECIMAL(10,4) NOT NULL,
    close DECIMAL(10,4) NOT NULL,
    volume BIGINT NOT NULL DEFAULT 0,
    exchange VARCHAR(10) DEFAULT 'UNKNOWN',
    currency VARCHAR(3) DEFAULT 'USD',
    split_adjusted BOOLEAN DEFAULT TRUE,
    dividend_adjusted BOOLEAN DEFAULT TRUE,
    data_source VARCHAR(50) DEFAULT 'unknown',
    data_quality ENUM('high', 'medium', 'low', 'suspect') DEFAULT 'medium',
    bid DECIMAL(10,4) NULL,
    ask DECIMAL(10,4) NULL,
    vwap DECIMAL(10,4) NULL COMMENT 'Volume Weighted Average Price',
    true_range DECIMAL(10,4) NULL,
    typical_price DECIMAL(10,4) NULL COMMENT '(H+L+C)/3',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_symbol_date (symbol, date),
    INDEX idx_date (date),
    INDEX idx_symbol (symbol),
    INDEX idx_exchange (exchange)
);
```

**Enhanced Technical Indicators (_{symbol}_indicators):**
```sql
CREATE TABLE IF NOT EXISTS {symbol}_indicators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    indicator_name VARCHAR(50) NOT NULL,
    value DECIMAL(15,8) NULL,
    upper_band DECIMAL(15,8) NULL COMMENT 'Bollinger upper band, resistance levels',
    lower_band DECIMAL(15,8) NULL COMMENT 'Bollinger lower band, support levels',
    signal_line DECIMAL(15,8) NULL COMMENT 'MACD signal line, moving average signals',
    histogram DECIMAL(15,8) NULL COMMENT 'MACD histogram, momentum differences',
    plus_di DECIMAL(15,8) NULL COMMENT 'ADX positive directional indicator',
    minus_di DECIMAL(15,8) NULL COMMENT 'ADX negative directional indicator',
    aroon_up DECIMAL(15,8) NULL COMMENT 'Aroon up indicator',
    aroon_down DECIMAL(15,8) NULL COMMENT 'Aroon down indicator',
    k_percent DECIMAL(15,8) NULL COMMENT 'Stochastic %K',
    d_percent DECIMAL(15,8) NULL COMMENT 'Stochastic %D',
    trend_direction ENUM('up', 'down', 'sideways', 'unknown') DEFAULT 'unknown',
    accuracy_score DECIMAL(5,2) NULL COMMENT 'Indicator accuracy percentage',
    confidence_level DECIMAL(5,2) NULL COMMENT 'Statistical confidence level',
    sample_size INT NULL COMMENT 'Sample size used for accuracy calculation',
    indicator_type ENUM('trend', 'momentum', 'volume', 'volatility', 'statistical') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_symbol_date_indicator (symbol, date, indicator_name),
    INDEX idx_date (date),
    INDEX idx_indicator_name (indicator_name),
    INDEX idx_symbol (symbol),
    INDEX idx_trend_direction (trend_direction),
    INDEX idx_indicator_type (indicator_type)
);
```

**Enhanced Dividends (_{symbol}_dividends):**
```sql
CREATE TABLE IF NOT EXISTS {symbol}_dividends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    ex_date DATE NOT NULL,
    pay_date DATE NULL,
    record_date DATE NULL,
    amount DECIMAL(10,6) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    tax_status ENUM('qualified', 'non_qualified', 'exempt', 'unknown') DEFAULT 'unknown',
    yield_on_ex_date DECIMAL(6,4) NULL COMMENT 'Dividend yield on ex-date',
    payout_ratio DECIMAL(6,4) NULL COMMENT 'Percentage of earnings paid as dividend',
    growth_rate DECIMAL(6,4) NULL COMMENT 'Year-over-year dividend growth rate',
    coverage_ratio DECIMAL(6,4) NULL COMMENT 'Earnings coverage ratio',
    franking_credit DECIMAL(6,4) NULL COMMENT 'Tax credit (Australian/UK markets)',
    adjustment_factor DECIMAL(10,6) DEFAULT 1.0 COMMENT 'Historical price adjustment factor',
    dividend_type ENUM('regular', 'special', 'stock', 'liquidating') DEFAULT 'regular',
    frequency ENUM('monthly', 'quarterly', 'semi_annual', 'annual', 'irregular') DEFAULT 'quarterly',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_symbol_ex_date (symbol, ex_date),
    INDEX idx_ex_date (ex_date),
    INDEX idx_pay_date (pay_date),
    INDEX idx_symbol (symbol),
    INDEX idx_dividend_type (dividend_type),
    INDEX idx_frequency (frequency)
);
```

**New Stock Splits Table (_{symbol}_splits):**
```sql
CREATE TABLE IF NOT EXISTS {symbol}_splits (
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
);
```

**New Risk Metrics Table (_{symbol}_risk_metrics):**
```sql
CREATE TABLE IF NOT EXISTS {symbol}_risk_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    calculation_date DATE NOT NULL,
    metric_name VARCHAR(50) NOT NULL,
    metric_value DECIMAL(15,8) NULL,
    confidence_level DECIMAL(5,2) DEFAULT 95.0,
    time_horizon INT DEFAULT 1 COMMENT 'Days',
    sample_period INT DEFAULT 252 COMMENT 'Days used in calculation',
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_metric (symbol, calculation_date, metric_name, time_horizon),
    INDEX idx_calculation_date (calculation_date),
    INDEX idx_metric_name (metric_name),
    INDEX idx_symbol (symbol)
);
```

**New Shannon Analysis Table (_{symbol}_shannon):**
```sql
CREATE TABLE IF NOT EXISTS {symbol}_shannon (
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
    hurst_exponent DECIMAL(6,4) NULL COMMENT 'Market persistence measure',
    persistence_interpretation ENUM('persistent', 'anti_persistent', 'random_walk') NULL,
    mean_reversion_score DECIMAL(6,4) NULL,
    is_mean_reverting BOOLEAN DEFAULT FALSE,
    autocorrelation_lag1 DECIMAL(6,4) NULL,
    half_life DECIMAL(8,2) NULL COMMENT 'Mean reversion half-life in days',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_analysis (symbol, analysis_date, window_size),
    INDEX idx_analysis_date (analysis_date),
    INDEX idx_shannon_probability (shannon_probability),
    INDEX idx_symbol (symbol),
    INDEX idx_persistence (persistence_interpretation)
);
```

**New Backtesting Results Table (_{symbol}_backtests):**
```sql
CREATE TABLE IF NOT EXISTS {symbol}_backtests (
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
    benchmark_return DECIMAL(8,4) NULL COMMENT 'Benchmark performance',
    alpha DECIMAL(6,4) NULL COMMENT 'Excess return over benchmark',
    beta DECIMAL(6,4) NULL COMMENT 'Market sensitivity',
    tracking_error DECIMAL(6,4) NULL COMMENT 'Standard deviation of excess returns',
    information_ratio DECIMAL(6,4) NULL COMMENT 'Alpha divided by tracking error',
    calmar_ratio DECIMAL(6,4) NULL COMMENT 'Return over max drawdown',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_strategy_name (strategy_name),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date),
    INDEX idx_symbol (symbol),
    INDEX idx_total_return (total_return),
    INDEX idx_sharpe_ratio (sharpe_ratio)
);
```

**New Correlation Data Table (_{symbol}_correlations):**
```sql
CREATE TABLE IF NOT EXISTS {symbol}_correlations (
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
    covariance DECIMAL(12,8) NULL COMMENT 'Covariance between assets',
    r_squared DECIMAL(6,4) NULL COMMENT 'Coefficient of determination',
    beta_coefficient DECIMAL(8,6) NULL COMMENT 'Regression beta',
    alpha_coefficient DECIMAL(8,6) NULL COMMENT 'Regression alpha',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_correlation (symbol, comparison_symbol, calculation_date, sample_period, correlation_type),
    INDEX idx_calculation_date (calculation_date),
    INDEX idx_comparison_symbol (comparison_symbol),
    INDEX idx_correlation_coefficient (correlation_coefficient),
    INDEX idx_symbol (symbol)
);
```

### Table Management:
The `StockTableManager.php` class handles automatic creation of these per-symbol tables when new symbols are added to the system. Each table follows the naming convention: `{SYMBOL}_{suffix}` where the suffix identifies the data type.

## API Endpoints to Add

### New REST API endpoints:
```php
// Advanced Technical Indicators
GET /api/indicators/{symbol}/adx
GET /api/indicators/{symbol}/bollinger-bands
GET /api/indicators/{symbol}/stochastic
GET /api/indicators/{symbol}/shannon-probability

// Portfolio Risk Management
GET /api/portfolio/{id}/risk-metrics
GET /api/portfolio/{id}/correlation-matrix
GET /api/portfolio/{id}/var-analysis
POST /api/portfolio/{id}/optimize

// Backtesting
POST /api/backtest/strategy
GET /api/backtest/{id}/results
GET /api/backtest/{id}/trades
POST /api/backtest/walk-forward

// Multi-Exchange Data
GET /api/quotes/{symbol}/all-exchanges
GET /api/exchanges/{exchange}/symbols
GET /api/currency/convert/{from}/{to}

// Alerts and Notifications
POST /api/alerts/create
GET /api/alerts/active
PUT /api/alerts/{id}/toggle
DELETE /api/alerts/{id}
```

## Configuration Files

### New configuration options:
```php
// config/advanced_features.php
return [
    'exchanges' => [
        'NYSE' => ['timezone' => 'America/New_York', 'currency' => 'USD'],
        'TSX' => ['timezone' => 'America/Toronto', 'currency' => 'CAD'],
        'LSE' => ['timezone' => 'Europe/London', 'currency' => 'GBP'],
    ],
    
    'indicators' => [
        'default_periods' => [
            'adx' => 14,
            'stochastic' => ['k' => 14, 'd' => 3],
            'bollinger' => ['period' => 20, 'std_dev' => 2],
        ],
        'cache_duration' => 300, // 5 minutes
    ],
    
    'backtesting' => [
        'default_commission' => 0.001, // 0.1%
        'default_slippage' => 0.0005,  // 0.05%
        'max_simulation_days' => 10000,
    ],
    
    'risk_management' => [
        'var_confidence_levels' => [0.95, 0.99],
        'max_position_size' => 0.1, // 10% of portfolio
        'correlation_threshold' => 0.7,
    ],
    
    'alerts' => [
        'max_alerts_per_user' => 100,
        'notification_channels' => ['email', 'sms', 'webhook'],
        'rate_limits' => ['email' => 10, 'sms' => 5], // per hour
    ]
];
```

## Testing Strategy

### Comprehensive test coverage for new features:
1. Unit tests for all new indicator calculations
2. Integration tests for backtesting engine
3. Performance tests for Shannon probability calculations
4. Validation tests against known financial datasets
5. Regression tests for portfolio optimization
6. Load tests for multi-exchange data handling

This analysis provides a roadmap for significantly enhancing our current trading system with proven algorithms and features from mature financial software packages. The implementation should be done in phases to ensure stability and proper testing of each component.
