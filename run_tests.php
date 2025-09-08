<?php
/**
 * Simple test runner for Ksfraser Database and Auth modules
 * This can run without PHPUnit as a basic validation tool
 */

require_once __DIR__ . '/test_autoload.php';

use Ksfraser\Database\EnhancedDbManager;
use Ksfraser\Database\EnhancedUserAuthDAO;

class SimpleTestRunner
{
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    private $errors = [];

    public function addTest($name, $callable)
    {
        $this->tests[$name] = $callable;
    }

    public function runTests()
    {
        echo "=== Ksfraser Database and Auth Module Tests ===\n\n";
        
        foreach ($this->tests as $name => $test) {
            echo "Running: {$name}... ";
            
            try {
                $test();
                echo "PASS\n";
                $this->passed++;
            } catch (Exception $e) {
                echo "FAIL\n";
                $this->failed++;
                $this->errors[] = [
                    'test' => $name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ];
            }
        }
        
        $this->printSummary();
    }

    private function printSummary()
    {
        echo "\n=== Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total:  " . ($this->passed + $this->failed) . "\n";
        
        if (!empty($this->errors)) {
            echo "\n=== Failures ===\n";
            foreach ($this->errors as $error) {
                echo "FAIL: {$error['test']}\n";
                echo "Error: {$error['error']}\n";
                echo "Trace: " . substr($error['trace'], 0, 200) . "...\n\n";
            }
        }
        
        if ($this->failed > 0) {
            exit(1);
        }
    }

    public function assertEquals($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            throw new Exception($message ?: "Expected '{$expected}', got '{$actual}'");
        }
    }

    public function assertTrue($condition, $message = '')
    {
        if (!$condition) {
            throw new Exception($message ?: "Expected true, got false");
        }
    }

    public function assertFalse($condition, $message = '')
    {
        if ($condition) {
            throw new Exception($message ?: "Expected false, got true");
        }
    }

    public function assertNotNull($value, $message = '')
    {
        if ($value === null) {
            throw new Exception($message ?: "Expected non-null value, got null");
        }
    }

    public function assertInstanceOf($className, $object, $message = '')
    {
        if (!($object instanceof $className)) {
            throw new Exception($message ?: "Expected instance of {$className}, got " . get_class($object));
        }
    }
}

// Initialize test runner
$runner = new SimpleTestRunner();

// Database Manager Tests
$runner->addTest('Database Manager - Get Connection', function() use ($runner) {
    EnhancedDbManager::resetConnection();
    $connection = EnhancedDbManager::getConnection();
    $runner->assertInstanceOf('Ksfraser\Database\DatabaseConnectionInterface', $connection);
});

$runner->addTest('Database Manager - Current Driver', function() use ($runner) {
    EnhancedDbManager::resetConnection();
    EnhancedDbManager::getConnection();
    $driver = EnhancedDbManager::getCurrentDriver();
    $runner->assertNotNull($driver);
    $runner->assertEquals('pdo_sqlite', $driver); // Should fallback to SQLite in test environment
});

$runner->addTest('Database Manager - Fetch All', function() use ($runner) {
    EnhancedDbManager::resetConnection();
    $results = EnhancedDbManager::fetchAll("SELECT 1 as test");
    $runner->assertTrue(is_array($results));
    $runner->assertEquals(1, count($results));
    $runner->assertEquals(1, $results[0]['test']);
});

$runner->addTest('Database Manager - Fetch One', function() use ($runner) {
    EnhancedDbManager::resetConnection();
    $result = EnhancedDbManager::fetchOne("SELECT 'hello' as message");
    $runner->assertTrue(is_array($result));
    $runner->assertEquals('hello', $result['message']);
});

$runner->addTest('Database Manager - Fetch Value', function() use ($runner) {
    EnhancedDbManager::resetConnection();
    $value = EnhancedDbManager::fetchValue("SELECT 42");
    $runner->assertEquals(42, $value);
});

$runner->addTest('Database Manager - Transactions', function() use ($runner) {
    EnhancedDbManager::resetConnection();
    $runner->assertTrue(EnhancedDbManager::beginTransaction());
    $runner->assertTrue(EnhancedDbManager::rollback());
    
    $runner->assertTrue(EnhancedDbManager::beginTransaction());
    $runner->assertTrue(EnhancedDbManager::commit());
});

// Auth DAO Tests
$runner->addTest('Auth DAO - Register User', function() use ($runner) {
    EnhancedDbManager::resetConnection();
    $auth = new EnhancedUserAuthDAO();
    
    // Clean up any existing test users
    try {
        EnhancedDbManager::execute("DELETE FROM users WHERE username LIKE 'test_%'");
    } catch (Exception $e) {}
    
    $userId = $auth->registerUser('test_user', 'test@example.com', 'password123');
    $runner->assertTrue(is_int($userId));
    $runner->assertTrue($userId > 0);
});

