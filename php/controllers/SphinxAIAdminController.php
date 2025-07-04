<?php
/**
 * Sphinx AI Search Admin Controller
 * 
 * Handles all admin-related functionality for the Sphinx AI Search plugin.
 * Follows single responsibility principle and clean separation of concerns.
 * 
 * @package SphinxAISearch
 * @subpackage Controllers
 */

declare(strict_types=1);

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Admin Controller for Sphinx AI Search
 * 
 * Manages all administrative operations including settings, indexing, and statistics.
 */
class SphinxAIAdminController
{
	private array $smcFunc;
	private array $modSettings;
	private array $context;
	private array $txt;

	/**
	 * Constructor
	 * 
	 * @param array $smcFunc SMF database functions
	 * @param array $modSettings Forum settings
	 * @param array $context Template context
	 * @param array $txt Language strings
	 */
	public function __construct(array $smcFunc, array $modSettings, array &$context, array $txt)
	{
		$this->smcFunc = $smcFunc;
		$this->modSettings = $modSettings;
		$this->context = &$context;
		$this->txt = $txt;
	}

	/**
	 * Main admin interface dispatcher
	 * 
	 * @return void
	 */
	public function handle(): void
	{
		loadLanguage('SphinxAISearch');
		loadTemplate('SphinxAISearch');
		
		// Sanitize input parameters
		$subaction = filter_input(INPUT_GET, 'sa', FILTER_SANITIZE_STRING);
		$subaction = !empty($subaction) ? trim($subaction) : 'settings';
		
		// Validate against allowed subactions
		$allowed_subactions = array('settings', 'index', 'stats');
		if (!in_array($subaction, $allowed_subactions)) {
			$subaction = 'settings';
		}
		
		$this->context['page_title'] = $this->txt['sphinx_ai_search_admin'] ?? 'AI Search Admin';
		$this->context['sub_template'] = 'sphinx_ai_admin';
		
		switch ($subaction) {
			case 'settings':
				$this->handleSettings();
				break;
			case 'index':
				$this->handleIndexing();
				break;
			case 'stats':
				$this->handleStatistics();
				break;
			default:
				$this->handleSettings();
		}
	}

	/**
	 * Handle settings page
	 * 
	 * @return void
	 */
	private function handleSettings(): void
	{
		// Check if form was submitted
		$save_settings = filter_input(INPUT_POST, 'save_settings', FILTER_SANITIZE_STRING);
		
		if (!empty($save_settings)) {
			$this->saveSettings();
		}
		
		$this->loadCurrentSettings();
	}

	/**
	 * Save admin settings
	 * 
	 * @return void
	 */
	private function saveSettings(): void
	{
		// Validate CSRF token
		checkSession();
		
		// Sanitize and validate settings
		$model_path = filter_input(INPUT_POST, 'sphinx_ai_model_path', FILTER_SANITIZE_STRING);
		$model_path = !empty($model_path) ? trim($model_path) : '';
		
		// Validate model path to prevent directory traversal
		if (!empty($model_path)) {
			$model_path = $this->validateModelPath($model_path);
			if ($model_path === false) {
				fatal_error('Invalid model path provided. Path must be within allowed directories.');
			}
		}
		
		$max_results = filter_input(INPUT_POST, 'sphinx_ai_max_results', FILTER_VALIDATE_INT, array(
			'options' => array(
				'min_range' => 1,
				'max_range' => 100,
				'default' => 10
			)
		));
		
		$summary_length = filter_input(INPUT_POST, 'sphinx_ai_summary_length', FILTER_VALIDATE_INT, array(
			'options' => array(
				'min_range' => 50,
				'max_range' => 500,
				'default' => 200
			)
		));
		
		$auto_index = filter_input(INPUT_POST, 'sphinx_ai_auto_index', FILTER_VALIDATE_BOOLEAN);
		
		$settings = array(
			'sphinx_ai_model_path' => $model_path,
			'sphinx_ai_max_results' => $max_results,
			'sphinx_ai_summary_length' => $summary_length,
			'sphinx_ai_auto_index' => $auto_index,
		);
		
		foreach ($settings as $key => $value) {
			updateSettings(array($key => $value));
		}
		
		$this->context['settings_saved'] = true;
	}

	/**
	 * Load current settings for display
	 * 
	 * @return void
	 */
	private function loadCurrentSettings(): void
	{
		$this->context['current_settings'] = array(
			'sphinx_ai_model_path' => $this->modSettings['sphinx_ai_model_path'] ?? '',
			'sphinx_ai_max_results' => $this->modSettings['sphinx_ai_max_results'] ?? 10,
			'sphinx_ai_summary_length' => $this->modSettings['sphinx_ai_summary_length'] ?? 200,
			'sphinx_ai_auto_index' => !empty($this->modSettings['sphinx_ai_auto_index']),
		);
	}

	/**
	 * Handle indexing page
	 * 
	 * @return void
	 */
	private function handleIndexing(): void
	{
		require_once __DIR__ . '/../handlers/SphinxAIIndexHandler.php';
		$indexHandler = new SphinxAIIndexHandler($this->smcFunc);
		
		// Check if indexing was requested
		$start_indexing = filter_input(INPUT_POST, 'start_indexing', FILTER_SANITIZE_STRING);
		
		if (!empty($start_indexing)) {
			$indexed_count = $indexHandler->reindexContent();
			$this->context['indexing_started'] = true;
			$this->context['indexed_count'] = $indexed_count;
		}
		
		// Get indexing stats
		$this->context['index_stats'] = $indexHandler->getIndexStats();
	}

	/**
	 * Handle statistics page
	 * 
	 * @return void
	 */
	private function handleStatistics(): void
	{
		require_once __DIR__ . '/../handlers/SphinxAIStatsHandler.php';
		$statsHandler = new SphinxAIStatsHandler($this->smcFunc);
		
		$this->context['search_stats'] = $statsHandler->getSearchStats();
		$this->context['popular_queries'] = $statsHandler->getPopularQueries();
	}
	
	/**
	 * Validate model path to prevent directory traversal attacks
	 * 
	 * @param string $path Path to validate
	 * @return string|false Validated path or false if invalid
	 */
	private function validateModelPath(string $path)
	{
		// Define allowed base directories for models (consolidated to SphinxAI/models)
		$allowedBaseDirs = [
			'/var/lib/sphinx-ai/models',
			'/opt/sphinx-ai/models',
			dirname(__DIR__, 2) . '/SphinxAI/models'
		];
		
		// Convert to absolute path
		$realPath = realpath($path);
		
		// If realpath returns false, the path doesn't exist or is invalid
		if ($realPath === false) {
			// For non-existing paths, validate the directory structure
			$parentDir = dirname($path);
			if (!is_dir($parentDir)) {
				return false;
			}
			
			// Check if parent directory is within allowed paths
			$realParentPath = realpath($parentDir);
			if ($realParentPath === false) {
				return false;
			}
			
			// Validate against allowed directories
			foreach ($allowedBaseDirs as $allowedDir) {
				$realAllowedDir = realpath($allowedDir);
				if ($realAllowedDir !== false && strpos($realParentPath, $realAllowedDir) === 0) {
					// Path is within allowed directory, return the validated path
					return $path;
				}
			}
			
			return false;
		}
		
		// For existing paths, check against allowed directories
		foreach ($allowedBaseDirs as $allowedDir) {
			$realAllowedDir = realpath($allowedDir);
			if ($realAllowedDir !== false && strpos($realPath, $realAllowedDir) === 0) {
				return $realPath;
			}
		}
		
		// Path is not within any allowed directory
		return false;
	}
}
