# Distributed Job Processing System

A flexible, scalable background job processing system designed for stock market analysis and data processing. The system supports multiple backends (Database, Redis, RabbitMQ) and can scale across multiple machines and VMs.

## Features

- **Multi-Backend Support**: Database, Redis, RabbitMQ, and MQTT (Mosquitto) backends
- **Distributed Workers**: Deploy workers on multiple machines, VMs, and Fedora boxes
- **Job Types**: Technical analysis, price updates, data imports, portfolio analysis
- **Web Monitoring**: Real-time dashboard for monitoring jobs, workers, and system health
- **Process Management**: Graceful shutdown, job timeout handling, worker heartbeat monitoring
- **Flexible Configuration**: YAML-based configuration for easy deployment management

## Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Web Dashboard │    │  Job Processors  │    │   Worker Pool   │
│   (monitor.html)│    │  (JobProcessor)  │    │  (Multiple VMs) │
└─────────┬───────┘    └─────────┬────────┘    └─────────┬───────┘
          │                      │                       │
          │                      │                       │
     ┌────▼─────────────────────▼───────────────────────▼────┐
     │              Job Queue Backend                        │
     │ (Database / Redis / RabbitMQ / MQTT/Mosquitto)      │
     └─────────────────────────────────────────────────────┘
```

## Quick Start

### 1. Setup Local Environment

```bash
# Make deployment script executable
chmod +x deploy-workers.sh

# Setup local environment
./deploy-workers.sh setup-local

# Test configuration
./deploy-workers.sh test-config
```

### 2. Configure System

Copy the example configuration:

```bash
cp job_processor.example.yml job_processor.yml
```

Edit `job_processor.yml` with your settings:

```yaml
# Queue Configuration
queue:
  backend: "database"  # database, redis, or rabbitmq
  
# Worker Configuration  
worker:
  max_concurrent_jobs: 3
  poll_interval: 5
  job_timeout: 3600
  log_file: "logs/worker.log"

# Database Configuration
database:
  host: "localhost"
  port: 3306
  username: "your_username"
  password: "your_password"
  database: "your_database"
```

### 3. Start Local Worker

```bash
# Start a worker locally
php worker.php job_processor.yml

# Or run in background
nohup php worker.php job_processor.yml > logs/worker.out 2>&1 &
```

### 4. Deploy to Remote Machines

```bash
# Deploy to a single machine
./deploy-workers.sh deploy worker1.example.com -u ubuntu -k ~/.ssh/id_rsa

# Start worker on remote machine
./deploy-workers.sh start worker1.example.com -u ubuntu -k ~/.ssh/id_rsa

# Deploy to all configured machines
./deploy-workers.sh deploy-all

# Check status of all workers
./deploy-workers.sh status-all
```

### 5. Monitor System

Open `monitor.html` in your web browser to access the monitoring dashboard.

## Components

### Core Classes

#### JobProcessor.php
Main job processing engine that:
- Manages worker lifecycle (registration, heartbeat, shutdown)
- Polls for jobs from the backend
- Executes jobs using process forking
- Handles job timeouts and failures

#### Job Backends

- **DatabaseJobBackend.php**: Uses MySQL database for job queue
- **RedisJobBackend.php**: Uses Redis for high-performance queuing
- **RabbitMQJobBackend.php**: Uses RabbitMQ for enterprise messaging

#### Job Processors

- **TechnicalAnalysisJobProcessor**: Calculates RSI, MACD, SMA, detects candlestick patterns
- **PriceUpdateJobProcessor**: Updates stock prices from external sources
- **DataImportJobProcessor**: Handles bulk data imports
- **PortfolioAnalysisJobProcessor**: Analyzes portfolio performance and risk

### Deployment Tools

#### worker.php
Standalone worker script that can be deployed on any machine with PHP. Features:
- Automatic backend detection and initialization
- Process forking for job isolation
- Signal handling for graceful shutdown
- Heartbeat monitoring
- Job timeout management

#### deploy-workers.sh
Bash script for deploying and managing workers across multiple machines:

```bash
# Commands
deploy <host>          # Deploy worker to remote host
start <host>           # Start worker on remote host  
stop <host>            # Stop worker on remote host
restart <host>         # Restart worker on remote host
status <host>          # Check worker status
logs <host>            # View worker logs
deploy-all             # Deploy to all configured hosts
start-all              # Start workers on all hosts
stop-all               # Stop workers on all hosts
status-all             # Check status on all hosts
```

### Monitoring

#### monitor.html
Web dashboard providing:
- Real-time system overview (active workers, job counts)
- Worker status and health monitoring
- Queue status and job distribution
- Job history and statistics
- System logs viewing
- Job management (add test jobs, view details)

#### monitor_api.php
REST API backend for the monitoring dashboard:
- `GET /monitor_api.php?action=overview` - System statistics
- `GET /monitor_api.php?action=workers` - Active workers
- `GET /monitor_api.php?action=jobs` - Recent jobs
- `POST /monitor_api.php?action=add_job` - Add test job

## Configuration

### Backend Selection

#### Database Backend (Default)
```yaml
queue:
  backend: "database"
database:
  host: "localhost"
  username: "user"
  password: "pass"  
  database: "stockdb"
```

#### Redis Backend
```yaml
queue:
  backend: "redis"
