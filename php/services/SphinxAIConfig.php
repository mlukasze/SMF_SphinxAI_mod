<?php

/**
 * SphinxAI Configuration Manager
 * Centralized configuration management for all SphinxAI settings
 * 
 * @package SphinxAI
 * @version 1.0.0
 * @author SMF Sphinx AI Search Plugin
 */

if (!defined('SMF')) {
    die('No direct access...');
}

class SphinxAIConfig
{
    /** @var array Default configuration values */
    private static $defaults = [
        // Core settings
        'enabled' => true,
        'model_path' => '',
        'max_results' => 10,
        'summary_length' => 200,
        'auto_index' => true,
        'timeout' => 30,
        
        // Cache settings
        'cache_enabled' => true,
        'cache_ttl' => 3600,
        'cache_type' => 'redis',
        
        // Redis settings
        'redis_host' => '127.0.0.1',
        'redis_port' => 6379,
        'redis_password' => null,
        'redis_db' => 0,
        'redis_prefix' => 'sphinxai:',
        
        // Rate limiting settings
        'rate_limit_enabled' => true,
        'rate_limit_search_requests' => 30,
        'rate_limit_search_window' => 60,
        'rate_limit_search_block' => 300,
        'rate_limit_suggestions_requests' => 60,
        'rate_limit_suggestions_window' => 60,
        'rate_limit_suggestions_block' => 300,
        'rate_limit_admin_requests' => 100,
        'rate_limit_admin_window' => 60,
        'rate_limit_admin_block' => 600,
        
        // Security settings
        'csrf_protection' => true,
        'input_validation' => true,
        'output_escaping' => true,
        'secure_temp_files' => true,
        'allowed_model_paths' => [
            '/var/lib/sphinx-ai/models',
            '/opt/sphinx-ai/models',
            'SphinxAI/models'
        ],
        
        // Performance settings
        'enable_query_optimization' => true,
        'enable_composite_indexes' => true,
        'log_slow_queries' => true,
        'slow_query_threshold' => 1.0,
        
        // Logging settings
        'log_level' => 'INFO',
        'log_api_requests' => true,
        'log_search_stats' => true,
        'log_errors' => true,
        
        // Search settings
        'search_type' => 'hybrid',
        'use_ai_summary' => true,
        'use_genai' => true,
        'polish_language_support' => true,
        
        // Monitoring settings
        'health_check_enabled' => true,
        'metrics_collection' => true,
        'performance_monitoring' => true
    ];
    
    /** @var array Configuration cache */
    private static $config = null;
    
    /** @var string Configuration file path */
    private static $configFile = null;
    
    /**
     * Initialize configuration system
     */
    public static function init(): void
    {
        self::$configFile = dirname(__DIR__, 2) . '/config/sphinxai.ini';
        self::loadConfig();
    }
    
    /**
     * Get configuration value
     * 
     * @param string $key Configuration key (supports dot notation)
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public static function get(string $key, $default = null)
    {
        if (self::$config === null) {
            self::loadConfig();
        }
        
        // Support dot notation for nested keys
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $keyPart) {
            if (is_array($value) && array_key_exists($keyPart, $value)) {
                $value = $value[$keyPart];
            } else {
                return $default !== null ? $default : (self::$defaults[$key] ?? null);
            }
        }
        
        return $value;
    }
    
    /**
     * Set configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return bool Success status
     */
    public static function set(string $key, $value): bool
    {
        if (self::$config === null) {
            self::loadConfig();
        }
        
        // Validate the configuration value
        if (!self::validateConfigValue($key, $value)) {
            return false;
        }
        
        // Support dot notation for nested keys
        $keys = explode('.', $key);
        $config = &self::$config;
        
        for ($i = 0; $i < count($keys) - 1; $i++) {
            $keyPart = $keys[$i];
            if (!isset($config[$keyPart]) || !is_array($config[$keyPart])) {
                $config[$keyPart] = [];
            }
            $config = &$config[$keyPart];
        }
        
        $config[end($keys)] = $value;
        
        return self::saveConfig();
    }
    
