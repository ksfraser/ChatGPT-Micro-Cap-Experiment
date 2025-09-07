<?php

/**
 * Migration Script - Move Authentication Components to Library
 * 
 * This script migrates existing authentication components from web_ui
 * to the new Ksfraser\Auth library structure
 */

require_once __DIR__ . '/vendor/autoload.php';

use Ksfraser\Auth\Auth;
use Ksfraser\Auth\AuthConfig;
use Ksfraser\Auth\AuthDatabase;

class AuthMigrationScript
{
    private $projectRoot;
    private $webUIPath;
    private $authLibPath;
    
    public function __construct()
    {
        $this->projectRoot = __DIR__;
        $this->webUIPath = $this->projectRoot . '/web_ui';
        $this->authLibPath = $this->projectRoot . '/src/Ksfraser/Auth';
    }
    
    /**
     * Run the migration
     */
    public function migrate(): void
    {
        echo "Starting Authentication Library Migration...\n\n";
        
        $this->step1_verifyLibraryStructure();
        $this->step2_migrateDatabase();
        $this->step3_createCompatibilityWrapper();
        $this->step4_createExampleIntegration();
        $this->step5_setupConfiguration();
        $this->step6_testSystem();
        
        echo "\nMigration completed successfully!\n";
        $this->printNextSteps();
    }
    
    /**
     * Step 1: Verify library structure
     */
    private function step1_verifyLibraryStructure(): void
    {
        echo "Step 1: Verifying library structure...\n";
        
        $requiredFiles = [
            'Auth.php',
            'AuthManager.php',
            'AuthConfig.php',
            'AuthDatabase.php',
            'AuthMiddleware.php',
            'AuthUI.php',
            'AuthUtils.php',
            'README.md'
        ];
        
        foreach ($requiredFiles as $file) {
            $filepath = $this->authLibPath . '/' . $file;
            if (!file_exists($filepath)) {
                throw new Exception("Required file missing: {$file}");
            }
            echo "  ✓ {$file}\n";
        }
        
        echo "  Library structure verified.\n\n";
    }
    
    /**
     * Step 2: Migrate database configuration
     */
    private function step2_migrateDatabase(): void
    {
        echo "Step 2: Setting up database configuration...\n";
        
        // Try to read existing database configuration
        $dbConfig = $this->extractDatabaseConfig();
        
        if ($dbConfig) {
            echo "  Found existing database configuration.\n";
            
            // Create auth-specific configuration
            $authConfig = [
                'database' => $dbConfig,
                'security' => [
                    'csrf_protection' => true,
                    'rate_limiting' => true,
                    'audit_logging' => true
                ],
                'ui' => [
                    'login_url' => '/web_ui/login.php',
                    'register_url' => '/web_ui/register.php',
                    'logout_url' => '/web_ui/logout.php',
                    'dashboard_url' => '/web_ui/dashboard.php',
                    'unauthorized_url' => '/web_ui/unauthorized.php'
                ]
            ];
            
            // Save configuration
            $configPath = $this->projectRoot . '/config/auth.php';
            $this->ensureDirectoryExists(dirname($configPath));
            
            $configContent = "<?php\n\n// Ksfraser Auth Configuration\n\nreturn " . var_export($authConfig, true) . ";\n";
            file_put_contents($configPath, $configContent);
            
            echo "  ✓ Configuration saved to config/auth.php\n";
        } else {
            echo "  No existing database configuration found.\n";
            echo "  Please configure database settings manually.\n";
        }
        
        echo "\n";
    }
    
