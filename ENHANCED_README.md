# Enhanced Trading Scripts - Database + CSV Integration

This directory contains enhanced versions of the original trading scripts that add database functionality while maintaining full CSV compatibility. The enhanced scripts support multiple market cap categories and provide advanced risk management features.

## Overview

The enhanced scripts provide:
- **Backward Compatibility**: Full compatibility with original CSV format
- **Database Integration**: Optional database storage for scalability
- **Multi-Market Cap Support**: Separate strategies for micro-cap, blue-chip, small-cap, etc.
- **Enhanced Risk Management**: Position sizing, stop-loss, and diversification controls
- **Advanced Analytics**: Session tracking, performance metrics, and LLM interaction logging
- **Dual Storage**: Hybrid CSV + Database approach for data persistence

## Files

### Core Enhanced Scripts
- `enhanced_trading_script.py` - Enhanced trading engine with database support
- `enhanced_automation.py` - Enhanced automation with multi-market cap strategies
- `db_config.yml` - Database configuration file

### Original Scripts (Not Modified)
- `trading_script.py` - Original trading script (maintained for compatibility)
- `simple_automation.py` - Original automation script (maintained for compatibility)

## Quick Start

### 1. Install Dependencies

```bash
pip install mysql-connector-python PyYAML pandas numpy
```

### 2. Configure Database (Optional)

Edit `db_config.yml` to match your database settings:

```yaml
database:
  host: localhost
  port: 3306
  username: your_username
  password: your_password
```

### 3. Basic Usage

#### Micro-Cap Trading (Default)
```python
from enhanced_trading_script import create_micro_cap_engine

# Create micro-cap trading engine
engine = create_micro_cap_engine()

# Load current portfolio
portfolio, cash = engine.load_portfolio_state()
print(f"Portfolio: {len(portfolio)} positions, ${cash:,.2f} cash")

# Process portfolio (interactive mode)
updated_portfolio, updated_cash = engine.process_portfolio(portfolio, cash)
```

#### Blue-Chip Trading
```python
from enhanced_trading_script import create_blue_chip_engine

# Create blue-chip trading engine  
engine = create_blue_chip_engine()

# Load and process portfolio
portfolio, cash = engine.load_portfolio_state()
updated_portfolio, updated_cash = engine.process_portfolio(portfolio, cash)
```

#### Automated Trading Session
```python
from enhanced_automation import create_micro_cap_automation, create_blue_chip_automation

# Micro-cap automation with moderate risk
micro_automation = create_micro_cap_automation(risk_tolerance='moderate')
results = micro_automation.run_automated_trading_session(max_trades=3, session_duration_hours=1.0)

# Blue-chip automation with conservative risk
blue_automation = create_blue_chip_automation(risk_tolerance='conservative')
results = blue_automation.run_automated_trading_session(max_trades=2, session_duration_hours=0.5)
```

## Market Cap Categories

The enhanced scripts support multiple market cap categories, each with tailored strategies:

### Micro-Cap (`micro`)
- **Focus**: High-growth potential micro-cap stocks
- **Risk**: Higher volatility and liquidity risk
- **Strategy**: Aggressive growth with tight risk management
- **Data Directory**: `data_micro_cap/`

### Blue-Chip (`blue-chip`)
- **Focus**: Large, established companies
- **Risk**: Lower volatility, dividend potential
- **Strategy**: Conservative growth and income
- **Data Directory**: `data_blue_chip/`

### Small-Cap (`small`)
- **Focus**: Small companies with growth potential
- **Risk**: Moderate volatility
- **Strategy**: Balanced growth approach
- **Data Directory**: `data_small_cap/`

## Data Storage

### CSV Files (Always Created)
Each market cap category maintains separate CSV files:
- `{category}_cap_portfolio.csv` - Portfolio snapshots
- `{category}_cap_trade_log.csv` - Trade execution log

### Database Tables (Optional)
When database is enabled, data is also stored in:
- `portfolio_data` - Current portfolio positions
- `trade_log` - Historical trade records
- `historical_prices` - Price data cache
- `trading_sessions` - Automation session tracking
- `llm_interactions` - LLM conversation logging

## Risk Management

### Position Sizing
- **Micro-Cap**: Max 5-12% per position (based on risk tolerance)
- **Blue-Chip**: Max 15-25% per position
- **Small-Cap**: Max 8-15% per position

### Stop Loss Levels
- **Micro-Cap**: 15-25% stop loss
- **Blue-Chip**: 10-15% stop loss  
- **Small-Cap**: 12-18% stop loss

