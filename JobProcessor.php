<?php

/**
 * Distributed Job Processing System
 * 
 * A flexible background job processor that can run on multiple machines
 * Supports database, Redis, and RabbitMQ backends
 */

require_once __DIR__ . '/DatabaseConfig.php';

class JobProcessor
{
    private $config;
    private $workerId;
    private $workerName;
    private $running = false;
    private $jobs = [];
    private $maxConcurrentJobs;
    private $backend;
    private $logger;
    private $lastHeartbeat;
    
    public function __construct($configFile = null)
    {
        $this->loadConfig($configFile);
        $this->workerId = $this->generateWorkerId();
        $this->workerName = $this->config['worker']['name'] ?? 'Worker-' . $this->workerId;
        $this->maxConcurrentJobs = $this->config['worker']['max_concurrent_jobs'] ?? 3;
        
        $this->initializeBackend();
        $this->initializeLogger();
        $this->registerWorker();
        
        // Handle graceful shutdown
        pcntl_signal(SIGTERM, [$this, 'handleShutdown']);
        pcntl_signal(SIGINT, [$this, 'handleShutdown']);
    }
    
    /**
     * Load configuration from YAML file
     */
    private function loadConfig($configFile = null)
    {
        $possibleFiles = [
            $configFile,
            'job_processor.yml',
            'job_processor.yaml',
            'job_processor.example.yml'
        ];
        
        foreach ($possibleFiles as $file) {
            if ($file && file_exists($file)) {
                if (function_exists('yaml_parse_file')) {
                    $this->config = yaml_parse_file($file)['job_processor'] ?? [];
                } else {
                    $this->config = $this->parseSimpleYaml($file)['job_processor'] ?? [];
                }
                return;
            }
        }
        
        throw new Exception('Job processor configuration file not found');
    }
    
