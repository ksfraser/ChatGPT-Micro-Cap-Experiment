<?php
/**
 * SessionManager: Centralized session management for the entire application
 * Handles all session operations, retry data storage, and session-based error tracking
 */
class SessionManager {
    private static $instance = null;
    
    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Store retry data for failed operations
     */
    public function setRetryData($key, $data) {
        $_SESSION['retry_data'][$key] = $data;
    }
    
    /**
     * Get retry data for failed operations
     */
    public function getRetryData($key) {
        return $_SESSION['retry_data'][$key] ?? null;
    }
    
    /**
     * Clear retry data after successful retry
     */
    public function clearRetryData($key) {
        unset($_SESSION['retry_data'][$key]);
    }
    
    /**
     * Store errors in session for display across requests
     */
    public function addError($component, $error) {
        $_SESSION['errors'][$component][] = $error;
    }
    
    /**
     * Get errors for a component
     */
    public function getErrors($component) {
        return $_SESSION['errors'][$component] ?? [];
    }
    
    /**
     * Clear errors for a component
     */
    public function clearErrors($component) {
        unset($_SESSION['errors'][$component]);
    }
    
    /**
     * Get all errors
     */
    public function getAllErrors() {
        return $_SESSION['errors'] ?? [];
    }
    
    /**
     * Clear all errors
     */
    public function clearAllErrors() {
        unset($_SESSION['errors']);
    }
    
    /**
     * Store arbitrary session data
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get arbitrary session data
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session key
     */
    public function remove($key) {
        unset($_SESSION[$key]);
    }
    
    /**
     * Destroy entire session
     */
    public function destroy() {
        session_destroy();
        self::$instance = null;
    }
}
