"""
SphinxAI Cache Service - Python Implementation
Provides caching for search results and model data using configuration from INI file

@package SphinxAI
@version 1.0.0
@author SMF Sphinx AI Search Plugin
"""

import hashlib
import json
import logging
import time
from functools import wraps
from typing import TYPE_CHECKING, Any, Dict, List, Optional

# Try to import redis at runtime
redis_available = True
redis = None
try:
    import redis

    redis_available = True
    RedisType = redis.Redis
except ImportError:
    redis_available = False
    RedisType = None
    logging.warning("Redis module not available. Caching will be disabled.")

# Import redis with proper type checking
if TYPE_CHECKING:
    if redis_available:
        from redis import Redis as RedisClient  # type: ignore
    else:
        RedisClient = Any
else:
    RedisClient = Any


class SphinxAICache:
    """Cache service for SphinxAI that reads configuration from INI file"""

    # Cache key prefixes for different data types
    KEY_PREFIXES = {
        "search": "search:",
        "model": "model:",
        "config": "config:",
        "stats": "stats:",
        "suggestions": "suggestions:",
        "embeddings": "embeddings:",
    }

    def __init__(self, config_path: Optional[str] = None):
        """
        Initialize cache connection based on INI configuration

        Args:
            config_path: Path to configuration file
        """
        self.redis_client: Optional[Any] = None
        self.is_connected = False
        self.cache_enabled = False
        self.logger = logging.getLogger(__name__)

        # Import ConfigManager here to avoid circular imports
        try:
            from .config_manager import ConfigManager

            self.config_manager = ConfigManager(config_path)
            self.config = self.config_manager.get_cache_config()
            self.cache_enabled = self.config.get("enabled", False)
        except ImportError:
            self.logger.error("ConfigManager not available")
            self.config = {"enabled": False}
            self.cache_enabled = False

        self.default_ttl = self.config.get("ttl", 3600)

        # Connect if cache is enabled and Redis is available
        if self.cache_enabled:
            self._connect()

    def _connect(self) -> bool:
        """
        Establish cache connection based on configuration

        Returns:
            bool: True if connection successful
        """
        if not self.cache_enabled:
            return False

        cache_type = self.config.get("type", "smf")

        # For 'smf' type, we check if Redis is available and configured
        if cache_type == "smf":
            # SMF cache type means we use whatever SMF is configured to use
            # If Redis connection details are provided, we attempt Redis connection
            if redis_available and self.config.get("host") and self.config.get("port"):
                return self._connect_redis()
            else:
                self.logger.info(
                    "SMF cache type configured but Redis not available or not configured"
                )
                return False
        elif cache_type == "redis":
            return self._connect_redis()
        else:
            self.logger.warning(f"Unsupported cache type: {cache_type}")
            return False

    def _connect_redis(self) -> bool:
        """
        Establish Redis connection

        Returns:
            bool: True if connection successful
        """
        if not redis_available or redis is None:
            self.logger.warning("Redis not available, caching disabled")
            return False

        try:
            # Create Redis connection pool
            pool = redis.ConnectionPool(  # type: ignore
                host=self.config["host"],
                port=self.config["port"],
                password=self.config["password"],
                db=self.config["database"],
                socket_timeout=2.0,
                socket_connect_timeout=2.0,
                decode_responses=True,
                retry_on_timeout=True,
                health_check_interval=30,
            )

            if redis_available:
                self.redis_client = redis.Redis(connection_pool=pool)  # type: ignore
            else:
                raise ImportError("Redis not available")

            # Test connection
            if self.redis_client:
                self.redis_client.ping()  # type: ignore
            self.is_connected = True

            self.logger.info("Successfully connected to Redis")
            return True

        except Exception as e:
            self.logger.error(f"Failed to connect to Redis: {e}")
            self.is_connected = False
            return False

    def is_available(self) -> bool:
        """Check if cache is available and connected"""
        return (
            self.cache_enabled and self.is_connected and self.redis_client is not None
        )

    def _get_cache_key(self, cache_type: str, key: str) -> str:
        """
        Generate cache key with proper prefix

        Args:
            cache_type: Cache type (search, model, etc.)
            key: Base key

        Returns:
            str: Full cache key
        """
        prefix = self.KEY_PREFIXES.get(cache_type, "")
        key_hash = hashlib.sha256(key.encode("utf-8")).hexdigest()
        return f"{self.config['prefix']}{prefix}{key_hash}"

    def cache_search_results(
        self,
        query: str,
        filters: Dict[str, Any],
        results: List[Dict[str, Any]],
        ttl: Optional[int] = None,
    ) -> bool:
        """
        Cache search results

        Args:
            query: Search query
            filters: Search filters
            results: Search results
            ttl: Time to live in seconds

        Returns:
            bool: Success status
        """
        if not self.is_available():
            return False

        key_data: Dict[str, Any] = {
            "query": query,
            "filters": filters,
            "version": self._get_search_version(),
        }

        cache_key = self._get_cache_key("search", json.dumps(key_data, sort_keys=True))

        data: Dict[str, Any] = {
            "query": query,
            "filters": filters,
            "results": results,
            "timestamp": time.time(),
            "count": len(results),
        }

        try:
            ttl = ttl or self.default_ttl
            return self.redis_client.setex(  # type: ignore
                cache_key, ttl, json.dumps(data, ensure_ascii=False)
            )
        except Exception as e:
            self.logger.error(f"Failed to cache search results: {e}")
            return False

    def get_cached_search_results(
        self, query: str, filters: Dict[str, Any]
    ) -> Optional[Dict[str, Any]]:
        """
        Retrieve cached search results

        Args:
            query: Search query
            filters: Search filters

        Returns:
            Optional[Dict]: Cached results or None if not found
        """
        if not self.is_available():
            return None

        key_data: Dict[str, Any] = {
            "query": query,
            "filters": filters,
            "version": self._get_search_version(),
        }

        cache_key = self._get_cache_key("search", json.dumps(key_data, sort_keys=True))

        try:
            cached = self.redis_client.get(cache_key)  # type: ignore
            if cached is None:
                self.record_cache_miss()
                return None

            data = json.loads(cached)  # type: ignore
            self.record_cache_hit()
            return data

        except (json.JSONDecodeError, Exception) as e:
            self.logger.error(f"Failed to retrieve cached search results: {e}")
            return None

    def cache_embeddings(
        self,
        text: str,
        embeddings: List[float],
        model_id: str,
        ttl: Optional[int] = None,
    ) -> bool:
        """
        Cache text embeddings

        Args:
            text: Input text
            embeddings: Computed embeddings
            model_id: Model identifier
            ttl: Time to live in seconds

        Returns:
            bool: Success status
        """
        if not self.is_available():
            return False

        key_data = f"{model_id}:{text}"
        cache_key = self._get_cache_key("embeddings", key_data)

        data: Dict[str, Any] = {
            "text": text,
            "embeddings": embeddings,
            "model_id": model_id,
            "timestamp": time.time(),
        }

        try:
            ttl = ttl or (24 * 3600)  # 24 hours for embeddings
            return self.redis_client.setex(  # type: ignore
                cache_key, ttl, json.dumps(data)
            )
        except Exception as e:
            self.logger.error(f"Failed to cache embeddings: {e}")
            return False

    def get_cached_embeddings(self, text: str, model_id: str) -> Optional[List[float]]:
        """
        Retrieve cached embeddings

        Args:
            text: Input text
            model_id: Model identifier

        Returns:
            Optional[List[float]]: Cached embeddings or None if not found
        """
        if not self.is_available():
            return None

        key_data = f"{model_id}:{text}"
        cache_key = self._get_cache_key("embeddings", key_data)

        try:
            cached = self.redis_client.get(cache_key)  # type: ignore
            if cached is None:
                return None

            data = json.loads(cached)  # type: ignore
            return data.get("embeddings")

        except (json.JSONDecodeError, Exception) as e:
            self.logger.error(f"Failed to retrieve cached embeddings: {e}")
            return None

    def cache_model_metadata(
        self, model_id: str, metadata: Dict[str, Any], ttl: Optional[int] = None
    ) -> bool:
        """
        Cache model metadata

        Args:
            model_id: Model identifier
            metadata: Model metadata
            ttl: Time to live in seconds

        Returns:
            bool: Success status
        """
        if not self.is_available():
            return False

        cache_key = self._get_cache_key("model", model_id)
        ttl = ttl or (24 * 3600)  # 24 hours for model metadata

        try:
            return self.redis_client.setex(  # type: ignore
                cache_key, ttl, json.dumps(metadata, ensure_ascii=False)
            )
        except Exception as e:
            self.logger.error(f"Failed to cache model metadata: {e}")
            return False

    def get_cached_model_metadata(self, model_id: str) -> Optional[Dict[str, Any]]:
        """
        Retrieve cached model metadata

        Args:
            model_id: Model identifier

        Returns:
            Optional[Dict]: Cached metadata or None if not found
        """
        if not self.is_available():
            return None

        cache_key = self._get_cache_key("model", model_id)

        try:
            cached = self.redis_client.get(cache_key)  # type: ignore
            if cached is None:
                return None

            return json.loads(cached)  # type: ignore

        except (json.JSONDecodeError, Exception) as e:
            self.logger.error(f"Failed to retrieve cached model metadata: {e}")
            return None

    def update_search_stats(
        self, query: str, result_count: int, response_time: float
    ) -> bool:
        """
        Update search statistics

        Args:
            query: Search query
            result_count: Number of results
            response_time: Response time in seconds

        Returns:
            bool: Success status
        """
        if not self.is_available():
            return False

        try:
            pipe = self.redis_client.pipeline()  # type: ignore

            # Increment search count
            pipe.incr(f"{self.config['prefix']}stats:search_count")  # type: ignore

            # Track popular queries
            pipe.zincrby(f"{self.config['prefix']}stats:popular_queries", 1, query)  # type: ignore

            # Track response times (keep last 1000 entries)
            pipe.lpush(f"{self.config['prefix']}stats:response_times", response_time)  # type: ignore
            pipe.ltrim(f"{self.config['prefix']}stats:response_times", 0, 999)  # type: ignore

            # Track result counts
            pipe.lpush(f"{self.config['prefix']}stats:result_counts", result_count)  # type: ignore
            pipe.ltrim(f"{self.config['prefix']}stats:result_counts", 0, 999)  # type: ignore

            # Daily stats
            today = time.strftime("%Y-%m-%d")
            daily_key = f"{self.config['prefix']}stats:daily:{today}:searches"
            pipe.incr(daily_key)  # type: ignore
            pipe.expire(daily_key, 30 * 24 * 3600)  # type: ignore  # Keep for 30 days

            pipe.execute()  # type: ignore
            return True

        except Exception as e:
            self.logger.error(f"Failed to update search stats: {e}")
            return False

    def get_search_stats(self) -> Dict[str, Any]:
        """
        Get search statistics

        Returns:
            Dict: Statistics data
        """
        if not self.is_available():
            return {}

        try:
            pipe = self.redis_client.pipeline()  # type: ignore

            pipe.get(f"{self.config['prefix']}stats:search_count")  # type: ignore
            pipe.zrevrange(f"{self.config['prefix']}stats:popular_queries", 0, 9, withscores=True)  # type: ignore
            pipe.lrange(f"{self.config['prefix']}stats:response_times", 0, 99)  # type: ignore
            pipe.lrange(f"{self.config['prefix']}stats:result_counts", 0, 99)  # type: ignore

            results = pipe.execute()  # type: ignore

            response_times = [float(x) for x in results[2] if x]
            result_counts = [int(x) for x in results[3] if x]

            return {
                "total_searches": int(results[0] or 0),
                "popular_queries": dict(results[1] or []),
                "avg_response_time": (
                    sum(response_times) / len(response_times) if response_times else 0
                ),
                "avg_result_count": (
                    sum(result_counts) / len(result_counts) if result_counts else 0
                ),
                "cache_hit_rate": self._get_cache_hit_rate(),
            }

        except Exception as e:
            self.logger.error(f"Failed to get search stats: {e}")
            return {}

    def _get_cache_hit_rate(self) -> float:
        """Calculate cache hit rate"""
        try:
            hits = int(self.redis_client.get(f"{self.config['prefix']}stats:cache_hits") or 0)  # type: ignore
            misses = int(self.redis_client.get(f"{self.config['prefix']}stats:cache_misses") or 0)  # type: ignore
            total = hits + misses

            return (hits / total) * 100 if total > 0 else 0

        except Exception:
            return 0

    def record_cache_hit(self) -> None:
        """Increment cache hit counter"""
        if self.is_available():
            try:
                self.redis_client.incr(f"{self.config['prefix']}stats:cache_hits")  # type: ignore
            except Exception:
                pass  # Ignore errors for stats

    def record_cache_miss(self) -> None:
        """Increment cache miss counter"""
        if self.is_available():
            try:
                self.redis_client.incr(f"{self.config['prefix']}stats:cache_misses")  # type: ignore
            except Exception:
                pass  # Ignore errors for stats

    def clear_cache(self, pattern: str = "*") -> int:
        """
        Clear cache by pattern

        Args:
            pattern: Cache key pattern (without prefix)

        Returns:
            int: Number of keys deleted
        """
        if not self.is_available():
            return 0

        try:
            full_pattern = f"{self.config['prefix']}{pattern}"
            keys = self.redis_client.keys(full_pattern)  # type: ignore
            if not keys:
                return 0

            return self.redis_client.delete(*keys)  # type: ignore

        except Exception as e:
            self.logger.error(f"Failed to clear cache: {e}")
            return 0

    def clear_search_cache(self) -> int:
        """Clear all search result cache"""
        return self.clear_cache(f"{self.KEY_PREFIXES['search']}*")

    def clear_embeddings_cache(self) -> int:
        """Clear all embeddings cache"""
        return self.clear_cache(f"{self.KEY_PREFIXES['embeddings']}*")

    def _get_search_version(self) -> str:
        """Get search version for cache invalidation"""
        # This should be updated when search configuration changes
        import os

        version_data = {
            "model_path": os.environ.get("SPHINX_AI_MODEL_PATH", ""),
            "model_type": os.environ.get("SPHINX_AI_MODEL_TYPE", ""),
            "max_results": os.environ.get("SPHINX_AI_MAX_RESULTS", "50"),
        }

        return hashlib.md5(
            json.dumps(version_data, sort_keys=True).encode()
        ).hexdigest()

    def close(self) -> None:
        """Close Redis connection"""
        if self.redis_client:
            try:
                self.redis_client.close()  # type: ignore
            except Exception:
                pass  # Ignore close errors

            self.is_connected = False

    def __enter__(self):
        """Context manager entry"""
        return self

    def __exit__(
        self,
        exc_type: Optional[type],
        exc_val: Optional[Exception],
        exc_tb: Optional[Any],
    ) -> None:
        """Context manager exit"""
        self.close()


