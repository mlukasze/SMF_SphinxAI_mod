<?php
/**
 * Sphinx AI Search Suggestion Handler
 * 
 * Handles search suggestions, autocomplete functionality,
 * and query enhancement features.
 * 
 * @package SphinxAISearch
 * @subpackage Handlers
 */

declare(strict_types=1);

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Suggestion Handler for Sphinx AI Search
 * 
 * Manages search suggestions and autocomplete functionality.
 */
class SphinxAISuggestionHandler
{
	private array $smcFunc;

	/**
	 * Constructor
	 * 
	 * @param array $smcFunc SMF database functions
	 */
	public function __construct(array $smcFunc)
	{
		$this->smcFunc = $smcFunc;
	}

	/**
	 * Get search suggestions for autocomplete
	 * 
	 * @param string $query The search query
	 * @param int $limit Maximum number of suggestions
	 * @return array Array of suggestion strings
	 */
	public function getSuggestions(string $query, int $limit = 10): array
	{
		// Sanitize and validate query
		$query = trim($query);
		if (strlen($query) < 2) {
			return array();
		}
		
		$suggestions = array();
		
		try {
			// Get suggestions from recent searches
			$suggestions = array_merge(
				$suggestions,
				$this->getSuggestionsFromSearches($query, $limit)
			);
			
			// Get suggestions from post subjects
			$suggestions = array_merge(
				$suggestions,
				$this->getSuggestionsFromSubjects($query, $limit)
			);
			
			// Remove duplicates and limit results
			$suggestions = array_unique($suggestions);
			$suggestions = array_slice($suggestions, 0, $limit);
			
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Database error while retrieving suggestions: ' . $e->getMessage());
		}
		
		return $suggestions;
	}

	/**
	 * Get suggestions from previous searches
	 * 
	 * @param string $query The search query
	 * @param int $limit Maximum number of suggestions
	 * @return array Suggestions from search history
	 */
	private function getSuggestionsFromSearches(string $query, int $limit): array
	{
		$suggestions = array();
		
		try {
			// Get popular search terms that match the query
			$request = $this->smcFunc['db_query']('', '
				SELECT DISTINCT query_text
				FROM {db_prefix}sphinx_ai_searches
				WHERE query_text LIKE {string:query}
				GROUP BY query_text
				ORDER BY COUNT(*) DESC
				LIMIT {int:limit}',
				array(
					'query' => $query . '%',
					'limit' => $limit,
				)
			);
			
			while ($row = $this->smcFunc['db_fetch_assoc']($request)) {
				$suggestions[] = (string) $row['query_text'];
			}
			
			$this->smcFunc['db_free_result']($request);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Error getting suggestions from searches: ' . $e->getMessage());
		}
		
		return $suggestions;
	}

	/**
	 * Get suggestions from post subjects
	 * 
	 * @param string $query The search query
	 * @param int $limit Maximum number of suggestions
	 * @return array Suggestions from post subjects
	 */
	private function getSuggestionsFromSubjects(string $query, int $limit): array
	{
		$suggestions = array();
		
		try {
			// Get suggestions from post subjects
			$request = $this->smcFunc['db_query']('', '
				SELECT DISTINCT subject
				FROM {db_prefix}sphinx_ai_index
				WHERE subject LIKE {string:query}
				ORDER BY subject
				LIMIT {int:limit}',
				array(
					'query' => '%' . $query . '%',
					'limit' => $limit,
				)
			);
			
			while ($row = $this->smcFunc['db_fetch_assoc']($request)) {
				$subject = (string) $row['subject'];
				$suggestions[] = $subject;
			}
			
			$this->smcFunc['db_free_result']($request);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Error getting suggestions from subjects: ' . $e->getMessage());
		}
		
		return $suggestions;
	}

	/**
	 * Get related search terms
	 * 
	 * @param string $query The search query
	 * @return array Related search terms
	 */
	public function getRelatedTerms(string $query): array
	{
		$related_terms = array();
		
		try {
			// Find queries that users searched for after this query
			$request = $this->smcFunc['db_query']('', '
				SELECT s2.query_text, COUNT(*) as frequency
				FROM {db_prefix}sphinx_ai_searches s1
				JOIN {db_prefix}sphinx_ai_searches s2 ON (
					s1.user_id = s2.user_id 
					AND s2.search_date > s1.search_date 
					AND s2.search_date < s1.search_date + INTERVAL 1 HOUR
				)
				WHERE s1.query_text = {string:query}
					AND s2.query_text != {string:query}
				GROUP BY s2.query_text
				ORDER BY frequency DESC
				LIMIT 5',
				array('query' => $query)
			);
			
			while ($row = $this->smcFunc['db_fetch_assoc']($request)) {
				$related_terms[] = array(
					'term' => (string) $row['query_text'],
					'frequency' => (int) $row['frequency'],
				);
			}
			
			$this->smcFunc['db_free_result']($request);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Error getting related terms: ' . $e->getMessage());
		}
		
		return $related_terms;
	}
}