$runner->addTest('Auth DAO - Authenticate User', function() use ($runner) {
    EnhancedDbManager::resetConnection();
    $auth = new EnhancedUserAuthDAO();
    
    // Clean up and register test user
    try {
        EnhancedDbManager::execute("DELETE FROM users WHERE username = 'test_auth'");
    } catch (Exception $e) {}
    
    $userId = $auth->registerUser('test_auth', 'auth@example.com', 'correct_password');
    $runner->assertTrue(is_int($userId));
    
    // Test correct authentication
    $result = $auth->authenticateUser('test_auth', 'correct_password');
    $runner->assertTrue(is_array($result));
    $runner->assertEquals('test_auth', $result['username']);
    
    // Test incorrect password
    $result = $auth->authenticateUser('test_auth', 'wrong_password');
    $runner->assertFalse($result);
});

$runner->addTest('Auth DAO - Get All Users', function() use ($runner) {
    EnhancedDbManager::resetConnection();
    $auth = new EnhancedUserAuthDAO();
    
    // Clean up and register multiple test users
    try {
        EnhancedDbManager::execute("DELETE FROM users WHERE username LIKE 'test_list_%'");
    } catch (Exception $e) {}
    
    $auth->registerUser('test_list_1', 'list1@example.com', 'password1');
    $auth->registerUser('test_list_2', 'list2@example.com', 'password2');
    
    $users = $auth->getAllUsers();
    $runner->assertTrue(is_array($users));
    $runner->assertTrue(count($users) >= 2);
    
    // Verify structure
    foreach ($users as $user) {
        $runner->assertTrue(array_key_exists('id', $user));
        $runner->assertTrue(array_key_exists('username', $user));
        $runner->assertTrue(array_key_exists('email', $user));
        $runner->assertFalse(array_key_exists('password_hash', $user)); // Should be filtered out
    }
});

$runner->addTest('Auth DAO - Get User By ID', function() use ($runner) {
    EnhancedDbManager::resetConnection();
    $auth = new EnhancedUserAuthDAO();
    
    // Clean up and register test user
    try {
        EnhancedDbManager::execute("DELETE FROM users WHERE username = 'test_getbyid'");
    } catch (Exception $e) {}
    
    $userId = $auth->registerUser('test_getbyid', 'getbyid@example.com', 'password123');
    $runner->assertTrue(is_int($userId));
    
    $user = $auth->getUserById($userId);
    $runner->assertNotNull($user);
    $runner->assertEquals($userId, $user['id']);
    $runner->assertEquals('test_getbyid', $user['username']);
});

$runner->addTest('Auth DAO - Delete User', function() use ($runner) {
    EnhancedDbManager::resetConnection();
    $auth = new EnhancedUserAuthDAO();
    
    // Clean up and register test user
    try {
        EnhancedDbManager::execute("DELETE FROM users WHERE username = 'test_delete'");
    } catch (Exception $e) {}
    
    $userId = $auth->registerUser('test_delete', 'delete@example.com', 'password123');
    $runner->assertTrue(is_int($userId));
    
    // Verify user exists
    $user = $auth->getUserById($userId);
    $runner->assertNotNull($user);
    
    // Delete user
    $result = $auth->deleteUser($userId);
    $runner->assertTrue($result);
    
    // Verify user is gone
    $deletedUser = $auth->getUserById($userId);
    $runner->assertTrue($deletedUser === null);
});

$runner->addTest('Auth DAO - Admin Status', function() use ($runner) {
    EnhancedDbManager::resetConnection();
    $auth = new EnhancedUserAuthDAO();
    
    // Clean up and register admin user
    try {
        EnhancedDbManager::execute("DELETE FROM users WHERE username = 'test_admin'");
    } catch (Exception $e) {}
    
    $adminId = $auth->registerUser('test_admin', 'admin@example.com', 'password123', true);
    $runner->assertTrue(is_int($adminId));
    
    $adminUser = $auth->getUserById($adminId);
    $runner->assertTrue((bool)$adminUser['is_admin']);
    
    // Test update admin status
    $result = $auth->updateUserAdminStatus($adminId, false);
    $runner->assertTrue($result);
    
    $updatedUser = $auth->getUserById($adminId);
    $runner->assertFalse((bool)$updatedUser['is_admin']);
});

$runner->addTest('Auth DAO - Database Info', function() use ($runner) {
    EnhancedDbManager::resetConnection();
    $auth = new EnhancedUserAuthDAO();
    
    $info = $auth->getDatabaseInfo();
    $runner->assertTrue(is_array($info));
    $runner->assertTrue(array_key_exists('driver', $info));
    $runner->assertTrue(array_key_exists('connection_class', $info));
    $runner->assertNotNull($info['driver']);
    $runner->assertNotNull($info['connection_class']);
});

// Run all tests
$runner->runTests();
?>
