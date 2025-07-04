<?php

/**
 * SphinxAI Enums - Type-safe constants for PHP 8.1+
 * 
 * @package SphinxAI
 * @version 2.0.0
 * @author SMF Sphinx AI Search Plugin
 */

declare(strict_types=1);

if (!defined('SMF')) {
    die('No direct access...');
}

/**
 * Cache key types for type-safe cache operations
 */
enum CacheKeyType: string
{
    case SEARCH = 'search_';
    case MODEL = 'model_';
    case CONFIG = 'config_';
    case STATS = 'stats_';
    case SUGGESTIONS = 'suggestions_';
    case EMBEDDINGS = 'embeddings_';
}

/**
 * Search types supported by the system
 */
enum SearchType: string
{
    case HYBRID = 'hybrid';
    case SEMANTIC = 'semantic';
    case TRADITIONAL = 'traditional';
    case AI_ENHANCED = 'ai_enhanced';
}

/**
 * Log levels for consistent logging
 */
enum LogLevel: string
{
    case DEBUG = 'DEBUG';
    case INFO = 'INFO';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
    case CRITICAL = 'CRITICAL';
}

/**
 * Cache types supported
 */
enum CacheType: string
{
    case SMF = 'smf';
    case REDIS = 'redis';
    case MEMCACHED = 'memcached';
    case FILE = 'file';
    case MEMORY = 'memory';
}

/**
 * Rate limit types
 */
enum RateLimitType: string
{
    case SEARCH = 'search';
    case SUGGESTIONS = 'suggestions';
    case ADMIN = 'admin';
    case API = 'api';
}

/**
 * Configuration sections
 */
enum ConfigSection: string
{
    case CORE = 'core';
    case CACHE = 'cache';
    case REDIS = 'redis';
    case RATE_LIMIT = 'rate_limit';
    case SECURITY = 'security';
    case PERFORMANCE = 'performance';
    case LOGGING = 'logging';
    case SEARCH = 'search';
    case MONITORING = 'monitoring';
}

/**
 * HTTP status codes for API responses
 */
enum HttpStatus: int
{
    case OK = 200;
    case CREATED = 201;
    case NO_CONTENT = 204;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case TOO_MANY_REQUESTS = 429;
    case INTERNAL_SERVER_ERROR = 500;
    case SERVICE_UNAVAILABLE = 503;
}
