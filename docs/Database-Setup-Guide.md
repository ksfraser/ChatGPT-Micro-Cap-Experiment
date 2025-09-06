# Database Setup and Migration Guide

This guide will help you set up the complete database infrastructure for the ChatGPT Micro-Cap Experiment, including both the Python data collection system and the PHP per-symbol table migration system.

## Overview

The system consists of two main components:

1. **Legacy Tables** - Where Python scripts import CSV data
2. **Per-Symbol Tables** - Modern schema where each stock symbol has its own set of tables

## Prerequisites

- MySQL/MariaDB server
- PHP 7.3+ with PDO MySQL extension
- Python 3.7+ with required packages
- Composer (for PHP dependencies)

## Step 1: Database Configuration

1. Copy the example configuration file:
```bash
cp db_config.example.yml db_config.yml
```

2. Edit `db_config.yml` with your database credentials:
```yaml
database:
  host: localhost
  port: 3306
  username: your_username
  password: your_password
  charset: utf8mb4
  
  legacy:
    database: stock_market_micro_cap_trading
    
  micro_cap:
    database: stock_market_micro_cap_trading
```

3. Create the database:
```sql
CREATE DATABASE stock_market_micro_cap_trading 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Step 2: Install Dependencies

### PHP Dependencies
```bash
composer install
```

### Python Dependencies
```bash
# Standard installation
pip3 install -r requirements.txt

# For Fedora 30 or older systems
pip3 install -r requirements-fedora30.txt

# Additional database dependency
pip3 install mysql-connector-python PyYAML
```

## Step 3: Create Database Tables

Run the database setup script to create all necessary tables:

```bash
# Create all tables (legacy + modern schema)
php scripts/setup-database.php

# Create with sample data for testing
php scripts/setup-database.php --with-sample

# Create only legacy tables
php scripts/setup-database.php --legacy-only
```

This will create:

### Legacy Tables:
- `historical_prices` - Stock price data from Python scripts
- `technical_indicators` - Calculated indicators
- `candlestick_patterns` - Pattern recognition data  
- `portfolio_data` - Portfolio tracking data
- `trade_log` - Trade execution log

### Modern Schema:
- `symbol_registry` - Master list of stock symbols
- `migration_tracking` - Tracks migration progress
- Per-symbol tables (created automatically when symbols are added)

## Step 4: Import Python Data

You have several options for importing data into the legacy tables:

### Option A: Import Existing CSV Files
```bash
# Import all CSV files from a directory
python3 scripts/import-csv-to-database.py --csv-dir "Scripts and CSV Files/"

# Import specific files
python3 scripts/import-csv-to-database.py --portfolio-file "chatgpt_portfolio_update.csv"
python3 scripts/import-csv-to-database.py --trade-log-file "chatgpt_trade_log.csv"
```

### Option B: Generate Sample Data for Testing
```bash
# Generate 30 days of sample data for testing
python3 scripts/import-csv-to-database.py --generate-sample

# Generate more data
python3 scripts/import-csv-to-database.py --generate-sample --sample-days 60
```

### Option C: Modify Python Trading Scripts
Update your existing Python trading scripts to write directly to the database instead of just CSV files. Add database writing functions that insert into the legacy tables.

## Step 5: Run the Migration

Once you have data in the legacy tables, migrate it to the modern per-symbol schema:

```bash
# Run complete migration (with confirmation prompt)
php scripts/MigrateToPerSymbolTables.php

# Dry run to see what would be migrated
php scripts/MigrateToPerSymbolTables.php --dry-run

# Migrate specific symbol only
php scripts/MigrateToPerSymbolTables.php --symbol=IBM

# Force migration without prompts
php scripts/MigrateToPerSymbolTables.php --force
```

## Step 6: Verify Setup

Check that everything is working correctly:

```bash
# Check database status
php scripts/setup-database.php --help  # Shows available options

# List symbols in the registry
php scripts/ManageSymbols.php list

# Check table integrity
php scripts/ManageSymbols.php check

