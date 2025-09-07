<?php
/**
 * Migration adapter for the new database system
 * Allows existing code to work with the enhanced database layer
 */

// Include the enhanced database classes
require_once __DIR__ . '/../src/Ksfraser/Database/EnhancedDbManager.php';
require_once __DIR__ . '/../src/Ksfraser/Database/PdoConnection.php';
require_once __DIR__ . '/../src/Ksfraser/Database/MysqliConnection.php';
require_once __DIR__ . '/../src/Ksfraser/Database/PdoSqliteConnection.php';
require_once __DIR__ . '/../src/Ksfraser/Database/EnhancedUserAuthDAO.php';

use Ksfraser\Database\EnhancedDbManager;
use Ksfraser\Database\EnhancedUserAuthDAO;

/**
 * Enhanced UserAuthDAO that replaces the original with fallback support
 * This class maintains backward compatibility while using the new database layer
 */
class UserAuthDAO
{
    /** @var EnhancedUserAuthDAO The enhanced DAO */
    protected $enhancedDAO;
    
    /** @var array Database connection info */
    protected $dbInfo;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        try {
            $this->enhancedDAO = new EnhancedUserAuthDAO();
            $this->dbInfo = $this->enhancedDAO->getDatabaseInfo();
        } catch (Exception $e) {
            // Log the error but don't throw - maintain compatibility
            error_log("Enhanced database initialization failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Register a new user
     * 
     * @param string $username Username
     * @param string $email Email address
     * @param string $password Plain text password
     * @param bool $isAdmin Whether user is admin
     * @return int|bool User ID on success, false on failure
     */
    public function registerUser($username, $email, $password, $isAdmin = false)
    {
        return $this->enhancedDAO->registerUser($username, $email, $password, $isAdmin);
    }
    
    /**
     * Authenticate user login
     * 
     * @param string $username Username
     * @param string $password Plain text password
     * @return array|bool User data on success, false on failure
     */
    public function authenticateUser($username, $password)
    {
        return $this->enhancedDAO->authenticateUser($username, $password);
    }
    
    /**
     * Get all users
     * 
     * @return array Array of user data
     */
    public function getAllUsers()
    {
        return $this->enhancedDAO->getAllUsers();
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId User ID
     * @return array|null User data or null
     */
    public function getUserById($userId)
    {
        return $this->enhancedDAO->getUserById($userId);
    }
    
    /**
     * Update user admin status
     * 
     * @param int $userId User ID
     * @param bool $isAdmin Admin status
     * @return bool Success status
     */
    public function updateUserAdminStatus($userId, $isAdmin)
    {
        return $this->enhancedDAO->updateUserAdminStatus($userId, $isAdmin);
    }
    
    /**
     * Delete user
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function deleteUser($userId)
    {
        return $this->enhancedDAO->deleteUser($userId);
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if logged in
     */
    public function isLoggedIn()
    {
        return $this->enhancedDAO->isLoggedIn();
    }
    
    /**
     * Check if current user is admin
     * 
     * @return bool True if admin
     */
    public function isAdmin()
    {
        return $this->enhancedDAO->isAdmin();
    }
    
    /**
     * Get current user ID
     * 
     * @return int|null Current user ID or null
     */
    public function getCurrentUserId()
    {
        return $this->enhancedDAO->getCurrentUserId();
    }
    
    /**
     * Login user (set session)
     * 
     * @param array $user User data
     */
    public function loginUser($user)
    {
        $this->enhancedDAO->loginUser($user);
    }
    
    /**
     * Logout user (clear session)
     */
    public function logoutUser()
    {
        $this->enhancedDAO->logoutUser();
    }
    
    /**
     * Require user to be logged in
     * 
     * @throws Exception If user not logged in
     */
    public function requireLogin()
    {
        $this->enhancedDAO->requireLogin();
    }
    
    /**
     * Require user to be admin
     * 
     * @throws Exception If user not admin
     */
    public function requireAdmin()
    {
        $this->enhancedDAO->requireAdmin();
    }
    
    /**
     * Get database connection information
     * 
     * @return array Database info including driver type
     */
    public function getDatabaseInfo()
    {
        return $this->dbInfo;
    }
}

/**
 * Legacy database configuration classes for compatibility
 * These are simplified versions that delegate to the enhanced system
 */
class LegacyDatabaseConfig
{
    /**
     * Create database connection using enhanced manager
     * 
     * @return PDO|mysqli|null Database connection
     */
    public static function createConnection()
    {
        try {
            $connection = EnhancedDbManager::getConnection();
            
            // Return the underlying connection for compatibility
            if ($connection instanceof \Ksfraser\Database\PdoConnection) {
                return $connection->getAttribute(0); // Get underlying PDO
            } elseif ($connection instanceof \Ksfraser\Database\MysqliConnection) {
                return null; // MySQLi doesn't expose underlying connection easily
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Legacy database connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get configuration
     * 
     * @return array Configuration array
     */
    public static function getConfig()
    {
        return EnhancedDbManager::getConfig();
    }
}

class MicroCapDatabaseConfig extends LegacyDatabaseConfig
{
    // Inherits all functionality from LegacyDatabaseConfig
}

class CommonDAO
{
    /** @var mixed Database connection */
    protected $pdo;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pdo = LegacyDatabaseConfig::createConnection();
    }
    
    /**
     * Get database connection
     * 
     * @return mixed Database connection
     */
    protected function getConnection()
    {
        if ($this->pdo === null) {
            $this->pdo = LegacyDatabaseConfig::createConnection();
        }
        return $this->pdo;
    }
}
?>
