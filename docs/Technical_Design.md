# Technical Design Document (TDD)
## Comprehensive Financial Analysis & Portfolio Management Platform

**Document Version:** 1.0  
**Date:** September 18, 2025  
**Project:** ChatGPT-Micro-Cap-Experiment  

---

## 1. Introduction

### 1.1 Purpose
This document provides detailed technical design specifications for the financial analysis platform, including system architecture, database design, API specifications, and implementation details.

### 1.2 Scope
This document covers:
- High-level system architecture
- Database schema design
- API design and endpoints
- Technical analysis engine design
- Security architecture
- Performance optimization strategies
- Deployment architecture

### 1.3 Audience
- Software developers and architects
- Database administrators
- DevOps engineers
- Quality assurance engineers
- Technical project managers

---

## 2. System Architecture

### 2.1 High-Level Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Web Client    │    │   Mobile App     │    │  Third-party    │
│   (React/Vue)   │    │   (React Native) │    │   Integrations  │
└─────────┬───────┘    └────────┬─────────┘    └─────────┬───────┘
          │                     │                        │
          └─────────────────────┼────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                      API Gateway & Load Balancer                │
│                     (Nginx / AWS ALB)                          │
└─────────────────────┬───────────────────────────────────────────┘
                      │
┌─────────────────────┼───────────────────────────────────────────┐
│                Application Layer (PHP 8.x)                     │
├─────────────────────┼───────────────────────────────────────────┤
│  ┌─────────────────┐ │ ┌─────────────────┐ ┌─────────────────┐  │
│  │ Authentication  │ │ │   Portfolio     │ │  Technical      │  │
│  │   Service       │ │ │   Management    │ │  Analysis       │  │
│  └─────────────────┘ │ └─────────────────┘ └─────────────────┘  │
│  ┌─────────────────┐ │ ┌─────────────────┐ ┌─────────────────┐  │
│  │ Alert System    │ │ │  Backtesting    │ │  AI Analysis    │  │
│  └─────────────────┘ │ └─────────────────┘ └─────────────────┘  │
└─────────────────────┼───────────────────────────────────────────┘
                      │
┌─────────────────────┼───────────────────────────────────────────┐
│                 Data Layer                                      │
├─────────────────────┼───────────────────────────────────────────┤
│  ┌─────────────────┐ │ ┌─────────────────┐ ┌─────────────────┐  │
│  │   MySQL/PG      │ │ │     Redis       │ │   Time Series   │  │
│  │   (Main DB)     │ │ │    (Cache)      │ │   (InfluxDB)    │  │
│  └─────────────────┘ │ └─────────────────┘ └─────────────────┘  │
└─────────────────────┼───────────────────────────────────────────┘
                      │
┌─────────────────────┼───────────────────────────────────────────┐
│              External Services                                  │
├─────────────────────┼───────────────────────────────────────────┤
│  ┌─────────────────┐ │ ┌─────────────────┐ ┌─────────────────┐  │
│  │ Market Data     │ │ │ News/Sentiment  │ │  Notification   │  │
│  │ Providers       │ │ │   Services      │ │   Services      │  │
│  └─────────────────┘ │ └─────────────────┘ └─────────────────┘  │
└─────────────────────┴───────────────────────────────────────────┘
```

### 2.2 Component Architecture

#### 2.2.1 Application Services

**Authentication Service**
```php
namespace App\Services;

class AuthenticationService {
    public function authenticate(string $email, string $password): AuthResult;
    public function generateJWT(User $user): string;
    public function validateJWT(string $token): User;
    public function enableTwoFactor(User $user): TwoFactorSetup;
    public function refreshToken(string $refreshToken): AuthResult;
}
```

**Technical Analysis Service** ✅ IMPLEMENTED
```php
namespace App\Services\Calculators;

