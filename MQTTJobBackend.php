<?php

/**
 * MQTT Job Backend using Mosquitto
 * Implements job queue using MQTT protocol with Mosquitto broker
 */
class MQTTJobBackend
{
    private $client;
    private $logger;
    private $config;
    private $isConnected = false;
    private $subscribedTopics = [];
    private $messageQueue = [];
    
    public function __construct($config, $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        
        if (!class_exists('Mosquitto\Client')) {
            throw new Exception('Mosquitto PHP extension is not installed. Install with: sudo apt-get install php-mosquitto');
        }
        
        $this->initializeClient();
        $this->connect();
    }
    
    /**
     * Initialize MQTT client
     */
    private function initializeClient()
    {
        $clientId = 'job_backend_' . uniqid();
        $this->client = new \Mosquitto\Client($clientId);
        
        // Set up callbacks
        $this->client->onConnect([$this, 'onConnect']);
        $this->client->onDisconnect([$this, 'onDisconnect']);
        $this->client->onMessage([$this, 'onMessage']);
        $this->client->onLog([$this, 'onLog']);
        
        // Set authentication if provided
        $username = $this->config['mqtt']['username'] ?? null;
        $password = $this->config['mqtt']['password'] ?? null;
        
        if ($username && $password) {
            $this->client->setCredentials($username, $password);
        }
        
        // Set will message for unexpected disconnections
        $willTopic = 'workers/disconnect';
        $willMessage = json_encode([
            'client_id' => $clientId,
            'timestamp' => time(),
            'reason' => 'unexpected'
        ]);
        $this->client->setWill($willTopic, $willMessage, 1, false);
    }
    
    /**
     * Connect to MQTT broker
     */
    private function connect()
    {
        $host = $this->config['mqtt']['host'] ?? 'localhost';
        $port = $this->config['mqtt']['port'] ?? 1883;
        $keepalive = $this->config['mqtt']['keepalive'] ?? 60;
        
        try {
            $this->client->connect($host, $port, $keepalive);
            $this->logger->info("Connecting to MQTT broker at {$host}:{$port}");
            
            // Start the network loop in non-blocking mode
            $this->client->loopStart();
            
            // Wait for connection
            $timeout = 10; // 10 seconds
            $start = time();
            while (!$this->isConnected && (time() - $start) < $timeout) {
                usleep(100000); // 100ms
            }
            
            if (!$this->isConnected) {
                throw new Exception("Failed to connect to MQTT broker within {$timeout} seconds");
            }
            
        } catch (Exception $e) {
            throw new Exception("Could not connect to MQTT broker: " . $e->getMessage());
        }
    }
    
    /**
     * MQTT connection callback
     */
    public function onConnect($rc)
    {
        if ($rc == 0) {
            $this->isConnected = true;
            $this->logger->info("Successfully connected to MQTT broker");
            
            // Subscribe to necessary topics
            $this->subscribeToTopics();
        } else {
            $this->logger->error("Failed to connect to MQTT broker. Return code: {$rc}");
        }
    }
    
    /**
     * MQTT disconnection callback
     */
    public function onDisconnect($rc)
    {
        $this->isConnected = false;
        $this->logger->warning("Disconnected from MQTT broker. Return code: {$rc}");
    }
    
    /**
     * MQTT message callback
     */
    public function onMessage($message)
    {
        $topic = $message->topic;
        $payload = $message->payload;
        
        $this->logger->debug("Received MQTT message on topic: {$topic}");
        
        // Store message in queue for processing
        $this->messageQueue[] = [
            'topic' => $topic,
            'payload' => $payload,
            'timestamp' => time()
        ];
    }
    
    /**
     * MQTT log callback
     */
    public function onLog($level, $str)
    {
        $this->logger->debug("MQTT Log [{$level}]: {$str}");
    }
    