    /**
     * Step 3: Create compatibility wrapper
     */
    private function step3_createCompatibilityWrapper(): void
    {
        echo "Step 3: Creating compatibility wrapper...\n";
        
        $wrapperPath = $this->webUIPath . '/AuthLibraryWrapper.php';
        
        $wrapperContent = '<?php

/**
 * Compatibility wrapper for existing web_ui components
 * 
 * This wrapper allows existing authentication code to work
 * with the new Ksfraser\Auth library
 */

require_once __DIR__ . \'/../vendor/autoload.php\';

use Ksfraser\Auth\Auth;

class AuthLibraryWrapper
{
    private static $auth = null;
    
    /**
     * Get Auth instance
     */
    public static function getInstance(): Auth
    {
        if (self::$auth === null) {
            // Load configuration if it exists
            $configPath = __DIR__ . \'/../config/auth.php\';
            if (file_exists($configPath)) {
                $config = require $configPath;
                self::$auth = Auth::getInstance($config);
            } else {
                self::$auth = Auth::getInstance();
            }
        }
        
        return self::$auth;
    }
    
    /**
     * Compatibility methods for existing UserAuthDAO usage
     */
    public static function authenticateUser($username, $password): array
    {
        try {
            $user = self::getInstance()->login($username, $password);
            return [
                \'success\' => true,
                \'user\' => $user,
                \'message\' => \'Login successful\'
            ];
        } catch (Exception $e) {
            return [
                \'success\' => false,
                \'message\' => $e->getMessage()
            ];
        }
    }
    
    public static function registerUser($username, $email, $password, $isAdmin = false): array
    {
        try {
            $userId = self::getInstance()->register($username, $email, $password, $isAdmin);
            return [
                \'success\' => true,
                \'user_id\' => $userId,
                \'message\' => \'Registration successful\'
            ];
        } catch (Exception $e) {
            return [
                \'success\' => false,
                \'message\' => $e->getMessage()
            ];
        }
    }
    
    public static function isUserLoggedIn(): bool
    {
        return self::getInstance()->check();
    }
    
    public static function getCurrentUser(): ?array
    {
        return self::getInstance()->user();
    }
    
    public static function isUserAdmin(): bool
    {
        return self::getInstance()->admin();
    }
    
    public static function logout(): bool
    {
        return self::getInstance()->logout();
    }
    
    public static function generateCSRFToken(): string
    {
        return self::getInstance()->csrf();
    }
    
    public static function validateCSRFToken($token): bool
    {
        return self::getInstance()->validateCSRF($token);
    }
    
    public static function requireAuthentication(): void
    {
        self::getInstance()->requireAuth();
    }
    
    public static function requireAdminAccess(): void
    {
        self::getInstance()->requireAdmin();
    }
}

// Create global compatibility functions
function getAuthInstance(): Auth
{
    return AuthLibraryWrapper::getInstance();
}

function isLoggedIn(): bool
{
    return AuthLibraryWrapper::isUserLoggedIn();
}

function getCurrentUser(): ?array
{
    return AuthLibraryWrapper::getCurrentUser();
}

function isAdmin(): bool
{
    return AuthLibraryWrapper::isUserAdmin();
}

function requireAuth(): void
{
    AuthLibraryWrapper::requireAuthentication();
}

function requireAdmin(): void
{
    AuthLibraryWrapper::requireAdminAccess();
}
';
        
        file_put_contents($wrapperPath, $wrapperContent);
        echo "  ✓ Compatibility wrapper created at web_ui/AuthLibraryWrapper.php\n\n";
    }
    
