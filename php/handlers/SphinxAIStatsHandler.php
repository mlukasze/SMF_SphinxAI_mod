<?php
/**
 * Sphinx AI Search Statistics Handler
 * 
 * Handles collection and calculation of search statistics,
 * analytics, and reporting functionality.
 * 
 * @package SphinxAISearch
 * @subpackage Handlers
 */

declare(strict_types=1);

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Statistics Handler for Sphinx AI Search
 * 
 * Manages search analytics, statistics collection, and reporting.
 */
class SphinxAIStatsHandler
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
	 * Get search statistics
	 * 
	 * @return array Search statistics
	 */
	public function getSearchStats(): array
	{
		$stats = array('total_searches' => 0, 'avg_results' => 0);
		
		try {
			$request = $this->smcFunc['db_query']('', '
				SELECT COUNT(*) as total_searches, AVG(results_count) as avg_results
				FROM {db_prefix}sphinx_ai_searches
				WHERE search_date > NOW() - INTERVAL 30 DAY'
			);
			$stats = $this->smcFunc['db_fetch_assoc']($request);
			$this->smcFunc['db_free_result']($request);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Database error while retrieving statistics: ' . $e->getMessage());
		}
		
		return $stats;
	}

	/**
	 * Get popular search queries
	 * 
	 * @param int $limit Number of queries to return
	 * @return array Popular queries
	 */
	public function getPopularQueries(int $limit = 10): array
	{
		$popular_queries = array();
		
		try {
			$request = $this->smcFunc['db_query']('', '
				SELECT query_text, COUNT(*) as search_count
				FROM {db_prefix}sphinx_ai_searches
				WHERE search_date > NOW() - INTERVAL 30 DAY
				GROUP BY query_text
				ORDER BY search_count DESC
				LIMIT {int:limit}',
				array('limit' => $limit)
			);
			
			while ($row = $this->smcFunc['db_fetch_assoc']($request)) {
				$popular_queries[] = array(
					'query_text' => (string) $row['query_text'],
					'search_count' => (int) $row['search_count'],
				);
			}
			$this->smcFunc['db_free_result']($request);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Database error while retrieving popular queries: ' . $e->getMessage());
		}
		
		return $popular_queries;
	}

	/**
	 * Update search statistics cache
	 * 
	 * @return void
	 */
	public function updateSearchStats(): void
	{
		try {
			// Update cached statistics
			$request = $this->smcFunc['db_query']('', '
				SELECT COUNT(*) as total_searches, AVG(results_count) as avg_results
				FROM {db_prefix}sphinx_ai_searches
				WHERE search_date > NOW() - INTERVAL 30 DAY'
			);
			$stats = $this->smcFunc['db_fetch_assoc']($request);
			$this->smcFunc['db_free_result']($request);
			
			// Cache the stats
			updateSettings(array(
				'sphinx_ai_monthly_searches' => (int) $stats['total_searches'],
				'sphinx_ai_avg_results' => (float) $stats['avg_results'],
			));
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Database error while updating search stats: ' . $e->getMessage());
		}
	}

	/**
	 * Clean up old search logs
	 * 
	 * @param int $days_to_keep Number of days to keep logs
	 * @return void
	 */
	public function cleanupSearchLogs(int $days_to_keep = 90): void
	{
		try {
			// Remove search logs older than specified days
			$this->smcFunc['db_query']('', '
				DELETE FROM {db_prefix}sphinx_ai_searches 
				WHERE search_date < NOW() - INTERVAL {int:days} DAY',
				array('days' => $days_to_keep)
			);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Database error while cleaning up search logs: ' . $e->getMessage());
		}
	}

	/**
	 * Get search trends
	 * 
	 * @param int $days Number of days to analyze
	 * @return array Search trends data
	 */
	public function getSearchTrends(int $days = 30): array
	{
		$trends = array();
		
		try {
			$request = $this->smcFunc['db_query']('', '
				SELECT DATE(search_date) as search_day, COUNT(*) as daily_searches
				FROM {db_prefix}sphinx_ai_searches
				WHERE search_date > NOW() - INTERVAL {int:days} DAY
				GROUP BY DATE(search_date)
				ORDER BY search_day ASC',
				array('days' => $days)
			);
			
			while ($row = $this->smcFunc['db_fetch_assoc']($request)) {
				$trends[] = array(
					'date' => $row['search_day'],
					'searches' => (int) $row['daily_searches'],
				);
			}
			$this->smcFunc['db_free_result']($request);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Database error while retrieving search trends: ' . $e->getMessage());
		}
		
		return $trends;
	}
}
