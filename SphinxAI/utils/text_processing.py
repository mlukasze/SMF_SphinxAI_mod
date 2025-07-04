#!/usr/bin/env python3
"""
Text processing utilities for Polish language support.

This module provides utilities for processing Polish text, including
stopword removal, diacritics normalization, and text cleaning.
"""

import re
import logging
from typing import List, Optional, Set

logger = logging.getLogger(__name__)

# Import constants from core module
try:
    from ..core.constants import POLISH_STOPWORDS, POLISH_DIACRITICS_MAP, REGEX_PATTERNS
except ImportError:
    # Fallback if core module is not available
    POLISH_STOPWORDS = set()
    POLISH_DIACRITICS_MAP = {}
    REGEX_PATTERNS = {
        "url": r'https?://[^\s<>"{}|\\^`[\]]+',
        "email": r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b',
        "bbcode": r'\[/?[a-zA-Z][a-zA-Z0-9]*(?:\s[^\]]*)?]',
        "html_tags": r'<[^>]+>',
        "whitespace": r'\s+',
        "non_alphanum": r'[^\w\s]'
    }


class PolishTextProcessor:
    """Polish text processing utilities following Single Responsibility Principle."""

    def __init__(self, stopwords: Optional[Set[str]] = None):
        """
        Initialize text processor.

        Args:
            stopwords: Custom stopwords set, uses default if None
        """
        self.stopwords = stopwords or POLISH_STOPWORDS
        self._compile_patterns()

    def _compile_patterns(self) -> None:
        """Compile regex patterns for better performance."""
        self.url_pattern = re.compile(REGEX_PATTERNS["url"])
        self.email_pattern = re.compile(REGEX_PATTERNS["email"])
        self.bbcode_pattern = re.compile(REGEX_PATTERNS["bbcode"])
        self.html_pattern = re.compile(REGEX_PATTERNS["html_tags"])
        self.whitespace_pattern = re.compile(REGEX_PATTERNS["whitespace"])
        self.non_alphanum_pattern = re.compile(REGEX_PATTERNS["non_alphanum"])

    def normalize_diacritics(self, text: str) -> str:
        """
        Normalize Polish diacritics.

        Args:
            text: Input text with Polish characters

        Returns:
            Text with normalized characters
        """
        if not text:
            return ""

        result = text
        for polish_char, latin_char in POLISH_DIACRITICS_MAP.items():
            result = result.replace(polish_char, latin_char)

        return result

    def remove_stopwords(self, words: List[str]) -> List[str]:
        """
        Remove Polish stopwords from word list.

        Args:
            words: List of words

        Returns:
            Filtered word list without stopwords
        """
        return [word for word in words if word.lower() not in self.stopwords]

    def clean_forum_content(self, text: str) -> str:
        """
        Clean forum content by removing BBCode, HTML, URLs, etc.

        Args:
            text: Raw forum content

        Returns:
            Cleaned text
        """
        if not text:
            return ""

        # Remove BBCode
        text = self.bbcode_pattern.sub('', text)

        # Remove HTML tags
        text = self.html_pattern.sub('', text)

        # Remove URLs and emails
        text = self.url_pattern.sub('', text)
        text = self.email_pattern.sub('', text)

        # Normalize whitespace
        text = self.whitespace_pattern.sub(' ', text)

        return text.strip()

    def preprocess_text(self, text: str, normalize_diacritics: bool = True) -> str:
        """
        Complete text preprocessing pipeline.

        Args:
            text: Input text
            normalize_diacritics: Whether to normalize Polish characters

        Returns:
            Preprocessed text
        """
        if not text:
            return ""

        # Clean forum content
        text = self.clean_forum_content(text)

        # Normalize diacritics if requested
        if normalize_diacritics:
            text = self.normalize_diacritics(text)

        # Convert to lowercase
        text = text.lower()

        # Remove extra whitespace
        text = self.whitespace_pattern.sub(' ', text).strip()

        return text

    def extract_keywords(self, text: str, min_length: int = 3) -> List[str]:
        """
        Extract keywords from text.

        Args:
            text: Input text
            min_length: Minimum keyword length

        Returns:
            List of extracted keywords
        """
        if not text:
            return []

        # Preprocess text
        processed_text = self.preprocess_text(text)

        # Split into words
        words = processed_text.split()

        # Filter by length and remove stopwords
        keywords = [
            word for word in words
            if len(word) >= min_length and word not in self.stopwords
        ]

        return keywords

    def create_search_terms(self, query: str) -> List[str]:
        """
        Create search terms from user query.

        Args:
            query: User search query

        Returns:
            List of search terms
        """
        if not query:
            return []

        # Extract keywords
        keywords = self.extract_keywords(query)

        # Add original query
        search_terms = [query.strip()]

        # Add processed keywords
        search_terms.extend(keywords)

        # Remove duplicates while preserving order
        seen = set()
        unique_terms = []
        for term in search_terms:
            if term and term not in seen:
                seen.add(term)
                unique_terms.append(term)

        return unique_terms