    /**
     * Step 4: Create example integration
     */
    private function step4_createExampleIntegration(): void
    {
        echo "Step 4: Creating example integration files...\n";
        
        $examplePath = $this->projectRoot . '/examples/auth';
        $this->ensureDirectoryExists($examplePath);
        
        // Create example pages using the new library
        $auth = Auth::getInstance();
        $pages = $auth->createExamplePages($examplePath);
        
        foreach ($pages as $filename => $content) {
            echo "  ✓ Created {$filename}\n";
        }
        
        // Create integration example
        $integrationExample = '<?php

/**
 * Example: Integrating Ksfraser Auth with existing application
 */

require_once __DIR__ . \'/../../vendor/autoload.php\';

use Ksfraser\Auth\Auth;

// Option 1: Use the new library directly
$auth = Auth::getInstance();

// Option 2: Use compatibility wrapper for existing code
require_once __DIR__ . \'/../../web_ui/AuthLibraryWrapper.php\';

// Example: Protect a page
try {
    $auth->requireAuth();
    echo "Welcome, " . $auth->user()[\'username\'];
} catch (Exception $e) {
    header(\'Location: /examples/auth/login.php\');
    exit;
}

// Example: Admin-only section
if ($auth->admin()) {
    echo "<p>Admin features available</p>";
}

// Example: CSRF protection
if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    if (!$auth->validateCSRF($_POST[\'csrf_token\'] ?? \'\')) {
        die(\'Invalid CSRF token\');
    }
    // Process form
}

// Example: Get current authentication status
$status = $auth->status();
echo "<pre>" . json_encode($status, JSON_PRETTY_PRINT) . "</pre>";
';
        
        file_put_contents($examplePath . '/integration_example.php', $integrationExample);
        echo "  ✓ Created integration_example.php\n\n";
    }
    
    /**
     * Step 5: Setup configuration
     */
    private function step5_setupConfiguration(): void
    {
        echo "Step 5: Setting up configuration...\n";
        
        $configPath = $this->projectRoot . '/config/auth.php';
        
        if (!file_exists($configPath)) {
            // Create default configuration
            $this->ensureDirectoryExists(dirname($configPath));
            AuthConfig::createTemplate($configPath);
            echo "  ✓ Created default configuration template\n";
        } else {
            echo "  ✓ Configuration file already exists\n";
        }
        
        // Create environment-specific config template
        $envConfigPath = $this->projectRoot . '/config/auth.local.php.example';
        $envConfig = '<?php

// Local environment configuration
// Copy this file to auth.local.php and customize for your environment

return [
    \'database\' => [
        \'host\' => \'localhost\',
        \'database\' => \'your_database_name\',
        \'username\' => \'your_db_username\',
        \'password\' => \'your_db_password\'
    ],
    \'security\' => [
        \'jwt_secret\' => \'your-production-secret-key\',
        \'csrf_protection\' => true,
        \'rate_limiting\' => true
    ]
];
';
        
        file_put_contents($envConfigPath, $envConfig);
        echo "  ✓ Created environment configuration template\n\n";
    }
    
    /**
     * Step 6: Test the system
     */
    private function step6_testSystem(): void
    {
        echo "Step 6: Testing system...\n";
        
        try {
            // Load configuration if available
            $configPath = $this->projectRoot . '/config/auth.php';
            if (file_exists($configPath)) {
                $config = require $configPath;
                $auth = Auth::getInstance($config);
            } else {
                $auth = Auth::getInstance();
                echo "  ⚠ No configuration file found, using defaults\n";
            }
            
            // Run system tests
            $tests = $auth->test();
            
            foreach ($tests as $test => $result) {
                $status = $result ? '✓' : '✗';
                echo "  {$status} {$test}: " . ($result ? 'PASS' : 'FAIL') . "\n";
            }
            
            if (isset($tests['error'])) {
                echo "  Error: " . $tests['error'] . "\n";
            }
            
        } catch (Exception $e) {
            echo "  ✗ System test failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Extract database configuration from existing files
     */
    private function extractDatabaseConfig(): ?array
    {
        // Try to find existing database configuration
        $configFiles = [
            $this->webUIPath . '/CommonDAO.php',
            $this->webUIPath . '/DbConfigClasses.php',
            $this->projectRoot . '/config/database.php'
        ];
        
        foreach ($configFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                // Try to extract database credentials
                $config = [];
                
                if (preg_match('/\$host\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                    $config['host'] = $matches[1];
                }
                
                if (preg_match('/\$dbname\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                    $config['database'] = $matches[1];
                }
                
                if (preg_match('/\$username\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                    $config['username'] = $matches[1];
                }
                
                if (preg_match('/\$password\s*=\s*[\'"]([^\'"]*?)[\'"]/', $content, $matches)) {
                    $config['password'] = $matches[1];
                }
                
                if (count($config) >= 3) { // At least host, database, username
                    return $config;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Ensure directory exists
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
    
    /**
     * Print next steps
     */
    private function printNextSteps(): void
    {
        echo "=== NEXT STEPS ===\n\n";
        echo "1. Update database configuration in config/auth.php\n";
        echo "2. Run the setup to create database tables:\n";
        echo "   php -r \"require \'vendor/autoload.php\'; use Ksfraser\\Auth\\Auth; Auth::quickSetup([...]);\"\n\n";
        echo "3. Create your first admin user:\n";
        echo "   See examples/auth/integration_example.php\n\n";
        echo "4. Update your existing authentication code:\n";
        echo "   - Include web_ui/AuthLibraryWrapper.php for compatibility\n";
        echo "   - Or migrate to use the new Auth class directly\n\n";
        echo "5. Test the authentication system:\n";
        echo "   - Visit examples/auth/login.php\n";
        echo "   - Test with the created admin user\n\n";
        echo "6. For production deployment:\n";
        echo "   - Copy config/auth.local.php.example to config/auth.local.php\n";
        echo "   - Update with production database credentials\n";
        echo "   - Generate secure JWT secret key\n\n";
        echo "The authentication library is now ready for use!\n";
    }
}

// Run migration if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $migration = new AuthMigrationScript();
        $migration->migrate();
    } catch (Exception $e) {
        echo "Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