    /**
     * Simple YAML parser fallback
     */
    private function parseSimpleYaml($file)
    {
        // Reuse the same parser from DatabaseConfig
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        $config = [];
        $currentSection = &$config;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') continue;
            
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (empty($value)) {
                    $currentSection[$key] = [];
                    $currentSection = &$currentSection[$key];
                } else {
                    $currentSection[$key] = $value;
                }
            }
        }
        
        return $config;
    }
    
    /**
     * Generate unique worker ID
     */
    private function generateWorkerId()
    {
        return $this->config['worker']['worker_id'] ?? uniqid(gethostname() . '_', true);
    }
    
    /**
     * Initialize job backend
     */
    private function initializeBackend()
    {
        $backendType = $this->config['queue']['backend'] ?? 'database';
        
        switch ($backendType) {
            case 'database':
                $this->backend = new DatabaseJobBackend();
                break;
            case 'redis':
                $this->backend = new RedisJobBackend($this->config['queue']['redis'] ?? []);
                break;
            case 'rabbitmq':
                $this->backend = new RabbitMQJobBackend($this->config['queue']['rabbitmq'] ?? []);
                break;
            case 'mqtt':
            case 'mosquitto':
                if (!class_exists('Mosquitto\Client')) {
                    throw new Exception('Mosquitto PHP extension required for MQTT backend. Install with: sudo apt-get install php-mosquitto');
                }
                $this->backend = new MQTTJobBackend($this->config, $this->logger);
                break;
            default:
                throw new Exception("Unsupported backend: {$backendType}");
        }
    }
    
    /**
     * Initialize logging
     */
    private function initializeLogger()
    {
        $logFile = $this->config['logging']['file'] ?? 'logs/job_processor.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $this->logger = new JobLogger($logFile, $this->config['logging'] ?? []);
    }
    
    /**
     * Register this worker in the system
     */
    private function registerWorker()
    {
        $workerData = [
            'worker_id' => $this->workerId,
            'name' => $this->workerName,
            'hostname' => gethostname(),
            'pid' => getmypid(),
            'max_concurrent_jobs' => $this->maxConcurrentJobs,
            'supported_job_types' => json_encode($this->config['worker']['supported_job_types'] ?? []),
            'capabilities' => json_encode($this->config['worker']['capabilities'] ?? []),
            'status' => 'starting',
            'started_at' => date('Y-m-d H:i:s'),
            'last_heartbeat' => date('Y-m-d H:i:s')
        ];
        
        $this->backend->registerWorker($workerData);
        $this->logger->info("Worker registered: {$this->workerId} ({$this->workerName})");
    }
    
    /**
     * Start processing jobs
     */
    public function start()
    {
        $this->running = true;
        $this->backend->updateWorkerStatus($this->workerId, 'running');
        $this->logger->info("Job processor started");
        
        $pollInterval = $this->config['worker']['poll_interval'] ?? 5;
        $heartbeatInterval = $this->config['worker']['heartbeat_interval'] ?? 30;
        
        while ($this->running) {
            try {
                // Send heartbeat
                if (time() - $this->lastHeartbeat >= $heartbeatInterval) {
                    $this->sendHeartbeat();
                }
                
                // Process existing jobs
                $this->processRunningJobs();
                
                // Check for new jobs if we have capacity
                if (count($this->jobs) < $this->maxConcurrentJobs) {
                    $this->pollForNewJobs();
                }
                
                // Handle signals
                pcntl_signal_dispatch();
                
                sleep($pollInterval);
                
            } catch (Exception $e) {
                $this->logger->error("Error in main loop: " . $e->getMessage());
                sleep($pollInterval);
            }
        }
        
        $this->shutdown();
    }
    
    /**
     * Send heartbeat to indicate worker is alive
     */
    private function sendHeartbeat()
    {
        $this->backend->updateWorkerHeartbeat($this->workerId);
        $this->lastHeartbeat = time();
    }
    
    /**
     * Poll for new jobs from the queue
     */
    private function pollForNewJobs()
    {
        $availableSlots = $this->maxConcurrentJobs - count($this->jobs);
        $supportedTypes = $this->config['worker']['supported_job_types'] ?? [];
        $capabilities = $this->config['worker']['capabilities'] ?? [];
        
        $newJobs = $this->backend->getAvailableJobs($availableSlots, $supportedTypes, $capabilities);
        
        foreach ($newJobs as $job) {
            $this->startJob($job);
        }
    }
    
    /**
     * Start executing a job
     */
    private function startJob($jobData)
    {
        $jobId = $jobData['id'];
        $jobType = $jobData['job_type'];
        
        try {
            // Mark job as running
            $this->backend->updateJobStatus($jobId, 'running', $this->workerId);
            
            // Create job processor based on type
            $jobProcessor = $this->createJobProcessor($jobType);
            
            if (!$jobProcessor) {
                throw new Exception("No processor found for job type: {$jobType}");
            }
            
            // Start job in background
            $pid = pcntl_fork();
            
            if ($pid == -1) {
                throw new Exception("Failed to fork process for job {$jobId}");
            } elseif ($pid == 0) {
                // Child process - execute the job
                $this->executeJob($jobProcessor, $jobData);
                exit(0);
            } else {
                // Parent process - track the job
                $this->jobs[$jobId] = [
                    'pid' => $pid,
                    'job_data' => $jobData,
                    'started_at' => time(),
                    'processor' => $jobProcessor
                ];
                
                $this->logger->info("Started job {$jobId} (type: {$jobType}) with PID {$pid}");
            }
            
        } catch (Exception $e) {
            $this->backend->updateJobStatus($jobId, 'failed', $this->workerId, $e->getMessage());
            $this->logger->error("Failed to start job {$jobId}: " . $e->getMessage());
        }
    }
    
    /**
     * Create job processor based on job type
     */
    private function createJobProcessor($jobType)
    {
        switch ($jobType) {
            case 'technical_analysis':
                return new TechnicalAnalysisJobProcessor();
            case 'price_update':
                return new PriceUpdateJobProcessor();
            case 'data_import':
                return new DataImportJobProcessor();
            case 'portfolio_analysis':
                return new PortfolioAnalysisJobProcessor();
            default:
                return null;
        }
    }
    
    /**
     * Execute a job in child process
     */
    private function executeJob($processor, $jobData)
    {
        $jobId = $jobData['id'];
        
        try {
            // Set up error handling
            set_error_handler(function($severity, $message, $file, $line) use ($jobId) {
                throw new ErrorException($message, 0, $severity, $file, $line);
            });
            
            // Execute the job
            $result = $processor->execute($jobData);
            
            // Update job status
            $this->backend->updateJobStatus($jobId, 'completed', $this->workerId, null, $result);
            
        } catch (Exception $e) {
            $this->backend->updateJobStatus($jobId, 'failed', $this->workerId, $e->getMessage());
            $this->logger->error("Job {$jobId} failed: " . $e->getMessage());
        }
    }
    
    /**
     * Check status of running jobs
     */
    private function processRunningJobs()
    {
        foreach ($this->jobs as $jobId => $jobInfo) {
            $pid = $jobInfo['pid'];
            $status = pcntl_waitpid($pid, $exitStatus, WNOHANG);
            
            if ($status == $pid) {
                // Job finished
                unset($this->jobs[$jobId]);
                
                if (pcntl_wexitstatus($exitStatus) == 0) {
                    $this->logger->info("Job {$jobId} completed successfully");
                } else {
                    $this->logger->error("Job {$jobId} exited with error code: " . pcntl_wexitstatus($exitStatus));
                }
            } elseif ($status == 0) {
                // Job still running - check for timeout
                $maxExecutionTime = $this->config['worker']['max_execution_time'] ?? 3600;
                
                if (time() - $jobInfo['started_at'] > $maxExecutionTime) {
                    // Kill the job
                    posix_kill($pid, SIGTERM);
                    unset($this->jobs[$jobId]);
                    
                    $this->backend->updateJobStatus($jobId, 'timeout', $this->workerId, 'Job exceeded maximum execution time');
                    $this->logger->warning("Job {$jobId} timed out and was terminated");
                }
            }
        }
    }
    
    /**
     * Handle shutdown signal
     */
    public function handleShutdown($signal)
    {
        $this->logger->info("Received shutdown signal: {$signal}");
        $this->running = false;
    }
    
    /**
     * Shutdown the worker gracefully
     */
    private function shutdown()
    {
        $this->logger->info("Shutting down worker...");
        
        // Update worker status
        $this->backend->updateWorkerStatus($this->workerId, 'stopping');
        
        // Wait for running jobs to complete or timeout
        $shutdownTimeout = 30; // seconds
        $startTime = time();
        
        while (!empty($this->jobs) && (time() - $startTime) < $shutdownTimeout) {
            $this->processRunningJobs();
            sleep(1);
        }
        
        // Force kill any remaining jobs
        foreach ($this->jobs as $jobId => $jobInfo) {
            posix_kill($jobInfo['pid'], SIGKILL);
            $this->backend->updateJobStatus($jobId, 'cancelled', $this->workerId, 'Worker shutdown');
            $this->logger->warning("Force killed job {$jobId} during shutdown");
        }
        
        // Unregister worker
        $this->backend->unregisterWorker($this->workerId);
        $this->logger->info("Worker shutdown complete");
    }
}

/**
 * Simple logger for job processor
 */
class JobLogger
{
    private $logFile;
    private $level;
    
    public function __construct($logFile, $config = [])
    {
        $this->logFile = $logFile;
        $this->level = $config['level'] ?? 'INFO';
    }
    
    public function info($message)
    {
        $this->log('INFO', $message);
    }
    
    public function warning($message)
    {
        $this->log('WARNING', $message);
    }
    
    public function error($message)
    {
        $this->log('ERROR', $message);
    }
    
    private function log($level, $message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $pid = getmypid();
        $logLine = "[{$timestamp}] [{$level}] [PID:{$pid}] {$message}\n";
        
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
