<?php
/**
 * Enhanced Test Runner for Trading System
 * Tests both legacy Ksfraser modules and new SOLID architecture components
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
        echo "=== Enhanced Trading System Test Suite ===\n\n";
        
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
    
    public function assertStringContainsString($needle, $haystack, $message = '')
    {
        if (strpos($haystack, $needle) === false) {
            throw new Exception($message ?: "String does not contain '{$needle}'");
        }
    }
    
    public function assertStringNotContainsString($needle, $haystack, $message = '')
    {
        if (strpos($haystack, $needle) !== false) {
            throw new Exception($message ?: "String should not contain '{$needle}'");
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

// SOLID Architecture Tests
echo "\n=== Adding SOLID Architecture Tests ===\n";

// Include UI Renderer components
if (file_exists(__DIR__ . '/web_ui/UiRenderer.php')) {
    require_once __DIR__ . '/web_ui/UiRenderer.php';
    
    $runner->addTest('UI - NavigationDto Creation', function() use ($runner) {
        $navDto = new NavigationDto('Test Title', 'dashboard', ['username' => 'test'], true, [], true);
        $runner->assertEquals('Test Title', $navDto->title);
        $runner->assertEquals('dashboard', $navDto->currentPage);
        $runner->assertTrue($navDto->isAdmin);
        $runner->assertTrue($navDto->isAuthenticated);
    });
    
    $runner->addTest('UI - NavigationDto Defaults', function() use ($runner) {
        $navDto = new NavigationDto();
        $runner->assertEquals('Enhanced Trading System', $navDto->title);
        $runner->assertEquals('', $navDto->currentPage);
        $runner->assertFalse($navDto->isAdmin);
        $runner->assertFalse($navDto->isAuthenticated);
    });
    
    $runner->addTest('UI - CardDto Creation', function() use ($runner) {
        $cardDto = new CardDto('Test Card', 'Test content', 'success', '🎯');
        $runner->assertEquals('Test Card', $cardDto->title);
        $runner->assertEquals('Test content', $cardDto->content);
        $runner->assertEquals('success', $cardDto->type);
        $runner->assertEquals('🎯', $cardDto->icon);
    });
    
    $runner->addTest('UI - CSS Provider Base Styles', function() use ($runner) {
        $css = CssProvider::getBaseStyles();
        $runner->assertStringContainsString('body {', $css);
        $runner->assertStringContainsString('.container {', $css);
        $runner->assertStringContainsString('.card {', $css);
        $runner->assertStringContainsString('.btn {', $css);
    });
    
    $runner->addTest('UI - CSS Provider Navigation Styles', function() use ($runner) {
        $css = CssProvider::getNavigationStyles();
        $runner->assertStringContainsString('.nav-header {', $css);
        $runner->assertStringContainsString('.nav-container {', $css);
        $runner->assertStringContainsString('.admin-badge {', $css);
    });
    
    $runner->addTest('UI - Navigation Component Authenticated', function() use ($runner) {
        $navData = new NavigationDto('Test System', 'dashboard', ['username' => 'testuser'], false, [], true);
        $navComponent = new NavigationComponent($navData);
        $html = $navComponent->toHtml();
        $runner->assertStringContainsString('Test System', $html);
        $runner->assertStringContainsString('testuser', $html);
        $runner->assertStringContainsString('🚪 Logout', $html);
    });
    
    $runner->addTest('UI - Navigation Component Admin', function() use ($runner) {
        $navData = new NavigationDto('Admin System', 'admin', ['username' => 'admin'], true, [], true);
        $navComponent = new NavigationComponent($navData);
        $html = $navComponent->toHtml();
        $runner->assertStringContainsString('nav-header admin', $html);
        $runner->assertStringContainsString('ADMIN', $html);
    });
    
    $runner->addTest('UI - Card Component Basic', function() use ($runner) {
        $cardData = new CardDto('Test Title', 'Test content');
        $cardComponent = new CardComponent($cardData);
        $html = $cardComponent->toHtml();
        $runner->assertStringContainsString('<div class="card">', $html);
        $runner->assertStringContainsString('Test Title', $html);
        $runner->assertStringContainsString('Test content', $html);
    });
    
    $runner->addTest('UI - Card Component with Actions', function() use ($runner) {
        $actions = [['url' => 'action.php', 'label' => 'Action', 'class' => 'btn', 'icon' => '🔧']];
        $cardData = new CardDto('Card with Actions', 'Content', 'info', '📋', $actions);
        $cardComponent = new CardComponent($cardData);
        $html = $cardComponent->toHtml();
        $runner->assertStringContainsString('<a href="action.php" class="btn">🔧 Action</a>', $html);
    });
    
    $runner->addTest('UI - Page Renderer Basic', function() use ($runner) {
        $navData = new NavigationDto('Test Page', 'test', ['username' => 'user'], false, [], true);
        $navigation = new NavigationComponent($navData);
        $pageRenderer = new PageRenderer('Test Page Title', $navigation, []);
        $html = $pageRenderer->render();
        $runner->assertStringContainsString('<!DOCTYPE html>', $html);
        $runner->assertStringContainsString('<title>Test Page Title</title>', $html);
        $runner->assertStringContainsString('Test Page', $html);
        $runner->assertStringContainsString('</body></html>', $html);
    });
    
    $runner->addTest('UI - UI Factory Navigation Component', function() use ($runner) {
        $user = ['username' => 'testuser'];
        $menuItems = [['url' => 'test.php', 'label' => 'Test', 'active' => false]];
        $navigation = UiFactory::createNavigationComponent('Factory Test', 'dashboard', $user, true, $menuItems, true);
        $runner->assertInstanceOf('NavigationComponent', $navigation);
    });
    
    $runner->addTest('UI - UI Factory Card', function() use ($runner) {
        $card = UiFactory::createCard('Factory Card', 'Factory content', 'success', '🎯');
        $runner->assertInstanceOf('CardComponent', $card);
    });
    
    $runner->addTest('UI - XSS Prevention', function() use ($runner) {
        $maliciousInput = '<script>alert("XSS")</script>';
        $navData = new NavigationDto($maliciousInput, 'test', ['username' => $maliciousInput], false, [], true);
        $navigation = new NavigationComponent($navData);
        $html = $navigation->toHtml();
        $runner->assertStringNotContainsString('<script>', $html);
        $runner->assertStringContainsString('&lt;script&gt;', $html);
    });
    
    echo "Added 13 SOLID Architecture UI tests\n";
} else {
    echo "UiRenderer.php not found, skipping UI tests\n";
}

// Run all tests
$runner->runTests();
?>