### Diversification
- **Micro-Cap**: 8-15 positions optimal
- **Blue-Chip**: 5-8 positions optimal
- **Small-Cap**: 8-12 positions optimal

## Advanced Features

### Session Tracking
```python
# Get recent session history
automation = create_micro_cap_automation()
history = automation.get_session_history(limit=10)

# Get performance metrics
metrics = automation.get_performance_metrics(days=30)
print(f"30-day success rate: {metrics['success_rate']:.1%}")
```

### Enhanced Portfolio Analysis
```python
engine = create_blue_chip_engine()
portfolio, cash = engine.load_portfolio_state()

# Get detailed analysis with LLM insights
from enhanced_automation import EnhancedAutomationEngine
automation = EnhancedAutomationEngine('blue-chip')
analysis = automation.enhanced_portfolio_analysis(portfolio, cash)
print(analysis['llm_insights'])
```

### Market Data Caching
```python
# Get market data with database caching
engine = create_micro_cap_engine()
tickers = ['AAPL', 'MSFT', 'GOOGL']
market_data = engine.get_market_data(tickers, days=30)
```

## Configuration

### Risk Tolerance Levels
- **Conservative**: Lower position sizes, tighter stop losses
- **Moderate**: Balanced approach (default)
- **Aggressive**: Higher position sizes, wider stop losses

### Custom Configuration
```python
from enhanced_trading_script import EnhancedTradingEngine

# Custom configuration
engine = EnhancedTradingEngine(
    market_cap_category='micro',
    data_dir='custom_data_dir',
    enable_database=True,
    config_file='custom_config.yml'
)
```

## CSV-Only Mode

If you don't want to use the database features:

```python
# Disable database
engine = create_micro_cap_engine()
engine.enable_database = False

# Or create without database dependencies
from enhanced_trading_script import EnhancedTradingEngine
engine = EnhancedTradingEngine('micro', enable_database=False)
```

## Migration from Original Scripts

The enhanced scripts are fully backward compatible:

1. **Existing CSV files** will be read and processed normally
2. **Original workflows** continue to work unchanged  
3. **New features** are additive and optional
4. **Database integration** is optional and doesn't affect CSV operations

### Example Migration
```python
# Before (original script)
from trading_script import process_portfolio, load_latest_portfolio_state
portfolio, cash = load_latest_portfolio_state()
updated_portfolio, updated_cash = process_portfolio(portfolio, cash)

# After (enhanced script - same functionality + database)
from enhanced_trading_script import create_micro_cap_engine
engine = create_micro_cap_engine()
portfolio, cash = engine.load_portfolio_state()
updated_portfolio, updated_cash = engine.process_portfolio(portfolio, cash)
```

## Database Setup

If you want to use the database features:

1. **Create databases** using the provided PHP setup scripts:
   ```bash
   php setup-database.php
   ```

2. **Configure your databases**:
   - Legacy database: `stock_market_2`
   - Micro-cap database: `stock_market_micro_cap_trading`
   - Blue-chip database: `stock_market_blue_chip_trading` (optional)

3. **Import existing CSV data**:
   ```bash
   python import-csv-to-database.py
   ```

4. **Configure connection** in `db_config.yml`:
   ```yaml
   database:
     host: localhost
     username: your_username
     password: your_password
     micro_cap:
       database: stock_market_micro_cap_trading
     legacy:
       database: stock_market_2
   ```

5. **Test connection**:
   ```bash
   python test_database_connection.py
   ```

## Troubleshooting

### Database Connection Issues
- Check `db_config.yml` settings
- Verify database exists and user has permissions
- Test with CSV-only mode first

### Missing Dependencies
```bash
# Install all required packages
pip install mysql-connector-python PyYAML pandas numpy requests

# For CSV-only mode (minimal dependencies)
pip install pandas numpy requests
```

### File Permissions
- Ensure write permissions for data directories
- Check log file permissions

## Examples

See the `examples/` directory for complete usage examples:
- `micro_cap_example.py` - Micro-cap trading example
- `blue_chip_example.py` - Blue-chip trading example  
- `automation_example.py` - Automated trading example
- `migration_example.py` - Migration from original scripts

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review the configuration in `db_config.yml`
3. Test with CSV-only mode to isolate database issues
4. Check log files for detailed error information

## Performance Tips

1. **Use database caching** for frequently accessed market data
2. **Monitor session metrics** to optimize automation parameters
3. **Review LLM interactions** to improve prompt efficiency
4. **Regular backups** of both CSV and database data
