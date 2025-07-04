<?php
/**
 * Sphinx AI Search Main Controller
 * 
 * Handles the main search interface and user interactions.
 * Focused on search functionality and user experience.
 * 
 * @package SphinxAISearch
 * @subpackage Controllers
 */

declare(strict_types=1);

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Main Search Controller for Sphinx AI Search
 * 
 * Manages the main search interface and user search operations.
 */
class SphinxAISearchController
{
	private array $smcFunc;
	private array $modSettings;
	private array $context;
	private array $txt;
	private array $userInfo;
	private string $scriptUrl;
	private SphinxAISearchService $searchService;
	private SphinxAIResultFormatter $resultFormatter;

	/**
	 * Constructor
	 * 
	 * @param array $smcFunc SMF database functions
	 * @param array $modSettings Forum settings
	 * @param array $context Template context
	 * @param array $txt Language strings
	 * @param array $userInfo User information
	 * @param string $scriptUrl Script URL
	 * @param SphinxAISearchService $searchService Search service
	 * @param SphinxAIResultFormatter $resultFormatter Result formatter
	 */
	public function __construct(
		array $smcFunc,
		array $modSettings,
		array &$context,
		array $txt,
		array $userInfo,
		string $scriptUrl,
		SphinxAISearchService $searchService,
		SphinxAIResultFormatter $resultFormatter
	) {
		$this->smcFunc = $smcFunc;
		$this->modSettings = $modSettings;
		$this->context = &$context;
		$this->txt = $txt;
		$this->userInfo = $userInfo;
		$this->scriptUrl = $scriptUrl;
		$this->searchService = $searchService;
		$this->resultFormatter = $resultFormatter;
	}

	/**
	 * Handle main search interface
	 * 
	 * @return void
	 */
	public function handle(): void
	{
		loadLanguage('SphinxAISearch');
		loadTemplate('SphinxAISearch');
		
		$this->context['page_title'] = $this->txt['sphinx_ai_search'] ?? 'AI Search';
		$this->context['sub_template'] = 'sphinx_ai_search';
		
		// Handle search request
		$search_query = filter_input(INPUT_POST, 'search_query', FILTER_SANITIZE_STRING);
		$search_query = !empty($search_query) ? trim($search_query) : '';
		
		if (!empty($search_query)) {
			$this->processSearch($search_query);
		}
		
		$this->context['sphinx_ai_search_url'] = $this->scriptUrl . '?action=sphinxai';
	}

	/**
	 * Process search query
	 * 
	 * @param string $search_query The search query
	 * @return void
	 */
	private function processSearch(string $search_query): void
	{
		// Validate query length
		if (strlen($search_query) < 2) {
			$this->context['search_error'] = $this->txt['sphinx_ai_error_short_query'] ?? 'Query too short';
			return;
		}
		
		if (strlen($search_query) > 1000) {
			$this->context['search_error'] = $this->txt['sphinx_ai_error_query_too_long'] ?? 'Query too long';
			return;
		}
		
		// Sanitize and perform search
		$sanitized_query = $this->smcFunc['htmlspecialchars']($search_query, ENT_QUOTES);
		$results = $this->performSearch($sanitized_query);
		
		$this->context['search_results'] = $results;
		$this->context['search_query'] = $sanitized_query;
		
		// Log the search
		$this->logSearch($sanitized_query, count($results));
	}

	/**
	 * Perform AI-powered search
	 * 
	 * @param string $query The search query
	 * @return array Array of search results
	 */
	private function performSearch(string $query): array
	{
		try {
			$searchResult = $this->searchService->search($query);
			
			if (!$searchResult['success']) {
				log_error('Sphinx AI Search: ' . $searchResult['error']);
				return array();
			}
			
			// Format results for SMF template
			return $this->resultFormatter->formatResults($searchResult['results'], $query);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Exception during search - ' . $e->getMessage());
			return array();
		}
	}

	/**
	 * Log search query
	 * 
	 * @param string $query The search query
	 * @param int $results_count The number of results returned
	 * @return void
	 */
	private function logSearch(string $query, int $results_count): void
	{
		if (empty($query) || $results_count < 0) {
			return;
		}
		
		try {
			$this->smcFunc['db_insert']('',
				'{db_prefix}sphinx_ai_searches',
				array(
					'user_id' => 'int',
					'query_text' => 'string',
					'results_count' => 'int',
				),
				array(
					(int) $this->userInfo['id'],
					$query,
					$results_count,
				),
				array('id')
			);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Database error while logging search: ' . $e->getMessage());
		}
	}
}
