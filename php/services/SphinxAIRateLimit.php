<?php

/**
 * Rate Limiting Service for SphinxAI
 * Provides Redis-based rate limiting for API endpoints
 * 
 * @package SphinxAI
 * @version 1.0.0
 * @author SMF Sphinx AI Search Plugin
 */

if (!defined('SMF')) {
    die('No direct access...');
}

require_once dirname(__DIR__) . '/services/SphinxAICache.php';

class SphinxAIRateLimit
{
    /** @var SphinxAICache Cache service for storing rate limit data */
    private $cache;
    
    /** @var array Default rate limit configuration */
    private $config = [
        'search' => [
            'requests' => 30,     // requests per window
            'window' => 60,       // window in seconds (1 minute)
            'block_duration' => 300 // block duration in seconds (5 minutes)
        ],
        'suggestions' => [
            'requests' => 60,     // requests per window
            'window' => 60,       // window in seconds (1 minute)
            'block_duration' => 300
        ],
        'admin' => [
            'requests' => 100,    // requests per window
            'window' => 60,       // window in seconds (1 minute)
            'block_duration' => 600 // block duration in seconds (10 minutes)
        ]
    ];
    
    /**
     * Constructor
     * @param array $customConfig Custom rate limit configuration
     */
    public function __construct(array $customConfig = [])
    {
        global $modSettings;
        
        $this->cache = new SphinxAICache();
        
        // Merge with SMF settings if available
        $smfConfig = [
            'search' => [
                'requests' => (int)($modSettings['sphinx_ai_rate_limit_search_requests'] ?? 30),
                'window' => (int)($modSettings['sphinx_ai_rate_limit_search_window'] ?? 60),
                'block_duration' => (int)($modSettings['sphinx_ai_rate_limit_search_block'] ?? 300)
            ],
            'suggestions' => [
                'requests' => (int)($modSettings['sphinx_ai_rate_limit_suggestions_requests'] ?? 60),
                'window' => (int)($modSettings['sphinx_ai_rate_limit_suggestions_window'] ?? 60),
                'block_duration' => (int)($modSettings['sphinx_ai_rate_limit_suggestions_block'] ?? 300)
            ],
            'admin' => [
                'requests' => (int)($modSettings['sphinx_ai_rate_limit_admin_requests'] ?? 100),
                'window' => (int)($modSettings['sphinx_ai_rate_limit_admin_window'] ?? 60),
                'block_duration' => (int)($modSettings['sphinx_ai_rate_limit_admin_block'] ?? 600)
            ]
        ];
        
        $this->config = array_merge($this->config, $smfConfig, $customConfig);
    }
    
