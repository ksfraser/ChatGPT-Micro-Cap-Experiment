<?php
/**
 * User Authentication and Management System
 * 
 * Features:
 * - User registration and login
 * - Password hashing and verification
 * - Session management
 * - CSRF protection
 * - Role-based access control
 * - JWT token support for API access
 */

require_once __DIR__ . '/CommonDAO.php';
require_once __DIR__ . '/SessionManager.php';

class UserAuthDAO extends CommonDAO {
    
    private $sessionKey = 'user_auth';
    private $csrfKey = 'csrf_token';
    private $sessionManager;
    
    public function __construct() {
        parent::__construct('LegacyDatabaseConfig');
        
        // Use centralized SessionManager instead of direct session_start()
        $this->sessionManager = SessionManager::getInstance();
        
        // Log session initialization issues if any
        if (!$this->sessionManager->isSessionActive()) {
            $error = $this->sessionManager->getInitializationError();
            if ($error) {
                $this->logError("Session initialization issue: " . $error);
            }
        }
    }
    
    /**
     * Register a new user
     */
    public function registerUser($username, $email, $password, $isAdmin = false) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            // Validation
            if (empty($username) || empty($email) || empty($password)) {
                throw new Exception('Username, email, and password are required');
            }
            
            if (strlen($username) < 3 || strlen($username) > 64) {
                throw new Exception('Username must be between 3 and 64 characters');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            if (strlen($password) < 8) {
                throw new Exception('Password must be at least 8 characters long');
            }
            
            // Check if username or email already exists
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                throw new Exception('Username or email already exists');
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user with current timestamp
            $stmt = $this->pdo->prepare('INSERT INTO users (username, email, password_hash, is_admin, created_at) VALUES (?, ?, ?, ?, NOW())');
            if ($stmt->execute([$username, $email, $passwordHash, $isAdmin ? 1 : 0])) {
                $userId = $this->pdo->lastInsertId();
                return $userId;
            }
            
            throw new Exception('Failed to create user');
            
        } catch (Exception $e) {
            $this->logError("User registration failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Authenticate user login
     */
    public function loginUser($username, $password) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            if (empty($username) || empty($password)) {
                throw new Exception('Username and password are required');
            }
            
            // Get user by username or email
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('Invalid username or password');
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                throw new Exception('Invalid username or password');
            }
            
            // Set session
            $sessionData = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'is_admin' => (bool)$user['is_admin'],
                'login_time' => time()
            ];
            
            $this->sessionManager->set($this->sessionKey, $sessionData);
            
            // Generate CSRF token
            $this->generateCSRFToken();
            
            return $user;
            
        } catch (Exception $e) {
            $this->logError("User login failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Logout user
     */
    public function logoutUser() {
        $this->sessionManager->remove($this->sessionKey);
        $this->sessionManager->remove($this->csrfKey);
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        $this->sessionManager->destroy();
        return true;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        $userData = $this->sessionManager->get($this->sessionKey);
        return $userData && !empty($userData['id']);
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return $this->sessionManager->get($this->sessionKey);
        }
        return null;
    }
    
    /**
     * Get current user ID
     */
    public function getCurrentUserId() {
        $user = $this->getCurrentUser();
        return $user ? $user['id'] : null;
    }
    
    /**
     * Check if current user is admin
     */
    public function isAdmin() {
        $user = $this->getCurrentUser();
        return $user && $user['is_admin'];
    }
    
    /**
     * Require login (redirect if not logged in)
     */
    public function requireLogin($redirectUrl = 'login.php') {
        if (!$this->isLoggedIn()) {
            if (!headers_sent()) {
                header("Location: $redirectUrl");
            }
            throw new Exception("User not logged in - redirect to login required");
        }
    }
    
    /**
     * Require admin access
     */
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            throw new Exception('Admin access required');
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        $existingToken = $this->sessionManager->get($this->csrfKey);
        if (empty($existingToken)) {
            $token = bin2hex(random_bytes(32));
            $this->sessionManager->set($this->csrfKey, $token);
            return $token;
        }
        return $existingToken;
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token) {
        $sessionToken = $this->sessionManager->get($this->csrfKey);
        return $sessionToken && hash_equals($sessionToken, $token);
    }
    
    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            // Get current user
            $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Validate new password
            if (strlen($newPassword) < 8) {
                throw new Exception('New password must be at least 8 characters long');
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            if ($stmt->execute([$newPasswordHash, $userId])) {
                return true;
            }
            
            throw new Exception('Failed to update password');
            
        } catch (Exception $e) {
            $this->logError("Password change failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update user profile
     */
    public function updateUserProfile($userId, $email) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            // Check if email is already used by another user
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                throw new Exception('Email already in use by another user');
            }
            
            // Update email
            $stmt = $this->pdo->prepare('UPDATE users SET email = ? WHERE id = ?');
            if ($stmt->execute([$email, $userId])) {
                // Update session if it's the current user
                if ($this->getCurrentUserId() == $userId) {
                    $userData = $this->sessionManager->get($this->sessionKey);
                    if ($userData) {
                        $userData['email'] = $email;
                        $this->sessionManager->set($this->sessionKey, $userData);
                    }
                }
                return true;
            }
            
            throw new Exception('Failed to update profile');
            
        } catch (Exception $e) {
            $this->logError("Profile update failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get all users (admin only)
     */
    public function getAllUsers($limit = 100, $offset = 0) {
        try {
            if (!$this->pdo) {
                return [];
            }
            
            $stmt = $this->pdo->prepare('SELECT id, username, email, is_admin FROM users ORDER BY username LIMIT ? OFFSET ?');
            $stmt->execute([$limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logError("Get users failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        try {
            if (!$this->pdo) {
                return null;
            }
            
            $stmt = $this->pdo->prepare('SELECT id, username, email, is_admin FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logError("Get user by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Delete user (admin only)
     */
    public function deleteUser($userId) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            // Don't allow deleting self
            if ($this->getCurrentUserId() == $userId) {
                throw new Exception('Cannot delete your own account');
            }
            
            // Check if user has accounts - prevent deletion if they do
            $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM accounts WHERE user_id = ?');
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ((int)$result['count'] > 0) {
                throw new Exception('Cannot delete user: they have associated accounts');
            }
            
            // Delete user
            $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
            if ($stmt->execute([$userId])) {
                return $stmt->rowCount() > 0;
            }
            
            throw new Exception('Failed to delete user');
            
        } catch (Exception $e) {
            $this->logError("User deletion failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update user admin status
     */
    public function updateUserAdminStatus($userId, $isAdmin) {
        try {
            $stmt = $this->pdo->prepare('UPDATE users SET is_admin = ? WHERE id = ?');
            if ($stmt->execute([$isAdmin ? 1 : 0, $userId])) {
                return $stmt->rowCount() > 0;
            }
            
            throw new Exception('Failed to update user admin status');
            
        } catch (Exception $e) {
            $this->logError("User admin status update failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate JWT token for API access
     */
    public function generateJWTToken($userId) {
        // This would require firebase/php-jwt library
        // For now, return a simple token
        return base64_encode(json_encode([
            'user_id' => $userId,
            'issued_at' => time(),
            'expires_at' => time() + (24 * 60 * 60) // 24 hours
        ]));
    }
}
