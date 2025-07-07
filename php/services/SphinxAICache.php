<?php

/**
 * SphinxAI Cache Service
 * Provides SMF cache API-based caching for search results and model data
 * Uses SMF's built-in cache system (Redis, Memcached, or file-based)
 * 
 * @package SphinxAI
 * @version 2.0.0
 * @author SMF Sphinx AI Search Plugin
 */

declare(strict_types=1);

if (!defined('SMF')) {
    die('No direct access...');
}

require_once dirname(__DIR__) . '/core/SphinxAIEnums.php';

class SphinxAICache
{
    private readonly bool $isAvailable;
    private readonly string $prefix;

    /**
     * Constructor with enhanced initialization
     */
    public function __construct(
        private readonly int $defaultTtl = 3600
    ) {
        global $modSettings, $db_prefix;
        
        // Initialize availability based on SMF cache
        $this->isAvailable = !empty($modSettings['cache_enable']);
        
        // Use SMF table prefix for cache keys
        $this->prefix = !empty($db_prefix) ? $db_prefix . 'sphinxai_' : 'sphinxai_';
    }

    /**
     * Check if cache is available
     */
    public function isAvailable(): bool
    {
        return $this->isAvailable && function_exists('cache_put_data') && function_exists('cache_get_data');
    }

    /**
     * Generate cache key with proper prefix using enum
     */
    private function getCacheKey(CacheKeyType $type, string $key): string
    {
        return $this->prefix . $type->value . hash('sha256', $key);
    }

