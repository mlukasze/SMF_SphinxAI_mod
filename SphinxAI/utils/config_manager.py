"""
SphinxAI Configuration Manager
Loads and manages configuration from config.ini file

@package SphinxAI
@version 1.0.0
@author SMF Sphinx AI Search Plugin
"""

import configparser
import os
import logging
from typing import Dict, Any, Optional


class ConfigManager:
    """Configuration manager for SphinxAI"""

    def __init__(self, config_path: Optional[str] = None):
        """
        Initialize configuration manager

        Args:
            config_path: Path to configuration file
        """
        self.logger = logging.getLogger(__name__)
        self.config_path = config_path or self._get_default_config_path()
        self.config = configparser.ConfigParser()
        self._load_config()

    def _get_default_config_path(self) -> str:
        """Get default configuration file path"""
        return os.path.join(os.path.dirname(os.path.dirname(__file__)), 'config.ini')

    def _load_config(self) -> None:
        """Load configuration from INI file"""
        try:
            if os.path.exists(self.config_path):
                self.config.read(self.config_path)
                self.logger.info(f"Configuration loaded from {self.config_path}")
            else:
                self.logger.warning(f"Configuration file not found: {self.config_path}")
        except Exception as e:
            self.logger.error(f"Failed to load configuration: {e}")

    def get_database_config(self) -> Dict[str, Any]:
        """Get database configuration"""
        if 'database' not in self.config:
            return {}

        db_section = self.config['database']
        return {
            'host': db_section.get('host', 'localhost'),
            'port': db_section.getint('port', 3306),
            'database': db_section.get('database', ''),
            'user': db_section.get('user', ''),
            'password': db_section.get('password', ''),
            'table_prefix': db_section.get('table_prefix', 'smf_'),
            'charset': db_section.get('charset', 'utf8mb4')
        }

    def get_cache_config(self) -> Dict[str, Any]:
        """Get cache configuration"""
        if 'cache' not in self.config:
            return {'enabled': False}

        cache_section = self.config['cache']
        cache_config: Dict[str, Any] = {
            'enabled': cache_section.getboolean('enabled', False),
            'type': cache_section.get('type', 'smf'),
            'host': cache_section.get('host', '127.0.0.1'),
            'port': cache_section.getint('port', 6379),
            'database': cache_section.getint('database', 0),
            'prefix': cache_section.get('prefix', 'sphinxai:'),
            'ttl': cache_section.getint('ttl', 3600)
        }

        # Handle password
        password = cache_section.get('password', None)
        if password == '':
            password = None
        cache_config['password'] = password

        return cache_config

    def get_model_config(self) -> Dict[str, Any]:
        """Get model configuration"""
        if 'model_settings' not in self.config:
            return {}

        model_section = self.config['model_settings']
        return {
            'model_path': model_section.get('model_path', ''),
            'device': model_section.get('device', 'CPU'),
            'max_results': model_section.getint('max_results', 10),
            'summary_length': model_section.getint('summary_length', 200),
            'confidence_threshold': model_section.getfloat('confidence_threshold', 0.1),
            'embedding_model': model_section.get('embedding_model', 'all-MiniLM-L6-v2'),
            'default_model': model_section.get('default_model', 'sentence-transformers/paraphrase-multilingual-mpnet-base-v2')
        }

    def get_paths_config(self) -> Dict[str, str]:
        """Get paths configuration"""
        if 'paths' not in self.config:
            return {}

        paths_section = self.config['paths']
        return {
            'models_dir': paths_section.get('models_dir', 'SphinxAI/models'),
            'original_dir': paths_section.get('original_dir', 'SphinxAI/models/original'),
            'openvino_dir': paths_section.get('openvino_dir', 'SphinxAI/models/openvino'),
            'compressed_dir': paths_section.get('compressed_dir', 'SphinxAI/models/compressed'),
            'genai_dir': paths_section.get('genai_dir', 'SphinxAI/models/genai')
        }

    def get_sphinx_config(self) -> Dict[str, Any]:
        """Get Sphinx configuration"""
        if 'sphinx' not in self.config:
            return {}

        sphinx_section = self.config['sphinx']
        return {
            'config_path': sphinx_section.get('config_path', '/etc/sphinx/sphinx.conf'),
            'host': sphinx_section.get('host', 'localhost'),
            'port': sphinx_section.getint('port', 9312),
            'index_name': sphinx_section.get('index_name', 'smf_posts'),
            'searchd_pid': sphinx_section.get('searchd_pid', '/var/run/sphinx/searchd.pid'),
            'binlog_path': sphinx_section.get('binlog_path', '/var/lib/sphinx/binlog')
        }

    def get_security_config(self) -> Dict[str, Any]:
        """Get security configuration"""
        if 'security' not in self.config:
            return {}

        security_section = self.config['security']
        return {
            'max_query_length': security_section.getint('max_query_length', 1000),
            'rate_limit': security_section.getint('rate_limit', 100),
            'rate_limit_window': security_section.getint('rate_limit_window', 3600)
        }

    def get_logging_config(self) -> Dict[str, Any]:
        """Get logging configuration"""
        if 'logging' not in self.config:
            return {}

        logging_section = self.config['logging']
        return {
            'level': logging_section.get('level', 'INFO'),
            'file': logging_section.get('file', 'logs/sphinx_ai.log'),
            'max_size': logging_section.get('max_size', '10MB'),
            'backup_count': logging_section.getint('backup_count', 5)
        }

    def get_huggingface_config(self) -> Dict[str, str]:
        """Get Hugging Face configuration"""
        if 'huggingface' not in self.config:
            return {}

        hf_section = self.config['huggingface']
        token = hf_section.get('token', '')

        # Filter out placeholder tokens
        if token.startswith('hf_your_token_here') or token.strip() == '#':
            token = ''

        return {
            'token': token
        }

    def is_cache_enabled(self) -> bool:
        """Check if caching is enabled"""
        cache_config = self.get_cache_config()
        return cache_config.get('enabled', False)

    def reload(self) -> None:
        """Reload configuration from file"""
        self._load_config()