class TextChunker:
    """Text chunking utility for handling long content."""

    def __init__(self, max_chunk_size: int = 1000, overlap: int = 100):
        """
        Initialize text chunker.

        Args:
            max_chunk_size: Maximum chunk size in characters
            overlap: Overlap between chunks in characters
        """
        self.max_chunk_size = max_chunk_size
        self.overlap = overlap

    def chunk_text(self, text: str) -> List[str]:
        """
        Split text into chunks with overlap.

        Args:
            text: Input text

        Returns:
            List of text chunks
        """
        if not text or len(text) <= self.max_chunk_size:
            return [text] if text else []

        chunks = []
        start = 0

        while start < len(text):
            end = start + self.max_chunk_size

            # If we're not at the end, try to find a sentence boundary
            if end < len(text):
                # Look for sentence endings within the last 200 characters
                search_start = max(end - 200, start)
                sentence_end = self._find_sentence_boundary(text, search_start, end)
                if sentence_end > start:
                    end = sentence_end

            chunk = text[start:end].strip()
            if chunk:
                chunks.append(chunk)

            # Move start position with overlap
            start = max(end - self.overlap, start + 1)

            # Prevent infinite loop
            if start >= len(text):
                break

        return chunks

    def _find_sentence_boundary(self, text: str, start: int, end: int) -> int:
        """Find the best sentence boundary within range."""
        sentence_endings = ['.', '!', '?', '\n']

        for i in range(end - 1, start - 1, -1):
            if text[i] in sentence_endings:
                # Make sure it's not a decimal number
                if text[i] == '.' and 0 < i < len(text) - 1:
                    if text[i-1].isdigit() and text[i+1].isdigit():
                        continue
                return i + 1

        return end


def create_text_processor(stopwords: Optional[Set[str]] = None) -> PolishTextProcessor:
    """
    Factory function to create text processor instance.

    Args:
        stopwords: Custom stopwords set

    Returns:
        Configured text processor
    """
    return PolishTextProcessor(stopwords)


def create_chunker(max_chunk_size: int = 1000, overlap: int = 100) -> TextChunker:
    """
    Factory function to create text chunker instance.

    Args:
        max_chunk_size: Maximum chunk size
        overlap: Overlap between chunks

    Returns:
        Configured text chunker
    """
    return TextChunker(max_chunk_size, overlap)

# Standalone utility functions for backward compatibility

class _DefaultProcessor:
    """Singleton pattern for default processor to avoid global statement."""
    _instance = None
    
    @classmethod
    def get_instance(cls) -> PolishTextProcessor:
        """Get default text processor instance."""
        if cls._instance is None:
            cls._instance = PolishTextProcessor()
        return cls._instance

def get_default_processor() -> PolishTextProcessor:
    """Get default text processor instance."""
    return _DefaultProcessor.get_instance()


def normalize_polish_text(text: str) -> str:
    """
    Normalize Polish text using default processor.

    Args:
        text: Input text with Polish characters

    Returns:
        Normalized text
    """
    processor = get_default_processor()
    return processor.preprocess_text(text, normalize_diacritics=True)


def remove_stopwords(words: List[str]) -> List[str]:
    """
    Remove Polish stopwords from word list.

    Args:
        words: List of words

    Returns:
        Filtered word list without stopwords
    """
    processor = get_default_processor()
    return processor.remove_stopwords(words)


def clean_forum_content(text: str) -> str:
    """
    Clean forum content by removing BBCode, HTML, URLs, etc.

    Args:
        text: Raw forum content

    Returns:
        Cleaned text
    """
    processor = get_default_processor()
    return processor.clean_forum_content(text)
