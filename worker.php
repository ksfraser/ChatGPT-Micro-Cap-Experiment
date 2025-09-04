#!/usr/bin/env php
<?php

/**
 * Standalone Worker Script
 * Can be deployed on multiple machines to process jobs
 */

// Basic autoloader
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/' . str_replace('\\', '/', $class) . '.php',
        __DIR__ . '/Stock-Analysis-Extension/Legacy/vendor/autoload.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Include required files
require_once __DIR__ . '/DatabaseConfig.php';
require_once __DIR__ . '/JobLogger.php';
require_once __DIR__ . '/JobProcessors.php';
require_once __DIR__ . '/DatabaseJobBackend.php';
require_once __DIR__ . '/RedisJobBackend.php';
require_once __DIR__ . '/RabbitMQJobBackend.php';
require_once __DIR__ . '/MQTTJobBackend.php';

/**
 * Worker class for processing jobs
 */
class StandaloneWorker
{
    private $workerId;
    private $config;
    private $backend;
    private $logger;
    private $running = false;
    private $processors = [];
    private $currentJobs = [];
    private $maxConcurrentJobs;
    private $pollInterval;
    
    public function __construct($configFile = null)
    {
        // Generate unique worker ID
        $this->workerId = gethostname() . '_' . getmypid() . '_' . uniqid();
        
        // Load configuration
        $this->loadConfig($configFile);
        
        // Setup logger
        $logFile = $this->config['worker']['log_file'] ?? 'logs/worker_' . $this->workerId . '.log';
        $this->logger = new JobLogger($logFile);
        
        // Initialize backend
        $this->initializeBackend();
        
        // Setup job processors
        $this->setupProcessors();
        
        // Set worker parameters
        $this->maxConcurrentJobs = $this->config['worker']['max_concurrent_jobs'] ?? 3;
        $this->pollInterval = $this->config['worker']['poll_interval'] ?? 5;
        
        // Setup signal handlers for graceful shutdown
        $this->setupSignalHandlers();
        
        $this->logger->info("Worker {$this->workerId} initialized");
    }
    
    /**
     * Load configuration
     */
    private function loadConfig($configFile)
    {
        if (!$configFile) {
            $configFile = __DIR__ . '/job_processor.yml';
        }
        
        if (!file_exists($configFile)) {
            throw new Exception("Configuration file not found: {$configFile}");
        }
        
        $this->config = DatabaseConfig::parseYaml(file_get_contents($configFile));
        
        if (!$this->config) {
            throw new Exception("Failed to parse configuration file: {$configFile}");
        }
    }
    
    /**
     * Initialize job backend
     */
    private function initializeBackend()
    {
        $backendType = $this->config['queue']['backend'] ?? 'database';
        
        switch ($backendType) {
            case 'database':
                $this->backend = new DatabaseJobBackend($this->config, $this->logger);
                break;
                
            case 'redis':
                if (!class_exists('Redis')) {
                    throw new Exception('Redis extension required for Redis backend');
                }
                $this->backend = new RedisJobBackend($this->config, $this->logger);
                break;
                
            case 'rabbitmq':
                if (!class_exists('PhpAmqpLib\Connection\AMQPStreamConnection')) {
                    throw new Exception('php-amqplib library required for RabbitMQ backend');
                }
                $this->backend = new RabbitMQJobBackend($this->config, $this->logger);
                break;
                
            case 'mqtt':
            case 'mosquitto':
                if (!class_exists('Mosquitto\Client')) {
                    throw new Exception('Mosquitto PHP extension required for MQTT backend. Install with: sudo apt-get install php-mosquitto');
                }
                $this->backend = new MQTTJobBackend($this->config, $this->logger);
                break;
                
            default:
                throw new Exception("Unknown backend type: {$backendType}");
        }
    }
    
    /**
     * Setup job processors
     */
    private function setupProcessors()
    {
        $this->processors = [
            'technical_analysis' => new TechnicalAnalysisJobProcessor(),
            'price_update' => new PriceUpdateJobProcessor(),
            'data_import' => new DataImportJobProcessor(),
            'portfolio_analysis' => new PortfolioAnalysisJobProcessor()
        ];
    }
    
    /**
     * Setup signal handlers for graceful shutdown
     */
    private function setupSignalHandlers()
    {
        if (!function_exists('pcntl_signal')) {
            $this->logger->warning('PCNTL extension not available - graceful shutdown not supported');
            return;
        }
        
        pcntl_signal(SIGTERM, [$this, 'shutdown']);
        pcntl_signal(SIGINT, [$this, 'shutdown']);
        pcntl_signal(SIGHUP, [$this, 'restart']);
    }
    
    /**
     * Start the worker
     */
    public function start()
    {
        $this->running = true;
        
        // Register worker with backend
        $workerInfo = [
            'hostname' => gethostname(),
            'pid' => getmypid(),
            'job_types' => array_keys($this->processors),
            'max_concurrent_jobs' => $this->maxConcurrentJobs,
            'version' => '1.0.0',
            'started_at' => time()
        ];
        
        $this->backend->registerWorker($this->workerId, $workerInfo);
        
        $this->logger->info("Worker {$this->workerId} started");
        
        // Main processing loop
        $lastHeartbeat = 0;
        $heartbeatInterval = 30; // 30 seconds
        
        while ($this->running) {
            try {
                // Process signals
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
                
                // Send heartbeat
                if (time() - $lastHeartbeat >= $heartbeatInterval) {
                    $this->backend->updateWorkerHeartbeat($this->workerId);
                    $lastHeartbeat = time();
                }
                
                // Check for new jobs if we have capacity
                if (count($this->currentJobs) < $this->maxConcurrentJobs) {
                    $this->checkForNewJobs();
                }
                
                // Check completed jobs
                $this->checkCompletedJobs();
                
                // Sleep before next iteration
                sleep($this->pollInterval);
                
            } catch (Exception $e) {
                $this->logger->error("Error in main loop: " . $e->getMessage());
                sleep($this->pollInterval);
            }
        }
        
        // Wait for current jobs to complete
        $this->waitForJobsToComplete();
        
        // Unregister worker
        $this->backend->unregisterWorker($this->workerId);
        
        $this->logger->info("Worker {$this->workerId} stopped");
    }
    
