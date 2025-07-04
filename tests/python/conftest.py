"""
Shared test fixtures and utilities for SphinxAI tests
"""

import pytest
import tempfile
import os
import configparser
from pathlib import Path
from typing import Dict, Any


@pytest.fixture
def temp_config_file():
    """Create a temporary config file for testing"""
    config_content = """
[database]
host = localhost
user = test_user
password = test_pass
name = test_db
prefix = smf_

[cache]
enabled = true
type = redis
host = localhost
port = 6379
database = 0
prefix = smf_test_
default_ttl = 3600

[sphinx]
host = localhost
port = 9312
max_results = 100
timeout = 30

[ai]
model_path = /tmp/test_models
embedding_model = test-model
max_tokens = 512
temperature = 0.7

[security]
api_key = test_api_key
allowed_origins = localhost,127.0.0.1
rate_limit = 100
"""

    with tempfile.NamedTemporaryFile(mode='w', suffix='.ini', delete=False) as f:
        f.write(config_content)
        f.flush()
        yield f.name

    # Cleanup
    try:
        os.unlink(f.name)
    except OSError:
        pass


@pytest.fixture
def sample_config_dict():
    """Sample configuration dictionary for testing"""
    return {
        'database': {
            'host': 'localhost',
            'user': 'test_user',
            'password': 'test_pass',
            'name': 'test_db',
            'prefix': 'smf_'
        },
        'cache': {
            'enabled': 'true',
            'type': 'redis',
            'host': 'localhost',
            'port': '6379',
            'database': '0',
            'prefix': 'smf_test_',
            'default_ttl': '3600'
        },
        'sphinx': {
            'host': 'localhost',
            'port': '9312',
            'max_results': '100',
            'timeout': '30'
        },
        'ai': {
            'model_path': '/tmp/test_models',
            'embedding_model': 'test-model',
            'max_tokens': '512',
            'temperature': '0.7'
        },
        'security': {
            'api_key': 'test_api_key',
            'allowed_origins': 'localhost,127.0.0.1',
            'rate_limit': '100'
        }
    }


@pytest.fixture
def mock_redis_config():
    """Mock Redis configuration for testing"""
    return {
        'host': 'localhost',
        'port': 6379,
        'db': 0,
        'decode_responses': True,
        'socket_timeout': 5,
        'socket_connect_timeout': 5,
        'retry_on_timeout': True
    }


@pytest.fixture
def sample_search_results():
    """Sample search results for cache testing"""
    return {
        'query': 'test query',
        'results': [
            {
                'id': 1,
                'title': 'Test Post 1',
                'content': 'This is test content',
                'score': 0.95,
                'url': '/index.php?topic=1.0'
            },
            {
                'id': 2,
                'title': 'Test Post 2',
                'content': 'Another test content',
                'score': 0.87,
                'url': '/index.php?topic=2.0'
            }
        ],
        'total': 2,
        'search_time': 0.123
    }


@pytest.fixture
def sample_model_data():
    """Sample model data for cache testing"""
    return {
        'model_name': 'test-embedding-model',
        'version': '1.0.0',
        'dimensions': 768,
        'vocab_size': 50000,
        'max_length': 512,
        'last_updated': '2024-01-01T00:00:00Z'
    }