def cached_search(ttl: int = 3600):  # type: ignore
    """
    Decorator for caching search function results

    Args:
        ttl: Time to live in seconds
    """

    def decorator(func):  # type: ignore
        @wraps(func)
        def wrapper(*args, **kwargs):  # type: ignore
            cache = SphinxAICache()

            if not cache.is_available():
                return func(*args, **kwargs)

            # Create cache key from function arguments
            cache_key = hashlib.sha256(
                json.dumps([args, kwargs], sort_keys=True, default=str).encode()
            ).hexdigest()

            # Try to get from cache
            cached_result = cache.redis_client.get(f"func:{func.__name__}:{cache_key}")  # type: ignore
            if cached_result is not None:
                try:
                    cache.record_cache_hit()
                    return json.loads(cached_result)  # type: ignore
                except json.JSONDecodeError:
                    pass

            # Execute function and cache result
            result = func(*args, **kwargs)

            try:
                cache.redis_client.setex(  # type: ignore
                    f"func:{func.__name__}:{cache_key}",
                    ttl,
                    json.dumps(result, default=str),
                )
                cache.record_cache_miss()
            except Exception as e:
                logging.error(f"Failed to cache function result: {e}")

            return result

        return wrapper

    return decorator


# Global cache instance
_cache_instance = None


def get_cache_instance(config_path: Optional[str] = None) -> SphinxAICache:
    """Get global cache instance"""
    global _cache_instance

    if _cache_instance is None:
        _cache_instance = SphinxAICache(config_path)

    return _cache_instance
