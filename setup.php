<?php

/**
 * Quick Setup Script for Ksfraser Authentication Library
 * 
 * This script helps set up the authentication system with minimal configuration
 */

require_once __DIR__ . '/vendor/autoload.php';

use Ksfraser\Auth\Auth;
use Ksfraser\Auth\AuthConfig;

class QuickSetup
{
    private $config = [];
    
    public function run(): void
    {
        echo "=== Ksfraser Authentication Library Setup ===\n\n";
        
        $this->step1_welcome();
        $this->step2_gatherConfig();
        $this->step3_setupDatabase();
        $this->step4_createAdmin();
        $this->step5_testSystem();
        $this->step6_complete();
    }
    
    private function step1_welcome(): void
    {
        echo "This script will help you set up the authentication library.\n";
        echo "You'll need database connection details and an admin user.\n\n";
    }
    
    private function step2_gatherConfig(): void
    {
        echo "Step 1: Database Configuration\n";
        echo "------------------------------\n";
        
        $this->config['database'] = [
            'host' => $this->prompt('Database host', 'localhost'),
            'port' => (int)$this->prompt('Database port', '3306'),
            'database' => $this->prompt('Database name', 'auth_db'),
            'username' => $this->prompt('Database username', 'root'),
            'password' => $this->prompt('Database password', '', true),
            'charset' => 'utf8mb4'
        ];
        
        echo "\n";
    }
    
    private function step3_setupDatabase(): void
    {
        echo "Step 2: Setting up database...\n";
        echo "------------------------------\n";
        
        try {
            $auth = Auth::getInstance($this->config);
            
            // Test connection
            if (!$auth->database()->testConnection()) {
                throw new Exception('Could not connect to database');
            }
            echo "✓ Database connection successful\n";
            
            // Create database if needed
            $auth->database()->createDatabase();
            echo "✓ Database created/verified\n";
            
            // Setup schema
            $auth->database()->setupSchema();
            echo "✓ Database tables created\n";
            
            // Create default roles
            $auth->database()->createDefaultRoles();
            echo "✓ Default roles created\n";
            
        } catch (Exception $e) {
            echo "✗ Database setup failed: " . $e->getMessage() . "\n";
            echo "Please check your database configuration and try again.\n";
            exit(1);
        }
        
        echo "\n";
    }
    
    private function step4_createAdmin(): void
    {
        echo "Step 3: Create admin user\n";
        echo "-------------------------\n";
        
        $username = $this->prompt('Admin username', 'admin');
        $email = $this->prompt('Admin email', 'admin@localhost');
        
        // Generate secure password or allow custom
        $useGenerated = $this->prompt('Generate secure password? (y/n)', 'y');
        
        if (strtolower($useGenerated) === 'y') {
            $password = $this->generatePassword();
            echo "Generated password: {$password}\n";
        } else {
            $password = $this->prompt('Admin password', '', true);
        }
        
        try {
            $auth = Auth::getInstance($this->config);
            $adminData = $auth->createAdmin($username, $email, $password);
            
            echo "✓ Admin user created successfully\n";
            echo "  Username: {$adminData['username']}\n";
            echo "  Email: {$adminData['email']}\n";
            echo "  Password: {$adminData['password']}\n";
            
            $this->config['admin'] = $adminData;
            
        } catch (Exception $e) {
            echo "✗ Failed to create admin user: " . $e->getMessage() . "\n";
            exit(1);
        }
        
        echo "\n";
    }
    
    private function step5_testSystem(): void
    {
        echo "Step 4: Testing system...\n";
        echo "-------------------------\n";
        
        try {
            $auth = Auth::getInstance($this->config);
            $tests = $auth->test();
            
            foreach ($tests as $test => $result) {
                $status = $result ? '✓' : '✗';
                echo "{$status} {$test}\n";
            }
            
            if (isset($tests['error'])) {
                echo "Error: " . $tests['error'] . "\n";
            }
            
        } catch (Exception $e) {
            echo "✗ System test failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function step6_complete(): void
    {
        echo "Step 5: Saving configuration...\n";
        echo "-------------------------------\n";
        
        // Save configuration
        $configDir = __DIR__ . '/config';
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }
        
        $configFile = $configDir . '/auth.php';
        $configContent = "<?php\n\n// Ksfraser Auth Configuration\n// Generated by setup script\n\nreturn " . var_export($this->config, true) . ";\n";
        
        file_put_contents($configFile, $configContent);
        echo "✓ Configuration saved to config/auth.php\n";
        
        // Create example files
        $exampleDir = __DIR__ . '/examples/auth';
        if (!is_dir($exampleDir)) {
            mkdir($exampleDir, 0755, true);
            
            $auth = Auth::getInstance($this->config);
            $auth->createExamplePages($exampleDir);
            echo "✓ Example pages created in examples/auth/\n";
        }
        
        echo "\n=== Setup Complete! ===\n\n";
        
        echo "Your authentication system is now ready to use.\n\n";
        
        echo "Admin Login Details:\n";
        echo "  Username: " . $this->config['admin']['username'] . "\n";
        echo "  Password: " . $this->config['admin']['password'] . "\n\n";
        
        echo "Next Steps:\n";
        echo "1. Test the login at: examples/auth/login.php\n";
        echo "2. View the dashboard at: examples/auth/dashboard.php\n";
        echo "3. Check out examples/auth/integration_example.php for usage examples\n";
        echo "4. See src/Ksfraser/Auth/README.md for complete documentation\n\n";
        
        echo "Security Notes:\n";
        echo "- Keep your database credentials secure\n";
        echo "- Change the admin password after first login\n";
        echo "- Consider setting up HTTPS for production\n";
        echo "- Review the security configuration in config/auth.php\n\n";
    }
    
    private function prompt(string $question, string $default = '', bool $hidden = false): string
    {
        $defaultText = $default ? " [{$default}]" : "";
        echo "{$question}{$defaultText}: ";
        
        if ($hidden) {
            // Hide password input (Linux/Mac)
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                system('stty -echo');
                $input = trim(fgets(STDIN));
                system('stty echo');
                echo "\n";
            } else {
                // For Windows, just use regular input
                $input = trim(fgets(STDIN));
            }
        } else {
            $input = trim(fgets(STDIN));
        }
        
        return $input ?: $default;
    }
    
    private function generatePassword(int $length = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
}

// Check if running from command line
if (php_sapi_name() === 'cli') {
    $setup = new QuickSetup();
    $setup->run();
} else {
    echo "This script must be run from the command line.\n";
    echo "Usage: php setup.php\n";
}