    /**
     * Put data into SMF cache with union types
     */
    private function putCache(string $key, array|string|int|float|bool|null $data, int $ttl): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }
        
        try {
            cache_put_data($key, $data, $ttl);
            return true;
        } catch (Exception $e) {
            error_log('SphinxAI Cache: Failed to put cache data - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get data from SMF cache with union return type
     */
    private function getCache(string $key): array|string|int|float|bool|null
    {
        if (!$this->isAvailable()) {
            return null;
        }
        
        try {
            return cache_get_data($key);
        } catch (Exception $e) {
            error_log('SphinxAI Cache: Failed to get cache data - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Set cache value with TTL
     */
    private function setCache(string $key, mixed $value): void
            error_log('SphinxAI Cache: Failed to get cache data - ' . $e->getMessage());
            return null;
        }
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
        
        return $this->putCache($key, $data, $ttl ?? $this->defaultTtl);
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
        
        $cached = $this->getCache($key);
        if ($cached === null || $cached === false) {
            return null;
        }
        
        // Validate cached data structure
        if (!is_array($cached) || !isset($cached['results'])) {
            error_log('SphinxAI Cache: Invalid cached search results structure');
            return null;
        }
        
        return $cached;
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
        
        return $this->putCache($key, $data, $ttl);
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
        $cached = $this->getCache($key);
        
        return ($cached === false || $cached === null) ? null : $cached;
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
        
        return $this->putCache($key, $suggestions, $ttl);
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
        $cached = $this->getCache($key);
        
        return ($cached === false || $cached === null) ? null : $cached;
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
            // Get existing stats
            $statsKey = $this->getCacheKey('stats', 'search_stats');
            $stats = $this->getCache($statsKey) ?: [
                'search_count' => 0,
                'popular_queries' => [],
                'response_times' => [],
                'result_counts' => [],
                'daily_stats' => []
            ];
            
            // Update search count
            $stats['search_count']++;
            
            // Track popular queries (keep top 100)
            $queryHash = hash('sha256', $query);
            if (!isset($stats['popular_queries'][$queryHash])) {
                $stats['popular_queries'][$queryHash] = ['query' => $query, 'count' => 0];
            }
            $stats['popular_queries'][$queryHash]['count']++;
            
            // Sort and keep top 100 popular queries
            uasort($stats['popular_queries'], function($a, $b) {
                return $b['count'] - $a['count'];
            });
            $stats['popular_queries'] = array_slice($stats['popular_queries'], 0, 100, true);
            
            // Track response times (keep last 1000 entries)
            $stats['response_times'][] = $responseTime;
            if (count($stats['response_times']) > 1000) {
                $stats['response_times'] = array_slice($stats['response_times'], -1000);
            }
            
            // Track result counts
            $stats['result_counts'][] = $resultCount;
            if (count($stats['result_counts']) > 1000) {
                $stats['result_counts'] = array_slice($stats['result_counts'], -1000);
            }
            
            // Daily stats
            $date = date('Y-m-d');
            if (!isset($stats['daily_stats'][$date])) {
                $stats['daily_stats'][$date] = 0;
            }
            $stats['daily_stats'][$date]++;
            
            // Keep only last 30 days
            $cutoffDate = date('Y-m-d', strtotime('-30 days'));
            foreach ($stats['daily_stats'] as $statsDate => $count) {
                if ($statsDate < $cutoffDate) {
                    unset($stats['daily_stats'][$statsDate]);
                }
            }
            
            // Save updated stats
            return $this->putCache($statsKey, $stats, 24 * 3600); // 24 hours
            
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
            $statsKey = $this->getCacheKey('stats', 'search_stats');
            $stats = $this->getCache($statsKey);
            
            if (!$stats) {
                return [
                    'total_searches' => 0,
                    'popular_queries' => [],
                    'avg_response_time' => 0,
                    'avg_result_count' => 0,
                    'cache_hit_rate' => 0
                ];
            }
            
            $responseTimes = $stats['response_times'] ?? [];
            $resultCounts = $stats['result_counts'] ?? [];
            $popularQueries = [];
            
            // Format popular queries for output
            foreach ($stats['popular_queries'] ?? [] as $queryData) {
                $popularQueries[$queryData['query']] = $queryData['count'];
            }
            
            return [
                'total_searches' => (int)($stats['search_count'] ?? 0),
                'popular_queries' => $popularQueries,
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
            $hitStatsKey = $this->getCacheKey('stats', 'cache_hits');
            $hitStats = $this->getCache($hitStatsKey) ?: ['hits' => 0, 'misses' => 0];
            
            $hits = $hitStats['hits'];
            $misses = $hitStats['misses'];
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
                $hitStatsKey = $this->getCacheKey('stats', 'cache_hits');
                $hitStats = $this->getCache($hitStatsKey) ?: ['hits' => 0, 'misses' => 0];
                $hitStats['hits']++;
                $this->putCache($hitStatsKey, $hitStats, 24 * 3600);
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
                $hitStatsKey = $this->getCacheKey('stats', 'cache_hits');
                $hitStats = $this->getCache($hitStatsKey) ?: ['hits' => 0, 'misses' => 0];
                $hitStats['misses']++;
                $this->putCache($hitStatsKey, $hitStats, 24 * 3600);
            } catch (Exception $e) {
                // Ignore errors for stats
            }
        }
    }
    
    /**
     * Clear cache by pattern
     * Note: SMF cache doesn't support pattern-based clearing like Redis,
     * so this method clears specific known cache keys
     * @param string $pattern Cache key pattern (limited support)
     * @return int Number of keys attempted to clear
     */
    public function clearCache(string $pattern = '*'): int
    {
        if (!$this->isAvailable()) {
            return 0;
        }
        
        $cleared = 0;
        
        try {
            // Clear known cache types based on pattern
            $typesToClear = [];
            
            if ($pattern === '*' || strpos($pattern, 'search') !== false) {
                $typesToClear[] = 'search';
            }
            if ($pattern === '*' || strpos($pattern, 'model') !== false) {
                $typesToClear[] = 'model';
            }
            if ($pattern === '*' || strpos($pattern, 'suggestions') !== false) {
                $typesToClear[] = 'suggestions';
            }
            if ($pattern === '*' || strpos($pattern, 'stats') !== false) {
                $typesToClear[] = 'stats';
            }
            
            // Note: Since SMF cache doesn't support pattern-based clearing,
            // we can only clear by removing specific known keys.
            // This is a limitation compared to direct Redis usage.
            
            foreach ($typesToClear as $type) {
                // Clear the main stats key for each type
                $key = $this->prefix . self::KEY_PREFIXES[$type] . 'main';
                if (function_exists('clean_cache')) {
                    clean_cache($key);
                    $cleared++;
                }
            }
            
            return $cleared;
            
        } catch (Exception $e) {
            error_log('SphinxAI Cache: Failed to clear cache - ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Clear all search result cache
     * @return int Number of keys attempted to clear
     */
    public function clearSearchCache(): int
    {
        return $this->clearCache('search');
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
     * Close connection (no-op for SMF cache)
     */
    public function close(): void
    {
        // SMF cache doesn't require explicit connection closing
    }
    
    /**
     * Destructor (no-op for SMF cache)
     */
    public function __destruct()
    {
        // SMF cache doesn't require cleanup
    }
}
