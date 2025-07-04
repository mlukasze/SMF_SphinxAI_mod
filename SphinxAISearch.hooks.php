<?php
/**
 * Sphinx AI Search Permissions and Hooks Configuration
 * 
 * @package SphinxAISearch
 * @version 1.0.0
 * @author SMF AI Team
 * @license MIT
 */

declare(strict_types=1);

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Integration hooks for the plugin
 */
function sphinxai_integrate_actions(array &$actions): void
{
	SphinxAISearch::registerActionsStatic($actions);
}

function sphinxai_integrate_menu_buttons(array &$menu_buttons): void
{
	SphinxAISearch::addMenuButtonStatic($menu_buttons);
}

function sphinxai_integrate_admin_areas(array &$admin_areas): void
{
	SphinxAISearch::addAdminAreaStatic($admin_areas);
}

function sphinxai_integrate_load_permissions(array &$permissionGroups, array &$permissionList): void
{
	SphinxAISearch::loadPermissionsStatic($permissionGroups, $permissionList);
}

/**
 * Hook for post creation to auto-index content
 */
function sphinxai_integrate_create_post(array &$msgOptions, array &$topicOptions, array &$posterOptions): void
{
	global $modSettings;
	
	// Only proceed if auto-indexing is enabled
	if (empty($modSettings['sphinx_ai_auto_index']))
		return;
	
	// Index the new post
	SphinxAISearch::indexPostStatic($msgOptions, $topicOptions, $posterOptions);
}

/**
 * Hook for post modification to update index
 */
function sphinxai_integrate_modify_post(array &$messages_columns, array &$update_parameters, array &$msgOptions, array &$topicOptions, array &$posterOptions): void
{
	global $modSettings;
	
	// Only proceed if auto-indexing is enabled
	if (empty($modSettings['sphinx_ai_auto_index']))
		return;
	
	// Update the index
	SphinxAISearch::updatePostIndexStatic($msgOptions, $topicOptions, $posterOptions);
}

/**
 * Hook for post deletion to remove from index
 */
function sphinxai_integrate_remove_message(array &$messages, array &$log_removed): void
{
	global $modSettings;
	
	// Only proceed if auto-indexing is enabled
	if (empty($modSettings['sphinx_ai_auto_index']))
		return;
	
	// Remove from index
	SphinxAISearch::removeFromIndexStatic($messages);
}

/**
 * Hook for board permissions
 */
function sphinxai_integrate_board_permissions(array &$permissions): void
{
	$permissions['sphinx_ai_search'] = array(
		'own' => array(
			'sphinx_ai_search_own' => false,
		),
		'any' => array(
			'sphinx_ai_search_any' => false,
		),
	);
}

/**
 * Hook for loading user permissions
 */
function sphinxai_integrate_load_illegal_guest_permissions(array &$illegal_guest_permissions): void
{
	$illegal_guest_permissions[] = 'sphinx_ai_search';
}

/**
 * Hook for theme loading
 */
function sphinxai_integrate_load_theme(int &$theme_id): void
{
	global $context, $settings;
	
	// Load CSS and JS files
	if (isset($context['current_action']) && $context['current_action'] == 'sphinxai') {
		loadCSSFile('SphinxAISearch.css');
		loadJavaScriptFile('SphinxAISearch.js');
	}
}

/**
 * Hook for search integration
 */
function sphinxai_integrate_search_params(array &$search_params): void
{
	// Add AI search parameters
	$search_params['sphinx_ai'] = !empty($_REQUEST['sphinx_ai']);
}

/**
 * Hook for credits
 */
function sphinxai_integrate_credits(array &$credits): void
{
	$credits[] = array(
		'pretext' => 'Sphinx AI Search Plugin',
		'title' => 'AI-Powered Forum Search',
		'version' => '1.0.0',
		'author' => 'SMF AI Team',
		'description' => 'Intelligent search using OpenVINO and Sphinx',
		'license' => 'MIT',
		'link' => 'https://github.com/smf-ai/sphinx-search',
	);
}

/**
 * Hook for maintenance tasks
 */
function sphinxai_integrate_daily_maintenance(): void
{
	global $modSettings;
	
	// Perform daily maintenance tasks
	if (!empty($modSettings['sphinx_ai_auto_index'])) {
		// Clean up old search logs
		SphinxAISearch::cleanupSearchLogsStatic();
		
		// Update search statistics
		SphinxAISearch::updateSearchStatsStatic();
		
		// Optimize search index if needed
		SphinxAISearch::optimizeIndexStatic();
	}
}

/**
 * Hook for XML feed integration
 */
function sphinxai_integrate_xml_data(array &$xml_data): void
{
	// Add AI search capabilities to XML feeds
	$xml_data['sphinx_ai_search'] = array(
		'enabled' => true,
		'version' => '1.0.0',
		'features' => array(
			'ai_summaries',
			'semantic_search',
			'source_linking',
			'multilingual'
		)
	);
}

/**
 * Hook for mobile theme integration
 */
function sphinxai_integrate_wireless_templates(array &$templates): void
{
	// Add mobile-friendly search templates
	$templates['sphinxai'] = array(
		'sub_template' => 'sphinx_ai_search_mobile',
		'layers' => array('html', 'body'),
	);
}

/**
 * Hook for API integration
 */
function sphinxai_integrate_api_endpoints(array &$endpoints): void
{
	// Add API endpoints for external integration
	$endpoints['sphinx_ai'] = array(
		'search' => 'SphinxAISearch::apiSearch',
		'suggest' => 'SphinxAISearch::apiSuggest',
		'stats' => 'SphinxAISearch::apiStats',
	);
}

