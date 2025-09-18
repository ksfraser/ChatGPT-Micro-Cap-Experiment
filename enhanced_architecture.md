# Enhanced Financial Analysis System Architecture

## Overview

This document outlines the updated architecture for our enhanced financial analysis system, incorporating advanced features from legacy PERL financial software while maintaining our per-symbol table structure for optimal performance and backup management.

## Core Architecture Principles

### 1. Per-Symbol Database Architecture

Our system uses a distributed table approach where each stock symbol gets its own set of tables to:
- **Manage file size limits**: Large datasets are split across multiple smaller tables
- **Improve backup performance**: Individual symbol data can be backed up independently
- **Enable parallel processing**: Multiple symbols can be analyzed concurrently
- **Reduce query complexity**: Symbol-specific queries run faster on smaller datasets

**Table Naming Convention:**
```
{SYMBOL}_{TABLE_TYPE}

Examples:
- AAPL_prices (Apple historical price data)
- MSFT_indicators (Microsoft technical indicators)
- GOOGL_dividends (Google dividend history)
- TSLA_risk_metrics (Tesla risk analysis data)
```

### 2. Modular Service Architecture

The system is built using a modular service architecture with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                    Web Dashboard                            │
│                 (Professional UI)                          │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                  API Controller Layer                      │
│            (Enhanced REST Endpoints)                       │
└─────────────────────────────────────────────────────────────┘
                              │
┌──────────────┬──────────────┬──────────────┬──────────────┐
│   Advanced   │   Portfolio  │   Shannon    │  Backtest    │
│  Technical   │     Risk     │   Analysis   │   Engine     │
│  Indicators  │   Manager    │   Service    │   Service    │
└──────────────┴──────────────┴──────────────┴──────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│              Stock Table Manager                           │
│         (Per-Symbol Table Management)                      │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                MySQL Database Layer                        │
│         (Per-Symbol Table Architecture)                    │
└─────────────────────────────────────────────────────────────┘
```

## Database Schema Design

### Per-Symbol Table Types

Each symbol in the system gets the following set of tables:

#### 1. Core Market Data Tables
- **`{symbol}_prices`**: Enhanced historical price data with multi-exchange support
- **`{symbol}_indicators`**: Technical indicator values with accuracy tracking
- **`{symbol}_dividends`**: Comprehensive dividend history with tax information
- **`{symbol}_splits`**: Stock split history with adjustment factors
- **`{symbol}_earnings`**: Earnings data and estimates

#### 2. Analysis and Pattern Tables
- **`{symbol}_patterns`**: Chart pattern recognition results
- **`{symbol}_support_resistance`**: Support and resistance level tracking
- **`{symbol}_signals`**: Trading signal generation and alerts

#### 3. Advanced Analytics Tables
- **`{symbol}_risk_metrics`**: Risk analysis data (VaR, beta, volatility, etc.)
- **`{symbol}_shannon`**: Shannon probability analysis and market persistence
- **`{symbol}_backtests`**: Strategy backtesting results
- **`{symbol}_correlations`**: Correlation analysis with other symbols

### Enhanced Field Specifications

#### Historical Prices Enhancement
```sql
-- Multi-exchange support with data quality tracking
{symbol}_prices:
- exchange VARCHAR(10): Trading exchange (NYSE, NASDAQ, TSX, etc.)
- currency VARCHAR(3): Base currency for the price data
- data_source VARCHAR(50): Data provider identification
- data_quality ENUM: Quality rating (high, medium, low, suspect)
- bid/ask DECIMAL: Bid-ask spread data
- vwap DECIMAL: Volume Weighted Average Price
- true_range DECIMAL: True Range for volatility calculations
- split_adjusted/dividend_adjusted BOOLEAN: Adjustment flags
```

#### Technical Indicators Enhancement
```sql
-- Advanced indicator components
{symbol}_indicators:
- upper_band/lower_band: Bollinger Bands, resistance/support levels
- signal_line: MACD signal line, moving average crossovers
- histogram: MACD histogram, momentum differences
- plus_di/minus_di: ADX directional indicators
- aroon_up/aroon_down: Aroon oscillator components
- k_percent/d_percent: Stochastic oscillator values
- accuracy_score/confidence_level: Statistical validation
- trend_direction ENUM: Simplified trend classification
- indicator_type ENUM: Category classification
```

#### Dividend Tracking Enhancement
```sql
-- Comprehensive dividend analysis
{symbol}_dividends:
- tax_status ENUM: Tax classification (qualified, non-qualified, etc.)
- yield_on_ex_date: Dividend yield at ex-date
- payout_ratio: Percentage of earnings paid as dividend
- growth_rate: Year-over-year dividend growth
- coverage_ratio: Earnings coverage analysis
- franking_credit: Tax credits (Australian/UK markets)
- adjustment_factor: Historical price adjustment multiplier
```

## Service Layer Architecture

### 1. AdvancedTechnicalIndicators Service

**Purpose**: Implements sophisticated technical analysis algorithms from legacy systems

**Key Methods**:
```php
class AdvancedTechnicalIndicators {
    // Trend Analysis
    public function calculateADX($symbol, $period = 14)
    public function calculateAroon($symbol, $period = 25)
    public function calculateParabolicSAR($symbol, $acceleration = 0.02)
    
