<?php
/**
 * Sphinx AI Search Plugin for SMF - Core Service
 * 
 * @package SphinxAISearch
 * @version 2.0.0
 * @author SMF Plugin Team
 * @license MIT
 */

declare(strict_types=1);

if (!defined('SMF'))
    die('Hacking attempt...');

require_once dirname(__DIR__) . '/services/SphinxAICache.php';

/**
 * Core service for AI search functionality
 * 
 * Following Single Responsibility Principle - this class only handles
 * the coordina/**
 * Factory function to create SphinxAISearchService instance
 * 
 * @param array $modSettings Forum settings
 * @param array $smcFunc SMF database functions
 * @return SphinxAISearchService Configured service instance
 */
function createSphinxAISearchService(array $modSettings, array $smcFunc): SphinxAISearchService
{
    $pythonPath = $modSettings['sphinxai_python_path'] ?? 'python';
    $scriptPath = $modSettings['sphinxai_script_path'] ?? '';
    $timeout = (int)($modSettings['sphinxai_timeout'] ?? 30);

    return new SphinxAISearchService($modSettings, $smcFunc, $pythonPath, $scriptPath, $timeout);
}ython backend and SMF frontend.
 */
class SphinxAISearchService
{
    private string $pythonPath;
    private string $scriptPath;
    private array $modSettings;
    private array $smcFunc;
    private int $timeout;
    private array $lastError;
    private SphinxAICache $cache;

    /**
     * Constructor
     * 
     * @param array $modSettings Forum settings
     * @param array $smcFunc SMF database functions
     * @param string $pythonPath Path to Python executable
     * @param string $scriptPath Path to Python script
     * @param int $timeout Request timeout in seconds
     */
    public function __construct(
        array $modSettings,
        array $smcFunc,
        string $pythonPath = 'python',
        string $scriptPath = '',
        int $timeout = 30
    ) {
        $this->modSettings = $modSettings;
        $this->smcFunc = $smcFunc;
        $this->pythonPath = $pythonPath;
        $this->scriptPath = $scriptPath ?: dirname(__DIR__) . '/SphinxAI/main.py';
        $this->timeout = $timeout;
        $this->lastError = [];
        $this->cache = new SphinxAICache();
    }

    /**
     * Perform AI-enhanced search
     * 
     * @param string $query Search query
     * @param array $options Search options
     * @return array Search results or error
     */
    public function search(string $query, array $options = []): array
    {
        $startTime = microtime(true);
        
        // Validate input
        if (empty(trim($query))) {
            return $this->createErrorResponse('Empty query provided');
        }

        // Prepare request data
        $requestData = [
            'query' => trim($query),
            'options' => array_merge([
                'type' => 'hybrid',
                'use_ai_summary' => true,
                'use_genai' => true,
                'max_results' => $this->modSettings['sphinxai_max_results'] ?? 10
            ], $options)
        ];

        // Try to get from cache first
        $cachedResults = $this->cache->getCachedSearchResults($requestData['query'], $requestData['options']);
        if ($cachedResults !== null) {
            $this->cache->recordCacheHit();
            
            // Update response time tracking
            $responseTime = (microtime(true) - $startTime) * 1000;
            $this->cache->updateSearchStats($requestData['query'], count($cachedResults['results'] ?? []), $responseTime);
            
            return $cachedResults;
        }

        $this->cache->recordCacheMiss();

        // Execute Python script
        $searchResults = $this->executePythonScript('search', $requestData);
        
        // Enrich results with SMF database content if needed
        if ($searchResults['success'] ?? false) {
            $searchResults = $this->enrichResultsWithContent($searchResults);
            
            // Cache successful results
            if (!empty($searchResults['data'])) {
                $cacheData = [
                    'success' => true,
                    'data' => $searchResults['data'],
                    'results' => $searchResults['data'], // For backward compatibility
                    'cached_at' => time()
                ];
                
                // Cache for 1 hour by default, configurable via settings
                $cacheTtl = (int)($this->modSettings['sphinxai_cache_ttl'] ?? 3600);
                $this->cache->cacheSearchResults($requestData['query'], $requestData['options'], $cacheData, $cacheTtl);
            }
        }

        // Update search statistics
        $responseTime = (microtime(true) - $startTime) * 1000;
        $resultCount = count($searchResults['data'] ?? []);
        $this->cache->updateSearchStats($requestData['query'], $resultCount, $responseTime);
        
        return $searchResults;
    }

