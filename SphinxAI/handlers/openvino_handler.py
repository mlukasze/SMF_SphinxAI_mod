#!/usr/bin/env python3
"""
OpenVINO Model Handler with Deep Learning Functions.

Handles loading and running OpenVINO models for AI inference,
text processing, embedding generation, and summarization.
"""

import logging
from pathlib import Path
from typing import Any, Dict, List, Optional

import numpy as np
from numpy.typing import NDArray

from ..core.interfaces import AIHandler
from ..core.constants import POLISH_STOPWORDS
from ..utils.text_processing import normalize_polish_text

logger = logging.getLogger(__name__)

try:
    # OpenVINO imports
    from openvino import CompiledModel, Core, Model
    OPENVINO_AVAILABLE = True
except ImportError:
    logger.warning("OpenVINO not available")
    OPENVINO_AVAILABLE = False

try:
    # Deep learning imports
    import nltk
    from nltk.tokenize import sent_tokenize
    from sentence_transformers import SentenceTransformer
    from sklearn.feature_extraction.text import TfidfVectorizer
    from sklearn.metrics.pairwise import cosine_similarity
    DEEP_LEARNING_AVAILABLE = True
except ImportError:
    logger.warning("Deep learning libraries not available")
    DEEP_LEARNING_AVAILABLE = False

# Model configuration constants
DEFAULT_MODEL_NAME = 'OpenVINO/paraphrase-multilingual-mpnet-base-v2'


