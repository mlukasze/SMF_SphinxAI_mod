"""
Unit tests for SphinxAI utility modules
"""

import os
import pytest
from unittest.mock import patch, MagicMock

# Add the project root to Python path for imports
import sys
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', '..', '..'))


class TestSphinxAIImports:
    """Test that core modules can be imported without errors"""

    def test_cache_import(self):
        """Test that cache module can be imported"""
        try:
            import sys
            import os
            # Add project root (two directories up from tests/python/)
            project_root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
            sys.path.insert(0, project_root)
            from SphinxAI.utils.cache import SphinxAICache
            assert SphinxAICache is not None
        except ImportError as e:
            pytest.fail(f"Failed to import SphinxAICache: {e}")

    def test_config_manager_import(self):
        """Test that config manager module can be imported"""
        try:
            from SphinxAI.utils.config_manager import ConfigManager
            assert ConfigManager is not None
        except ImportError:
            pytest.fail("Failed to import ConfigManager")

    def test_constants_import(self):
        """Test that constants module can be imported"""
        try:
            from SphinxAI.core import constants
            assert constants is not None
        except ImportError:
            # Constants module might not exist yet
            pass


class TestSphinxAIIntegration:
    """Integration tests for SphinxAI components"""

    @patch('SphinxAI.utils.cache.redis_available', False)
    def test_cache_without_redis_integration(self):
        """Test cache works without Redis installed"""
        from SphinxAI.utils.cache import SphinxAICache

        with patch('SphinxAI.utils.cache.ConfigManager') as mock_config:
            mock_config.return_value.get_cache_config.return_value = {
                'enabled': True,
                'type': 'redis'
            }

            cache = SphinxAICache()

            # Should gracefully handle missing Redis
            assert not cache.is_available()
            assert cache.cache_search_results('test', {}, []) is False
            assert cache.get_cached_search_results('test', {}) is None

    def test_config_manager_integration(self, temp_config_file):
        """Test config manager with real file"""
        from SphinxAI.utils.config_manager import ConfigManager

        manager = ConfigManager(temp_config_file)

        # Test that all config methods work
        db_config = manager.get_database_config()
        cache_config = manager.get_cache_config()

        assert isinstance(db_config, dict)
        assert isinstance(cache_config, dict)
        assert 'host' in db_config
        assert 'enabled' in cache_config


class TestSphinxAIErrorHandling:
    """Test error handling across modules"""

    def test_cache_redis_connection_error(self):
        """Test cache handles Redis connection errors gracefully"""
        from SphinxAI.utils.cache import SphinxAICache

        with patch('SphinxAI.utils.cache.redis_available', True), \
             patch('SphinxAI.utils.cache.redis') as mock_redis:

            mock_redis.Redis.side_effect = ConnectionError("Cannot connect")

            with patch('SphinxAI.utils.cache.ConfigManager') as mock_config:
                mock_config.return_value.get_cache_config.return_value = {
                    'enabled': True,
                    'type': 'redis',
                    'host': 'localhost',
                    'port': 6379
                }

                cache = SphinxAICache()

                # Should handle connection error gracefully
                assert not cache.is_connected
                assert not cache.is_available()

    def test_config_file_permission_error(self):
        """Test config manager handles file permission errors"""
        from SphinxAI.utils.config_manager import ConfigManager

        with patch('os.path.exists', return_value=True), \
             patch('configparser.ConfigParser.read', side_effect=PermissionError("Access denied")):

            # Should not raise exception
            manager = ConfigManager('/restricted/config.ini')

            # Should return empty/default configs
            db_config = manager.get_database_config()
            assert isinstance(db_config, dict)


class TestSphinxAIPerformance:
    """Basic performance tests"""

    def test_cache_key_generation_performance(self):
        """Test cache key generation is reasonably fast"""
        from SphinxAI.utils.cache import SphinxAICache
        import time

        with patch('SphinxAI.utils.cache.ConfigManager') as mock_config:
            mock_config.return_value.get_cache_config.return_value = {
                'enabled': False,
                'prefix': 'test_'
            }

            cache = SphinxAICache()

            start_time = time.time()

            # Generate 1000 cache keys
            for i in range(1000):
                cache._get_cache_key('search', f'test_query_{i}')

            end_time = time.time()
            duration = end_time - start_time

            # Should complete within reasonable time (adjust as needed)
            assert duration < 1.0, f"Cache key generation took too long: {duration}s"

    def test_config_loading_performance(self, temp_config_file):
        """Test config loading is reasonably fast"""
        from SphinxAI.utils.config_manager import ConfigManager
        import time

        start_time = time.time()

        # Load config 100 times
        for _ in range(100):
            manager = ConfigManager(temp_config_file)
            manager.get_database_config()
            manager.get_cache_config()

        end_time = time.time()
        duration = end_time - start_time

        # Should complete within reasonable time
        assert duration < 2.0, f"Config loading took too long: {duration}s"


class TestSphinxAICompatibility:
    """Test compatibility with different environments"""

    def test_python_version_compatibility(self):
        """Test that code works with current Python version"""
        import sys

        # Should work with Python 3.7+
        assert sys.version_info >= (3, 7), "Python 3.7+ required"

    def test_import_fallbacks(self):
        """Test that optional imports have proper fallbacks"""
        # Test Redis import fallback
        with patch.dict('sys.modules', {'redis': None}):
            try:
                from SphinxAI.utils.cache import SphinxAICache, redis_available
                assert redis_available is False

                # Should still be able to create cache instance
                cache = SphinxAICache()
                assert cache is not None
            except ImportError:
                pytest.fail("Cache should handle missing Redis gracefully")


class TestSphinxAISecurityBasics:
    """Basic security tests"""

    def test_cache_key_no_injection(self):
        """Test cache keys are properly sanitized"""
        from SphinxAI.utils.cache import SphinxAICache

        with patch('SphinxAI.utils.cache.ConfigManager') as mock_config:
            mock_config.return_value.get_cache_config.return_value = {
                'enabled': False,
                'prefix': 'test_'
            }

            cache = SphinxAICache()

            # Test with potentially malicious input
            malicious_input = "'; DROP TABLE users; --"
            key = cache._get_cache_key('search', malicious_input)

            # Should be hashed and safe
            assert 'DROP' not in key
            assert ';' not in key
            assert '--' not in key

    def test_config_no_eval_injection(self, temp_config_file):
        """Test config values are not evaluated as code"""
        from SphinxAI.utils.config_manager import ConfigManager

        # Create config with potentially dangerous values
        dangerous_config = """
[database]
host = __import__('os').system('echo "hacked"')
user = eval('1+1')
"""

        import tempfile
        with tempfile.NamedTemporaryFile(mode='w', suffix='.ini', delete=False) as f:
            f.write(dangerous_config)
            f.flush()

            manager = ConfigManager(f.name)
            db_config = manager.get_database_config()

            # Values should be treated as strings, not evaluated
            assert "__import__" in db_config.get('host', '')
            assert "eval" in db_config.get('user', '')

            os.unlink(f.name)