    /**
     * Get system status
     * 
     * @return array System status
     */
    public function getStatus(): array
    {
        return $this->executePythonScript('status', []);
    }

    /**
     * Execute Python script with data
     * 
     * @param string $action Action to perform
     * @param array $data Request data
     * @return array Response from Python script
     */
    private function executePythonScript(string $action, array $data): array
    {
        $inputFile = null;
        $process = null;
        
        try {
            // Validate action parameter against whitelist
            $allowedActions = ['search', 'status', 'index', 'suggestions'];
            if (!in_array($action, $allowedActions, true)) {
                return $this->createErrorResponse('Invalid action specified');
            }

            // Create secure temporary input file
            $inputFile = $this->createSecureTempFile();
            if ($inputFile === false) {
                return $this->createErrorResponse('Failed to create temporary file');
            }

            // Write request data to file with proper permissions
            $requestJson = json_encode($data, JSON_UNESCAPED_UNICODE);
            if (file_put_contents($inputFile, $requestJson, LOCK_EX) === false) {
                return $this->createErrorResponse('Failed to write request data');
            }
            
            // Set restrictive permissions on temp file
            chmod($inputFile, 0600);

            // Build command arguments array (safer than shell command)
            $commandArgs = [
                $this->pythonPath,
                $this->scriptPath,
                $action,
                '--input',
                $inputFile
            ];

            // Add config if available
            $configPath = $this->getConfigPath();
            if (!empty($configPath) && $this->isValidConfigPath($configPath)) {
                $commandArgs[] = '--config';
                $commandArgs[] = $configPath;
            }

            // Execute with proc_open for better security and control
            $response = $this->executeSecureProcess($commandArgs);
            
            return $response;

        } catch (Exception $e) {
            log_error('Sphinx AI Search: Script execution error: ' . $e->getMessage());
            return $this->createErrorResponse('Script execution failed');
        } finally {
            // Always clean up temporary file
            if ($inputFile && file_exists($inputFile)) {
                unlink($inputFile);
            }
        }
    }

    /**
     * Create secure temporary file
     * 
     * @return string|false Temporary file path or false on failure
     */
    private function createSecureTempFile()
    {
        // Use more secure random prefix
        $prefix = 'sphinxai_' . bin2hex(random_bytes(8)) . '_';
        return tempnam(sys_get_temp_dir(), $prefix);
    }

    /**
     * Execute process securely using proc_open
     * 
     * @param array $commandArgs Command arguments array
     * @return array Response from process
     */
    private function executeSecureProcess(array $commandArgs): array
    {
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout  
            2 => ['pipe', 'w']  // stderr
        ];

        $process = proc_open($commandArgs, $descriptors, $pipes, null, null);
        
        if (!is_resource($process)) {
            return $this->createErrorResponse('Failed to start Python process');
        }

        try {
            // Close stdin
            fclose($pipes[0]);

            // Set timeout for reading
            stream_set_timeout($pipes[1], $this->timeout);
            stream_set_timeout($pipes[2], $this->timeout);

            // Read stdout and stderr
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);

            fclose($pipes[1]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);

            // Parse response
            if ($returnCode !== 0) {
                // Log detailed error but don't expose to user
                log_error('Sphinx AI Search: Python process failed with code ' . $returnCode . ': ' . $stderr);
                return $this->createErrorResponse('AI processing failed');
            }

            $response = json_decode($stdout, true);

            if ($response === null) {
                log_error('Sphinx AI Search: Invalid JSON response from Python script');
                return $this->createErrorResponse('Invalid response from AI service');
            }

