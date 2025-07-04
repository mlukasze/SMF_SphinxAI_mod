#!/usr/bin/env python3
"""
Shared constants for Sphinx AI Search Plugin.

This module contains all shared constants, configurations, and settings
used across different components of the Sphinx AI Search system.
"""

from typing import Dict, Set

# Version and metadata
VERSION = "1.0.0"
PLUGIN_NAME = "SphinxAISearch"
PLUGIN_DESCRIPTION = "Advanced AI-powered search for SMF forums with Polish language support"

# Polish language constants
POLISH_STOPWORDS: Set[str] = {
    'a', 'aby', 'ale', 'albo', 'am', 'an', 'ani', 'bardzo', 'bez', 'będzie',
    'by', 'być', 'ci', 'co', 'czy', 'dla', 'do', 'gdy', 'go', 'i', 'ich', 
    'ile', 'im', 'ja', 'jak', 'jako', 'je', 'jego', 'jej', 'jeden', 'jednej',
    'jedną', 'już', 'każdy', 'która', 'które', 'której', 'lub', 'ma', 'mają',
    'może', 'my', 'na', 'nad', 'nasz', 'nasze', 'naszego', 'nie', 'niego',
    'niej', 'nim', 'nimi', 'o', 'od', 'oraz', 'po', 'pod', 'przez', 'się',
    'są', 'ta', 'tak', 'tam', 'te', 'tej', 'tem', 'temu', 'to', 'tu', 'ty',
    'tym', 'w', 'we', 'właśnie', 'z', 'za', 'ze', 'że', 'żeby', 'tylko',
    'także', 'więc', 'gdzie', 'kiedy', 'czyli', 'dlatego', 'jednak', 'między',
    'przed', 'podczas', 'zatem'
}

POLISH_DIACRITICS_MAP: Dict[str, str] = {
    'ą': 'a', 'ć': 'c', 'ę': 'e', 'ł': 'l', 'ń': 'n', 'ó': 'o',
    'ś': 's', 'ź': 'z', 'ż': 'z',
    'Ą': 'A', 'Ć': 'C', 'Ę': 'E', 'Ł': 'L', 'Ń': 'N', 'Ó': 'O',
    'Ś': 'S', 'Ź': 'Z', 'Ż': 'Z'
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
LOG_FORMAT = '%(asctime)s - %(name)s - %(levelname)s - %(message)s'
LOG_LEVEL = "INFO"

# OpenVINO configuration
OPENVINO_DEVICES = ["CPU", "GPU", "AUTO"]
DEFAULT_DEVICE = "CPU"

# Regular expressions for text processing
REGEX_PATTERNS = {
    "url": r'https?://[^\s<>"{}|\\^`[\]]+',
    "email": r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b',
    "phone": r'\b\d{3}[-.]?\d{3}[-.]?\d{3}\b',
    "bbcode": r'\[/?[a-zA-Z][a-zA-Z0-9]*(?:\s[^\]]*)?]',
    "html_tags": r'<[^>]+>',
    "whitespace": r'\s+',
    "non_alphanum": r'[^\w\s]',
    "polish_chars": r'[ąćęłńóśźżĄĆĘŁŃÓŚŹŻ]'
}
