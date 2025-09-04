#!/bin/bash

# Worker Deployment Script
# Deploys and manages workers on multiple machines

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEFAULT_CONFIG="job_processor.yml"
WORKER_SCRIPT="worker.php"
LOG_DIR="logs"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Show usage
show_usage() {
    cat << EOF
Usage: $0 [command] [options]

Commands:
    deploy <host>          Deploy worker to remote host
    start <host>           Start worker on remote host
    stop <host>            Stop worker on remote host
    restart <host>         Restart worker on remote host
    status <host>          Check worker status on remote host
    logs <host>            View worker logs on remote host
    deploy-all             Deploy to all configured hosts
    start-all              Start workers on all hosts
    stop-all               Stop workers on all hosts
    status-all             Check status on all hosts
    setup-local            Setup local environment
    test-config            Test configuration file

Options:
    -c, --config FILE      Configuration file (default: $DEFAULT_CONFIG)
    -u, --user USER        SSH user for remote connections
    -k, --key FILE         SSH private key file
    -p, --port PORT        SSH port (default: 22)
    -h, --help             Show this help message

Examples:
    $0 setup-local
    $0 deploy worker1.example.com
    $0 start worker1.example.com -u ubuntu -k ~/.ssh/id_rsa
    $0 deploy-all -c production.yml
    $0 status-all

EOF
}

# Parse command line arguments
parse_args() {
    COMMAND=""
    TARGET_HOST=""
    CONFIG_FILE="$DEFAULT_CONFIG"
    SSH_USER="$USER"
    SSH_KEY=""
    SSH_PORT="22"
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            deploy|start|stop|restart|status|logs)
                COMMAND="$1"
                if [[ -n "$2" && ! "$2" =~ ^- ]]; then
                    TARGET_HOST="$2"
                    shift
                fi
                ;;
            deploy-all|start-all|stop-all|status-all|setup-local|test-config)
                COMMAND="$1"
                ;;
            -c|--config)
                CONFIG_FILE="$2"
                shift
                ;;
            -u|--user)
                SSH_USER="$2"
                shift
                ;;
            -k|--key)
                SSH_KEY="$2"
                shift
                ;;
            -p|--port)
                SSH_PORT="$2"
                shift
                ;;
            -h|--help)
                show_usage
                exit 0
                ;;
            *)
                log_error "Unknown option: $1"
                show_usage
                exit 1
                ;;
        esac
        shift
    done
    
    if [[ -z "$COMMAND" ]]; then
        log_error "No command specified"
        show_usage
        exit 1
    fi
}

# SSH command builder
build_ssh_cmd() {
    local host="$1"
    local cmd="ssh"
    
    if [[ -n "$SSH_KEY" ]]; then
        cmd="$cmd -i $SSH_KEY"
    fi
    
    cmd="$cmd -p $SSH_PORT $SSH_USER@$host"
    echo "$cmd"
}

# SCP command builder
build_scp_cmd() {
    local src="$1"
    local host="$2"
    local dest="$3"
    local cmd="scp"
    
    if [[ -n "$SSH_KEY" ]]; then
        cmd="$cmd -i $SSH_KEY"
    fi
    
    cmd="$cmd -P $SSH_PORT $src $SSH_USER@$host:$dest"
    echo "$cmd"
}

# Get host list from config
get_hosts() {
    if [[ ! -f "$CONFIG_FILE" ]]; then
        log_error "Configuration file not found: $CONFIG_FILE"
        exit 1
    fi
    
    # Extract hosts from YAML config (simplified parsing)
    grep -E "^\s*-\s*host:" "$CONFIG_FILE" | sed 's/.*host:\s*//' | tr -d '"' || true
}

