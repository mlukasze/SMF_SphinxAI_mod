#!/usr/bin/env python3
"""
Base interfaces and abstract classes for the Sphinx AI Search system.

This module defines the core interfaces that all handlers must implement,
following the Interface Segregation Principle.
"""

from abc import ABC, abstractmethod
from typing import Any, Dict, List, Optional

import numpy as np
from numpy.typing import NDArray


class SearchHandler(ABC):
    """Abstract base class for search handlers."""

    @abstractmethod
    def search(self, query: str, max_results: int = 10) -> List[Dict[str, Any]]:
        """
        Perform search operation.

        Args:
            query: Search query
            max_results: Maximum number of results

        Returns:
            List of search results
        """
        ...

    @abstractmethod
    def get_status(self) -> Dict[str, Any]:
        """Get handler status information."""
        ...


class AIHandler(ABC):
    """Abstract base class for AI handlers."""

    @abstractmethod
    def generate_embeddings(self, texts: List[str]) -> Optional[NDArray[np.float32]]:
        """
        Generate embeddings for text list.

        Args:
            texts: List of texts to embed

        Returns:
            Embeddings array or None if failed
        """
        ...

    @abstractmethod
    def calculate_similarity(
        self,
        query_embedding: NDArray[np.float32],
        content_embeddings: NDArray[np.float32],
    ) -> Optional[NDArray[np.float32]]:
        """
        Calculate similarity between query and content embeddings.

        Args:
            query_embedding: Query embedding
            content_embeddings: Content embeddings

        Returns:
            Similarity scores or None if failed
        """
        ...

    @abstractmethod
    def generate_summary(self, query: str, content: str, max_length: int = 200) -> str:
        """
        Generate content summary.

        Args:
            query: Search query for context
            content: Content to summarize
            max_length: Maximum summary length

        Returns:
            Generated summary
        """
        ...

    @abstractmethod
    def preprocess_text(self, text: str) -> str:
        """
        Preprocess text for AI processing.

        Args:
            text: Input text

        Returns:
            Preprocessed text
        """
        ...

    @abstractmethod
    def get_model_info(self) -> Dict[str, Any]:
        """Get model information and status."""
        ...


class TextProcessor(ABC):
    """Abstract base class for text processors."""

    @abstractmethod
    def normalize_diacritics(self, text: str) -> str:
        """Normalize diacritics in text."""
        ...

    @abstractmethod
    def remove_stopwords(self, words: List[str]) -> List[str]:
        """Remove stopwords from word list."""
        ...

    @abstractmethod
    def clean_forum_content(self, text: str) -> str:
        """Clean forum-specific content."""
        ...

    @abstractmethod
    def preprocess_text(self, text: str, normalize_diacritics: bool = True) -> str:
        """Complete text preprocessing pipeline."""
        ...


class ModelConverter(ABC):
    """Abstract base class for model converters."""

    @abstractmethod
    def download_model(self, model_name: str, output_dir: str) -> bool:
        """Download model from repository."""
        ...

    @abstractmethod
    def convert_model(self, input_path: str, output_path: str) -> bool:
        """Convert model to target format."""
        ...

    @abstractmethod
    def compress_model(self, model_path: str, output_path: str) -> bool:
        """Compress model for optimization."""
        ...

    @abstractmethod
    def verify_model(self, model_path: str) -> bool:
        """Verify model integrity and functionality."""
        ...


class ConfigurationManager(ABC):
    """Abstract base class for configuration management."""

    @abstractmethod
    def load_config(self, config_path: str) -> Dict[str, Any]:
        """Load configuration from file."""
        ...

    @abstractmethod
    def save_config(self, config: Dict[str, Any], config_path: str) -> bool:
        """Save configuration to file."""
        ...

    @abstractmethod
    def validate_config(self, config: Dict[str, Any]) -> bool:
        """Validate configuration values."""
        ...


class Logger(ABC):
    """Abstract base class for logging functionality."""

    @abstractmethod
    def log_info(self, message: str) -> None:
        """Log info message."""
        ...

    @abstractmethod
    def log_warning(self, message: str) -> None:
        """Log warning message."""
        ...

    @abstractmethod
    def log_error(self, message: str) -> None:
        """Log error message."""
        ...

    @abstractmethod
    def log_debug(self, message: str) -> None:
        """Log debug message."""
        ...


# Result data classes for type safety
class SearchResult:
    """Data class for search results."""

    def __init__(
        self,
        result_id: str,
        title: str,
        content: str,
        url: str,
        *,  # Force keyword-only arguments
        relevance_score: float = 0.0,
        ai_summary: str = "",
        metadata: Optional[Dict[str, Any]] = None
    ):
        self.id = result_id
        self.title = title
        self.content = content
        self.url = url
        self.relevance_score = relevance_score
        self.ai_summary = ai_summary
        self.metadata = metadata or {}

    def to_dict(self) -> Dict[str, Any]:
        """Convert to dictionary representation."""
        return {
            "id": self.id,
            "title": self.title,
            "content": self.content,
            "url": self.url,
            "relevance_score": self.relevance_score,
            "ai_summary": self.ai_summary,
            "metadata": self.metadata,
        }


class ProcessingResult:
    """Data class for processing results."""

    def __init__(
        self,
        success: bool,
        message: str = "",
        data: Optional[Any] = None,
        errors: Optional[List[str]] = None,
    ):
        self.success = success
        self.message = message
        self.data = data
        self.errors = errors or []

    def to_dict(self) -> Dict[str, Any]:
        """Convert to dictionary representation."""
        return {
            "success": self.success,
            "message": self.message,
            "data": self.data,
            "errors": self.errors,
        }
