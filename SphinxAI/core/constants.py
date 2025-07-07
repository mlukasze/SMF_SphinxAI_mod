#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Shared constants for Sphinx AI Search Plugin.

This module contains all shared constants, configurations, and settings
used across different components of the Sphinx AI Search system.
"""

import configparser
import json
from pathlib import Path
from typing import Any, Dict, Optional, Set

# Version and metadata
VERSION = "1.0.0"
PLUGIN_NAME = "SphinxAISearch"
PLUGIN_DESCRIPTION = (
    "Advanced AI-powered search for SMF forums with Polish language support"
)

# Polish language constants
POLISH_STOPWORDS: Set[str] = {
    "a",
    "aby",
    "ale",
    "albo",
    "am",
    "an",
    "ani",
    "bardzo",
    "bez",
    "będzie",
    "by",
    "być",
    "ci",
    "co",
    "czy",
    "dla",
    "do",
    "gdy",
    "go",
    "i",
    "ich",
    "ile",
    "im",
    "ja",
    "jak",
    "jako",
    "je",
    "jego",
    "jej",
    "jeden",
    "jednej",
    "jedną",
    "już",
    "każdy",
    "która",
    "które",
    "której",
    "lub",
    "ma",
    "mają",
    "może",
    "my",
    "na",
    "nad",
    "nasz",
    "nasze",
    "naszego",
    "nie",
    "niego",
    "niej",
    "nim",
    "nimi",
    "o",
    "od",
    "oraz",
    "po",
    "pod",
    "przez",
    "się",
    "są",
    "ta",
    "tak",
    "tam",
    "te",
    "tej",
    "tem",
    "temu",
    "to",
    "tu",
    "ty",
    "tym",
    "w",
    "we",
    "właśnie",
    "z",
    "za",
    "ze",
    "że",
    "żeby",
    "tylko",
    "także",
    "więc",
    "gdzie",
    "kiedy",
    "czyli",
    "dlatego",
    "jednak",
    "między",
    "przed",
    "podczas",
    "zatem",
}

POLISH_DIACRITICS_MAP: Dict[str, str] = {
    "ą": "a",
    "ć": "c",
    "ę": "e",
    "ł": "l",
    "ń": "n",
    "ó": "o",
    "ś": "s",
    "ź": "z",
    "ż": "z",
    "Ą": "A",
    "Ć": "C",
    "Ę": "E",
    "Ł": "L",
    "Ń": "N",
    "Ó": "O",
    "Ś": "S",
    "Ź": "Z",
    "Ż": "Z",
}

# Model configuration constants
DEFAULT_EMBEDDING_MODEL = "sentence-transformers/paraphrase-multilingual-mpnet-base-v2"
DEFAULT_CHAT_MODEL = "TinyLlama/TinyLlama-1.1B-Chat-v1.0"
MAX_CONTEXT_LENGTH = 4096
MAX_SUMMARY_LENGTH = 200
MAX_ANSWER_LENGTH = 150

# Search configuration
DEFAULT_MAX_RESULTS = 10
SIMILARITY_THRESHOLD = 0.1
CONTENT_TRUNCATE_LENGTH = 500

# File paths and directories (consolidated to single models location)
MODELS_DIR = "SphinxAI/models"
ORIGINAL_DIR = "SphinxAI/models/original"
OPENVINO_DIR = "SphinxAI/models/openvino"
COMPRESSED_DIR = "SphinxAI/models/compressed"
GENAI_DIR = "SphinxAI/models/genai"
CONFIG_DIR = "SphinxAI/config"

# Logging configuration
LOG_FORMAT = "%(asctime)s - %(name)s - %(levelname)s - %(message)s"
LOG_LEVEL = "INFO"

# OpenVINO configuration
OPENVINO_DEVICES = ["CPU", "GPU", "AUTO"]
DEFAULT_DEVICE = "CPU"

# Regular expressions for text processing
REGEX_PATTERNS = {
    "url": r'https?://[^\s<>"{}|\\^`[\]]+',
    "email": r"\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b",
    "phone": r"\b\d{3}[-.]?\d{3}[-.]?\d{3}\b",
    "bbcode": r"\[/?[a-zA-Z][a-zA-Z0-9]*(?:\s[^\]]*)?]",
    "html_tags": r"<[^>]+>",
    "whitespace": r"\s+",
    "non_alphanum": r"[^\w\s]",
    "polish_chars": r"[ąćęłńóśźżĄĆĘŁŃÓŚŹŻ]",
}


# Enhanced Polish prompts for forum-specific content
ENHANCED_POLISH_PROMPTS = {
    "summarize": """Streć poniższy wpis z forum noży w kontekście zapytania użytkownika.
Skup się na praktycznych informacjach o nożach, opiniach, doświadczeniach i rekomendacjach.
Użyj maksymalnie 3-4 zdań w języku polskim.

Zapytanie: {query}
Treść: {content}

Streszczenie:""",
    "answer": """Odpowiedz na pytanie użytkownika na podstawie treści z forum o nożach.
Odpowiedz w języku polskim, w sposób zwięzły i praktyczny.
Skup się na faktach, opiniach i doświadczeniach związanych z nożami.

Pytanie: {query}
Kontekst z forum: {context}