class OpenVINOHandler(AIHandler):
    """OpenVINO model handler for AI inference and text processing."""
    
    def __init__(self, model_path: Optional[str] = None, device: str = "CPU"):
        """Initialize OpenVINO handler.
        
        Args:
            model_path: Path to the OpenVINO model
            device: Target device for inference (CPU, GPU, etc.)
        """
        self.model_path = Path(model_path) if model_path else None
        self.device = device
        self.core = None
        self.model = None
        self.compiled_model = None
        self.embedding_model = None
        self.tfidf_vectorizer = None
        
        if not OPENVINO_AVAILABLE:
            logger.error("OpenVINO not available - handler will be limited")
            return
            
        try:
            self.core = Core()
            if self.model_path and self.model_path.exists():
                self._load_model()
        except Exception as e:
            logger.error(f"Failed to initialize OpenVINO handler: {e}")
    
    def _load_model(self) -> bool:
        """Load OpenVINO model.
        
        Returns:
            True if model loaded successfully, False otherwise
        """
        try:
            if not self.model_path or not self.model_path.exists():
                logger.error(f"Model path does not exist: {self.model_path}")
                return False
                
            self.model = self.core.read_model(str(self.model_path))
            self.compiled_model = self.core.compile_model(self.model, self.device)
            logger.info(f"OpenVINO model loaded: {self.model_path}")
            return True
        except Exception as e:
            logger.error(f"Failed to load OpenVINO model: {e}")
            return False
    
    def _load_embedding_model(self) -> bool:
        """Load sentence transformer model for embeddings.
        
        Returns:
            True if model loaded successfully, False otherwise
        """
        if not DEEP_LEARNING_AVAILABLE:
            logger.warning("Deep learning libraries not available for embeddings")
            return False
            
        try:
            self.embedding_model = SentenceTransformer(DEFAULT_MODEL_NAME)
            logger.info("Embedding model loaded successfully")
            return True
        except Exception as e:
            logger.error(f"Failed to load embedding model: {e}")
            return False
    
    def generate_embeddings(self, texts: List[str]) -> Optional[NDArray]:
        """Generate embeddings for list of texts.
        
        Args:
            texts: List of text strings to embed
            
        Returns:
            NumPy array of embeddings or None if failed
        """
        if not self.embedding_model and not self._load_embedding_model():
            return None
            
        try:
            # Preprocess texts
            processed_texts = []
            for text in texts:
                normalized = normalize_polish_text(text)
                without_stopwords = remove_stopwords(normalized, POLISH_STOPWORDS)
                processed_texts.append(without_stopwords)
            
            embeddings = self.embedding_model.encode(processed_texts)
            return embeddings
        except Exception as e:
            logger.error(f"Failed to generate embeddings: {e}")
            return None
    
    def compute_similarity(self, query_embedding: NDArray, document_embeddings: NDArray) -> NDArray:
        """Compute cosine similarity between query and documents.
        
        Args:
            query_embedding: Query embedding vector
            document_embeddings: Document embedding matrix
            
        Returns:
            Similarity scores array
        """
        try:
            if query_embedding.ndim == 1:
                query_embedding = query_embedding.reshape(1, -1)
            
            similarities = cosine_similarity(query_embedding, document_embeddings)
            return similarities.flatten()
        except Exception as e:
            logger.error(f"Failed to compute similarity: {e}")
            return np.array([])
    
    def summarize_text(self, text: str, max_length: int = 150) -> str:
        """Summarize text using TF-IDF and sentence ranking.
        
        Args:
            text: Text to summarize
            max_length: Maximum length of summary
            
        Returns:
            Summarized text
        """
        try:
            # Tokenize into sentences
            sentences = sent_tokenize(text)
            if len(sentences) <= 2:
                return text
            
            # Preprocess sentences
            processed_sentences = []
            for sentence in sentences:
                normalized = normalize_polish_text(sentence)
                without_stopwords = remove_stopwords(normalized, POLISH_STOPWORDS)
                processed_sentences.append(without_stopwords)
            
            # Use TF-IDF to rank sentences
            if not self.tfidf_vectorizer:
                self.tfidf_vectorizer = TfidfVectorizer(max_features=1000)
            
            tfidf_matrix = self.tfidf_vectorizer.fit_transform(processed_sentences)
            sentence_scores = np.sum(tfidf_matrix.toarray(), axis=1)
            
            # Select top sentences
            top_indices = np.argsort(sentence_scores)[-3:][::-1]
            summary_sentences = [sentences[i] for i in sorted(top_indices)]
            
            summary = ' '.join(summary_sentences)
            
            # Truncate if too long
            if len(summary) > max_length:
                summary = summary[:max_length].rsplit(' ', 1)[0] + '...'
            
            return summary
        except Exception as e:
            logger.error(f"Failed to summarize text: {e}")
            return text[:max_length] + '...' if len(text) > max_length else text
    
    def process_query(self, query: str, context: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        """Process search query with AI enhancement.
        
        Args:
            query: Search query text
            context: Additional context for processing
            
        Returns:
            Dictionary with processed query and metadata
        """
        try:
            # Normalize and clean query
            normalized_query = normalize_polish_text(query)
            clean_query = remove_stopwords(normalized_query, POLISH_STOPWORDS)
            
            # Generate query embedding if model available
            query_embedding = None
            if self.embedding_model or self._load_embedding_model():
                embeddings = self.generate_embeddings([clean_query])
                if embeddings is not None:
                    query_embedding = embeddings[0]
            
            return {
                'original_query': query,
                'normalized_query': normalized_query,
                'clean_query': clean_query,
                'embedding': query_embedding.tolist() if query_embedding is not None else None,
                'context': context or {}
            }
        except Exception as e:
            logger.error(f"Failed to process query: {e}")
            return {
                'original_query': query,
                'normalized_query': query,
                'clean_query': query,
                'embedding': None,
                'context': context or {}
            }
    
    def enhance_results(self, results: List[Dict[str, Any]], query: str) -> List[Dict[str, Any]]:
        """Enhance search results with AI processing.
        
        Args:
            results: List of search result dictionaries
            query: Original search query
            
        Returns:
            Enhanced results with AI-generated summaries and relevance scores
        """
        try:
            enhanced_results = []
            query_data = self.process_query(query)
            query_embedding = query_data.get('embedding')
            
            for result in results:
                enhanced_result = result.copy()
                
                # Generate summary for result content
                content = result.get('content', result.get('body', ''))
                if content:
                    summary = self.summarize_text(content)
                    enhanced_result['ai_summary'] = summary
                
                # Compute AI relevance score if embeddings available
                if query_embedding and content:
                    content_embeddings = self.generate_embeddings([content])
                    if content_embeddings is not None:
                        similarity = self.compute_similarity(
                            np.array(query_embedding), 
                            content_embeddings
                        )
                        enhanced_result['ai_relevance'] = float(similarity[0])
                
                enhanced_results.append(enhanced_result)
            
            # Sort by AI relevance if available
            if any('ai_relevance' in r for r in enhanced_results):
                enhanced_results.sort(key=lambda x: x.get('ai_relevance', 0), reverse=True)
            
            return enhanced_results
        except Exception as e:
            logger.error(f"Failed to enhance results: {e}")
            return results
    
    def is_available(self) -> bool:
        """Check if OpenVINO handler is available and functional.
        
        Returns:
            True if handler is available, False otherwise
        """
        return OPENVINO_AVAILABLE and self.core is not None
    
    def get_status(self) -> Dict[str, Any]:
        """Get current status of the handler.
        
        Returns:
            Status dictionary with availability and model info
        """
        return {
            'available': self.is_available(),
            'openvino_available': OPENVINO_AVAILABLE,
            'deep_learning_available': DEEP_LEARNING_AVAILABLE,
            'model_loaded': self.compiled_model is not None,
            'embedding_model_loaded': self.embedding_model is not None,
            'device': self.device,
            'model_path': str(self.model_path) if self.model_path else None
        }
