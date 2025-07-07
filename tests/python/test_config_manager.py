"""
Unit tests for SphinxAI ConfigManager
"""

import configparser
import os
import sys
import tempfile
from unittest.mock import MagicMock, mock_open, patch

import pytest

# Add the project root to Python path for imports
sys.path.insert(0, os.path.join(os.path.dirname(__file__), "..", "..", ".."))

# SphinxAI imports after path setup
from SphinxAI.utils.config_manager import ConfigManager


class TestConfigManager:
    """Test cases for ConfigManager class"""

    def test_init_with_default_path(self):
        """Test ConfigManager initialization with default config path"""
        with patch("os.path.exists", return_value=False):
            manager = ConfigManager()

            assert manager.config_path.endswith("config.ini")
            assert isinstance(manager.config, configparser.ConfigParser)

    def test_init_with_custom_path(self, temp_config_file):
        """Test ConfigManager initialization with custom config path"""
        manager = ConfigManager(temp_config_file)

        assert manager.config_path == temp_config_file
        assert isinstance(manager.config, configparser.ConfigParser)

    def test_load_existing_config(self, temp_config_file):
        """Test loading existing configuration file"""
        manager = ConfigManager(temp_config_file)

        # Verify config was loaded
        assert "database" in manager.config.sections()
        assert "cache" in manager.config.sections()
        assert manager.config.get("database", "host") == "localhost"

    def test_load_nonexistent_config(self):
        """Test loading non-existent configuration file"""
        with patch("os.path.exists", return_value=False), patch(
            "logging.getLogger"
        ) as mock_logger:

            mock_logger_instance = MagicMock()
            mock_logger.return_value = mock_logger_instance

            manager = ConfigManager("/nonexistent/config.ini")

            # Should handle gracefully without raising
            assert manager.config_path == "/nonexistent/config.ini"
            mock_logger_instance.warning.assert_called()

    def test_load_config_exception(self):
        """Test configuration loading with file read exception"""
        with patch("os.path.exists", return_value=True), patch.object(
            configparser.ConfigParser, "read", side_effect=Exception("Read error")
        ), patch("logging.getLogger") as mock_logger:

            mock_logger_instance = MagicMock()
            mock_logger.return_value = mock_logger_instance

            manager = ConfigManager("/test/config.ini")

            mock_logger_instance.error.assert_called()

    def test_get_database_config(self, temp_config_file):
        """Test getting database configuration"""
        manager = ConfigManager(temp_config_file)
        db_config = manager.get_database_config()

        assert db_config["host"] == "localhost"
        assert db_config["user"] == "test_user"
        assert db_config["password"] == "test_pass"
        assert db_config["name"] == "test_db"
        assert db_config["prefix"] == "smf_"

    def test_get_database_config_missing_section(self):
        """Test getting database config when section is missing"""
        with tempfile.NamedTemporaryFile(mode="w", suffix=".ini", delete=False) as f:
            f.write("[cache]\nenabled = true\n")
            f.flush()

            manager = ConfigManager(f.name)
            db_config = manager.get_database_config()

            assert not db_config

            os.unlink(f.name)

    def test_get_cache_config(self, temp_config_file):
        """Test getting cache configuration"""
        manager = ConfigManager(temp_config_file)
        cache_config = manager.get_cache_config()

        assert cache_config["enabled"] is True
        assert cache_config["type"] == "redis"
        assert cache_config["host"] == "localhost"
        assert cache_config["port"] == 6379
        assert cache_config["database"] == 0
        assert cache_config["prefix"] == "smf_test_"
        assert cache_config["ttl"] == 3600

    def test_get_cache_config_missing_section(self):
        """Test getting cache config when section is missing"""
        with tempfile.NamedTemporaryFile(mode="w", suffix=".ini", delete=False) as f:
            f.write("[database]\nhost = localhost\n")
            f.flush()

            manager = ConfigManager(f.name)
            cache_config = manager.get_cache_config()

            # Should return defaults
            assert cache_config["enabled"] is False
            assert cache_config["type"] == "smf"

            os.unlink(f.name)

    def test_get_cache_config_type_conversion(self):
        """Test cache config with type conversion"""
        config_content = """
[cache]
enabled = yes
type = redis
port = 6379
database = 1
default_ttl = 7200
"""
        with tempfile.NamedTemporaryFile(mode="w", suffix=".ini", delete=False) as f:
            f.write(config_content)
            f.flush()

            manager = ConfigManager(f.name)
            cache_config = manager.get_cache_config()

            assert cache_config["enabled"] is True
            assert cache_config["port"] == 6379
            assert cache_config["database"] == 1
            assert cache_config["ttl"] == 7200

            os.unlink(f.name)

    def test_get_sphinx_config(self, temp_config_file):
        """Test getting Sphinx configuration"""
        manager = ConfigManager(temp_config_file)
        sphinx_config = manager.get_sphinx_config()

        assert sphinx_config["host"] == "localhost"
        assert sphinx_config["port"] == 9312
        assert sphinx_config["max_results"] == 100
        assert sphinx_config["timeout"] == 30

    def test_get_sphinx_config_missing_section(self):
        """Test getting Sphinx config when section is missing"""
        with tempfile.NamedTemporaryFile(mode="w", suffix=".ini", delete=False) as f:
            f.write("[database]\nhost = localhost\n")
            f.flush()

            manager = ConfigManager(f.name)
            sphinx_config = manager.get_sphinx_config()

            # Should return defaults
            assert sphinx_config["host"] == "localhost"
            assert sphinx_config["port"] == 9312
            assert sphinx_config["max_results"] == 50
            assert sphinx_config["timeout"] == 30

            os.unlink(f.name)

    def test_get_ai_config(self, temp_config_file):
        """Test getting AI configuration"""
        manager = ConfigManager(temp_config_file)
        ai_config = manager.get_ai_config()

        assert ai_config["model_path"] == "/tmp/test_models"
        assert ai_config["embedding_model"] == "test-model"
        assert ai_config["max_tokens"] == 512
        assert ai_config["temperature"] == 0.7

    def test_get_ai_config_missing_section(self):
        """Test getting AI config when section is missing"""
        with tempfile.NamedTemporaryFile(mode="w", suffix=".ini", delete=False) as f:
            f.write("[database]\nhost = localhost\n")
            f.flush()

            manager = ConfigManager(f.name)
            ai_config = manager.get_ai_config()

            # Should return defaults
            assert ai_config["model_path"] == "./models"
            assert ai_config["embedding_model"] == "all-MiniLM-L6-v2"
            assert ai_config["max_tokens"] == 512
            assert ai_config["temperature"] == 0.7

            os.unlink(f.name)

    def test_get_security_config(self, temp_config_file):
        """Test getting security configuration"""
        manager = ConfigManager(temp_config_file)
        security_config = manager.get_security_config()

        assert security_config["api_key"] == "test_api_key"
        assert security_config["allowed_origins"] == ["localhost", "127.0.0.1"]
        assert security_config["rate_limit"] == 100

    def test_get_security_config_missing_section(self):
        """Test getting security config when section is missing"""
        with tempfile.NamedTemporaryFile(mode="w", suffix=".ini", delete=False) as f:
            f.write("[database]\nhost = localhost\n")
            f.flush()

            manager = ConfigManager(f.name)
            security_config = manager.get_security_config()

            # Should return defaults
            assert security_config["api_key"] == ""
            assert security_config["allowed_origins"] == []
            assert security_config["rate_limit"] == 100

            os.unlink(f.name)

    def test_get_all_config(self, temp_config_file):
        """Test getting all configuration sections"""
        manager = ConfigManager(temp_config_file)
        all_config = manager.get_all_config()

        assert "database" in all_config
        assert "cache" in all_config
        assert "sphinx" in all_config
        assert "ai" in all_config
        assert "security" in all_config

        # Verify structure
        assert all_config["database"]["host"] == "localhost"
        assert all_config["cache"]["enabled"] is True
        assert all_config["sphinx"]["port"] == 9312
        assert all_config["ai"]["max_tokens"] == 512
        assert all_config["security"]["rate_limit"] == 100

    def test_config_validation_errors(self):
        """Test configuration validation with invalid values"""
        config_content = """
[cache]
enabled = invalid_bool
port = not_a_number
database = also_not_a_number
"""
        with tempfile.NamedTemporaryFile(mode="w", suffix=".ini", delete=False) as f:
            f.write(config_content)
            f.flush()

            with patch("logging.getLogger") as mock_logger:
                mock_logger_instance = MagicMock()
                mock_logger.return_value = mock_logger_instance

                manager = ConfigManager(f.name)
                cache_config = manager.get_cache_config()

                # Should fall back to defaults for invalid values
                assert cache_config["enabled"] is False
                assert cache_config["port"] == 6379  # default
                assert cache_config["database"] == 0  # default

                # Should log warnings about invalid values
                assert mock_logger_instance.warning.called

            os.unlink(f.name)

    def test_environment_variable_override(self, temp_config_file):
        """Test environment variable overrides"""
        with patch.dict(
            os.environ,
            {"SPHINX_AI_CACHE_HOST": "env_host", "SPHINX_AI_CACHE_PORT": "7000"},
        ):
            manager = ConfigManager(temp_config_file)
            cache_config = manager.get_cache_config()

            # Should use environment values when available
            assert cache_config["host"] == "env_host"
            assert cache_config["port"] == 7000

    def test_get_default_config_path(self):
        """Test getting default configuration path"""
        manager = ConfigManager()
        default_path = manager._get_default_config_path()

        assert default_path.endswith("config.ini")
        assert "SphinxAI" in default_path