Odpowiedź:""",
    "enhance_query": """Popraw i rozszerz zapytanie o noże, dodając synonimy i powiązane terminy.
Zwróć tylko poprawione zapytanie w języku polskim.

Oryginalne zapytanie: {query}

Poprawione zapytanie:""",
    "classify": """Klasyfikuj wpis z forum o nożach do jednej z kategorii:
- opinie_produkty: opinie o konkretnych nożach
- porady_techniczne: porady dotyczące używania, konserwacji
- rekomendacje: rekomendacje zakupu
- dyskusja_ogolna: ogólna dyskusja o nożach
- inne: pozostałe treści

Treść: {content}

Kategoria:""",
}


# Configuration loading functionality
class ConfigManager:
    """Manages configuration loading from INI and JSON files"""

    def __init__(self, base_dir: Optional[str] = None):
        """Initialize configuration manager

        Args:
            base_dir: Base directory for configuration files
        """
        self.base_dir = Path(base_dir) if base_dir else Path(__file__).parent.parent
        self.ini_config_path = self.base_dir / "config.ini"
        self.json_config_path = self.base_dir / "config.json"
        self._config: Optional[Dict[str, Any]] = None
        self._json_config: Optional[Dict[str, Any]] = None

    def load_config(self) -> Dict[str, Any]:
        """Load configuration from INI and JSON files

        Returns:
            Combined configuration dictionary
        """
        if self._config is None:
            self._config = {}

            # Load INI configuration (sensitive settings like database)
            if self.ini_config_path.exists():
                ini_parser = configparser.ConfigParser()
                ini_parser.read(self.ini_config_path)

                for section_name in ini_parser.sections():
                    self._config[section_name] = dict(ini_parser[section_name])

            # Load JSON configuration (project settings)
            if self.json_config_path.exists():
                with open(self.json_config_path, "r", encoding="utf-8") as f:
                    json_config = json.load(f)

                # Merge JSON config, with INI taking precedence
                for key, value in json_config.items():
                    if key not in self._config:
                        self._config[key] = value
                    elif isinstance(value, dict) and isinstance(
                        self._config[key], dict
                    ):
                        # Merge dictionaries, INI values take precedence
                        merged = value.copy()
                        merged.update(self._config[key])
                        self._config[key] = merged

        return self._config

    def get_database_config(self) -> Dict[str, Any]:
        """Get database configuration from INI file

        Returns:
            Database configuration dictionary
        """
        config = self.load_config()
        return config.get("database", {})

    def get_model_config(self) -> Dict[str, Any]:
        """Get model configuration

        Returns:
            Model configuration dictionary
        """
        config = self.load_config()
        model_settings = config.get("model_settings", {})

        # Ensure paths are absolute
        if "model_path" in model_settings and model_settings["model_path"]:
            model_path = Path(model_settings["model_path"])
            if not model_path.is_absolute():
                model_settings["model_path"] = str(self.base_dir / model_path)

        return model_settings

    def get_sphinx_config(self) -> Dict[str, Any]:
        """Get Sphinx configuration

        Returns:
            Sphinx configuration dictionary
        """
        config = self.load_config()
        return config.get("sphinx", {})

    def get_cache_config(self) -> Dict[str, Any]:
        """Get cache configuration

        Returns:
            Cache configuration dictionary
        """
        config = self.load_config()
        return config.get("cache", {})

    def get_security_config(self) -> Dict[str, Any]:
        """Get security configuration

        Returns:
            Security configuration dictionary
        """
        config = self.load_config()
        return config.get("security", {})

    def get_logging_config(self) -> Dict[str, Any]:
        """Get logging configuration

        Returns:
            Logging configuration dictionary
        """
        config = self.load_config()
        return config.get("logging", {})

    def get_paths_config(self) -> Dict[str, str]:
        """Get paths configuration with absolute paths

        Returns:
            Paths configuration dictionary
        """
        config = self.load_config()
        paths = config.get("paths", {})

        # Convert relative paths to absolute
        for key, path in paths.items():
            if path and not Path(path).is_absolute():
                paths[key] = str(self.base_dir.parent / path)

        return paths

    def config_exists(self) -> bool:
        """Check if configuration files exist

        Returns:
            True if at least one config file exists
        """
        return self.ini_config_path.exists() or self.json_config_path.exists()


# Global configuration manager instance
config_manager = ConfigManager()


# Convenience functions for backward compatibility
def get_database_config() -> Dict[str, Any]:
    """Get database configuration"""
    return config_manager.get_database_config()


def get_model_config() -> Dict[str, Any]:
    """Get model configuration"""
    return config_manager.get_model_config()


def get_sphinx_config() -> Dict[str, Any]:
    """Get Sphinx configuration"""
    return config_manager.get_sphinx_config()


def get_cache_config() -> Dict[str, Any]:
    """Get cache configuration"""
    return config_manager.get_cache_config()


def get_security_config() -> Dict[str, Any]:
    """Get security configuration"""
    return config_manager.get_security_config()


def get_logging_config() -> Dict[str, Any]:
    """Get logging configuration"""
    return config_manager.get_logging_config()


def get_paths_config() -> Dict[str, str]:
    """Get paths configuration"""
    return config_manager.get_paths_config()
