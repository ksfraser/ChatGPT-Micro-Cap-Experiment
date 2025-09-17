# How-To Guides
## ChatGPT Micro-Cap Trading System v2.0

**Document Version:** 2.0  
**Date:** September 17, 2025  
**Author:** Documentation Team  
**Guide Collection ID:** HT-CMCTS-2.0

---

## Table of Contents

1. [Getting Started](#1-getting-started)
2. [Strategy Development](#2-strategy-development)
3. [Backtesting Operations](#3-backtesting-operations)
4. [AI Integration](#4-ai-integration)
5. [Data Management](#5-data-management)
6. [User Management](#6-user-management)
7. [System Administration](#7-system-administration)
8. [Troubleshooting](#8-troubleshooting)
9. [API Usage](#9-api-usage)
10. [Performance Optimization](#10-performance-optimization)

---

## 1. Getting Started

### 1.1 System Setup and Installation

#### Prerequisites
- PHP 8.1 or higher
- MySQL 8.0 or higher
- Redis 6.0 or higher
- Composer
- Node.js 16+ (for frontend assets)

#### Step-by-Step Installation

**Step 1: Clone the Repository**
```bash
git clone https://github.com/your-org/chatgpt-micro-cap-experiment.git
cd chatgpt-micro-cap-experiment
```

**Step 2: Install PHP Dependencies**
```bash
composer install
```

**Step 3: Configure Environment**
```bash
# Copy environment configuration
cp .env.example .env

# Edit configuration
nano .env
```

**Required Environment Variables:**
```bash
# Database Configuration
DATABASE_URL=mysql://username:password@localhost:3306/trading_db

# Redis Configuration
REDIS_URL=redis://localhost:6379

# External API Keys
YAHOO_FINANCE_API_KEY=your_yahoo_key
ALPHA_VANTAGE_API_KEY=your_alpha_vantage_key
OPENAI_API_KEY=your_openai_key

# Application Settings
APP_ENV=development
APP_DEBUG=true
APP_SECRET=your_secret_key
```

**Step 4: Database Setup**
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE trading_db;"

# Run migrations
php bin/console doctrine:migrations:migrate

# Load initial data
php bin/console doctrine:fixtures:load
```

**Step 5: Start Development Server**
```bash
# Start PHP development server
php -S localhost:8000 -t public/

# In another terminal, start asset compilation
npm run dev
```

**Step 6: Verify Installation**
1. Open http://localhost:8000 in your browser
2. Login with default credentials: `admin@example.com` / `password`
3. Navigate to Dashboard to verify all components are working

### 1.2 First-Time Configuration

#### Creating Your First Strategy

**Navigate to Strategy Management:**
1. Click "Strategies" in the main navigation
2. Click "Create New Strategy"
3. Select "Turtle Strategy" from the dropdown

**Configure Strategy Parameters:**
```json
{
  "system": 1,
  "entry_days": 20,
  "exit_days": 10,
  "risk_percentage": 2.0,
  "max_pyramid_units": 4
}
```

**Activate Strategy:**
1. Click "Save Configuration"
2. Select target symbols (e.g., "AAPL", "MSFT", "GOOGL")
3. Set position size and risk limits
4. Click "Activate Strategy"

---

## 2. Strategy Development

### 2.1 Creating a Custom Strategy

#### Strategy Class Structure

Create a new strategy file in the appropriate subdirectory:

**File: `src/Ksfraser/Finance/Strategy/CustomAnalysis/MyCustomStrategy.php`**

```php
<?php

namespace Ksfraser\Finance\Strategy\CustomAnalysis;

use Ksfraser\Finance\Strategy\StrategyInterface;
use Ksfraser\Finance\Strategy\Signal;

class MyCustomStrategy implements StrategyInterface
{
    private array $parameters;
    
    public function __construct(array $parameters = [])
    {
        $this->parameters = array_merge([
            'lookback_period' => 20,
            'threshold' => 0.02,
            'volume_factor' => 1.5
        ], $parameters);
    }
    
    public function generateSignal(array $marketData): ?Signal
    {
        // Validate input data
        if (count($marketData) < $this->parameters['lookback_period']) {
            return null;
        }
        
        // Implement your strategy logic here
        $signal = $this->analyzeMarketConditions($marketData);
        
        return $signal;
    }
    
    public function getName(): string
    {
        return 'My Custom Strategy';
    }
    
    public function getDescription(): string
    {
        return 'Custom strategy based on price and volume analysis';
    }
    
    public function getParameters(): array
    {
        return $this->parameters;
    }
    
    public function validateParameters(array $params): bool
    {
        $required = ['lookback_period', 'threshold', 'volume_factor'];
        
        foreach ($required as $param) {
            if (!isset($params[$param]) || !is_numeric($params[$param])) {
                return false;
            }
        }
        
        return true;
    }
    
    private function analyzeMarketConditions(array $marketData): ?Signal
    {
        $latest = end($marketData);
        $previous = array_slice($marketData, -$this->parameters['lookback_period']);
        
        // Example: Simple momentum strategy
        $priceChange = ($latest['close'] - $previous[0]['close']) / $previous[0]['close'];
        $volumeRatio = $latest['volume'] / array_sum(array_column($previous, 'volume')) * count($previous);
        
        $confidence = min(abs($priceChange) * 10, 1.0);
        
        if ($priceChange > $this->parameters['threshold'] && $volumeRatio > $this->parameters['volume_factor']) {
            return new Signal('BUY', $confidence, [
                'price_change' => $priceChange,
                'volume_ratio' => $volumeRatio,
                'analysis' => 'Strong upward momentum with volume confirmation'
            ]);
        } elseif ($priceChange < -$this->parameters['threshold'] && $volumeRatio > $this->parameters['volume_factor']) {
            return new Signal('SELL', $confidence, [
                'price_change' => $priceChange,
                'volume_ratio' => $volumeRatio,
                'analysis' => 'Strong downward momentum with volume confirmation'
            ]);
        }
        
        return null;
    }
}
```

#### Registering Your Strategy

**Update StrategyFactory:**

```php
// src/Ksfraser/Finance/Strategy/StrategyFactory.php

public function createStrategy(string $type, array $parameters = []): StrategyInterface
{
    return match ($type) {
        'turtle' => new Turtle\TurtleStrategy($parameters),
        'moving_average' => new TechnicalAnalysis\MovingAverageCrossoverStrategy($parameters),
        'my_custom' => new CustomAnalysis\MyCustomStrategy($parameters),
        // Add your strategy here
        default => throw new InvalidArgumentException("Unknown strategy type: $type"),
    };
}
```

#### Testing Your Strategy

**Create Unit Tests:**

```php
// tests/Unit/Strategy/MyCustomStrategyTest.php

use PHPUnit\Framework\TestCase;
use Ksfraser\Finance\Strategy\CustomAnalysis\MyCustomStrategy;

class MyCustomStrategyTest extends TestCase
{
    public function testBuySignalGeneration(): void
    {
        $strategy = new MyCustomStrategy([
            'lookback_period' => 5,
            'threshold' => 0.02,
            'volume_factor' => 1.5
        ]);
        
        $marketData = [
            ['close' => 100, 'volume' => 1000],
            ['close' => 101, 'volume' => 1100],
            ['close' => 102, 'volume' => 1200],
            ['close' => 103, 'volume' => 1300],
            ['close' => 105, 'volume' => 2000], // Strong move up with volume
        ];
        
        $signal = $strategy->generateSignal($marketData);
        
        $this->assertNotNull($signal);
        $this->assertEquals('BUY', $signal->getAction());
        $this->assertGreaterThan(0.5, $signal->getConfidence());
    }
}
```

**Run Tests:**
```bash
php vendor/bin/phpunit tests/Unit/Strategy/MyCustomStrategyTest.php
```

### 2.2 Strategy Parameter Optimization

#### Parameter Optimization Tool

Create an optimization script to find the best parameters:

```php
// scripts/optimize_strategy.php

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ksfraser\Finance\Strategy\CustomAnalysis\MyCustomStrategy;
use Ksfraser\Finance\Backtesting\BacktestingEngine;
use Ksfraser\Finance\Data\MarketDataService;

class ParameterOptimizer
{
    private BacktestingEngine $backtestingEngine;
    private MarketDataService $dataService;
    
    public function optimize(string $symbol, array $parameterRanges): array
    {
        $bestResult = null;
        $bestParameters = null;
        
        foreach ($this->generateParameterCombinations($parameterRanges) as $params) {
            $strategy = new MyCustomStrategy($params);
            $marketData = $this->dataService->getHistoricalData($symbol, '2020-01-01', '2023-12-31');
            
            $result = $this->backtestingEngine->runBacktest($strategy, $marketData);
            
            if (!$bestResult || $result->getSharpeRatio() > $bestResult->getSharpeRatio()) {
                $bestResult = $result;
                $bestParameters = $params;
            }
        }
        
        return [
            'parameters' => $bestParameters,
            'result' => $bestResult
        ];
    }
    
    private function generateParameterCombinations(array $ranges): Generator
    {
        // Implementation for generating parameter combinations
        foreach ($ranges['lookback_period'] as $lookback) {
            foreach ($ranges['threshold'] as $threshold) {
                foreach ($ranges['volume_factor'] as $volume) {
                    yield [
                        'lookback_period' => $lookback,
                        'threshold' => $threshold,
                        'volume_factor' => $volume
                    ];
                }
            }
        }
    }
}

// Usage
$optimizer = new ParameterOptimizer($backtestingEngine, $dataService);

$parameterRanges = [
    'lookback_period' => [10, 15, 20, 25, 30],
    'threshold' => [0.01, 0.015, 0.02, 0.025, 0.03],
    'volume_factor' => [1.2, 1.5, 1.8, 2.0, 2.5]
];

$optimization = $optimizer->optimize('AAPL', $parameterRanges);
echo "Best parameters: " . json_encode($optimization['parameters'], JSON_PRETTY_PRINT);
```

---

## 3. Backtesting Operations

### 3.1 Running Basic Backtests

#### Command Line Backtesting

**Single Strategy Backtest:**
```bash
# Run backtest for AAPL using Turtle Strategy
php bin/console trading:backtest \
    --strategy=turtle \
    --symbol=AAPL \
    --start-date=2020-01-01 \
    --end-date=2023-12-31 \
    --initial-capital=100000 \
    --output=results/aapl_turtle_backtest.json
```

**Multi-Symbol Backtest:**
```bash
# Run backtest across multiple symbols
php bin/console trading:backtest \
    --strategy=turtle \
    --symbols=AAPL,MSFT,GOOGL,AMZN \
    --start-date=2020-01-01 \
    --end-date=2023-12-31 \
    --initial-capital=100000 \
    --output=results/multi_symbol_backtest.json
```

#### Web Interface Backtesting

**Step 1: Navigate to Backtesting**
1. Go to "Backtesting" in the main navigation
2. Click "New Backtest"

**Step 2: Configure Backtest Parameters**
```json
{
  "strategy": {
    "type": "turtle",
    "parameters": {
      "system": 1,
      "entry_days": 20,
      "exit_days": 10
    }
  },
  "symbols": ["AAPL", "MSFT", "GOOGL"],
  "date_range": {
    "start": "2020-01-01",
    "end": "2023-12-31"
  },
  "initial_capital": 100000,
  "commission": 0.001,
  "slippage": 0.0005
}
```

**Step 3: Review Results**
- Total Return: 24.5%
- Sharpe Ratio: 1.42
- Maximum Drawdown: -8.3%
- Number of Trades: 187
- Win Rate: 52%

### 3.2 Advanced Backtesting Features

#### Portfolio-Level Backtesting

Create a portfolio backtest configuration:

```php
// config/portfolio_backtest.php

return [
    'portfolio' => [
        'initial_capital' => 1000000,
        'max_position_size' => 0.05, // 5% max per position
        'cash_reserve' => 0.1, // 10% cash reserve
        'rebalance_frequency' => 'monthly'
    ],
    'strategies' => [
        [
            'name' => 'turtle',
            'allocation' => 0.4, // 40% allocation
            'symbols' => ['AAPL', 'MSFT', 'GOOGL', 'AMZN'],
            'parameters' => ['system' => 1, 'entry_days' => 20]
        ],
        [
            'name' => 'moving_average',
            'allocation' => 0.3, // 30% allocation
            'symbols' => ['SPY', 'QQQ', 'IWM'],
            'parameters' => ['fast_period' => 10, 'slow_period' => 30]
        ],
        [
            'name' => 'support_resistance',
            'allocation' => 0.3, // 30% allocation
            'symbols' => ['TSLA', 'NVDA', 'AMD'],
            'parameters' => ['lookback_period' => 50]
        ]
    ]
];
```

**Run Portfolio Backtest:**
```bash
php bin/console trading:portfolio-backtest \
    --config=config/portfolio_backtest.php \
    --start-date=2020-01-01 \
    --end-date=2023-12-31 \
    --output=results/portfolio_backtest.json
```

#### Monte Carlo Analysis

Run Monte Carlo simulations to assess strategy robustness:

```php
// scripts/monte_carlo_analysis.php

class MonteCarloAnalyzer
{
    public function runMonteCarlo(Strategy $strategy, array $baseData, int $simulations = 1000): array
    {
        $results = [];
        
        for ($i = 0; $i < $simulations; $i++) {
            // Add random noise to historical data
            $perturbedData = $this->perturbData($baseData, 0.02); // 2% noise
            
            $result = $this->backtestingEngine->runBacktest($strategy, $perturbedData);
            $results[] = $result->getTotalReturn();
        }
        
        return [
            'mean_return' => array_sum($results) / count($results),
            'std_deviation' => $this->calculateStdDev($results),
            'percentile_5' => $this->percentile($results, 5),
            'percentile_95' => $this->percentile($results, 95),
            'probability_positive' => count(array_filter($results, fn($r) => $r > 0)) / count($results)
        ];
    }
}
```

### 3.3 Backtesting Best Practices

#### Avoiding Look-Ahead Bias

```php
class BiasAvoidanceBacktest
{
    public function runRealisticBacktest(Strategy $strategy, array $marketData): BacktestResult
    {
        $portfolio = new Portfolio($this->initialCapital);
        $trades = [];
        
        foreach ($marketData as $date => $dailyData) {
            // Only use data available at the time of decision
            $availableData = $this->getDataUpToDate($marketData, $date);
            
            // Simulate market delays (e.g., end-of-day data available next morning)
            $signal = $strategy->generateSignal($availableData);
            
            if ($signal) {
                // Simulate realistic execution with slippage and delay
                $executionPrice = $this->calculateRealisticExecutionPrice($signal, $dailyData);
                $trade = $this->executeTrade($signal, $executionPrice, $portfolio);
                
                if ($trade) {
                    $trades[] = $trade;
                }
            }
        }
        
        return new BacktestResult($portfolio, $trades);
    }
}
```

#### Transaction Cost Modeling

```php
class TransactionCostModel
{
    public function calculateTotalCost(Trade $trade): float
    {
        $baseCost = $trade->getQuantity() * $trade->getPrice();
        
        // Commission
        $commission = max(1.00, $baseCost * 0.001); // $1 min, 0.1% max
        
        // Slippage (market impact)
        $slippage = $this->calculateSlippage($trade);
        
        // Bid-ask spread
        $spread = $this->calculateSpread($trade);
        
        return $commission + $slippage + $spread;
    }
    
    private function calculateSlippage(Trade $trade): float
    {
        // Model slippage based on order size and market conditions
        $marketImpact = min($trade->getQuantity() / 100000, 0.002); // Max 0.2%
        return $trade->getQuantity() * $trade->getPrice() * $marketImpact;
    }
}
```

---

## 4. AI Integration

### 4.1 Setting Up AI Analysis

#### OpenAI Configuration

**Configure API Key:**
```bash
# Add to .env file
OPENAI_API_KEY=sk-your-openai-api-key-here
OPENAI_MODEL=gpt-4
OPENAI_MAX_TOKENS=2000
OPENAI_TEMPERATURE=0.3
```

**Test AI Connection:**
```bash
php bin/console ai:test-connection
```

Expected output:
```
✓ OpenAI API connection successful
✓ Model: gpt-4 available
✓ Rate limits: 10,000 tokens/minute
```

#### Basic AI Analysis Usage

**Analyze Strategy Performance:**
```php
use Ksfraser\LLM\OpenAIProvider;
use Ksfraser\Finance\AI\StrategyAnalysisService;

$aiProvider = new OpenAIProvider($apiKey);
$analysisService = new StrategyAnalysisService($aiProvider);

$strategy = $strategyFactory->createStrategy('turtle', ['system' => 1]);
$backtestResult = $backtestingEngine->runBacktest($strategy, $marketData);

$aiAnalysis = $analysisService->analyzeStrategy($strategy, $backtestResult);

echo "AI Insights:\n";
echo $aiAnalysis->getSummary() . "\n";
echo "Strengths: " . implode(', ', $aiAnalysis->getStrengths()) . "\n";
echo "Weaknesses: " . implode(', ', $aiAnalysis->getWeaknesses()) . "\n";
echo "Recommendations: " . implode(', ', $aiAnalysis->getRecommendations()) . "\n";
```

### 4.2 Custom AI Prompts

#### Creating Custom Analysis Prompts

```php
class CustomAIAnalyzer
{
    private LLMProviderInterface $llmProvider;
    
    public function analyzeMarketSentiment(string $news, array $marketData): array
    {
        $prompt = $this->buildSentimentPrompt($news, $marketData);
        
        $response = $this->llmProvider->generateResponse($prompt, [
            'temperature' => 0.2, // Lower temperature for more consistent analysis
            'max_tokens' => 500
        ]);
        
        return $this->parseAnalysisResponse($response);
    }
    
    private function buildSentimentPrompt(string $news, array $marketData): string
    {
        $latestPrice = end($marketData)['close'];
        $priceChange = ($latestPrice - $marketData[0]['close']) / $marketData[0]['close'] * 100;
        
        return sprintf(
            "Analyze the following financial news and market data:\n\n" .
            "NEWS: %s\n\n" .
            "MARKET DATA:\n" .
            "- Current Price: $%.2f\n" .
            "- Price Change: %.2f%%\n" .
            "- Volume: %d\n\n" .
            "Please provide:\n" .
            "1. Sentiment score (-1 to 1)\n" .
            "2. Key factors influencing sentiment\n" .
            "3. Potential market impact\n" .
            "4. Confidence level (0-1)\n\n" .
            "Respond in JSON format.",
            $news,
            $latestPrice,
            $priceChange,
            end($marketData)['volume']
        );
    }
    
    private function parseAnalysisResponse(Response $response): array
    {
        $content = $response->getContent();
        
        // Extract JSON from response
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            return json_decode($matches[0], true) ?? [];
        }
        
        return [];
    }
}
```

### 4.3 AI-Enhanced Signal Generation

#### Intelligent Signal Filtering

```php
class AISignalFilter
{
    public function filterSignal(Signal $signal, array $context): ?Signal
    {
        $prompt = sprintf(
            "Evaluate this trading signal:\n\n" .
            "Signal: %s %s with confidence %.2f\n" .
            "Current Price: $%.2f\n" .
            "Market Conditions: %s\n" .
            "Recent Performance: %s\n\n" .
            "Should this signal be executed? Consider:\n" .
            "1. Market volatility\n" .
            "2. Economic conditions\n" .
            "3. Technical indicators\n" .
            "4. Risk factors\n\n" .
            "Respond with: EXECUTE, MODIFY, or REJECT with reasoning.",
            $signal->getAction(),
            $signal->getSymbol(),
            $signal->getConfidence(),
            $context['current_price'],
            $context['market_conditions'],
            $context['recent_performance']
        );
        
        $response = $this->llmProvider->generateResponse($prompt);
        $decision = $this->parseDecision($response);
        
        return match ($decision['action']) {
            'EXECUTE' => $signal,
            'MODIFY' => $this->modifySignal($signal, $decision['modifications']),
            'REJECT' => null,
            default => $signal
        };
    }
}
```

---

## 5. Data Management

### 5.1 Market Data Operations

#### Historical Data Download

**Download Single Symbol:**
```bash
# Download AAPL data for the last 5 years
php bin/console data:download \
    --symbol=AAPL \
    --start-date=2019-01-01 \
    --end-date=2024-01-01 \
    --provider=yahoo
```

**Bulk Data Download:**
```bash
# Download data for multiple symbols
php bin/console data:bulk-download \
    --symbols-file=config/symbols.txt \
    --start-date=2020-01-01 \
    --provider=yahoo \
    --batch-size=10 \
    --delay=1000 # 1 second delay between requests
```

**symbols.txt format:**
```
AAPL
MSFT
GOOGL
AMZN
TSLA
NVDA
META
NFLX
```

#### Data Quality Validation

**Run Data Quality Checks:**
```bash
php bin/console data:validate \
    --symbol=AAPL \
    --start-date=2020-01-01 \
    --end-date=2023-12-31
```

**Custom Data Validation:**
```php
class DataQualityValidator
{
    public function validateMarketData(array $data): ValidationResult
    {
        $errors = [];
        $warnings = [];
        
        foreach ($data as $date => $ohlcv) {
            // Check for missing data
            if ($this->hasMissingFields($ohlcv)) {
                $errors[] = "Missing fields on $date";
            }
            
            // Check for unrealistic values
            if ($this->hasUnrealisticValues($ohlcv)) {
                $warnings[] = "Unrealistic values on $date";
            }
            
            // Check for data consistency
            if ($this->hasInconsistentData($ohlcv)) {
                $errors[] = "Inconsistent OHLC data on $date";
            }
        }
        
        return new ValidationResult($errors, $warnings);
    }
    
    private function hasInconsistentData(array $ohlcv): bool
    {
        // High should be >= Open, Close, Low
        if ($ohlcv['high'] < max($ohlcv['open'], $ohlcv['close'], $ohlcv['low'])) {
            return true;
        }
        
        // Low should be <= Open, Close, High
        if ($ohlcv['low'] > min($ohlcv['open'], $ohlcv['close'], $ohlcv['high'])) {
            return true;
        }
        
        return false;
    }
}
```

### 5.2 Data Backup and Recovery

#### Database Backup

**Automated Backup Script:**
```bash
#!/bin/bash
# backup_database.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/database"
DB_NAME="trading_db"

# Create backup directory
mkdir -p $BACKUP_DIR

# Perform backup
mysqldump -u $DB_USER -p$DB_PASSWORD \
    --single-transaction \
    --triggers \
    --routines \
    --events \
    $DB_NAME > $BACKUP_DIR/trading_db_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/trading_db_$DATE.sql

# Remove backups older than 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "Backup completed: trading_db_$DATE.sql.gz"
```

**Schedule Backup (Cron):**
```bash
# Add to crontab (crontab -e)
0 2 * * * /path/to/backup_database.sh >> /var/log/backup.log 2>&1
```

#### Data Recovery

**Restore from Backup:**
```bash
# Uncompress backup
gunzip /backups/database/trading_db_20241215_020000.sql.gz

# Restore database
mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME < /backups/database/trading_db_20241215_020000.sql
```

**Point-in-Time Recovery:**
```bash
# Restore to specific timestamp using binary logs
mysqlbinlog --start-datetime="2024-12-15 10:00:00" \
    --stop-datetime="2024-12-15 11:00:00" \
    /var/log/mysql/mysql-bin.000001 | \
    mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME
```

---

## 6. User Management

### 6.1 User Administration

#### Creating User Accounts

**Command Line User Creation:**
```bash
# Create admin user
php bin/console user:create \
    --email=admin@company.com \
    --password=SecurePassword123! \
    --role=ROLE_ADMIN \
    --first-name=John \
    --last-name=Doe

# Create trader user
php bin/console user:create \
    --email=trader@company.com \
    --password=SecurePassword123! \
    --role=ROLE_TRADER \
    --first-name=Jane \
    --last-name=Smith
```

**Web Interface User Creation:**
1. Login as admin
2. Navigate to "User Management"
3. Click "Create New User"
4. Fill in user details:
   - Email: user@company.com
   - Password: (auto-generated or custom)
   - Role: Select appropriate role
   - Name: First and last name
5. Click "Create User"

#### User Role Management

**Available Roles:**
- `ROLE_ADMIN`: Full system access
- `ROLE_TRADER`: Strategy creation and execution
- `ROLE_ANALYST`: Read-only access to data and reports
- `ROLE_VIEWER`: Dashboard viewing only

**Update User Role:**
```bash
php bin/console user:change-role \
    --email=user@company.com \
    --role=ROLE_ANALYST
```

### 6.2 Permission Management

#### Role-Based Access Control

**Configuration Example:**
```yaml
# config/security.yaml
security:
    role_hierarchy:
        ROLE_ADMIN: [ROLE_TRADER, ROLE_ANALYST, ROLE_VIEWER]
        ROLE_TRADER: [ROLE_ANALYST, ROLE_VIEWER]
        ROLE_ANALYST: [ROLE_VIEWER]
    
    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/strategies, roles: ROLE_TRADER }
        - { path: ^/reports, roles: ROLE_ANALYST }
        - { path: ^/dashboard, roles: ROLE_VIEWER }
```

**Custom Permission Checks:**
```php
class PermissionChecker
{
    public function canManageStrategy(User $user, Strategy $strategy): bool
    {
        // Admins can manage all strategies
        if ($user->hasRole('ROLE_ADMIN')) {
            return true;
        }
        
        // Users can only manage their own strategies
        if ($strategy->getOwner() === $user) {
            return true;
        }
        
        return false;
    }
    
    public function canViewPortfolio(User $user, Portfolio $portfolio): bool
    {
        // Check if user has access to this portfolio
        return $portfolio->hasUser($user) || $user->hasRole('ROLE_ADMIN');
    }
}
```

---

## 7. System Administration

### 7.1 System Monitoring

#### Health Check Endpoints

**Create Health Check Controller:**
```php
class HealthCheckController
{
    public function healthCheck(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'external_apis' => $this->checkExternalAPIs(),
            'disk_space' => $this->checkDiskSpace(),
            'memory_usage' => $this->checkMemoryUsage()
        ];
        
        $overall = array_reduce($checks, fn($carry, $check) => $carry && $check['status'] === 'ok', true);
        
        return new JsonResponse([
            'status' => $overall ? 'ok' : 'error',
            'timestamp' => date('c'),
            'checks' => $checks
        ]);
    }
    
    private function checkDatabase(): array
    {
        try {
            $this->entityManager->getConnection()->connect();
            return ['status' => 'ok', 'message' => 'Database connected'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
```

**Monitor System Resources:**
```bash
# CPU and Memory monitoring script
#!/bin/bash
# monitor_system.sh

while true; do
    CPU=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
    MEM=$(free | grep Mem | awk '{printf "%.2f", $3/$2 * 100.0}')
    DISK=$(df -h / | awk 'NR==2 {print $5}' | cut -d'%' -f1)
    
    echo "$(date): CPU: ${CPU}%, Memory: ${MEM}%, Disk: ${DISK}%"
    
    # Alert if thresholds exceeded
    if (( $(echo "$CPU > 80" | bc -l) )); then
        echo "ALERT: High CPU usage: ${CPU}%"
    fi
    
    if (( $(echo "$MEM > 85" | bc -l) )); then
        echo "ALERT: High memory usage: ${MEM}%"
    fi
    
    sleep 60
done
```

### 7.2 Log Management

#### Centralized Logging Configuration

**Configure Monolog:**
```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['trading', 'strategy', 'ai', 'data']
    
    handlers:
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: info
            channels: ["!event"]
            
        trading:
            type: stream
            path: "%kernel.logs_dir%/trading.log"
            level: debug
            channels: ["trading"]
            
        strategy:
            type: stream
            path: "%kernel.logs_dir%/strategy.log"
            level: info
            channels: ["strategy"]
            
        ai:
            type: stream
            path: "%kernel.logs_dir%/ai.log"
            level: info
            channels: ["ai"]
```

**Custom Logging Service:**
```php
class TradingLogger
{
    private LoggerInterface $logger;
    
    public function logSignal(Signal $signal, Strategy $strategy): void
    {
        $this->logger->info('Signal generated', [
            'strategy' => $strategy->getName(),
            'symbol' => $signal->getSymbol(),
            'action' => $signal->getAction(),
            'confidence' => $signal->getConfidence(),
            'timestamp' => new DateTime()
        ]);
    }
    
    public function logTrade(Trade $trade): void
    {
        $this->logger->info('Trade executed', [
            'symbol' => $trade->getSymbol(),
            'action' => $trade->getAction(),
            'quantity' => $trade->getQuantity(),
            'price' => $trade->getPrice(),
            'total_cost' => $trade->getTotalCost(),
            'timestamp' => $trade->getTimestamp()
        ]);
    }
}
```

#### Log Analysis

**Analyze Trading Performance:**
```bash
# Extract daily trading summary
grep "Trade executed" /var/log/trading.log | \
    grep "$(date +%Y-%m-%d)" | \
    jq -r '.symbol + " " + .action + " " + (.quantity|tostring) + " @ $" + (.price|tostring)'
```

**Monitor Error Rates:**
```bash
# Count errors by hour
grep "ERROR" /var/log/trading.log | \
    awk '{print $2}' | \
    cut -d: -f1 | \
    sort | uniq -c
```

---

## 8. Troubleshooting

### 8.1 Common Issues and Solutions

#### Database Connection Issues

**Problem:** `SQLSTATE[HY000] [2002] Connection refused`

**Solutions:**
1. **Check MySQL Service:**
   ```bash
   sudo systemctl status mysql
   sudo systemctl start mysql
   ```

2. **Verify Database Credentials:**
   ```bash
   mysql -u username -p
   ```

3. **Check Database Configuration:**
   ```bash
   # Verify .env file
   grep DATABASE_URL .env
   
   # Test connection
   php bin/console doctrine:database:create --if-not-exists
   ```

#### Redis Connection Issues

**Problem:** `Connection refused [tcp://127.0.0.1:6379]`

**Solutions:**
1. **Start Redis Service:**
   ```bash
   sudo systemctl start redis-server
   sudo systemctl enable redis-server
   ```

2. **Check Redis Configuration:**
   ```bash
   redis-cli ping
   # Should return PONG
   ```

3. **Verify Redis URL:**
   ```bash
   grep REDIS_URL .env
   ```

#### External API Rate Limiting

**Problem:** `HTTP 429 Too Many Requests`

**Solutions:**
1. **Implement Rate Limiting:**
   ```php
   class RateLimitedApiClient
   {
       private int $requestsPerMinute = 60;
       private array $lastRequestTimes = [];
       
       public function makeRequest(string $url): Response
       {
           $this->enforceRateLimit();
           
           try {
               return $this->httpClient->get($url);
           } catch (TooManyRequestsException $e) {
               $this->handleRateLimit($e);
               throw $e;
           }
       }
       
       private function enforceRateLimit(): void
       {
           $now = time();
           $this->lastRequestTimes = array_filter(
               $this->lastRequestTimes,
               fn($time) => $now - $time < 60
           );
           
           if (count($this->lastRequestTimes) >= $this->requestsPerMinute) {
               $waitTime = 60 - ($now - min($this->lastRequestTimes));
               sleep($waitTime);
           }
           
           $this->lastRequestTimes[] = $now;
       }
   }
   ```

2. **Add Request Delays:**
   ```bash
   # Add delay in bulk operations
   php bin/console data:bulk-download \
       --symbols-file=symbols.txt \
       --delay=2000 # 2 second delay
   ```

### 8.2 Performance Issues

#### Slow Backtesting

**Problem:** Backtests taking too long to complete

**Solutions:**
1. **Optimize Database Queries:**
   ```sql
   -- Add indexes for better performance
   CREATE INDEX idx_market_data_symbol_date ON market_data(symbol, date);
   CREATE INDEX idx_trades_strategy_date ON trades(strategy_id, created_at);
   ```

2. **Use Data Caching:**
   ```php
   class CachedMarketDataService
   {
       public function getHistoricalData(string $symbol, DateRange $range): array
       {
           $cacheKey = "market_data:{$symbol}:" . $range->getStartDate() . ":" . $range->getEndDate();
           
           if ($data = $this->cache->get($cacheKey)) {
               return $data;
           }
           
           $data = $this->dataProvider->getHistoricalData($symbol, $range);
           $this->cache->set($cacheKey, $data, 3600); // 1 hour cache
           
           return $data;
       }
   }
   ```

3. **Parallel Processing:**
   ```php
   class ParallelBacktester
   {
       public function runParallelBacktests(array $strategies, array $symbols): array
       {
           $processes = [];
           $results = [];
           
           foreach ($strategies as $strategy) {
               foreach ($symbols as $symbol) {
                   $cmd = sprintf(
                       'php bin/console trading:backtest --strategy=%s --symbol=%s --output=/tmp/backtest_%s_%s.json',
                       $strategy,
                       $symbol,
                       $strategy,
                       $symbol
                   );
                   
                   $processes[] = proc_open($cmd, [], $pipes);
               }
           }
           
           // Wait for all processes to complete
           foreach ($processes as $process) {
               proc_close($process);
           }
           
           // Collect results
           foreach (glob('/tmp/backtest_*.json') as $file) {
               $results[] = json_decode(file_get_contents($file), true);
               unlink($file);
           }
           
           return $results;
       }
   }
   ```

#### Memory Issues

**Problem:** `Fatal error: Allowed memory size exhausted`

**Solutions:**
1. **Increase Memory Limit:**
   ```bash
   # Temporary increase
   php -d memory_limit=2G bin/console trading:backtest
   
   # Permanent increase in php.ini
   memory_limit = 2G
   ```

2. **Process Data in Chunks:**
   ```php
   class ChunkedDataProcessor
   {
       public function processLargeDataset(array $symbols, int $chunkSize = 100): void
       {
           $chunks = array_chunk($symbols, $chunkSize);
           
           foreach ($chunks as $chunk) {
               $this->processChunk($chunk);
               
               // Free memory
               gc_collect_cycles();
           }
       }
   }
   ```

### 8.3 Security Issues

#### Unauthorized Access

**Problem:** Users accessing resources they shouldn't

**Solutions:**
1. **Review Access Controls:**
   ```php
   public function viewStrategy(Strategy $strategy): Response
   {
       // Always check permissions
       if (!$this->permissionChecker->canViewStrategy($this->getUser(), $strategy)) {
           throw new AccessDeniedException();
       }
       
       return $this->render('strategy/view.html.twig', ['strategy' => $strategy]);
   }
   ```

2. **Audit User Activities:**
   ```bash
   # Review access logs
   grep "UNAUTHORIZED" /var/log/trading.log
   
   # Check for suspicious patterns
   awk '{print $1}' /var/log/nginx/access.log | sort | uniq -c | sort -nr | head -20
   ```

---

## 9. API Usage

### 9.1 REST API Endpoints

#### Authentication

**Get Access Token:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password"
  }'
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "email": "user@example.com",
    "roles": ["ROLE_TRADER"]
  }
}
```

#### Strategy Management

**List Strategies:**
```bash
curl -X GET http://localhost:8000/api/strategies \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Create Strategy:**
```bash
curl -X POST http://localhost:8000/api/strategies \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Turtle Strategy",
    "type": "turtle",
    "parameters": {
      "system": 1,
      "entry_days": 20,
      "exit_days": 10,
      "risk_percentage": 2.0
    },
    "symbols": ["AAPL", "MSFT"]
  }'
```

**Run Backtest:**
```bash
curl -X POST http://localhost:8000/api/backtests \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "strategy_id": 1,
    "start_date": "2020-01-01",
    "end_date": "2023-12-31",
    "initial_capital": 100000
  }'
```

### 9.2 WebSocket API

#### Real-time Signal Updates

**Connect to WebSocket:**
```javascript
const ws = new WebSocket('ws://localhost:8000/ws/signals');

ws.onopen = function() {
    console.log('Connected to signal stream');
    
    // Subscribe to specific symbols
    ws.send(JSON.stringify({
        action: 'subscribe',
        symbols: ['AAPL', 'MSFT', 'GOOGL']
    }));
};

ws.onmessage = function(event) {
    const signal = JSON.parse(event.data);
    console.log('New signal:', signal);
    
    // Handle signal
    if (signal.action === 'BUY' && signal.confidence > 0.7) {
        executeTradeOrder(signal);
    }
};
```

**Signal Message Format:**
```json
{
  "type": "signal",
  "data": {
    "symbol": "AAPL",
    "action": "BUY",
    "confidence": 0.85,
    "price": 150.25,
    "timestamp": "2024-12-15T10:30:00Z",
    "strategy": "turtle",
    "reasoning": "20-day high breakout with strong volume"
  }
}
```

### 9.3 Python Client Library

#### Installation and Setup

```bash
pip install requests websocket-client
```

**Python Client:**
```python
import requests
import json
from datetime import datetime

class TradingSystemClient:
    def __init__(self, base_url, api_key):
        self.base_url = base_url
        self.session = requests.Session()
        self.session.headers.update({
            'Authorization': f'Bearer {api_key}',
            'Content-Type': 'application/json'
        })
    
    def get_strategies(self):
        response = self.session.get(f'{self.base_url}/api/strategies')
        response.raise_for_status()
        return response.json()
    
    def create_strategy(self, name, strategy_type, parameters, symbols):
        data = {
            'name': name,
            'type': strategy_type,
            'parameters': parameters,
            'symbols': symbols
        }
        
        response = self.session.post(
            f'{self.base_url}/api/strategies',
            json=data
        )
        response.raise_for_status()
        return response.json()
    
    def run_backtest(self, strategy_id, start_date, end_date, initial_capital):
        data = {
            'strategy_id': strategy_id,
            'start_date': start_date,
            'end_date': end_date,
            'initial_capital': initial_capital
        }
        
        response = self.session.post(
            f'{self.base_url}/api/backtests',
            json=data
        )
        response.raise_for_status()
        return response.json()

# Usage example
client = TradingSystemClient('http://localhost:8000', 'your-api-key')

# Create a new strategy
strategy = client.create_strategy(
    name='Python Turtle Strategy',
    strategy_type='turtle',
    parameters={'system': 1, 'entry_days': 20},
    symbols=['AAPL', 'MSFT']
)

# Run backtest
result = client.run_backtest(
    strategy_id=strategy['id'],
    start_date='2020-01-01',
    end_date='2023-12-31',
    initial_capital=100000
)

print(f"Backtest completed with {result['total_return']:.2%} return")
```

---

## 10. Performance Optimization

### 10.1 Database Optimization

#### Query Optimization

**Optimize Slow Queries:**
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Analyze slow queries
SELECT 
    query_time,
    lock_time,
    rows_sent,
    rows_examined,
    sql_text
FROM mysql.slow_log
ORDER BY query_time DESC
LIMIT 10;
```

**Index Optimization:**
```sql
-- Analyze index usage
SELECT 
    table_name,
    index_name,
    cardinality,
    non_unique
FROM information_schema.statistics 
WHERE table_schema = 'trading_db'
ORDER BY cardinality DESC;

-- Add composite indexes for common queries
CREATE INDEX idx_trades_strategy_symbol_date 
ON trades(strategy_id, symbol, created_at);

CREATE INDEX idx_market_data_symbol_date_close 
ON market_data(symbol, date, close);
```

**Partitioning Large Tables:**
```sql
-- Partition market_data by date
ALTER TABLE market_data 
PARTITION BY RANGE (YEAR(date)) (
    PARTITION p2020 VALUES LESS THAN (2021),
    PARTITION p2021 VALUES LESS THAN (2022),
    PARTITION p2022 VALUES LESS THAN (2023),
    PARTITION p2023 VALUES LESS THAN (2024),
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION pfuture VALUES LESS THAN MAXVALUE
);
```

### 10.2 Application Optimization

#### Caching Strategies

**Multi-Level Caching:**
```php
class OptimizedDataService
{
    private array $memoryCache = [];
    private CacheInterface $redisCache;
    private EntityManagerInterface $entityManager;
    
    public function getMarketData(string $symbol, string $date): ?array
    {
        // Level 1: Memory cache
        $memoryKey = "{$symbol}:{$date}";
        if (isset($this->memoryCache[$memoryKey])) {
            return $this->memoryCache[$memoryKey];
        }
        
        // Level 2: Redis cache
        $redisKey = "market_data:{$symbol}:{$date}";
        if ($data = $this->redisCache->get($redisKey)) {
            $this->memoryCache[$memoryKey] = $data;
            return $data;
        }
        
        // Level 3: Database
        $data = $this->entityManager
            ->getRepository(MarketData::class)
            ->findBySymbolAndDate($symbol, $date);
        
        if ($data) {
            $dataArray = $data->toArray();
            $this->redisCache->set($redisKey, $dataArray, 3600);
            $this->memoryCache[$memoryKey] = $dataArray;
            return $dataArray;
        }
        
        return null;
    }
}
```

#### Connection Pooling

**Database Connection Pool:**
```php
class ConnectionPool
{
    private array $connections = [];
    private int $maxConnections = 20;
    private int $currentConnections = 0;
    
    public function getConnection(): PDO
    {
        if (!empty($this->connections)) {
            return array_pop($this->connections);
        }
        
        if ($this->currentConnections < $this->maxConnections) {
            $this->currentConnections++;
            return $this->createConnection();
        }
        
        // Wait for available connection
        while (empty($this->connections)) {
            usleep(10000); // 10ms
        }
        
        return array_pop($this->connections);
    }
    
    public function releaseConnection(PDO $connection): void
    {
        if (count($this->connections) < $this->maxConnections) {
            $this->connections[] = $connection;
        } else {
            $this->currentConnections--;
        }
    }
}
```

### 10.3 Frontend Optimization

#### Lazy Loading

**Component Lazy Loading:**
```javascript
// router/index.js
const routes = [
    {
        path: '/strategies',
        name: 'Strategies',
        component: () => import('../components/Strategies.vue')
    },
    {
        path: '/backtesting',
        name: 'Backtesting',
        component: () => import('../components/Backtesting.vue')
    }
];
```

**Data Table Virtualization:**
```vue
<template>
  <div class="data-table">
    <virtual-list
      :data-key="'id'"
      :data-sources="visibleRows"
      :data-component="RowComponent"
      :keeps="50"
      :estimate-size="40"
    />
  </div>
</template>

<script>
import VirtualList from 'vue-virtual-scroll-list';

export default {
  components: {
    VirtualList
  },
  computed: {
    visibleRows() {
      return this.allRows.slice(0, this.loadedCount);
    }
  }
};
</script>
```

#### Chart Optimization

**Efficient Chart Updates:**
```javascript
class OptimizedChart {
    constructor(chartElement, options) {
        this.chart = new Chart(chartElement, options);
        this.updateQueue = [];
        this.isUpdating = false;
    }
    
    addDataPoint(data) {
        this.updateQueue.push(data);
        
        if (!this.isUpdating) {
            this.scheduleUpdate();
        }
    }
    
    scheduleUpdate() {
        this.isUpdating = true;
        
        requestAnimationFrame(() => {
            // Batch process all queued updates
            const newData = this.updateQueue.splice(0);
            
            newData.forEach(data => {
                this.chart.data.datasets[0].data.push(data);
                
                // Keep only last 1000 points for performance
                if (this.chart.data.datasets[0].data.length > 1000) {
                    this.chart.data.datasets[0].data.shift();
                }
            });
            
            this.chart.update('none'); // No animation for better performance
            this.isUpdating = false;
        });
    }
}
```

---

**Document Control:**
- **Version:** 2.0
- **Last Updated:** September 17, 2025
- **Next Review:** October 17, 2025
- **Approval Required:** Technical Writer, Development Team Lead, Product Manager
- **Distribution:** All Users, Development Team, Support Team