redis:
  host: "localhost"
  port: 6379
  password: null
  database: 0
```

#### RabbitMQ Backend
```yaml
queue:
  backend: "rabbitmq"
rabbitmq:
  host: "localhost"
  port: 5672
  user: "guest"
  password: "guest"
  vhost: "/"
```

#### MQTT/Mosquitto Backend
```yaml
queue:
  backend: "mqtt"
mqtt:
  host: "localhost"
  port: 1883
  username: null
  password: null
  keepalive: 60
```

### Worker Configuration
```yaml
worker:
  max_concurrent_jobs: 3    # Jobs per worker
  poll_interval: 5          # Seconds between job checks
  job_timeout: 3600         # Job timeout in seconds
  log_file: "logs/worker.log"
```

### Multi-Machine Setup
```yaml
hosts:
  - host: "worker1.example.com"
    user: "ubuntu"
    key: "~/.ssh/id_rsa"
  - host: "worker2.example.com" 
    user: "fedora"
    key: "~/.ssh/id_rsa"
```

## Job Types

### Technical Analysis Jobs
Calculates technical indicators and detects patterns:
- RSI (Relative Strength Index)
- MACD (Moving Average Convergence Divergence)
- Simple Moving Averages
- Candlestick patterns (Doji, Hammer, etc.)

```php
// Add technical analysis job
$jobData = [
    'job_type' => 'technical_analysis',
    'priority' => 'normal',
    'parameters' => [
        'stockId' => 123,  // Specific stock, or null for all stocks
        'indicators' => ['RSI', 'MACD', 'SMA_20']
    ]
];
```

### Price Update Jobs
Updates stock prices from external data sources:

```php
$jobData = [
    'job_type' => 'price_update',
    'priority' => 'high',
    'parameters' => [
        'symbols' => ['AAPL', 'GOOGL', 'MSFT'],
        'source' => 'yahoo_finance'
    ]
];
```

### Data Import Jobs
Handles bulk data imports:

```php
$jobData = [
    'job_type' => 'data_import',
    'priority' => 'low',
    'parameters' => [
        'file_path' => '/path/to/data.csv',
        'import_type' => 'historical_prices'
    ]
];
```

## Database Schema

The system uses the `ta_analysis_jobs` table for job management:

```sql
CREATE TABLE ta_analysis_jobs (
    id VARCHAR(255) PRIMARY KEY,
    job_type VARCHAR(100) NOT NULL,
    priority ENUM('high', 'normal', 'low') DEFAULT 'normal',
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    worker_id VARCHAR(255),
    hostname VARCHAR(255),
    parameters TEXT,
    result TEXT,
    progress INT DEFAULT 0,
    status_message TEXT,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_worker (worker_id),
    INDEX idx_created (created_at),
    INDEX idx_job_type (job_type)
);
```

## Scaling

### Horizontal Scaling
- Deploy workers on multiple machines
- Use Redis or RabbitMQ for better performance
- Configure load balancers for web monitoring

### Vertical Scaling
- Increase `max_concurrent_jobs` per worker
- Use more powerful machines
- Optimize job processors for specific workloads

### Performance Tips
- Use Redis backend for high-throughput scenarios
- Implement job batching for bulk operations
- Monitor job execution times and optimize slow processors
- Use database indexing for job queries

## Monitoring and Maintenance

### Health Checks
- Worker heartbeat monitoring (automatic cleanup of dead workers)
- Job timeout detection and retry logic
- Queue size monitoring and alerts

### Logging
- Structured logging with levels (INFO, WARNING, ERROR)
- Log rotation to prevent disk space issues
- Centralized log aggregation for multi-machine deployments

### Backup and Recovery
- Regular database backups
- Job result archival
- Configuration management with version control

## Troubleshooting

### Common Issues

#### Workers Not Starting
```bash
# Check PHP requirements
php -m | grep -E "(pdo|mysql|json)"

# Check configuration
./deploy-workers.sh test-config

# Check logs
tail -f logs/worker_*.log
```

#### Jobs Not Processing
```bash
# Check worker status
./deploy-workers.sh status-all

# Check queue backend connectivity
php -r "require 'DatabaseConfig.php'; DatabaseConfig::createLegacyConnection();"

# Restart workers
./deploy-workers.sh restart-all
```

#### High Memory Usage
- Monitor job processor memory usage
- Implement job result cleanup
- Consider process recycling for long-running workers

### Performance Monitoring
- Use the web dashboard for real-time monitoring
- Set up alerts for queue size thresholds
- Monitor worker CPU and memory usage
- Track job completion rates and failure patterns

## Security Considerations

- Use SSH keys for remote deployment
- Secure database credentials in configuration files
- Implement job parameter validation
- Use network firewalls for backend services
- Regular security updates for all dependencies

## Extensions

### Adding New Job Types
1. Create a new job processor class extending `AbstractJobProcessor`
2. Register the processor in `worker.php`
3. Update the monitoring dashboard job types
4. Add appropriate database migrations if needed

### Custom Backends
1. Implement the backend interface methods
2. Add backend selection logic in `JobProcessor.php`
3. Update configuration schema
4. Test with the monitoring dashboard

## Support

For issues and questions:
1. Check the logs for error messages
2. Verify configuration settings
3. Test with a minimal job processor
4. Review the monitoring dashboard for system health
