<?php
/**
 * Sphinx AI Search Install Handler
 * 
 * Handles plugin installation, uninstallation, and upgrade operations.
 * Manages database schema and plugin settings.
 * 
 * @package SphinxAISearch
 * @subpackage Handlers
 */

declare(strict_types=1);

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Installation and upgrade handler for Sphinx AI Search
 * 
 * Manages:
 * - Database table creation/removal
 * - Plugin settings management
 * - Version upgrades
 * - Requirements checking
 */
class SphinxAIInstallHandler
{
	/**
	 * Current plugin version
	 */
	public const CURRENT_VERSION = '1.0.0';

	/**
	 * Required PHP version
	 */
	public const REQUIRED_PHP_VERSION = '7.4.0';

	/**
	 * Required SMF version
	 */
	public const REQUIRED_SMF_VERSION = '2.1.0';

	/**
	 * Install the plugin
	 * 
	 * Creates necessary database tables and sets up initial configuration.
	 * 
	 * @return bool Installation success
	 */
	public static function install(): bool
	{
		global $smcFunc;

		try {
			// Check requirements first
			$requirements = self::checkRequirements();
			if (!$requirements['status']) {
				foreach ($requirements['messages'] as $message) {
					log_error('Sphinx AI Search installation requirement not met: ' . $message);
				}
				return false;
			}

			// Generate configuration file from SMF settings
			require_once dirname(__DIR__) . '/utils/SphinxAIConfigGenerator.php';
			if (!SphinxAIConfigGenerator::generateConfigFile()) {
				log_error('Sphinx AI Search: Failed to generate configuration file');
				return false;
			}

			// Create database tables
			self::createTables();

			// Set default settings
			self::setDefaultSettings();

			// Log successful installation
			log_error('Sphinx AI Search plugin installed successfully (version ' . self::CURRENT_VERSION . ')', 'general');

			return true;
		} catch (Exception $e) {
			log_error('Sphinx AI Search installation failed: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Uninstall the plugin
	 * 
	 * Removes database tables and settings.
	 * 
	 * @param bool $remove_data Whether to remove all data (default: true)
	 * @return bool Uninstallation success
	 */
	public static function uninstall(bool $remove_data = true): bool
	{
		global $smcFunc;

		try {
			// Remove hook integrations first
			self::removeHookIntegrations();

			if ($remove_data) {
				// Remove database tables
				self::removeTables();

				// Remove settings
				self::removeSettings();
			}

			// Log successful uninstallation
			log_error('Sphinx AI Search plugin uninstalled successfully', 'general');

			return true;
		} catch (Exception $e) {
			log_error('Sphinx AI Search uninstallation failed: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Check if plugin requirements are met
	 * 
	 * @return array Array with 'status' (bool) and 'messages' (array)
	 */
	public static function checkRequirements(): array
	{
		$status = true;
		$messages = array();

		// Check PHP version
		if (version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION, '<')) {
			$status = false;
			$messages[] = 'PHP ' . self::REQUIRED_PHP_VERSION . ' or higher is required. Current version: ' . PHP_VERSION;
		}

		// Check SMF version
		global $smcFunc;
		if (isset($smcFunc['db_get_version'])) {
			$smf_version = SMF_VERSION ?? '2.0.0';
			if (version_compare($smf_version, self::REQUIRED_SMF_VERSION, '<')) {
				$status = false;
				$messages[] = 'SMF ' . self::REQUIRED_SMF_VERSION . ' or higher is required. Current version: ' . $smf_version;
			}
		}

		// Check if required directories exist
		$plugin_dir = dirname(dirname(__DIR__));
		$required_dirs = array(
			$plugin_dir . '/SphinxAI',
			$plugin_dir . '/php',
		);

		foreach ($required_dirs as $dir) {
			if (!is_dir($dir)) {
				$status = false;
				$messages[] = "Required directory missing: {$dir}";
			}
		}

		// Check if Python script exists
		$python_script = $plugin_dir . '/SphinxAI/main.py';
		if (!file_exists($python_script)) {
			$status = false;
			$messages[] = "Python script missing: {$python_script}";
		}

		// Check write permissions
		$cache_dir = $plugin_dir . '/cache';
		if (!is_dir($cache_dir) && !mkdir($cache_dir, 0755, true)) {
			$status = false;
			$messages[] = "Cannot create cache directory: {$cache_dir}";
		} elseif (!is_writable($cache_dir)) {
			$status = false;
			$messages[] = "Cache directory is not writable: {$cache_dir}";
		}

		return array(
			'status' => $status,
			'messages' => $messages,
		);
	}

	/**
	 * Upgrade plugin from an older version
	 * 
	 * @param string $from_version Previous version
	 * @return bool Upgrade success
	 */
	public static function upgrade(string $from_version): bool
	{
		try {
			// Version-specific upgrade logic
			if (version_compare($from_version, '1.0.0', '<')) {
				// Upgrade from pre-1.0.0 versions
				self::upgradeFrom090($from_version);
			}

			// Update version setting
			updateSettings(array(
				'sphinx_ai_version' => self::CURRENT_VERSION,
			));

			log_error("Sphinx AI Search plugin upgraded from {$from_version} to " . self::CURRENT_VERSION, 'general');

			return true;
		} catch (Exception $e) {
			log_error('Sphinx AI Search upgrade failed: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Create database tables
	 * 
	 * @return void
	 */
	private static function createTables(): void
	{
		global $smcFunc;

		// Create sphinx_ai_index table with optimized indexes
		$smcFunc['db_query']('', '
			CREATE TABLE IF NOT EXISTS {db_prefix}sphinx_ai_index (
				id int(10) unsigned NOT NULL auto_increment,
				topic_id int(10) unsigned NOT NULL,
				post_id int(10) unsigned NOT NULL,
				board_id smallint(5) unsigned NOT NULL,
				subject varchar(255) NOT NULL,
				content text NOT NULL,
				sphinx_index text,
				indexed_date timestamp DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY topic_id (topic_id),
				KEY post_id (post_id),
				KEY board_id (board_id),
				KEY idx_board_topic (board_id, topic_id),
				KEY idx_topic_post (topic_id, post_id),
				KEY idx_indexed_date (indexed_date),
				FULLTEXT KEY search_content (subject, content)
			) ENGINE=MyISAM'
		);

		// Create sphinx_ai_searches table with performance indexes
		$smcFunc['db_query']('', '
			CREATE TABLE IF NOT EXISTS {db_prefix}sphinx_ai_searches (
				id int(10) unsigned NOT NULL auto_increment,
				user_id int(10) unsigned NOT NULL,
				query_text varchar(500) NOT NULL,
				query_hash varchar(64) NOT NULL,
				results_count int(10) unsigned NOT NULL,
				search_date timestamp DEFAULT CURRENT_TIMESTAMP,
				execution_time float DEFAULT 0,
				search_type varchar(50) DEFAULT "ai",
				cache_hit tinyint(1) DEFAULT 0,
				PRIMARY KEY (id),
				KEY user_id (user_id),
				KEY search_date (search_date),
				KEY query_text (query_text(255)),
				KEY idx_user_date (user_id, search_date),
				KEY idx_query_date (query_hash, search_date),
				KEY idx_cache_performance (cache_hit, execution_time),
				KEY idx_stats_hourly (search_date, search_type, cache_hit)
			) ENGINE=MyISAM'
		);

		// Create sphinx_ai_settings table for advanced configuration
		$smcFunc['db_query']('', '
			CREATE TABLE IF NOT EXISTS {db_prefix}sphinx_ai_settings (
				setting_name varchar(100) NOT NULL,
				setting_value text,
				setting_type varchar(20) DEFAULT "string",
				updated_date timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (setting_name),
				KEY idx_type_updated (setting_type, updated_date)
			) ENGINE=MyISAM'
		);
		
		// Add performance indexes on existing SMF tables if they don't exist
		self::addPerformanceIndexes();
	}

	/**
	 * Remove database tables
	 * 
	 * @return void
	 */
	private static function removeTables(): void
	{
		global $smcFunc;

		// Remove performance indexes first
		self::removePerformanceIndexes();

		$tables = array(
			'sphinx_ai_index',
			'sphinx_ai_searches',
			'sphinx_ai_settings',
		);

		foreach ($tables as $table) {
			$smcFunc['db_query']('', "DROP TABLE IF EXISTS {db_prefix}{$table}");
		}
	}

	/**
	 * Set default plugin settings
	 * 
	 * @return void
	 */
	private static function setDefaultSettings(): void
	{
		updateSettings(array(
			'sphinx_ai_enabled' => 1,
			'sphinx_ai_model_path' => '',
			'sphinx_ai_max_results' => 10,
			'sphinx_ai_summary_length' => 200,
			'sphinx_ai_auto_index' => 1,
			'sphinx_ai_version' => self::CURRENT_VERSION,
			'sphinx_ai_monthly_searches' => 0,
			'sphinx_ai_avg_results' => 0,
			'sphinx_ai_last_maintenance' => time(),
		));
	}

	/**
	 * Remove plugin settings
	 * 
	 * @return void
	 */
	private static function removeSettings(): void
	{
		$settings_to_remove = array(
			'sphinx_ai_enabled',
			'sphinx_ai_model_path',
			'sphinx_ai_max_results',
			'sphinx_ai_summary_length',
			'sphinx_ai_auto_index',
			'sphinx_ai_version',
			'sphinx_ai_monthly_searches',
			'sphinx_ai_avg_results',
			'sphinx_ai_last_maintenance',
		);

		foreach ($settings_to_remove as $setting) {
			removeSettings($setting);
		}
	}

	/**
	 * Remove hook integrations
	 * 
	 * @return void
	 */
	private static function removeHookIntegrations(): void
	{
		// Remove integration hooks
		$hooks_to_remove = array(
			'integrate_actions',
			'integrate_menu_buttons',
			'integrate_admin_areas',
			'integrate_load_permissions',
			'integrate_create_post',
			'integrate_modify_post',
			'integrate_remove_message',
			'integrate_daily_maintenance',
			'integrate_weekly_maintenance',
		);

		foreach ($hooks_to_remove as $hook) {
			remove_integration_function($hook, 'SphinxAIHookManager::*');
		}
	}

	/**
	 * Upgrade from version 0.9.x
	 * 
	 * @param string $from_version
	 * @return void
	 */
	private static function upgradeFrom090(string $from_version): void
	{
		global $smcFunc;

		// Add new columns to existing tables if they don't exist
		$smcFunc['db_query']('', '
			ALTER TABLE {db_prefix}sphinx_ai_searches 
			ADD COLUMN IF NOT EXISTS execution_time float DEFAULT 0,
			ADD COLUMN IF NOT EXISTS search_type varchar(50) DEFAULT "ai"
		');

		// Create new settings table
		$smcFunc['db_query']('', '
			CREATE TABLE IF NOT EXISTS {db_prefix}sphinx_ai_settings (
				setting_name varchar(100) NOT NULL,
				setting_value text,
				setting_type varchar(20) DEFAULT "string",
				updated_date timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (setting_name)
			) ENGINE=MyISAM'
		);
	}

	/**
	 * Add performance indexes to existing SMF tables for search optimization
	 * 
	 * @return void
	 */
	private static function addPerformanceIndexes(): void
	{
		global $smcFunc;
		
		try {
			// Add composite index on messages table for better search performance
			// Only add if it doesn't already exist
			$result = $smcFunc['db_query']('', '
				SHOW INDEX FROM {db_prefix}messages WHERE Key_name = "idx_sphinx_search"
			');
			
			if ($smcFunc['db_num_rows']($result) === 0) {
				$smcFunc['db_query']('', '
					ALTER TABLE {db_prefix}messages 
					ADD INDEX idx_sphinx_search (id_board, id_topic, approved, poster_time)
				');
				log_error('SphinxAI: Added performance index idx_sphinx_search to messages table');
			}
			$smcFunc['db_free_result']($result);
			
			// Add composite index on topics table
			$result = $smcFunc['db_query']('', '
				SHOW INDEX FROM {db_prefix}topics WHERE Key_name = "idx_sphinx_topics"
			');
			
			if ($smcFunc['db_num_rows']($result) === 0) {
				$smcFunc['db_query']('', '
					ALTER TABLE {db_prefix}topics 
					ADD INDEX idx_sphinx_topics (id_board, approved, num_replies, id_last_msg)
				');
				log_error('SphinxAI: Added performance index idx_sphinx_topics to topics table');
			}
			$smcFunc['db_free_result']($result);
			
			// Add index for board permissions if it doesn't exist
			$result = $smcFunc['db_query']('', '
				SHOW INDEX FROM {db_prefix}boards WHERE Key_name = "idx_sphinx_boards"
			');
			
			if ($smcFunc['db_num_rows']($result) === 0) {
				$smcFunc['db_query']('', '
					ALTER TABLE {db_prefix}boards 
					ADD INDEX idx_sphinx_boards (id_cat, child_level, board_order)
				');
				log_error('SphinxAI: Added performance index idx_sphinx_boards to boards table');
			}
			$smcFunc['db_free_result']($result);
			
		} catch (Exception $e) {
			// Log error but don't fail installation
			log_error('SphinxAI: Failed to add performance indexes: ' . $e->getMessage());
		}
	}
	
	/**
	 * Remove performance indexes from SMF tables
	 * 
	 * @return void
	 */
	private static function removePerformanceIndexes(): void
	{
		global $smcFunc;
		
		try {
			// Remove the indexes we added
			$indexes = [
				'messages' => 'idx_sphinx_search',
				'topics' => 'idx_sphinx_topics',
				'boards' => 'idx_sphinx_boards'
			];
			
			foreach ($indexes as $table => $indexName) {
				$result = $smcFunc['db_query']('', "
					SHOW INDEX FROM {db_prefix}{$table} WHERE Key_name = '{$indexName}'
				");
				
				if ($smcFunc['db_num_rows']($result) > 0) {
					$smcFunc['db_query']('', "
						ALTER TABLE {db_prefix}{$table} DROP INDEX {$indexName}
					");
					log_error("SphinxAI: Removed performance index {$indexName} from {$table} table");
				}
				$smcFunc['db_free_result']($result);
			}
			
		} catch (Exception $e) {
			// Log error but don't fail uninstallation
			log_error('SphinxAI: Failed to remove performance indexes: ' . $e->getMessage());
		}
	}

	/**
	 * Get plugin information
	 * 
	 * @return array Plugin information
	 */
	public static function getPluginInfo(): array
	{
		return array(
			'name' => 'SphinxAISearch',
			'version' => self::CURRENT_VERSION,
			'description' => 'Advanced AI-powered search for SMF forums with Polish language support',
			'author' => 'Your Name',
			'license' => 'MIT',
			'smf_version' => self::REQUIRED_SMF_VERSION . '+',
			'php_version' => self::REQUIRED_PHP_VERSION . '+',
			'python_version' => '3.8+',
			'features' => array(
				'AI-powered semantic search',
				'Polish language support',
				'Real-time indexing',
				'Search analytics',
				'Suggestion system',
			),
		);
	}
}