    /**
     * Subscribe to necessary topics
     */
    private function subscribeToTopics()
    {
        $topics = [
            'workers/register' => 1,
            'workers/heartbeat' => 1,
            'workers/unregister' => 1,
            'jobs/request/+' => 1, // + is wildcard for worker ID
        ];
        
        foreach ($topics as $topic => $qos) {
            $this->client->subscribe($topic, $qos);
            $this->subscribedTopics[] = $topic;
            $this->logger->info("Subscribed to MQTT topic: {$topic}");
        }
    }
    
    /**
     * Register a worker
     */
    public function registerWorker($workerId, $workerInfo)
    {
        $topic = 'workers/register';
        $message = json_encode([
            'worker_id' => $workerId,
            'worker_info' => $workerInfo,
            'timestamp' => time()
        ]);
        
        $this->client->publish($topic, $message, 1, true); // QoS 1, retained
        
        $this->logger->info("Registered worker via MQTT: {$workerId}");
    }
    
    /**
     * Update worker heartbeat
     */
    public function updateWorkerHeartbeat($workerId)
    {
        $topic = "workers/heartbeat/{$workerId}";
        $message = json_encode([
            'worker_id' => $workerId,
            'timestamp' => time(),
            'status' => 'alive'
        ]);
        
        $this->client->publish($topic, $message, 0, false); // QoS 0, not retained
        
        return true;
    }
    
    /**
     * Unregister a worker
     */
    public function unregisterWorker($workerId)
    {
        $topic = 'workers/unregister';
        $message = json_encode([
            'worker_id' => $workerId,
            'timestamp' => time()
        ]);
        
        $this->client->publish($topic, $message, 1, false); // QoS 1, not retained
        
        // Clear retained registration message
        $this->client->publish('workers/register', '', 1, true);
        
        $this->logger->info("Unregistered worker via MQTT: {$workerId}");
    }
    
    /**
     * Get next job for worker
     */
    public function getNextJob($workerId, $jobTypes = [])
    {
        // Request job from available job topics
        $requestTopic = "jobs/request/{$workerId}";
        $requestMessage = json_encode([
            'worker_id' => $workerId,
            'job_types' => $jobTypes,
            'timestamp' => time()
        ]);
        
        $this->client->publish($requestTopic, $requestMessage, 1, false);
        
        // Process any pending messages
        $this->client->loop(100); // 100ms timeout
        
        // Check for job assignments in message queue
        $job = $this->checkForJobAssignment($workerId);
        
        return $job;
    }
    