/**
 * Hook for caching integration
 */
function sphinxai_integrate_cache_get_data(&$cache_key, &$cache_data)
{
	// Custom cache handling for AI search results
	if (strpos($cache_key, 'sphinx_ai_') === 0) {
		$cache_data = SphinxAISearch::getCacheData($cache_key);
	}
}

/**
 * Hook for profile integration
 */
function sphinxai_integrate_profile_areas(&$profile_areas)
{
	global $txt, $user_info;
	
	// Add AI search preferences to user profile
	$profile_areas['modify_profile']['areas']['sphinx_ai_prefs'] = array(
		'label' => $txt['sphinx_ai_preferences'],
		'file' => 'SphinxAISearch.php',
		'function' => 'SphinxAISearch::profilePreferences',
		'icon' => 'search.png',
		'permission' => array(
			'own' => array('profile_view_own', 'profile_view_any'),
			'any' => array('profile_view_any'),
		),
	);
}

/**
 * Hook for backup integration
 */
function sphinxai_integrate_backup_data(&$backup_data)
{
	// Include AI search data in backups
	$backup_data['sphinx_ai_index'] = array(
		'table' => 'sphinx_ai_index',
		'essential' => true,
	);
	
	$backup_data['sphinx_ai_searches'] = array(
		'table' => 'sphinx_ai_searches',
		'essential' => false,
	);
}

/**
 * Hook for error handling
 */
function sphinxai_integrate_error_handling(&$error_data)
{
	// Custom error handling for AI search
	if (isset($error_data['sphinx_ai_error'])) {
		log_error('Sphinx AI Search Error: ' . $error_data['sphinx_ai_error']);
	}
}

/**
 * Hook for scheduled tasks
 */
function sphinxai_integrate_scheduled_tasks(&$scheduled_tasks)
{
	// Add scheduled tasks for AI search maintenance
	$scheduled_tasks['sphinx_ai_reindex'] = array(
		'file' => 'SphinxAISearch.php',
		'function' => 'SphinxAISearch::scheduledReindex',
		'interval' => 86400, // Daily
		'time_offset' => 0,
		'time_regularity' => 1,
		'time_unit' => 'd',
	);
	
	$scheduled_tasks['sphinx_ai_cleanup'] = array(
		'file' => 'SphinxAISearch.php',
		'function' => 'SphinxAISearch::scheduledCleanup',
		'interval' => 604800, // Weekly
		'time_offset' => 0,
		'time_regularity' => 1,
		'time_unit' => 'w',
	);
}

/**
 * Hook for statistics integration
 */
function sphinxai_integrate_stats_admin(&$stats_admin)
{
	global $txt;
	
	// Add AI search statistics to admin stats
	$stats_admin['sphinx_ai'] = array(
		'title' => $txt['sphinx_ai_statistics'],
		'data' => array(
			'total_searches' => SphinxAISearch::getTotalSearches(),
			'avg_results' => SphinxAISearch::getAverageResults(),
			'popular_queries' => SphinxAISearch::getPopularQueries(),
			'search_performance' => SphinxAISearch::getSearchPerformance(),
		),
	);
}

/**
 * Hook for language integration
 */
function sphinxai_integrate_language_files(&$language_files)
{
	// Load language files for AI search
	$language_files[] = 'SphinxAISearch';
}

/**
 * Hook for theme integration
 */
function sphinxai_integrate_theme_variants(&$theme_variants)
{
	// Add theme variants for AI search
	$theme_variants['sphinx_ai_dark'] = array(
		'name' => 'Sphinx AI Dark',
		'css' => 'SphinxAISearch_dark.css',
	);
}

/**
 * Hook for board index integration
 */
function sphinxai_integrate_board_index_after(&$board_index)
{
	global $context;
	
	// Add quick search widget to board index
	if (!empty($context['sphinx_ai_quick_search'])) {
		$context['sphinx_ai_quick_search_widget'] = true;
	}
}

/**
 * Hook for message index integration
 */
function sphinxai_integrate_message_index(array &$message_index): void
{
	// Add AI search context to message index
	$message_index['sphinx_ai_context'] = SphinxAISearch::getMessageContextStatic();
}

/**
 * Hook for RSS integration
 */
function sphinxai_integrate_rss_data(array &$rss_data): void
{
	// Add AI search results to RSS feeds
	if (!empty($_REQUEST['sphinx_ai_rss'])) {
		$rss_data['sphinx_ai_results'] = SphinxAISearch::getRSSSearchResultsStatic();
	}
}

/**
 * Hook for sitemap integration
 */
function sphinxai_integrate_sitemap(array &$sitemap_data): void
{
	// Add AI search pages to sitemap
	$sitemap_data['sphinx_ai_search'] = array(
		'url' => 'index.php?action=sphinxai',
		'priority' => 0.8,
		'changefreq' => 'weekly',
	);
}

/**
 * Hook for SEO integration
 */
function sphinxai_integrate_seo_data(array &$seo_data): void
{
	global $context;
	
	// Add SEO data for AI search pages
	if (isset($context['current_action']) && $context['current_action'] == 'sphinxai') {
		$seo_data['meta_description'] = 'AI-powered search for forum content';
		$seo_data['meta_keywords'] = 'AI search, forum search, intelligent search';
		$seo_data['canonical_url'] = 'index.php?action=sphinxai';
	}
}