# Setup local environment
setup_local() {
    log_info "Setting up local environment..."
    
    # Create directories
    mkdir -p "$LOG_DIR"
    mkdir -p "tmp"
    
    # Check PHP CLI
    if ! command -v php &> /dev/null; then
        log_error "PHP CLI is not installed"
        exit 1
    fi
    
    log_success "PHP CLI found: $(php --version | head -n1)"
    
    # Check required PHP extensions
    local required_extensions=("pdo" "pdo_mysql" "json" "yaml")
    local missing_extensions=()
    
    for ext in "${required_extensions[@]}"; do
        if ! php -m | grep -q "^$ext$"; then
            missing_extensions+=("$ext")
        fi
    done
    
    if [[ ${#missing_extensions[@]} -gt 0 ]]; then
        log_warning "Missing PHP extensions: ${missing_extensions[*]}"
        log_info "Install with: sudo apt-get install $(printf 'php-%s ' "${missing_extensions[@]}")"
    fi
    
    # Check optional extensions
    local optional_extensions=("pcntl" "posix" "redis")
    for ext in "${optional_extensions[@]}"; do
        if php -m | grep -q "^$ext$"; then
            log_success "Optional extension $ext is available"
        else
            log_warning "Optional extension $ext is not available"
        fi
    done
    
    # Test configuration
    if [[ -f "$CONFIG_FILE" ]]; then
        log_info "Testing configuration..."
        php -r "
            require_once 'DatabaseConfig.php';
            try {
                \$config = DatabaseConfig::parseYaml(file_get_contents('$CONFIG_FILE'));
                echo 'Configuration is valid' . PHP_EOL;
            } catch (Exception \$e) {
                echo 'Configuration error: ' . \$e->getMessage() . PHP_EOL;
                exit(1);
            }
        "
        log_success "Configuration is valid"
    else
        log_warning "Configuration file not found: $CONFIG_FILE"
        log_info "Create one based on job_processor.example.yml"
    fi
    
    log_success "Local environment setup complete"
}

# Test configuration
test_config() {
    log_info "Testing configuration file: $CONFIG_FILE"
    
    if [[ ! -f "$CONFIG_FILE" ]]; then
        log_error "Configuration file not found: $CONFIG_FILE"
        exit 1
    fi
    
    # Test YAML syntax
    if command -v python3 &> /dev/null; then
        python3 -c "
import yaml
import sys
try:
    with open('$CONFIG_FILE', 'r') as f:
        yaml.safe_load(f)
    print('YAML syntax is valid')
except Exception as e:
    print(f'YAML syntax error: {e}')
    sys.exit(1)
        "
    else
        log_warning "Python3 not available for YAML validation"
    fi
    
    # Test PHP parsing
    php -r "
        require_once 'DatabaseConfig.php';
        try {
            \$config = DatabaseConfig::parseYaml(file_get_contents('$CONFIG_FILE'));
            echo 'PHP configuration parsing successful' . PHP_EOL;
            
            // Check required sections
            \$required = ['queue', 'worker', 'database'];
            foreach (\$required as \$section) {
                if (!isset(\$config[\$section])) {
                    echo 'Missing required section: ' . \$section . PHP_EOL;
                    exit(1);
                }
            }
            echo 'All required sections present' . PHP_EOL;
            
        } catch (Exception \$e) {
            echo 'Configuration error: ' . \$e->getMessage() . PHP_EOL;
            exit(1);
        }
    "
    
    log_success "Configuration test passed"
}

# Deploy worker to remote host
deploy_worker() {
    local host="$1"
    log_info "Deploying worker to $host..."
    
    # Create remote directory
    local ssh_cmd=$(build_ssh_cmd "$host")
    $ssh_cmd "mkdir -p ~/stock-worker/{logs,tmp}"
    
    # Copy files
    local files=(
        "$WORKER_SCRIPT"
        "DatabaseConfig.php"
        "JobLogger.php"
        "JobProcessors.php"
        "DatabaseJobBackend.php"
        "RedisJobBackend.php"
        "RabbitMQJobBackend.php"
        "MQTTJobBackend.php"
        "$CONFIG_FILE"
    )
    
    for file in "${files[@]}"; do
        if [[ -f "$file" ]]; then
            local scp_cmd=$(build_scp_cmd "$file" "$host" "~/stock-worker/")
            $scp_cmd
            log_info "Copied $file"
        else
            log_warning "File not found: $file"
        fi
    done
    
    # Copy Legacy directory if it exists
    if [[ -d "Stock-Analysis-Extension/Legacy" ]]; then
        $ssh_cmd "mkdir -p ~/stock-worker/Stock-Analysis-Extension"
        rsync -avz -e "ssh -p $SSH_PORT $([ -n "$SSH_KEY" ] && echo "-i $SSH_KEY")" \
            Stock-Analysis-Extension/Legacy/ \
            $SSH_USER@$host:~/stock-worker/Stock-Analysis-Extension/Legacy/
        log_info "Copied Legacy directory"
    fi
    
    # Set permissions
    $ssh_cmd "chmod +x ~/stock-worker/$WORKER_SCRIPT"
    
    log_success "Deployment to $host complete"
}

# Start worker on remote host
start_worker() {
    local host="$1"
    log_info "Starting worker on $host..."
    
    local ssh_cmd=$(build_ssh_cmd "$host")
    $ssh_cmd "cd ~/stock-worker && nohup php $WORKER_SCRIPT $CONFIG_FILE > logs/worker.out 2>&1 & echo \$! > worker.pid"
    
    # Check if worker started
    sleep 2
    local pid=$($ssh_cmd "cd ~/stock-worker && cat worker.pid 2>/dev/null || echo '0'")
    
    if [[ "$pid" != "0" ]] && $ssh_cmd "kill -0 $pid 2>/dev/null"; then
        log_success "Worker started on $host (PID: $pid)"
    else
        log_error "Failed to start worker on $host"
    fi
}

# Stop worker on remote host
stop_worker() {
    local host="$1"
    log_info "Stopping worker on $host..."
    
    local ssh_cmd=$(build_ssh_cmd "$host")
    local pid=$($ssh_cmd "cd ~/stock-worker && cat worker.pid 2>/dev/null || echo '0'")
    
    if [[ "$pid" != "0" ]]; then
        $ssh_cmd "kill $pid 2>/dev/null || true"
        sleep 3
        
        # Force kill if still running
        if $ssh_cmd "kill -0 $pid 2>/dev/null"; then
            $ssh_cmd "kill -9 $pid 2>/dev/null || true"
            log_warning "Force killed worker on $host"
        else
            log_success "Worker stopped on $host"
        fi
        
        $ssh_cmd "cd ~/stock-worker && rm -f worker.pid"
    else
        log_info "No worker running on $host"
    fi
}

# Check worker status on remote host
check_status() {
    local host="$1"
    log_info "Checking worker status on $host..."
    
    local ssh_cmd=$(build_ssh_cmd "$host")
    local pid=$($ssh_cmd "cd ~/stock-worker && cat worker.pid 2>/dev/null || echo '0'")
    
    if [[ "$pid" != "0" ]] && $ssh_cmd "kill -0 $pid 2>/dev/null"; then
        local uptime=$($ssh_cmd "ps -o etime= -p $pid 2>/dev/null | tr -d ' '" || echo "unknown")
        log_success "Worker running on $host (PID: $pid, Uptime: $uptime)"
        
        # Show recent log entries
        $ssh_cmd "cd ~/stock-worker && tail -n 5 logs/worker_*.log 2>/dev/null || echo 'No log files found'"
    else
        log_warning "Worker not running on $host"
    fi
}

# View worker logs on remote host
view_logs() {
    local host="$1"
    log_info "Viewing worker logs on $host..."
    
    local ssh_cmd=$(build_ssh_cmd "$host")
    $ssh_cmd "cd ~/stock-worker && tail -f logs/worker_*.log"
}

# Process all hosts
process_all_hosts() {
    local action="$1"
    local hosts=($(get_hosts))
    
    if [[ ${#hosts[@]} -eq 0 ]]; then
        log_error "No hosts found in configuration"
        exit 1
    fi
    
    log_info "Processing ${#hosts[@]} hosts: ${hosts[*]}"
    
    for host in "${hosts[@]}"; do
        case "$action" in
            deploy) deploy_worker "$host" ;;
            start) start_worker "$host" ;;
            stop) stop_worker "$host" ;;
            status) check_status "$host" ;;
        esac
        echo
    done
}

# Main execution
main() {
    parse_args "$@"
    
    case "$COMMAND" in
        setup-local)
            setup_local
            ;;
        test-config)
            test_config
            ;;
        deploy)
            if [[ -z "$TARGET_HOST" ]]; then
                log_error "Host not specified for deploy command"
                exit 1
            fi
            deploy_worker "$TARGET_HOST"
            ;;
        start)
            if [[ -z "$TARGET_HOST" ]]; then
                log_error "Host not specified for start command"
                exit 1
            fi
            start_worker "$TARGET_HOST"
            ;;
        stop)
            if [[ -z "$TARGET_HOST" ]]; then
                log_error "Host not specified for stop command"
                exit 1
            fi
            stop_worker "$TARGET_HOST"
            ;;
        restart)
            if [[ -z "$TARGET_HOST" ]]; then
                log_error "Host not specified for restart command"
                exit 1
            fi
            stop_worker "$TARGET_HOST"
            sleep 2
            start_worker "$TARGET_HOST"
            ;;
        status)
            if [[ -z "$TARGET_HOST" ]]; then
                log_error "Host not specified for status command"
                exit 1
            fi
            check_status "$TARGET_HOST"
            ;;
        logs)
            if [[ -z "$TARGET_HOST" ]]; then
                log_error "Host not specified for logs command"
                exit 1
            fi
            view_logs "$TARGET_HOST"
            ;;
        deploy-all)
            process_all_hosts "deploy"
            ;;
        start-all)
            process_all_hosts "start"
            ;;
        stop-all)
            process_all_hosts "stop"
            ;;
        status-all)
            process_all_hosts "status"
            ;;
        *)
            log_error "Unknown command: $COMMAND"
            show_usage
            exit 1
            ;;
    esac
}

# Run main function with all arguments
main "$@"
