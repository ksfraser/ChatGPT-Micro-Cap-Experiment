<?php

/**
 * RabbitMQ Job Backend
 * Implements job queue using RabbitMQ
 */
class RabbitMQJobBackend
{
    private $connection;
    private $channel;
    private $logger;
    private $config;
    private $exchanges = [];
    private $queues = [];
    
    public function __construct($config, $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        
        if (!class_exists('PhpAmqpLib\Connection\AMQPStreamConnection')) {
            throw new Exception('php-amqplib library is not installed. Run: composer require php-amqplib/php-amqplib');
        }
        
        $this->connect();
        $this->setupExchangesAndQueues();
    }
    
    /**
     * Connect to RabbitMQ
     */
    private function connect()
    {
        $host = $this->config['rabbitmq']['host'] ?? 'localhost';
        $port = $this->config['rabbitmq']['port'] ?? 5672;
        $user = $this->config['rabbitmq']['user'] ?? 'guest';
        $password = $this->config['rabbitmq']['password'] ?? 'guest';
        $vhost = $this->config['rabbitmq']['vhost'] ?? '/';
        
        $this->connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
            $host, $port, $user, $password, $vhost
        );
        
        $this->channel = $this->connection->channel();
        
        $this->logger->info("Connected to RabbitMQ at {$host}:{$port}");
    }
    
    /**
     * Setup exchanges and queues
     */
    private function setupExchangesAndQueues()
    {
        // Declare exchanges
        $this->channel->exchange_declare('jobs', 'topic', false, true, false);
        $this->channel->exchange_declare('workers', 'fanout', false, true, false);
        
        // Declare standard job queues
        $priorities = ['high', 'normal', 'low'];
        $jobTypes = ['technical_analysis', 'price_update', 'data_import', 'portfolio_analysis'];
        
        foreach ($priorities as $priority) {
            foreach ($jobTypes as $jobType) {
                $queueName = "jobs.{$priority}.{$jobType}";
                $routingKey = "jobs.{$priority}.{$jobType}";
                
                $this->channel->queue_declare($queueName, false, true, false, false);
                $this->channel->queue_bind($queueName, 'jobs', $routingKey);
                
                $this->queues[$routingKey] = $queueName;
            }
        }
        
        // Worker status queue
        $this->channel->queue_declare('worker_status', false, false, false, true);
        $this->channel->queue_bind('worker_status', 'workers');
    }
    
    /**
     * Register a worker
     */
    public function registerWorker($workerId, $workerInfo)
    {
        $message = json_encode([
            'action' => 'register',
            'worker_id' => $workerId,
            'worker_info' => $workerInfo,
            'timestamp' => time()
        ]);
        
        $msg = new \PhpAmqpLib\Message\AMQPMessage($message, [
            'delivery_mode' => 1 // Non-persistent
        ]);
        
        $this->channel->basic_publish($msg, 'workers');
        
        $this->logger->info("Registered worker: {$workerId}");
    }
    
    /**
     * Update worker heartbeat
     */
    public function updateWorkerHeartbeat($workerId)
    {
        $message = json_encode([
            'action' => 'heartbeat',
            'worker_id' => $workerId,
            'timestamp' => time()
        ]);
        
        $msg = new \PhpAmqpLib\Message\AMQPMessage($message, [
            'delivery_mode' => 1
        ]);
        
        $this->channel->basic_publish($msg, 'workers');
        
        return true;
    }
    
    /**
     * Unregister a worker
     */
    public function unregisterWorker($workerId)
    {
        $message = json_encode([
            'action' => 'unregister',
            'worker_id' => $workerId,
            'timestamp' => time()
        ]);
        
        $msg = new \PhpAmqpLib\Message\AMQPMessage($message, [
            'delivery_mode' => 1
        ]);
        
        $this->channel->basic_publish($msg, 'workers');
        
        $this->logger->info("Unregistered worker: {$workerId}");
    }
    
    /**
     * Get next job for worker
     */
    public function getNextJob($workerId, $jobTypes = [])
    {
        $priorities = ['high', 'normal', 'low'];
        
        foreach ($priorities as $priority) {
            foreach ($jobTypes as $jobType) {
                $queueName = "jobs.{$priority}.{$jobType}";
                
                $msg = $this->channel->basic_get($queueName, true); // Auto-ack
                
                if ($msg) {
                    $jobData = json_decode($msg->getBody(), true);
                    
                    if ($jobData) {
                        $jobData['status'] = 'running';
                        $jobData['worker_id'] = $workerId;
                        $jobData['started_at'] = time();
                        
                        return $jobData;
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
        $routingKey = "jobs.{$priority}.{$jobType}";
        
        $message = json_encode($job);
        $msg = new \PhpAmqpLib\Message\AMQPMessage($message, [
            'delivery_mode' => 2 // Persistent
        ]);
        
        $this->channel->basic_publish($msg, 'jobs', $routingKey);
        
        $this->logger->info("Added job {$jobId} to queue {$routingKey}");
        
        return $jobId;
    }
    
    /**
     * Complete job
     */
    public function completeJob($jobId, $workerId, $result)
    {
        // In RabbitMQ, job completion is typically handled by the consumer acknowledging the message
        // This method can be used for logging or notifying other systems
        
        $this->logger->info("Completed job {$jobId} by worker {$workerId}");
        
        // Optionally publish completion notification
        $message = json_encode([
            'job_id' => $jobId,
            'worker_id' => $workerId,
            'status' => 'completed',
            'result' => $result,
            'completed_at' => time()
        ]);
        
        $msg = new \PhpAmqpLib\Message\AMQPMessage($message, [
            'delivery_mode' => 1
        ]);
        
        $this->channel->basic_publish($msg, 'jobs', 'jobs.completed');
    }
    
    /**
     * Fail job
     */
    public function failJob($jobId, $workerId, $error, $retry = true)
    {
        $this->logger->error("Job {$jobId} failed: {$error}");
        
        if ($retry) {
            // Re-queue the job with updated attempt count
            // This would typically be handled by rejecting the message and re-queuing
            $this->logger->info("Retrying job {$jobId}");
        }
        
        // Publish failure notification
        $message = json_encode([
            'job_id' => $jobId,
            'worker_id' => $workerId,
            'status' => 'failed',
            'error' => $error,
            'failed_at' => time(),
            'retry' => $retry
        ]);
        
        $msg = new \PhpAmqpLib\Message\AMQPMessage($message, [
            'delivery_mode' => 1
        ]);
        
        $this->channel->basic_publish($msg, 'jobs', 'jobs.failed');
    }
    
    /**
     * Get queue statistics
     */
    public function getQueueStats()
    {
        $stats = [
            'queues' => []
        ];
        
        foreach ($this->queues as $routingKey => $queueName) {
            try {
                list($messageCount, $consumerCount) = $this->channel->queue_declare($queueName, true);
                
                if ($messageCount > 0) {
                    $stats['queues'][$queueName] = [
                        'messages' => $messageCount,
                        'consumers' => $consumerCount
                    ];
                }
            } catch (Exception $e) {
                $this->logger->warning("Could not get stats for queue {$queueName}: " . $e->getMessage());
            }
        }
        
        return $stats;
    }
    
    /**
     * Cleanup expired workers
     */
    public function cleanupExpiredWorkers()
    {
        // In RabbitMQ, worker cleanup is typically handled by connection monitoring
        // This method can publish a cleanup request to worker monitoring service
        
        $message = json_encode([
            'action' => 'cleanup_expired',
            'timestamp' => time()
        ]);
        
        $msg = new \PhpAmqpLib\Message\AMQPMessage($message, [
            'delivery_mode' => 1
        ]);
        
        $this->channel->basic_publish($msg, 'workers');
        
        return 0; // Return value would come from monitoring service
    }
    
    /**
     * Setup consumer for worker status monitoring
     */
    public function setupWorkerMonitoring($callback)
    {
        $this->channel->basic_consume(
            'worker_status',
            '',
            false,
            true,
            false,
            false,
            $callback
        );
        
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }
    
    /**
     * Close connection
     */
    public function close()
    {
        if ($this->channel) {
            $this->channel->close();
        }
        
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->close();
    }
}