class TechnicalAnalysisService {
    public function calculateIndicator(string $symbol, string $indicator, array $params): array;
    public function detectPatterns(string $symbol): array;
    public function generateCompositeSignal(string $symbol, array $indicators): CompositeSignal;
    public function getHistoricalAnalysis(string $symbol, \DateTime $from, \DateTime $to): array;
}
```

**Portfolio Service**
```php
namespace App\Services;

class PortfolioService {
    public function createPortfolio(int $userId, string $name): Portfolio;
    public function addPosition(int $portfolioId, string $symbol, float $quantity, float $price): Position;
    public function calculatePerformance(int $portfolioId): PerformanceMetrics;
    public function getPortfolioValue(int $portfolioId): float;
    public function getRiskMetrics(int $portfolioId): RiskMetrics;
}
```

**Alert Service**
```php
namespace App\Services;

class AlertService {
    public function createAlert(int $userId, AlertDefinition $alert): Alert;
    public function checkAlerts(string $symbol, MarketData $data): array;
    public function triggerAlert(Alert $alert, array $data): void;
    public function getActiveAlerts(int $userId): array;
}
```

### 2.3 Data Flow Architecture

#### 2.3.1 Real-time Data Flow
```
Market Data → Data Ingestion → Validation → Technical Analysis → Alert Check → User Notification
     ↓              ↓             ↓              ↓              ↓              ↓
Time Series DB → Redis Cache → Indicator Cache → Alert Queue → Notification Queue → SMS/Email
```

#### 2.3.2 Batch Processing Flow
```
Historical Data → ETL Process → Database Storage → Technical Analysis → Backtesting → Report Generation
```

---

## 3. Database Design

### 3.1 Core Schema

#### 3.1.1 User Management Tables
```sql
-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    timezone VARCHAR(50) DEFAULT 'UTC',
    subscription_tier ENUM('FREE', 'PREMIUM', 'PROFESSIONAL') DEFAULT 'FREE',
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(32),
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    INDEX idx_email (email),
    INDEX idx_subscription (subscription_tier),
    INDEX idx_active (is_active)
);

-- User preferences
CREATE TABLE user_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id, preference_key)
);

-- User sessions
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_sessions (user_id),
    INDEX idx_expires (expires_at)
);
```

#### 3.1.2 Market Data Tables
```sql
-- Companies/Symbols
CREATE TABLE companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    exchange VARCHAR(10) NOT NULL,
    sector VARCHAR(100),
    industry VARCHAR(100),
    market_cap BIGINT,
    description TEXT,
    website VARCHAR(255),
    employees INT,
    headquarters VARCHAR(100),
    ceo VARCHAR(100),
    founded_year INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_symbol (symbol),
    INDEX idx_exchange (exchange),
    INDEX idx_sector (sector),
    INDEX idx_active (is_active)
);

-- Daily price data
CREATE TABLE daily_prices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    open DECIMAL(12,4) NOT NULL,
    high DECIMAL(12,4) NOT NULL,
    low DECIMAL(12,4) NOT NULL,
    close DECIMAL(12,4) NOT NULL,
    volume BIGINT NOT NULL DEFAULT 0,
    adjusted_close DECIMAL(12,4),
    dividend_amount DECIMAL(8,4) DEFAULT 0,
    split_coefficient DECIMAL(8,4) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_symbol_date (symbol, date),
    INDEX idx_symbol (symbol),
    INDEX idx_date (date),
    INDEX idx_symbol_date_range (symbol, date)
);

