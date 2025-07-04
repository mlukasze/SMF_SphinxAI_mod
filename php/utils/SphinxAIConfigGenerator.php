<?php
/**
 * Sphinx AI Configuration Generator
 * 
 * Generates SphinxAI/config.ini from SMF database settings and template.
 * This ensures database credentials are kept secure and up-to-date.
 * 
 * @package SphinxAISearch
 * @subpackage Utils
 */

declare(strict_types=1);

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Configuration generator for Sphinx AI Search
 * 
 * Extracts database settings from SMF and generates config.ini
 */
class SphinxAIConfigGenerator
{
	/**
	 * Generate config.ini from template and SMF settings
	 * 
	 * @return bool Success status
	 */
	public static function generateConfigFile(): bool
	{
		try {
			$plugin_dir = dirname(dirname(__DIR__));
			$template_path = $plugin_dir . '/SphinxAI/config.ini.template';
			$config_path = $plugin_dir . '/SphinxAI/config.ini';
			
			// Check if template exists
			if (!file_exists($template_path)) {
				log_error('SphinxAI: Configuration template not found: ' . $template_path);
				return false;
			}
			
			// Read template
			$template_content = file_get_contents($template_path);
			if ($template_content === false) {
				log_error('SphinxAI: Failed to read configuration template');
				return false;
			}
			
			// Get database settings from SMF
			$db_settings = self::extractDatabaseSettings();
			if (!$db_settings) {
				log_error('SphinxAI: Failed to extract database settings');
				return false;
			}
			
			// Replace database placeholders
			$config_content = self::replaceDatabaseSettings($template_content, $db_settings);
			
			// Add additional SMF-specific settings
			$config_content = self::addSmfSettings($config_content);
			
			// Write config file with proper permissions
			if (file_put_contents($config_path, $config_content, LOCK_EX) === false) {
				log_error('SphinxAI: Failed to write configuration file');
				return false;
			}
			
			// Set restrictive permissions (readable only by owner)
			chmod($config_path, 0600);
			
			log_error('SphinxAI: Configuration file generated successfully', 'general');
			return true;
			
		} catch (Exception $e) {
			log_error('SphinxAI: Configuration generation failed: ' . $e->getMessage());
			return false;
		}
	}
	
	/**
	 * Extract database settings from SMF
	 * 
	 * @return array|false Database settings or false on failure
	 */
	private static function extractDatabaseSettings()
	{
		global $db_server, $db_name, $db_user, $db_passwd, $db_prefix, $db_port, $db_character_set;
		
		// Validate required SMF database settings
		if (empty($db_server) || empty($db_name) || empty($db_user)) {
			return false;
		}
		
		return array(
			'host' => $db_server,
			'port' => !empty($db_port) ? (int)$db_port : 3306,
			'database' => $db_name,
			'user' => $db_user,
			'password' => $db_passwd ?? '',
			'table_prefix' => $db_prefix ?? 'smf_',
			'charset' => $db_character_set ?? 'utf8mb4',
		);
	}
	
	/**
	 * Replace database settings in template
	 * 
	 * @param string $template_content Template content
	 * @param array $db_settings Database settings
	 * @return string Updated content
	 */
	private static function replaceDatabaseSettings(string $template_content, array $db_settings): string
	{
		$replacements = array(
			'host = localhost' => 'host = ' . $db_settings['host'],
			'port = 3306' => 'port = ' . $db_settings['port'],
			'database = smf_database' => 'database = ' . $db_settings['database'],
			'user = smf_user' => 'user = ' . $db_settings['user'],
			'password = smf_password' => 'password = ' . $db_settings['password'],
			'table_prefix = smf_' => 'table_prefix = ' . $db_settings['table_prefix'],
			'charset = utf8mb4' => 'charset = ' . $db_settings['charset'],
		);
		
		return str_replace(array_keys($replacements), array_values($replacements), $template_content);
	}
	
	/**
	 * Add SMF-specific settings to configuration
	 * 
	 * @param string $config_content Current config content
	 * @return string Updated content
	 */
	private static function addSmfSettings(string $config_content): string
	{
		global $boarddir, $modSettings;
		
		// Add SMF board directory path for relative path resolution
		$smf_settings = "\n# SMF Integration Settings\n";
		$smf_settings .= "smf_board_dir = " . $boarddir . "\n";
		
		// Extract and configure cache settings from SMF
		$cache_config = self::extractCacheSettings();
		if (!empty($cache_config)) {
			// Update cache section in config content
			$config_content = self::updateCacheSection($config_content, $cache_config);
		}
		
		// Append SMF settings to the end
		return $config_content . $smf_settings;
	}
	
