<?php
/**
 * Sphinx AI Search Hook Manager
 * 
 * Manages SMF hooks and integration points.
 * Provides clean separation between hook system and business logic.
 * 
 * @package SphinxAISearch
 * @subpackage Core
 */

declare(strict_types=1);

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Hook Manager for Sphinx AI Search
 * 
 * Manages all SMF hook integrations and provides bridge to modular components.
 */
class SphinxAIHookManager
{
	private static ?SphinxAIHookManager $instance = null;
	private array $smcFunc;
	private array $modSettings;
	private array $context;
	private array $txt;
	private array $userInfo;
	private string $scriptUrl;
	private string $pluginPath;

	/**
	 * Private constructor for singleton pattern
	 */
	private function __construct()
	{
		global $smcFunc, $modSettings, $context, $txt, $user_info, $scripturl;
		
		$this->smcFunc = $smcFunc ?? [];
		$this->modSettings = $modSettings ?? [];
		$this->context = &$context;
		$this->txt = $txt ?? [];
		$this->userInfo = $user_info ?? [];
		$this->scriptUrl = $scripturl ?? '';
		$this->pluginPath = dirname(__DIR__);
	}

	/**
	 * Get singleton instance
	 * 
	 * @return self
	 */
	public static function getInstance(): self
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register plugin actions (hook: integrate_actions)
	 * 
	 * @param array $actions Actions array to modify
	 * @return void
	 */
	public function registerActions(array &$actions): void
	{
		$actions['sphinxai'] = array('SphinxAISearch.php', array($this, 'handleMainAction'));
		$actions['sphinxai_admin'] = array('SphinxAISearch.php', array($this, 'handleAdminAction'));
		$actions['sphinxai_api'] = array('SphinxAISearch.php', array($this, 'handleApiAction'));
	}

	/**
	 * Add menu button (hook: integrate_menu_buttons)
	 * 
	 * @param array $menu_buttons Menu buttons array to modify
	 * @return void
	 */
	public function addMenuButton(array &$menu_buttons): void
	{
		$menu_buttons['sphinxai'] = array(
			'title' => $this->txt['sphinx_ai_search'] ?? 'AI Search',
			'href' => $this->scriptUrl . '?action=sphinxai',
			'show' => allowedTo('sphinx_ai_search'),
			'sub_buttons' => array(),
		);
	}

	/**
	 * Add admin area (hook: integrate_admin_areas)
	 * 
	 * @param array $admin_areas Admin areas array to modify
	 * @return void
	 */
	public function addAdminArea(array &$admin_areas): void
	{
		$admin_areas['config']['areas']['sphinx_ai_search'] = array(
			'label' => $this->txt['sphinx_ai_search_admin'] ?? 'AI Search Admin',
			'function' => array($this, 'handleAdminAction'),
			'icon' => 'search.png',
			'permission' => array('admin_forum'),
			'subsections' => array(
				'settings' => array($this->txt['sphinx_ai_search_settings'] ?? 'Settings'),
				'index' => array($this->txt['sphinx_ai_search_index'] ?? 'Index'),
				'stats' => array($this->txt['sphinx_ai_search_stats'] ?? 'Statistics'),
			),
		);
	}

	/**
	 * Load permissions (hook: integrate_load_permissions)
	 * 
	 * @param array $permissionGroups Permission groups array to modify
	 * @param array $permissionList Permission list array to modify
	 * @return void
	 */
	public function loadPermissions(array &$permissionGroups, array &$permissionList): void
	{
		$permissionGroups['membergroup']['simple'] = array('sphinx_ai_search');
		$permissionGroups['membergroup']['classic'] = array('sphinx_ai_search');
		
		$permissionList['membergroup']['sphinx_ai_search'] = array(false, 'general', 'view_basic_info');
	}

	/**
	 * Handle main search action
	 * 
	 * @return void
	 */
	public function handleMainAction(): void
	{
		require_once $this->pluginPath . '/php/core/SphinxAISearchService.php';
		require_once $this->pluginPath . '/php/services/SphinxAIResultFormatter.php';
		require_once $this->pluginPath . '/php/controllers/SphinxAISearchController.php';
		
		$searchService = new SphinxAISearchService($this->modSettings, $this->smcFunc);
		$resultFormatter = new SphinxAIResultFormatter($this->smcFunc, $this->modSettings, $this->txt, $this->scriptUrl);
		
		$controller = new SphinxAISearchController(
			$this->smcFunc,
			$this->modSettings,
			$this->context,
			$this->txt,
			$this->userInfo,
			$this->scriptUrl,
			$searchService,
			$resultFormatter
		);
		
		$controller->handle();
	}

	/**
	 * Handle admin action
	 * 
	 * @return void
	 */
	public function handleAdminAction(): void
	{
		require_once $this->pluginPath . '/php/controllers/SphinxAIAdminController.php';
		
		$controller = new SphinxAIAdminController(
			$this->smcFunc,
			$this->modSettings,
			$this->context,
			$this->txt
		);
		
		$controller->handle();
	}