    // Momentum Indicators
    public function calculateStochastic($symbol, $kPeriod = 14, $dPeriod = 3)
    public function calculateWilliamsR($symbol, $period = 14)
    public function calculateCCI($symbol, $period = 20)
    public function calculateROC($symbol, $period = 10)
    
    // Volume Analysis
    public function calculateOBV($symbol)
    public function calculateVolumeROC($symbol, $period = 10)
    
    // Volatility Indicators
    public function calculateBollingerBands($symbol, $period = 20, $stdDev = 2)
    public function calculateATR($symbol, $period = 14)
    
    // Statistical Analysis
    public function calculateShannonProbability($symbol, $period = 20)
    public function calculateIndicatorAccuracy($symbol, $indicator, $lookback = 252)
}
```

### 2. PortfolioRiskManager Service

**Purpose**: Professional-grade risk analysis and portfolio optimization

**Key Methods**:
```php
class PortfolioRiskManager {
    // Risk Metrics
    public function calculateVaR($symbols, $confidence = 0.95, $timeHorizon = 1)
    public function calculateExpectedShortfall($symbols, $confidence = 0.95)
    public function calculateBeta($symbol, $marketSymbol = 'SPY')
    public function calculateSharpeRatio($symbol, $riskFreeRate = 0.02)
    public function calculateSortinoRatio($symbol, $targetReturn = 0)
    public function calculateMaxDrawdown($symbol, $period = 252)
    
    // Portfolio Analysis
    public function calculateCorrelationMatrix($symbols, $period = 252)
    public function optimizePortfolio($symbols, $constraints, $objective = 'sharpe')
    public function calculatePortfolioVaR($portfolio, $confidence = 0.95)
    public function generateRiskReport($portfolio, $benchmark = null)
}
```

### 3. ShannonAnalysis Service

**Purpose**: Information theory-based market analysis from TsInvest system

**Key Methods**:
```php
class ShannonAnalysis {
    // Core Shannon Analysis
    public function calculateShannonProbability($symbol, $window = 20)
    public function calculateEffectiveProbability($measured, $confidence, $sampleSize)
    public function estimateAccuracy($probability, $dataSetSize)
    
    // Market Behavior Analysis
    public function calculateHurstExponent($symbol, $minPeriod = 10, $maxPeriod = 100)
    public function analyzeMarketPersistence($symbol, $method = 'hurst')
    public function detectMeanReversion($symbol, $threshold = 0.5)
    public function calculateAutocorrelation($symbol, $maxLags = 20)
    
    // Portfolio Optimization
    public function optimizePortfolioAllocation($symbols, $method = 'shannon')
    public function calculateKellyOptimalFraction($symbol)
    public function generateMarketScenarios($symbols, $scenarios = 1000)
}
```

### 4. BacktestEngine Service

**Purpose**: Comprehensive strategy backtesting with advanced analytics

**Key Methods**:
```php
class BacktestEngine {
    // Core Backtesting
    public function runBacktest($symbol, $strategy, $parameters)
    public function performWalkForwardAnalysis($symbol, $strategy, $windows)
    public function runMonteCarloSimulation($symbol, $strategy, $iterations = 1000)
    
    // Performance Analysis
    public function calculatePerformanceMetrics($results)
    public function generateEquityCurve($trades)
    public function analyzeTradingPattern($trades)
    public function compareStrategies($results)
    
