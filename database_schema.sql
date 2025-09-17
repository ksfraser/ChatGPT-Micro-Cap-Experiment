-- Trading System Database Schema
-- This schema supports the comprehensive trading strategies and analysis system

-- Drop existing tables if they exist (for clean installation)
DROP TABLE IF EXISTS llm_analysis;
DROP TABLE IF EXISTS technical_indicators;
DROP TABLE IF EXISTS backtesting_results;
DROP TABLE IF EXISTS strategy_executions;
DROP TABLE IF EXISTS market_data;
DROP TABLE IF EXISTS trading_strategies;

-- 1. Trading Strategies Master Table
CREATE TABLE trading_strategies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    strategy_type ENUM('trend_following', 'mean_reversion', 'breakout', 'support_resistance', 'turtle', 'technical_analysis') NOT NULL,
    php_class_name VARCHAR(100), -- For mapping to PHP strategy classes
    parameters JSON, -- Strategy-specific configuration parameters
    risk_percentage DECIMAL(4,2) DEFAULT 2.00, -- Maximum risk per trade (%)
    max_positions INT DEFAULT 10, -- Maximum concurrent positions
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_strategy_type (strategy_type),
    INDEX idx_active (is_active)
);

-- 2. Market Data Storage
CREATE TABLE market_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(20) NOT NULL,
    date DATE NOT NULL,
    open_price DECIMAL(12,4) NOT NULL,
    high_price DECIMAL(12,4) NOT NULL,
    low_price DECIMAL(12,4) NOT NULL,
    close_price DECIMAL(12,4) NOT NULL,
    volume BIGINT NOT NULL,
    adjusted_close DECIMAL(12,4),
    source VARCHAR(50) NOT NULL, -- 'yahoo_finance', 'alpha_vantage', etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_symbol_date_source (symbol, date, source),
    INDEX idx_symbol_date (symbol, date),
    INDEX idx_date (date)
);

-- 3. Technical Indicators Storage
CREATE TABLE technical_indicators (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(20) NOT NULL,
    date DATE NOT NULL,
    indicator_name VARCHAR(50) NOT NULL, -- 'RSI', 'MACD', 'SMA_20', 'ATR', 'turtle_n_value'
    indicator_value DECIMAL(15,6) NOT NULL,
    timeframe VARCHAR(10) DEFAULT 'daily', -- 'daily', 'weekly', 'monthly'
    calculation_params JSON, -- Parameters used for calculation
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_symbol_date_indicator (symbol, date, indicator_name),
    INDEX idx_indicator_name (indicator_name)
);

-- 4. Strategy Executions and Signals
CREATE TABLE strategy_executions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    strategy_id INT NOT NULL,
    symbol VARCHAR(20) NOT NULL,
    execution_date DATETIME NOT NULL,
    action ENUM('BUY', 'SELL', 'SHORT', 'COVER', 'HOLD') NOT NULL,
    signal_strength DECIMAL(3,2), -- 0.00 to 1.00 confidence
    price DECIMAL(12,4),
    quantity INT,
    position_size DECIMAL(15,2), -- Dollar amount
    stop_loss_price DECIMAL(12,4),
    take_profit_price DECIMAL(12,4),
    reasoning TEXT,
    technical_conditions JSON, -- Technical analysis conditions met
    risk_reward_ratio DECIMAL(6,3),
    executed BOOLEAN DEFAULT FALSE, -- Whether signal was actually executed
    execution_price DECIMAL(12,4), -- Actual execution price
    execution_timestamp DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (strategy_id) REFERENCES trading_strategies(id) ON DELETE CASCADE,
    INDEX idx_strategy_symbol (strategy_id, symbol),
    INDEX idx_execution_date (execution_date),
    INDEX idx_action (action)
);