	/**
	 * Extract cache settings from SMF configuration
	 * 
	 * @return array Cache configuration
	 */
	private static function extractCacheSettings(): array
	{
		global $modSettings;
		
		$cache_config = array(
			'enabled' => false,
			'type' => 'smf',  // Always use SMF cache API
			'host' => 'localhost',
			'port' => 6379,
			'database' => 0,
			'prefix' => 'smf_',
		);
		
		// Check if caching is enabled in SMF
		if (!empty($modSettings['cache_enable'])) {
			$cache_config['enabled'] = true;
			
			// Determine underlying cache type based on SMF settings for informational purposes
			if (!empty($modSettings['cache_memcached'])) {
				// SMF is using Memcached/Redis behind the scenes
				$cache_config['underlying_type'] = 'redis';
				
				// Parse connection string (format: host:port or just host)
				$connection = $modSettings['cache_memcached'];
				if (strpos($connection, ':') !== false) {
					list($host, $port) = explode(':', $connection, 2);
					$cache_config['host'] = trim($host);
					$cache_config['port'] = (int)trim($port);
				} else {
					$cache_config['host'] = trim($connection);
				}
			} elseif (!empty($modSettings['cache_filebased'])) {
				// SMF is using file-based caching
				$cache_config['underlying_type'] = 'file';
			} else {
				// Default underlying type
				$cache_config['underlying_type'] = 'file';
			}
			
			// Set cache prefix based on SMF table prefix
			global $db_prefix;
			if (!empty($db_prefix)) {
				$cache_config['prefix'] = $db_prefix . 'sphinxai_';
			}
		}
		
		return $cache_config;
	}
	
	/**
	 * Update cache section in configuration content
	 * 
	 * @param string $config_content Configuration content
	 * @param array $cache_config Cache configuration
	 * @return string Updated configuration content
	 */
	private static function updateCacheSection(string $config_content, array $cache_config): string
	{
		// Replace cache configuration values
		$replacements = array(
			'enabled = true' => 'enabled = ' . ($cache_config['enabled'] ? 'true' : 'false'),
			'type = redis' => 'type = ' . $cache_config['type'],
			'host = localhost' => 'host = ' . $cache_config['host'],
			'port = 6379' => 'port = ' . $cache_config['port'],
			'database = 0' => 'database = ' . $cache_config['database'],
		);
		
		// Apply cache section replacements
		foreach ($replacements as $search => $replace) {
			// Only replace within [cache] section
			if (strpos($config_content, '[cache]') !== false) {
				$cache_section_start = strpos($config_content, '[cache]');
				$next_section_start = strpos($config_content, '[', $cache_section_start + 1);
				
				if ($next_section_start === false) {
					$next_section_start = strlen($config_content);
				}
				
				$cache_section = substr($config_content, $cache_section_start, $next_section_start - $cache_section_start);
				$updated_cache_section = str_replace($search, $replace, $cache_section);
				
				$config_content = substr_replace($config_content, $updated_cache_section, $cache_section_start, $next_section_start - $cache_section_start);
			}
		}
		
		// Add cache type and prefix
		$additional_cache_settings = "\n# Using SMF cache API (underlying type: " . ($cache_config['underlying_type'] ?? 'unknown') . ")\n";
		$additional_cache_settings .= "prefix = " . $cache_config['prefix'] . "\n";
		
		// Insert additional settings after [cache] section header
		if (strpos($config_content, '[cache]') !== false) {
			$cache_header_pos = strpos($config_content, '[cache]');
			$cache_header_end = strpos($config_content, "\n", $cache_header_pos);
			
			if ($cache_header_end !== false) {
				$config_content = substr_replace($config_content, $additional_cache_settings, $cache_header_end, 0);
			}
		}
		
		return $config_content;
	}
	
	/**
	 * Check if config.ini file exists and is up-to-date
	 * 
	 * @return bool True if config file exists and is recent
	 */
	public static function isConfigFileUpToDate(): bool
	{
		$plugin_dir = dirname(dirname(__DIR__));
		$config_path = $plugin_dir . '/SphinxAI/config.ini';
		$template_path = $plugin_dir . '/SphinxAI/config.ini.template';
		
		if (!file_exists($config_path)) {
			return false;
		}
		
		// Check if config is newer than template (template was updated)
		if (file_exists($template_path) && filemtime($template_path) > filemtime($config_path)) {
			return false;
		}
		
		// Check if config is older than 24 hours (daily regeneration)
		if (time() - filemtime($config_path) > 86400) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Get current configuration file path
	 * 
	 * @return string Configuration file path
	 */
	public static function getConfigFilePath(): string
	{
		$plugin_dir = dirname(dirname(__DIR__));
		return $plugin_dir . '/SphinxAI/config.ini';
	}
	
	/**
	 * Validate configuration file integrity
	 * 
	 * @return array Validation result with 'status' and 'messages'
	 */
	public static function validateConfigFile(): array
	{
		$config_path = self::getConfigFilePath();
		$status = true;
		$messages = array();
		
		if (!file_exists($config_path)) {
			$status = false;
			$messages[] = 'Configuration file does not exist: ' . $config_path;
			return array('status' => $status, 'messages' => $messages);
		}
		
		// Parse INI file
		$config = parse_ini_file($config_path, true);
		if ($config === false) {
			$status = false;
			$messages[] = 'Configuration file is not valid INI format';
			return array('status' => $status, 'messages' => $messages);
		}
		
		// Check required sections
		$required_sections = array('database', 'model_settings', 'paths');
		foreach ($required_sections as $section) {
			if (!isset($config[$section])) {
				$status = false;
				$messages[] = "Required configuration section missing: {$section}";
			}
		}
		
		// Check required database settings
		if (isset($config['database'])) {
			$required_db_keys = array('host', 'database', 'user', 'table_prefix');
			foreach ($required_db_keys as $key) {
				if (empty($config['database'][$key])) {
					$status = false;
					$messages[] = "Required database setting missing: {$key}";
				}
			}
		}
		
		return array('status' => $status, 'messages' => $messages);
	}
}
