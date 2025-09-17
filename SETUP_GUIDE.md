# Trading System Setup and Integration Guide

## Overview

This document provides comprehensive instructions for setting up and using the ChatGPT Micro-Cap Trading Experiment system with the new SOLID Finance architecture and trading strategy integration.

## System Components

### 1. SOLID Finance Package (`src/Ksfraser/Finance/`)
- **Controllers**: Web interface and API endpoints
- **Services**: Business logic for strategies, portfolio, backtesting
- **Interfaces**: Clean abstractions for data sources and repositories
- **Integration**: Connects original PHP strategies with modern architecture
- **Views**: Modern web UI for trading operations

### 2. Trading Strategies (`src/Ksfraser/Finance/2000/strategies/`)
- **turtle.php**: Complete Turtle Trading System implementation
- **buyLeadingStocksAtSupport.php**: Support/resistance trading
- **macrossover.php**: Moving average crossover strategies
- **fourweekrule.php**: Four-week breakout rule
- **strategiesConstants.php**: Trading action constants

### 3. Database Schema (`database_schema.sql`)
- Comprehensive tables for strategies, executions, backtesting, portfolio management
- Views and stored procedures for common operations
- Audit logging and performance tracking

## Installation Steps

### 1. Database Setup

```sql
-- Create the database
CREATE DATABASE trading_system;
USE trading_system;

-- Import the schema
SOURCE database_schema.sql;
```

### 2. Python Dependencies

```bash
# Install the enhanced requirements
pip install -r requirements.txt

# Note: TA-Lib requires additional setup on Windows:
# Download appropriate wheel from: https://www.lfd.uci.edu/~gohlke/pythonlibs/#ta-lib
# pip install TA_Lib-0.4.24-cp39-cp39-win_amd64.whl
```

### 3. PHP Dependencies

The system integrates with your existing PHP environment. Ensure you have:

- PHP 8.1+ with required extensions
- Existing DatabaseConfig and ApiConfig classes
- Symfony Session Component (already installed)

### 4. Configuration

#### Update DatabaseConfig.php
Add the Finance configuration method if not already present:

```php
public function getFinanceConfig(): array
{
    return [
        'default_risk_percentage' => 2.0,
        'max_portfolio_risk' => 10.0,
        'commission_per_trade' => 1.00
    ];
}
```

#### Update ApiConfig.php
Ensure external API configurations are properly set:

```yaml
# api_config.yml
openai:
  api_key: "your_openai_api_key"
  model: "gpt-4"

alpha_vantage:
  api_key: "your_alpha_vantage_key"
  
yahoo_finance:
  # No API key required
  rate_limit: 2000  # requests per hour
```

### 5. Web Server Configuration

Add the following to your web server configuration (Apache/Nginx):

```apache
# Apache .htaccess for /finance routes
RewriteEngine On
RewriteBase /finance/

# Route all finance requests through the router
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ src/Ksfraser/Finance/routes.php [QSA,L]
```

## Usage Guide

### 1. Access the Dashboard

Navigate to: `http://your-domain/finance/dashboard`

The dashboard provides:
- Portfolio overview and performance metrics
- Active strategy status
- Recent trading signals
- Quick action buttons for strategy execution and backtesting

### 2. Strategy Management

**Available Strategies:**
- **Turtle System 1**: 20-day breakout entry, 10-day exit
- **Turtle System 2**: 55-day breakout entry, 20-day exit
- **Support/Resistance**: Buy leading stocks at support levels
- **MA Crossover**: Moving average crossover signals
- **Four Week Rule**: 4-week high/low breakout system

**Strategy Configuration:**
```json
{
  "entry_days": 20,
  "exit_days": 10,
  "atr_period": 20,
  "unit_risk": 0.02,
  "max_units": 4
}
```

### 3. Executing Strategies

#### Manual Execution
1. Go to Dashboard → Execute Strategy
2. Select strategy and symbol
3. Optionally override parameters
4. Click Execute

#### Programmatic Execution
```php
use Ksfraser\Finance\Integration\StrategyIntegration;

$strategyIntegration = $container->get(StrategyIntegration::class);

// Execute single strategy
$result = $strategyIntegration->executeStrategy(1, 'AAPL', [
    'unit_risk' => 0.025  // Override default 2% risk
]);

// Scan multiple symbols
$results = $strategyIntegration->scanMarket(['AAPL', 'MSFT', 'GOOGL']);
```

### 4. Backtesting

