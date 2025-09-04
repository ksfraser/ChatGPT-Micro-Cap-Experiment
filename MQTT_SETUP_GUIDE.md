# MQTT/Mosquitto Setup Guide

This guide explains how to configure and use the MQTT backend with your existing Mosquitto broker for the distributed job processing system.

## Prerequisites

- **Mosquitto Broker**: Already running (you mentioned you have this)
- **PHP Mosquitto Extension**: Required for PHP to communicate with MQTT

## Installation

### 1. Install PHP Mosquitto Extension

#### Ubuntu/Debian:
```bash
sudo apt-get update
sudo apt-get install php-mosquitto
```

#### CentOS/RHEL/Fedora:
```bash
sudo yum install php-mosquitto
# or for newer versions:
sudo dnf install php-mosquitto
```

#### From Source (if package not available):
```bash
# Install libmosquitto development files
sudo apt-get install libmosquitto-dev  # Ubuntu/Debian
sudo yum install mosquitto-devel       # CentOS/RHEL

# Install PHP extension via PECL
sudo pecl install Mosquitto-alpha

# Add to php.ini
echo "extension=mosquitto.so" | sudo tee -a /etc/php/7.4/cli/php.ini
```

### 2. Verify Installation
```bash
php -m | grep mosquitto
```

## Configuration

### 1. Update Job Processor Configuration

Edit your `job_processor.yml`:

```yaml
job_processor:
  queue:
    # Use MQTT backend
    backend: "mqtt"
    
    # MQTT/Mosquitto configuration
    mqtt:
      host: "localhost"          # Your Mosquitto broker host
      port: 1883                 # Standard MQTT port (or 8883 for SSL)
      username: null             # Set if your broker requires auth
      password: null             # Set if your broker requires auth
      keepalive: 60             # Keep-alive interval
      client_id_prefix: "stockworker"
```

### 2. Mosquitto Broker Configuration

If you need to configure your Mosquitto broker for the job system, create/edit `/etc/mosquitto/mosquitto.conf`:

```conf
# Basic configuration
listener 1883 localhost
allow_anonymous true

# Optional: Enable persistence
persistence true
persistence_location /var/lib/mosquitto/

# Optional: Enable logging
log_dest file /var/log/mosquitto/mosquitto.log
log_type error
log_type warning
log_type notice
log_type information

# Optional: Authentication (if needed)
# password_file /etc/mosquitto/passwd
# acl_file /etc/mosquitto/acl
```

### 3. Authentication Setup (Optional)

If you want to secure your MQTT broker:

```bash
# Create password file
sudo mosquitto_passwd -c /etc/mosquitto/passwd stockworker

# Create ACL file for topic access control
sudo tee /etc/mosquitto/acl << EOF
# Stock worker topics
user stockworker
topic readwrite jobs/#
topic readwrite workers/#
topic readwrite system/#
EOF

# Update mosquitto.conf
echo "password_file /etc/mosquitto/passwd" | sudo tee -a /etc/mosquitto/mosquitto.conf
echo "acl_file /etc/mosquitto/acl" | sudo tee -a /etc/mosquitto/mosquitto.conf

# Restart Mosquitto
sudo systemctl restart mosquitto
```

## MQTT Topic Structure

The system uses the following topic hierarchy:

```
jobs/
├── queue/
│   ├── high/
│   │   ├── technical_analysis
│   │   ├── price_update
│   │   └── data_import
│   ├── normal/
│   │   ├── technical_analysis
│   │   ├── price_update
│   │   └── data_import
│   └── low/
│       ├── technical_analysis
│       ├── price_update
│       └── data_import
├── assign/
│   └── {worker_id}         # Job assignments to specific workers
├── request/
│   └── {worker_id}         # Job requests from workers
├── completed/
│   └── {job_id}           # Job completion notifications
├── failed/
│   └── {job_id}           # Job failure notifications
└── retry/
    └── {job_id}           # Job retry requests

workers/
├── register               # Worker registration
├── heartbeat/
│   └── {worker_id}       # Worker heartbeat messages
├── unregister            # Worker unregistration
├── disconnect            # Unexpected disconnections
└── cleanup               # Cleanup requests

system/
├── stats/
│   ├── request           # Statistics requests
│   └── response          # Statistics responses
└── monitor               # System monitoring messages
```

## Starting the System

### 1. Test MQTT Connection

```bash
# Test basic connectivity
mosquitto_sub -h localhost -t "test/topic" &
mosquitto_pub -h localhost -t "test/topic" -m "Hello MQTT"
```

### 2. Start Workers with MQTT Backend

```bash
# Start local worker
php worker.php job_processor.yml

# Deploy to remote machines
./deploy-workers.sh deploy worker1.example.com
./deploy-workers.sh start worker1.example.com
```