    /**
     * Check for new jobs
     */
    private function checkForNewJobs()
    {
        $jobTypes = array_keys($this->processors);
        $job = $this->backend->getNextJob($this->workerId, $jobTypes);
        
        if ($job) {
            $this->processJob($job);
        }
    }
    
    /**
     * Process a job
     */
    private function processJob($job)
    {
        $jobId = $job['id'];
        $jobType = $job['job_type'];
        
        if (!isset($this->processors[$jobType])) {
            $this->logger->error("No processor found for job type: {$jobType}");
            $this->backend->failJob($jobId, $this->workerId, "No processor for job type: {$jobType}", false);
            return;
        }
        
        $this->logger->info("Starting job {$jobId} ({$jobType})");
        
        // Fork process if PCNTL is available
        if (function_exists('pcntl_fork')) {
            $pid = pcntl_fork();
            
            if ($pid == -1) {
                // Fork failed
                $this->logger->error("Failed to fork process for job {$jobId}");
                $this->backend->failJob($jobId, $this->workerId, "Failed to fork process", true);
                return;
            } elseif ($pid == 0) {
                // Child process
                $this->executeJobInChild($job);
                exit(0);
            } else {
                // Parent process
                $this->currentJobs[$jobId] = [
                    'pid' => $pid,
                    'job' => $job,
                    'started_at' => time()
                ];
            }
        } else {
            // No forking available - execute in main process
            $this->executeJob($job);
        }
    }
    
    /**
     * Execute job in child process
     */
    private function executeJobInChild($job)
    {
        try {
            $result = $this->executeJob($job);
            exit($result ? 0 : 1);
        } catch (Exception $e) {
            $this->logger->error("Job {$job['id']} failed in child process: " . $e->getMessage());
            exit(1);
        }
    }
    
    /**
     * Execute a job
     */
    private function executeJob($job)
    {
        $jobId = $job['id'];
        $jobType = $job['job_type'];
        
        try {
            $processor = $this->processors[$jobType];
            $result = $processor->execute($job);
            
            $this->backend->completeJob($jobId, $this->workerId, $result);
            $this->logger->info("Completed job {$jobId}");
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error("Job {$jobId} failed: " . $e->getMessage());
            $this->backend->failJob($jobId, $this->workerId, $e->getMessage(), true);
            
            return false;
        }
    }
    
    /**
     * Check for completed jobs
     */
    private function checkCompletedJobs()
    {
        if (!function_exists('pcntl_waitpid')) {
            return;
        }
        
        foreach ($this->currentJobs as $jobId => $jobInfo) {
            $status = 0;
            $result = pcntl_waitpid($jobInfo['pid'], $status, WNOHANG);
            
            if ($result > 0) {
                // Child process completed
                unset($this->currentJobs[$jobId]);
                
                if (pcntl_wexitstatus($status) == 0) {
                    $this->logger->info("Job {$jobId} completed successfully");
                } else {
                    $this->logger->error("Job {$jobId} failed with exit code: " . pcntl_wexitstatus($status));
                }
            } elseif ($result == -1) {
                // Error occurred
                unset($this->currentJobs[$jobId]);
                $this->logger->error("Error waiting for job {$jobId}");
            }
            
            // Check for job timeout
            $timeout = $this->config['worker']['job_timeout'] ?? 3600; // 1 hour default
            if (time() - $jobInfo['started_at'] > $timeout) {
                $this->logger->warning("Job {$jobId} timed out, killing process");
                posix_kill($jobInfo['pid'], SIGTERM);
                unset($this->currentJobs[$jobId]);
            }
        }
    }
    
    /**
     * Wait for current jobs to complete
     */
    private function waitForJobsToComplete($timeout = 60)
    {
        $startTime = time();
        
        while (!empty($this->currentJobs) && (time() - $startTime) < $timeout) {
            $this->checkCompletedJobs();
            sleep(1);
        }
        
        // Force kill remaining jobs
        foreach ($this->currentJobs as $jobId => $jobInfo) {
            $this->logger->warning("Force killing job {$jobId}");
            posix_kill($jobInfo['pid'], SIGKILL);
        }
        
        $this->currentJobs = [];
    }
    
    /**
     * Shutdown signal handler
     */
    public function shutdown($signal = null)
    {
        $this->logger->info("Received shutdown signal, stopping worker gracefully");
        $this->running = false;
    }
    
    /**
     * Restart signal handler
     */
    public function restart($signal = null)
    {
        $this->logger->info("Received restart signal, restarting worker");
        $this->shutdown();
        // In a real implementation, you might exec() a new instance
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    $configFile = $argv[1] ?? null;
    
    try {
        $worker = new StandaloneWorker($configFile);
        $worker->start();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "This script must be run from the command line.\n";
    exit(1);
}