-- 5. Backtesting Results
CREATE TABLE backtesting_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    strategy_id INT NOT NULL,
    run_name VARCHAR(100), -- User-defined name for this backtest run
    symbol VARCHAR(20), -- NULL for portfolio-wide backtests
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    initial_capital DECIMAL(15,2) NOT NULL,
    final_capital DECIMAL(15,2) NOT NULL,
    total_return DECIMAL(10,4), -- Percentage return
    annualized_return DECIMAL(10,4),
    sharpe_ratio DECIMAL(8,4),
    sortino_ratio DECIMAL(8,4),
    max_drawdown DECIMAL(8,4), -- Maximum percentage drawdown
    max_drawdown_duration INT, -- Days
    volatility DECIMAL(8,4), -- Annualized volatility
    win_rate DECIMAL(6,4), -- Percentage of winning trades
    profit_factor DECIMAL(8,4), -- Gross profit / Gross loss
    total_trades INT NOT NULL,
    winning_trades INT,
    losing_trades INT,
    avg_winning_trade DECIMAL(10,4),
    avg_losing_trade DECIMAL(10,4),
    largest_winning_trade DECIMAL(10,4),
    largest_losing_trade DECIMAL(10,4),
    avg_trade_duration INT, -- Days
    parameters JSON, -- Strategy parameters used
    market_conditions JSON, -- Market context during test period
    benchmark_return DECIMAL(10,4), -- SPY or other benchmark return
    alpha DECIMAL(8,4), -- Alpha vs benchmark
    beta DECIMAL(6,4), -- Beta vs benchmark
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (strategy_id) REFERENCES trading_strategies(id) ON DELETE CASCADE,
    INDEX idx_strategy_dates (strategy_id, start_date, end_date),
    INDEX idx_total_return (total_return)
);

-- 6. Individual Trades (for detailed backtesting)
CREATE TABLE backtest_trades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    backtest_id INT NOT NULL,
    symbol VARCHAR(20) NOT NULL,
    entry_date DATE NOT NULL,
    exit_date DATE,
    entry_price DECIMAL(12,4) NOT NULL,
    exit_price DECIMAL(12,4),
    quantity INT NOT NULL,
    trade_type ENUM('LONG', 'SHORT') NOT NULL,
    entry_signal VARCHAR(100), -- Signal that triggered entry
    exit_signal VARCHAR(100), -- Signal that triggered exit
    pnl DECIMAL(15,2), -- Profit/Loss in dollars
    pnl_percentage DECIMAL(8,4), -- Profit/Loss percentage
    duration_days INT, -- Trade duration
    max_favorable_excursion DECIMAL(8,4), -- MFE percentage
    max_adverse_excursion DECIMAL(8,4), -- MAE percentage
    commission DECIMAL(8,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (backtest_id) REFERENCES backtesting_results(id) ON DELETE CASCADE,
    INDEX idx_backtest_symbol (backtest_id, symbol),
    INDEX idx_entry_date (entry_date),
    INDEX idx_pnl (pnl)
);

-- 7. LLM Analysis and AI Insights
CREATE TABLE llm_analysis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(20),
    analysis_date DATETIME NOT NULL,
    llm_provider VARCHAR(50) NOT NULL, -- 'openai', 'anthropic', 'local'
    model_name VARCHAR(100), -- 'gpt-4', 'claude-3', etc.
    prompt TEXT NOT NULL,
    response TEXT NOT NULL,
    sentiment_score DECIMAL(3,2), -- -1.00 to 1.00 (negative to positive)
    confidence_level DECIMAL(3,2), -- 0.00 to 1.00
    key_insights JSON, -- Structured insights from analysis
    recommendations JSON, -- Specific trading recommendations
    risk_factors JSON, -- Identified risk factors
    market_conditions JSON, -- AI assessment of market conditions
    processing_time_ms INT, -- Response time tracking
    token_usage INT, -- For cost tracking
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_symbol_date (symbol, analysis_date),
    INDEX idx_provider (llm_provider),
    INDEX idx_sentiment (sentiment_score)
);

-- 8. Portfolio Tracking
CREATE TABLE portfolio_positions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(20) NOT NULL,
    strategy_id INT NOT NULL,
    entry_date DATE NOT NULL,
    entry_price DECIMAL(12,4) NOT NULL,
    quantity INT NOT NULL,
    position_type ENUM('LONG', 'SHORT') NOT NULL,
    current_price DECIMAL(12,4),
    unrealized_pnl DECIMAL(15,2),
    stop_loss_price DECIMAL(12,4),
    take_profit_price DECIMAL(12,4),
    risk_amount DECIMAL(15,2), -- Dollar amount at risk
    is_open BOOLEAN DEFAULT TRUE,
    exit_date DATE,
    exit_price DECIMAL(12,4),
    realized_pnl DECIMAL(15,2),
    exit_reason VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (strategy_id) REFERENCES trading_strategies(id) ON DELETE CASCADE,
    INDEX idx_symbol_open (symbol, is_open),
    INDEX idx_strategy_open (strategy_id, is_open),
    INDEX idx_entry_date (entry_date)
);