    // Risk Management
    public function calculateSlippage($orderSize, $avgVolume)
    public function applyCommissions($trades, $commissionStructure)
    public function simulateMarketImpact($trades, $marketData)
}
```

## API Layer Enhancement

### Enhanced REST Endpoints

#### Advanced Technical Indicators
```
GET /api/indicators/{symbol}/adx
GET /api/indicators/{symbol}/bollinger-bands
GET /api/indicators/{symbol}/stochastic
GET /api/indicators/{symbol}/shannon-probability
GET /api/indicators/{symbol}/accuracy/{indicator}
```

#### Portfolio Risk Management
```
GET /api/portfolio/{id}/risk-metrics
GET /api/portfolio/{id}/correlation-matrix
GET /api/portfolio/{id}/var-analysis
POST /api/portfolio/{id}/optimize
GET /api/portfolio/{id}/performance-attribution
```

#### Backtesting and Analysis
```
POST /api/backtest/strategy
GET /api/backtest/{id}/results
GET /api/backtest/{id}/equity-curve
POST /api/backtest/walk-forward
POST /api/backtest/monte-carlo
```

#### Multi-Exchange Data
```
GET /api/quotes/{symbol}/all-exchanges
GET /api/exchanges/{exchange}/symbols
GET /api/currency/convert/{from}/{to}
GET /api/data-quality/{symbol}/report
```

#### Shannon Probability Analysis
```
GET /api/shannon/{symbol}/probability
GET /api/shannon/{symbol}/hurst-exponent
GET /api/shannon/{symbol}/mean-reversion
POST /api/shannon/portfolio/optimize
```

## Data Management Layer

### StockTableManager Enhancement

The `StockTableManager.php` class has been enhanced to support the new per-symbol architecture:

**Key Features**:
- **Automatic table creation**: Creates all required tables when adding a new symbol
- **Schema management**: Maintains consistent table structures across all symbols
- **Migration support**: Handles schema updates across multiple symbol tables
- **Backup optimization**: Enables symbol-specific backup and restore operations

**Table Template System**:
```php
class StockTableManager {
    private $tableTemplates = [
        'prices' => ['suffix' => '_prices', 'schema' => '...'],
        'indicators' => ['suffix' => '_indicators', 'schema' => '...'],
        'dividends' => ['suffix' => '_dividends', 'schema' => '...'],
        'splits' => ['suffix' => '_splits', 'schema' => '...'],
        'risk_metrics' => ['suffix' => '_risk_metrics', 'schema' => '...'],
        'shannon' => ['suffix' => '_shannon', 'schema' => '...'],
        'backtests' => ['suffix' => '_backtests', 'schema' => '...'],
        'correlations' => ['suffix' => '_correlations', 'schema' => '...']
    ];
}
```

## Performance Optimization

### 1. Query Optimization
- **Symbol-specific indexes**: Each table has optimized indexes for common queries
- **Composite indexes**: Multi-column indexes for complex analytical queries
- **Partitioning strategy**: Date-based partitioning for large historical datasets

### 2. Caching Strategy
- **Indicator caching**: Computed indicators cached to avoid recalculation
- **Query result caching**: Frequently accessed data cached in memory
- **Progressive loading**: Large datasets loaded incrementally

### 3. Parallel Processing
- **Multi-symbol analysis**: Concurrent processing of multiple symbols
- **Background calculations**: Heavy computations run asynchronously
- **Batch operations**: Bulk updates for efficiency

## Security and Data Integrity

### 1. Data Validation
- **Input sanitization**: All user inputs validated and sanitized
- **Data quality checks**: Automated validation of market data integrity
- **Anomaly detection**: Statistical outlier detection in price data

### 2. Access Control
- **Role-based permissions**: Granular access control for different user types
- **API rate limiting**: Protection against abuse and excessive usage
- **Audit logging**: Complete audit trail of all system operations

### 3. Backup and Recovery
- **Symbol-level backups**: Individual symbol data can be backed up independently
- **Incremental backups**: Only changed data backed up for efficiency
- **Point-in-time recovery**: Ability to restore to specific timestamps

## Monitoring and Alerting

### 1. Performance Monitoring
- **Query performance tracking**: Monitor slow queries and optimization opportunities
- **Resource utilization**: Track CPU, memory, and disk usage
- **Service health checks**: Automated monitoring of all services

### 2. Business Logic Monitoring
- **Data quality alerts**: Notification of data anomalies or missing data
- **Calculation accuracy**: Monitoring of indicator accuracy over time
- **System capacity**: Alerts for approaching system limits

### 3. User Activity Monitoring
- **Usage analytics**: Track feature usage and user behavior
- **Error tracking**: Monitor and alert on application errors
- **Performance metrics**: User experience monitoring

## Deployment and Scaling

### 1. Horizontal Scaling
- **Database sharding**: Symbol-based sharding for horizontal scaling
- **Load balancing**: Distribution of API requests across multiple servers
- **Service decomposition**: Individual services can be scaled independently

### 2. Vertical Scaling
- **Resource optimization**: Efficient use of CPU and memory resources
- **Database tuning**: MySQL optimization for analytical workloads
- **Cache optimization**: Intelligent caching strategies

### 3. Cloud Deployment
- **Container support**: Docker containers for consistent deployment
- **Auto-scaling**: Automatic scaling based on demand
- **Multi-region support**: Geographic distribution for performance

## Future Enhancements

### 1. Machine Learning Integration
- **Predictive models**: Integration of ML models for price prediction
- **Pattern recognition**: Automated chart pattern detection
- **Sentiment analysis**: News and social media sentiment integration

### 2. Real-time Processing
- **Streaming data**: Real-time market data processing
- **Event-driven architecture**: React to market events in real-time
- **Low-latency indicators**: Ultra-fast indicator calculations

### 3. Advanced Analytics
- **Alternative data**: Integration of non-traditional data sources
- **Cross-asset analysis**: Analysis across multiple asset classes
- **Factor models**: Multi-factor risk models and attribution

This enhanced architecture provides a robust foundation for professional-grade financial analysis while maintaining the performance benefits of our per-symbol table approach. The modular design allows for easy extension and maintenance as new features are added.
