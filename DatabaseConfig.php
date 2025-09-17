<?php

/**
 * Database Configuration Manager
 * 
 * Centralized configuration loader for both micro-cap and legacy systems
 * Supports YAML and INI configuration files
 */
class DatabaseConfig
{
    private static $config = null;
    private static $configFile = null;
    
    /**
     * Load configuration from file
     */
    public static function load($configFile = null)
    {
        if (self::$config !== null && $configFile === self::$configFile) {
            return self::$config;
        }
        
        // Default config file locations
        if ($configFile === null) {
            $possibleFiles = [
                __DIR__ . '/db_config.yml',
                __DIR__ . '/db_config.yaml',
                __DIR__ . '/db_config.ini',
                __DIR__ . '/../db_config.yml',
                __DIR__ . '/../db_config.yaml',
                __DIR__ . '/../db_config.ini'
            ];
            
            foreach ($possibleFiles as $file) {
                if (file_exists($file)) {
                    $configFile = $file;
                    break;
                }
            }
        }
        
        if (!$configFile || !file_exists($configFile)) {
            throw new Exception('Database configuration file not found. Please create db_config.yml from db_config.example.yml');
        }
        
        $extension = pathinfo($configFile, PATHINFO_EXTENSION);
        
        switch (strtolower($extension)) {
            case 'yml':
            case 'yaml':
                self::$config = self::loadYaml($configFile);
                break;
            case 'ini':
                self::$config = self::loadIni($configFile);
                break;
            default:
                throw new Exception('Unsupported configuration file format. Use .yml, .yaml, or .ini');
        }
        
        self::$configFile = $configFile;
        return self::$config;
    }
    
    /**
     * Load YAML configuration
     */
    private static function loadYaml($file)
    {
        if (function_exists('yaml_parse_file')) {
            return yaml_parse_file($file);
        } else {
            // Simple YAML parser for basic configs
            return self::parseSimpleYaml($file);
        }
    }
    
    /**
     * Simple YAML parser (fallback if yaml extension not available)
     */
    private static function parseSimpleYaml($file)
    {
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        $config = [];
        $currentSection = &$config;
        $stack = [&$config];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            // Handle indentation
            $indent = strlen($line) - strlen(ltrim($line));
            $line = ltrim($line);
            
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (empty($value)) {
                    // This is a section
                    $currentSection[$key] = [];
                    $currentSection = &$currentSection[$key];
                } else {
                    // This is a key-value pair
                    $currentSection[$key] = $value;
                }
            }
        }
        
        return $config;
    }
    
    /**
     * Load INI configuration
     */
    private static function loadIni($file)
    {
        return parse_ini_file($file, true);
    }
    
    /**
     * Get database configuration for micro-cap system
     */
    public static function getMicroCapConfig()
    {
        $config = self::load();
        
        return [
            'host' => $config['database']['host'] ?? 'localhost',
            'port' => $config['database']['port'] ?? 3306,
            'dbname' => $config['database']['micro_cap']['database'] ?? 'micro_cap_trading',
            'username' => $config['database']['username'] ?? '',
            'password' => $config['database']['password'] ?? '',
            'charset' => $config['database']['charset'] ?? 'utf8mb4'
        ];
    }
    
    /**
     * Get database configuration for legacy system
     */
    public static function getLegacyConfig()
    {
        $config = self::load();
        
        return [
            'host' => $config['database']['host'] ?? 'localhost',
            'port' => $config['database']['port'] ?? 3306,
            'database' => $config['database']['legacy']['database'] ?? 'stock_market_2',
            'dbname' => $config['database']['legacy']['database'] ?? 'stock_market_2', // Alias for compatibility
            'username' => $config['database']['username'] ?? '',
            'password' => $config['database']['password'] ?? '',
            'charset' => $config['database']['charset'] ?? 'utf8mb4'
        ];
    }
    
    /**
     * Get API configuration
     */
    public static function getApiConfig($provider = null)
    {
        $config = self::load();
        $apiConfig = $config['apis'] ?? [];
        
        if ($provider) {
            return $apiConfig[$provider] ?? [];
        }
        
        return $apiConfig;
    }
    
    /**
     * Get Finance package configuration
     */
    public static function getFinanceConfig()
    {
        // Load API configuration separately
        require_once __DIR__ . '/ApiConfig.php';
        
        $dbConfig = self::getLegacyConfig(); // Use legacy DB for stock market data
        $apiConfig = ApiConfig::load();
        
        // Get stock API configs
        $alphavantageConfig = ApiConfig::getStockApiConfig('alphavantage');
        $openaiConfig = ApiConfig::getAiApiConfig('openai');
        $financeSettings = ApiConfig::getFinanceConfig();
        
        return [
            'database' => [
                'dsn' => sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $dbConfig['host'],
                    $dbConfig['port'] ?? 3306,
                    $dbConfig['database'],
                    $dbConfig['charset']
                ),
                'username' => $dbConfig['username'],
                'password' => $dbConfig['password']
            ],
            'alphavantage' => [
                'api_key' => $alphavantageConfig['api_key'] ?? '',
                'base_url' => $alphavantageConfig['base_url'] ?? 'https://www.alphavantage.co/query',
                'timeout' => $alphavantageConfig['timeout'] ?? 30
            ],
            'openai' => [
                'api_key' => $openaiConfig['api_key'] ?? '',
                'base_url' => $openaiConfig['base_url'] ?? 'https://api.openai.com/v1/chat/completions',
                'model' => $openaiConfig['model'] ?? 'gpt-4',
                'timeout' => $openaiConfig['timeout'] ?? 60
            ],
            'rate_limiting' => $financeSettings['rate_limiting'] ?? [
                'delay_between_requests' => 500000,
                'max_concurrent_requests' => 3
            ],
            'general' => $financeSettings['general'] ?? [
                'max_retries' => 3,
                'timeout' => 30,
                'bulk_update_limit' => 100
            ]
        ];
    }
    
    /**
     * Get application configuration
     */
    public static function getAppConfig()
    {
        $config = self::load();
        
        return [
            'debug' => $config['app']['debug'] ?? false,
            'timezone' => $config['app']['timezone'] ?? 'UTC',
            'cache_enabled' => $config['app']['cache_enabled'] ?? true
        ];
    }
    
    /**
     * Get logging configuration
     */
    public static function getLoggingConfig()
    {
        $config = self::load();
        
        return [
            'level' => $config['logging']['level'] ?? 'INFO',
            'file' => $config['logging']['file'] ?? 'logs/app.log'
        ];
    }
    
    /**
     * Create PDO connection for micro-cap system
     */
    public static function createMicroCapConnection()
    {
        $config = self::getMicroCapConfig();
        
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, $config['username'], $config['password'], $options);
    }
    
    /**
     * Create PDO connection for legacy system
     */
    public static function createLegacyConnection()
    {
        $config = self::getLegacyConfig();
        
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, $config['username'], $config['password'], $options);
    }
}