    /**
     * Get multiple configuration values
     * 
     * @param array $keys Array of configuration keys
     * @return array Configuration values
     */
    public static function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = self::get($key);
        }
        return $result;
    }
    
    /**
     * Set multiple configuration values
     * 
     * @param array $values Key-value pairs to set
     * @return bool Success status
     */
    public static function setMultiple(array $values): bool
    {
        $success = true;
        
        foreach ($values as $key => $value) {
            if (!self::set($key, $value)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get all configuration values
     * 
     * @return array All configuration values
     */
    public static function getAll(): array
    {
        if (self::$config === null) {
            self::loadConfig();
        }
        
        return self::$config;
    }
    
    /**
     * Reset configuration to defaults
     * 
     * @return bool Success status
     */
    public static function resetToDefaults(): bool
    {
        self::$config = self::$defaults;
        return self::saveConfig();
    }
    
    /**
     * Load configuration from file and SMF settings
     */
    private static function loadConfig(): void
    {
        global $modSettings;
        
        // Start with defaults
        self::$config = self::$defaults;
        
        // Load from configuration file if it exists
        if (self::$configFile && file_exists(self::$configFile)) {
            $fileConfig = parse_ini_file(self::$configFile, true);
            if ($fileConfig !== false) {
                self::$config = array_merge(self::$config, $fileConfig);
            }
        }
        
        // Override with SMF settings (with prefix)
        $smfPrefix = 'sphinx_ai_';
        foreach ($modSettings as $key => $value) {
            if (strpos($key, $smfPrefix) === 0) {
                $configKey = substr($key, strlen($smfPrefix));
                self::$config[$configKey] = $value;
            }
        }
        
        // Apply environment variable overrides
        self::applyEnvironmentOverrides();
    }
    
    /**
     * Save configuration to file
     * 
     * @return bool Success status
     */
    private static function saveConfig(): bool
    {
        if (!self::$configFile) {
            return false;
        }
        
        // Ensure config directory exists
        $configDir = dirname(self::$configFile);
        if (!is_dir($configDir)) {
            if (!mkdir($configDir, 0755, true)) {
                return false;
            }
        }
        
        // Convert array to INI format
        $iniContent = self::arrayToIni(self::$config);
        
        // Write to file with atomic operation
        $tempFile = self::$configFile . '.tmp';
        if (file_put_contents($tempFile, $iniContent, LOCK_EX) === false) {
            return false;
        }
        
        if (!rename($tempFile, self::$configFile)) {
            unlink($tempFile);
            return false;
        }
        
        return true;
    }
    
    /**
     * Apply environment variable overrides
     */
    private static function applyEnvironmentOverrides(): void
    {
        $envPrefix = 'SPHINX_AI_';
        
        foreach ($_ENV as $key => $value) {
            if (strpos($key, $envPrefix) === 0) {
                $configKey = strtolower(substr($key, strlen($envPrefix)));
                $configKey = str_replace('_', '.', $configKey);
                
                // Convert string values to appropriate types
                $value = self::convertEnvValue($value);
                self::$config[$configKey] = $value;
            }
        }
    }
    
    /**
     * Convert environment variable value to appropriate type
     * 
     * @param string $value Environment variable value
     * @return mixed Converted value
     */
    private static function convertEnvValue(string $value)
    {
        // Boolean values
        if (in_array(strtolower($value), ['true', 'false'])) {
            return strtolower($value) === 'true';
        }
        
        // Numeric values
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        
        // JSON arrays/objects
        if (substr($value, 0, 1) === '[' || substr($value, 0, 1) === '{') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        return $value;
    }
    
    /**
     * Validate configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Value to validate
     * @return bool Validation result
     */
    private static function validateConfigValue(string $key, $value): bool
    {
        // Define validation rules
        $rules = [
            'redis_port' => function($v) { return is_int($v) && $v > 0 && $v <= 65535; },
            'redis_db' => function($v) { return is_int($v) && $v >= 0 && $v <= 15; },
            'max_results' => function($v) { return is_int($v) && $v > 0 && $v <= 1000; },
            'timeout' => function($v) { return is_int($v) && $v > 0 && $v <= 300; },
            'cache_ttl' => function($v) { return is_int($v) && $v > 0; },
            'log_level' => function($v) { return in_array($v, ['DEBUG', 'INFO', 'WARNING', 'ERROR']); },
            'search_type' => function($v) { return in_array($v, ['hybrid', 'semantic', 'keyword']); }
        ];
        
        if (isset($rules[$key])) {
            return $rules[$key]($value);
        }
        
        return true; // No specific validation rule
    }
    
    /**
     * Convert array to INI format
     * 
     * @param array $array Array to convert
     * @return string INI content
     */
    private static function arrayToIni(array $array): string
    {
        $content = "; SphinxAI Configuration\n";
        $content .= "; Generated on " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $content .= "[$key]\n";
                foreach ($value as $subKey => $subValue) {
                    $content .= sprintf("%s = %s\n", $subKey, self::formatIniValue($subValue));
                }
                $content .= "\n";
            } else {
                $content .= sprintf("%s = %s\n", $key, self::formatIniValue($value));
            }
        }
        
        return $content;
    }
    
    /**
     * Format value for INI file
     * 
     * @param mixed $value Value to format
     * @return string Formatted value
     */
    private static function formatIniValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_string($value) && (strpos($value, ' ') !== false || strpos($value, ';') !== false)) {
            return '"' . addslashes($value) . '"';
        }
        
        return (string)$value;
    }
    
    /**
     * Get configuration schema for admin interface
     * 
     * @return array Configuration schema
     */
    public static function getSchema(): array
    {
        return [
            'core' => [
                'title' => 'Core Settings',
                'fields' => [
                    'enabled' => ['type' => 'boolean', 'title' => 'Enable SphinxAI', 'default' => true],
                    'model_path' => ['type' => 'text', 'title' => 'Model Path', 'default' => ''],
                    'max_results' => ['type' => 'int', 'title' => 'Maximum Results', 'min' => 1, 'max' => 1000],
                    'timeout' => ['type' => 'int', 'title' => 'Timeout (seconds)', 'min' => 1, 'max' => 300]
                ]
            ],
            'cache' => [
                'title' => 'Cache Settings',
                'fields' => [
                    'cache_enabled' => ['type' => 'boolean', 'title' => 'Enable Caching'],
                    'cache_ttl' => ['type' => 'int', 'title' => 'Cache TTL (seconds)', 'min' => 60],
                    'redis_host' => ['type' => 'text', 'title' => 'Redis Host'],
                    'redis_port' => ['type' => 'int', 'title' => 'Redis Port', 'min' => 1, 'max' => 65535],
                    'redis_password' => ['type' => 'password', 'title' => 'Redis Password']
                ]
            ],
            'security' => [
                'title' => 'Security Settings', 
                'fields' => [
                    'rate_limit_enabled' => ['type' => 'boolean', 'title' => 'Enable Rate Limiting'],
                    'csrf_protection' => ['type' => 'boolean', 'title' => 'Enable CSRF Protection'],
                    'input_validation' => ['type' => 'boolean', 'title' => 'Enable Input Validation']
                ]
            ]
        ];
    }
}

// Initialize configuration system
SphinxAIConfig::init();
