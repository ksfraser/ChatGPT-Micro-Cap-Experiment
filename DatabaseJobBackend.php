<?php

/**
 * Database Job Backend
 * 
 * Implements job queue using MySQL database
 */
class DatabaseJobBackend
{
    private $pdo;
    
    public function __construct()
    {
        $this->pdo = DatabaseConfig::createLegacyConnection();
        $this->initializeTables();
    }
    
    /**
     * Initialize database tables for job processing
     */
    private function initializeTables()
    {
        // Job workers table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS job_workers (
                worker_id VARCHAR(255) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                hostname VARCHAR(255) NOT NULL,
                pid INT NOT NULL,
                max_concurrent_jobs INT DEFAULT 3,
                supported_job_types JSON,
                capabilities JSON,
                status ENUM('starting', 'running', 'stopping', 'stopped') DEFAULT 'starting',
                started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Job queue table (extend existing ta_analysis_jobs)
        $this->pdo->exec("
            ALTER TABLE ta_analysis_jobs 
            ADD COLUMN IF NOT EXISTS job_type VARCHAR(50) DEFAULT 'technical_analysis',
            ADD COLUMN IF NOT EXISTS priority INT DEFAULT 5,
            ADD COLUMN IF NOT EXISTS worker_id VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS retry_count INT DEFAULT 0,
            ADD COLUMN IF NOT EXISTS max_retries INT DEFAULT 3,
            ADD COLUMN IF NOT EXISTS scheduled_at TIMESTAMP NULL,
            ADD COLUMN IF NOT EXISTS started_at TIMESTAMP NULL,
            ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP NULL,
            ADD COLUMN IF NOT EXISTS result_data JSON NULL,
            ADD COLUMN IF NOT EXISTS error_details TEXT NULL,
            ADD INDEX idx_job_status_priority (status, priority),
            ADD INDEX idx_worker_id (worker_id),
            ADD INDEX idx_job_type (job_type)
        ");
    }
    
    /**
     * Register a worker
     */
    public function registerWorker($workerData)
    {
        $sql = "INSERT INTO job_workers 
                (worker_id, name, hostname, pid, max_concurrent_jobs, supported_job_types, capabilities, status, started_at, last_heartbeat)
                VALUES (:worker_id, :name, :hostname, :pid, :max_concurrent_jobs, :supported_job_types, :capabilities, :status, :started_at, :last_heartbeat)
                ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                hostname = VALUES(hostname), 
                pid = VALUES(pid),
                max_concurrent_jobs = VALUES(max_concurrent_jobs),
                supported_job_types = VALUES(supported_job_types),
                capabilities = VALUES(capabilities),
                status = VALUES(status),
                started_at = VALUES(started_at),
                last_heartbeat = VALUES(last_heartbeat)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($workerData);
    }
    
    /**
     * Update worker status
     */
    public function updateWorkerStatus($workerId, $status)
    {
        $sql = "UPDATE job_workers SET status = :status, last_heartbeat = CURRENT_TIMESTAMP WHERE worker_id = :worker_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['status' => $status, 'worker_id' => $workerId]);
    }
    
    /**
     * Update worker heartbeat
     */
    public function updateWorkerHeartbeat($workerId)
    {
        $sql = "UPDATE job_workers SET last_heartbeat = CURRENT_TIMESTAMP WHERE worker_id = :worker_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['worker_id' => $workerId]);
    }
    
    /**
     * Unregister worker
     */
    public function unregisterWorker($workerId)
    {
        // Update status instead of deleting to keep history
        $sql = "UPDATE job_workers SET status = 'stopped' WHERE worker_id = :worker_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['worker_id' => $workerId]);
    }
    
    /**
     * Get available jobs for worker
     */
    public function getAvailableJobs($limit, $supportedTypes = [], $capabilities = [])
    {
        $sql = "SELECT * FROM ta_analysis_jobs 
                WHERE status IN ('PENDING', 'pending') 
                AND (scheduled_at IS NULL OR scheduled_at <= CURRENT_TIMESTAMP)
                AND retry_count < max_retries";
        
        $params = [];
        
        // Filter by supported job types
        if (!empty($supportedTypes)) {
            $placeholders = str_repeat('?,', count($supportedTypes) - 1) . '?';
            $sql .= " AND job_type IN ({$placeholders})";
            $params = array_merge($params, $supportedTypes);
        }
        
        $sql .= " ORDER BY priority DESC, created_at ASC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark jobs as claimed by this request
        foreach ($jobs as $job) {
            $this->updateJobStatus($job['id'], 'claimed', null);
        }
        
        return $jobs;
    }
    
    /**
     * Update job status
     */
    public function updateJobStatus($jobId, $status, $workerId = null, $errorMessage = null, $resultData = null)
    {
        $updates = ['status' => $status];
        $params = ['id' => $jobId, 'status' => $status];
        
        if ($workerId !== null) {
            $updates[] = 'worker_id = :worker_id';
            $params['worker_id'] = $workerId;
        }
        
        if ($status === 'running' || $status === 'RUNNING') {
            $updates[] = 'started_at = CURRENT_TIMESTAMP';
        } elseif ($status === 'completed' || $status === 'COMPLETED') {
            $updates[] = 'completed_at = CURRENT_TIMESTAMP';
            $updates[] = 'progress = 100';
        } elseif ($status === 'failed' || $status === 'FAILED') {
            $updates[] = 'retry_count = retry_count + 1';
        }
        
        if ($errorMessage !== null) {
            $updates[] = 'error_details = :error_details';
            $params['error_details'] = $errorMessage;
        }
        
        if ($resultData !== null) {
            $updates[] = 'result_data = :result_data';
            $params['result_data'] = json_encode($resultData);
        }
        
        $updateClause = is_array($updates) ? implode(', ', $updates) : $updates;
        $sql = "UPDATE ta_analysis_jobs SET {$updateClause} WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Create a new job
     */
    public function createJob($jobData)
    {
        $sql = "INSERT INTO ta_analysis_jobs 
                (job_type, idstockinfo, symbol, analysis_type, status, priority, parameters, max_retries, created_at, scheduled_at)
                VALUES (:job_type, :idstockinfo, :symbol, :analysis_type, :status, :priority, :parameters, :max_retries, CURRENT_TIMESTAMP, :scheduled_at)";
        
        $params = [
            'job_type' => $jobData['job_type'] ?? 'technical_analysis',
            'idstockinfo' => $jobData['idstockinfo'] ?? null,
            'symbol' => $jobData['symbol'] ?? null,
            'analysis_type' => $jobData['analysis_type'] ?? $jobData['job_type'],
            'status' => $jobData['status'] ?? 'pending',
            'priority' => $jobData['priority'] ?? 5,
            'parameters' => is_array($jobData['parameters']) ? json_encode($jobData['parameters']) : $jobData['parameters'],
            'max_retries' => $jobData['max_retries'] ?? 3,
            'scheduled_at' => $jobData['scheduled_at'] ?? null
        ];
        
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute($params)) {
            return $this->pdo->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Get worker statistics
     */
    public function getWorkerStats()
    {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count,
                    AVG(TIMESTAMPDIFF(SECOND, last_heartbeat, CURRENT_TIMESTAMP)) as avg_seconds_since_heartbeat
                FROM job_workers 
                GROUP BY status";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get job queue statistics
     */
    public function getJobStats()
    {
        $sql = "SELECT 
                    job_type,
                    status,
                    COUNT(*) as count,
                    AVG(priority) as avg_priority
                FROM ta_analysis_jobs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY job_type, status";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Clean up old completed jobs
     */
    public function cleanupOldJobs($daysOld = 30)
    {
        $sql = "DELETE FROM ta_analysis_jobs 
                WHERE status IN ('completed', 'COMPLETED') 
                AND completed_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$daysOld]);
    }
    
    /**
     * Get stale workers (no heartbeat for X minutes)
     */
    public function getStaleWorkers($minutesStale = 5)
    {
        $sql = "SELECT * FROM job_workers 
                WHERE status IN ('running', 'starting')
                AND last_heartbeat < DATE_SUB(NOW(), INTERVAL ? MINUTE)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$minutesStale]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