-- Intraday price data (for real-time analysis)
CREATE TABLE intraday_prices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(10) NOT NULL,
    timestamp TIMESTAMP NOT NULL,
    open DECIMAL(12,4) NOT NULL,
    high DECIMAL(12,4) NOT NULL,
    low DECIMAL(12,4) NOT NULL,
    close DECIMAL(12,4) NOT NULL,
    volume BIGINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_symbol_timestamp (symbol, timestamp),
    INDEX idx_timestamp (timestamp)
) PARTITION BY RANGE (UNIX_TIMESTAMP(timestamp)) (
    PARTITION p_current VALUES LESS THAN (UNIX_TIMESTAMP('2026-01-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

#### 3.1.3 Technical Analysis Tables ✅ IMPLEMENTED
```sql
-- Per-symbol technical indicators (dynamic table creation)
CREATE TABLE {SYMBOL}_technical_indicators (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    indicator_type VARCHAR(50) NOT NULL,
    value DECIMAL(15,8),
    parameters JSON,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_symbol_date_indicator (symbol, date, indicator_type),
    INDEX idx_symbol_date (symbol, date),
    INDEX idx_indicator_type (indicator_type)
);

-- Per-symbol candlestick patterns (dynamic table creation)
CREATE TABLE {SYMBOL}_candlestick_patterns (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    pattern_name VARCHAR(50) NOT NULL,
    strength INT NOT NULL,
    signal ENUM('BULLISH', 'BEARISH', 'NEUTRAL') NOT NULL,
    timeframe VARCHAR(20) DEFAULT 'daily',
    detection_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_symbol_date (symbol, date),
    INDEX idx_pattern_name (pattern_name),
    INDEX idx_signal (signal)
);

-- Signal history
CREATE TABLE signal_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(10) NOT NULL,
    signal_type ENUM('BUY', 'SELL', 'HOLD') NOT NULL,
    signal_source VARCHAR(100) NOT NULL, -- 'RSI', 'MACD', 'COMPOSITE', etc.
    strength INT NOT NULL, -- 0-100
    price DECIMAL(12,4) NOT NULL,
    volume BIGINT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_symbol_created (symbol, created_at),
    INDEX idx_signal_type (signal_type),
    INDEX idx_source (signal_source)
);
```

#### 3.1.4 Portfolio Management Tables
```sql
-- Portfolios
CREATE TABLE portfolios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    base_currency VARCHAR(3) DEFAULT 'USD',
    initial_value DECIMAL(15,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_portfolios (user_id),
    INDEX idx_active (is_active)
);

-- Portfolio positions
CREATE TABLE portfolio_positions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    portfolio_id INT NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    quantity DECIMAL(15,8) NOT NULL,
    average_cost DECIMAL(12,4) NOT NULL,
    current_price DECIMAL(12,4),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_portfolio_symbol (portfolio_id, symbol),
    INDEX idx_portfolio (portfolio_id),
    INDEX idx_symbol (symbol)
);

-- Transaction history
CREATE TABLE transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    portfolio_id INT NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    transaction_type ENUM('BUY', 'SELL', 'DIVIDEND', 'SPLIT') NOT NULL,
    quantity DECIMAL(15,8) NOT NULL,
    price DECIMAL(12,4) NOT NULL,
    commission DECIMAL(8,4) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    transaction_date TIMESTAMP NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    INDEX idx_portfolio_date (portfolio_id, transaction_date),
    INDEX idx_symbol (symbol),
    INDEX idx_type (transaction_type)
);
```

#### 3.1.5 Alert System Tables
```sql
-- User alerts
CREATE TABLE user_alerts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    symbol VARCHAR(10),
    alert_type ENUM('PRICE', 'INDICATOR', 'PATTERN', 'VOLUME', 'NEWS') NOT NULL,
    condition_type ENUM('ABOVE', 'BELOW', 'CROSSES_ABOVE', 'CROSSES_BELOW', 'EQUALS', 'CHANGES') NOT NULL,
    threshold_value DECIMAL(15,8),
    indicator_name VARCHAR(50),
    indicator_parameters JSON,
    notification_methods JSON, -- ['email', 'sms', 'push']
    is_active BOOLEAN DEFAULT TRUE,
    trigger_count INT DEFAULT 0,
    last_triggered TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_symbol (symbol),
    INDEX idx_type (alert_type)
);

-- Alert history
CREATE TABLE alert_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    alert_id BIGINT NOT NULL,
    triggered_at TIMESTAMP NOT NULL,
    trigger_value DECIMAL(15,8),
    market_price DECIMAL(12,4),
    notification_sent BOOLEAN DEFAULT FALSE,
    notification_methods JSON,
    metadata JSON,
    
    FOREIGN KEY (alert_id) REFERENCES user_alerts(id) ON DELETE CASCADE,
    INDEX idx_alert_triggered (alert_id, triggered_at),
    INDEX idx_triggered_at (triggered_at)
);
```

### 3.2 Database Optimization

#### 3.2.1 Indexing Strategy
```sql
-- Composite indexes for common query patterns
CREATE INDEX idx_prices_symbol_date_range ON daily_prices(symbol, date, close);
CREATE INDEX idx_indicators_symbol_type_date ON {SYMBOL}_technical_indicators(symbol, indicator_type, date);
CREATE INDEX idx_patterns_symbol_date_signal ON {SYMBOL}_candlestick_patterns(symbol, date, signal);

-- Covering indexes for frequently accessed data
CREATE INDEX idx_portfolio_performance ON portfolio_positions(portfolio_id, symbol, quantity, average_cost, current_price);
```

#### 3.2.2 Partitioning Strategy
```sql
-- Partition large tables by date for better performance
ALTER TABLE daily_prices PARTITION BY RANGE (YEAR(date)) (
    PARTITION p2020 VALUES LESS THAN (2021),
    PARTITION p2021 VALUES LESS THAN (2022),
    PARTITION p2022 VALUES LESS THAN (2023),
    PARTITION p2023 VALUES LESS THAN (2024),
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

---

## 4. API Design

### 4.1 RESTful API Endpoints

#### 4.1.1 Authentication Endpoints
```http
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/logout
POST /api/v1/auth/refresh
POST /api/v1/auth/forgot-password
POST /api/v1/auth/reset-password
POST /api/v1/auth/verify-email
POST /api/v1/auth/enable-2fa
POST /api/v1/auth/verify-2fa
```

#### 4.1.2 Technical Analysis Endpoints
```http
GET /api/v1/analysis/{symbol}/indicators
GET /api/v1/analysis/{symbol}/indicators/{indicator}
GET /api/v1/analysis/{symbol}/patterns
GET /api/v1/analysis/{symbol}/signals
GET /api/v1/analysis/{symbol}/composite-signal
POST /api/v1/analysis/{symbol}/calculate

# Example responses:
GET /api/v1/analysis/AAPL/indicators/rsi
{
    "symbol": "AAPL",
    "indicator": "RSI",
    "period": 14,
    "current_value": 67.34,
    "signal": "NEUTRAL",
    "strength": 45,
    "data": [
        {
            "date": "2025-09-18",
            "value": 67.34,
            "signal": "NEUTRAL"
        }
    ],
    "metadata": {
        "calculation_time": "2025-09-18T10:30:00Z",
        "data_points": 100,
        "accuracy": 99.9
    }
}
```

#### 4.1.3 Portfolio Management Endpoints
```http
GET /api/v1/portfolios
POST /api/v1/portfolios
GET /api/v1/portfolios/{id}
PUT /api/v1/portfolios/{id}
DELETE /api/v1/portfolios/{id}

GET /api/v1/portfolios/{id}/positions
POST /api/v1/portfolios/{id}/positions
PUT /api/v1/portfolios/{id}/positions/{symbol}
DELETE /api/v1/portfolios/{id}/positions/{symbol}

GET /api/v1/portfolios/{id}/performance
GET /api/v1/portfolios/{id}/risk-metrics
GET /api/v1/portfolios/{id}/transactions
```

#### 4.1.4 Alert System Endpoints
```http
GET /api/v1/alerts
POST /api/v1/alerts
GET /api/v1/alerts/{id}
PUT /api/v1/alerts/{id}
DELETE /api/v1/alerts/{id}
POST /api/v1/alerts/{id}/test

# WebSocket for real-time alerts
WS /api/v1/alerts/stream
```

### 4.2 API Authentication & Security

#### 4.2.1 JWT Implementation
```php
class JWTService {
    private string $secretKey;
    private string $algorithm = 'HS256';
    private int $accessTokenTTL = 3600; // 1 hour
    private int $refreshTokenTTL = 604800; // 7 days
    
    public function generateTokens(User $user): array {
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->accessTokenTTL
        ];
    }
    
    private function generateAccessToken(User $user): string {
        $payload = [
            'user_id' => $user->id,
            'email' => $user->email,
            'subscription_tier' => $user->subscription_tier,
            'iat' => time(),
            'exp' => time() + $this->accessTokenTTL
        ];
        
        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }
}
```

#### 4.2.2 Rate Limiting
```php
class RateLimiter {
    private Redis $redis;
    
    public function checkRateLimit(string $key, int $limit, int $window): bool {
        $current = $this->redis->incr($key);
        
        if ($current === 1) {
            $this->redis->expire($key, $window);
        }
        
        return $current <= $limit;
    }
    
    public function getRateLimits(string $tier): array {
        return [
            'FREE' => ['requests' => 100, 'window' => 3600],
            'PREMIUM' => ['requests' => 1000, 'window' => 3600],
            'PROFESSIONAL' => ['requests' => 10000, 'window' => 3600]
        ][$tier] ?? ['requests' => 60, 'window' => 3600];
    }
}
```

---

## 5. Technical Analysis Engine Design ✅ IMPLEMENTED

### 5.1 Calculator Architecture
```php
abstract class TALibCalculatorBase {
    protected function validatePriceData(array $priceData, array $requiredFields): void;
    protected function extractArrays(array $priceData, array $fields): array;
    protected function formatError(string $errorType, string $message): array;
}

// Implemented calculators:
class RSICalculator extends TALibCalculatorBase
class MACDCalculator extends TALibCalculatorBase
class MovingAverageCalculator extends TALibCalculatorBase
class CandlestickPatternCalculator extends TALibCalculatorBase
class HilbertTransformCalculator extends TALibCalculatorBase
class VolumeIndicatorsCalculator extends TALibCalculatorBase
class StatisticalIndicatorsCalculator extends TALibCalculatorBase
```

### 5.2 Signal Processing Pipeline
```php
class SignalProcessor {
    public function generateCompositeSignal(
        string $symbol, 
        array $indicators, 
        array $weights
    ): CompositeSignal {
        $signals = [];
        $totalWeight = 0;
        $weightedScore = 0;
        
        foreach ($indicators as $indicator => $config) {
            $calculator = $this->getCalculator($indicator);
            $result = $calculator->calculate($this->getPriceData($symbol), $config);
            
            if (!isset($result['error'])) {
                $signal = $this->extractSignal($result);
                $weight = $weights[$indicator] ?? 1.0;
                
                $signals[] = [
                    'indicator' => $indicator,
                    'signal' => $signal['type'],
                    'strength' => $signal['strength'],
                    'weight' => $weight
                ];
                
                $weightedScore += $signal['score'] * $weight;
                $totalWeight += $weight;
            }
        }
        
        $compositeScore = $totalWeight > 0 ? $weightedScore / $totalWeight : 0;
        
        return new CompositeSignal([
            'symbol' => $symbol,
            'composite_score' => $compositeScore,
            'signal_type' => $this->determineSignalType($compositeScore),
            'confidence' => $this->calculateConfidence($signals),
            'individual_signals' => $signals,
            'generated_at' => new \DateTime()
        ]);
    }
}
```

### 5.3 Real-time Processing
```php
class RealTimeProcessor {
    private MessageQueue $queue;
    private Redis $cache;
    
    public function processMarketData(MarketDataEvent $event): void {
        // 1. Update price cache
        $this->cache->setex(
            "price:{$event->symbol}", 
            300, 
            json_encode($event->data)
        );
        
        // 2. Trigger indicator calculations
        $this->queue->publish('calculate_indicators', [
            'symbol' => $event->symbol,
            'data' => $event->data,
            'timestamp' => $event->timestamp
        ]);
        
        // 3. Check alerts
        $this->queue->publish('check_alerts', [
            'symbol' => $event->symbol,
            'price' => $event->data['close'],
            'volume' => $event->data['volume']
        ]);
    }
}
```

---

## 6. Security Architecture

### 6.1 Security Layers

#### 6.1.1 Network Security
```nginx
# Nginx configuration for security
server {
    listen 443 ssl http2;
    server_name api.tradingplatform.com;
    
    # SSL/TLS configuration
    ssl_certificate /path/to/certificate.pem;
    ssl_certificate_key /path/to/private-key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    
    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req zone=api burst=20 nodelay;
    
    location / {
        proxy_pass http://app_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

#### 6.1.2 Application Security
```php
class SecurityMiddleware {
    public function handle(Request $request, Closure $next): Response {
        // 1. Validate JWT token
        $token = $this->extractToken($request);
        if (!$token || !$this->jwtService->validateToken($token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // 2. Check rate limits
        $user = $this->jwtService->getUserFromToken($token);
        if (!$this->rateLimiter->checkLimit($user)) {
            return response()->json(['error' => 'Rate limit exceeded'], 429);
        }
        
        // 3. Validate request integrity
        if (!$this->validateRequestIntegrity($request)) {
            return response()->json(['error' => 'Invalid request'], 400);
        }
        
        // 4. Log security events
        $this->securityLogger->logAccess($user, $request);
        
        return $next($request);
    }
}
```

#### 6.1.3 Data Encryption
```php
class EncryptionService {
    private string $encryptionKey;
    
    public function encrypt(string $data): string {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    public function decrypt(string $encryptedData): string {
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $this->encryptionKey, 0, $iv);
    }
}
```

---

## 7. Performance Optimization

### 7.1 Caching Strategy

#### 7.1.1 Redis Cache Implementation
```php
class CacheManager {
    private Redis $redis;
    
    public function cacheIndicatorResult(string $symbol, string $indicator, array $result): void {
        $key = "indicator:{$symbol}:{$indicator}";
        $this->redis->setex($key, 300, json_encode($result)); // 5 minute cache
    }
    
    public function getCachedIndicator(string $symbol, string $indicator): ?array {
        $key = "indicator:{$symbol}:{$indicator}";
        $cached = $this->redis->get($key);
        return $cached ? json_decode($cached, true) : null;
    }
    
    public function cachePriceData(string $symbol, array $priceData): void {
        $key = "prices:{$symbol}";
        $this->redis->setex($key, 60, json_encode($priceData)); // 1 minute cache
    }
}
```

#### 7.1.2 Database Query Optimization
```php
class OptimizedQueries {
    public function getRecentPrices(string $symbol, int $days = 100): array {
        // Use covering index and limit results
        $sql = "
            SELECT date, open, high, low, close, volume 
            FROM daily_prices 
            WHERE symbol = ? 
            ORDER BY date DESC 
            LIMIT ?
        ";
        
        return $this->db->prepare($sql)->execute([$symbol, $days])->fetchAll();
    }
    
    public function getIndicatorBatch(array $symbols, string $indicator, string $date): array {
        // Batch query for multiple symbols
        $placeholders = str_repeat('?,', count($symbols) - 1) . '?';
        $tables = array_map(fn($s) => "{$s}_technical_indicators", $symbols);
        
        $sql = "
            SELECT symbol, value, metadata 
            FROM (" . implode(' UNION ALL ', array_map(fn($t) => "
                SELECT symbol, value, metadata FROM {$t} 
                WHERE indicator_type = ? AND date = ?
            ", $tables)) . ") AS combined_indicators
        ";
        
        $params = [];
        foreach ($symbols as $symbol) {
            $params[] = $indicator;
            $params[] = $date;
        }
        
        return $this->db->prepare($sql)->execute($params)->fetchAll();
    }
}
```

### 7.2 Asynchronous Processing

#### 7.2.1 Job Queue Implementation
```php
class JobProcessor {
    private MessageQueue $queue;
    
    public function queueIndicatorCalculation(string $symbol, array $indicators): void {
        $job = [
            'type' => 'calculate_indicators',
            'symbol' => $symbol,
            'indicators' => $indicators,
            'priority' => 'normal',
            'created_at' => time()
        ];
        
        $this->queue->publish('indicator_calculations', $job);
    }
    
    public function processIndicatorJob(array $job): void {
        $symbol = $job['symbol'];
        $priceData = $this->getPriceData($symbol);
        
        foreach ($job['indicators'] as $indicator => $config) {
            try {
                $calculator = $this->getCalculator($indicator);
                $result = $calculator->calculate($priceData, $config);
                
                if (!isset($result['error'])) {
                    $this->saveIndicatorResult($symbol, $indicator, $result);
                    $this->checkAlertsForIndicator($symbol, $indicator, $result);
                }
            } catch (\Exception $e) {
                $this->logger->error("Indicator calculation failed", [
                    'symbol' => $symbol,
                    'indicator' => $indicator,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
```

---

## 8. Deployment Architecture

### 8.1 Container Configuration

#### 8.1.1 Docker Compose Setup
```yaml
version: '3.8'

services:
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/ssl/certs
    depends_on:
      - app

  app:
    build: .
    environment:
      - DB_HOST=database
      - REDIS_HOST=redis
      - JWT_SECRET=${JWT_SECRET}
    volumes:
      - ./src:/var/www/html
    depends_on:
      - database
      - redis

  database:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
      - MYSQL_DATABASE=${DB_NAME}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./init.sql:/docker-entrypoint-initdb.d/init.sql

  redis:
    image: redis:alpine
    command: redis-server --requirepass ${REDIS_PASSWORD}

  worker:
    build: .
    command: php artisan queue:work
    environment:
      - DB_HOST=database
      - REDIS_HOST=redis
    depends_on:
      - database
      - redis

volumes:
  mysql_data:
```

### 8.2 Kubernetes Deployment
```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: trading-platform-app
spec:
  replicas: 3
  selector:
    matchLabels:
      app: trading-platform
  template:
    metadata:
      labels:
        app: trading-platform
    spec:
      containers:
      - name: app
        image: trading-platform:latest
        ports:
        - containerPort: 9000
        env:
        - name: DB_HOST
          value: "mysql-service"
        - name: REDIS_HOST
          value: "redis-service"
        resources:
          requests:
            memory: "256Mi"
            cpu: "250m"
          limits:
            memory: "512Mi"
            cpu: "500m"
```

### 8.3 Monitoring and Logging
```php
class ApplicationMonitoring {
    private MetricsCollector $metrics;
    private Logger $logger;
    
    public function recordIndicatorCalculation(string $indicator, float $executionTime): void {
        $this->metrics->increment('indicator.calculations.total', [
            'indicator' => $indicator
        ]);
        
        $this->metrics->histogram('indicator.calculation.duration', $executionTime, [
            'indicator' => $indicator
        ]);
    }
    
    public function recordAPIRequest(string $endpoint, int $statusCode, float $duration): void {
        $this->metrics->increment('api.requests.total', [
            'endpoint' => $endpoint,
            'status_code' => $statusCode
        ]);
        
        $this->metrics->histogram('api.request.duration', $duration, [
            'endpoint' => $endpoint
        ]);
    }
}
```

This technical design document provides the comprehensive blueprint for implementing the financial analysis platform. The architecture is designed for scalability, security, and performance while maintaining the flexibility to evolve with changing requirements.
