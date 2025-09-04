<?php

/**
 * Monitoring API for Job Processing System
 * Provides REST endpoints for the monitoring dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'DatabaseConfig.php';
require_once 'JobLogger.php';

class MonitorAPI
{
    private $pdo;
    private $logger;
    
    public function __construct()
    {
        try {
            $this->pdo = DatabaseConfig::createLegacyConnection();
            $this->logger = new JobLogger('logs/monitor_api.log');
        } catch (Exception $e) {
            $this->sendError('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle API requests
     */
    public function handleRequest()
    {
        $action = $_GET['action'] ?? '';
        
        try {
            switch ($action) {
                case 'overview':
                    $this->getOverview();
                    break;
                    
                case 'workers':
                    $this->getWorkers();
                    break;
                    
                case 'queues':
                    $this->getQueues();
                    break;
                    
                case 'jobs':
                    $this->getJobs();
                    break;
                    
                case 'job_stats':
                    $this->getJobStats();
                    break;
                    
                case 'logs':
                    $this->getLogs();
                    break;
                    
                case 'clear_logs':
                    $this->clearLogs();
                    break;
                    
                case 'add_job':
                    $this->addJob();
                    break;
                    
                case 'job_details':
                    $this->getJobDetails();
                    break;
                    
                default:
                    $this->sendError('Unknown action: ' . $action);
            }
        } catch (Exception $e) {
            $this->logger->error("API error for action '{$action}': " . $e->getMessage());
            $this->sendError('Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get system overview statistics
     */
    private function getOverview()
    {
        $overview = [
            'active_workers' => 0,
            'pending_jobs' => 0,
            'running_jobs' => 0,
            'completed_today' => 0,
            'failed_today' => 0
        ];
        
        // Count active workers (last heartbeat within 5 minutes)
        $sql = "SELECT COUNT(*) FROM ta_analysis_jobs 
                WHERE worker_id IS NOT NULL 
                AND status = 'running'
                AND updated_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        $stmt = $this->pdo->query($sql);
        $overview['active_workers'] = $stmt->fetchColumn();
        
        // Count job statuses
        $sql = "SELECT status, COUNT(*) as count FROM ta_analysis_jobs 
                WHERE created_at > CURDATE() 
                GROUP BY status";
        $stmt = $this->pdo->query($sql);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            switch ($row['status']) {
                case 'pending':
                    $overview['pending_jobs'] = $row['count'];
                    break;
                case 'running':
                    $overview['running_jobs'] = $row['count'];
                    break;
                case 'completed':
                    $overview['completed_today'] = $row['count'];
                    break;
                case 'failed':
                    $overview['failed_today'] = $row['count'];
                    break;
            }
        }
        
        $this->sendSuccess($overview);
    }
    
    /**
     * Get active workers
     */
    private function getWorkers()
    {
        $workers = [];
        
        // Get unique workers from recent jobs (last 10 minutes)
        $sql = "SELECT DISTINCT worker_id, hostname, MAX(updated_at) as last_seen
                FROM ta_analysis_jobs 
                WHERE worker_id IS NOT NULL 
                AND updated_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                GROUP BY worker_id, hostname
                ORDER BY last_seen DESC";
        
        $stmt = $this->pdo->query($sql);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $workerId = $row['worker_id'];
            
            // Get worker job counts
            $jobCountSql = "SELECT COUNT(*) FROM ta_analysis_jobs 
                           WHERE worker_id = ? AND status = 'running'";
            $jobStmt = $this->pdo->prepare($jobCountSql);
            $jobStmt->execute([$workerId]);
            $currentJobs = $jobStmt->fetchColumn();
            
            $workers[] = [
                'id' => $workerId,
                'hostname' => $row['hostname'] ?: explode('_', $workerId)[0],
                'pid' => explode('_', $workerId)[1] ?? 'unknown',
                'status' => $currentJobs > 0 ? 'active' : 'idle',
                'current_jobs' => $currentJobs,
                'max_concurrent_jobs' => 3, // Default, could be stored in config
                'last_heartbeat' => strtotime($row['last_seen']),
                'job_types' => ['technical_analysis', 'price_update', 'data_import'] // Default types
            ];
        }
        
        $this->sendSuccess($workers);
    }
    
    /**
     * Get queue status
     */
    private function getQueues()
    {
        $queues = [];
        
        // Count pending jobs by type and priority
        $sql = "SELECT 
                    CONCAT(COALESCE(priority, 'normal'), '_', job_type) as queue_name,
                    COUNT(*) as count
                FROM ta_analysis_jobs 
                WHERE status = 'pending'
                GROUP BY priority, job_type
                HAVING count > 0
                ORDER BY priority DESC, job_type";
        
        $stmt = $this->pdo->query($sql);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $queues[$row['queue_name']] = $row['count'];
        }
        
        $this->sendSuccess($queues);
    }
    
    /**
     * Get recent jobs
     */
    private function getJobs()
    {
        $limit = min((int)($_GET['limit'] ?? 50), 100); // Max 100 jobs
        $offset = max((int)($_GET['offset'] ?? 0), 0);
        
        $sql = "SELECT id, job_type, status, worker_id, hostname, priority,
                       progress, status_message, created_at, started_at, 
                       completed_at, failed_at, attempts
                FROM ta_analysis_jobs 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
        
        $jobs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $jobs[] = [
                'id' => $row['id'],
                'job_type' => $row['job_type'],
                'status' => $row['status'],
                'worker_id' => $row['worker_id'],
                'hostname' => $row['hostname'],
                'priority' => $row['priority'] ?: 'normal',
                'progress' => (int)$row['progress'],
                'status_message' => $row['status_message'],
                'created_at' => strtotime($row['created_at']),
                'started_at' => $row['started_at'] ? strtotime($row['started_at']) : null,
                'completed_at' => $row['completed_at'] ? strtotime($row['completed_at']) : null,
                'failed_at' => $row['failed_at'] ? strtotime($row['failed_at']) : null,
                'attempts' => (int)$row['attempts']
            ];
        }
        
        $this->sendSuccess($jobs);
    }
    
    /**
     * Get job statistics for charting
     */
    private function getJobStats()
    {
        $hours = min((int)($_GET['hours'] ?? 24), 168); // Max 7 days
        
        // Generate hourly labels
        $labels = [];
        $completed = [];
        $failed = [];
        $running = [];
        
        for ($i = $hours - 1; $i >= 0; $i--) {
            $hour = date('H:i', strtotime("-{$i} hours"));
            $labels[] = $hour;
            
            $startTime = date('Y-m-d H:00:00', strtotime("-{$i} hours"));
            $endTime = date('Y-m-d H:59:59', strtotime("-{$i} hours"));
            
            // Count completed jobs
            $sql = "SELECT COUNT(*) FROM ta_analysis_jobs 
                    WHERE status = 'completed' 
                    AND completed_at BETWEEN ? AND ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$startTime, $endTime]);
            $completed[] = (int)$stmt->fetchColumn();
            
            // Count failed jobs
            $sql = "SELECT COUNT(*) FROM ta_analysis_jobs 
                    WHERE status = 'failed' 
                    AND failed_at BETWEEN ? AND ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$startTime, $endTime]);
            $failed[] = (int)$stmt->fetchColumn();
            
            // Count running jobs (at end of hour)
            $sql = "SELECT COUNT(*) FROM ta_analysis_jobs 
                    WHERE status = 'running' 
                    AND started_at <= ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$endTime]);
            $running[] = (int)$stmt->fetchColumn();
        }
        
        $this->sendSuccess([
            'labels' => $labels,
            'data' => [
                'completed' => $completed,
                'failed' => $failed,
                'running' => $running
            ]
        ]);
    }
    
    /**
     * Get system logs
     */
    private function getLogs()
    {
        $lines = min((int)($_GET['lines'] ?? 100), 500); // Max 500 lines
        $logFiles = [
            'logs/job_processor.log',
            'logs/monitor_api.log'
        ];
        
        $logs = [];
        
        foreach ($logFiles as $logFile) {
            if (file_exists($logFile)) {
                try {
                    $logger = new JobLogger($logFile);
                    $entries = $logger->getRecentEntries($lines);
                    $logs = array_merge($logs, $entries);
                } catch (Exception $e) {
                    $logs[] = "[ERROR] Failed to read log file: {$logFile}";
                }
            }
        }
        
        // Sort by timestamp (basic sorting)
        usort($logs, function($a, $b) {
            return strcmp($a, $b);
        });
        
        // Keep only recent entries
        $logs = array_slice($logs, -$lines);
        
        $this->sendSuccess($logs);
    }
    
    /**
     * Clear system logs
     */
    private function clearLogs()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('POST method required');
        }
        
        $logFiles = [
            'logs/job_processor.log',
            'logs/monitor_api.log'
        ];
        
        $cleared = 0;
        foreach ($logFiles as $logFile) {
            if (file_exists($logFile)) {
                file_put_contents($logFile, '');
                $cleared++;
            }
        }
        
        $this->logger->info("Cleared {$cleared} log files via API");
        $this->sendSuccess(['cleared_files' => $cleared]);
    }
    
    /**
     * Add a test job
     */
    private function addJob()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('POST method required');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $this->sendError('Invalid JSON input');
        }
        
        $jobType = $input['job_type'] ?? 'technical_analysis';
        $priority = $input['priority'] ?? 'normal';
        $parameters = $input['parameters'] ?? [];
        
        // Validate job type
        $validTypes = ['technical_analysis', 'price_update', 'data_import', 'portfolio_analysis'];
        if (!in_array($jobType, $validTypes)) {
            $this->sendError('Invalid job type');
        }
        
        // Validate priority
        $validPriorities = ['high', 'normal', 'low'];
        if (!in_array($priority, $validPriorities)) {
            $priority = 'normal';
        }
        
        // Generate job ID
        $jobId = uniqid('job_', true);
        
        // Insert job into database
        $sql = "INSERT INTO ta_analysis_jobs 
                (id, job_type, priority, parameters, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            $jobId,
            $jobType,
            $priority,
            json_encode($parameters)
        ]);
        
        if ($success) {
            $this->logger->info("Added test job: {$jobId} ({$jobType})");
            $this->sendSuccess(['job_id' => $jobId, 'message' => 'Job added successfully']);
        } else {
            $this->sendError('Failed to add job to database');
        }
    }
    
    /**
     * Get detailed job information
     */
    private function getJobDetails()
    {
        $jobId = $_GET['job_id'] ?? '';
        
        if (empty($jobId)) {
            $this->sendError('Job ID required');
        }
        
        $sql = "SELECT * FROM ta_analysis_jobs WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$jobId]);
        
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$job) {
            $this->sendError('Job not found');
        }
        
        // Convert timestamps
        $job['created_at'] = strtotime($job['created_at']);
        $job['started_at'] = $job['started_at'] ? strtotime($job['started_at']) : null;
        $job['completed_at'] = $job['completed_at'] ? strtotime($job['completed_at']) : null;
        $job['failed_at'] = $job['failed_at'] ? strtotime($job['failed_at']) : null;
        $job['updated_at'] = strtotime($job['updated_at']);
        
        // Parse JSON fields
        $job['parameters'] = $job['parameters'] ? json_decode($job['parameters'], true) : [];
        $job['result'] = $job['result'] ? json_decode($job['result'], true) : null;
        
        $this->sendSuccess($job);
    }
    
    /**
     * Send success response
     */
    private function sendSuccess($data)
    {
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    /**
     * Send error response
     */
    private function sendError($message, $code = 400)
    {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}

// Initialize and handle request
$api = new MonitorAPI();
$api->handleRequest();