    /**
     * Check if request is allowed under rate limit
     * 
     * @param string $action Action being performed (search, suggestions, admin)
     * @param string $identifier User identifier (IP, user ID, etc.)
     * @return array Rate limit status
     */
    public function checkRateLimit(string $action, string $identifier): array
    {
        if (!$this->cache->isAvailable()) {
            // If cache is not available, allow the request but log it
            error_log('SphinxAI Rate Limit: Cache not available, allowing request');
            return [
                'allowed' => true,
                'remaining' => 999,
                'reset_time' => time() + 60,
                'blocked_until' => null
            ];
        }
        
        $actionConfig = $this->config[$action] ?? $this->config['search'];
        $currentTime = time();
        $windowStart = $currentTime - $actionConfig['window'];
        
        // Generate cache keys
        $requestKey = $this->getRequestKey($action, $identifier);
        $blockKey = $this->getBlockKey($action, $identifier);
        
        // Check if user is currently blocked
        $blockedUntil = $this->cache->getCachedModelData($blockKey);
        if ($blockedUntil && $blockedUntil['blocked_until'] > $currentTime) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => $blockedUntil['blocked_until'],
                'blocked_until' => $blockedUntil['blocked_until'],
                'reason' => 'Rate limit exceeded'
            ];
        }
        
        // Clean up expired block if it exists
        if ($blockedUntil && $blockedUntil['blocked_until'] <= $currentTime) {
            $this->cache->clearCache($blockKey);
        }
        
        // Get current request count in the window
        $requestData = $this->cache->getCachedModelData($requestKey);
        if (!$requestData) {
            $requestData = [
                'count' => 0,
                'window_start' => $currentTime,
                'requests' => []
            ];
        }
        
        // Clean up old requests outside the current window
        $requestData['requests'] = array_filter(
            $requestData['requests'], 
            function($timestamp) use ($windowStart) {
                return $timestamp >= $windowStart;
            }
        );
        
        $currentCount = count($requestData['requests']);
        
        // Check if limit is exceeded
        if ($currentCount >= $actionConfig['requests']) {
            // Block the user
            $blockUntil = $currentTime + $actionConfig['block_duration'];
            $this->cache->cacheModelData($blockKey, [
                'blocked_until' => $blockUntil,
                'reason' => 'Rate limit exceeded',
                'action' => $action,
                'identifier' => $identifier
            ], $actionConfig['block_duration']);
            
            // Log the rate limit violation
            $this->logRateLimit($action, $identifier, $currentCount, $actionConfig['requests']);
            
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => $blockUntil,
                'blocked_until' => $blockUntil,
                'reason' => 'Rate limit exceeded'
            ];
        }
        
        // Allow the request and record it
        $requestData['requests'][] = $currentTime;
        $requestData['count'] = count($requestData['requests']);
        
        // Cache the updated request data
        $this->cache->cacheModelData($requestKey, $requestData, $actionConfig['window']);
        
        // Calculate when the rate limit resets
        $oldestRequest = min($requestData['requests']);
        $resetTime = $oldestRequest + $actionConfig['window'];
        
        return [
            'allowed' => true,
            'remaining' => $actionConfig['requests'] - $requestData['count'],
            'reset_time' => $resetTime,
            'blocked_until' => null
        ];
    }
    
    /**
     * Record a successful request for rate limiting
     * 
     * @param string $action Action being performed
     * @param string $identifier User identifier
     * @return bool Success status
     */
    public function recordRequest(string $action, string $identifier): bool
    {
        if (!$this->cache->isAvailable()) {
            return false;
        }
        
        $result = $this->checkRateLimit($action, $identifier);
        return $result['allowed'];
    }
    
    /**
     * Get remaining requests for an identifier
     * 
     * @param string $action Action being performed
     * @param string $identifier User identifier
     * @return int Remaining requests
     */
    public function getRemainingRequests(string $action, string $identifier): int
    {
        $result = $this->checkRateLimit($action, $identifier);
        return max(0, $result['remaining']);
    }
    
    /**
     * Check if identifier is currently blocked
     * 
     * @param string $action Action being performed
     * @param string $identifier User identifier
     * @return array|null Block information or null if not blocked
     */
    public function isBlocked(string $action, string $identifier): ?array
    {
        if (!$this->cache->isAvailable()) {
            return null;
        }
        
        $blockKey = $this->getBlockKey($action, $identifier);
        $blockData = $this->cache->getCachedModelData($blockKey);
        
        if ($blockData && $blockData['blocked_until'] > time()) {
            return $blockData;
        }
        
        return null;
    }
    
    /**
     * Clear rate limit for an identifier (admin function)
     * 
     * @param string $action Action to clear
     * @param string $identifier User identifier
     * @return bool Success status
     */
    public function clearRateLimit(string $action, string $identifier): bool
    {
        if (!$this->cache->isAvailable()) {
            return false;
        }
        
        $requestKey = $this->getRequestKey($action, $identifier);
        $blockKey = $this->getBlockKey($action, $identifier);
        
        $cleared = 0;
        $cleared += $this->cache->clearCache($requestKey);
        $cleared += $this->cache->clearCache($blockKey);
        
        return $cleared > 0;
    }
    
    /**
     * Get rate limit statistics for monitoring
     * 
     * @return array Statistics data
     */
    public function getStatistics(): array
    {
        if (!$this->cache->isAvailable()) {
            return [];
        }
        
        try {
            // This would require additional tracking in a real implementation
            return [
                'total_requests' => 0,
                'blocked_requests' => 0,
                'active_blocks' => 0,
                'top_blocked_ips' => []
            ];
        } catch (Exception $e) {
            error_log('SphinxAI Rate Limit: Failed to get statistics - ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate cache key for request tracking
     * 
     * @param string $action Action being performed
     * @param string $identifier User identifier
     * @return string Cache key
     */
    private function getRequestKey(string $action, string $identifier): string
    {
        return "rate_limit:requests:{$action}:" . hash('sha256', $identifier);
    }
    
    /**
     * Generate cache key for block tracking
     * 
     * @param string $action Action being performed
     * @param string $identifier User identifier
     * @return string Cache key
     */
    private function getBlockKey(string $action, string $identifier): string
    {
        return "rate_limit:blocks:{$action}:" . hash('sha256', $identifier);
    }
    
    /**
     * Log rate limit violation
     * 
     * @param string $action Action that was rate limited
     * @param string $identifier User identifier
     * @param int $currentCount Current request count
     * @param int $limit Rate limit
     * @return void
     */
    private function logRateLimit(string $action, string $identifier, int $currentCount, int $limit): void
    {
        $logMessage = sprintf(
            'SphinxAI Rate Limit: %s action blocked for %s (requests: %d/%d)',
            $action,
            hash('sha256', $identifier), // Hash the identifier for privacy
            $currentCount,
            $limit
        );
        
        log_error($logMessage);
        
        // Also log to cache for admin monitoring
        if ($this->cache->isAvailable()) {
            try {
                $logKey = 'rate_limit:log:' . date('Y-m-d-H');
                $logData = $this->cache->getCachedModelData($logKey) ?? [];
                
                $logData[] = [
                    'timestamp' => time(),
                    'action' => $action,
                    'identifier_hash' => hash('sha256', $identifier),
                    'current_count' => $currentCount,
                    'limit' => $limit
                ];
                
                // Keep only the latest 100 log entries per hour
                if (count($logData) > 100) {
                    $logData = array_slice($logData, -100);
                }
                
                $this->cache->cacheModelData($logKey, $logData, 3600); // 1 hour
            } catch (Exception $e) {
                // Ignore cache errors for logging
            }
        }
    }
}

/**
 * Get user identifier for rate limiting
 * 
 * @return string User identifier (user ID if logged in, IP address otherwise)
 */
function getSphinxAIUserIdentifier(): string
{
    global $user_info;
    
    if (!empty($user_info['id']) && $user_info['id'] > 0) {
        return 'user:' . $user_info['id'];
    }
    
    // Fallback to IP address for guests
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Handle forwarded IPs (with validation)
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $firstIp = trim($forwardedIps[0]);
        
        // Validate the IP address
        if (filter_var($firstIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $ip = $firstIp;
        }
    }
    
    return 'ip:' . $ip;
}
