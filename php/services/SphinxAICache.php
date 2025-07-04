<?php

/**
 * SphinxAI Cache Service
 * Provides Redis-based caching for search results and model data
 * 
 * @package SphinxAI
 * @version 1.0.0
 * @author SMF Sphinx AI Search Plugin
 */

if (!defined('SMF')) {
    die('No direct access...');
}

class SphinxAICache
{
    /** @var Redis|null Redis connection instance */
    private $redis = null;
    
    /** @var bool Whether Redis is available and connected */
    private $isConnected = false;
    
    /** @var array Default cache configuration */
    private $config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0,
        'timeout' => 2.0,
        'read_timeout' => 2.0,
        'persistent' => true,
        'prefix' => 'sphinxai:'
    ];
    
    /** @var int Default TTL in seconds (1 hour) */
    private $defaultTtl = 3600;
    
    /** @var array Cache key prefixes for different data types */
    private const KEY_PREFIXES = [
        'search' => 'search:',
        'model' => 'model:',
        'config' => 'config:',
        'stats' => 'stats:',
        'suggestions' => 'suggestions:'
    ];
    
    /**
     * Constructor
     * @param array $config Redis configuration options
     */
    public function __construct(array $config = [])
    {
        global $modSettings;
        
        // Merge with SMF settings if available
        $smfConfig = [
            'host' => $modSettings['sphinx_ai_redis_host'] ?? '127.0.0.1',
            'port' => (int)($modSettings['sphinx_ai_redis_port'] ?? 6379),
            'password' => $modSettings['sphinx_ai_redis_password'] ?? null,
            'database' => (int)($modSettings['sphinx_ai_redis_db'] ?? 0),
            'prefix' => $modSettings['sphinx_ai_redis_prefix'] ?? 'sphinxai:'
        ];
        
        $this->config = array_merge($this->config, $smfConfig, $config);
        $this->connect();
    }
    
    /**
     * Establish Redis connection
     * @return bool True if connection successful
     */
    private function connect(): bool
    {
        if ($this->isConnected) {
            return true;
        }
        
        try {
            if (!extension_loaded('redis')) {
                error_log('SphinxAI Cache: Redis extension not available');
                return false;
            }
            
            $this->redis = new Redis();
            
            if ($this->config['persistent']) {
                $connected = $this->redis->pconnect(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['timeout'],
                    'sphinxai_' . md5($this->config['host'] . $this->config['port'])
                );
            } else {
                $connected = $this->redis->connect(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['timeout']
                );
            }
            
            if (!$connected) {
                error_log('SphinxAI Cache: Failed to connect to Redis');
                return false;
            }
            
            // Set read timeout
            $this->redis->setOption(Redis::OPT_READ_TIMEOUT, $this->config['read_timeout']);
            
            // Authenticate if password provided
            if (!empty($this->config['password'])) {
                if (!$this->redis->auth($this->config['password'])) {
                    error_log('SphinxAI Cache: Redis authentication failed');
                    return false;
                }
            }
            
            // Select database
            if ($this->config['database'] > 0) {
                $this->redis->select($this->config['database']);
            }
            
            // Set key prefix
            $this->redis->setOption(Redis::OPT_PREFIX, $this->config['prefix']);
            
            $this->isConnected = true;
            return true;
            
        } catch (Exception $e) {
            error_log('SphinxAI Cache: Redis connection error - ' . $e->getMessage());
            $this->isConnected = false;
            return false;
        }
    }
    
    /**
     * Check if Redis is available and connected
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->isConnected && $this->redis !== null;
    }
    
    /**
     * Generate cache key with proper prefix
     * @param string $type Cache type (search, model, etc.)
     * @param string $key Base key
     * @return string Full cache key
     */
    private function getCacheKey(string $type, string $key): string
    {
        $prefix = self::KEY_PREFIXES[$type] ?? '';
        return $prefix . hash('sha256', $key);
    }
    
    /**
     * Cache search results
     * @param string $query Search query
     * @param array $filters Search filters
     * @param array $results Search results
     * @param int|null $ttl Time to live in seconds
     * @return bool Success status
     */
    public function cacheSearchResults(string $query, array $filters, array $results, ?int $ttl = null): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }
        
        $key = $this->getCacheKey('search', json_encode([
            'query' => $query,
            'filters' => $filters,
            'version' => $this->getSearchVersion()
        ]));
        
        $data = [
            'query' => $query,
            'filters' => $filters,
            'results' => $results,
            'timestamp' => time(),
            'count' => count($results)
        ];
        
        try {
            return $this->redis->setex(
                $key,
                $ttl ?? $this->defaultTtl,
                json_encode($data, JSON_UNESCAPED_UNICODE)
            );
        } catch (Exception $e) {
            error_log('SphinxAI Cache: Failed to cache search results - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retrieve cached search results
     * @param string $query Search query
     * @param array $filters Search filters
     * @return array|null Cached results or null if not found
     */
    public function getCachedSearchResults(string $query, array $filters): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }
        
        $key = $this->getCacheKey('search', json_encode([
            'query' => $query,
            'filters' => $filters,
            'version' => $this->getSearchVersion()
        ]));
        
        try {
            $cached = $this->redis->get($key);
            if ($cached === false) {
                return null;
            }
            
            $data = json_decode($cached, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('SphinxAI Cache: Invalid JSON in cached data');
                return null;
            }
            
            return $data;
            
        } catch (Exception $e) {
            error_log('SphinxAI Cache: Failed to retrieve cached search results - ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Cache model configuration or metadata
     * @param string $modelId Model identifier
     * @param array $data Model data
     * @param int|null $ttl Time to live in seconds (default: 24 hours)
     * @return bool Success status
     */
    public function cacheModelData(string $modelId, array $data, ?int $ttl = null): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }
        
        $key = $this->getCacheKey('model', $modelId);
        $ttl = $ttl ?? (24 * 3600); // 24 hours default for model data
        
        try {
            return $this->redis->setex($key, $ttl, json_encode($data, JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            error_log('SphinxAI Cache: Failed to cache model data - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retrieve cached model data
     * @param string $modelId Model identifier
     * @return array|null Cached model data or null if not found
     */
    public function getCachedModelData(string $modelId): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }
        
        $key = $this->getCacheKey('model', $modelId);
        
        try {
            $cached = $this->redis->get($key);
            if ($cached === false) {
                return null;
            }
            
            return json_decode($cached, true);
            
        } catch (Exception $e) {
            error_log('SphinxAI Cache: Failed to retrieve cached model data - ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Cache search suggestions
     * @param string $prefix Query prefix
     * @param array $suggestions List of suggestions
     * @param int|null $ttl Time to live in seconds
     * @return bool Success status
     */
    public function cacheSuggestions(string $prefix, array $suggestions, ?int $ttl = null): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }
        
        $key = $this->getCacheKey('suggestions', strtolower(trim($prefix)));
        $ttl = $ttl ?? (6 * 3600); // 6 hours default for suggestions
        
        try {
            return $this->redis->setex($key, $ttl, json_encode($suggestions));
        } catch (Exception $e) {
            error_log('SphinxAI Cache: Failed to cache suggestions - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retrieve cached suggestions
     * @param string $prefix Query prefix
     * @return array|null Cached suggestions or null if not found
     */
    public function getCachedSuggestions(string $prefix): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }
        
        $key = $this->getCacheKey('suggestions', strtolower(trim($prefix)));
        
        try {
            $cached = $this->redis->get($key);
            if ($cached === false) {
                return null;
            }
            
            return json_decode($cached, true);
            
        } catch (Exception $e) {
            error_log('SphinxAI Cache: Failed to retrieve cached suggestions - ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update search statistics
     * @param string $query Search query
     * @param int $resultCount Number of results
     * @param float $responseTime Response time in milliseconds
     * @return bool Success status
     */
    public function updateSearchStats(string $query, int $resultCount, float $responseTime): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }
        
        try {
            $pipe = $this->redis->multi(Redis::PIPELINE);
            
            // Increment search count
            $pipe->incr('stats:search_count');
            
            // Track popular queries
            $pipe->zincrby('stats:popular_queries', 1, $query);
            
            // Track response times (keep last 1000 entries)
            $pipe->lpush('stats:response_times', $responseTime);
            $pipe->ltrim('stats:response_times', 0, 999);
            
            // Track result counts
            $pipe->lpush('stats:result_counts', $resultCount);
            $pipe->ltrim('stats:result_counts', 0, 999);
            
            // Daily stats
            $date = date('Y-m-d');
            $pipe->incr("stats:daily:$date:searches");
            $pipe->expire("stats:daily:$date:searches", 30 * 24 * 3600); // Keep for 30 days
            
            $pipe->exec();
            return true;
            
        } catch (Exception $e) {
            error_log('SphinxAI Cache: Failed to update search stats - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get search statistics
     * @return array Statistics data
     */
    public function getSearchStats(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }
        
        try {
            $pipe = $this->redis->multi(Redis::PIPELINE);
            
            $pipe->get('stats:search_count');
            $pipe->zrevrange('stats:popular_queries', 0, 9, true); // Top 10 queries
            $pipe->lrange('stats:response_times', 0, 99); // Last 100 response times
            $pipe->lrange('stats:result_counts', 0, 99); // Last 100 result counts
            
            $results = $pipe->exec();
            
            $responseTimes = array_map('floatval', $results[2] ?? []);
            $resultCounts = array_map('intval', $results[3] ?? []);
            
            return [
                'total_searches' => (int)($results[0] ?? 0),
                'popular_queries' => $results[1] ?? [],
                'avg_response_time' => !empty($responseTimes) ? array_sum($responseTimes) / count($responseTimes) : 0,
                'avg_result_count' => !empty($resultCounts) ? array_sum($resultCounts) / count($resultCounts) : 0,
                'cache_hit_rate' => $this->getCacheHitRate()
            ];
            
        } catch (Exception $e) {
            error_log('SphinxAI Cache: Failed to get search stats - ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate cache hit rate
     * @return float Hit rate percentage
     */
    private function getCacheHitRate(): float
    {
        try {
            $hits = (int)$this->redis->get('stats:cache_hits') ?: 0;
            $misses = (int)$this->redis->get('stats:cache_misses') ?: 0;
            $total = $hits + $misses;
            
            return $total > 0 ? ($hits / $total) * 100 : 0;
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Increment cache hit counter
     */
    public function recordCacheHit(): void
    {
        if ($this->isAvailable()) {
            try {
                $this->redis->incr('stats:cache_hits');
            } catch (Exception $e) {
                // Ignore errors for stats
            }
        }
    }
    
    /**
     * Increment cache miss counter
     */
    public function recordCacheMiss(): void
    {
        if ($this->isAvailable()) {
            try {
                $this->redis->incr('stats:cache_misses');
            } catch (Exception $e) {
                // Ignore errors for stats
            }
        }
    }
    
    /**
     * Clear cache by pattern
     * @param string $pattern Cache key pattern (without prefix)
     * @return int Number of keys deleted
     */
    public function clearCache(string $pattern = '*'): int
    {
        if (!$this->isAvailable()) {
            return 0;
        }
        
        try {
            $keys = $this->redis->keys($pattern);
            if (empty($keys)) {
                return 0;
            }
            
            return $this->redis->del($keys);
            
        } catch (Exception $e) {
            error_log('SphinxAI Cache: Failed to clear cache - ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Clear all search result cache
     * @return int Number of keys deleted
     */
    public function clearSearchCache(): int
    {
        return $this->clearCache(self::KEY_PREFIXES['search'] . '*');
    }
    
    /**
     * Get search version for cache invalidation
     * @return string Version string
     */
    private function getSearchVersion(): string
    {
        global $modSettings;
        
        return md5(
            ($modSettings['sphinx_ai_model_path'] ?? '') .
            ($modSettings['sphinx_ai_model_type'] ?? '') .
            ($modSettings['sphinx_ai_max_results'] ?? '50')
        );
    }
    
    /**
     * Close Redis connection
     */
    public function close(): void
    {
        if ($this->redis && $this->isConnected) {
            try {
                if (!$this->config['persistent']) {
                    $this->redis->close();
                }
            } catch (Exception $e) {
                // Ignore close errors
            }
            
            $this->isConnected = false;
        }
    }
    
    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct()
    {
        $this->close();
    }
}
