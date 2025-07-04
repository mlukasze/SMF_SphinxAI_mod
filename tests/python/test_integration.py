"""
Integration tests for SphinxAI modules
"""

import os
import pytest
from unittest.mock import patch, MagicMock

# Add the project root to Python path for imports
import sys
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', '..', '..'))


@pytest.mark.integration
class TestSphinxAIFullIntegration:
    """Full integration tests that test multiple components together"""
    
    def test_cache_and_config_integration(self, temp_config_file):
        """Test cache and config manager working together"""
        from SphinxAI.utils.cache import SphinxAICache
        from SphinxAI.utils.config_manager import ConfigManager
        
        # Test that cache can use config manager
        cache = SphinxAICache(temp_config_file)
        
        # Should have loaded config
        assert cache.config is not None
        assert 'enabled' in cache.config
        
        # Test cache operations with configuration
        if cache.is_available():
            # If Redis is available, test actual operations
            result = cache.cache_search_results(
                query="integration test",
                filters={'test': True},
                results=[{'id': 1, 'title': 'Test Result'}]
            )
            # Result depends on Redis availability
            assert isinstance(result, bool)
    
    @pytest.mark.slow
    def test_cache_performance_with_large_data(self):
        """Test cache performance with larger datasets"""
        from SphinxAI.utils.cache import SphinxAICache
        
        with patch('SphinxAI.utils.cache.ConfigManager') as mock_config:
            mock_config.return_value.get_cache_config.return_value = {
                'enabled': False  # Use memory for this test
            }
            
            cache = SphinxAICache()
            
            # Test with larger datasets
            large_results = []
            for i in range(1000):
                large_results.append({
                    'id': i,
                    'title': f'Test Post {i}',
                    'content': f'Test content for post {i}' * 10,
                    'score': 0.9 - (i * 0.0001)
                })
            
            # Should handle large datasets without errors
            result = cache.cache_search_results(
                query="performance test",
                filters={'large': True},
                results=large_results
            )
            
            # Should complete without errors
            assert isinstance(result, bool)
    
    def test_config_environment_override_integration(self, temp_config_file):
        """Test that environment variables properly override config file values"""
        from SphinxAI.utils.config_manager import ConfigManager
        
        # Set environment variables
        test_env = {
            'SPHINX_AI_CACHE_HOST': 'env-redis-host',
            'SPHINX_AI_CACHE_PORT': '7000',
            'SPHINX_AI_DB_HOST': 'env-db-host'
        }
        
        with patch.dict(os.environ, test_env):
            manager = ConfigManager(temp_config_file)
            
            cache_config = manager.get_cache_config()
            db_config = manager.get_database_config()
            
            # Environment values should override file values
            assert cache_config['host'] == 'env-redis-host'
            assert cache_config['port'] == 7000
            assert db_config['host'] == 'env-db-host'


@pytest.mark.redis
class TestRedisIntegration:
    """Tests that require actual Redis connection (marked for optional running)"""
    
    @pytest.mark.skipif(
        os.getenv('REDIS_URL') is None,
        reason="Redis connection not available"
    )
    def test_real_redis_operations(self):
        """Test with real Redis if available"""
        from SphinxAI.utils.cache import SphinxAICache
        
        # This test only runs if REDIS_URL environment variable is set
        cache = SphinxAICache()
        
        if cache.is_available():
            # Test real Redis operations
            test_data = {'test': 'real_redis_data'}
            
            success = cache.cache_search_results(
                query="redis test",
                filters={},
                results=[test_data]
            )
            
            if success:
                cached = cache.get_cached_search_results(
                    query="redis test",
                    filters={}
                )
                
                assert cached is not None
                assert cached['results'] == [test_data]


@pytest.mark.network
class TestNetworkDependencies:
    """Tests that require network access (marked for optional running)"""
    
    def test_external_model_loading_simulation(self):
        """Simulate testing external model dependencies"""
        # This would test downloading/loading external models
        # For now, just test that the code handles network failures gracefully
        
        from SphinxAI.utils.cache import SphinxAICache
        
        with patch('SphinxAI.utils.cache.ConfigManager') as mock_config:
            mock_config.return_value.get_cache_config.return_value = {
                'enabled': True,
                'type': 'redis',
                'host': 'nonexistent-host.example.com',  # Should fail
                'port': 6379
            }
            
            cache = SphinxAICache()
            
            # Should handle network failure gracefully
            assert not cache.is_connected
            assert not cache.is_available()


class TestErrorRecovery:
    """Test error recovery and resilience"""
    
    def test_cache_recovery_after_connection_loss(self):
        """Test cache behavior when connection is lost and restored"""
        from SphinxAI.utils.cache import SphinxAICache
        
        mock_redis_client = MagicMock()
        
        cache = SphinxAICache()
        cache.cache_enabled = True
        cache.is_connected = True
        cache.redis_client = mock_redis_client
        cache.config = {'prefix': 'test_'}
        
        # Simulate connection loss
        mock_redis_client.setex.side_effect = ConnectionError("Connection lost")
        
        # Cache operations should fail gracefully
        result = cache.cache_search_results(
            query="test",
            filters={},
            results=[{'id': 1}]
        )
        
        assert result is False
        
        # Simulate connection recovery
        mock_redis_client.setex.side_effect = None
        mock_redis_client.setex.return_value = True
        
        # Operations should work again
        result = cache.cache_search_results(
            query="test",
            filters={},
            results=[{'id': 1}]
        )
        
        assert result is True
    
    def test_config_corruption_handling(self):
        """Test handling of corrupted configuration files"""
        from SphinxAI.utils.config_manager import ConfigManager
        
        # Create a corrupted config file
        import tempfile
        with tempfile.NamedTemporaryFile(mode='w', suffix='.ini', delete=False) as f:
            f.write("This is not valid INI format [unclosed section")
            f.flush()
            
            # Should handle gracefully
            manager = ConfigManager(f.name)
            
            # Should return default values
            cache_config = manager.get_cache_config()
            db_config = manager.get_database_config()
            
            assert isinstance(cache_config, dict)
            assert isinstance(db_config, dict)
            
            os.unlink(f.name)


class TestConcurrency:
    """Test concurrent access patterns"""
    
    def test_multiple_cache_instances(self):
        """Test multiple cache instances don't interfere"""
        from SphinxAI.utils.cache import SphinxAICache
        
        with patch('SphinxAI.utils.cache.ConfigManager') as mock_config:
            mock_config.return_value.get_cache_config.return_value = {
                'enabled': False
            }
            
            # Create multiple instances
            cache1 = SphinxAICache()
            cache2 = SphinxAICache()
            cache3 = SphinxAICache()
            
            # Should all be independent
            assert cache1 is not cache2
            assert cache2 is not cache3
            assert cache1 is not cache3
    
    def test_config_manager_thread_safety_simulation(self):
        """Simulate thread safety testing for config manager"""
        from SphinxAI.utils.config_manager import ConfigManager
        
        # Test that multiple config managers can be created safely
        managers = []
        
        for i in range(10):
            manager = ConfigManager()
            managers.append(manager)
        
        # All should be independent instances
        for i, manager in enumerate(managers):
            for j, other_manager in enumerate(managers):
                if i != j:
                    assert manager is not other_manager
