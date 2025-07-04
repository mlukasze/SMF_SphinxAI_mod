<?php
/**
 * Sphinx AI Search Plugin for SMF - Main Entry Point
 * 
 * This is the main entry point for the Sphinx AI Search plugin.
 * It follows a clean, modular architecture with proper separation of concerns.
 * 
 * @package SphinxAISearch
 * @version 1.0.0
 * @author Your Name
 * @license MIT
 */

declare(strict_types=1);

if (!defined('SMF'))
	die('Hacking attempt...');

// Load the hook manager which handles all SMF integration
require_once __DIR__ . '/php/core/SphinxAIHookManager.php';

// Load the install handler for setup operations
require_once __DIR__ . '/php/handlers/SphinxAIInstallHandler.php';

/**
 * Sphinx AI Search Plugin - Main Class
 * 
 * This class serves as the main entry point and coordinator for the plugin.
 * It delegates all functionality to specialized controllers and handlers.
 * 
 * The class is kept minimal and focused on:
 * - Plugin initialization and setup
 * - Hook registration with SMF
 * - Coordination between components
 * 
 * All business logic is handled by specialized classes in the php/ directory.
 */
class SphinxAISearch
{
	/**
	 * Plugin version
	 */
	public const VERSION = '1.0.0';

	/**
	 * Plugin name
	 */
	public const PLUGIN_NAME = 'SphinxAISearch';

	/**
	 * Hook manager instance
	 */
	private static ?SphinxAIHookManager $hookManager = null;

	/**
	 * Initialize the plugin
	 * 
	 * This method sets up the plugin and registers all necessary hooks.
	 * Called during SMF initialization.
	 * 
	 * @return void
	 */
	public static function initialize(): void
	{
		self::$hookManager = SphinxAIHookManager::getInstance();
		self::registerHooks();
	}

	/**
	 * Register SMF hooks
	 * 
	 * This method registers all the hooks that the plugin needs to integrate with SMF.
	 * Uses the hook manager to maintain clean separation.
	 * 
	 * @return void
	 */
	private static function registerHooks(): void
	{
		// Core functionality hooks
		add_integration_function('integrate_actions', 'SphinxAIHookManager::hookRegisterActions');
		add_integration_function('integrate_menu_buttons', 'SphinxAIHookManager::hookAddMenuButton');
		add_integration_function('integrate_admin_areas', 'SphinxAIHookManager::hookAddAdminArea');
		add_integration_function('integrate_load_permissions', 'SphinxAIHookManager::hookLoadPermissions');
		
		// Content indexing hooks
		add_integration_function('integrate_create_post', 'SphinxAIHookManager::hookIndexPost');
		add_integration_function('integrate_modify_post', 'SphinxAIHookManager::hookUpdatePostIndex');
		add_integration_function('integrate_remove_message', 'SphinxAIHookManager::hookRemoveFromIndex');
		
		// Maintenance hooks
		add_integration_function('integrate_daily_maintenance', 'SphinxAIHookManager::hookCleanupSearchLogs');
		add_integration_function('integrate_weekly_maintenance', 'SphinxAIHookManager::hookUpdateSearchStats');
	}

	/**
	 * Get plugin information
	 * 
	 * @return array Plugin information
	 */
	public static function getPluginInfo(): array
	{
		return SphinxAIInstallHandler::getPluginInfo();
	}

	/**
	 * Check if plugin requirements are met
	 * 
	 * @return array Array with 'status' (bool) and 'messages' (array)
	 */
	public static function checkRequirements(): array
	{
		return SphinxAIInstallHandler::checkRequirements();
	}

	/**
	 * Install plugin
	 * 
	 * Delegates to the install handler for all setup operations.
	 * 
	 * @return bool Installation success
	 */
	public static function install(): bool
	{
		return SphinxAIInstallHandler::install();
	}

	/**
	 * Uninstall plugin
	 * 
	 * Delegates to the install handler for all cleanup operations.
	 * 
	 * @param bool $remove_data Whether to remove all data (default: true)
	 * @return bool Uninstallation success
	 */
	public static function uninstall(bool $remove_data = true): bool
	{
		return SphinxAIInstallHandler::uninstall($remove_data);
	}

	/**
	 * Upgrade plugin from an older version
	 * 
	 * @param string $from_version Previous version
	 * @return bool Upgrade success
	 */
	public static function upgrade(string $from_version): bool
	{
		return SphinxAIInstallHandler::upgrade($from_version);
	}

	// ==========================================
	// LEGACY COMPATIBILITY METHODS
	// These methods maintain backward compatibility with the old interface
	// while delegating to the new modular system
	// ==========================================

	/**
	 * Legacy method for main search interface
	 * 
	 * @deprecated Use SphinxAIHookManager instead
	 * @return void
	 */
	public function main(): void
	{
		if (self::$hookManager) {
			self::$hookManager->handleMainAction();
		}
	}

	/**
	 * Legacy method for admin interface
	 * 
	 * @deprecated Use SphinxAIHookManager instead
	 * @return void
	 */
	public function admin(): void
	{
		if (self::$hookManager) {
			self::$hookManager->handleAdminAction();
		}
	}

	/**
	 * Legacy method for API interface
	 * 
	 * @deprecated Use SphinxAIHookManager instead
	 * @return void
	 */
	public function api(): void
	{
		if (self::$hookManager) {
			self::$hookManager->handleApiAction();
		}
	}

	/**
	 * Static factory method for legacy compatibility
	 * 
	 * @deprecated Use the new modular system instead
	 * @return self
	 */
	public static function createFromGlobals(): self
	{
		return new self();
	}
}

// Initialize the plugin when this file is loaded
SphinxAISearch::initialize();