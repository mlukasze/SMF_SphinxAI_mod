<?php
/**
 * Sphinx AI Search Index Handler
 * 
 * Handles all indexing operations including content processing,
 * index management, and Sphinx integration.
 * 
 * @package SphinxAISearch
 * @subpackage Handlers
 */

declare(strict_types=1);

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Index Handler for Sphinx AI Search
 * 
 * Manages content indexing, processing, and database operations.
 */
class SphinxAIIndexHandler
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
	 * Reindex all content
	 * 
	 * @return int Number of indexed items
	 */
	public function reindexContent(): int
	{
		$indexed_count = 0;
		
		try {
			// Clear existing index
			$this->smcFunc['db_query']('', 'TRUNCATE TABLE {db_prefix}sphinx_ai_index');
			
			// Index recent posts
			$request = $this->smcFunc['db_query']('', '
				SELECT p.id_msg, p.id_topic, p.id_board, p.subject, p.body, p.poster_time
				FROM {db_prefix}messages AS p
				WHERE p.approved = 1
				ORDER BY p.poster_time DESC
				LIMIT 10000'
			);
			
			while ($row = $this->smcFunc['db_fetch_assoc']($request)) {
				// Process content with Sphinx
				$sphinx_index = $this->processWithSphinx((string) $row['body']);
				
				// Insert into index
				$this->smcFunc['db_insert']('',
					'{db_prefix}sphinx_ai_index',
					array(
						'topic_id' => 'int',
						'post_id' => 'int',
						'board_id' => 'int',
						'subject' => 'string',
						'content' => 'string',
						'sphinx_index' => 'string',
					),
					array(
						(int) $row['id_topic'],
						(int) $row['id_msg'],
						(int) $row['id_board'],
						(string) $row['subject'],
						(string) $row['body'],
						$sphinx_index,
					),
					array('id')
				);
				
				$indexed_count++;
			}
			
			$this->smcFunc['db_free_result']($request);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Database error during reindexing: ' . $e->getMessage());
		}
		
		return $indexed_count;
	}

	/**
	 * Index a single post
	 * 
	 * @param array $msgOptions Message options
	 * @param array $topicOptions Topic options
	 * @param array $posterOptions Poster options
	 * @return void
	 */
	public function indexPost(array $msgOptions, array $topicOptions, array $posterOptions): void
	{
		// Validate inputs
		if (empty($msgOptions['id']) || empty($msgOptions['body'])) {
			return;
		}
		
		try {
			// Process content with Sphinx
			$sphinx_index = $this->processWithSphinx((string) $msgOptions['body']);
			
			// Insert into index
			$this->smcFunc['db_insert']('',
				'{db_prefix}sphinx_ai_index',
				array(
					'topic_id' => 'int',
					'post_id' => 'int',
					'board_id' => 'int',
					'subject' => 'string',
					'content' => 'string',
					'sphinx_index' => 'string',
				),
				array(
					(int) $topicOptions['id'],
					(int) $msgOptions['id'],
					(int) $topicOptions['board'],
					(string) $msgOptions['subject'],
					(string) $msgOptions['body'],
					$sphinx_index,
				),
				array('id')
			);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Database error while indexing post: ' . $e->getMessage());
		}
	}

	/**
	 * Update post index when modified
	 * 
	 * @param array $msgOptions Message options
	 * @param array $topicOptions Topic options
	 * @param array $posterOptions Poster options
	 * @return void
	 */
	public function updatePostIndex(array $msgOptions, array $topicOptions, array $posterOptions): void
	{
		// Validate inputs
		if (empty($msgOptions['id']) || empty($msgOptions['body'])) {
			return;
		}
		
		try {
			// Process content with Sphinx
			$sphinx_index = $this->processWithSphinx((string) $msgOptions['body']);
			
			// Update index
			$this->smcFunc['db_query']('', '
				UPDATE {db_prefix}sphinx_ai_index 
				SET subject = {string:subject}, content = {string:content}, sphinx_index = {string:sphinx_index}
				WHERE post_id = {int:post_id}',
				array(
					'subject' => (string) $msgOptions['subject'],
					'content' => (string) $msgOptions['body'],
					'sphinx_index' => $sphinx_index,
					'post_id' => (int) $msgOptions['id'],
				)
			);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Database error while updating post index: ' . $e->getMessage());
		}
	}

	/**
	 * Remove posts from index when deleted
	 * 
	 * @param array $messages Array of message IDs
	 * @return void
	 */
	public function removeFromIndex(array $messages): void
	{
		// Validate inputs
		if (empty($messages)) {
			return;
		}
		
		try {
			// Convert to integers for safety
			$message_ids = array_map('intval', $messages);
			
			// Remove from index
			$this->smcFunc['db_query']('', '
				DELETE FROM {db_prefix}sphinx_ai_index 
				WHERE post_id IN ({array_int:messages})',
				array(
					'messages' => $message_ids,
				)
			);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Database error while removing from index: ' . $e->getMessage());
		}
	}

	/**
	 * Get index statistics
	 * 
	 * @return array Index statistics
	 */
	public function getIndexStats(): array
	{
		$stats = array('total_indexed' => 0, 'last_indexed' => null);
		
		try {
			$request = $this->smcFunc['db_query']('', '
				SELECT COUNT(*) as total_indexed, MAX(indexed_date) as last_indexed
				FROM {db_prefix}sphinx_ai_index'
			);
			$stats = $this->smcFunc['db_fetch_assoc']($request);
			$this->smcFunc['db_free_result']($request);
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Database error while retrieving index stats: ' . $e->getMessage());
		}
		
		return $stats;
	}

	/**
	 * Optimize search index
	 * 
	 * @return void
	 */
	public function optimizeIndex(): void
	{
		try {
			// Optimize the search index table
			$this->smcFunc['db_query']('', 'OPTIMIZE TABLE {db_prefix}sphinx_ai_index');
		} catch (Exception $e) {
			log_error('Sphinx AI Search: Database error while optimizing index: ' . $e->getMessage());
		}
	}

	/**
	 * Process content with Sphinx
	 * 
	 * @param string $content The content to process
	 * @return string The processed content
	 */
	private function processWithSphinx(string $content): string
	{
		// Basic text processing for Sphinx indexing
		if (empty($content)) {
			return '';
		}
		
		$content = strip_tags($content);
		$content = html_entity_decode($content);
		$content = preg_replace('/\s+/', ' ', $content);
		$content = trim($content);
		
		// Return processed content (in real implementation, this would use Sphinx API)
		return $content;
	}
}