            return $response;

        } catch (Exception $e) {
            proc_terminate($process);
            proc_close($process);
            throw $e;
        }
    }

    /**
     * Validate config file path
     * 
     * @param string $configPath Path to validate
     * @return bool True if path is valid
     */
    private function isValidConfigPath(string $configPath): bool
    {
        // Ensure config path is within allowed directory and has valid extension
        $allowedDir = dirname($this->scriptPath);
        $realConfigPath = realpath($configPath);
        $realAllowedDir = realpath($allowedDir);
        
        return $realConfigPath !== false && 
               $realAllowedDir !== false &&
               strpos($realConfigPath, $realAllowedDir) === 0 &&
               pathinfo($configPath, PATHINFO_EXTENSION) === 'ini';
    }

    /**
     * Get configuration file path
     * 
     * @return string Config file path or empty string
     */
    private function getConfigPath(): string
    {
        $configPath = dirname(__DIR__) . '/SphinxAI/config/search_config.json';
        return file_exists($configPath) ? $configPath : '';
    }

    /**
     * Create error response
     * 
     * @param string $message Error message
     * @return array Error response
     */
    private function createErrorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'data' => [],
            'debug_info' => $this->lastError
        ];
    }

    /**
     * Get last error information
     * 
     * @return array Last error details
     */
    public function getLastError(): array
    {
        return $this->lastError;
    }

    /**
     * Test system configuration
     * 
     * @return array Test results
     */
    public function testConfiguration(): array
    {
        $results = [
            'python_available' => false,
            'script_exists' => false,
            'script_executable' => false,
            'config_exists' => false,
            'dependencies_ok' => false
        ];

        // Test Python availability
        $output = [];
        $returnCode = 0;
        exec(sprintf('%s --version 2>&1', escapeshellarg($this->pythonPath)), $output, $returnCode);
        $results['python_available'] = ($returnCode === 0);
        $results['python_version'] = $results['python_available'] ? $output[0] ?? 'Unknown' : 'Not available';

        // Test script existence
        $results['script_exists'] = file_exists($this->scriptPath);

        // Test script execution
        if ($results['python_available'] && $results['script_exists']) {
            $output = [];
            $returnCode = 0;
            exec(sprintf('%s %s status 2>&1', 
                escapeshellarg($this->pythonPath), 
                escapeshellarg($this->scriptPath)), $output, $returnCode);
            $results['script_executable'] = ($returnCode === 0);
        }

        // Test config
        $configPath = $this->getConfigPath();
        $results['config_exists'] = !empty($configPath);
        $results['config_path'] = $configPath;

        // Overall status
        $results['system_ready'] = $results['python_available'] && 
                                   $results['script_exists'] && 
                                   $results['script_executable'];

        return $results;
    }

    /**
     * Enrich search results with content from SMF database when Sphinx returns only IDs
     * 
     * @param array $searchResults Search results from Python backend
     * @return array Enhanced results with content from SMF database
     */
    private function enrichResultsWithContent(array $searchResults): array
    {
        if (empty($searchResults) || !isset($searchResults['data']['results'])) {
            return $searchResults;
        }
        
        $results = $searchResults['data']['results'];
        $postIds = [];
        $topicIds = [];
        
        // Extract IDs from results that need content fetching
        foreach ($results as $result) {
            if (isset($result['needs_content_fetch']) && $result['needs_content_fetch']) {
                if (!empty($result['post_id'])) {
                    $postIds[] = (int)$result['post_id'];
                }
                if (!empty($result['topic_id'])) {
                    $topicIds[] = (int)$result['topic_id'];
                }
            }
        }
        
        if (empty($postIds) && empty($topicIds)) {
            return $searchResults; // No content fetching needed
        }
        
        // Fetch content from SMF database
        $contentMap = $this->fetchPostContent($postIds, $topicIds);
        
        // Merge Sphinx results with SMF content
        foreach ($results as &$result) {
            if (isset($result['needs_content_fetch']) && $result['needs_content_fetch']) {
                $postId = $result['post_id'] ?? null;
                $topicId = $result['topic_id'] ?? null;
                
                if ($postId && isset($contentMap['posts'][$postId])) {
                    $content = $contentMap['posts'][$postId];
                    $result['content'] = $content['body'];
                    $result['subject'] = $content['subject'];
                    $result['poster_name'] = $content['poster_name'];
                    $result['post_time'] = $content['post_time'];
                    $result['board_name'] = $content['board_name'];
                } elseif ($topicId && isset($contentMap['topics'][$topicId])) {
                    $content = $contentMap['topics'][$topicId];
                    $result['subject'] = $content['subject'];
                    $result['board_name'] = $content['board_name'];
                    $result['num_replies'] = $content['num_replies'];
                    $result['num_views'] = $content['num_views'];
                }
            }
        }
        
        $searchResults['data']['results'] = $results;
        return $searchResults;
    }
    
    /**
     * Fetch post and topic content from SMF database
     * 
     * @param array $postIds Array of post IDs to fetch
     * @param array $topicIds Array of topic IDs to fetch
     * @return array Content map with posts and topics
     */
    private function fetchPostContent(array $postIds, array $topicIds): array
    {
        $contentMap = ['posts' => [], 'topics' => []];
        
        // Fetch post content
        if (!empty($postIds)) {
            $postIds = array_unique(array_filter($postIds));
            $request = $this->smcFunc['db_query']('', '
                SELECT m.id_msg, m.subject, m.body, m.id_topic, m.id_board,
                       m.poster_time, m.poster_name,
                       b.name as board_name, b.id_board
                FROM {db_prefix}messages AS m
                LEFT JOIN {db_prefix}boards AS b ON b.id_board = m.id_board
                WHERE m.id_msg IN ({array_int:message_ids})
                ORDER BY m.poster_time DESC',
                [
                    'message_ids' => $postIds
                ]
            );
            
            while ($row = $this->smcFunc['db_fetch_assoc']($request)) {
                $contentMap['posts'][$row['id_msg']] = [
                    'subject' => $row['subject'],
                    'body' => $row['body'],
                    'topic_id' => $row['id_topic'],
                    'board_id' => $row['id_board'],
                    'board_name' => $row['board_name'],
                    'poster_name' => $row['poster_name'],
                    'post_time' => $row['poster_time']
                ];
            }
            $this->smcFunc['db_free_result']($request);
        }
        
        // Fetch topic content
        if (!empty($topicIds)) {
            $topicIds = array_unique(array_filter($topicIds));
            $request = $this->smcFunc['db_query']('', '
                SELECT t.id_topic, t.subject, t.num_replies, t.num_views, t.id_board,
                       b.name as board_name,
                       m.body as first_message_body, m.poster_name as topic_starter
                FROM {db_prefix}topics AS t
                LEFT JOIN {db_prefix}boards AS b ON b.id_board = t.id_board
                LEFT JOIN {db_prefix}messages AS m ON m.id_msg = t.id_first_msg
                WHERE t.id_topic IN ({array_int:topic_ids})
                ORDER BY t.id_last_msg DESC',
                [
                    'topic_ids' => $topicIds
                ]
            );
            
            while ($row = $this->smcFunc['db_fetch_assoc']($request)) {
                $contentMap['topics'][$row['id_topic']] = [
                    'subject' => $row['subject'],
                    'board_id' => $row['id_board'],
                    'board_name' => $row['board_name'],
                    'num_replies' => $row['num_replies'],
                    'num_views' => $row['num_views'],
                    'first_message_body' => $row['first_message_body'],
                    'topic_starter' => $row['topic_starter']
                ];
            }
            $this->smcFunc['db_free_result']($request);
        }
        
        return $contentMap;
    }
}

/**
 * Factory function to create service instance
 * 
 * @param array $modSettings Forum settings
 * @return SphinxAISearchService Service instance
 */
function createSphinxAISearchService(array $modSettings): SphinxAISearchService
{
    $pythonPath = $modSettings['sphinxai_python_path'] ?? 'python';
    $scriptPath = $modSettings['sphinxai_script_path'] ?? '';
    $timeout = (int)($modSettings['sphinxai_timeout'] ?? 30);

    return new SphinxAISearchService($modSettings, $pythonPath, $scriptPath, $timeout);
}