    /**
     * Check message queue for job assignments
     */
    private function checkForJobAssignment($workerId)
    {
        $jobTopic = "jobs/assign/{$workerId}";
        
        foreach ($this->messageQueue as $index => $message) {
            if ($message['topic'] === $jobTopic) {
                $jobData = json_decode($message['payload'], true);
                
                if ($jobData && isset($jobData['id'])) {
                    // Remove message from queue
                    unset($this->messageQueue[$index]);
                    $this->messageQueue = array_values($this->messageQueue);
                    
                    return $jobData;
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
        
        // Publish to job queue topic
        $topic = "jobs/queue/{$priority}/{$jobType}";
        $message = json_encode($job);
        
        $qos = ($priority === 'high') ? 2 : 1; // Higher QoS for high priority jobs
        $this->client->publish($topic, $message, $qos, false);
        
        $this->logger->info("Added job {$jobId} to MQTT queue {$topic}");
        
        return $jobId;
    }
    
    /**
     * Complete job
     */
    public function completeJob($jobId, $workerId, $result)
    {
        $topic = "jobs/completed/{$jobId}";
        $message = json_encode([
            'job_id' => $jobId,
            'worker_id' => $workerId,
            'status' => 'completed',
            'result' => $result,
            'completed_at' => time()
        ]);
        
        $this->client->publish($topic, $message, 1, false);
        
        $this->logger->info("Job {$jobId} completed via MQTT");
    }
    
    /**
     * Fail job
     */
    public function failJob($jobId, $workerId, $error, $retry = true)
    {
        $topic = "jobs/failed/{$jobId}";
        $message = json_encode([
            'job_id' => $jobId,
            'worker_id' => $workerId,
            'status' => 'failed',
            'error' => $error,
            'failed_at' => time(),
            'retry' => $retry
        ]);
        
        $this->client->publish($topic, $message, 1, false);
        
        if ($retry) {
            // Re-queue the job
            $retryTopic = "jobs/retry/{$jobId}";
            $this->client->publish($retryTopic, $message, 1, false);
            $this->logger->info("Job {$jobId} failed, queued for retry via MQTT");
        } else {
            $this->logger->error("Job {$jobId} permanently failed via MQTT: {$error}");
        }
    }
    
    /**
     * Get queue statistics
     */
    public function getQueueStats()
    {
        // Request statistics from broker
        $requestTopic = 'system/stats/request';
        $requestMessage = json_encode([
            'timestamp' => time(),
            'request_id' => uniqid()
        ]);
        
        $this->client->publish($requestTopic, $requestMessage, 1, false);
        
        // Process messages for a short time to get response
        $this->client->loop(1000); // 1 second timeout
        
        // Parse statistics from message queue
        $stats = $this->parseStatsFromMessages();
        
        return $stats;
    }
    
    /**
     * Parse statistics from received messages
     */
    private function parseStatsFromMessages()
    {
        $stats = [
            'active_workers' => 0,
            'queues' => []
        ];
        
        // Count active workers from heartbeat messages
        $recentTime = time() - 300; // 5 minutes ago
        
        foreach ($this->messageQueue as $message) {
            if (strpos($message['topic'], 'workers/heartbeat/') === 0) {
                $data = json_decode($message['payload'], true);
                if ($data && $data['timestamp'] > $recentTime) {
                    $stats['active_workers']++;
                }
            }
        }
        
        // For MQTT, queue stats would typically come from broker statistics
        // This is a simplified implementation
        $stats['queues'] = [
            'jobs/queue/high' => 0,
            'jobs/queue/normal' => 0,
            'jobs/queue/low' => 0
        ];
        
        return $stats;
    }
    
    /**
     * Cleanup expired workers
     */
    public function cleanupExpiredWorkers()
    {
        $cleanupTopic = 'workers/cleanup';
        $message = json_encode([
            'action' => 'cleanup_expired',
            'timestamp' => time(),
            'expiry_threshold' => 300 // 5 minutes
        ]);
        
        $this->client->publish($cleanupTopic, $message, 1, false);
        
        $this->logger->info("Published worker cleanup request via MQTT");
        
        return 0; // Actual count would come from broker response
    }
    
    /**
     * Setup message processing loop
     */
    public function startMessageLoop()
    {
        while ($this->isConnected) {
            $this->client->loop(1000); // 1 second timeout
            
            // Process any queued messages
            $this->processMessageQueue();
            
            // Clean old messages from queue
            $this->cleanupMessageQueue();
        }
    }
    
    /**
     * Process queued messages
     */
    private function processMessageQueue()
    {
        // This would be extended based on your specific message processing needs
        foreach ($this->messageQueue as $message) {
            $this->logger->debug("Processing MQTT message: {$message['topic']}");
        }
    }
    
    /**
     * Cleanup old messages from internal queue
     */
    private function cleanupMessageQueue()
    {
        $maxAge = 3600; // 1 hour
        $cutoffTime = time() - $maxAge;
        
        $this->messageQueue = array_filter($this->messageQueue, function($message) use ($cutoffTime) {
            return $message['timestamp'] > $cutoffTime;
        });
    }
    
    /**
     * Disconnect from MQTT broker
     */
    public function disconnect()
    {
        if ($this->isConnected) {
            $this->client->disconnect();
            $this->client->loopStop();
        }
    }
    
    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
