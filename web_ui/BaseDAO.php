<?php
/**
 * BaseDAO: Abstract base class for all Data Access Objects
 * Provides common functionality for error handling, session management, and basic operations
 */

require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/CsvHandler.php';

abstract class BaseDAO {
    protected $sessionManager;
    protected $csvHandler;
    protected $componentName;
    protected $sessionKey;
    
    public function __construct($componentName) {
        $this->componentName = $componentName;
        $this->sessionKey = $componentName . '_retry';
        $this->sessionManager = SessionManager::getInstance();
        $this->csvHandler = new CsvHandler();
    }
    
    /**
     * Log an error for this component
     */
    protected function logError($message) {
        $this->sessionManager->addError($this->componentName, $message);
    }
    
    /**
     * Get all errors for this component
     */
    public function getErrors() {
        return $this->sessionManager->getErrors($this->componentName);
    }
    
    /**
     * Clear errors for this component
     */
    public function clearErrors() {
        $this->sessionManager->clearErrors($this->componentName);
    }
    
    /**
     * Check if there are any errors
     */
    public function hasErrors() {
        return !empty($this->getErrors());
    }
    
    /**
     * Store retry data for failed operations
     */
    protected function setRetryData($data) {
        $this->sessionManager->setRetryData($this->sessionKey, $data);
    }
    
    /**
     * Get retry data for failed operations
     */
    public function getRetryData() {
        return $this->sessionManager->getRetryData($this->sessionKey);
    }
    
    /**
     * Clear retry data after successful retry
     */
    public function clearRetryData() {
        $this->sessionManager->clearRetryData($this->sessionKey);
    }
    
    /**
     * Read CSV file using centralized CSV handler
     */
    protected function readCsv($csvPath) {
        $data = $this->csvHandler->read($csvPath);
        
        // Transfer CSV errors to component errors
        foreach ($this->csvHandler->getErrors() as $error) {
            $this->logError($error);
        }
        
        return $data;
    }
    
    /**
     * Write CSV file using centralized CSV handler
     */
    protected function writeCsv($csvPath, $data) {
        $success = $this->csvHandler->write($csvPath, $data);
        
        // Transfer CSV errors to component errors
        foreach ($this->csvHandler->getErrors() as $error) {
            $this->logError($error);
        }
        
        return $success;
    }
    
    /**
     * Append to CSV file using centralized CSV handler
     */
    protected function appendCsv($csvPath, $data) {
        $success = $this->csvHandler->append($csvPath, $data);
        
        // Transfer CSV errors to component errors
        foreach ($this->csvHandler->getErrors() as $error) {
            $this->logError($error);
        }
        
        return $success;
    }
    
    /**
     * Validate CSV file structure
     */
    protected function validateCsv($csvPath, $expectedColumns = null) {
        $valid = $this->csvHandler->validate($csvPath, $expectedColumns);
        
        // Transfer CSV errors to component errors
        foreach ($this->csvHandler->getErrors() as $error) {
            $this->logError($error);
        }
        
        return $valid;
    }
    
    /**
     * Find the first existing file from a list of paths
     */
    protected function findExistingFile($paths) {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        return null;
    }
    
    /**
     * Handle operation result with retry logic
     */
    protected function handleOperationResult($success, $data = null) {
        if (!$success && $data !== null) {
            $this->setRetryData($data);
        } elseif ($success) {
            $this->clearRetryData();
        }
        
        return $success;
    }
}
