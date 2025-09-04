<?php

/**
 * Redis Job Backend
 * Implements job queue using Redis
 */
class RedisJobBackend
{
    private $redis;
    private $logger;
    private $config;
    
    public function __construct($config, $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        
        if (!class_exists('Redis')) {
            throw new Exception('Redis extension is not installed');
        }
        
        $this->redis = new Redis();
        $this->connect();
    }
    
    /**
     * Connect to Redis
     */
    private function connect()
    {
        $host = $this->config['redis']['host'] ?? 'localhost';
        $port = $this->config['redis']['port'] ?? 6379;
        $timeout = $this->config['redis']['timeout'] ?? 5;
        $password = $this->config['redis']['password'] ?? null;
        $database = $this->config['redis']['database'] ?? 0;
        
        if (!$this->redis->connect($host, $port, $timeout)) {
            throw new Exception("Could not connect to Redis server at {$host}:{$port}");
        }
        
        if ($password) {
            $this->redis->auth($password);
        }
        
        $this->redis->select($database);
        
        $this->logger->info("Connected to Redis at {$host}:{$port}");
    }
    
    /**
     * Register a worker
     */
    public function registerWorker($workerId, $workerInfo)
    {
        $key = "workers:{$workerId}";
        $this->redis->hMSet($key, [
            'id' => $workerId,
            'hostname' => $workerInfo['hostname'],
            'pid' => $workerInfo['pid'],
            'status' => 'active',
            'last_heartbeat' => time(),
            'job_types' => json_encode($workerInfo['job_types'] ?? []),
            'max_concurrent_jobs' => $workerInfo['max_concurrent_jobs'] ?? 1,
            'current_jobs' => 0,
            'registered_at' => time()
        ]);
        
        $this->redis->expire($key, 300); // 5 minute TTL
        $this->redis->sAdd('active_workers', $workerId);
        
        $this->logger->info("Registered worker: {$workerId}");
    }
    
    /**
     * Update worker heartbeat
     */
    public function updateWorkerHeartbeat($workerId)
    {
        $key = "workers:{$workerId}";
        if ($this->redis->exists($key)) {
            $this->redis->hSet($key, 'last_heartbeat', time());
            $this->redis->expire($key, 300); // Reset TTL
            return true;
        }
        return false;
    }
    
    /**
     * Unregister a worker
     */
    public function unregisterWorker($workerId)
    {
        $key = "workers:{$workerId}";
        $this->redis->del($key);
        $this->redis->sRem('active_workers', $workerId);
        
        $this->logger->info("Unregistered worker: {$workerId}");
    }
    