-- 9. System Configuration and Settings
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_category VARCHAR(50) NOT NULL, -- 'risk_management', 'data_sources', 'ui_preferences'
    setting_key VARCHAR(100) NOT NULL,
    setting_value JSON NOT NULL,
    description TEXT,
    updated_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_category_key (setting_category, setting_key)
);

-- 10. Audit Log for Trading Actions
CREATE TABLE audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(100),
    action_type VARCHAR(50) NOT NULL, -- 'strategy_execution', 'parameter_change', 'manual_trade'
    entity_type VARCHAR(50), -- 'strategy', 'position', 'setting'
    entity_id INT,
    old_values JSON,
    new_values JSON,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_action (user_id, action_type),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
);

-- Insert default trading strategies
INSERT INTO trading_strategies (name, description, strategy_type, php_class_name, parameters) VALUES
('Turtle System 1', 'Classic Turtle Trading System 1 - 20-day breakout entry, 10-day exit', 'turtle', 'TurtleStrategy', 
 JSON_OBJECT('entry_days', 20, 'exit_days', 10, 'atr_period', 20, 'max_units', 4, 'unit_risk', 0.02)),

('Turtle System 2', 'Classic Turtle Trading System 2 - 55-day breakout entry, 20-day exit', 'turtle', 'TurtleStrategy',
 JSON_OBJECT('entry_days', 55, 'exit_days', 20, 'atr_period', 20, 'max_units', 4, 'unit_risk', 0.02)),

('Support Resistance', 'Buy leading stocks at support levels', 'support_resistance', 'SupportResistanceStrategy',
 JSON_OBJECT('lookback_period', 50, 'support_threshold', 0.02, 'volume_confirmation', true)),

('Moving Average Crossover', 'Simple moving average crossover strategy', 'technical_analysis', 'MACrossoverStrategy',
 JSON_OBJECT('fast_period', 20, 'slow_period', 50, 'confirmation_bars', 2)),

('Four Week Rule', 'Four week high/low breakout system', 'breakout', 'FourWeekRuleStrategy',
 JSON_OBJECT('breakout_period', 28, 'confirmation_required', false));

-- Insert default system settings
INSERT INTO system_settings (setting_category, setting_key, setting_value, description) VALUES
('risk_management', 'max_portfolio_risk', JSON_OBJECT('value', 10.00), 'Maximum portfolio risk percentage'),
('risk_management', 'max_position_size', JSON_OBJECT('value', 5.00), 'Maximum position size as percentage of portfolio'),
('risk_management', 'default_stop_loss', JSON_OBJECT('value', 2.00), 'Default stop loss percentage'),
('data_sources', 'primary_source', JSON_OBJECT('value', 'yahoo_finance'), 'Primary market data source'),
('data_sources', 'backup_source', JSON_OBJECT('value', 'alpha_vantage'), 'Backup market data source'),
('backtesting', 'default_commission', JSON_OBJECT('value', 1.00), 'Default commission per trade'),
('backtesting', 'slippage', JSON_OBJECT('value', 0.001), 'Default slippage percentage'),
('ui_preferences', 'default_chart_period', JSON_OBJECT('value', '1y'), 'Default chart time period'),
('ui_preferences', 'refresh_interval', JSON_OBJECT('value', 300), 'Data refresh interval in seconds');

-- Create views for common queries

-- Current portfolio summary
CREATE VIEW portfolio_summary AS
SELECT 
    pp.strategy_id,
    ts.name as strategy_name,
    COUNT(*) as total_positions,
    SUM(CASE WHEN pp.is_open = 1 THEN 1 ELSE 0 END) as open_positions,
    SUM(CASE WHEN pp.is_open = 0 THEN pp.realized_pnl ELSE 0 END) as realized_pnl,
    SUM(CASE WHEN pp.is_open = 1 THEN pp.unrealized_pnl ELSE 0 END) as unrealized_pnl,
    SUM(CASE WHEN pp.is_open = 1 THEN pp.quantity * pp.current_price ELSE 0 END) as market_value
FROM portfolio_positions pp
JOIN trading_strategies ts ON pp.strategy_id = ts.id
GROUP BY pp.strategy_id, ts.name;

