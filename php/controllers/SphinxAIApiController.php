<?php
/**
 * Sphinx AI Search API Controller
 * 
 * Handles AJAX/API requests for the Sphinx AI Search plugin.
 * Provides JSON endpoints for dynamic functionality.
 * 
 * @package SphinxAISearch
 * @subpackage Controllers
 */

declare(strict_types=1);

if (!defined('SMF'))
	die('Hacking attempt...');

require_once dirname(__DIR__) . '/services/SphinxAIRateLimit.php';

/**
 * API Controller for Sphinx AI Search
 * 
 * Manages AJAX endpoints and API functionality.
 */
class SphinxAIApiController
{
	private array $smcFunc;
	private SphinxAISearchService $searchService;
	private SphinxAIResultFormatter $resultFormatter;
	private SphinxAIRateLimit $rateLimit;

	/**
	 * Constructor
	 * 
	 * @param array $smcFunc SMF database functions
	 * @param SphinxAISearchService $searchService Search service
	 * @param SphinxAIResultFormatter $resultFormatter Result formatter
	 */
	public function __construct(
		array $smcFunc,
		SphinxAISearchService $searchService,
		SphinxAIResultFormatter $resultFormatter
	) {
		$this->smcFunc = $smcFunc;
		$this->searchService = $searchService;
		$this->resultFormatter = $resultFormatter;
		$this->rateLimit = new SphinxAIRateLimit();
	}

	/**
	 * Handle API requests
	 * 
	 * @return void
	 */
	public function handle(): void
	{
		header('Content-Type: application/json');
		
		// Check permissions first
		if (!allowedTo('sphinx_ai_search')) {
			$this->sendError('Permission denied', 403);
			return;
		}
		
		// Check rate limiting
		$userIdentifier = getSphinxAIUserIdentifier();
		$rateLimitResult = $this->rateLimit->checkRateLimit('search', $userIdentifier);
		
		// Set rate limit headers
		header('X-RateLimit-Limit: ' . ($this->rateLimit->config['search']['requests'] ?? 30));
		header('X-RateLimit-Remaining: ' . max(0, $rateLimitResult['remaining']));
		header('X-RateLimit-Reset: ' . $rateLimitResult['reset_time']);
		
		if (!$rateLimitResult['allowed']) {
			header('Retry-After: ' . ($rateLimitResult['blocked_until'] - time()));
			$this->sendError('Rate limit exceeded. Please try again later.', 429);
			return;
		}
		
		// Validate CSRF token for state-changing operations
		$api_action = filter_input(INPUT_POST, 'api_action', FILTER_SANITIZE_STRING);
		$api_action = !empty($api_action) ? trim($api_action) : '';
		
		if (in_array($api_action, ['reindex'], true)) {
			checkSession('post');
		}
		
		// Validate against allowed API actions
		$allowed_api_actions = array('search', 'reindex', 'suggestions');
		if (!in_array($api_action, $allowed_api_actions)) {
			$this->sendError('Invalid action', 400);
			return;
		}
		
		// Log the API request for monitoring
		$this->logApiRequest($api_action, $userIdentifier);
		
		switch ($api_action) {
			case 'search':
				$this->handleSearch();
				break;
			case 'reindex':
				$this->handleReindex();
				break;
			case 'suggestions':
				$this->handleSuggestions();
				break;
			default:
				$this->sendError('Invalid action', 400);
		}
	}

	/**
	 * Handle search API request
	 * 
	 * @return void
	 */
	private function handleSearch(): void
	{
		$query = filter_input(INPUT_POST, 'query', FILTER_SANITIZE_STRING);
		$query = !empty($query) ? trim($query) : '';
		
		if (empty($query)) {
			$this->sendError('Empty query');
			return;
		}
		
		try {
			$searchResult = $this->searchService->search($query);
			
			if (!$searchResult['success']) {
				$this->sendError($searchResult['error']);
				return;
			}
			
			$formattedResults = $this->resultFormatter->formatResults(
				$searchResult['results'], 
				$query
			);
			
			$this->sendSuccess(array('results' => $formattedResults));
		} catch (Exception $e) {
			$this->sendError('Search failed: ' . $e->getMessage());
		}
	}

	/**
	 * Handle reindex API request
	 * 
	 * @return void
	 */
	private function handleReindex(): void
	{
		try {
			require_once __DIR__ . '/../handlers/SphinxAIIndexHandler.php';
			$indexHandler = new SphinxAIIndexHandler($this->smcFunc);
			
			$indexed_count = $indexHandler->reindexContent();
			$this->sendSuccess(array('indexed_count' => $indexed_count));
		} catch (Exception $e) {
			$this->sendError('Reindexing failed: ' . $e->getMessage());
		}
	}

	/**
	 * Handle suggestions API request
	 * 
	 * @return void
	 */
	private function handleSuggestions(): void
	{
		$query = filter_input(INPUT_POST, 'query', FILTER_SANITIZE_STRING);
		$query = !empty($query) ? trim($query) : '';
		
		if (empty($query)) {
			$this->sendSuccess(array('suggestions' => array()));
			return;
		}
		
		try {
			require_once __DIR__ . '/../handlers/SphinxAISuggestionHandler.php';
			$suggestionHandler = new SphinxAISuggestionHandler($this->smcFunc);
			
			$suggestions = $suggestionHandler->getSuggestions($query);
			$this->sendSuccess(array('suggestions' => $suggestions));
		} catch (Exception $e) {
			$this->sendError('Failed to get suggestions: ' . $e->getMessage());
		}
	}

	/**
	 * Send JSON success response
	 * 
	 * @param array $data Response data
	 * @return void
	 */
	private function sendSuccess(array $data): void
	{
		echo json_encode(array_merge(array('success' => true), $data));
	}

	/**
	 * Send JSON error response
	 * 
	 * @param string $message Error message
	 * @param int $statusCode HTTP status code
	 * @return void
	 */
	private function sendError(string $message, int $statusCode = 500): void
	{
		http_response_code($statusCode);
		echo json_encode(array(
			'success' => false, 
			'error' => $message,
			'code' => $statusCode
		));
	}
	
	/**
	 * Log API request for monitoring
	 * 
	 * @param string $action API action
	 * @param string $userIdentifier User identifier
	 * @return void
	 */
	private function logApiRequest(string $action, string $userIdentifier): void
	{
		$logData = [
			'timestamp' => time(),
			'action' => $action,
			'user_identifier' => hash('sha256', $userIdentifier), // Hash for privacy
			'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
			'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200)
		];
		
		// Log to SMF error log
		log_error('SphinxAI API: ' . $action . ' request from ' . $logData['user_identifier']);
	}
}
