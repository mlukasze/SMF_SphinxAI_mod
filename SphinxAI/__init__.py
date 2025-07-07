"""
SphinxAI - AI-Enhanced Search Plugin for SMF

This package provides AI-powered search capabilities for Simple Machines Forum,
combining Sphinx indexing with machine learning models for intelligent search results.

@package SphinxAI
@version 1.0.0
@author SMF Sphinx AI Team
"""

__version__ = "1.0.0"
__author__ = "SMF Sphinx AI Team"

# Import main components for easier access
try:
    from .utils.cache import SphinxAICache, get_cache_instance
    from .utils.config_manager import ConfigManager

    __all__ = [
        "SphinxAICache",
        "get_cache_instance",
        "ConfigManager",
        "__version__",
        "__author__",
    ]
except ImportError:
    # Allow package to be imported even if dependencies are missing
    __all__ = ["__version__", "__author__"]
