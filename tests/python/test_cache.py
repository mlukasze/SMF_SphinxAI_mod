"""
Unit tests for SphinxAI Cache Service
"""

import hashlib
import json
import os
import sys
import time
from unittest.mock import MagicMock, Mock, patch

import pytest

# Add the project root to Python path for imports
sys.path.insert(0, os.path.join(os.path.dirname(__file__), "..", "..", ".."))

# SphinxAI imports after path setup
from SphinxAI.utils.cache import SphinxAICache, cached_search, get_cache_instance


class TestSphinxAICache:
    """Test cases for SphinxAICache class"""

    @patch("SphinxAI.utils.cache.redis", None)
    @patch("SphinxAI.utils.cache.redis_available", False)
    def test_init_without_redis(self):
        """Test initialization when Redis is not available"""
        with patch("SphinxAI.utils.cache.ConfigManager") as mock_config_manager:
            mock_config_manager.return_value.get_cache_config.return_value = {
                "enabled": True,
                "type": "redis",
            }

            cache = SphinxAICache()

            assert not cache.is_connected
            assert cache.redis_client is None
            assert cache.cache_enabled

    def test_init_cache_disabled(self):
        """Test initialization when cache is disabled"""
        with patch("SphinxAI.utils.cache.ConfigManager") as mock_config_manager:
            mock_config_manager.return_value.get_cache_config.return_value = {
                "enabled": False
            }

            cache = SphinxAICache()

            assert not cache.cache_enabled
            assert not cache.is_connected

    @patch("SphinxAI.utils.cache.redis_available", True)
    def test_init_with_redis_success(self):
        """Test successful Redis initialization"""
        mock_redis = MagicMock()
        mock_pool = MagicMock()
        mock_redis.ConnectionPool.return_value = mock_pool
        mock_redis_client = MagicMock()
        mock_redis.Redis.return_value = mock_redis_client

        with patch("SphinxAI.utils.cache.redis", mock_redis), patch(
            "SphinxAI.utils.cache.ConfigManager"
        ) as mock_config_manager:

            mock_config_manager.return_value.get_cache_config.return_value = {
                "enabled": True,
                "type": "redis",
                "host": "localhost",
                "port": 6379,
                "password": "test_pass",
                "database": 0,
                "prefix": "test_",
            }

            cache = SphinxAICache()

            assert cache.is_connected
            assert cache.redis_client == mock_redis_client
            mock_redis_client.ping.assert_called_once()

    @patch("SphinxAI.utils.cache.redis_available", True)
    def test_init_with_redis_failure(self):
        """Test Redis initialization failure"""
        mock_redis = MagicMock()
        mock_redis.Redis.side_effect = Exception("Connection failed")

        with patch("SphinxAI.utils.cache.redis", mock_redis), patch(
            "SphinxAI.utils.cache.ConfigManager"
        ) as mock_config_manager:

            mock_config_manager.return_value.get_cache_config.return_value = {
                "enabled": True,
                "type": "redis",
                "host": "localhost",
                "port": 6379,
                "password": "test_pass",
                "database": 0,
                "prefix": "test_",
            }

            cache = SphinxAICache()

            assert not cache.is_connected
            assert cache.redis_client is None

    def test_is_available_true(self):
        """Test is_available returns True when cache is ready"""
        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = MagicMock()

        assert cache.is_available()

    def test_is_available_false(self):
        """Test is_available returns False when cache is not ready"""
        cache = SphinxAICache()
        cache.cache_enabled = False
        cache.is_connected = False
        cache.redis_client = None

        assert not cache.is_available()

    def test_get_cache_key(self):
        """Test cache key generation"""
        with patch("SphinxAI.utils.cache.ConfigManager") as mock_config_manager:
            mock_config_manager.return_value.get_cache_config.return_value = {
                "enabled": False,
                "prefix": "test_",
            }

            cache = SphinxAICache()
            key = cache._get_cache_key("search", "test_query")

            expected_hash = hashlib.sha256("test_query".encode("utf-8")).hexdigest()
            expected_key = f"test_search:{expected_hash}"

            assert key == expected_key

    def test_cache_search_results_success(self, sample_search_results):
        """Test successful search results caching"""
        from .conftest import setup_mock_cache_with_redis

        cache, mock_redis_client = setup_mock_cache_with_redis()
        mock_redis_client.setex.return_value = True

        result = cache.cache_search_results(
            query="test query",
            filters={"category": "general"},
            results=sample_search_results["results"],
            ttl=3600,
        )

        assert result is True
        mock_redis_client.setex.assert_called_once()

    def test_cache_search_results_unavailable(self, sample_search_results):
        """Test caching when cache is unavailable"""
        cache = SphinxAICache()
        cache.cache_enabled = False

        result = cache.cache_search_results(
            query="test query",
            filters={"category": "general"},
            results=sample_search_results["results"],
        )

        assert result is False

    def test_cache_search_results_exception(self, sample_search_results):
        """Test caching with Redis exception"""
        mock_redis_client = MagicMock()
        mock_redis_client.setex.side_effect = Exception("Redis error")

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        result = cache.cache_search_results(
            query="test query",
            filters={"category": "general"},
            results=sample_search_results["results"],
        )

        assert result is False

    def test_get_cached_search_results_hit(self):
        """Test successful cache hit for search results"""
        cached_data = {
            "query": "test query",
            "filters": {"category": "general"},
            "results": [{"id": 1, "title": "Test"}],
            "timestamp": time.time(),
            "count": 1,
        }

        mock_redis_client = MagicMock()
        mock_redis_client.get.return_value = json.dumps(cached_data)

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        with patch.object(cache, "record_cache_hit") as mock_hit:
            result = cache.get_cached_search_results(
                query="test query", filters={"category": "general"}
            )

            assert result == cached_data
            mock_hit.assert_called_once()

    def test_get_cached_search_results_miss(self):
        """Test cache miss for search results"""
        mock_redis_client = MagicMock()
        mock_redis_client.get.return_value = None

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        with patch.object(cache, "record_cache_miss") as mock_miss:
            result = cache.get_cached_search_results(
                query="test query", filters={"category": "general"}
            )

            assert result is None
            mock_miss.assert_called_once()

    def test_get_cached_search_results_unavailable(self):
        """Test getting cached results when cache is unavailable"""
        cache = SphinxAICache()
        cache.cache_enabled = False

        result = cache.get_cached_search_results(
            query="test query", filters={"category": "general"}
        )

        assert result is None

    def test_cache_embeddings_success(self):
        """Test successful embeddings caching"""
        mock_redis_client = MagicMock()
        mock_redis_client.setex.return_value = True

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        embeddings = [0.1, 0.2, 0.3, 0.4, 0.5]
        result = cache.cache_embeddings(
            text="test text", embeddings=embeddings, model_id="test-model", ttl=86400
        )

        assert result is True
        mock_redis_client.setex.assert_called_once()

    def test_get_cached_embeddings_hit(self):
        """Test successful cache hit for embeddings"""
        embeddings = [0.1, 0.2, 0.3, 0.4, 0.5]
        cached_data = {
            "text": "test text",
            "embeddings": embeddings,
            "model_id": "test-model",
            "timestamp": time.time(),
        }

        mock_redis_client = MagicMock()
        mock_redis_client.get.return_value = json.dumps(cached_data)

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        result = cache.get_cached_embeddings(text="test text", model_id="test-model")

        assert result == embeddings

    def test_get_cached_embeddings_miss(self):
        """Test cache miss for embeddings"""
        mock_redis_client = MagicMock()
        mock_redis_client.get.return_value = None

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        result = cache.get_cached_embeddings(text="test text", model_id="test-model")

        assert result is None

    def test_cache_model_metadata_success(self, sample_model_data):
        """Test successful model metadata caching"""
        mock_redis_client = MagicMock()
        mock_redis_client.setex.return_value = True

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        result = cache.cache_model_metadata(
            model_id="test-model", metadata=sample_model_data, ttl=86400
        )

        assert result is True
        mock_redis_client.setex.assert_called_once()

    def test_get_cached_model_metadata_hit(self, sample_model_data):
        """Test successful cache hit for model metadata"""
        mock_redis_client = MagicMock()
        mock_redis_client.get.return_value = json.dumps(sample_model_data)

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        result = cache.get_cached_model_metadata("test-model")

        assert result == sample_model_data

    def test_update_search_stats_success(self):
        """Test successful search stats update"""
        mock_redis_client = MagicMock()
        mock_pipe = MagicMock()
        mock_redis_client.pipeline.return_value = mock_pipe
        mock_pipe.execute.return_value = [1, 1, 1, 1, 1, True]

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        result = cache.update_search_stats(
            query="test query", result_count=5, response_time=0.123
        )

        assert result is True
        mock_pipe.incr.assert_called()
        mock_pipe.zincrby.assert_called()
        mock_pipe.lpush.assert_called()
        mock_pipe.execute.assert_called_once()

    def test_get_search_stats_success(self):
        """Test successful search stats retrieval"""
        mock_redis_client = MagicMock()
        mock_pipe = MagicMock()
        mock_redis_client.pipeline.return_value = mock_pipe
        mock_pipe.execute.return_value = [
            100,  # total searches
            [("query1", 5), ("query2", 3)],  # popular queries
            ["0.123", "0.456"],  # response times
            ["5", "3"],  # result counts
        ]

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        with patch.object(cache, "_get_cache_hit_rate", return_value=85.5):
            stats = cache.get_search_stats()

            assert stats["total_searches"] == 100
            assert stats["popular_queries"] == {"query1": 5, "query2": 3}
            assert stats["avg_response_time"] == 0.2895  # (0.123 + 0.456) / 2
            assert stats["avg_result_count"] == 4  # (5 + 3) / 2
            assert stats["cache_hit_rate"] == 85.5

    def test_get_cache_hit_rate(self):
        """Test cache hit rate calculation"""
        mock_redis_client = MagicMock()
        mock_redis_client.get.side_effect = ["80", "20"]  # hits, misses

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        hit_rate = cache._get_cache_hit_rate()

        assert hit_rate == 80.0  # 80 / (80 + 20) * 100

    def test_record_cache_hit(self):
        """Test recording cache hit"""
        from .conftest import setup_mock_cache_with_redis

        cache, mock_redis_client = setup_mock_cache_with_redis()

        cache.record_cache_hit()

        mock_redis_client.incr.assert_called_once()

    def test_record_cache_miss(self):
        """Test recording cache miss"""
        mock_redis_client = MagicMock()

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        cache.record_cache_miss()

        mock_redis_client.incr.assert_called_once()

    def test_clear_cache_success(self):
        """Test successful cache clearing"""
        mock_redis_client = MagicMock()
        mock_redis_client.keys.return_value = ["test_key1", "test_key2"]
        mock_redis_client.delete.return_value = 2

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        count = cache.clear_cache("search:*")

        assert count == 2
        mock_redis_client.keys.assert_called_once_with("test_search:*")
        mock_redis_client.delete.assert_called_once_with("test_key1", "test_key2")

    def test_clear_cache_no_keys(self):
        """Test cache clearing when no keys found"""
        mock_redis_client = MagicMock()
        mock_redis_client.keys.return_value = []

        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {"prefix": "test_"}

        count = cache.clear_cache("search:*")

        assert count == 0
        mock_redis_client.delete.assert_not_called()

    def test_clear_search_cache(self):
        """Test clearing search cache"""
        cache = SphinxAICache()

        with patch.object(cache, "clear_cache", return_value=5) as mock_clear:
            count = cache.clear_search_cache()

            assert count == 5
            mock_clear.assert_called_once_with("search:*")

    def test_clear_embeddings_cache(self):
        """Test clearing embeddings cache"""
        cache = SphinxAICache()

        with patch.object(cache, "clear_cache", return_value=3) as mock_clear:
            count = cache.clear_embeddings_cache()

            assert count == 3
            mock_clear.assert_called_once_with("embeddings:*")

    def test_get_search_version(self):
        """Test search version generation"""
        with patch.dict(
            os.environ,
            {
                "SPHINX_AI_MODEL_PATH": "/test/path",
                "SPHINX_AI_MODEL_TYPE": "test-type",
                "SPHINX_AI_MAX_RESULTS": "100",
            },
        ):
            cache = SphinxAICache()
            version = cache._get_search_version()

            assert isinstance(version, str)
            assert len(version) == 32  # MD5 hash length

    def test_close(self):
        """Test closing cache connection"""
        mock_redis_client = MagicMock()

        cache = SphinxAICache()
        cache.redis_client = mock_redis_client
        cache.is_connected = True

        cache.close()

        mock_redis_client.close.assert_called_once()
        assert not cache.is_connected

    def test_context_manager(self):
        """Test cache as context manager"""
        cache = SphinxAICache()

        with patch.object(cache, "close") as mock_close:
            with cache:
                pass

            mock_close.assert_called_once()