-- Strategy performance summary
CREATE VIEW strategy_performance AS
SELECT 
    br.strategy_id,
    ts.name as strategy_name,
    COUNT(*) as total_backtests,
    AVG(br.total_return) as avg_return,
    AVG(br.sharpe_ratio) as avg_sharpe,
    AVG(br.max_drawdown) as avg_drawdown,
    AVG(br.win_rate) as avg_win_rate,
    MAX(br.total_return) as best_return,
    MIN(br.total_return) as worst_return
FROM backtesting_results br
JOIN trading_strategies ts ON br.strategy_id = ts.id
GROUP BY br.strategy_id, ts.name;

-- Recent trading signals
CREATE VIEW recent_signals AS
SELECT 
    se.id,
    se.symbol,
    se.execution_date,
    se.action,
    se.signal_strength,
    se.price,
    se.reasoning,
    ts.name as strategy_name
FROM strategy_executions se
JOIN trading_strategies ts ON se.strategy_id = ts.id
ORDER BY se.execution_date DESC
LIMIT 100;

-- Add indexes for performance optimization
CREATE INDEX idx_market_data_symbol_date ON market_data(symbol, date DESC);
CREATE INDEX idx_technical_indicators_symbol_date ON technical_indicators(symbol, date DESC, indicator_name);
CREATE INDEX idx_strategy_executions_date ON strategy_executions(execution_date DESC);
CREATE INDEX idx_backtest_trades_pnl ON backtest_trades(pnl DESC);
CREATE INDEX idx_llm_analysis_date ON llm_analysis(analysis_date DESC);

-- Add triggers for automatic timestamping
DELIMITER //

CREATE TRIGGER update_portfolio_timestamp
    BEFORE UPDATE ON portfolio_positions
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

CREATE TRIGGER log_strategy_changes
    AFTER UPDATE ON trading_strategies
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (action_type, entity_type, entity_id, old_values, new_values, description)
    VALUES ('strategy_update', 'strategy', NEW.id, 
            JSON_OBJECT('name', OLD.name, 'parameters', OLD.parameters, 'is_active', OLD.is_active),
            JSON_OBJECT('name', NEW.name, 'parameters', NEW.parameters, 'is_active', NEW.is_active),
            CONCAT('Strategy updated: ', NEW.name));
END//

DELIMITER ;

-- Create stored procedures for common operations

DELIMITER //

-- Calculate position size based on risk management rules
CREATE PROCEDURE CalculatePositionSize(
    IN p_symbol VARCHAR(20),
    IN p_entry_price DECIMAL(12,4),
    IN p_stop_loss_price DECIMAL(12,4),
    IN p_account_value DECIMAL(15,2),
    IN p_risk_percentage DECIMAL(4,2),
    OUT p_position_size INT
)
BEGIN
    DECLARE risk_amount DECIMAL(15,2);
    DECLARE price_risk DECIMAL(12,4);
    
    SET risk_amount = p_account_value * (p_risk_percentage / 100);
    SET price_risk = ABS(p_entry_price - p_stop_loss_price);
    
    IF price_risk > 0 THEN
        SET p_position_size = FLOOR(risk_amount / price_risk);
    ELSE
        SET p_position_size = 0;
    END IF;
END//

-- Get latest price for a symbol
CREATE PROCEDURE GetLatestPrice(
    IN p_symbol VARCHAR(20),
    OUT p_price DECIMAL(12,4),
    OUT p_date DATE
)
BEGIN
    SELECT close_price, date INTO p_price, p_date
    FROM market_data
    WHERE symbol = p_symbol
    ORDER BY date DESC
    LIMIT 1;
END//

DELIMITER ;

-- Grant appropriate permissions (adjust user as needed)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON trading_system.* TO 'trading_user'@'localhost';
-- GRANT EXECUTE ON trading_system.* TO 'trading_user'@'localhost';

-- Comments for documentation
ALTER TABLE trading_strategies COMMENT = 'Master table for all trading strategies and their configurations';
ALTER TABLE market_data COMMENT = 'Historical and real-time market data from multiple sources';
ALTER TABLE technical_indicators COMMENT = 'Calculated technical analysis indicators';
ALTER TABLE strategy_executions COMMENT = 'Trading signals and strategy execution records';
ALTER TABLE backtesting_results COMMENT = 'Results from strategy backtesting runs';
ALTER TABLE llm_analysis COMMENT = 'AI-powered market analysis and insights';
ALTER TABLE portfolio_positions COMMENT = 'Current and historical portfolio positions';
ALTER TABLE audit_log COMMENT = 'Audit trail for all system changes and trading actions';