	/**
	 * Handle API action
	 * 
	 * @return void
	 */
	public function handleApiAction(): void
	{
		require_once $this->pluginPath . '/php/core/SphinxAISearchService.php';
		require_once $this->pluginPath . '/php/services/SphinxAIResultFormatter.php';
		require_once $this->pluginPath . '/php/controllers/SphinxAIApiController.php';
		
		$searchService = new SphinxAISearchService($this->modSettings, $this->smcFunc);
		$resultFormatter = new SphinxAIResultFormatter($this->smcFunc, $this->modSettings, $this->txt, $this->scriptUrl);
		
		$controller = new SphinxAIApiController(
			$this->smcFunc,
			$searchService,
			$resultFormatter
		);
		
		$controller->handle();
	}

	/**
	 * Index post (hook: integrate_create_post)
	 * 
	 * @param array $msgOptions Message options
	 * @param array $topicOptions Topic options
	 * @param array $posterOptions Poster options
	 * @return void
	 */
	public function indexPost(array $msgOptions, array $topicOptions, array $posterOptions): void
	{
		require_once $this->pluginPath . '/php/handlers/SphinxAIIndexHandler.php';
		
		$indexHandler = new SphinxAIIndexHandler($this->smcFunc);
		$indexHandler->indexPost($msgOptions, $topicOptions, $posterOptions);
	}

	/**
	 * Update post index (hook: integrate_modify_post)
	 * 
	 * @param array $msgOptions Message options
	 * @param array $topicOptions Topic options
	 * @param array $posterOptions Poster options
	 * @return void
	 */
	public function updatePostIndex(array $msgOptions, array $topicOptions, array $posterOptions): void
	{
		require_once $this->pluginPath . '/php/handlers/SphinxAIIndexHandler.php';
		
		$indexHandler = new SphinxAIIndexHandler($this->smcFunc);
		$indexHandler->updatePostIndex($msgOptions, $topicOptions, $posterOptions);
	}

	/**
	 * Remove from index (hook: integrate_remove_message)
	 * 
	 * @param array $messages Array of message IDs
	 * @return void
	 */
	public function removeFromIndex(array $messages): void
	{
		require_once $this->pluginPath . '/php/handlers/SphinxAIIndexHandler.php';
		
		$indexHandler = new SphinxAIIndexHandler($this->smcFunc);
		$indexHandler->removeFromIndex($messages);
	}

	/**
	 * Cleanup search logs (hook: integrate_daily_maintenance)
	 * 
	 * @return void
	 */
	public function cleanupSearchLogs(): void
	{
		require_once $this->pluginPath . '/php/handlers/SphinxAIStatsHandler.php';
		
		$statsHandler = new SphinxAIStatsHandler($this->smcFunc);
		$statsHandler->cleanupSearchLogs();
	}

	/**
	 * Update search stats (hook: integrate_weekly_maintenance)
	 * 
	 * @return void
	 */
	public function updateSearchStats(): void
	{
		require_once $this->pluginPath . '/php/handlers/SphinxAIStatsHandler.php';
		
		$statsHandler = new SphinxAIStatsHandler($this->smcFunc);
		$statsHandler->updateSearchStats();
	}

	// ==========================================
	// STATIC HOOK METHODS FOR SMF COMPATIBILITY
	// ==========================================

	/**
	 * Static hook: integrate_actions
	 */
	public static function hookRegisterActions(array &$actions): void
	{
		self::getInstance()->registerActions($actions);
	}

	/**
	 * Static hook: integrate_menu_buttons
	 */
	public static function hookAddMenuButton(array &$menu_buttons): void
	{
		self::getInstance()->addMenuButton($menu_buttons);
	}

	/**
	 * Static hook: integrate_admin_areas
	 */
	public static function hookAddAdminArea(array &$admin_areas): void
	{
		self::getInstance()->addAdminArea($admin_areas);
	}

	/**
	 * Static hook: integrate_load_permissions
	 */
	public static function hookLoadPermissions(array &$permissionGroups, array &$permissionList): void
	{
		self::getInstance()->loadPermissions($permissionGroups, $permissionList);
	}

	/**
	 * Static hook: integrate_create_post
	 */
	public static function hookIndexPost(array $msgOptions, array $topicOptions, array $posterOptions): void
	{
		self::getInstance()->indexPost($msgOptions, $topicOptions, $posterOptions);
	}

	/**
	 * Static hook: integrate_modify_post
	 */
	public static function hookUpdatePostIndex(array $msgOptions, array $topicOptions, array $posterOptions): void
	{
		self::getInstance()->updatePostIndex($msgOptions, $topicOptions, $posterOptions);
	}

	/**
	 * Static hook: integrate_remove_message
	 */
	public static function hookRemoveFromIndex(array $messages): void
	{
		self::getInstance()->removeFromIndex($messages);
	}

	/**
	 * Static hook: integrate_daily_maintenance
	 */
	public static function hookCleanupSearchLogs(): void
	{
		self::getInstance()->cleanupSearchLogs();
	}

	/**
	 * Static hook: integrate_weekly_maintenance
	 */
	public static function hookUpdateSearchStats(): void
	{
		self::getInstance()->updateSearchStats();
	}
}
