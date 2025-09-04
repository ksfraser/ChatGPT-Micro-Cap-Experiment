<?php

/**
 * Simple Job Logger
 * Provides logging functionality for job processors
 */
class JobLogger
{
    private $logFile;
    private $dateFormat = 'Y-m-d H:i:s';
    
    public function __construct($logFile)
    {
        $this->logFile = $logFile;
        
        // Ensure log directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Log an info message
     */
    public function info($message)
    {
        $this->writeLog('INFO', $message);
    }
    
    /**
     * Log a warning message
     */
    public function warning($message)
    {
        $this->writeLog('WARNING', $message);
    }
    
    /**
     * Log an error message
     */
    public function error($message)
    {
        $this->writeLog('ERROR', $message);
    }
    
    /**
     * Log a debug message
     */
    public function debug($message)
    {
        $this->writeLog('DEBUG', $message);
    }
    
    /**
     * Write log entry to file
     */
    private function writeLog($level, $message)
    {
        $timestamp = date($this->dateFormat);
        $pid = getmypid();
        $logEntry = "[{$timestamp}] [{$level}] [PID:{$pid}] {$message}" . PHP_EOL;
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get recent log entries
     */
    public function getRecentEntries($lines = 100)
    {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $file = new SplFileObject($this->logFile);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $entries = [];
        
        $file->seek($startLine);
        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $entries[] = $line;
            }
            $file->next();
        }
        
        return $entries;
    }
    
    /**
     * Clear log file
     */
    public function clear()
    {
        if (file_exists($this->logFile)) {
            file_put_contents($this->logFile, '');
        }
    }
    
    /**
     * Rotate log file
     */
    public function rotate($maxSize = 10485760) // 10MB default
    {
        if (!file_exists($this->logFile)) {
            return;
        }
        
        if (filesize($this->logFile) > $maxSize) {
            $rotatedFile = $this->logFile . '.' . date('Y-m-d-H-i-s');
            rename($this->logFile, $rotatedFile);
            
            // Compress old log file
            if (function_exists('gzencode')) {
                $content = file_get_contents($rotatedFile);
                file_put_contents($rotatedFile . '.gz', gzencode($content));
                unlink($rotatedFile);
            }
        }
    }
}