class TestCachedSearchDecorator:
    """Test cases for cached_search decorator"""

    @patch("SphinxAI.utils.cache.SphinxAICache")
    def test_cached_search_cache_hit(self, mock_cache_class):
        """Test decorator with cache hit"""
        mock_cache = MagicMock()
        mock_cache.is_available.return_value = True
        mock_cache.redis_client.get.return_value = json.dumps({"result": "cached"})
        mock_cache_class.return_value = mock_cache

        @cached_search(ttl=3600)
        def test_function(query, filters):
            return {"result": "fresh"}

        result = test_function("test", {"filter": "value"})

        assert result == {"result": "cached"}
        mock_cache.record_cache_hit.assert_called_once()

    @patch("SphinxAI.utils.cache.SphinxAICache")
    def test_cached_search_cache_miss(self, mock_cache_class):
        """Test decorator with cache miss"""
        mock_cache = MagicMock()
        mock_cache.is_available.return_value = True
        mock_cache.redis_client.get.return_value = None
        mock_cache_class.return_value = mock_cache

        @cached_search(ttl=3600)
        def test_function(query, filters):
            return {"result": "fresh"}

        result = test_function("test", {"filter": "value"})

        assert result == {"result": "fresh"}
        mock_cache.redis_client.setex.assert_called_once()
        mock_cache.record_cache_miss.assert_called_once()

    @patch("SphinxAI.utils.cache.SphinxAICache")
    def test_cached_search_cache_unavailable(self, mock_cache_class):
        """Test decorator when cache is unavailable"""
        mock_cache = MagicMock()
        mock_cache.is_available.return_value = False
        mock_cache_class.return_value = mock_cache

        @cached_search(ttl=3600)
        def test_function(query, filters):
            return {"result": "fresh"}

        result = test_function("test", {"filter": "value"})

        assert result == {"result": "fresh"}
        mock_cache.redis_client.get.assert_not_called()


class TestCacheInstanceFunction:
    """Test cases for global cache instance function"""

    @patch("SphinxAI.utils.cache._cache_instance", None)
    @patch("SphinxAI.utils.cache.SphinxAICache")
    def test_get_cache_instance_new(self, mock_cache_class):
        """Test getting new cache instance"""
        mock_cache = MagicMock()
        mock_cache_class.return_value = mock_cache

        instance = get_cache_instance("/test/config.ini")

        assert instance == mock_cache
        mock_cache_class.assert_called_once_with("/test/config.ini")

    @patch("SphinxAI.utils.cache._cache_instance")
    def test_get_cache_instance_existing(self, mock_existing_cache):
        """Test getting existing cache instance"""
        instance = get_cache_instance()

        assert instance == mock_existing_cache