# Run tests
./vendor/bin/phpunit --configuration phpunit.xml
```

## Database Schema Reference

### Legacy Tables Structure

#### historical_prices
```sql
CREATE TABLE historical_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    open DECIMAL(10,4) NOT NULL,
    high DECIMAL(10,4) NOT NULL,
    low DECIMAL(10,4) NOT NULL,
    close DECIMAL(10,4) NOT NULL,
    adj_close DECIMAL(10,4) NOT NULL,
    volume BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_symbol_date (symbol, date)
);
```

#### technical_indicators
```sql
CREATE TABLE technical_indicators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    indicator_name VARCHAR(50) NOT NULL,
    value DECIMAL(15,6) NOT NULL,
    timeframe VARCHAR(10) DEFAULT 'daily',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_symbol_date_indicator (symbol, date, indicator_name)
);
```

### Per-Symbol Tables Structure

For each symbol (e.g., IBM), the following tables are created:
- `ibm_prices` - Price data for IBM
- `ibm_indicators` - Technical indicators for IBM
- `ibm_patterns` - Candlestick patterns for IBM
- `ibm_support_resistance` - Support/resistance levels
- `ibm_signals` - Trading signals
- `ibm_earnings` - Earnings data
- `ibm_dividends` - Dividend data

## Troubleshooting

### Error: "Table 'historical_prices' doesn't exist"
This means Step 3 (Create Database Tables) wasn't completed successfully.

**Solution:**
```bash
php scripts/setup-database.php --legacy-only
```

### Error: "No symbols found to migrate"
This means Step 4 (Import Python Data) needs to be completed.

**Solution:**
```bash
python3 scripts/import-csv-to-database.py --generate-sample
```

### Error: Database connection failed
Check your `db_config.yml` file and ensure:
1. Database credentials are correct
2. Database exists
3. User has proper permissions

### Error: PHP PDO extension not found
Install PHP PDO MySQL extension:
```bash
# Ubuntu/Debian
sudo apt install php-mysql

# Fedora/RHEL
sudo dnf install php-mysqlnd

# Or compile with --with-pdo-mysql
```

### Permission Errors
Ensure your database user has permissions to:
- CREATE tables
- INSERT, UPDATE, DELETE data
- SELECT data

```sql
GRANT ALL PRIVILEGES ON stock_market_micro_cap_trading.* TO 'your_username'@'localhost';
FLUSH PRIVILEGES;
```

## Complete Example Workflow

Here's a complete example from scratch:

```bash
# 1. Setup database configuration
cp db_config.example.yml db_config.yml
# Edit db_config.yml with your credentials

# 2. Create all database tables
php scripts/setup-database.php --with-sample

# 3. Import Python CSV data (if you have existing data)
python3 scripts/import-csv-to-database.py --csv-dir "Scripts and CSV Files/"

# 4. Migrate to per-symbol tables
php scripts/MigrateToPerSymbolTables.php --force

# 5. Verify everything works
php scripts/ManageSymbols.php list
./vendor/bin/phpunit --configuration phpunit.xml
```

## Integration with Python Scripts

To integrate the database with your existing Python trading scripts, modify them to also write to the database:

```python
import mysql.connector
from datetime import datetime

def save_portfolio_data(symbol, position_size, avg_cost, current_price, market_value, unrealized_pnl):
    connection = mysql.connector.connect(
        host='localhost',
        database='stock_market_micro_cap_trading',
        user='your_username',
        password='your_password'
    )
    
    cursor = connection.cursor()
    
    query = """
        INSERT INTO portfolio_data 
        (symbol, date, position_size, avg_cost, current_price, market_value, unrealized_pnl)
        VALUES (%s, %s, %s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
        position_size = VALUES(position_size),
        avg_cost = VALUES(avg_cost),
        current_price = VALUES(current_price),
        market_value = VALUES(market_value),
        unrealized_pnl = VALUES(unrealized_pnl)
    """
    
    cursor.execute(query, (
        symbol,
        datetime.now().date(),
        position_size,
        avg_cost,
        current_price,
        market_value,
        unrealized_pnl
    ))
    
    connection.commit()
    cursor.close()
    connection.close()
```

This setup provides a complete bridge between your Python data collection and the PHP per-symbol table system.