#### Web Interface
1. Navigate to Finance → Backtest
2. Select strategy, symbol, date range
3. Set initial capital
4. Run backtest

#### API Usage
```javascript
const backtestData = {
    strategy_id: 1,
    symbol: 'AAPL',
    start_date: '2022-01-01',
    end_date: '2023-12-31',
    initial_capital: 100000
};

fetch('/finance/api/backtest', {
    method: 'POST',
    body: JSON.stringify(backtestData)
})
.then(response => response.json())
.then(data => console.log(data));
```

### 5. Portfolio Management

Monitor and manage positions through:
- Real-time P&L tracking
- Risk metrics and exposure analysis
- Position sizing recommendations
- Stop-loss and take-profit management

## Strategy Integration Details

### Original PHP Strategies
The system preserves and enhances your original trading strategies:

#### Turtle System (`turtle.php`)
- `enter_1()`: 20-day breakout entry
- `enter_2()`: 55-day breakout entry  
- `exit_1()`: 10-day breakdown exit
- `exit_2()`: 20-day breakdown exit
- Automatic position sizing based on ATR

#### Support/Resistance (`buyLeadingStocksAtSupport.php`)
- Dynamic support level detection
- 50-day moving average confirmation
- Volume validation
- Leading stock identification

### Integration Layer
The `StrategyIntegration` class provides:
- Unified interface for all strategies
- Consistent signal generation
- Parameter management
- Performance tracking
- Error handling and logging

### Trading Constants
```php
// From strategiesConstants.php
const BUY = 10;
const SELL = 20;
const HOLD = 30;
const SHORT = 40;
const COVER = 50;
```

## API Reference

### Dashboard API
- `GET /finance/api/dashboard` - Get dashboard data
- `GET /finance/api/strategies` - List all strategies
- `POST /finance/api/execute-strategy` - Execute a strategy
- `POST /finance/api/backtest` - Run backtest
- `GET /finance/api/market-data` - Get market data

### Strategy Integration API
- `POST /finance/api/scan-market` - Scan multiple symbols with strategies

### Request/Response Examples

#### Execute Strategy
```json
// Request
{
    "strategy_id": 1,
    "symbol": "AAPL",
    "parameters": {
        "unit_risk": 0.025,
        "entry_days": 25
    }
}

// Response
{
    "success": true,
    "data": {
        "strategy": {...},
        "symbol": "AAPL",
        "signal": {
            "action": "BUY",
            "price": 150.25,
            "stop_loss": 145.50,
            "confidence": 0.8,
            "reasoning": "Turtle 25-day breakout..."
        }
    }
}
```

## Performance Optimization

### Database Indexing
The schema includes optimized indexes for:
- Symbol and date lookups
- Strategy performance queries
- Portfolio calculations
- Historical data analysis

### Caching Strategy
Implement caching for:
- Market data (5-minute cache)
- Strategy parameters (1-hour cache)
- Performance metrics (15-minute cache)

### Rate Limiting
Respect API limits:
- Yahoo Finance: 2000 requests/hour
- Alpha Vantage: 5 calls/minute
- OpenAI: Based on your plan

## Monitoring and Maintenance

### Log Files
Monitor these log files:
- Strategy execution logs
- API call logs
- Error and exception logs
- Performance metrics logs

### Regular Maintenance
- Update market data daily
- Review strategy performance weekly
- Backup database regularly
- Monitor API usage and costs

### Health Checks
Implement monitoring for:
- Database connectivity
- API availability
- Strategy execution success rates
- Data quality and completeness

## Troubleshooting

### Common Issues

1. **No Market Data**
   - Check API configurations
   - Verify network connectivity
   - Review API key validity

2. **Strategy Execution Errors**
   - Check parameter formats
   - Verify sufficient historical data
   - Review error logs for details

3. **Performance Issues**
   - Check database indexes
   - Monitor query execution times
   - Review caching implementation

### Debug Mode
Enable debug logging:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Future Enhancements

### Planned Features
- Real-time alerts and notifications
- Advanced charting capabilities
- Machine learning integration
- Options trading strategies
- Cryptocurrency support

### Extension Points
- Additional data sources
- Custom strategy builders
- Advanced risk management
- Portfolio optimization
- Social trading features

## Support and Documentation

For additional support:
1. Review the comprehensive requirements document (`TRADING_REQUIREMENTS.md`)
2. Check the database schema documentation
3. Examine the example code in the integration layer
4. Review the original strategy implementations

The system is designed to be modular, extensible, and maintainable while preserving the proven trading logic you've already developed.
