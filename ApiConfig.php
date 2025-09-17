<?php

/**
 * API Configuration Manager
 * 
 * Centralized configuration loader for external API services
 * Supports YAML and INI configuration files
 * Separate from database configuration for better security
 */
class ApiConfig
{
    private static $config = null;
    private static $configFile = null;
    
    /**
     * Load API configuration from file
     */
    public static function load($configFile = null)
    {
        if (self::$config !== null && $configFile === self::$configFile) {
            return self::$config;
        }
        
        // Default config file locations
        if ($configFile === null) {
            $possibleFiles = [
                __DIR__ . '/api_config.yml',
                __DIR__ . '/api_config.yaml',
                __DIR__ . '/api_config.ini',
                __DIR__ . '/../api_config.yml',
                __DIR__ . '/../api_config.yaml',
                __DIR__ . '/../api_config.ini'
            ];
            
            foreach ($possibleFiles as $file) {
                if (file_exists($file)) {
                    $configFile = $file;
                    break;
                }
            }
        }
        
        if (!$configFile || !file_exists($configFile)) {
            // Return empty config if no file found (APIs will be unavailable)
            error_log('API configuration file not found. API features will be disabled.');
            self::$config = self::getDefaultConfig();
            return self::$config;
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
                throw new Exception('Unsupported API configuration file format. Use .yml, .yaml, or .ini');
        }
        
        self::$configFile = $configFile;
        return self::$config;
    }
    
    /**
     * Get stock API configuration
     */
    public static function getStockApiConfig($provider = null)
    {
        $config = self::load();
        $stockApis = $config['stock_apis'] ?? [];
        
        if ($provider) {
            return $stockApis[$provider] ?? [];
        }
        
        return $stockApis;
    }
    
    /**
     * Get AI/LLM API configuration
     */
    public static function getAiApiConfig($provider = null)
    {
        $config = self::load();
        $aiApis = $config['ai_apis'] ?? [];
        
        if ($provider) {
            return $aiApis[$provider] ?? [];
        }
        
        return $aiApis;
    }
    
    /**
     * Get financial data API configuration
     */
    public static function getFinancialApiConfig($provider = null)
    {
        $config = self::load();
        $financialApis = $config['financial_apis'] ?? [];
        
        if ($provider) {
            return $financialApis[$provider] ?? [];
        }
        
        return $financialApis;
    }
    
    /**
     * Get notification API configuration
     */
    public static function getNotificationConfig($provider = null)
    {
        $config = self::load();
        $notifications = $config['notifications'] ?? [];
        
        if ($provider) {
            return $notifications[$provider] ?? [];
        }
        
        return $notifications;
    }
    
    /**
     * Get finance package configuration
     */
    public static function getFinanceConfig()
    {
        $config = self::load();
        return $config['finance'] ?? self::getDefaultFinanceConfig();
    }
    
    /**
     * Check if a specific API is configured and available
     */
    public static function isApiAvailable(string $category, string $provider): bool
    {
        $config = self::load();
        
        $apiConfig = $config[$category][$provider] ?? [];
        
        // Check if API key exists for providers that require it
        $requiresApiKey = ['alphavantage', 'openai', 'claude', 'twelve_data', 'fred'];
        
        if (in_array($provider, $requiresApiKey)) {
            return !empty($apiConfig['api_key']);
        }
        
        // For APIs that don't require keys (like yahoo_finance)
        return isset($apiConfig) && ($apiConfig['enabled'] ?? true);
    }
    
    /**
     * Get all available APIs by category
     */
    public static function getAvailableApis(): array
    {
        $config = self::load();
        $available = [];
        
        $categories = ['stock_apis', 'ai_apis', 'financial_apis'];
        
        foreach ($categories as $category) {
            $available[$category] = [];
            $apis = $config[$category] ?? [];
            
            foreach ($apis as $provider => $apiConfig) {
                if (self::isApiAvailable($category, $provider)) {
                    $available[$category][] = $provider;
                }
            }
        }
        
        return $available;
    }
    
    /**
     * Load YAML configuration (reuse from DatabaseConfig)
     */
    private static function loadYaml($file)
    {
        if (function_exists('yaml_parse_file')) {
            return yaml_parse_file($file);
        } else {
            return self::parseSimpleYaml($file);
        }
    }
    
    /**
     * Simple YAML parser (fallback)
     */
    private static function parseSimpleYaml($file)
    {
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        $config = [];
        $currentSection = &$config;
        $stack = [&$config];
        $lastIndent = 0;
        
        foreach ($lines as $line) {
            $originalLine = $line;
            $line = rtrim($line);
            
            // Skip comments and empty lines
            if (empty(trim($line)) || trim($line)[0] === '#') {
                continue;
            }
            
            // Calculate indentation
            $indent = strlen($line) - strlen(ltrim($line));
            $line = ltrim($line);
            
            // Handle indentation changes
            if ($indent < $lastIndent) {
                // Go back up the stack
                $levels = ($lastIndent - $indent) / 2;
                for ($i = 0; $i < $levels; $i++) {
                    array_pop($stack);
                }
                $currentSection = &$stack[count($stack) - 1];
            }
            
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (empty($value) || $value === '') {
                    // This is a section
                    $currentSection[$key] = [];
                    $stack[] = &$currentSection[$key];
                    $currentSection = &$currentSection[$key];
                } else {
                    // Remove quotes if present
                    if (($value[0] === '"' && $value[-1] === '"') || 
                        ($value[0] === "'" && $value[-1] === "'")) {
                        $value = substr($value, 1, -1);
                    }
                    // This is a key-value pair
                    $currentSection[$key] = $value;
                }
            }
            
            $lastIndent = $indent;
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
     * Get default configuration when no file is found
     */
    private static function getDefaultConfig(): array
    {
        return [
            'stock_apis' => [
                'yahoo_finance' => ['enabled' => true, 'timeout' => 30]
            ],
            'ai_apis' => [],
            'financial_apis' => [],
            'notifications' => [],
            'finance' => self::getDefaultFinanceConfig()
        ];
    }
    
    /**
     * Get default finance configuration
     */
    private static function getDefaultFinanceConfig(): array
    {
        return [
            'rate_limiting' => [
                'delay_between_requests' => 500000,
                'max_concurrent_requests' => 3
            ],
            'general' => [
                'max_retries' => 3,
                'timeout' => 30,
                'bulk_update_limit' => 100
            ],
            'features' => [
                'llm_analysis' => false,
                'historical_data' => true,
                'bulk_updates' => true,
                'market_overview' => true
            ]
        ];
    }
}