### 3. Monitor MQTT Traffic

```bash
# Monitor all job-related topics
mosquitto_sub -h localhost -t "jobs/#" -v

# Monitor worker activity
mosquitto_sub -h localhost -t "workers/#" -v

# Monitor specific job queue
mosquitto_sub -h localhost -t "jobs/queue/normal/technical_analysis" -v
```

## Advantages of MQTT Backend

### vs Database Backend:
- **Lower Latency**: Real-time message delivery
- **Better Scalability**: Designed for high-throughput messaging
- **Network Efficiency**: Lightweight protocol, ideal for distributed systems
- **Event-Driven**: Workers notified immediately when jobs are available

### vs Redis Backend:
- **Standards-Based**: MQTT is an industry standard protocol
- **Better for WAN**: Optimized for unreliable networks
- **Quality of Service**: Built-in message delivery guarantees
- **Topic-Based Routing**: Natural job categorization

### vs RabbitMQ Backend:
- **Simpler Setup**: Mosquitto is lightweight and easy to configure
- **Lower Resource Usage**: Less memory and CPU overhead
- **IoT Optimized**: Designed for resource-constrained environments
- **Existing Infrastructure**: You already have Mosquitto running

## Monitoring and Debugging

### 1. View MQTT Broker Status
```bash
# Check Mosquitto status
sudo systemctl status mosquitto

# View Mosquitto logs
sudo tail -f /var/log/mosquitto/mosquitto.log
```

### 2. Monitor Job Processing
```bash
# Watch job assignments
mosquitto_sub -h localhost -t "jobs/assign/+" -v

# Watch job completions
mosquitto_sub -h localhost -t "jobs/completed/+" -v

# Watch worker heartbeats
mosquitto_sub -h localhost -t "workers/heartbeat/+" -v
```

### 3. Debug Worker Issues
```bash
# Check worker logs
tail -f logs/worker_*.log

# Test worker connectivity
php -r "
require 'MQTTJobBackend.php';
require 'JobLogger.php';
\$config = ['mqtt' => ['host' => 'localhost', 'port' => 1883]];
\$logger = new JobLogger('test.log');
\$backend = new MQTTJobBackend(\$config, \$logger);
echo 'MQTT connection successful';
"
```

## Performance Tuning

### 1. Mosquitto Broker Optimization

```conf
# /etc/mosquitto/mosquitto.conf

# Increase connection limits
max_connections 1000
max_inflight_messages 100

# Optimize memory usage
memory_limit 67108864  # 64MB

# Set message size limits
message_size_limit 268435456  # 256MB

# Connection timeout settings
keepalive_interval 60
```

### 2. Worker Configuration

```yaml
# job_processor.yml
job_processor:
  worker:
    max_concurrent_jobs: 5      # Increase for more parallelism
    poll_interval: 1            # Faster polling with MQTT
    heartbeat_interval: 30      # Regular heartbeats
```

### 3. Network Optimization

For distributed workers across WAN connections:

```yaml
mqtt:
  host: "your-mqtt-server.com"
  port: 8883                    # Use SSL/TLS for security
  keepalive: 120               # Longer keepalive for WAN
  username: "worker"
  password: "secure_password"
```

## Security Considerations

### 1. Enable SSL/TLS
```conf
# /etc/mosquitto/mosquitto.conf
listener 8883
cafile /etc/mosquitto/ca_certificates/ca.crt
certfile /etc/mosquitto/certs/server.crt
keyfile /etc/mosquitto/certs/server.key
```

### 2. Network Security
- Use firewall rules to restrict MQTT port access
- Consider VPN for worker communications
- Implement topic-based access control

### 3. Message Encryption
For sensitive job data, consider encrypting message payloads before publishing.

## Troubleshooting

### Common Issues:

1. **"Mosquitto\Client class not found"**
   - Install php-mosquitto extension
   - Verify with `php -m | grep mosquitto`

2. **Connection refused**
   - Check Mosquitto is running: `sudo systemctl status mosquitto`
   - Verify port and host configuration
   - Check firewall settings

3. **Authentication failed**
   - Verify username/password in configuration
   - Check mosquitto.conf authentication settings

4. **Messages not received**
   - Verify topic subscriptions
   - Check QoS levels
   - Monitor broker logs

5. **High latency**
   - Tune keepalive settings
   - Check network connectivity
   - Consider QoS level adjustments

## Migration from Other Backends

To migrate from Database/Redis/RabbitMQ to MQTT:

1. **Stop all workers**
2. **Update configuration** to use MQTT backend
3. **Migrate pending jobs** (if needed)
4. **Start workers** with new configuration
5. **Monitor** job processing to ensure smooth transition

The system will automatically handle the backend switch without losing job data stored in the database.