    /**
     * Get next job for worker
     */
    public function getNextJob($workerId, $jobTypes = [])
    {
        $workerKey = "workers:{$workerId}";
        $workerInfo = $this->redis->hGetAll($workerKey);
        
        if (empty($workerInfo)) {
            return null; // Worker not registered
        }
        
        $currentJobs = (int)($workerInfo['current_jobs'] ?? 0);
        $maxJobs = (int)($workerInfo['max_concurrent_jobs'] ?? 1);
        
        if ($currentJobs >= $maxJobs) {
            return null; // Worker at capacity
        }
        
        // Try to get a job from priority queues
        $priorities = ['high', 'normal', 'low'];
        
        foreach ($priorities as $priority) {
            foreach ($jobTypes as $jobType) {
                $queueKey = "jobs:{$priority}:{$jobType}";
                $jobData = $this->redis->lPop($queueKey);
                
                if ($jobData) {
                    $job = json_decode($jobData, true);
                    if ($job) {
                        // Mark job as in progress
                        $job['status'] = 'running';
                        $job['worker_id'] = $workerId;
                        $job['started_at'] = time();
                        
                        $this->redis->hSet("job:{$job['id']}", 'data', json_encode($job));
                        $this->redis->hSet($workerKey, 'current_jobs', $currentJobs + 1);
                        
                        return $job;
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Add job to queue
     */
    public function addJob($jobData)
    {
        $jobId = uniqid('job_', true);
        $job = array_merge($jobData, [
            'id' => $jobId,
            'status' => 'pending',
            'created_at' => time(),
            'attempts' => 0
        ]);
        
        $priority = $job['priority'] ?? 'normal';
        $jobType = $job['job_type'] ?? 'default';
        
        // Store job data
        $this->redis->hSet("job:{$jobId}", 'data', json_encode($job));
        
        // Add to appropriate queue
        $queueKey = "jobs:{$priority}:{$jobType}";
        $this->redis->rPush($queueKey, json_encode($job));
        
        $this->logger->info("Added job {$jobId} to queue {$queueKey}");
        
        return $jobId;
    }
    
    /**
     * Complete job
     */
    public function completeJob($jobId, $workerId, $result)
    {
        $jobKey = "job:{$jobId}";
        $jobData = $this->redis->hGet($jobKey, 'data');
        
        if ($jobData) {
            $job = json_decode($jobData, true);
            $job['status'] = 'completed';
            $job['completed_at'] = time();
            $job['result'] = $result;
            
            $this->redis->hSet($jobKey, 'data', json_encode($job));
            $this->redis->expire($jobKey, 86400); // Keep for 24 hours
            
            // Update worker current jobs count
            $workerKey = "workers:{$workerId}";
            $currentJobs = (int)$this->redis->hGet($workerKey, 'current_jobs');
            $this->redis->hSet($workerKey, 'current_jobs', max(0, $currentJobs - 1));
            
            $this->logger->info("Completed job {$jobId}");
        }
    }
    
    /**
     * Fail job
     */
    public function failJob($jobId, $workerId, $error, $retry = true)
    {
        $jobKey = "job:{$jobId}";
        $jobData = $this->redis->hGet($jobKey, 'data');
        
        if ($jobData) {
            $job = json_decode($jobData, true);
            $job['attempts'] = ($job['attempts'] ?? 0) + 1;
            $job['last_error'] = $error;
            $job['failed_at'] = time();
            
            $maxAttempts = $job['max_attempts'] ?? 3;
            
            if ($retry && $job['attempts'] < $maxAttempts) {
                // Retry job
                $job['status'] = 'pending';
                $job['worker_id'] = null;
                
                $priority = $job['priority'] ?? 'normal';
                $jobType = $job['job_type'] ?? 'default';
                $queueKey = "jobs:{$priority}:{$jobType}";
                
                $this->redis->rPush($queueKey, json_encode($job));
                $this->logger->info("Retrying job {$jobId} (attempt {$job['attempts']})");
            } else {
                // Permanently failed
                $job['status'] = 'failed';
                $this->logger->error("Job {$jobId} permanently failed: {$error}");
            }
            
            $this->redis->hSet($jobKey, 'data', json_encode($job));
            
            // Update worker current jobs count
            $workerKey = "workers:{$workerId}";
            $currentJobs = (int)$this->redis->hGet($workerKey, 'current_jobs');
            $this->redis->hSet($workerKey, 'current_jobs', max(0, $currentJobs - 1));
        }
    }
    
    /**
     * Get queue statistics
     */
    public function getQueueStats()
    {
        $stats = [
            'active_workers' => $this->redis->sCard('active_workers'),
            'queues' => []
        ];
        
        $priorities = ['high', 'normal', 'low'];
        $jobTypes = ['technical_analysis', 'price_update', 'data_import', 'portfolio_analysis'];
        
        foreach ($priorities as $priority) {
            foreach ($jobTypes as $jobType) {
                $queueKey = "jobs:{$priority}:{$jobType}";
                $queueSize = $this->redis->lLen($queueKey);
                if ($queueSize > 0) {
                    $stats['queues'][$queueKey] = $queueSize;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Cleanup expired workers
     */
    public function cleanupExpiredWorkers()
    {
        $activeWorkers = $this->redis->sMembers('active_workers');
        $cleanedUp = 0;
        
        foreach ($activeWorkers as $workerId) {
            $workerKey = "workers:{$workerId}";
            if (!$this->redis->exists($workerKey)) {
                $this->redis->sRem('active_workers', $workerId);
                $cleanedUp++;
            }
        }
        
        if ($cleanedUp > 0) {
            $this->logger->info("Cleaned up {$cleanedUp} expired workers");
        }
        
        return $cleanedUp;
    }
}
